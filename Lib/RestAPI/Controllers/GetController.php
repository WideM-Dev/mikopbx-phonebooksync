<?php
declare(strict_types=1);
/**
 * Cloud Phonebook — GetController v1.2.3
 */
namespace Modules\ModulePhoneBookSync\Lib\RestAPI\Controllers;

use MikoPBX\PBXCoreREST\Controllers\Modules\ModulesControllerBase;
use Modules\ModulePhoneBookSync\Lib\PhoneBookSyncConf;

class GetController extends ModulesControllerBase
{
    public function getContacts(): void
    {
        $format   = $_REQUEST['format'] ?? 'json';
        $contacts = PhoneBookSyncConf::getAllContacts();

        if ($format === 'xml') {
            header('Content-Type: application/xml; charset=UTF-8');
            echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
            echo '<AddressBook>' . PHP_EOL;
            foreach ($contacts as $c) {
                $name   = htmlspecialchars($c['name']   ?? '', ENT_XML1, 'UTF-8');
                $number = htmlspecialchars($c['number'] ?? ($c['extension'] ?? ''), ENT_XML1, 'UTF-8');
                $type   = ($c['type'] === 'internal') ? 'Internal' : 'External';
                echo "  <Contact>" . PHP_EOL;
                echo "    <Name>{$name}</Name>" . PHP_EOL;
                echo "    <Phone type=\"{$type}\">{$number}</Phone>" . PHP_EOL;
                echo "  </Contact>" . PHP_EOL;
            }
            echo '</AddressBook>';
        } else {
            header('Content-Type: application/json; charset=UTF-8');
            $result = array_map(static function (array $c): array {
                return [
                    'name'       => $c['name']       ?? '',
                    'number'     => $c['number']      ?? ($c['extension'] ?? ''),
                    'type'       => $c['type']        ?? 'external',
                    'department' => $c['department']  ?? '',
                ];
            }, $contacts);
            echo json_encode(
                ['contacts' => $result, 'total' => count($result)],
                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            );
        }
        // exit() stopt de Phalcon response middleware die anders
        // een JSON envelope toevoegt na onze output
        exit();
    }
}
