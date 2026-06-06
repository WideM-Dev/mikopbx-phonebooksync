<?php
/**
 * Copyright © 2024 YourCompany
 * Module: ModulePhonebookSync v1.0.0
 * REST API Controller — handles all /api/modules/ModulePhonebookSync/* routes
 */
namespace Modules\ModulePhonebookSync\Lib;

use MikoPBX\PBXCoreREST\Lib\PBXApiResult;
use Modules\ModulePhonebookSync\Models\PhonebookSyncContact;

class ApiController
{
    // ------------------------------------------------------------------
    // GET /contacts  — returns internal + external merged
    // ------------------------------------------------------------------
    public static function getContacts(array $params = []): PBXApiResult
    {
        $result = new PBXApiResult();
        try {
            $result->data['contacts'] = ModulePhonebookSyncConf::getAllContacts();
            $result->data['version']  = ModulePhonebookSyncConf::VERSION;
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
        $exists = PhonebookSyncContact::findFirst([
            'conditions' => 'number = :number:',
            'bind'       => ['number' => $number],
        ]);
        if ($exists) {
            $result->success  = false;
            $result->messages = ['duplicate'];
            return $result;
        }

        $contact             = new PhonebookSyncContact();
        $contact->name       = $name;
        $contact->number     = $number;
        $contact->department = trim($params['department'] ?? '');
        $contact->category   = trim($params['category']   ?? '');
        $contact->notes      = trim($params['notes']      ?? '');

        if ($contact->save()) {
            // Auto-sync CallerID after every change
            ModulePhonebookSyncConf::syncToCallerID();
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
        $contact = PhonebookSyncContact::findFirstById($id);

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
            ModulePhonebookSyncConf::syncToCallerID();
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
        $contact = PhonebookSyncContact::findFirstById($id);

        if (!$contact) {
            $result->success  = false;
            $result->messages = ['not_found'];
            return $result;
        }

        if ($contact->delete()) {
            ModulePhonebookSyncConf::syncToCallerID();
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
        $result->success = ModulePhonebookSyncConf::syncToCallerID();
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
        $result->data['csv'] = ModulePhonebookSyncConf::exportToCsv();
        return $result;
    }

    // ------------------------------------------------------------------
    // POST /import-csv  — body: { csv: "..." }
    // ------------------------------------------------------------------
    public static function importCsv(array $params): PBXApiResult
    {
        $result    = new PBXApiResult();
        $csvData   = $params['csv'] ?? '';
        $importResult = ModulePhonebookSyncConf::importFromCsv($csvData);

        if ($importResult['imported'] > 0) {
            ModulePhonebookSyncConf::syncToCallerID();
        }

        $result->success                = true;
        $result->data['imported']       = $importResult['imported'];
        $result->data['errors']         = $importResult['errors'];
        return $result;
    }
}
