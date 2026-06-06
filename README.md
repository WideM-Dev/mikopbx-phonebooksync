# Cloud Phonebook voor MikoPBX

**Versie:** 1.4.3  
**Ontwikkelaar:** Michiel Meedema — [WideM](https://widem.nl) (support@widem.nl)  
**Compatibiliteit:** MikoPBX 2024.1+ / 2026.x  
**Licentie:** GPLv3

---

## Wat doet deze module?

Cloud Phonebook biedt één publieke URL waarmee IP-telefoons een actueel en gecombineerd telefoonboek kunnen ophalen. De module combineert automatisch:

1. **Interne toestellen** — direct uit de PBX Extensions tabel (201 - Olaf, 202 - Homme, enz.)
2. **ModulePhoneBook contacten** — uit de ingebouwde MikoPBX Phonebook module (externe nummers zoals klanten, leveranciers, enz.)

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
# View symlink corrigeren (vereist na elke installatie)
rm -f /offload/rootfs/usr/www/src/AdminCabinet/Views/Modules/ModulePhoneBookSync
ln -sf /storage/usbdisk1/mikopbx/custom_modules/ModulePhoneBookSync/App/Views/ModulePhoneBookSync \
    /offload/rootfs/usr/www/src/AdminCabinet/Views/Modules/ModulePhoneBookSync
```

---

## Contacten beheren

Contacten toevoegen en beheren doe je via de ingebouwde **ModulePhoneBook** module:

**Modules → Phonebook**

- Interne toestellen worden automatisch opgehaald vanuit de PBX
- Externe nummers voeg je toe via ModulePhoneBook
- Alle contacten verschijnen automatisch in de Cloud Phonebook URL's

---

## URL's voor IP-telefoons

Configureer de onderstaande URL op je toestellen als remote phonebook:

| Merk | URL |
|------|-----|
| **Yealink** | `https://jouw-pbx/pbxcore/api/phonebooksync/contacts?format=yealink` |
| **Fanvil** | `https://jouw-pbx/pbxcore/api/phonebooksync/contacts?format=fanvil` |
| **Snom** | `https://jouw-pbx/pbxcore/api/phonebooksync/contacts?format=snom` |
| **Cisco** | `https://jouw-pbx/pbxcore/api/phonebooksync/contacts?format=cisco` |
| **Grandstream** | `https://jouw-pbx/pbxcore/api/phonebooksync/contacts?format=grandstream` |
| **JSON** | `https://jouw-pbx/pbxcore/api/phonebooksync/contacts` |

> De URL's zijn publiek toegankelijk zonder authenticatie — noodzakelijk zodat IP-telefoons het telefoonboek kunnen ophalen.

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

## Hoe werkt het?

```
IP-telefoon
    │
    │ GET /pbxcore/api/phonebooksync/contacts?format=yealink
    ▼
MikoPBX (Cloud Phonebook module)
    │
    ├── PBX Extensions tabel  →  interne toestellen (201, 202, ...)
    └── ModulePhoneBook DB    →  externe nummers (+31502050400, ...)
    │
    ▼
XML response (YealinkIPPhoneDirectory formaat)
```

De module leest bij elk verzoek live de gegevens uit beide bronnen. Nummers worden genormaliseerd — streepjes en spaties worden gestript zodat het toestel correct kan bellen.

---

## Bekende beperkingen

- Na elke herinstallatie moet de view symlink handmatig gecorrigeerd worden (zie installatie)
- De module beheerpagina toont alleen de URL's — contacten beheer loopt via ModulePhoneBook

---

## Changelog

| Versie | Wijzigingen |
|--------|-------------|
| 1.4.3 | Stabiele minimale beheer UI |
| 1.4.2 | CRUD API via moduleRestAPICallback |
| 1.3.1 | Controller hernoemd naar MikoPBX conventie |
| 1.3.0 | BaseController, correcte view paden |
| 1.2.9 | Separate XML formaten per merk |
| 1.2.8 | Nummernormalisatie (streepjes/spaties) |
| 1.2.7 | ModulePhoneBook contacten meelezen |
| 1.2.2 | Conf klasse hernoemd naar PhoneBookSyncConf |
| 1.2.0 | Publieke REST endpoint werkend |
| 1.0.0 | Eerste versie |

---

## Licentie

[GNU General Public License v3.0](LICENSE)

© 2026 Michiel Meedema — WideM
