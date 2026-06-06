{# Cloud Phonebook v1.3.2 — Admin view #}
<div id="phonebook-root"></div>

<script id="phonebook-config" type="application/json">
{
    "version": "1.3.2",
    "moduleId": "ModulePhoneBookSync",
    "apiBase": "/pbxcore/api/modules/ModulePhoneBookSync",
    "pbxLang": "{{ pbxLang }}",
    "changelog": {{ changelog | json_encode | raw }}
}
</script>

<script id="phonebook-i18n" type="application/json">
{{ translations | json_encode | raw }}
</script>

<script src="{{ url.get() }}assets/js/cache/ModulePhoneBookSync/module-phonebook-sync.js"></script>
