<?php
/**
 * Cloud Phonebook — ModulePhoneBookSyncConf v1.2.0
 * Route formaat exact conform ModuleAutoprovision:
 * [ControllerClass, 'method', '/uri', 'httpmethod', '/', true]
 * true = publiek toegankelijk (geen auth)
 */
namespace Modules\ModulePhoneBookSync\Lib;

use MikoPBX\Modules\Config\ConfigClass;
use MikoPBX\Common\Models\PbxSettings;
use Modules\ModulePhoneBookSync\Lib\RestAPI\Controllers\GetController;

class ModulePhoneBookSyncConf extends ConfigClass
{
    /**
     * Registreert publieke REST routes — formaat conform AutoprovisionConf
     */
    public function getPBXCoreRESTAdditionalRoutes(): array
    {
        return [
            [GetController::class, 'getContacts', '/pbxcore/api/phonebooksync/contacts',     'get', '/', true],
            [GetController::class, 'getContacts', '/pbxcore/api/phonebooksync/contacts.xml',  'get', '/', true],
        ];
    }

    /**
     * Geeft alle contacten terug (intern + extern)
     */
    public static function getAllContacts(): array
    {
        return array_merge(
            self::getInternalExtensions(),
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
                    'readonly' => true,
                ];
            }
        } catch (\Throwable $e) {
            error_log('[ModulePhoneBookSync] getInternalExtensions: ' . $e->getMessage());
        }
        return $result;
    }

    public static function getExternalContacts(): array
    {
        $result = [];
        try {
            $contacts = \Modules\ModulePhoneBookSync\Models\PhoneBookSyncContact::find(['order' => 'name ASC']);
            foreach ($contacts as $c) {
                $result[] = [
                    'id'         => $c->id,
                    'name'       => $c->name,
                    'number'     => $c->number,
                    'department' => $c->department ?? '',
                    'category'   => $c->category ?? '',
                    'type'       => 'external',
                    'readonly'   => false,
                ];
            }
        } catch (\Throwable $e) {
            error_log('[ModulePhoneBookSync] getExternalContacts: ' . $e->getMessage());
        }
        return $result;
    }

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
