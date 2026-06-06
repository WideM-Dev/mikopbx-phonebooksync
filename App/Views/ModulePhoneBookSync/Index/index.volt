{# Cloud Phonebook v1.3.0 — Admin view #}
{# View pad: App/Views/ModulePhoneBookSync/Index/index.volt #}
{# MikoPBX zoekt: Modules/{ModuleUniqueId}/{Controller}/{action} #}

<div class="ui container" id="module-phonebook-app">
    <div id="phonebook-root"></div>

    <script id="phonebook-config" type="application/json">
        {
            "version": "1.3.0",
            "moduleId": "ModulePhoneBookSync",
            "apiBase": "/pbxcore/api/modules/ModulePhoneBookSync",
            "pbxLang": "{{ pbxLang }}",
            "changelog": {{ changelog | json_encode | raw }}
        }
    </script>

    <script id="phonebook-i18n" type="application/json">
        {{ translations | json_encode | raw }}
    </script>
</div>
