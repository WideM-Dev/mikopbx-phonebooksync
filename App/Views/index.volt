{# Cloud Phonebook v1.1.0 — Admin view (Volt template) #}
{# Taal: lees PBXLanguage uit PbxSettings, geef door aan JS als data-attribuut #}
{% extends 'layouts/main.volt' %}

{% block title %}{{ t('module_name') }}{% endblock %}

{% block content %}
<div class="ui container" id="module-phonebook-app">
    <div id="phonebook-root"></div>

    {# Taalsleutel van MikoPBX doorgeven aan JS (bijv. "nl-nl", "en-gb", "ru-ru") #}
    <script id="phonebook-config" type="application/json">
        {
            "version": "{{ version }}",
            "moduleId": "ModulePhonebookSync",
            "apiBase": "/pbxcore/api/modules/ModulePhonebookSync",
            "pbxLang": "{{ pbxLang }}",
            "changelog": {{ changelog | json_encode | raw }}
        }
    </script>

    {# Vertalingen voor de actieve taal, meegegeven door de controller #}
    <script id="phonebook-i18n" type="application/json">
        {{ translations | json_encode | raw }}
    </script>
</div>
{% endblock %}

{% block javascripts %}
    {{ super() }}
    <script src="/pbxcore/modules/ModulePhonebookSync/public/js/phonebook.js"></script>
{% endblock %}
