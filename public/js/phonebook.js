/**
 * Cloud Phonebook — Frontend  (WideM)
 * v1.2.9  |  EN (default) / NL / DE / FR / RU
 *
 * Werkt standalone (index.html) én als MikoPBX module (geladen via index.volt).
 * Taaldetectie: (1) PBXLanguage uit MikoPBX config (via phonebook-config JSON),
 *               (2) fallback naar Engels
 */
;(function () {
    'use strict';

    // ─────────────────────────────────────────────
    // 1. Config & i18n bootstrap
    // ─────────────────────────────────────────────

    const cfgEl  = document.getElementById('phonebook-config');
    const i18nEl = document.getElementById('phonebook-i18n');

    const CFG = cfgEl ? JSON.parse(cfgEl.textContent) : {
        version:   '1.0.0',
        moduleId:  'ModulePhoneBookSync',
        apiBase:   '/pbxcore/api/modules/ModulePhoneBookSync',
        changelog: {
            '1.0.0': 'Initial release. Auto-sync internal extensions, external contacts CRUD, CallerID integration, multilingual NL/EN/DE/FR.'
        }
    };

    // Built-in translations (fallback when not injected by Volt)
    const FALLBACK_I18N = {
        nl: {
            phonebook_title:'MikoPBX Telefoonboek', version_label:'VERSIE',
            add_external:'Extern contact toevoegen', search_placeholder:'Zoek op naam, nummer of afdeling...',
            filter_all:'Alles', filter_internal:'Intern', filter_external:'Extern',
            contacts_found:'contact(en) gevonden', no_external:'Geen externe contacten gevonden.',
            section_internal:'Interne toestellen — automatisch gesynchroniseerd',
            section_external:'Externe nummers — handmatig beheerd',
            stat_total:'Totaal contacten', stat_internal:'Intern (toestellen)', stat_external:'Extern (nummers)',
            modal_title_add:'Nieuw extern contact', modal_title_edit:'Contact bewerken',
            field_name:'Naam', field_number:'Telefoonnummer', field_department:'Afdeling', field_category:'Categorie',
            field_name_placeholder:'Bedrijfs- of contactnaam', field_number_placeholder:'+31 20 123 4567',
            btn_cancel:'Annuleren', btn_save:'Contact opslaan', btn_update:'Contact bijwerken',
            err_name_required:'Naam is verplicht', err_number_required:'Telefoonnummer is verplicht',
            err_number_invalid:'Ongeldig telefoonnummer', err_duplicate:'Dit nummer bestaat al',
            callerid_sync_ok:'CallerID succesvol gesynchroniseerd', callerid_sync_fail:'Synchronisatie mislukt',
            btn_sync_callerid:'CallerID synchroniseren', btn_import_csv:'CSV importeren', btn_export_csv:'CSV exporteren',
            import_success:'{n} contacten geïmporteerd', import_fail:'Import mislukt: {error}',
            changelog_title:'Versiegeschiedenis', changelog_version:'Versie',
            dept_sales:'Sales', dept_support:'Support', dept_it:'IT', dept_hr:'HR',
            dept_marketing:'Marketing', dept_finance:'Finance', dept_management:'Directie',
            dept_government:'Overheid', dept_telecom:'Telecom', dept_other:'Anders',
            cat_client:'Klant', cat_supplier:'Leverancier', cat_partner:'Partner',
            cat_emergency:'Noodnummer', cat_other:'Anders',
        },
        en: {
            phonebook_title:'MikoPBX Phone Book', version_label:'VERSION',
            add_external:'Add external contact', search_placeholder:'Search by name, number or department...',
            filter_all:'All', filter_internal:'Internal', filter_external:'External',
            contacts_found:'contact(s) found', no_external:'No external contacts found.',
            section_internal:'Internal extensions — auto-synced from PBX',
            section_external:'External numbers — manually managed',
            stat_total:'Total contacts', stat_internal:'Internal (extensions)', stat_external:'External (numbers)',
            modal_title_add:'New external contact', modal_title_edit:'Edit contact',
            field_name:'Name', field_number:'Phone number', field_department:'Department', field_category:'Category',
            field_name_placeholder:'Company or contact name', field_number_placeholder:'+31 20 123 4567',
            btn_cancel:'Cancel', btn_save:'Save contact', btn_update:'Update contact',
            err_name_required:'Name is required', err_number_required:'Phone number is required',
            err_number_invalid:'Invalid phone number', err_duplicate:'This number already exists',
            callerid_sync_ok:'CallerID synced successfully', callerid_sync_fail:'Sync failed',
            btn_sync_callerid:'Sync CallerID', btn_import_csv:'Import CSV', btn_export_csv:'Export CSV',
            import_success:'{n} contacts imported', import_fail:'Import failed: {error}',
            changelog_title:'Changelog', changelog_version:'Version',
            dept_sales:'Sales', dept_support:'Support', dept_it:'IT', dept_hr:'HR',
            dept_marketing:'Marketing', dept_finance:'Finance', dept_management:'Management',
            dept_government:'Government', dept_telecom:'Telecom', dept_other:'Other',
            cat_client:'Client', cat_supplier:'Supplier', cat_partner:'Partner',
            cat_emergency:'Emergency', cat_other:'Other',
        },
        de: {
            phonebook_title:'MikoPBX Telefonbuch', version_label:'VERSION',
            add_external:'Externen Kontakt hinzufügen', search_placeholder:'Nach Name, Nummer oder Abteilung suchen...',
            filter_all:'Alle', filter_internal:'Intern', filter_external:'Extern',
            contacts_found:'Kontakt(e) gefunden', no_external:'Keine externen Kontakte gefunden.',
            section_internal:'Interne Nebenstellen — automatisch synchronisiert',
            section_external:'Externe Nummern — manuell verwaltet',
            stat_total:'Kontakte gesamt', stat_internal:'Intern (Nebenstellen)', stat_external:'Extern (Nummern)',
            modal_title_add:'Neuer externer Kontakt', modal_title_edit:'Kontakt bearbeiten',
            field_name:'Name', field_number:'Telefonnummer', field_department:'Abteilung', field_category:'Kategorie',
            field_name_placeholder:'Firmen- oder Kontaktname', field_number_placeholder:'+49 30 123 4567',
            btn_cancel:'Abbrechen', btn_save:'Kontakt speichern', btn_update:'Kontakt aktualisieren',
            err_name_required:'Name ist erforderlich', err_number_required:'Telefonnummer ist erforderlich',
            err_number_invalid:'Ungültige Telefonnummer', err_duplicate:'Diese Nummer existiert bereits',
            callerid_sync_ok:'CallerID erfolgreich synchronisiert', callerid_sync_fail:'Synchronisierung fehlgeschlagen',
            btn_sync_callerid:'CallerID synchronisieren', btn_import_csv:'CSV importieren', btn_export_csv:'CSV exportieren',
            import_success:'{n} Kontakte importiert', import_fail:'Import fehlgeschlagen: {error}',
            changelog_title:'Versionshistorie', changelog_version:'Version',
            dept_sales:'Vertrieb', dept_support:'Support', dept_it:'IT', dept_hr:'Personal',
            dept_marketing:'Marketing', dept_finance:'Finanzen', dept_management:'Geschäftsführung',
            dept_government:'Behörde', dept_telecom:'Telekommunikation', dept_other:'Sonstige',
            cat_client:'Kunde', cat_supplier:'Lieferant', cat_partner:'Partner',
            cat_emergency:'Notfall', cat_other:'Sonstige',
        },
        fr: {
            phonebook_title:'Cloud Annuaire', version_label:'VERSION',
            add_external:'Ajouter un contact externe', search_placeholder:'Rechercher par nom, numéro ou département...',
            filter_all:'Tous', filter_internal:'Interne', filter_external:'Externe',
            contacts_found:'contact(s) trouvé(s)', no_external:'Aucun contact externe trouvé.',
            section_internal:'Extensions internes — synchronisées automatiquement',
            section_external:'Numéros externes — gérés manuellement',
            stat_total:'Total contacts', stat_internal:'Interne (extensions)', stat_external:'Externe (numéros)',
            modal_title_add:'Nouveau contact externe', modal_title_edit:'Modifier le contact',
            field_name:'Nom', field_number:'Numéro de téléphone', field_department:'Département', field_category:'Catégorie',
            field_name_placeholder:'Nom de la société ou du contact', field_number_placeholder:'+33 1 23 45 67 89',
            btn_cancel:'Annuler', btn_save:'Enregistrer le contact', btn_update:'Mettre à jour',
            err_name_required:'Le nom est obligatoire', err_number_required:'Le numéro est obligatoire',
            err_number_invalid:'Numéro invalide', err_duplicate:'Ce numéro existe déjà',
            callerid_sync_ok:'Annuaire CallerID synchronisé', callerid_sync_fail:'Échec de la synchronisation',
            btn_sync_callerid:'Synchroniser CallerID', btn_import_csv:'Importer CSV', btn_export_csv:'Exporter CSV',
            import_success:'{n} contacts importés', import_fail:'Échec importation: {error}',
            changelog_title:'Journal des modifications', changelog_version:'Version',
            dept_sales:'Commercial', dept_support:'Support', dept_it:'Informatique', dept_hr:'RH',
            dept_marketing:'Marketing', dept_finance:'Finance', dept_management:'Direction',
            dept_government:'Administration', dept_telecom:'Télécom', dept_other:'Autre',
            cat_client:'Client', cat_supplier:'Fournisseur', cat_partner:'Partenaire',
            cat_emergency:'Urgence', cat_other:'Autre',
        },
        ru: {
            phonebook_title:'Облачный телефонный справочник', version_label:'ВЕРСИЯ',
            add_external:'Добавить внешний контакт', search_placeholder:'Поиск по имени, номеру или отделу...',
            filter_all:'Все', filter_internal:'Внутренние', filter_external:'Внешние',
            contacts_found:'контакт(ов) найдено', no_external:'Внешние контакты не найдены.',
            section_internal:'Внутренние номера — автоматически синхронизированы',
            section_external:'Внешние номера — управляются вручную',
            stat_total:'Всего контактов', stat_internal:'Внутренние (добавочные)', stat_external:'Внешние (номера)',
            modal_title_add:'Новый внешний контакт', modal_title_edit:'Редактировать контакт',
            field_name:'Имя', field_number:'Телефонный номер', field_department:'Отдел', field_category:'Категория',
            field_name_placeholder:'Название компании или имя контакта', field_number_placeholder:'+7 495 123 4567',
            btn_cancel:'Отмена', btn_save:'Сохранить контакт', btn_update:'Обновить контакт',
            err_name_required:'Имя обязательно', err_number_required:'Номер телефона обязателен',
            err_number_invalid:'Неверный номер телефона', err_duplicate:'Этот номер уже существует',
            callerid_sync_ok:'CallerID успешно синхронизирован', callerid_sync_fail:'Ошибка синхронизации CallerID',
            btn_sync_callerid:'Синхронизировать CallerID', btn_import_csv:'Импорт CSV', btn_export_csv:'Экспорт CSV',
            import_success:'Импортировано контактов: {n}', import_fail:'Ошибка импорта: {error}',
            changelog_title:'История изменений', changelog_version:'Версия',
            dept_sales:'Продажи', dept_support:'Поддержка', dept_it:'ИТ', dept_hr:'Кадры',
            dept_marketing:'Маркетинг', dept_finance:'Финансы', dept_management:'Руководство',
            dept_government:'Государство', dept_telecom:'Телеком', dept_other:'Другое',
            cat_client:'Клиент', cat_supplier:'Поставщик', cat_partner:'Партнёр',
            cat_emergency:'Экстренный', cat_other:'Другое',
        }
    };

    // Taal detectie op basis van MikoPBX PBXLanguage instelling
    // PBXLanguage formaat: "nl-nl", "en-gb", "ru-ru", "de-de", "fr-fr"
    // Mapping: eerste 2 tekens naar onze taalcode
    const LANG_MAP = {
        'nl':'nl', 'en':'en', 'de':'de', 'fr':'fr', 'ru':'ru',
        'be':'ru', 'uk':'ru', 'kk':'ru',
    };

    function detectLang() {
        // 1. PBXLanguage via phonebook-config JSON (meegegeven door Volt controller)
        const pbxLang = (CFG.pbxLang || '').toLowerCase().slice(0, 2);
        if (pbxLang && LANG_MAP[pbxLang] && FALLBACK_I18N[LANG_MAP[pbxLang]]) {
            return LANG_MAP[pbxLang];
        }
        // 2. Fallback: Engels
        return 'en';
    }

    let currentLang = detectLang();

    function getI18n() {
        // If injected by Volt, prefer that; else use built-in
        if (i18nEl) {
            try { return JSON.parse(i18nEl.textContent); } catch (e) {}
        }
        return FALLBACK_I18N[currentLang] || FALLBACK_I18N.nl;
    }

    function t(key, vars) {
        let str = getI18n()[key] || key;
        if (vars) {
            Object.entries(vars).forEach(([k, v]) => { str = str.replace('{' + k + '}', v); });
        }
        return str;
    }

    // ─────────────────────────────────────────────
    // 2. State
    // ─────────────────────────────────────────────

    let state = {
        contacts:    [],   // all contacts (internal + external) from API
        search:      '',
        filter:      'all',   // all | internal | external
        loading:     true,
        modal:       null,    // null | 'add' | 'edit' | 'changelog'
        editContact: null,
        toast:       null,
        syncBusy:    false,
        importBusy:  false,
        lang:        currentLang,
    };

    // ─────────────────────────────────────────────
    // 3. API helpers
    // ─────────────────────────────────────────────

    const API = CFG.apiBase;

    async function apiFetch(method, path, body) {
        const opts = { method, headers: { 'Content-Type': 'application/json' } };
        if (body) opts.body = JSON.stringify(body);
        const r = await fetch(API + path, opts);
        return r.json();
    }

    async function loadContacts() {
        state.loading = true; render();
        try {
            const data = await apiFetch('GET', '/contacts');
            state.contacts = data.data?.contacts || [];
        } catch (e) {
            showToast('error', 'API error: ' + e.message);
        }
        state.loading = false; render();
    }

    async function saveContact(form) {
        const data = await apiFetch('POST', '/contacts', form);
        if (data.success) {
            state.contacts.push(data.data.contact);
            state.modal = null;
            showToast('success', t('callerid_sync_ok'));
        } else {
            return data.messages?.[0] || 'error';
        }
        render();
        return null;
    }

    async function updateContact(id, form) {
        const data = await apiFetch('PUT', '/contacts/' + id, form);
        if (data.success) {
            const idx = state.contacts.findIndex(c => c.id === id);
            if (idx >= 0) Object.assign(state.contacts[idx], form);
            state.modal = null;
            showToast('success', t('callerid_sync_ok'));
        } else {
            return data.messages?.[0] || 'error';
        }
        render();
        return null;
    }

    async function deleteContact(id) {
        const data = await apiFetch('DELETE', '/contacts/' + id);
        if (data.success) {
            state.contacts = state.contacts.filter(c => c.id !== id);
            showToast('success', t('callerid_sync_ok'));
            render();
        }
    }

    async function syncCallerID() {
        state.syncBusy = true; render();
        const data = await apiFetch('POST', '/sync-callerid');
        state.syncBusy = false;
        showToast(data.success ? 'success' : 'error',
                  data.success ? t('callerid_sync_ok') : t('callerid_sync_fail'));
        render();
    }

    async function exportCsv() {
        const data = await apiFetch('GET', '/export-csv');
        if (data.success) {
            const blob = new Blob([data.data.csv], { type: 'text/csv' });
            const url  = URL.createObjectURL(blob);
            const a    = document.createElement('a');
            a.href = url; a.download = 'phonebook_export.csv'; a.click();
            URL.revokeObjectURL(url);
        }
    }

    async function importCsv(csvText) {
        state.importBusy = true; render();
        const data = await apiFetch('POST', '/import-csv', { csv: csvText });
        state.importBusy = false;
        if (data.success) {
            showToast('success', t('import_success', { n: data.data.imported }));
            await loadContacts();
        } else {
            showToast('error', t('import_fail', { error: (data.messages || []).join(', ') }));
            render();
        }
    }

    // ─────────────────────────────────────────────
    // 4. Toast
    // ─────────────────────────────────────────────

    function showToast(type, msg) {
        state.toast = { type, msg };
        render();
        setTimeout(() => { state.toast = null; render(); }, 3500);
    }

    // ─────────────────────────────────────────────
    // 5. Filtered contacts
    // ─────────────────────────────────────────────

    function getFiltered() {
        const q = state.search.toLowerCase();
        return state.contacts.filter(c => {
            const matchType = state.filter === 'all' || c.type === state.filter;
            const num       = c.number || c.extension || '';
            const matchSearch = !q ||
                (c.name || '').toLowerCase().includes(q) ||
                num.includes(q) ||
                (c.department || '').toLowerCase().includes(q);
            return matchType && matchSearch;
        });
    }

    // ─────────────────────────────────────────────
    // 6. HTML building helpers (vanilla, no vdom)
    // ─────────────────────────────────────────────

    function h(tag, attrs, ...children) {
        const el = document.createElement(tag);
        Object.entries(attrs || {}).forEach(([k, v]) => {
            if (k === 'style' && typeof v === 'object') {
                Object.assign(el.style, v);
            } else if (k.startsWith('on')) {
                el.addEventListener(k.slice(2).toLowerCase(), v);
            } else if (k === 'className') {
                el.className = v;
            } else {
                el.setAttribute(k, v);
            }
        });
        children.flat(Infinity).forEach(child => {
            if (child == null) return;
            el.appendChild(typeof child === 'string' ? document.createTextNode(child) : child);
        });
        return el;
    }

    function initials(name) {
        return (name || '?').split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase();
    }

    // ─────────────────────────────────────────────
    // 7. Component: ContactCard
    // ─────────────────────────────────────────────

    function ContactCard(contact) {
        const isInt = contact.type === 'internal';
        const num   = contact.number || contact.extension || '';

        const avatar = h('div', {
            style: {
                width:'38px', height:'38px', borderRadius:'50%', flexShrink:'0',
                background: isInt ? '#1a3a5c' : '#1a3d2e',
                color: isInt ? '#7ec8f7' : '#5dca9c',
                display:'flex', alignItems:'center', justifyContent:'center',
                fontSize:'12px', fontWeight:'600', letterSpacing:'1px',
                fontFamily:"'DM Mono', monospace",
            }
        }, initials(contact.name));

        const nameBadge = h('span', {
            style: {
                fontSize:'10px', fontWeight:'600', letterSpacing:'0.08em',
                padding:'2px 7px', borderRadius:'4px', textTransform:'uppercase',
                fontFamily:"'DM Mono', monospace",
                background: isInt ? '#0e2d4a' : '#0d2e1f',
                color: isInt ? '#5ab4f0' : '#4ecf8c',
                border: isInt ? '1px solid #1a4a6e' : '1px solid #1a5535',
            }
        }, isInt ? t('filter_internal') : t('filter_external'));

        const deptBadge = contact.department ? h('span', {
            style: {
                fontSize:'10px', fontWeight:'600', letterSpacing:'0.08em',
                padding:'2px 7px', borderRadius:'4px', textTransform:'uppercase',
                fontFamily:"'DM Mono', monospace",
                background:'#1e1e2e', color:'#9891c8', border:'1px solid #3a3560',
            }
        }, contact.department) : null;

        const catBadge = (!isInt && contact.category) ? h('span', {
            style: {
                fontSize:'10px', fontWeight:'600', letterSpacing:'0.08em',
                padding:'2px 7px', borderRadius:'4px', textTransform:'uppercase',
                fontFamily:"'DM Mono', monospace",
                background:'#2e1e0e', color:'#d4954e', border:'1px solid #5a3e1a',
            }
        }, contact.category) : null;

        const nameRow = h('div', { style:{display:'flex',alignItems:'center',gap:'8px',marginBottom:'3px'} },
            h('span', { style:{color:'#dce8f5',fontSize:'14px',fontWeight:'500',fontFamily:"'DM Sans',sans-serif"} }, contact.name),
            nameBadge
        );

        const numRow = h('div', { style:{display:'flex',alignItems:'center',gap:'8px',flexWrap:'wrap'} },
            h('span', { style:{color:'#5ab4f0',fontSize:'13px',fontFamily:"'DM Mono',monospace"} }, num),
            deptBadge,
            catBadge
        );

        const info = h('div', { style:{flex:'1',minWidth:'0'} }, nameRow, numRow);

        const actions = [];
        if (!isInt) {
            // Edit button
            const editBtn = h('button', {
                title:'Bewerken',
                style:{background:'none',border:'none',cursor:'pointer',color:'#4a5568',padding:'6px',borderRadius:'4px',lineHeight:'1',transition:'color 0.15s'},
                onMouseenter: e => e.target.style.color = '#5ab4f0',
                onMouseleave: e => e.target.style.color = '#4a5568',
                onClick: () => { state.modal = 'edit'; state.editContact = { ...contact }; render(); }
            });
            editBtn.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>';

            // Delete button
            const delBtn = h('button', {
                title:'Verwijderen',
                style:{background:'none',border:'none',cursor:'pointer',color:'#4a5568',padding:'6px',borderRadius:'4px',lineHeight:'1',transition:'color 0.15s'},
                onMouseenter: e => e.target.style.color = '#e05050',
                onMouseleave: e => e.target.style.color = '#4a5568',
                onClick: () => { if (confirm(contact.name + '?')) deleteContact(contact.id); }
            });
            delBtn.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3,6 5,6 21,6"/><path d="M19,6l-1,14H6L5,6"/><path d="M10,11v6M14,11v6"/><path d="M9,6V4h6v2"/></svg>';

            actions.push(editBtn, delBtn);
        }

        const card = h('div', {
            style:{
                background:'#0f1623', border:'1px solid #1e2d3d', borderRadius:'8px',
                padding:'12px 16px', display:'flex', alignItems:'center', gap:'12px',
                transition:'border-color 0.15s',
            },
            onMouseenter: e => e.currentTarget.style.borderColor = '#2a4060',
            onMouseleave: e => e.currentTarget.style.borderColor = '#1e2d3d',
        }, avatar, info, ...actions);

        return card;
    }

    // ─────────────────────────────────────────────
    // 8. Modal: Add / Edit
    // ─────────────────────────────────────────────

    function ContactModal() {
        const isEdit    = state.modal === 'edit';
        const initial   = isEdit ? { ...state.editContact } : { name:'', number:'', department:'', category:'', notes:'' };
        let formData    = { ...initial };
        let errorMsg    = '';

        const DEPTS = ['dept_sales','dept_support','dept_it','dept_hr','dept_marketing','dept_finance','dept_management','dept_government','dept_telecom','dept_other'];
        const CATS  = ['cat_client','cat_supplier','cat_partner','cat_emergency','cat_other'];

        const inputStyle = {
            width:'100%', boxSizing:'border-box',
            background:'#0a0f1a', border:'1px solid #1e2d3d', borderRadius:'6px',
            padding:'9px 12px', color:'#dce8f5', fontSize:'14px',
            fontFamily:"'DM Sans',sans-serif", outline:'none',
        };

        const labelStyle = {
            display:'block', fontSize:'11px', fontWeight:'600', letterSpacing:'0.06em',
            color:'#5a7090', marginBottom:'5px', textTransform:'uppercase',
            fontFamily:"'DM Mono',monospace",
        };

        function field(labelKey, inputEl) {
            return h('div', {},
                h('label', { style: labelStyle }, t(labelKey)),
                inputEl
            );
        }

        const nameInput = h('input', {
            type:'text', placeholder: t('field_name_placeholder'),
            value: formData.name, style: inputStyle,
            onInput: e => formData.name = e.target.value,
            onFocus: e => e.target.style.borderColor = '#2a5a8a',
            onBlur:  e => e.target.style.borderColor = '#1e2d3d',
        });

        const numInput = h('input', {
            type:'text', placeholder: t('field_number_placeholder'),
            value: formData.number, style: inputStyle,
            onInput: e => formData.number = e.target.value,
            onFocus: e => e.target.style.borderColor = '#2a5a8a',
            onBlur:  e => e.target.style.borderColor = '#1e2d3d',
        });

        const deptSelect = h('select', {
            style: { ...inputStyle, cursor:'pointer' },
            onFocus: e => e.target.style.borderColor = '#2a5a8a',
            onBlur:  e => e.target.style.borderColor = '#1e2d3d',
            onChange: e => formData.department = e.target.value,
        }, ...DEPTS.map(k => {
            const opt = h('option', { value: t(k) }, t(k));
            if (formData.department === t(k)) opt.selected = true;
            return opt;
        }));

        const catSelect = h('select', {
            style: { ...inputStyle, cursor:'pointer' },
            onFocus: e => e.target.style.borderColor = '#2a5a8a',
            onBlur:  e => e.target.style.borderColor = '#1e2d3d',
            onChange: e => formData.category = e.target.value,
        }, ...CATS.map(k => {
            const opt = h('option', { value: t(k) }, t(k));
            if (formData.category === t(k)) opt.selected = true;
            return opt;
        }));

        const notesInput = h('input', {
            type:'text', placeholder:'...',
            value: formData.notes || '', style: inputStyle,
            onInput: e => formData.notes = e.target.value,
            onFocus: e => e.target.style.borderColor = '#2a5a8a',
            onBlur:  e => e.target.style.borderColor = '#1e2d3d',
        });

        const errEl = h('p', { style:{margin:0,color:'#e05050',fontSize:'13px',fontFamily:"'DM Mono',monospace",display:'none'} });

        const saveBtn = h('button', {
            style:{
                flex:'2', padding:'10px 16px', borderRadius:'6px', border:'none',
                background:'#1a4a6e', color:'#7ec8f7', cursor:'pointer',
                fontSize:'14px', fontWeight:'500', fontFamily:"'DM Sans',sans-serif",
            },
            onClick: async () => {
                const name   = nameInput.value.trim();
                const number = numInput.value.trim();
                if (!name)   { errEl.textContent = t('err_name_required');   errEl.style.display='block'; return; }
                if (!number) { errEl.textContent = t('err_number_required'); errEl.style.display='block'; return; }
                const clean = number.replace(/[\s\-\(\)]/g,'');
                if (!/^[\+\d]{6,20}$/.test(clean)) { errEl.textContent = t('err_number_invalid'); errEl.style.display='block'; return; }

                formData.name = name; formData.number = number;
                if (!formData.department) formData.department = t('dept_other');
                if (!formData.category)   formData.category   = t('cat_other');

                let apiErr;
                if (isEdit) {
                    apiErr = await updateContact(state.editContact.id, formData);
                } else {
                    apiErr = await saveContact(formData);
                }
                if (apiErr) {
                    errEl.textContent = t('err_' + apiErr) || apiErr;
                    errEl.style.display = 'block';
                }
            }
        }, isEdit ? t('btn_update') : t('btn_save'));

        const cancelBtn = h('button', {
            style:{
                flex:'1', padding:'10px 16px', borderRadius:'6px',
                border:'1px solid #1e2d3d', background:'none',
                color:'#7a90a8', cursor:'pointer', fontSize:'14px',
                fontFamily:"'DM Sans',sans-serif",
            },
            onClick: () => { state.modal = null; state.editContact = null; render(); }
        }, t('btn_cancel'));

        // Close X
        const closeBtn = h('button', {
            style:{background:'none',border:'none',color:'#4a5568',cursor:'pointer',padding:'4px'},
            onClick: () => { state.modal = null; state.editContact = null; render(); }
        });
        closeBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';

        const overlay = h('div', {
            style:{
                position:'fixed',inset:'0',background:'rgba(0,0,0,0.72)',
                display:'flex',alignItems:'center',justifyContent:'center',zIndex:'200',
            },
            onClick: e => { if (e.target === overlay) { state.modal = null; state.editContact = null; render(); } }
        },
            h('div', {
                style:{
                    background:'#0d1420', border:'1px solid #1e2d3d', borderRadius:'12px',
                    padding:'28px', width:'420px', maxWidth:'95vw',
                    boxShadow:'0 24px 60px rgba(0,0,0,0.5)',
                }
            },
                h('div', {style:{display:'flex',alignItems:'center',justifyContent:'space-between',marginBottom:'24px'}},
                    h('h2', {style:{margin:'0',color:'#dce8f5',fontSize:'17px',fontWeight:'500',fontFamily:"'DM Sans',sans-serif"}},
                        isEdit ? t('modal_title_edit') : t('modal_title_add')),
                    closeBtn
                ),
                h('div', {style:{display:'flex',flexDirection:'column',gap:'16px'}},
                    field('field_name',   nameInput),
                    field('field_number', numInput),
                    h('div', {style:{display:'grid',gridTemplateColumns:'1fr 1fr',gap:'12px'}},
                        field('field_department', deptSelect),
                        field('field_category',   catSelect)
                    ),
                    errEl
                ),
                h('div', {style:{display:'flex',gap:'10px',marginTop:'24px'}}, cancelBtn, saveBtn)
            )
        );

        return overlay;
    }

    // ─────────────────────────────────────────────
    // 9. Changelog modal
    // ─────────────────────────────────────────────

    function ChangelogModal() {
        const entries = Object.entries(CFG.changelog || {}).reverse();
        const closeBtn = h('button', {
            style:{background:'none',border:'none',color:'#4a5568',cursor:'pointer',padding:'4px'},
            onClick: () => { state.modal = null; render(); }
        });
        closeBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';

        const rows = entries.map(([ver, notes]) => {
            const note = typeof notes === 'object'
                ? (notes[state.lang] || notes.en || notes.nl || Object.values(notes)[0])
                : notes;
            return h('div', {style:{marginBottom:'12px'}},
                h('div', {style:{fontSize:'12px',fontWeight:'600',color:'#5ab4f0',fontFamily:"'DM Mono',monospace",marginBottom:'4px'}},
                    t('changelog_version') + ' ' + ver),
                h('div', {style:{fontSize:'13px',color:'#8aa0b8',lineHeight:'1.6'}}, note)
            );
        });

        const overlay = h('div', {
            style:{position:'fixed',inset:'0',background:'rgba(0,0,0,0.72)',display:'flex',alignItems:'center',justifyContent:'center',zIndex:'200'},
            onClick: e => { if (e.target === overlay) { state.modal = null; render(); } }
        },
            h('div', {style:{background:'#0d1420',border:'1px solid #1e2d3d',borderRadius:'12px',padding:'28px',width:'480px',maxWidth:'95vw',maxHeight:'70vh',overflowY:'auto'}},
                h('div', {style:{display:'flex',alignItems:'center',justifyContent:'space-between',marginBottom:'20px'}},
                    h('h2', {style:{margin:'0',color:'#dce8f5',fontSize:'17px',fontWeight:'500',fontFamily:"'DM Sans',sans-serif"}}, t('changelog_title')),
                    closeBtn
                ),
                ...rows
            )
        );
        return overlay;
    }

    // ─────────────────────────────────────────────
    // 10. Main render
    // ─────────────────────────────────────────────

    function render() {
        const root = document.getElementById('phonebook-root');
        if (!root) return;
        root.innerHTML = '';

        const filtered  = getFiltered();
        const internals = filtered.filter(c => c.type === 'internal');
        const externals = filtered.filter(c => c.type === 'external');
        const allInt    = state.contacts.filter(c => c.type === 'internal');
        const allExt    = state.contacts.filter(c => c.type === 'external');

        // ── Font import
        if (!document.getElementById('pb-fonts')) {
            const lnk = document.createElement('link');
            lnk.id = 'pb-fonts'; lnk.rel = 'stylesheet';
            lnk.href = 'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap';
            document.head.appendChild(lnk);
        }

        // ── Toast
        if (state.toast) {
            const toast = h('div', {
                style:{
                    position:'fixed', bottom:'28px', right:'28px', zIndex:'300',
                    background: state.toast.type === 'success' ? '#0d2e1f' : '#2e0d0d',
                    border: state.toast.type === 'success' ? '1px solid #1a5535' : '1px solid #551a1a',
                    color: state.toast.type === 'success' ? '#4ecf8c' : '#e05050',
                    borderRadius:'8px', padding:'12px 20px', fontSize:'13px',
                    fontFamily:"'DM Mono',monospace", maxWidth:'320px',
                    boxShadow:'0 8px 24px rgba(0,0,0,0.4)',
                }
            }, state.toast.msg);
            document.body.appendChild(toast);
        }

        // ── Language switcher verwijderd: taal volgt PBX interface-instelling automatisch

        // ── Header
        const iconBox = h('div', {
            style:{width:'36px',height:'36px',borderRadius:'8px',background:'#0e2d4a',display:'flex',alignItems:'center',justifyContent:'center'}
        });
        iconBox.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#5ab4f0" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81 19.79 19.79 0 01.01 1.18 2 2 0 012 0h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>';

        const versionBadge = h('span', {
            style:{fontSize:'10px',color:'#3a5070',fontFamily:"'DM Mono',monospace",letterSpacing:'0.04em',cursor:'pointer',textDecoration:'underline dotted'},
            onClick: () => { state.modal = 'changelog'; render(); },
            title: t('changelog_title'),
        }, t('version_label') + ' ' + CFG.version);

        const addBtn = h('button', {
            style:{
                display:'flex',alignItems:'center',gap:'7px',
                background:'#1a4a6e',border:'1px solid #2a6090',
                color:'#7ec8f7',borderRadius:'7px',padding:'8px 14px',
                cursor:'pointer',fontSize:'13px',fontWeight:'500',
                fontFamily:"'DM Sans',sans-serif",
            },
            onClick: () => { state.modal = 'add'; render(); }
        });
        addBtn.innerHTML = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>';
        addBtn.appendChild(document.createTextNode(' ' + t('add_external')));

        const syncBtn = h('button', {
            style:{
                display:'flex',alignItems:'center',gap:'6px',
                background:'none',border:'1px solid #1e2d3d',
                color: state.syncBusy ? '#4a6080' : '#4ecf8c',
                borderRadius:'7px',padding:'8px 12px',
                cursor: state.syncBusy ? 'not-allowed' : 'pointer',
                fontSize:'13px',fontFamily:"'DM Sans',sans-serif",
            },
            onClick: () => { if (!state.syncBusy) syncCallerID(); }
        }, state.syncBusy ? '⟳ ...' : '⟳ ' + t('btn_sync_callerid'));

        // CSV buttons
        const exportBtn = h('button', {
            style:{background:'none',border:'1px solid #1e2d3d',color:'#7a90a8',borderRadius:'7px',padding:'8px 12px',cursor:'pointer',fontSize:'13px',fontFamily:"'DM Sans',sans-serif"},
            onClick: exportCsv
        }, t('btn_export_csv'));

        const importInput = h('input', { type:'file', accept:'.csv', style:{display:'none'} });
        importInput.onchange = async (e) => {
            const file = e.target.files[0];
            if (!file) return;
            const text = await file.text();
            importInput.value = '';
            await importCsv(text);
        };
        const importBtn = h('button', {
            style:{background:'none',border:'1px solid #1e2d3d',color:'#7a90a8',borderRadius:'7px',padding:'8px 12px',cursor:'pointer',fontSize:'13px',fontFamily:"'DM Sans',sans-serif"},
            onClick: () => importInput.click()
        }, t('btn_import_csv'));

        const header = h('div', {
            style:{background:'#0a1020',borderBottom:'1px solid #1e2d3d',borderRadius:'10px 10px 0 0',padding:'14px 22px',display:'flex',alignItems:'center',gap:'12px',flexWrap:'wrap'}
        },
            iconBox,
            h('div', {},
                h('div', {style:{fontSize:'15px',fontWeight:'500',color:'#dce8f5',letterSpacing:'-0.01em',fontFamily:"'DM Sans',sans-serif"}}, t('phonebook_title')),
                versionBadge
            ),
            h('div', {style:{flex:'1'}}),
            exportBtn, importBtn, importInput,
            syncBtn,
            addBtn
        );

        // ── Stats
        const statCards = h('div', {style:{display:'grid',gridTemplateColumns:'repeat(3,1fr)',gap:'10px',marginBottom:'20px'}},
            ...[
                { label: t('stat_total'),    val: state.contacts.length, color:'#5a7090' },
                { label: t('stat_internal'), val: allInt.length,          color:'#5ab4f0' },
                { label: t('stat_external'), val: allExt.length,          color:'#4ecf8c' },
            ].map(s =>
                h('div', {style:{background:'#0a1020',border:'1px solid #1e2d3d',borderRadius:'8px',padding:'12px 16px'}},
                    h('div', {style:{fontSize:'22px',fontWeight:'500',color:s.color,fontFamily:"'DM Mono',monospace"}}, String(s.val)),
                    h('div', {style:{fontSize:'11px',color:'#4a6080',textTransform:'uppercase',letterSpacing:'0.06em',marginTop:'2px'}}, s.label)
                )
            )
        );

        // ── Search + filter
        const searchWrap = h('div', {style:{flex:'1',position:'relative'}});
        const searchIcon = document.createElement('span');
        searchIcon.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#4a6080" stroke-width="2" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);pointer-events:none"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>';
        const searchInput = h('input', {
            type:'text', placeholder: t('search_placeholder'), value: state.search,
            style:{width:'100%',boxSizing:'border-box',paddingLeft:'36px',paddingRight:'12px',paddingTop:'9px',paddingBottom:'9px',background:'#0a1020',border:'1px solid #1e2d3d',borderRadius:'7px',color:'#dce8f5',fontSize:'14px',outline:'none',fontFamily:"'DM Sans',sans-serif"},
            onInput: e => { state.search = e.target.value; render(); },
        });
        searchWrap.appendChild(searchIcon);
        searchWrap.appendChild(searchInput);

        const filterRow = h('div', {style:{display:'flex',gap:'8px',marginBottom:'18px',flexWrap:'wrap'}},
            searchWrap,
            ...['all','internal','external'].map(f =>
                h('button', {
                    style:{
                        padding:'9px 14px',borderRadius:'7px',cursor:'pointer',
                        border: state.filter === f ? '1px solid #2a5a8a' : '1px solid #1e2d3d',
                        background: state.filter === f ? '#0e2d4a' : 'none',
                        color: state.filter === f ? '#7ec8f7' : '#5a7090',
                        fontSize:'13px',fontFamily:"'DM Sans',sans-serif",fontWeight: state.filter===f?'500':'400',
                    },
                    onClick: () => { state.filter = f; render(); }
                }, t('filter_' + f))
            )
        );

        // ── Result count
        const countEl = h('div', {style:{fontSize:'11px',color:'#3a5070',fontFamily:"'DM Mono',monospace",letterSpacing:'0.06em',marginBottom:'10px',textTransform:'uppercase'}},
            filtered.length + ' ' + t('contacts_found')
        );

        // ── Divider helper
        function sectionDivider(label) {
            return h('div', {style:{display:'flex',alignItems:'center',gap:'10px',marginBottom:'10px',marginTop:'4px'}},
                h('div', {style:{height:'1px',flex:'1',background:'#1e2d3d'}}),
                h('span', {style:{fontSize:'10px',color:'#3a5070',fontFamily:"'DM Mono',monospace",letterSpacing:'0.08em',textTransform:'uppercase',whiteSpace:'nowrap'}}, label),
                h('div', {style:{height:'1px',flex:'1',background:'#1e2d3d'}})
            );
        }

        // ── Contact lists
        const contactsArea = h('div', {});

        if (state.loading) {
            contactsArea.appendChild(h('div', {style:{textAlign:'center',padding:'40px 0',color:'#3a5070',fontSize:'13px',fontFamily:"'DM Mono',monospace"}}, '...'));
        } else {
            if (state.filter !== 'external' && internals.length > 0) {
                const intSection = h('div', {style:{marginBottom:'20px'}},
                    sectionDivider(t('section_internal')),
                    h('div', {style:{display:'flex',flexDirection:'column',gap:'6px'}},
                        ...internals.map(ContactCard)
                    )
                );
                contactsArea.appendChild(intSection);
            }

            if (state.filter !== 'internal') {
                const extCards = externals.length > 0
                    ? h('div', {style:{display:'flex',flexDirection:'column',gap:'6px'}}, ...externals.map(ContactCard))
                    : h('div', {style:{textAlign:'center',padding:'28px 0',color:'#3a5070',fontSize:'13px',fontFamily:"'DM Mono',monospace"}}, t('no_external'));

                contactsArea.appendChild(h('div', {},
                    sectionDivider(t('section_external')),
                    extCards
                ));
            }
        }

        // ── Assemble
        const inner = h('div', {style:{padding:'22px 22px'}},
            statCards, filterRow, countEl, contactsArea
        );

        root.appendChild(header);
        root.appendChild(inner);

        // ── Modals
        if (state.modal === 'add' || state.modal === 'edit') {
            document.body.appendChild(ContactModal());
        }
        if (state.modal === 'changelog') {
            document.body.appendChild(ChangelogModal());
        }
    }

    // ─────────────────────────────────────────────
    // 11. Boot
    // ─────────────────────────────────────────────

    function boot() {
        // Inject wrapper styles
        const style = document.createElement('style');
        style.textContent = `
            #phonebook-root { font-family: 'DM Sans', sans-serif; }
            #phonebook-root * { box-sizing: border-box; }
            #phonebook-root select option { background: #0d1420; color: #dce8f5; }
        `;
        document.head.appendChild(style);

        if (CFG.apiBase && !CFG.apiBase.includes('STANDALONE')) {
            loadContacts();
        } else {
            // Standalone demo mode with fake data
            state.contacts = [
                {id:'int_1',name:'Receptie',number:'100',department:'Algemeen',type:'internal',readonly:true},
                {id:'int_2',name:'Jan de Vries',number:'101',department:'Sales',type:'internal',readonly:true},
                {id:'int_3',name:'Anouk Smit',number:'102',department:'Support',type:'internal',readonly:true},
                {id:'int_4',name:'Peter Bakker',number:'103',department:'IT',type:'internal',readonly:true},
                {id:'int_5',name:'Conferencezaal A',number:'200',department:'Vergadering',type:'internal',readonly:true},
                {id:101,name:'KPN Zakelijk',number:'+31 70 343 4343',department:'Telecom',category:'Leverancier',type:'external',readonly:false},
                {id:102,name:'Gemeente Amsterdam',number:'+31 20 624 1111',department:'Overheid',category:'Partner',type:'external',readonly:false},
            ];
            state.loading = false;
            render();
        }
    }

    document.addEventListener('DOMContentLoaded', boot);

})();
