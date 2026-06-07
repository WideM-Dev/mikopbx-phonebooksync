# Cloud Phonebook for MikoPBX

**Version:** 1.4.3  
**Developer:** Michiel Meedema — [WideM](https://widem.nl) (support@widem.nl)  
**Compatibility:** MikoPBX 2024.1+ / 2026.x  
**License:** GPLv3

---

## What does this module do?

Cloud Phonebook provides a single public URL that IP phones can use to retrieve an up-to-date phonebook. The module automatically combines:

1. **Internal extensions** — directly from the PBX Extensions table (201 - Olaf, 202 - Homme, etc.)
2. **ModulePhoneBook contacts** — from the built-in MikoPBX Phonebook module (external numbers such as clients, suppliers, etc.)

No manual synchronisation, no dependency on Autoprovision — the URL always returns the current state.

---

## Requirements

- MikoPBX 2024.1 or higher
- The built-in **ModulePhoneBook** module must be installed and active

---

## Installation

1. Download the latest ZIP from the [Releases page](../../releases/latest)
2. In MikoPBX go to **Modules → Module Management → Upload new module**
3. Upload the ZIP and activate the module
4. The module is now ready to use.

---

## Managing contacts

Add and manage contacts via the built-in **ModulePhoneBook** module:

**Modules → Phonebook**

- Internal extensions are pulled automatically from the PBX
- External numbers are added via ModulePhoneBook
- All contacts appear automatically in the Cloud Phonebook URLs

---

## URLs for IP phones

Configure the URL below on your devices as a remote phonebook:

| Brand | URL |
|-------|-----|
| **Yealink** | `https://your-pbx/pbxcore/api/phonebooksync/contacts?format=yealink` |
| **Fanvil** | `https://your-pbx/pbxcore/api/phonebooksync/contacts?format=fanvil` |
| **Snom** | `https://your-pbx/pbxcore/api/phonebooksync/contacts?format=snom` |
| **Cisco** | `https://your-pbx/pbxcore/api/phonebooksync/contacts?format=cisco` |
| **Grandstream** | `https://your-pbx/pbxcore/api/phonebooksync/contacts?format=grandstream` |
| **JSON** | `https://your-pbx/pbxcore/api/phonebooksync/contacts` |

> The URLs are publicly accessible without authentication — required so IP phones can retrieve the phonebook.

---

## Phone configuration

### Yealink
**Directory → Remote Phone Book → Server URL**

### Fanvil
**Phone Book → Cloud Phone Book → Server URL**

### Snom
**Setup → Advanced → Remote Directory URL**

### Grandstream
**Contacts → LDAP/XML Phonebook → Phonebook XML Server Path**

---

## How it works

```
IP phone
    │
    │ GET /pbxcore/api/phonebooksync/contacts?format=yealink
    ▼
MikoPBX (Cloud Phonebook module)
    │
    ├── PBX Extensions table  →  internal extensions (201, 202, ...)
    └── ModulePhoneBook DB    →  external numbers (+31502050400, ...)
    │
    ▼
XML response (YealinkIPPhoneDirectory format)
```

The module reads live data from both sources on every request. Numbers are normalised — dashes and spaces are stripped so the phone can dial correctly.

---

## Known limitations

- The module settings page only shows the URLs — contact management is done via ModulePhoneBook

---

## Changelog

| Version | Changes |
|---------|---------|
| 1.4.3 | Stable minimal admin UI |
| 1.4.2 | CRUD API via moduleRestAPICallback |
| 1.3.1 | Controller renamed to MikoPBX convention |
| 1.3.0 | BaseController inheritance, correct view paths |
| 1.2.9 | Separate XML formats per brand |
| 1.2.8 | Number normalisation (strip dashes/spaces) |
| 1.2.7 | Read ModulePhoneBook contacts |
| 1.2.2 | Conf class renamed to PhoneBookSyncConf |
| 1.2.0 | Public REST endpoint working |
| 1.0.0 | Initial release |

---

## Nederlands

### Wat doet deze module?

Cloud Phonebook biedt één publieke URL waarmee IP-telefoons een actueel telefoonboek kunnen ophalen. De module combineert automatisch interne toestellen (direct uit de PBX) en externe contacten uit de ingebouwde ModulePhoneBook.

### Installatie

1. Download de laatste ZIP via de [Releases pagina](../../releases/latest)
2. Ga naar **Modules → Module Management → Upload new module**
3. Upload de ZIP en activeer de module
4. De module is klaar voor gebruik.

### Contacten beheren

Via de ingebouwde **ModulePhoneBook** module — alle contacten daar worden automatisch meegenomen in de Cloud Phonebook URL's.

---

## License

[GNU General Public License v3.0](LICENSE)

© 2026 Michiel Meedema — WideM
