<?php
/**
 * Cloud Phonebook v1.1.5 — IndexController
 *
 * Erft van ModuleController (MikoPBX\AdminCabinet\Controllers\ModuleController)
 * Dit is de correcte basisklasse voor module-controllers in MikoPBX 2024+
 * ModuleController handelt autorisatie correct af voor module-pagina's
 */
namespace Modules\ModulePhoneBook\App\Controllers;

use MikoPBX\AdminCabinet\Controllers\ModuleController;
use MikoPBX\Common\Models\PbxSettings;

class IndexController extends ModuleController
{
    private const VERSION   = '1.1.5';
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
        $langCode = strtolower(substr($pbxLang, 0, 2));
        $lang     = self::LANG_MAP[$langCode] ?? 'en';
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
            '1.1.5' => [
                'en' => 'Fixed authorization: switched to ModuleController base class.',
                'nl' => 'Autorisatie opgelost: overgegaan op ModuleController basisklasse.',
            ],
            '1.1.4' => ['en' => 'Added ConfigClass main module class.'],
            '1.1.3' => ['en' => 'Added db/ directory, fixed Phalcon DI.'],
            '1.1.2' => ['en' => 'Added addToSidebar() in installer.'],
            '1.1.1' => ['en' => 'Added BreadcrumbModulePhoneBook key.'],
            '1.1.0' => ['en' => 'PBX language detection, Russian, renamed to Cloud Phonebook, WideM.'],
            '1.0.0' => ['en' => 'Initial release.'],
        ];
    }
}
