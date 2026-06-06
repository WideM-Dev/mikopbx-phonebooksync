/**
 * Cloud Phonebook — Frontend v1.4.2
 * Semantic UI — MikoPBX stijl
 * Response gebruikt "result" (niet "success")
 */
;(function () {
    'use strict';

    const cfgEl  = document.getElementById('phonebook-config');
    const i18nEl = document.getElementById('phonebook-i18n');
    const CFG    = cfgEl ? JSON.parse(cfgEl.textContent) : {
        version: '1.4.2', moduleId: 'ModulePhoneBookSync',
        apiBase: '/pbxcore/api/modules/ModulePhoneBookSync', pbxLang: 'nl-nl'
    };
    let I18N = {};
    try { I18N = i18nEl ? JSON.parse(i18nEl.textContent) : {}; } catch(e) {}
    function t(key) { return I18N[key] || key; }

    let allContacts   = [];
    let currentFilter = 'all';
    let currentSearch = '';

    // ── API ──────────────────────────────────────────────────────────
    async function apiFetch(method, path, body) {
        const opts = { method: method, headers: { 'Content-Type': 'application/json' } };
        if (body) opts.body = JSON.stringify(body);
        const r = await fetch(CFG.apiBase + path, opts);
        return r.json();
    }

    async function loadContacts() {
        setLoading();
        try {
            const data = await apiFetch('GET', '/contacts');
            allContacts = (data.data && data.data.contacts) ? data.data.contacts : (data.contacts || []);
            renderTable();
            updateStats();
        } catch(e) {
            console.error('Load contacts error:', e);
        }
    }

    // ── Stats ────────────────────────────────────────────────────────
    function updateStats() {
        var total    = document.getElementById('stat-total');
        var internal = document.getElementById('stat-internal');
        var external = document.getElementById('stat-external');
        if (total)    total.textContent    = allContacts.length;
        if (internal) internal.textContent = allContacts.filter(function(c){ return c.type === 'internal'; }).length;
        if (external) external.textContent = allContacts.filter(function(c){ return c.type !== 'internal'; }).length;
    }

    // ── Filtering ────────────────────────────────────────────────────
    function getFiltered() {
        var q = currentSearch.toLowerCase();
        return allContacts.filter(function(c) {
            var matchType   = currentFilter === 'all' || c.type === currentFilter;
            var num         = c.number || c.extension || '';
            var matchSearch = !q ||
                (c.name||'').toLowerCase().indexOf(q) >= 0 ||
                num.indexOf(q) >= 0 ||
                (c.department||'').toLowerCase().indexOf(q) >= 0;
            return matchType && matchSearch;
        });
    }

    // ── Table ────────────────────────────────────────────────────────
    function setLoading() {
        var tbody = document.getElementById('phonebook-tbody');
        if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="center aligned"><i class="spinner loading icon"></i></td></tr>';
    }

    function escHtml(s) {
        return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function renderTable() {
        var tbody    = document.getElementById('phonebook-tbody');
        var countEl  = document.getElementById('contacts-found');
        if (!tbody) return;
        var filtered = getFiltered();
        if (countEl) countEl.textContent = filtered.length + ' contact(en) gevonden';
        if (filtered.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="center aligned disabled">Geen contacten gevonden.</td></tr>';
            return;
        }
        var rows = '';
        for (var i = 0; i < filtered.length; i++) {
            var c      = filtered[i];
            var num    = c.number || c.extension || '';
            var label  = c.type === 'internal'
                ? '<span class="ui blue tiny label">Intern</span>'
                : '<span class="ui green tiny label">Extern</span>';
            var actions = (c.type !== 'internal' && !c.readonly)
                ? '<button class="ui icon tiny basic button btn-edit" data-id="' + c.id + '"><i class="edit icon"></i></button>' +
                  '<button class="ui icon tiny basic red button btn-delete" data-id="' + c.id + '"><i class="trash icon"></i></button>'
                : '';
            rows += '<tr>' +
                '<td>' + label + '</td>' +
                '<td>' + escHtml(c.name) + '</td>' +
                '<td><code>' + escHtml(num) + '</code></td>' +
                '<td>' + escHtml(c.department||'') + '</td>' +
                '<td>' + escHtml(c.category||'') + '</td>' +
                '<td class="right aligned">' + actions + '</td>' +
                '</tr>';
        }
        tbody.innerHTML = rows;

        tbody.querySelectorAll('.btn-edit').forEach(function(btn) {
            btn.addEventListener('click', function() { openEditModal(btn.dataset.id); });
        });
        tbody.querySelectorAll('.btn-delete').forEach(function(btn) {
            btn.addEventListener('click', function() { deleteContact(btn.dataset.id); });
        });
    }

    // ── Modal ────────────────────────────────────────────────────────
    function openAddModal() {
        document.getElementById('modal-title').textContent = 'Nieuw extern contact';
        document.getElementById('contact-id').value        = '';
        document.getElementById('contact-name').value      = '';
        document.getElementById('contact-number').value    = '';
        document.getElementById('contact-department').value = 'Anders';
        document.getElementById('contact-category').value  = 'Anders';
        document.getElementById('btn-save-contact').textContent = 'Contact opslaan';
        hideFormError();
        if (window.$) $('#phonebook-modal').modal('show');
    }

    function openEditModal(id) {
        var c = null;
        for (var i = 0; i < allContacts.length; i++) {
            if (String(allContacts[i].id) === String(id)) { c = allContacts[i]; break; }
        }
        if (!c) return;
        document.getElementById('modal-title').textContent  = 'Contact bewerken';
        document.getElementById('contact-id').value         = c.id;
        document.getElementById('contact-name').value       = c.name;
        document.getElementById('contact-number').value     = c.number;
        document.getElementById('contact-department').value = c.department || 'Anders';
        document.getElementById('contact-category').value   = c.category  || 'Anders';
        document.getElementById('btn-save-contact').textContent = 'Bijwerken';
        hideFormError();
        if (window.$) $('#phonebook-modal').modal('show');
    }

    async function saveContact() {
        var id     = document.getElementById('contact-id').value;
        var name   = document.getElementById('contact-name').value.trim();
        var number = document.getElementById('contact-number').value.trim();
        var dept   = document.getElementById('contact-department').value;
        var cat    = document.getElementById('contact-category').value;

        if (!name)   return showFormError('Naam is verplicht');
        if (!number) return showFormError('Telefoonnummer is verplicht');
        hideFormError();

        var data;
        if (id) {
            data = await apiFetch('POST', '/updateContact', { id: id, name: name, number: number, department: dept, category: cat });
        } else {
            data = await apiFetch('POST', '/saveContact', { name: name, number: number, department: dept, category: cat });
        }

        if (data.result) {
            if (window.$) $('#phonebook-modal').modal('hide');
            await loadContacts();
        } else {
            var msg = Array.isArray(data.messages) ? data.messages[0] : 'Fout bij opslaan';
            var labels = {
                'duplicate':       'Dit nummer bestaat al.',
                'name_required':   'Naam is verplicht.',
                'number_required': 'Nummer is verplicht.',
                'number_invalid':  'Ongeldig telefoonnummer.',
                'not_found':       'Contact niet gevonden.'
            };
            showFormError(labels[msg] || msg);
        }
    }

    async function deleteContact(id) {
        if (!confirm('Contact verwijderen?')) return;
        var data = await apiFetch('POST', '/deleteContact', { id: id });
        if (data.result) await loadContacts();
    }

    function showFormError(msg) {
        var el = document.getElementById('form-error');
        if (el) { el.textContent = msg; el.style.display = 'block'; }
    }

    function hideFormError() {
        var el = document.getElementById('form-error');
        if (el) el.style.display = 'none';
    }

    // ── CSV ──────────────────────────────────────────────────────────
    async function exportCsv() {
        var data = await apiFetch('GET', '/export-csv');
        if (data.result) {
            var blob = new Blob([data.data.csv], { type: 'text/csv' });
            var url  = URL.createObjectURL(blob);
            var a    = document.createElement('a');
            a.href = url; a.download = 'phonebook_export.csv'; a.click();
            URL.revokeObjectURL(url);
        }
    }

    async function importCsv(csvText) {
        var data = await apiFetch('POST', '/import-csv', { csv: csvText });
        if (data.result) {
            alert('Geïmporteerd: ' + (data.data && data.data.imported || 0) + ' contacten');
            await loadContacts();
        } else {
            alert('Import mislukt: ' + (data.messages||[]).join(', '));
        }
    }

    // ── CallerID sync ─────────────────────────────────────────────────
    async function syncCallerID() {
        var btn = document.getElementById('btn-sync-callerid');
        if (btn) { btn.classList.add('loading', 'disabled'); }
        var data = await apiFetch('POST', '/sync-callerid');
        if (btn) { btn.classList.remove('loading', 'disabled'); }
        alert(data.result ? 'CallerID gesynchroniseerd.' : 'Synchronisatie mislukt.');
    }

    // ── Filter ───────────────────────────────────────────────────────
    function setFilter(f) {
        currentFilter = f;
        ['all','internal','external'].forEach(function(x) {
            var btn = document.getElementById('filter-' + x);
            if (btn) btn.classList.toggle('active', x === f);
        });
        renderTable();
    }

    // ── Boot ─────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function() {
        if (window.$) {
            $('.ui.dropdown').dropdown();
            $('#phonebook-modal').modal({
                onApprove: function() { saveContact(); return false; }
            });
        }

        var searchEl = document.getElementById('phonebook-search');
        if (searchEl) {
            searchEl.addEventListener('input', function() {
                currentSearch = this.value;
                renderTable();
            });
        }

        ['all','internal','external'].forEach(function(f) {
            var btn = document.getElementById('filter-' + f);
            if (btn) btn.addEventListener('click', function() { setFilter(f); });
        });

        setFilter('all');

        var addBtn = document.getElementById('btn-add-external');
        if (addBtn) addBtn.addEventListener('click', openAddModal);

        var exportBtn = document.getElementById('btn-export-csv');
        if (exportBtn) exportBtn.addEventListener('click', exportCsv);

        var importBtn = document.getElementById('btn-import-csv');
        var fileInput = document.getElementById('csv-file-input');
        if (importBtn && fileInput) {
            importBtn.addEventListener('click', function() { fileInput.click(); });
            fileInput.addEventListener('change', async function() {
                if (this.files[0]) {
                    var text = await this.files[0].text();
                    this.value = '';
                    await importCsv(text);
                }
            });
        }

        var syncBtn = document.getElementById('btn-sync-callerid');
        if (syncBtn) syncBtn.addEventListener('click', syncCallerID);

        loadContacts();
    });

})();
