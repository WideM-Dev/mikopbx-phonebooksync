# Cloud Phonebook voor MikoPBX

**Versie:** 1.4.3  
**Ontwikkelaar:** Michiel Meedema — [WideM](https://widem.nl) (support@widem.nl)  
**Compatibiliteit:** MikoPBX 2024.1+ / 2026.x  
**Licentie:** GPLv3

---

## Wat doet deze module?

Cloud Phonebook biedt één publieke URL waarmee IP-telefoons een actueel telefoonboek kunnen ophalen. De module combineert automatisch:

1. **Interne toestellen** — direct uit de PBX Extensions tabel
2. **Externe contacten** — uit de ingebouwde MikoPBX ModulePhoneBook

Geen handmatige synchronisatie, geen afhankelijkheid van Autoprovision — de URL geeft altijd de actuele stand terug.

---

## Vereisten

- MikoPBX 2024.1 of hoger
- De ingebouwde **ModulePhoneBook** module moet geïnstalleerd en actief zijn

---

## Installatie

1. Download de laatste ZIP van de [Releases pagina](../../releases/latest)
2. Ga in MikoPBX naar **Modules → Module Management → Upload new module**
3. Upload de ZIP en activeer de module
4. Voer na installatie de volgende commando's uit via SSH:

```bash
# View symlink corrigeren
rm -f /offload/rootfs/usr/www/src/AdminCabinet/Views/Modules/ModulePhoneBookSync
ln -sf /storage/usbdisk1/mikopbx/custom_modules/ModulePhoneBookSync/App/Views/ModulePhoneBookSync \
    /offload/rootfs/usr/www/src/AdminCabinet/Views/Modules/ModulePhoneBookSync
```

> **Let op:** Na elke herinstallatie moeten bovenstaande commando's opnieuw uitgevoerd worden. Dit is een bekende beperking van de MikoPBX module installer.

---

## Gebruik

### Contacten beheren

Contacten toevoegen en beheren doe je via de ingebouwde **ModulePhoneBook** module:

**Modules → Phonebook**

Alle contacten die je daar toevoegt verschijnen automatisch in de Cloud Phonebook URL's.

### URL's voor IP-telefoons

Configureer de onderstaande URL op je toestellen als remote phonebook:

| Merk | Format parameter | Voorbeeld URL |
|------|-----------------|---------------|
| **Yealink** | `?format=yealink` | `https://jouw-pbx/pbxcore/api/phonebooksync/contacts?format=yealink` |
| **Fanvil** | `?format=fanvil` | `https://jouw-pbx/pbxcore/api/phonebooksync/contacts?format=fanvil` |
| **Snom** | `?format=snom` | `https://jouw-pbx/pbxcore/api/phonebooksync/contacts?format=snom` |
| **Cisco** | `?format=cisco` | `https://jouw-pbx/pbxcore/api/phonebooksync/contacts?format=cisco` |
| **Grandstream** | `?format=grandstream` | `https://jouw-pbx/pbxcore/api/phonebooksync/contacts?format=grandstream` |
| **JSON** | *(geen)* | `https://jouw-pbx/pbxcore/api/phonebooksync/contacts` |

> De URL's zijn publiek toegankelijk — geen authenticatie vereist. Dit is noodzakelijk zodat IP-telefoons het telefoonboek kunnen ophalen.

---

## Toestel configuratie

### Yealink
**Directory → Remote Phone Book → Server URL**

### Fanvil
**Phone Book → Cloud Phone Book → Server URL**

### Snom
**Setup → Advanced → Remote Directory URL**

### Grandstream
**Contacts → LDAP/XML Phonebook → Phonebook XML Server Path**

---

## Hoe werkt het technisch?

```
IP-telefoon
    │
    │ GET /pbxcore/api/phonebooksync/contacts?format=yealink
    ▼
MikoPBX (Cloud Phonebook module)
    │
    ├── PBX Extensions tabel    → interne toestellen (201, 202, ...)
    └── ModulePhoneBook DB      → externe contacten (+31502050400, ...)
    │
    ▼
XML response (YealinkIPPhoneDirectory formaat)
```

De module leest bij elk verzoek live de gegevens uit beide bronnen en geeft ze terug in het gewenste formaat. Er is geen cache — de telefoons krijgen altijd de actuele stand.

---

## Bekende beperkingen

- Na herinstallatie moet de view symlink handmatig gecorrigeerd worden (zie installatie)
- De module beheerpagina toont alleen de URL's — contacten beheer loopt via ModulePhoneBook
- Nummers worden genormaliseerd (streepjes en spaties verwijderd) voor compatibiliteit

---

## Changelog

| Versie | Wijzigingen |
|--------|-------------|
| 1.4.3 | Minimale beheer UI — stabiel, geen JS crashes |
| 1.4.2 | Volledige CRUD API via moduleRestAPICallback |
| 1.4.0 | Nieuwe Semantic UI interface |
| 1.3.1 | Controller hernoemd naar MikoPBX conventie |
| 1.3.0 | BaseController overerving, correcte view paden |
| 1.2.9 | Separate XML formaten per merk |
| 1.2.8 | Nummernormalisatie (streepjes/spaties) |
| 1.2.7 | ModulePhoneBook contacten meelezen |
| 1.2.6 | Fanvil gebruikt Yealink XML formaat |
| 1.2.3 | exit() i.p.v. terminateStreamedResponse() |
| 1.2.2 | Conf klasse hernoemd naar PhoneBookSyncConf |
| 1.2.0 | Publieke REST endpoint werkend |
| 1.0.0 | Eerste versie |

---

## Licentie

[GNU General Public License v3.0](LICENSE)

© 2026 Michiel Meedema — WideM
