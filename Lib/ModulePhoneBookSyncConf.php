<?php
/**
 * Cloud Phonebook — ModulePhoneBookSyncConf
 * v1.1.8
 * Registreert publieke (unauthenticated) API endpoints voor telefoontoestellen
 */
namespace Modules\ModulePhoneBookSync\Lib;

use MikoPBX\Modules\Config\ConfigClass;
use MikoPBX\Common\Models\PbxSettings;

class ModulePhoneBookSyncConf extends ConfigClass
{
    /**
     * Registreert endpoints die ZONDER authenticatie toegankelijk zijn.
     * MikoPBX roept deze methode aan bij het opbouwen van de routing tabel.
     * Zelfde mechanisme als ModuleAutoprovision gebruikt voor /phonebook.
     *
     * @return array
     */
    public function getPBXCoreRESTAdditionalRoutes(): array
    {
        return [
            // Publieke phonebook endpoint voor telefoontoestellen
            [
                'methodName'   => 'getPublicContacts',
                'uri'          => '/pbxcore/api/phonebooksync/contacts',
                'call'         => 'Modules\ModulePhoneBookSync\Lib\ApiController::getPublicContacts',
                'httpMethod'   => 'GET',
                'allowNoAuth'  => true,
            ],
            // Authenticated endpoints voor de web UI
            [
                'methodName'   => 'getContacts',
                'uri'          => '/pbxcore/api/modules/ModulePhoneBookSync/contacts',
                'call'         => 'Modules\ModulePhoneBookSync\Lib\ApiController::getContacts',
                'httpMethod'   => 'GET',
                'allowNoAuth'  => false,
            ],
            [
                'methodName'   => 'saveContact',
                'uri'          => '/pbxcore/api/modules/ModulePhoneBookSync/contacts',
                'call'         => 'Modules\ModulePhoneBookSync\Lib\ApiController::saveContact',
                'httpMethod'   => 'POST',
                'allowNoAuth'  => false,
            ],
            [
                'methodName'   => 'updateContact',
                'uri'          => '/pbxcore/api/modules/ModulePhoneBookSync/contacts/{id}',
                'call'         => 'Modules\ModulePhoneBookSync\Lib\ApiController::updateContact',
                'httpMethod'   => 'PUT',
                'allowNoAuth'  => false,
            ],
            [
                'methodName'   => 'deleteContact',
                'uri'          => '/pbxcore/api/modules/ModulePhoneBookSync/contacts/{id}',
                'call'         => 'Modules\ModulePhoneBookSync\Lib\ApiController::deleteContact',
                'httpMethod'   => 'DELETE',
                'allowNoAuth'  => false,
            ],
            [
                'methodName'   => 'syncCallerID',
                'uri'          => '/pbxcore/api/modules/ModulePhoneBookSync/sync-callerid',
                'call'         => 'Modules\ModulePhoneBookSync\Lib\ApiController::syncCallerID',
                'httpMethod'   => 'POST',
                'allowNoAuth'  => false,
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
        $pbxLang  = strtolower(substr(PbxSettings::getValueByKey('PBXLanguage') ?? 'en-gb', 0, 2));
        $lang     = $langMap[$pbxLang] ?? 'en';
        $moduleDir = dirname(__DIR__);
        $file     = $moduleDir . '/Messages/' . $lang . '.php';
        $fallback  = $moduleDir . '/Messages/en.php';
        if (file_exists($file)) {
            $result = require $file;
        } elseif (file_exists($fallback)) {
            $result = require $fallback;
        } else {
            $result = [];
        }
        return is_array($result) ? $result : [];
    }
}
