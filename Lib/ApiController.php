<?php
/**
 * Copyright © 2024 YourCompany
 * Module: ModulePhoneBookSync v1.0.0
 * REST API Controller — handles all /api/modules/ModulePhoneBookSync/* routes
 */
namespace Modules\ModulePhoneBookSync\Lib;

use MikoPBX\PBXCoreREST\Lib\PBXApiResult;
use Modules\ModulePhoneBookSync\Models\PhoneBookSyncContact;

class ApiController
{
    // ------------------------------------------------------------------
    // GET /contacts  — returns internal + external merged
    // ------------------------------------------------------------------
    public static function getContacts(array $params = []): PBXApiResult
    {
        $result = new PBXApiResult();
        try {
            $result->data['contacts'] = PhoneBookSyncConf::getAllContacts();
            $result->data['version']  = PhoneBookSyncConf::VERSION;
            $result->success = true;
        } catch (\Throwable $e) {
            $result->success  = false;
            $result->messages = [$e->getMessage()];
        }
        return $result;
    }

    // ------------------------------------------------------------------
    // POST /contacts  — create external contact
    // ------------------------------------------------------------------
    public static function saveContact(array $params): PBXApiResult
    {
        $result = new PBXApiResult();
        $name   = trim($params['name']   ?? '');
        $number = trim($params['number'] ?? '');

        if (!$name) {
            $result->success  = false;
            $result->messages = ['name_required'];
            return $result;
        }
        if (!$number) {
            $result->success  = false;
            $result->messages = ['number_required'];
            return $result;
        }
        $clean = preg_replace('/[\s\-\(\)]/', '', $number);
        if (!preg_match('/^[\+\d]{6,20}$/', $clean)) {
            $result->success  = false;
            $result->messages = ['number_invalid'];
            return $result;
        }

        // Duplicate check
        $exists = PhoneBookSyncContact::findFirst([
            'conditions' => 'number = :number:',
            'bind'       => ['number' => $number],
        ]);
        if ($exists) {
            $result->success  = false;
            $result->messages = ['duplicate'];
            return $result;
        }

        $contact             = new PhoneBookSyncContact();
        $contact->name       = $name;
        $contact->number     = $number;
        $contact->department = trim($params['department'] ?? '');
        $contact->category   = trim($params['category']   ?? '');
        $contact->notes      = trim($params['notes']      ?? '');

        if ($contact->save()) {
            // Auto-sync CallerID after every change
            PhoneBookSyncConf::syncToCallerID();
            $result->success       = true;
            $result->data['id']    = $contact->id;
            $result->data['contact'] = [
                'id'         => $contact->id,
                'name'       => $contact->name,
                'number'     => $contact->number,
                'department' => $contact->department,
                'category'   => $contact->category,
                'notes'      => $contact->notes,
                'type'       => 'external',
                'readonly'   => false,
            ];
        } else {
            $result->success  = false;
            $result->messages = $contact->getMessages();
        }
        return $result;
    }

    // ------------------------------------------------------------------
    // PUT /contacts/{id}  — update external contact
    // ------------------------------------------------------------------
    public static function updateContact(array $params): PBXApiResult
    {
        $result  = new PBXApiResult();
        $id      = (int)($params['id'] ?? 0);
        $contact = PhoneBookSyncContact::findFirstById($id);

        if (!$contact) {
            $result->success  = false;
            $result->messages = ['not_found'];
            return $result;
        }

        if (isset($params['name']))       $contact->name       = trim($params['name']);
        if (isset($params['number']))     $contact->number     = trim($params['number']);
        if (isset($params['department'])) $contact->department = trim($params['department']);
        if (isset($params['category']))   $contact->category   = trim($params['category']);
        if (isset($params['notes']))      $contact->notes      = trim($params['notes']);

        if ($contact->save()) {
            PhoneBookSyncConf::syncToCallerID();
            $result->success = true;
        } else {
            $result->success  = false;
            $result->messages = $contact->getMessages();
        }
        return $result;
    }

    // ------------------------------------------------------------------
    // DELETE /contacts/{id}  — delete external contact
    // ------------------------------------------------------------------
    public static function deleteContact(array $params): PBXApiResult
    {
        $result  = new PBXApiResult();
        $id      = (int)($params['id'] ?? 0);
        $contact = PhoneBookSyncContact::findFirstById($id);

        if (!$contact) {
            $result->success  = false;
            $result->messages = ['not_found'];
            return $result;
        }

        if ($contact->delete()) {
            PhoneBookSyncConf::syncToCallerID();
            $result->success = true;
        } else {
            $result->success  = false;
            $result->messages = $contact->getMessages();
        }
        return $result;
    }

    // ------------------------------------------------------------------
    // POST /sync-callerid  — manual full sync trigger
    // ------------------------------------------------------------------
    public static function syncCallerID(array $params = []): PBXApiResult
    {
        $result          = new PBXApiResult();
        $result->success = PhoneBookSyncConf::syncToCallerID();
        if (!$result->success) {
            $result->messages = ['callerid_sync_fail'];
        }
        return $result;
    }

    // ------------------------------------------------------------------
    // GET /export-csv
    // ------------------------------------------------------------------
    public static function exportCsv(array $params = []): PBXApiResult
    {
        $result              = new PBXApiResult();
        $result->success     = true;
        $result->data['csv'] = PhoneBookSyncConf::exportToCsv();
        return $result;
    }

    // ------------------------------------------------------------------
    // POST /import-csv  — body: { csv: "..." }
    // ------------------------------------------------------------------
    public static function importCsv(array $params): PBXApiResult
    {
        $result    = new PBXApiResult();
        $csvData   = $params['csv'] ?? '';
        $importResult = PhoneBookSyncConf::importFromCsv($csvData);

        if ($importResult['imported'] > 0) {
            PhoneBookSyncConf::syncToCallerID();
        }

        $result->success                = true;
        $result->data['imported']       = $importResult['imported'];
        $result->data['errors']         = $importResult['errors'];
        return $result;
    }
}

    // ------------------------------------------------------------------
    // GET /pbxcore/api/phonebooksync/contacts — PUBLIEK, geen auth vereist
    // Formaat compatibel met telefoontoestellen (Yealink, Fanvil, Snom etc.)
    // ------------------------------------------------------------------
    public static function getPublicContacts(): void
    {
        $format = $_GET['format'] ?? 'json';
        $contacts = PhoneBookSyncConf::getAllContacts();

        if ($format === 'xml') {
            // XML formaat voor toestellen die dat verwachten (Yealink etc.)
            header('Content-Type: application/xml; charset=utf-8');
            echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            echo '<AddressBook>' . "\n";
            foreach ($contacts as $c) {
                $name   = htmlspecialchars($c['name'] ?? '', ENT_XML1);
                $number = htmlspecialchars($c['number'] ?? ($c['extension'] ?? ''), ENT_XML1);
                $type   = $c['type'] === 'internal' ? 'Internal' : 'External';
                echo "  <Contact>\n";
                echo "    <Name>{$name}</Name>\n";
                echo "    <Phone type=\"{$type}\">{$number}</Phone>\n";
                echo "  </Contact>\n";
            }
            echo '</AddressBook>';
        } else {
            // JSON formaat (standaard)
            header('Content-Type: application/json; charset=utf-8');
            $result = array_map(function($c) {
                return [
                    'name'   => $c['name'] ?? '',
                    'number' => $c['number'] ?? ($c['extension'] ?? ''),
                    'type'   => $c['type'] ?? 'external',
                    'department' => $c['department'] ?? '',
                ];
            }, $contacts);
            echo json_encode(['contacts' => $result, 'total' => count($result)], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
