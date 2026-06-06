<?php
/**
 * Cloud Phonebook — ModulePhoneBookSyncConf v1.1.9
 * Registreert publieke REST routes conform MikoPBX documentatie formaat
 */
namespace Modules\ModulePhoneBookSync\Lib;

use MikoPBX\Modules\Config\ConfigClass;
use MikoPBX\Common\Models\PbxSettings;

class ModulePhoneBookSyncConf extends ConfigClass
{
    /**
     * Registreert extra REST routes.
     * Formaat per route: [ControllerClass, 'actionMethod', '/uri', 'httpmethod', '/', allowNoAuth]
     * allowNoAuth = false = geen authenticatie vereist (publiek toegankelijk)
     *
     * @return array
     */
    public function getPBXCoreRESTAdditionalRoutes(): array
    {
        return [
            // Publieke phonebook endpoint voor telefoontoestellen — geen auth vereist
            [
                ApiController::class,
                'getPublicContacts',
                '/pbxcore/api/phonebooksync/contacts',
                'get',
                '/',
                false,
            ],
            // XML variant voor Yealink/Fanvil toestellen
            [
                ApiController::class,
                'getPublicContacts',
                '/pbxcore/api/phonebooksync/contacts.xml',
                'get',
                '/',
                false,
            ],
        ];
    }

    /**
     * Laad vertalingen op basis van PBXLanguage instelling
     */
    public static function getTranslations(): array
    {
        $langMap = [
            'nl'=>'nl','en'=>'en','de'=>'de','fr'=>'fr','ru'=>'ru',
            'be'=>'ru','uk'=>'ru','kk'=>'ru',
        ];
        $pbxLang   = strtolower(substr(PbxSettings::getValueByKey('PBXLanguage') ?? 'en-gb', 0, 2));
        $lang      = $langMap[$pbxLang] ?? 'en';
        $moduleDir = dirname(__DIR__);
        $file      = $moduleDir . '/Messages/' . $lang . '.php';
        $fallback  = $moduleDir . '/Messages/en.php';
        $result    = file_exists($file) ? require $file : (file_exists($fallback) ? require $fallback : []);
        return is_array($result) ? $result : [];
    }

    /**
     * Geeft alle contacten terug (intern + extern) voor CallerID en API
     */
    public static function getAllContacts(): array
    {
        $internal = self::getInternalExtensions();
        $external = self::getExternalContacts();
        return array_merge($internal, $external);
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
                    'id'         => 'int_' . $ext->id,
                    'name'       => $ext->callerid ?: 'Extension ' . $ext->number,
                    'number'     => $ext->number,
                    'department' => '',
                    'category'   => '',
                    'type'       => 'internal',
                    'readonly'   => true,
                ];
            }
        } catch (\Throwable $e) {
            error_log('[ModulePhoneBookSync] getInternalExtensions error: ' . $e->getMessage());
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
            error_log('[ModulePhoneBookSync] getExternalContacts error: ' . $e->getMessage());
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
            error_log('[ModulePhoneBookSync] CallerID sync error: ' . $e->getMessage());
            return false;
        }
    }
}
