<?php
/**
 * Cloud Phonebook — PhoneBookSyncConf v1.4.4
 * Herstelt view symlink automatisch na PBX herstart via onAfterPbxStarted hook
 */
namespace Modules\ModulePhoneBookSync\Lib;

use MikoPBX\Modules\Config\ConfigClass;
use MikoPBX\Modules\PbxExtensionUtils;
use MikoPBX\Common\Models\PbxSettings;
use MikoPBX\PBXCoreREST\Lib\PBXApiResult;
use Modules\ModulePhoneBookSync\Lib\RestAPI\Controllers\GetController;
use Modules\ModulePhoneBookSync\Models\PhoneBookSyncContact;

class PhoneBookSyncConf extends ConfigClass
{
    private const MODULE_PHONEBOOK_DB =
        '/storage/usbdisk1/mikopbx/custom_modules/ModulePhoneBook/db/module.db';

    // ── Symlink herstel na herstart ───────────────────────────────────
    /**
     * Wordt aangeroepen na elke PBX herstart.
     * /offload/rootfs/ wordt gereset bij herstart — symlink hier herstellen.
     */
    public function onAfterPbxStarted(): void
    {
        $this->fixViewSymlink();
        PbxExtensionUtils::createAssetsSymlinks($this->moduleUniqueId);
    }

    /**
     * Wordt aangeroepen na activeren van de module.
     */
    public function onAfterModuleEnable(): void
    {
        $this->fixViewSymlink();
        PbxExtensionUtils::createAssetsSymlinks($this->moduleUniqueId);
    }

    /**
     * Maakt de view symlink correct aan.
     * MikoPBX createViewSymlinks() wijst naar App/Views/ maar wij hebben
     * App/Views/ModulePhoneBookSync/ nodig als symlink doel.
     */
    private function fixViewSymlink(): void
    {
        $moduleDir  = PbxExtensionUtils::getModuleDir($this->moduleUniqueId);
        $viewSrc    = $moduleDir . '/App/Views/ModulePhoneBookSync';
        $viewTarget = '/offload/rootfs/usr/www/src/AdminCabinet/Views/Modules/' . $this->moduleUniqueId;
        $parentDir  = dirname($viewTarget);

        // Al correct?
        if (is_link($viewTarget) && readlink($viewTarget) === $viewSrc) {
            return;
        }

        // Verwijder verkeerde symlink/map
        if (is_link($viewTarget)) {
            unlink($viewTarget);
        } elseif (is_dir($viewTarget)) {
            // Laat map staan — niet verwijderen
            return;
        }

        // Maak parent aan indien nodig
        if (!is_dir($parentDir)) {
            mkdir($parentDir, 0755, true);
        }

        // Maak correcte symlink
        if (is_dir($viewSrc)) {
            symlink($viewSrc, $viewTarget);
        }
    }

    // ── Publieke REST routes ──────────────────────────────────────────
    public function getPBXCoreRESTAdditionalRoutes(): array
    {
        return [
            [GetController::class, 'getContacts', '/pbxcore/api/phonebooksync/contacts',    'get', '/', true],
            [GetController::class, 'getContacts', '/pbxcore/api/phonebooksync/contacts.xml', 'get', '/', true],
        ];
    }

    // ── Legacy API callback ───────────────────────────────────────────
    public function moduleRestAPICallback(array $request): PBXApiResult
    {
        $res = new PBXApiResult();
        $res->processor = __METHOD__;

        $action = strtoupper($request['action'] ?? '');
        $data   = $request['data'] ?? [];
        $id     = $data['id'] ?? $request['id'] ?? null;

        switch ($action) {
            case 'CONTACTS':
                $res->success          = true;
                $res->data['contacts'] = self::getAllContacts();
                $res->data['version']  = '1.4.4';
                break;

            case 'SAVECONTACT':
                $name   = trim($data['name']   ?? '');
                $number = trim($data['number'] ?? '');
                if (!$name)   { $res->success = false; $res->messages[] = 'name_required';   break; }
                if (!$number) { $res->success = false; $res->messages[] = 'number_required'; break; }
                $clean = preg_replace('/[\s\-\(\)]/', '', $number);
                if (!preg_match('/^[\+\d]{6,20}$/', $clean)) {
                    $res->success = false; $res->messages[] = 'number_invalid'; break;
                }
                $exists = PhoneBookSyncContact::findFirst([
                    'conditions' => 'number = :number:', 'bind' => ['number' => $number]
                ]);
                if ($exists) { $res->success = false; $res->messages[] = 'duplicate'; break; }
                $contact             = new PhoneBookSyncContact();
                $contact->name       = $name;
                $contact->number     = $number;
                $contact->department = trim($data['department'] ?? '');
                $contact->category   = trim($data['category']   ?? '');
                $contact->notes      = trim($data['notes']      ?? '');
                if ($contact->save()) {
                    self::syncToCallerID();
                    $res->success    = true;
                    $res->data['id'] = $contact->id;
                } else {
                    $res->success  = false;
                    $res->messages = $contact->getMessages();
                }
                break;

            case 'UPDATECONTACT':
                $contact = PhoneBookSyncContact::findFirstById($id);
                if (!$contact) { $res->success = false; $res->messages[] = 'not_found'; break; }
                if (isset($data['name']))       $contact->name       = trim($data['name']);
                if (isset($data['number']))     $contact->number     = trim($data['number']);
                if (isset($data['department'])) $contact->department = trim($data['department']);
                if (isset($data['category']))   $contact->category   = trim($data['category']);
                if ($contact->save()) { self::syncToCallerID(); $res->success = true; }
                else { $res->success = false; $res->messages = $contact->getMessages(); }
                break;

            case 'DELETECONTACT':
                $contact = PhoneBookSyncContact::findFirstById($id);
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
                $res->success     = true;
                $res->data['csv'] = self::exportToCsv();
                break;

            case 'IMPORT-CSV':
            case 'IMPORTCSV':
                $result              = self::importFromCsv($data['csv'] ?? '');
                if ($result['imported'] > 0) self::syncToCallerID();
                $res->success            = true;
                $res->data['imported']   = $result['imported'];
                $res->data['errors']     = $result['errors'];
                break;

            default:
                $res->success    = false;
                $res->messages[] = 'Unknown action: ' . $action;
        }
        return $res;
    }

    // ── Contact bronnen ───────────────────────────────────────────────
    public static function getAllContacts(): array
    {
        return array_merge(
            self::getInternalExtensions(),
            self::getModulePhoneBookContacts(),
            self::getExternalContacts()
        );
    }

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

    public static function getModulePhoneBookContacts(): array
    {
        $result = [];
        $dbPath = self::MODULE_PHONEBOOK_DB;
        if (!file_exists($dbPath)) return $result;
        try {
            $db   = new \SQLite3($dbPath, SQLITE3_OPEN_READONLY);
            $stmt = $db->prepare(
                'SELECT id, number_rep, call_id FROM m_PhoneBook
                 WHERE call_id IS NOT NULL AND call_id != ""
                 AND number_rep IS NOT NULL AND number_rep != ""
                 ORDER BY call_id ASC'
            );
            $rows = $stmt->execute();
            while ($row = $rows->fetchArray(SQLITE3_ASSOC)) {
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

    public static function getExternalContacts(): array
    {
        $result = [];
        try {
            $contacts = PhoneBookSyncContact::find(['order' => 'name ASC']);
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

    // ── CallerID sync ─────────────────────────────────────────────────
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

    // ── CSV helpers ───────────────────────────────────────────────────
    public static function exportToCsv(): string
    {
        $lines    = ['name,number,department,category,notes'];
        $contacts = PhoneBookSyncContact::find(['order' => 'name ASC']);
        foreach ($contacts as $c) {
            $vals    = [$c->name, $c->number, $c->department??'', $c->category??'', $c->notes??''];
            $lines[] = implode(',', array_map(
                fn($v) => str_contains((string)$v, ',') ? '"' . $v . '"' : (string)$v,
                $vals
            ));
        }
        return implode("\n", $lines);
    }

    public static function importFromCsv(string $csv): array
    {
        $lines    = array_filter(explode("\n", trim($csv)));
        $imported = 0;
        $errors   = [];
        foreach ($lines as $i => $line) {
            $cols   = str_getcsv($line);
            $name   = trim($cols[0] ?? '');
            $number = trim($cols[1] ?? '');
            if ($i === 0 && strtolower($name) === 'name') continue;
            if (!$name || !$number) continue;
            $exists = PhoneBookSyncContact::findFirst([
                'conditions' => 'number = :n:', 'bind' => ['n' => $number]
            ]);
            if ($exists) continue;
            $c             = new PhoneBookSyncContact();
            $c->name       = $name;
            $c->number     = $number;
            $c->department = trim($cols[2] ?? '');
            $c->category   = trim($cols[3] ?? '');
            if ($c->save()) $imported++;
            else $errors[] = "Row $i failed";
        }
        return ['imported' => $imported, 'errors' => $errors];
    }

    // ── Vertalingen ───────────────────────────────────────────────────
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
