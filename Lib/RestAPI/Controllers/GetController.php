<?php
declare(strict_types=1);
/**
 * Cloud Phonebook — GetController v1.2.4
 * Ondersteunt meerdere XML formaten via ?format= parameter
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
                $this->echoYealink($contacts);
                break;
            case 'fanvil':
                $this->echoFanvil($contacts);
                break;
            case 'grandstream':
                $this->echoGrandstream($contacts);
                break;
            default:
                $this->echoJson($contacts);
                break;
        }

        exit();
    }

    /**
     * Yealink XML formaat — werkt ook voor Snom
     * <YealinkIPPhoneDirectory>
     *   <DirectoryEntry><Name>...</Name><Telephone>...</Telephone></DirectoryEntry>
     * </YealinkIPPhoneDirectory>
     */
    private function echoYealink(array $contacts): void
    {
        header('Content-Type: application/xml; charset=UTF-8');
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

    /**
     * Fanvil XML formaat
     * <AddressBook>
     *   <Contact><Name>...</Name><Phone>...</Phone></Contact>
     * </AddressBook>
     */
    private function echoFanvil(array $contacts): void
    {
        header('Content-Type: application/xml; charset=UTF-8');
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

    /**
     * Grandstream XML formaat
     * <AddressBook>
     *   <Contact><FirstName>...</FirstName><Phone><phonenumber>...</phonenumber><accountindex>0</accountindex></Phone></Contact>
     * </AddressBook>
     */
    private function echoGrandstream(array $contacts): void
    {
        header('Content-Type: application/xml; charset=UTF-8');
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

    /**
     * JSON formaat — standaard
     */
    private function echoJson(array $contacts): void
    {
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
}
