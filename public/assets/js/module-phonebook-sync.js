/**
 * Cloud Phonebook — Frontend v1.4.0
 * Semantic UI stijl conform MikoPBX interface
 * Geen eigen styling — gebruikt MikoPBX Semantic UI klassen
 */
;(function () {
    'use strict';

    // ─── Config & i18n ───────────────────────────────────────────────
    const cfgEl  = document.getElementById('phonebook-config');
    const i18nEl = document.getElementById('phonebook-i18n');

    const CFG = cfgEl ? JSON.parse(cfgEl.textContent) : {
        version: '1.4.0',
        moduleId: 'ModulePhoneBookSync',
        apiBase: '/pbxcore/api/modules/ModulePhoneBookSync',
        pbxLang: 'en-gb',
        standalone: false
    };

    let I18N = {};
    try { I18N = i18nEl ? JSON.parse(i18nEl.textContent) : {}; } catch(e) {}

    function t(key) { return I18N[key] || key; }

    // ─── State ───────────────────────────────────────────────────────
    let allContacts = [];
    let currentFilter = 'all';
    let currentSearch = '';

    // ─── API ─────────────────────────────────────────────────────────
    async function apiFetch(method, path, body) {
        const opts = { method, headers: { 'Content-Type': 'application/json' } };
        if (body) opts.body = JSON.stringify(body);
        // Haal Bearer token op via MikoPBX token manager
        if (window.globalPBXToken) {
            opts.headers['Authorization'] = 'Bearer ' + window.globalPBXToken;
        }
        const r = await fetch(CFG.apiBase + path, opts);
        return r.json();
    }

    async function loadContacts() {
        setTableLoading();
        try {
            const data = await apiFetch('GET', '/contacts');
            allContacts = data.data?.contacts || data.contacts || [];
            renderTable();
            updateStats();
        } catch(e) {
            showMessage('error', 'API error: ' + e.message);
        }
    }

    // ─── Filtering ───────────────────────────────────────────────────
    function getFiltered() {
        const q = currentSearch.toLowerCase();
        return allContacts.filter(c => {
            const matchType = currentFilter === 'all' || c.type === currentFilter;
            const num = c.number || c.extension || '';
            const matchSearch = !q ||
                (c.name||'').toLowerCase().includes(q) ||
                num.includes(q) ||
                (c.department||'').toLowerCase().includes(q);
            return matchType && matchSearch;
        });
    }

    // ─── Stats ───────────────────────────────────────────────────────
    function updateStats() {
        const total    = document.getElementById('stat-total');
        const internal = document.getElementById('stat-internal');
        const external = document.getElementById('stat-external');
        if (total)    total.textContent    = allContacts.length;
        if (internal) internal.textContent = allContacts.filter(c=>c.type==='internal').length;
        if (external) external.textContent = allContacts.filter(c=>c.type==='external').length;
    }

    // ─── Table rendering ─────────────────────────────────────────────
    function setTableLoading() {
        const tbody = document.getElementById('phonebook-tbody');
        if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="center aligned"><i class="spinner loading icon"></i></td></tr>';
    }

    function renderTable() {
        const tbody = document.getElementById('phonebook-tbody');
        if (!tbody) return;

        const filtered = getFiltered();

        // Update found count
        const countEl = document.getElementById('contacts-found');
        if (countEl) countEl.textContent = filtered.length + ' ' + t('contacts_found');

        if (filtered.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="center aligned disabled">' + t('no_external') + '</td></tr>';
            return;
        }

        tbody.innerHTML = filtered.map(c => {
            const num     = c.number || c.extension || '';
            const typeLabel = c.type === 'internal'
                ? '<span class="ui blue tiny label">' + t('filter_internal') + '</span>'
                : '<span class="ui green tiny label">' + t('filter_external') + '</span>';
            const actions = c.type === 'external' && !c.readonly
                ? `<button class="ui icon tiny basic button btn-edit" data-id="${c.id}" title="${t('modal_title_edit')}"><i class="edit icon"></i></button>
                   <button class="ui icon tiny basic red button btn-delete" data-id="${c.id}" title="Delete"><i class="trash icon"></i></button>`
                : '';
            return `<tr>
                <td>${typeLabel}</td>
                <td>${escHtml(c.name)}</td>
                <td><code>${escHtml(num)}</code></td>
                <td>${escHtml(c.department||'')}</td>
                <td>${escHtml(c.category||'')}</td>
                <td class="right aligned">${actions}</td>
            </tr>`;
        }).join('');

        // Bind edit/delete buttons
        tbody.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', () => openEditModal(btn.dataset.id));
        });
        tbody.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', () => deleteContact(btn.dataset.id));
        });
    }

    function escHtml(s) {
        return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    // ─── Modal ───────────────────────────────────────────────────────
    function openAddModal() {
        document.getElementById('modal-title').textContent = t('modal_title_add');
        document.getElementById('contact-id').value = '';
        document.getElementById('contact-name').value = '';
        document.getElementById('contact-number').value = '';
        document.getElementById('contact-department').value = 'Other';
        document.getElementById('contact-category').value = 'Other';
        document.getElementById('btn-save-contact').textContent = t('btn_save');
        hideFormError();
        $('#phonebook-modal').modal('show');
    }

    function openEditModal(id) {
        const c = allContacts.find(x => String(x.id) === String(id));
        if (!c) return;
        document.getElementById('modal-title').textContent = t('modal_title_edit');
        document.getElementById('contact-id').value = c.id;
        document.getElementById('contact-name').value = c.name;
        document.getElementById('contact-number').value = c.number;
        document.getElementById('contact-department').value = c.department || 'Other';
        document.getElementById('contact-category').value = c.category || 'Other';
        document.getElementById('btn-save-contact').textContent = t('btn_update');
        hideFormError();
        $('#phonebook-modal').modal('show');
    }

    async function saveContact() {
        const id     = document.getElementById('contact-id').value;
        const name   = document.getElementById('contact-name').value.trim();
        const number = document.getElementById('contact-number').value.trim();
        const dept   = document.getElementById('contact-department').value;
        const cat    = document.getElementById('contact-category').value;

        if (!name)   return showFormError(t('err_name_required'));
        if (!number) return showFormError(t('err_number_required'));

        hideFormError();

        let data;
        if (id) {
            data = await apiFetch('POST', '/updateContact', {id, { name, number, department: dept, category: cat });
        } else {
            data = await apiFetch('POST', '/saveContact', { name, number, department: dept, category: cat });
        }

        if (data.success) {
            $('#phonebook-modal').modal('hide');
            await loadContacts();
        } else {
            const err = data.messages?.error?.[0] || data.messages?.[0] || 'Error';
            showFormError(t('err_' + err) || err);
        }
    }

    async function deleteContact(id) {
        if (!confirm(t('field_name') + '?')) return;
        const data = await apiFetch('POST', '/deleteContact', {id});
        if (data.success) await loadContacts();
    }

    function showFormError(msg) {
        const el = document.getElementById('form-error');
        if (el) { el.textContent = msg; el.style.display = 'block'; }
    }

    function hideFormError() {
        const el = document.getElementById('form-error');
        if (el) el.style.display = 'none';
    }

    function showMessage(type, msg) {
        // Gebruik MikoPBX flash messaging als beschikbaar
        if (window.UserMessage) {
            if (type === 'error') UserMessage.showError(msg);
            else UserMessage.showSuccess(msg);
        } else {
            console.log(type + ': ' + msg);
        }
    }

    // ─── CSV Export ──────────────────────────────────────────────────
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
        const data = await apiFetch('POST', '/import-csv', { csv: csvText });
        if (data.success) {
            showMessage('success', t('import_success').replace('{n}', data.data?.imported || 0));
            await loadContacts();
        } else {
            showMessage('error', t('import_fail').replace('{error}', (data.messages||[]).join(', ')));
        }
    }

    // ─── CallerID sync ───────────────────────────────────────────────
    async function syncCallerID() {
        const btn = document.getElementById('btn-sync-callerid');
        if (btn) { btn.classList.add('loading', 'disabled'); }
        const data = await apiFetch('POST', '/sync-callerid');
        if (btn) { btn.classList.remove('loading', 'disabled'); }
        showMessage(data.success ? 'success' : 'error',
            data.success ? t('callerid_sync_ok') : t('callerid_sync_fail'));
    }

    // ─── Filter buttons ──────────────────────────────────────────────
    function setFilter(f) {
        currentFilter = f;
        ['all','internal','external'].forEach(x => {
            const btn = document.getElementById('filter-' + x);
            if (btn) btn.classList.toggle('active', x === f);
        });
        renderTable();
    }

    // ─── Boot ────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function() {

        // Init Semantic UI dropdowns
        if (window.$) {
            $('.ui.dropdown').dropdown();
            $('#phonebook-modal').modal({
                onApprove: function() { saveContact(); return false; }
            });
        }

        // Zoek
        const searchEl = document.getElementById('phonebook-search');
        if (searchEl) {
            searchEl.addEventListener('input', function() {
                currentSearch = this.value;
                renderTable();
            });
        }

        // Filter knoppen
        ['all','internal','external'].forEach(f => {
            const btn = document.getElementById('filter-' + f);
            if (btn) btn.addEventListener('click', () => setFilter(f));
        });

        // Standaard filter actief
        setFilter('all');

        // Add button
        const addBtn = document.getElementById('btn-add-external');
        if (addBtn) addBtn.addEventListener('click', openAddModal);

        // Export CSV
        const exportBtn = document.getElementById('btn-export-csv');
        if (exportBtn) exportBtn.addEventListener('click', exportCsv);

        // Import CSV
        const importBtn = document.getElementById('btn-import-csv');
        const fileInput = document.getElementById('csv-file-input');
        if (importBtn && fileInput) {
            importBtn.addEventListener('click', () => fileInput.click());
            fileInput.addEventListener('change', async function() {
                if (this.files[0]) {
                    const text = await this.files[0].text();
                    this.value = '';
                    await importCsv(text);
                }
            });
        }

        // Sync CallerID
        const syncBtn = document.getElementById('btn-sync-callerid');
        if (syncBtn) syncBtn.addEventListener('click', syncCallerID);

        // Laad contacten
        loadContacts();
    });

})();
