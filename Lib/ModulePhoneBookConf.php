<?php
/**
 * Cloud Phonebook v1.1.4
 * Hoofdklasse — verplicht voor MikoPBX routing en autorisatie.
 * Naam moet zijn: {ModuleUniqueID}Conf, in Lib/
 * Erft van ConfigClass (MikoPBX\Modules\Config\ConfigClass)
 */
namespace Modules\ModulePhoneBook\Lib;

use MikoPBX\Modules\Config\ConfigClass;
use MikoPBX\Common\Models\PbxSettings;

class ModulePhoneBookConf extends ConfigClass
{
    // Verplicht: wordt door MikoPBX gebruikt voor routing autorisatie
    // Geeft aan welke controllers/actions toegankelijk zijn
    public function getModuleMenu(): array
    {
        return [];
    }

    /**
     * Laad taalvertalingen op basis van PBXLanguage instelling
     */
    public static function getTranslations(): array
    {
        $langMap = [
            'nl' => 'nl', 'en' => 'en', 'de' => 'de',
            'fr' => 'fr', 'ru' => 'ru', 'be' => 'ru',
            'uk' => 'ru', 'kk' => 'ru',
        ];

        $pbxLang  = strtolower(substr(PbxSettings::getValueByKey('PBXLanguage') ?? 'en-gb', 0, 2));
        $lang     = $langMap[$pbxLang] ?? 'en';
        $moduleDir = dirname(__DIR__);
        $file     = $moduleDir . '/Messages/' . $lang . '.php';
        $fallback = $moduleDir . '/Messages/en.php';

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
