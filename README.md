# Cloud Phonebook — MikoPBX Module

**Developer:** Michiel Meedema — [WideM](https://widem.nl)  
**Version:** 1.2.6  
**Compatibility:** MikoPBX 2024.1.0+  
**License:** GPLv3

---

## Beschrijving

Cloud Phonebook is een uniforme telefoonboek module voor MikoPBX die interne toestellen automatisch synchroniseert vanuit de PBX en externe contacten handmatig laat beheren — alles via één publieke URL voor IP-telefoons.

### Voordelen ten opzichte van de standaard MikoPBX Phonebook

De ingebouwde MikoPBX Phonebook module is afhankelijk van de **ModuleAutoprovision** module om interne nummers op te halen. Als Autoprovision uitgeschakeld is, is het telefoonboek leeg.

**Cloud Phonebook** leest interne nummers **direct uit de PBX database** — geen afhankelijkheid van andere modules.

---

## Vereisten

- MikoPBX 2024.1.0 of hoger
- De **MikoPBX ingebouwde Phonebook module** (`ModulePhoneBook`) moet **actief** zijn — deze module gebruikt de onderliggende CallerID tabel (`pb_PhoneBook`) van die module voor het opslaan van CallerID gegevens

---

## Installatie

1. Download de laatste ZIP van [Releases](../../releases)
2. Ga in MikoPBX naar **Modules → Module Management → Upload new module**
3. Upload de ZIP
4. Activeer de module
5. Sidebar toevoegen: klik op het edit-icoon → tandwiel → sidebar inschakelen

---

## URL's voor IP-telefoons

### Yealink, Fanvil, Snom, Cisco (XML)
```
https://jouw-pbx/pbxcore/api/phonebooksync/contacts?format=xml
```

### Grandstream (XML)
```
https://jouw-pbx/pbxcore/api/phonebooksync/contacts?format=grandstream
```

### JSON (voor overige systemen)
```
https://jouw-pbx/pbxcore/api/phonebooksync/contacts
```

> **Let op:** De URL's zijn publiek toegankelijk — geen authenticatie vereist. Dit is noodzakelijk zodat IP-telefoons het telefoonboek kunnen ophalen zonder login.

---

## Toestel configuratie

### Yealink
Ga naar **Directory → Remote Phone Book** en voer in:
- **Server URL:** `https://jouw-pbx/pbxcore/api/phonebooksync/contacts?format=xml`
- **Display Name:** Cloud Phonebook

### Fanvil
Ga naar **Phone Book → Cloud Phone Book** en voer in:
- **Server URL:** `https://jouw-pbx/pbxcore/api/phonebooksync/contacts?format=xml`
- **Name:** Cloud Phonebook

### Grandstream
Ga naar **Contacts → LDAP/XML Phonebook** en voer in:
- **Phonebook XML Server Path:** `https://jouw-pbx/pbxcore/api/phonebooksync/contacts?format=grandstream`

---

## Module structuur

```
ModulePhoneBookSync/
├── module.json
├── Setup/
│   └── PbxExtensionSetup.php
├── Lib/
│   ├── PhoneBookSyncConf.php          # Hoofdklasse + CallerID sync
│   ├── PhoneBookSyncSetup.php
│   ├── ApiController.php              # REST API (authenticated)
│   └── RestAPI/
│       └── Controllers/
│           └── GetController.php     # Publieke phonebook endpoint
├── Models/
│   └── PhoneBookSyncContact.php      # Externe contacten
├── Messages/
│   ├── en.php                        # Engels (standaard)
│   ├── nl.php                        # Nederlands
│   ├── de.php                        # Duits
│   ├── fr.php                        # Frans
│   └── ru.php                        # Russisch
├── App/
│   ├── Controllers/IndexController.php
│   └── Views/index.volt
└── public/js/phonebook.js
```

---

## REST API (authenticated)

Alle endpoints onder `/pbxcore/api/modules/ModulePhoneBookSync/`:

| Method | Path | Beschrijving |
|--------|------|-------------|
| GET | `/contacts` | Alle contacten (intern + extern) |
| POST | `/contacts` | Extern contact aanmaken |
| PUT | `/contacts/{id}` | Extern contact bijwerken |
| DELETE | `/contacts/{id}` | Extern contact verwijderen |
| POST | `/sync-callerid` | Handmatige CallerID sync |
| GET | `/export-csv` | Export als CSV |
| POST | `/import-csv` | Import vanuit CSV |

---

## Changelog

### v1.2.6
- Fanvil gebruikt hetzelfde XML formaat als Yealink/Cisco — `?format=fanvil` en `?format=xml` geven identieke output
- Documentatie vereenvoudigd: één URL voor alle gangbare merken

### v1.2.5
- Content-Type header correct via Phalcon response object
- Eigen `terminateStreamedResponse()` implementatie (conform ModuleAutoprovision)

### v1.2.4
- XML formaten toegevoegd voor Yealink, Fanvil, Grandstream

### v1.2.3
- `exit()` toegevoegd om Phalcon JSON envelope te voorkomen

### v1.2.2
- Conf klasse hernoemd naar `PhoneBookSyncConf` (MikoPBX naamconventie)

### v1.2.1
- `sendRaw()` + `terminateStreamedResponse()` patroon (conform ModuleAutoprovision)

### v1.2.0
- Publieke REST endpoint via `Lib/RestAPI/Controllers/GetController.php`
- Route formaat gecorrigeerd conform MikoPBX RouterProvider

### v1.1.x
- Diverse fixes: module ID, breadcrumb, taaldetectie, DB registratie

### v1.0.0
- Eerste versie: auto-sync interne toestellen, externe contacten CRUD, CallerID, meertalig

---

## Developer

**Michiel Meedema — WideM**  
support@widem.nl

## License

[GNU General Public License v3.0](LICENSE)
