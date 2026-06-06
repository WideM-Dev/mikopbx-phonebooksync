<?php
/**
 * Copyright © 2024 YourCompany
 * Module: ModulePhoneBook v1.0.0
 * Installer: creates DB table, adds source column to pb_PhoneBook if needed
 */
namespace Modules\ModulePhoneBook\Lib;

use MikoPBX\Modules\Setup\PbxExtensionSetupBase;
use Modules\ModulePhoneBook\Models\PhoneBookContact;

class ModulePhoneBookSetup extends PbxExtensionSetupBase
{
    /**
     * Install the module: create tables + seed demo data
     */
    public function installDB(): bool
    {
        // 1. Create the module's own contacts table via model annotations
        $result = $this->createSettingsTableByModelsAnnotations();
        if (!$result) {
            $this->messages[] = 'Failed to create contacts table';
            return false;
        }

        // 2. Add 'source' column to built-in pb_PhoneBook if it doesn't exist
        //    (needed so we can track which entries belong to this module)
        try {
            $db = \Phalcon\Di\Di::getDefault()->get('db');
            $cols = $db->fetchAll("PRAGMA table_info(pb_PhoneBook)");
            $colNames = array_column($cols, 'name');
            if (!in_array('source', $colNames, true)) {
                $db->execute("ALTER TABLE pb_PhoneBook ADD COLUMN source TEXT DEFAULT ''");
            }
        } catch (\Throwable $e) {
            // Non-fatal — CallerID sync still works without the source column
            error_log('[ModulePhoneBook] Could not add source column: ' . $e->getMessage());
        }

        // 3. Register module with PBX
        $result = $this->registerNewModule();
        if (!$result) {
            $this->messages[] = 'Failed to register module';
            return false;
        }

        return true;
    }

    /**
     * Uninstall: remove CallerID entries managed by this module
     */
    public function unInstallDB(): bool
    {
        try {
            $db = \Phalcon\Di\Di::getDefault()->get('db');
            $db->execute("DELETE FROM pb_PhoneBook WHERE source = 'ModulePhoneBook'");
        } catch (\Throwable $e) {
            // Ignore on uninstall
        }
        return parent::unInstallDB();
    }
}
