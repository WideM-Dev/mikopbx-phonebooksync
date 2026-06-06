{# Cloud Phonebook v1.4.3 — Minimale beheer pagina #}
<div class="ui segment">
    <h3 class="ui header">
        <i class="phone icon"></i>
        <div class="content">
            Cloud Phonebook
            <div class="sub header">Unified phonebook endpoint voor IP-telefoons</div>
        </div>
    </h3>

    <div class="ui divider"></div>

    <p>Contacten beheren via <a href="/admin-cabinet/module-phone-book/index">ModulePhoneBook</a>.</p>

    <h4 class="ui header">Phonebook URL's voor toestellen</h4>
    <table class="ui celled table">
        <thead><tr><th>Merk</th><th>URL</th></tr></thead>
        <tbody>
            <tr><td>Yealink / Fanvil</td><td><code>/pbxcore/api/phonebooksync/contacts?format=yealink</code></td></tr>
            <tr><td>Snom</td><td><code>/pbxcore/api/phonebooksync/contacts?format=snom</code></td></tr>
            <tr><td>Cisco</td><td><code>/pbxcore/api/phonebooksync/contacts?format=cisco</code></td></tr>
            <tr><td>Grandstream</td><td><code>/pbxcore/api/phonebooksync/contacts?format=grandstream</code></td></tr>
            <tr><td>JSON</td><td><code>/pbxcore/api/phonebooksync/contacts</code></td></tr>
        </tbody>
    </table>
</div>
