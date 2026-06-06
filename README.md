# Cloud Phonebook — MikoPBX Module

**Developer:** Michiel Meedema — [WideM](https://widem.nl)  
**Version:** 1.1.5  
**Compatibility:** MikoPBX 2024.1.0+  
**License:** GPLv3  

---

## Description

Cloud Phonebook is a unified phonebook module for MikoPBX that combines internal PBX extensions with manually managed external contacts, and automatically syncs everything to the built-in CallerID lookup system.

### Features

- **Internal extensions** — automatically synced from PBX (read-only)
- **External contacts** — full CRUD via web interface (add, edit, delete)
- **CallerID sync** — all contacts pushed to `pb_PhoneBook` after every change
- **CSV import/export** — bulk contact management
- **Multilingual** — English, Dutch, German, French, Russian
- **Auto language detection** — follows PBX interface language setting (`PBXLanguage`)
- **Version history** — changelog visible in the UI

---

## Installation

1. Download the latest ZIP from [Releases](../../releases)
2. In MikoPBX go to **Modules → Module Management → Upload new module**
3. Upload the ZIP file
4. Activate the module
5. Add to sidebar: click the edit icon → gear icon → enable sidebar

---

## Module Structure

```
mikopbx-phonebooksync/
├── module.json                         # Module metadata
├── Setup/
│   └── PbxExtensionSetup.php          # Installer / uninstaller
├── Lib/
│   ├── ModulePhonebookSyncConf.php        # Main ConfigClass (routing & auth)
│   └── ApiController.php              # REST API endpoints
├── Models/
│   └── PhonebookSyncContact.php           # Phalcon model — external contacts
├── Messages/
│   ├── en.php                         # English (default)
│   ├── nl.php                         # Dutch
│   ├── de.php                         # German
│   ├── fr.php                         # French
│   └── ru.php                         # Russian
├── App/
│   ├── Controllers/
│   │   └── IndexController.php        # Web UI controller
│   └── Views/
│       └── index.volt                 # Volt template
├── public/
│   └── js/
│       └── phonebook.js               # Frontend (vanilla JS)
└── db/                                # SQLite database directory
```

---

## REST API

All endpoints under `/pbxcore/api/modules/ModulePhonebookSync/`:

| Method | Path | Description |
|--------|------|-------------|
| GET | `/contacts` | All contacts (internal + external) |
| POST | `/contacts` | Create external contact |
| PUT | `/contacts/{id}` | Update external contact |
| DELETE | `/contacts/{id}` | Delete external contact |
| POST | `/sync-callerid` | Manual CallerID sync |
| GET | `/export-csv` | Export contacts as CSV |
| POST | `/import-csv` | Import contacts from CSV |

---

## CallerID Integration

After every add/edit/delete the module automatically writes to MikoPBX's built-in `pb_PhoneBook` table. Entries are tagged with `source = 'ModulePhonebookSync'` and cleaned up on uninstall.

---

## Changelog

### v1.1.5
- Fixed authorization error: switched to correct controller base class

### v1.1.4
- Added `ModulePhonebookSyncConf` ConfigClass for MikoPBX routing

### v1.1.3
- Added `db/` directory, fixed Phalcon DI service registration

### v1.1.2
- Added `addToSidebar()` in installer

### v1.1.1
- Added `BreadcrumbModulePhonebookSync` translation key (fixes module name in UI)

### v1.1.0
- PBX interface language detection (`PBXLanguage`)
- Added Russian language
- Module renamed to Cloud Phonebook
- Developer set to WideM

### v1.0.0
- Initial release
- Internal extensions auto-sync
- External contacts CRUD
- CallerID integration
- CSV import/export
- Multilingual (NL/EN/DE/FR)

---

## Developer

**Michiel Meedema**  
**WideM**  
support@widem.nl

---

## License

This module is licensed under the [GNU General Public License v3.0](LICENSE).
