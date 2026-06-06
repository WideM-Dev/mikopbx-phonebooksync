<?php
/**
 * Cloud Phonebook — PhoneBookSyncConf v1.2.7
 * Leest contacten uit 3 bronnen:
 * 1. Interne toestellen (PBX Extensions tabel)
 * 2. Externe contacten (eigen DB: m_PhoneBookSyncContacts)
 * 3. ModulePhoneBook contacten (ingebouwde module: m_PhoneBook)
 */
namespace Modules\ModulePhoneBookSync\Lib;

use MikoPBX\Modules\Config\ConfigClass;
use MikoPBX\Common\Models\PbxSettings;
use Modules\ModulePhoneBookSync\Lib\RestAPI\Controllers\GetController;

class PhoneBookSyncConf extends ConfigClass
{
    private const MODULE_PHONEBOOK_DB =
        '/storage/usbdisk1/mikopbx/custom_modules/ModulePhoneBook/db/module.db';

    /**
     * Registreert publieke REST routes conform ModuleAutoprovision patroon
     */
    public function getPBXCoreRESTAdditionalRoutes(): array
    {
        return [
            [GetController::class, 'getContacts', '/pbxcore/api/phonebooksync/contacts',    'get', '/', true],
            [GetController::class, 'getContacts', '/pbxcore/api/phonebooksync/contacts.xml', 'get', '/', true],
        ];
    }

    /**
     * Geeft alle contacten terug uit alle bronnen
     */
    public static function getAllContacts(): array
    {
        return array_merge(
            self::getInternalExtensions(),
            self::getModulePhoneBookContacts(),
            self::getExternalContacts()
        );
    }

    /**
     * Bron 1: Interne toestellen direct uit PBX Extensions tabel
     */
    public static function getInternalExtensions(): array
    {
        $result = [];
        try {
            $extensions = \MikoPBX\Common\Models\Extensions::find([
                'conditions' => 'type = "SIP" OR type = "VIRTUAL"',
                'order'      => 'number ASC',
            ]);
            foreach ($extensions as $ext) {
                $result[] = [
                    'id'       => 'int_' . $ext->id,
                    'name'     => $ext->callerid ?: 'Extension ' . $ext->number,
                    'number'   => $ext->number,
                    'type'     => 'internal',
                    'source'   => 'pbx',
                    'readonly' => true,
                ];
            }
        } catch (\Throwable $e) {
            error_log('[ModulePhoneBookSync] getInternalExtensions: ' . $e->getMessage());
        }
        return $result;
    }

    /**
     * Bron 2: Contacten uit de ingebouwde ModulePhoneBook (m_PhoneBook)
     * Alleen als die module geïnstalleerd en de DB beschikbaar is
     */
    public static function getModulePhoneBookContacts(): array
    {
        $result = [];
        $dbPath = self::MODULE_PHONEBOOK_DB;

        if (!file_exists($dbPath)) {
            return $result;
        }

        try {
            $db = new \SQLite3($dbPath, SQLITE3_OPEN_READONLY);
            $stmt = $db->prepare(
                'SELECT id, number_rep, call_id FROM m_PhoneBook
                 WHERE call_id IS NOT NULL AND call_id != ""
                 AND number_rep IS NOT NULL AND number_rep != ""
                 ORDER BY call_id ASC'
            );
            $rows = $stmt->execute();
            while ($row = $rows->fetchArray(SQLITE3_ASSOC)) {
                // Normaliseer nummer: verwijder streepjes en spaties
                // +31-50-205-0400 → +31502050400
                $number = preg_replace('/[\s\-]/', '', $row['number_rep']);
                $result[] = [
                    'id'       => 'mpb_' . $row['id'],
                    'name'     => $row['call_id'],
                    'number'   => $number,
                    'type'     => 'external',
                    'source'   => 'modulephonebook',
                    'readonly' => false,
                ];
            }
            $db->close();
        } catch (\Throwable $e) {
            error_log('[ModulePhoneBookSync] getModulePhoneBookContacts: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Bron 3: Eigen externe contacten (m_PhoneBookSyncContacts)
     */
    public static function getExternalContacts(): array
    {
        $result = [];
        try {
            $contacts = \Modules\ModulePhoneBookSync\Models\PhoneBookSyncContact::find(
                ['order' => 'name ASC']
            );
            foreach ($contacts as $c) {
                $result[] = [
                    'id'         => $c->id,
                    'name'       => $c->name,
                    'number'     => $c->number,
                    'department' => $c->department ?? '',
                    'category'   => $c->category   ?? '',
                    'type'       => 'external',
                    'source'     => 'cloudphonebook',
                    'readonly'   => false,
                ];
            }
        } catch (\Throwable $e) {
            error_log('[ModulePhoneBookSync] getExternalContacts: ' . $e->getMessage());
        }
        return $result;
    }

    /**
     * Synchroniseer alle contacten naar pb_PhoneBook voor CallerID
     */
    public static function syncToCallerID(): bool
    {
        try {
            $db = \Phalcon\Di\Di::getDefault()->get('db');
            $db->execute("DELETE FROM pb_PhoneBook WHERE source = 'ModulePhoneBookSync'");
            foreach (self::getAllContacts() as $contact) {
                $number = preg_replace('/[\s\-\(\)]/', '', $contact['number'] ?? '');
                if (empty($number)) continue;
                $db->execute(
                    "INSERT OR REPLACE INTO pb_PhoneBook (number, call_id, source) VALUES (?, ?, ?)",
                    [$number, $contact['name'], 'ModulePhoneBookSync']
                );
            }
            return true;
        } catch (\Throwable $e) {
            error_log('[ModulePhoneBookSync] CallerID sync: ' . $e->getMessage());
            return false;
        }
    }

    public static function getTranslations(): array
    {
        $langMap   = ['nl'=>'nl','en'=>'en','de'=>'de','fr'=>'fr','ru'=>'ru','be'=>'ru','uk'=>'ru','kk'=>'ru'];
        $pbxLang   = strtolower(substr(PbxSettings::getValueByKey('PBXLanguage') ?? 'en-gb', 0, 2));
        $lang      = $langMap[$pbxLang] ?? 'en';
        $moduleDir = dirname(__DIR__);
        $file      = $moduleDir . '/Messages/' . $lang . '.php';
        $fallback  = $moduleDir . '/Messages/en.php';
        $result    = file_exists($file) ? require $file : (file_exists($fallback) ? require $fallback : []);
        return is_array($result) ? $result : [];
    }
}

    // ------------------------------------------------------------------
    // Legacy API callback — gebruikt door /pbxcore/api/modules/{module}/{action}
    // ------------------------------------------------------------------
    public function moduleRestAPICallback(array $request): \MikoPBX\PBXCoreREST\Lib\PBXApiResult
    {
        $res = new \MikoPBX\PBXCoreREST\Lib\PBXApiResult();
        $res->processor = __METHOD__;

        $action = strtoupper($request['action'] ?? '');
        $data   = $request['data'] ?? [];
        $id     = $data['id'] ?? $request['id'] ?? null;

        switch ($action) {
            case 'CONTACTS':
                // GET /pbxcore/api/modules/ModulePhoneBookSync/contacts
                $res->success = true;
                $res->data['contacts'] = self::getAllContacts();
                $res->data['version']  = '1.4.0';
                break;

            case 'SAVECONTACT':
                // POST /pbxcore/api/modules/ModulePhoneBookSync/saveContact
                $name   = trim($data['name'] ?? '');
                $number = trim($data['number'] ?? '');
                if (!$name)   { $res->success = false; $res->messages[] = 'name_required'; break; }
                if (!$number) { $res->success = false; $res->messages[] = 'number_required'; break; }
                $clean = preg_replace('/[\s\-\(\)]/', '', $number);
                if (!preg_match('/^[\+\d]{6,20}$/', $clean)) {
                    $res->success = false; $res->messages[] = 'number_invalid'; break;
                }
                $exists = \Modules\ModulePhoneBookSync\Models\PhoneBookSyncContact::findFirst([
                    'conditions' => 'number = :number:', 'bind' => ['number' => $number]
                ]);
                if ($exists) { $res->success = false; $res->messages[] = 'duplicate'; break; }
                $contact = new \Modules\ModulePhoneBookSync\Models\PhoneBookSyncContact();
                $contact->name       = $name;
                $contact->number     = $number;
                $contact->department = trim($data['department'] ?? '');
                $contact->category   = trim($data['category']   ?? '');
                $contact->notes      = trim($data['notes']      ?? '');
                if ($contact->save()) {
                    self::syncToCallerID();
                    $res->success = true;
                    $res->data['id'] = $contact->id;
                } else {
                    $res->success = false;
                    $res->messages = $contact->getMessages();
                }
                break;

            case 'UPDATECONTACT':
                $contact = \Modules\ModulePhoneBookSync\Models\PhoneBookSyncContact::findFirstById($id);
                if (!$contact) { $res->success = false; $res->messages[] = 'not_found'; break; }
                if (isset($data['name']))       $contact->name       = trim($data['name']);
                if (isset($data['number']))     $contact->number     = trim($data['number']);
                if (isset($data['department'])) $contact->department = trim($data['department']);
                if (isset($data['category']))   $contact->category   = trim($data['category']);
                if ($contact->save()) { self::syncToCallerID(); $res->success = true; }
                else { $res->success = false; $res->messages = $contact->getMessages(); }
                break;

            case 'DELETECONTACT':
                $contact = \Modules\ModulePhoneBookSync\Models\PhoneBookSyncContact::findFirstById($id);
                if (!$contact) { $res->success = false; $res->messages[] = 'not_found'; break; }
                if ($contact->delete()) { self::syncToCallerID(); $res->success = true; }
                else { $res->success = false; $res->messages = $contact->getMessages(); }
                break;

            case 'SYNC-CALLERID':
            case 'SYNCCALLERID':
                $res->success = self::syncToCallerID();
                if (!$res->success) $res->messages[] = 'callerid_sync_fail';
                break;

            case 'EXPORT-CSV':
            case 'EXPORTCSV':
                $res->success = true;
                $res->data['csv'] = self::exportToCsv();
                break;

            case 'IMPORT-CSV':
            case 'IMPORTCSV':
                $csvData = $data['csv'] ?? '';
                $result  = self::importFromCsv($csvData);
                if ($result['imported'] > 0) self::syncToCallerID();
                $res->success = true;
                $res->data['imported'] = $result['imported'];
                $res->data['errors']   = $result['errors'];
                break;

            default:
                $res->success  = false;
                $res->messages = ['Unknown action: ' . $action];
        }
        return $res;
    }

    public static function exportToCsv(): string
    {
        $lines = ['name,number,department,category,notes'];
        $contacts = \Modules\ModulePhoneBookSync\Models\PhoneBookSyncContact::find(['order' => 'name ASC']);
        foreach ($contacts as $c) {
            $lines[] = implode(',', array_map(fn($v) => str_contains($v,',') ? '"'.$v.'"' : $v,
                [$c->name, $c->number, $c->department??'', $c->category??'', $c->notes??'']));
        }
        return implode("\n", $lines);
    }

    public static function importFromCsv(string $csv): array
    {
        $lines = array_filter(explode("\n", trim($csv)));
        $imported = 0; $errors = [];
        foreach ($lines as $i => $line) {
            $cols = str_getcsv($line);
            if ($i === 0 && strtolower($cols[0]??'') === 'name') continue;
            $name = trim($cols[0]??''); $number = trim($cols[1]??'');
            if (!$name || !$number) continue;
            $exists = \Modules\ModulePhoneBookSync\Models\PhoneBookSyncContact::findFirst([
                'conditions' => 'number = :n:', 'bind' => ['n' => $number]
            ]);
            if ($exists) continue;
            $c = new \Modules\ModulePhoneBookSync\Models\PhoneBookSyncContact();
            $c->name = $name; $c->number = $number;
            $c->department = trim($cols[2]??''); $c->category = trim($cols[3]??'');
            if ($c->save()) $imported++; else $errors[] = "Row $i failed";
        }
        return ['imported' => $imported, 'errors' => $errors];
    }
