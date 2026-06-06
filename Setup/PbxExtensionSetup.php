<?php
/**
 * Cloud Phonebook v1.1.3 — PbxExtensionSetup
 */
namespace Modules\ModulePhonebookSync\Setup;

use MikoPBX\Modules\Setup\PbxExtensionSetupBase;

class PbxExtensionSetup extends PbxExtensionSetupBase
{
    public function installDB(): bool
    {
        // 1. Maak eigen SQLite tabel aan via model annotations
        $result = $this->createSettingsTableByModelsAnnotations();
        if (!$result) {
            $this->messages[] = 'Failed to create module database tables';
            return false;
        }

        // 2. Registreer module in PbxExtensionModules tabel
        $result = $this->registerNewModule();
        if (!$result) {
            $this->messages[] = 'Failed to register module';
            return false;
        }

        // 3. Sidebar menu-item toevoegen
        $this->addToSidebar();

        // 4. source-kolom in pb_PhoneBook (non-destructief, best-effort)
        try {
            $db    = \Phalcon\Di\Di::getDefault()->get('db');
            $cols  = $db->fetchAll("PRAGMA table_info(pb_PhoneBook)");
            $names = array_column($cols, 'name');
            if (!in_array('source', $names, true)) {
                $db->execute("ALTER TABLE pb_PhoneBook ADD COLUMN source TEXT DEFAULT ''");
            }
        } catch (\Throwable $e) {
            // Niet fataal
        }

        return true;
    }

    public function unInstallDB(bool $keepSettings = false): bool
    {
        try {
            $db = \Phalcon\Di\Di::getDefault()->get('db');
            $db->execute("DELETE FROM pb_PhoneBook WHERE source = 'ModulePhonebookSync'");
        } catch (\Throwable $e) {
            // Negeer bij verwijdering
        }
        return parent::unInstallDB($keepSettings);
    }
}
