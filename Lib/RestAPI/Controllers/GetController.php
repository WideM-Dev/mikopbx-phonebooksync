<?php
/**
 * Cloud Phonebook — GetController v1.2.0
 * Publieke REST controller voor telefoontoestellen
 * Structuur conform ModuleAutoprovision patroon
 */
namespace Modules\ModulePhoneBookSync\Lib\RestAPI\Controllers;

use MikoPBX\PBXCoreREST\Controllers\Modules\ModulesControllerBase;
use Modules\ModulePhoneBookSync\Lib\ModulePhoneBookSyncConf;

class GetController extends ModulesControllerBase
{
    /**
     * Publieke phonebook endpoint — geen authenticatie vereist
     * GET /pbxcore/api/phonebooksync/contacts
     * GET /pbxcore/api/phonebooksync/contacts?format=xml
     */
    public function getContacts(): void
    {
        $format   = $this->request->getQuery('format', 'string', 'json');
        $contacts = ModulePhoneBookSyncConf::getAllContacts();

        if ($format === 'xml') {
            $this->response->setContentType('application/xml', 'UTF-8');
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n<AddressBook>\n";
            foreach ($contacts as $c) {
                $name   = htmlspecialchars($c['name'] ?? '', ENT_XML1, 'UTF-8');
                $number = htmlspecialchars($c['number'] ?? ($c['extension'] ?? ''), ENT_XML1, 'UTF-8');
                $type   = ($c['type'] === 'internal') ? 'Internal' : 'External';
                $xml   .= "  <Contact>\n";
                $xml   .= "    <Name>{$name}</Name>\n";
                $xml   .= "    <Phone type=\"{$type}\">{$number}</Phone>\n";
                $xml   .= "  </Contact>\n";
            }
            $xml .= '</AddressBook>';
            $this->response->setContent($xml);
        } else {
            $result = array_map(function ($c) {
                return [
                    'name'       => $c['name'] ?? '',
                    'number'     => $c['number'] ?? ($c['extension'] ?? ''),
                    'type'       => $c['type'] ?? 'external',
                    'department' => $c['department'] ?? '',
                ];
            }, $contacts);
            $this->response->setJsonContent([
                'contacts' => $result,
                'total'    => count($result),
            ]);
        }
        $this->response->send();
    }
}
