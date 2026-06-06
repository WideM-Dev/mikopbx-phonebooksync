<?php
declare(strict_types=1);
/**
 * Cloud Phonebook — GetController v1.2.5
 * Gebruikt exact hetzelfde patroon als ModuleAutoprovision:
 * - $this->response->setContentType() voor de Content-Type header
 * - echo voor de output
 * - $this->response->sendRaw() om raw te sturen
 * - terminateStreamedResponse() (eigen implementatie) om de Phalcon envelope te stoppen
 */
namespace Modules\ModulePhoneBookSync\Lib\RestAPI\Controllers;

use MikoPBX\PBXCoreREST\Controllers\Modules\ModulesControllerBase;
use Modules\ModulePhoneBookSync\Lib\PhoneBookSyncConf;

class GetController extends ModulesControllerBase
{
    public function getContacts(): void
    {
        $format   = strtolower($_REQUEST['format'] ?? 'json');
        $contacts = PhoneBookSyncConf::getAllContacts();

        switch ($format) {
            case 'yealink':
            case 'xml':
                $this->response->setContentType('application/xml', 'UTF-8');
                $this->echoYealink($contacts);
                break;
            case 'fanvil':
                $this->response->setContentType('application/xml', 'UTF-8');
                $this->echoFanvil($contacts);
                break;
            case 'grandstream':
                $this->response->setContentType('application/xml', 'UTF-8');
                $this->echoGrandstream($contacts);
                break;
            default:
                $this->response->setContentType('application/json', 'UTF-8');
                $this->echoJson($contacts);
                break;
        }

        $this->response->sendRaw();
        $this->terminateStreamedResponse();
    }

    /**
     * Exact dezelfde implementatie als ModuleAutoprovision
     */
    private function terminateStreamedResponse(): void
    {
        while (ob_get_level() > 0 && @ob_end_flush()) {
            // drain output buffers
        }
        flush();
        exit;
    }

    private function echoYealink(array $contacts): void
    {
        echo "<?xml version='1.0' encoding='UTF-8'?>" . PHP_EOL;
        echo '<YealinkIPPhoneDirectory>' . PHP_EOL;
        foreach ($contacts as $c) {
            $name   = htmlspecialchars($c['name']   ?? '', ENT_XML1, 'UTF-8');
            $number = htmlspecialchars($c['number'] ?? ($c['extension'] ?? ''), ENT_XML1, 'UTF-8');
            echo "\t<DirectoryEntry>" . PHP_EOL;
            echo "\t\t<Name>{$name}</Name>" . PHP_EOL;
            echo "\t\t<Telephone>{$number}</Telephone>" . PHP_EOL;
            echo "\t</DirectoryEntry>" . PHP_EOL;
        }
        echo '</YealinkIPPhoneDirectory>';
    }

    private function echoFanvil(array $contacts): void
    {
        echo "<?xml version='1.0' encoding='UTF-8'?>" . PHP_EOL;
        echo '<AddressBook>' . PHP_EOL;
        foreach ($contacts as $c) {
            $name   = htmlspecialchars($c['name']   ?? '', ENT_XML1, 'UTF-8');
            $number = htmlspecialchars($c['number'] ?? ($c['extension'] ?? ''), ENT_XML1, 'UTF-8');
            echo "\t<Contact>" . PHP_EOL;
            echo "\t\t<Name>{$name}</Name>" . PHP_EOL;
            echo "\t\t<Phone>{$number}</Phone>" . PHP_EOL;
            echo "\t</Contact>" . PHP_EOL;
        }
        echo '</AddressBook>';
    }

    private function echoGrandstream(array $contacts): void
    {
        echo "<?xml version='1.0' encoding='UTF-8'?>" . PHP_EOL;
        echo '<AddressBook>' . PHP_EOL;
        foreach ($contacts as $c) {
            $name   = htmlspecialchars($c['name']   ?? '', ENT_XML1, 'UTF-8');
            $number = htmlspecialchars($c['number'] ?? ($c['extension'] ?? ''), ENT_XML1, 'UTF-8');
            echo "\t<Contact>" . PHP_EOL;
            echo "\t\t<FirstName>{$name}</FirstName>" . PHP_EOL;
            echo "\t\t<LastName></LastName>" . PHP_EOL;
            echo "\t\t<Phone><phonenumber>{$number}</phonenumber><accountindex>0</accountindex></Phone>" . PHP_EOL;
            echo "\t</Contact>" . PHP_EOL;
        }
        echo '</AddressBook>';
    }

    private function echoJson(array $contacts): void
    {
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
}
