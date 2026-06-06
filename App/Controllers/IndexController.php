<?php
/**
 * Cloud Phonebook v1.3.0 — IndexController
 * Erft van BaseController (enige beschikbare basisklasse in MikoPBX 2026.x)
 * View pad wordt automatisch bepaald door BaseController::beforeExecuteRoute():
 * Modules/ModulePhoneBookSync/Index/index → App/Views/ModulePhoneBookSync/Index/index.volt
 */
namespace Modules\ModulePhoneBookSync\App\Controllers;

use MikoPBX\AdminCabinet\Controllers\BaseController;
use MikoPBX\Common\Models\PbxSettings;

class IndexController extends BaseController
{
    private const VERSION   = '1.3.0';
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
        // moduleDir is beschikbaar via BaseController
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
            '1.3.0' => [
                'en' => 'Fixed authorization: correct view path and BaseController inheritance.',
                'nl' => 'Autorisatie opgelost: correct view pad en BaseController overerving.',
            ],
            '1.2.9' => ['en' => 'Separate XML formats per brand (Yealink, Fanvil, Snom, Cisco, Grandstream).'],
            '1.2.8' => ['en' => 'Normalize phone numbers from ModulePhoneBook (strip dashes/spaces).'],
            '1.2.7' => ['en' => 'Read contacts from ModulePhoneBook m_PhoneBook table.'],
            '1.2.6' => ['en' => 'Fanvil uses same XML format as Yealink.'],
            '1.0.0' => ['en' => 'Initial release.'],
        ];
    }
}
