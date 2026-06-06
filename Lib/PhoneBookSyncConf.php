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
