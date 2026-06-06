<?php
/**
 * Cloud Phonebook v1.3.1 — ModulePhoneBookSyncController
 * Naam conform MikoPBX conventie: {ModuleUniqueId}Controller
 * Erft van BaseController
 */
namespace Modules\ModulePhoneBookSync\App\Controllers;

use MikoPBX\AdminCabinet\Controllers\BaseController;
use MikoPBX\Common\Models\PbxSettings;

class ModulePhoneBookSyncController extends BaseController
{
    public bool $showModuleStatusToggle = true;

    private const VERSION   = '1.4.3';
    private const SUPPORTED = ['en', 'nl', 'de', 'fr', 'ru'];
    private const LANG_MAP  = [
        'nl'=>'nl','en'=>'en','de'=>'de','fr'=>'fr','ru'=>'ru',
        'be'=>'ru','uk'=>'ru','kk'=>'ru',
    ];

    public function indexAction(): void
    {
        $pbxLang = PbxSettings::getValueByKey('PBXLanguage') ?? 'en-gb';

        $this->view->version      = self::VERSION;
        $this->view->pbxLang      = $pbxLang;
        $this->view->translations = $this->loadTranslations($pbxLang);
        $this->view->changelog    = $this->getChangelog();
    }

    private function loadTranslations(string $pbxLang): array
    {
        $langCode  = strtolower(substr($pbxLang, 0, 2));
        $lang      = self::LANG_MAP[$langCode] ?? 'en';
        if (!in_array($lang, self::SUPPORTED, true)) {
            $lang = 'en';
        }
        $moduleDir = dirname(__DIR__, 2);
        $file      = $moduleDir . '/Messages/' . $lang . '.php';
        $fallback  = $moduleDir . '/Messages/en.php';
        $result    = file_exists($file)
            ? require $file
            : (file_exists($fallback) ? require $fallback : []);
        return is_array($result) ? $result : [];
    }

    private function getChangelog(): array
    {
        return [
            '1.3.1' => [
                'en' => 'Fixed controller name to ModulePhoneBookSyncController (MikoPBX convention).',
                'nl' => 'Controller naam gecorrigeerd naar ModulePhoneBookSyncController (MikoPBX conventie).',
            ],
            '1.3.0' => ['en' => 'Fixed view path and BaseController inheritance.'],
            '1.2.9' => ['en' => 'Separate XML formats per brand.'],
            '1.0.0' => ['en' => 'Initial release.'],
        ];
    }
}
