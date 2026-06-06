{# Cloud Phonebook v1.4.0 — Semantic UI layout conform MikoPBX stijl #}

{# Statistieken balk #}
<div class="ui three statistics" style="margin-bottom: 1em;">
    <div class="statistic">
        <div class="value" id="stat-total">-</div>
        <div class="label">{{ i18n.stat_total ?? 'Total contacts' }}</div>
    </div>
    <div class="statistic">
        <div class="value" id="stat-internal">-</div>
        <div class="label">{{ i18n.stat_internal ?? 'Internal' }}</div>
    </div>
    <div class="statistic">
        <div class="value" id="stat-external">-</div>
        <div class="label">{{ i18n.stat_external ?? 'External' }}</div>
    </div>
</div>

{# Zoek + filter + knoppen #}
<div class="ui grid">
    <div class="ui row">
        <div class="ui ten wide column">
            <div class="ui search left icon fluid input">
                <i class="search icon"></i>
                <input type="search" id="phonebook-search"
                       placeholder="{{ i18n.search_placeholder ?? 'Search by name, number or department...' }}">
            </div>
        </div>
        <div class="ui six wide column right aligned">
            <div class="ui buttons">
                <button class="ui basic button" id="filter-all">{{ i18n.filter_all ?? 'All' }}</button>
                <button class="ui basic button" id="filter-internal">{{ i18n.filter_internal ?? 'Internal' }}</button>
                <button class="ui basic button" id="filter-external">{{ i18n.filter_external ?? 'External' }}</button>
            </div>
        </div>
    </div>
    <div class="ui row" style="padding-top:0">
        <div class="ui right aligned column">
            <button class="ui basic small button" id="btn-export-csv">
                <i class="download icon"></i>{{ i18n.btn_export_csv ?? 'Export CSV' }}
            </button>
            <button class="ui basic small button" id="btn-import-csv">
                <i class="upload icon"></i>{{ i18n.btn_import_csv ?? 'Import CSV' }}
            </button>
            <input type="file" id="csv-file-input" accept=".csv" style="display:none">
            <button class="ui basic small button" id="btn-sync-callerid">
                <i class="sync icon"></i>{{ i18n.btn_sync_callerid ?? 'Sync CallerID' }}
            </button>
            <button class="ui blue button" id="btn-add-external">
                <i class="add icon"></i>{{ i18n.add_external ?? 'Add external contact' }}
            </button>
        </div>
    </div>
</div>

{# Contacten tabel #}
<table id="phonebook-table" class="ui small very compact single line table">
    <thead>
        <tr>
            <th class="one wide"></th>
            <th class="five wide">{{ i18n.field_name ?? 'Name' }}</th>
            <th class="three wide">{{ i18n.field_number ?? 'Number' }}</th>
            <th class="two wide">{{ i18n.field_department ?? 'Department' }}</th>
            <th class="two wide">{{ i18n.field_category ?? 'Category' }}</th>
            <th class="one wide collapsing"></th>
        </tr>
    </thead>
    <tbody id="phonebook-tbody">
        <tr>
            <td colspan="6" class="center aligned">
                <i class="spinner loading icon"></i>
            </td>
        </tr>
    </tbody>
</table>

{# Modal: extern contact toevoegen/bewerken #}
<div class="ui modal" id="phonebook-modal">
    <div class="header" id="modal-title">{{ i18n.modal_title_add ?? 'New external contact' }}</div>
    <div class="content">
        <form class="ui form" id="phonebook-form">
            <input type="hidden" id="contact-id" value="">
            <div class="two fields">
                <div class="required field">
                    <label>{{ i18n.field_name ?? 'Name' }}</label>
                    <input type="text" id="contact-name"
                           placeholder="{{ i18n.field_name_placeholder ?? 'Company or contact name' }}">
                </div>
                <div class="required field">
                    <label>{{ i18n.field_number ?? 'Phone number' }}</label>
                    <input type="text" id="contact-number"
                           placeholder="{{ i18n.field_number_placeholder ?? '+31 20 123 4567' }}">
                </div>
            </div>
            <div class="two fields">
                <div class="field">
                    <label>{{ i18n.field_department ?? 'Department' }}</label>
                    <select class="ui dropdown" id="contact-department">
                        <option value="Sales">{{ i18n.dept_sales ?? 'Sales' }}</option>
                        <option value="Support">{{ i18n.dept_support ?? 'Support' }}</option>
                        <option value="IT">{{ i18n.dept_it ?? 'IT' }}</option>
                        <option value="HR">{{ i18n.dept_hr ?? 'HR' }}</option>
                        <option value="Marketing">{{ i18n.dept_marketing ?? 'Marketing' }}</option>
                        <option value="Finance">{{ i18n.dept_finance ?? 'Finance' }}</option>
                        <option value="Management">{{ i18n.dept_management ?? 'Management' }}</option>
                        <option value="Government">{{ i18n.dept_government ?? 'Government' }}</option>
                        <option value="Telecom">{{ i18n.dept_telecom ?? 'Telecom' }}</option>
                        <option value="Other">{{ i18n.dept_other ?? 'Other' }}</option>
                    </select>
                </div>
                <div class="field">
                    <label>{{ i18n.field_category ?? 'Category' }}</label>
                    <select class="ui dropdown" id="contact-category">
                        <option value="Client">{{ i18n.cat_client ?? 'Client' }}</option>
                        <option value="Supplier">{{ i18n.cat_supplier ?? 'Supplier' }}</option>
                        <option value="Partner">{{ i18n.cat_partner ?? 'Partner' }}</option>
                        <option value="Emergency">{{ i18n.cat_emergency ?? 'Emergency' }}</option>
                        <option value="Other">{{ i18n.cat_other ?? 'Other' }}</option>
                    </select>
                </div>
            </div>
            <div class="ui error message" id="form-error" style="display:none"></div>
        </form>
    </div>
    <div class="actions">
        <button class="ui cancel button">{{ i18n.btn_cancel ?? 'Cancel' }}</button>
        <button class="ui blue approve button" id="btn-save-contact">
            {{ i18n.btn_save ?? 'Save contact' }}
        </button>
    </div>
</div>

<script id="phonebook-config" type="application/json">
{
    "version": "{{ version }}",
    "moduleId": "ModulePhoneBookSync",
    "apiBase": "/pbxcore/api/modules/ModulePhoneBookSync",
    "pbxLang": "{{ pbxLang }}",
    "standalone": false
}
</script>

<script id="phonebook-i18n" type="application/json">
{{ translations | json_encode }}
</script>

<script src="/admin-cabinet/assets/js/cache/ModulePhoneBookSync/module-phonebook-sync.js"></script>
