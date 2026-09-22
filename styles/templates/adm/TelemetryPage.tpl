{include file="overall_header.tpl"}
<style>
{literal}
.telemetry {max-width:1200px;margin:20px auto;padding:0 16px;text-align:left;color:#ddd;font:14px/1.5 Arial,sans-serif}
.telemetry table,.telemetry tr,.telemetry td,.telemetry th,.telemetry input,.telemetry button,.telemetry select,.telemetry textarea {font:inherit}
.telemetry h1 {font-size:24px}.telemetry h2 {font-size:20px}.telemetry h3 {font-size:16px}
.telemetry th {background:#1d2d3e;font-weight:600}
.telemetry input,.telemetry button,.telemetry select {padding:5px}
.telemetry :focus-visible {outline:2px solid #a9d5ff;outline-offset:2px}
.telemetry table {width:100%;margin:12px 0;border-collapse:collapse}
.telemetry td,.telemetry th {padding:8px;text-align:left;vertical-align:top}
.telemetry fieldset {margin:16px 0;padding:16px}
.telemetry input[type=number] {width:110px}
.telemetry .evidence {max-height:480px;overflow:auto}
.telemetry details {margin:14px 0}
.telemetry summary {cursor:pointer}
.telemetry .metrics th {width:45%}
.telemetry .resources td {text-align:right;font-variant-numeric:tabular-nums}
.telemetry .resources th:not(:first-child) {text-align:right}
.telemetry .error {color:#ffb4a4}.telemetry .notice {color:#c4e5ac}
.telemetry .heat {display:grid;grid-template-columns:repeat(288,minmax(0,1fr));height:24px;background:#202c3b;position:relative;overflow:hidden;border-radius:3px}
.telemetry .heat:after {content:"";position:absolute;inset:0;pointer-events:none;background:repeating-linear-gradient(to right,transparent 0,transparent calc(4.16667% - 1px),#ffffff24 calc(4.16667% - 1px),#ffffff24 4.16667%)}
.telemetry .bit {background:#73cba6;grid-row:1}
.telemetry .hours {display:flex;justify-content:space-between;font-size:12px;color:#c7d5e5}
.telemetry .scroll {overflow-x:auto}
.telemetry .heatmap {min-width:760px;table-layout:fixed}
.telemetry .heatmap th:first-child {width:150px}
.telemetry .heatmap th:last-child {width:145px;text-align:right}
.telemetry .heatmap td:last-child {text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
.telemetry .heatmap td,.telemetry .heatmap th {border-bottom:1px solid #ffffff15}
.telemetry .heatmap tr.new-day td,.telemetry .heatmap tr.new-day th {border-top:1px solid #ffffff40}
.telemetry .heatmap small {font-weight:normal}
.telemetry .panel {background:#142235;border:1px solid #35475e;border-radius:6px;padding:16px;margin:20px 0}
.telemetry h2 {margin:8px 0 16px}
.telemetry h3 {margin:12px 0}
.telemetry .comparison td {font-variant-numeric:tabular-nums}
.telemetry .account-list tbody tr:hover,.telemetry .warnings tbody tr:hover {background:#ffffff09}
.telemetry a {color:#a9d5ff}
.telemetry .timezone-form {display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:16px 0}
.telemetry .legend {font-size:12px;color:#c7d5e5}
.telemetry .legend:before {content:"";display:inline-block;width:12px;height:12px;background:#73cba6;margin-right:6px;vertical-align:middle}
.telemetry small {display:block;color:#bbb}
.telemetry textarea {width:95%;min-height:80px}
.telemetry nav {display:flex;gap:18px;margin:20px 0;flex-wrap:wrap}
.telemetry .setting-row {display:grid;grid-template-columns:minmax(230px,1fr) minmax(210px,1fr);gap:16px;padding:12px 0;border-top:1px solid #ffffff15;align-items:start}
.telemetry .setting-row small {margin-top:4px;font-size:12px;line-height:1.5}
.telemetry .setting-control {display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.telemetry .setting-control input[type=checkbox] {width:18px;height:18px;margin:3px 0;accent-color:#73cba6}
.telemetry .settings-actions {display:flex;gap:16px;flex-wrap:wrap;margin:20px 0}
.telemetry .settings-form summary {font-size:16px;font-weight:600;margin-bottom:10px}
@media(max-width:650px) {.telemetry .setting-row {grid-template-columns:1fr;gap:8px}}

{/literal}
</style>
<div class="telemetry">
<h1>{if $telemetryShowSettings}Réglages de l’activité{else}{$LNG.modul_43}{/if}</h1>
{if $telemetryShowSettings}
<form class="timezone-form" method="get" action="admin.php">
<input type="hidden" name="page" value="telemetry">
<input type="hidden" name="view" value="settings">
<label for="telemetry-timezone">Fuseau horaire</label>
<select id="telemetry-timezone" name="timezone">{html_options options=$telemetryTimezones selected=$telemetryTimezone}</select>
<button type="submit">Appliquer</button>
</form>
{/if}
{if !$telemetryMaster}<p class="notice">Collecte désactivée dans la configuration du serveur.</p>{/if}
{if $telemetryMessage}<p class="notice">{$telemetryMessage|escape}</p>{/if}
{if $telemetryError}<p class="error" role="alert">{$telemetryError|escape}</p>{/if}
<nav><a href="?page=telemetry">À examiner</a><a href="?page=telemetry&amp;status=follow_up">À suivre</a><a href="?page=telemetry&amp;status=dismissed">Classés</a><a href="?page=telemetry&amp;view=settings#telemetry-settings">Réglages</a>{if !$telemetryShowSettings}<a href="#telemetry-health" onclick="document.getElementById('telemetry-health').open=true">État de collecte</a>{/if}</nav>
{if !$telemetryShowSettings}
{if $telemetryAnalysis}
<p>Analyse : {$telemetryAnalysis.findings} signalements mis à jour.</p>
{if $telemetryAnalysis.incomplete}
<p class="notice">Analyse incomplète : d’autres comptes ou échanges restent à examiner.
<a href="?page=telemetry&amp;cursor={$telemetryAnalysis.cursor}&amp;pair_a={$telemetryAnalysis.pair_a}&amp;pair_b={$telemetryAnalysis.pair_b}">Analyser le lot suivant</a></p>
{/if}
{if $telemetryAnalysis.truncated}<p class="error">Limite d’événements atteinte : {implode(', ', $telemetryAnalysis.truncated)|escape}. Les observations sont partielles.</p>{/if}
{/if}
{if $telemetryCase}
<p><a href="?page=telemetry&amp;account={$telemetryCase.actor}">← {$telemetryNames[$telemetryCase.actor]|escape}</a>{if $telemetryCase.other} · <a href="?page=telemetry&amp;account={$telemetryCase.other}">{$telemetryNames[$telemetryCase.other]|escape}</a>{/if}</p>
<h2>Dossier #{$telemetryCase.id} — {$telemetryCase.kind_label|escape}</h2>
<p>{$telemetryCase.explanation|escape} Force : <strong>{$telemetryCase.strength_label|escape}</strong>. Statut : {$telemetryCase.status_label|escape}.</p>
<p>Créé le {$telemetryCase.formatted_first|escape}, dernière observation le {$telemetryCase.formatted_last|escape}.</p>
<p><a href="?page=telemetry&amp;account={$telemetryCase.actor}&amp;other={$telemetryCase.other}">Voir l’activité</a></p>
{foreach $telemetryCase.evaluations as $evaluation}
<details {if $evaluation@first}open{/if}><summary>{$evaluation.label|escape} — {$evaluation.date|escape}</summary>
{if !$evaluation.matches}<p>Les seuils ne sont plus atteints sur la période analysée. La décision de revue reste inchangée.</p>{/if}
{if $evaluation.support}<p>Autres observations : {implode(', ', $evaluation.support)|escape}.</p>{/if}
{if $evaluation.exchange}
<p><strong>{$telemetryNames[$evaluation.exchange.sender]|escape} → {$telemetryNames[$evaluation.exchange.recipient]|escape}</strong></p>
<table class="resources"><thead><tr><th>Ressources</th><th>Métal</th><th>Cristal</th><th>Deutérium</th></tr></thead><tbody>
{foreach $evaluation.exchange.resources as $row}<tr><th>{$row.label|escape}</th><td>{$row.metal|escape}</td><td>{$row.crystal|escape}</td><td>{$row.deuterium|escape}</td></tr>{/foreach}
</tbody></table>
<table class="metrics exchange-summary"><tbody>{foreach $evaluation.exchange.summary as $row}<tr><th>{$row.label|escape}</th><td>{$row.value|escape}</td></tr>{/foreach}</tbody></table>
{else}
<table class="metrics"><tbody>{foreach $evaluation.metrics as $row}<tr><th>{$row.label|escape}</th><td>{$row.value|escape}</td></tr>{/foreach}</tbody></table>
{/if}
<details><summary>Seuils utilisés</summary><table class="metrics">{foreach $evaluation.thresholds as $row}<tr><th>{$row.label|escape}</th><td>{$row.value|escape}</td></tr>{/foreach}</table></details>
{foreach $evaluation.gaps as $gap}<p class="notice">{$gap.start|escape} — {$gap.end|escape} : {$gap.label|escape}.</p>{/foreach}
{if $evaluation.truncated}<p class="notice">Limite d’analyse atteinte : ce dossier couvre un lot partiel.</p>{/if}
{if $evaluation.sampled}<p>Premières et dernières observations sur {$evaluation.count} événements analysés.</p>{/if}
<div class="evidence"><table class="timeline"><thead><tr><th>Date</th><th>Acteur → cible</th><th>Action / flotte</th><th>Contexte</th></tr></thead><tbody>
{foreach $evaluation.timeline as $event}
<tr><td>{$event.date|escape}</td><td>{$telemetryNames[$event.actor|default:0]|default:'—'|escape} → {$telemetryNames[$event.target|default:0]|default:'—'|escape}</td><td>{$event.label|escape} {if $event.fleet_id}#{$event.fleet_id}{/if}</td><td>{foreach $event.details as $row}<span>{$row.label|escape} : {$row.value|escape}</span><br>{/foreach}</td></tr>
{/foreach}
</tbody></table></div>
</details>
{/foreach}
<form method="post" action="?page=telemetry&amp;id={$telemetryCase.id}">
<input type="hidden" name="token" value="{$telemetryToken|escape}"><input type="hidden" name="action" value="review"><input type="hidden" name="id" value="{$telemetryCase.id}">
<label>Décision <select name="status"><option value="open" {if $telemetryCase.status=='open'}selected{/if}>À examiner</option><option value="follow_up" {if $telemetryCase.status=='follow_up'}selected{/if}>À suivre</option><option value="dismissed" {if $telemetryCase.status=='dismissed'}selected{/if}>Classé</option></select></label>
<label>Note <textarea name="note" maxlength="4000"></textarea></label><button type="submit">Enregistrer la décision</button>
</form>
<h3>Historique de revue</h3>
{foreach $telemetryCase.review_history as $entry}<p>{$entry.date|escape} — modérateur #{$entry.admin} : {$entry.status|escape} — {$entry.note|escape}</p>{/foreach}
{/if}
<details {if $telemetryAccount}open{/if}><summary>Consulter l’activité d’un compte</summary>
<form method="get" action="admin.php">
<input type="hidden" name="page" value="telemetry">
<label>Compte (ID) <input name="account" type="number" min="1" value="{$telemetryAccount.id|default:''}" required></label>
<label>Comparer avec (facultatif) <input name="other" type="number" min="1" value="{if !empty($telemetryAccount.other)}{$telemetryAccount.other}{/if}"></label>
<label>Période <input name="days" type="number" min="1" max="{$telemetryMaxDays}" value="{$telemetryAccount.period|default:7}"> jours</label>
<button type="submit">Afficher</button>
</form></details>
{if $telemetryAccount}
<h2>{foreach $telemetryAccount.players as $player}{$player.username|escape} ({$player.id}){if !$player@last} / {/if}{/foreach}</h2>
<section class="panel" id="player-warnings">
<h3>Signalements de {$telemetryAccount.players[0].username|escape}</h3>
<div class="scroll"><table class="warnings"><thead><tr><th>Signalement</th><th>Force</th><th>Comptes concernés</th><th>Observations</th><th>Statut</th></tr></thead><tbody>
{foreach $telemetryWarnings as $warning}
<tr><td><a href="?page=telemetry&amp;id={$warning.id}">{$warning.kind_label|escape}</a><small>{$warning.explanation|escape}</small></td><td>{$warning.strength_label|escape}</td><td><a href="?page=telemetry&amp;account={$warning.actor}">{$telemetryNames[$warning.actor]|escape}</a>{if $warning.other} / <a href="?page=telemetry&amp;account={$warning.other}">{$telemetryNames[$warning.other]|escape}</a>{/if}</td><td>{$warning.dates|escape}</td><td>{$warning.status_label|escape}</td></tr>
{foreachelse}<tr><td colspan="5">Aucun signalement pour ce compte.</td></tr>{/foreach}
</tbody></table></div>
{if $telemetryNext}<a href="?page=telemetry&amp;account={$telemetryAccount.id}&amp;other={$telemetryAccount.other}&amp;days={$telemetryAccount.period}&amp;before={$telemetryNext}#player-warnings">Signalements suivants</a>{/if}
</section>
<section class="panel"><h3>Activité</h3>
<p>{$telemetryAccount.start|escape} — {$telemetryAccount.end|escape}</p>
<table class="comparison"><thead><tr><th>Activité</th>{foreach $telemetryAccount.players as $player}<th>{$player.username|escape} ({$player.id})</th>{/foreach}</tr></thead><tbody>
<tr><th>Durée active estimée / jour</th>{foreach $telemetryAccount.players as $player}<td>{$player.activity.average|escape}</td>{/foreach}</tr>
<tr><th>Jours actifs</th>{foreach $telemetryAccount.players as $player}<td>{$player.activity.active_days} / {$telemetryAccount.period}</td>{/foreach}</tr>
<tr><th>Durée active estimée totale</th>{foreach $telemetryAccount.players as $player}<td>{$player.activity.total|escape}</td>{/foreach}</tr>
<tr><th>Dernière interaction</th>{foreach $telemetryAccount.players as $player}<td>{$player.activity.last_activity|escape}</td>{/foreach}</tr>
</tbody></table>
<h3>Activité par jour</h3>
<div class="scroll">
<table class="heatmap"><thead><tr><th>Jour</th><th><div class="hours"><span>00</span><span>06</span><span>12</span><span>18</span><span>24 h</span></div></th><th>Durée active</th></tr></thead><tbody>
{foreach $telemetryAccount.days as $day}
<tr {if $day.actor==$telemetryAccount.id}class="new-day"{/if}><th>{$day.day|escape}{if $telemetryAccount.other}<small>{$day.name|escape}</small>{/if}</th><td><div class="heat" role="img" aria-label="{$day.name|escape}, {$day.day|escape} : {$day.duration|escape}">
{foreach $day.slots as $slot}<span class="bit" style="grid-column:{$slot.minute+1}" title="{$slot.date|escape}"></span>{/foreach}
</div></td><td>{$day.duration|escape}</td></tr>
{/foreach}
</tbody></table>
</div>
<span class="legend">Activité estimée</span>
<details><summary>Fenêtres d’activité</summary>
<table><thead><tr><th>Jour</th>{if $telemetryAccount.other}<th>Compte</th>{/if}<th>Horaires</th><th>Durée</th></tr></thead><tbody>
{foreach $telemetryAccount.days as $day}{foreach $day.windows as $window}<tr><td>{$day.day|escape}</td>{if $telemetryAccount.other}<td>{$day.name|escape}</td>{/if}<td>{$window.range|escape}</td><td>{$window.duration|escape}</td></tr>{/foreach}{/foreach}
</tbody></table>
</details>
{foreach $telemetryAccount.gaps as $gap}<p class="notice">{$gap.start|escape} — {$gap.end|escape} : {$gap.label|escape}.</p>{/foreach}
</section>
<section class="panel"><h3>Actions depuis le {$telemetryAccount.event_start|escape}</h3>
<table class="comparison"><thead><tr><th>Actions</th>{foreach $telemetryAccount.players as $player}<th>{$player.username|escape}</th>{/foreach}</tr></thead><tbody>
<tr><th>Envois de flotte</th>{foreach $telemetryAccount.players as $player}<td>{$player.sends}</td>{/foreach}</tr>
<tr><th>Rappels de flotte</th>{foreach $telemetryAccount.players as $player}<td>{$player.recalls}</td>{/foreach}</tr>
<tr><th>Adresses IP</th>{foreach $telemetryAccount.players as $player}<td>{$player.network.ips}</td>{/foreach}</tr>
<tr><th>Profils client</th>{foreach $telemetryAccount.players as $player}<td>{$player.network.clients}</td>{/foreach}</tr>
<tr><th>Consultations de galaxie</th>{foreach $telemetryAccount.players as $player}<td>{$player.reads}</td>{/foreach}</tr>
</tbody></table>
{foreach $telemetryAccount.event_gaps as $gap}<p class="notice">{$gap.start|escape} — {$gap.end|escape} : {$gap.label|escape}.</p>{/foreach}
<details><summary>Adresses IP et clients utilisés</summary>
<p>Navigateur, système et type d’appareil déclarés par le client.</p>
{foreach $telemetryAccount.players as $player}
<h4>{$player.username|escape}</h4>
<table class="network"><thead><tr><th>IP</th><th>Client</th><th>Requêtes</th><th>Première utilisation</th><th>Dernière utilisation</th></tr></thead><tbody>
{foreach $player.network.rows as $network}<tr><td>{$network.ip|default:'—'|escape}</td><td>{$network.client|default:'—'|escape}</td><td>{$network.requests}</td><td>{$network.first_seen|escape}</td><td>{$network.last_seen|escape}</td></tr>
{foreachelse}<tr><td colspan="5">Aucune observation sur la période.</td></tr>{/foreach}
</tbody></table>
{if $player.network.more}<p>Les 50 combinaisons utilisées le plus récemment sont affichées.</p>{/if}
{/foreach}
</details>
<details><summary>Actions récentes (500 maximum par compte)</summary>
<table><tr><th>Date</th><th>Acteur → cible</th><th>Action</th><th>Contexte</th></tr>
{foreach $telemetryAccount.events as $event}<tr><td>{$event.date|escape}</td><td>{$telemetryNames[$event.actor]|default:'—'|escape} → {$telemetryNames[$event.target]|default:'—'|escape}</td><td>{$event.label|escape} {if $event.fleet_id}#{$event.fleet_id}{/if}</td><td>{foreach $event.details as $row}{$row.label|escape} : {$row.value|escape}<br>{/foreach}</td></tr>{/foreach}
</table></details>
</section>
{/if}
{if !$telemetryAccount && !$telemetryCase}
<h2>Signalements par compte</h2>
<table class="account-list"><thead><tr><th>Compte</th><th>Signalements</th><th>Observations</th><th>Dernière observation</th></tr></thead><tbody>
{foreach $telemetryAccounts as $player}
<tr><td><a href="?page=telemetry&amp;account={$player.account}">{$player.name|escape}</a></td><td>{$player.total}</td><td>{$player.kinds|escape}</td><td>{$player.date|escape}</td></tr>
{foreachelse}<tr><td colspan="4">Aucun compte dans cette vue.</td></tr>{/foreach}
</tbody></table>
{if $telemetryNext}<a href="?page=telemetry&amp;status={$telemetryStatus|escape}&amp;before={$telemetryNext}">Comptes suivants</a>{/if}
{/if}
<details id="telemetry-health"><summary>État de collecte</summary>

<table class="metrics">{foreach $telemetryHealth as $row}<tr><th>{$row.label|escape}</th><td>{$row.value|escape}</td></tr>{/foreach}</table>
{foreach $telemetryGaps as $gap}<p class="notice">{$gap.start|escape} — {$gap.end|escape} : {$gap.label|escape}.</p>{/foreach}
</details>
{/if}
{if $telemetryShowSettings}
<section id="telemetry-settings">
{if allowedTo('ShowModulePage')}<p>Module de collecte : <strong>{if $telemetryEnabled}activée{else}désactivée{/if}</strong> · <a href="?page=module">Modifier dans Modules</a></p>{/if}

<form class="settings-form" method="post" action="?page=telemetry&amp;view=settings">
<input type="hidden" name="token" value="{$telemetryToken|escape}"><input type="hidden" name="action" value="settings">
{foreach $telemetryFields as $group=>$fields}<details class="panel" id="telemetry-group-{$group}" {if $group=='collection' || $group=='storage'}open{/if}><summary>{$telemetryGroups[$group]|escape}</summary>
{foreach $fields as $field}<div class="setting-row">
<div><label for="telemetry-{$field.key}">{$field.label|escape}</label>{if $field.help}<small>{$field.help|escape}</small>{/if}</div>
<div class="setting-control">
{if $field.checkbox}
<input type="hidden" name="settings[{$field.key}]" value="0">
<input id="telemetry-{$field.key}" name="settings[{$field.key}]" type="checkbox" value="1" data-default="{$field.default}" {if $field.value}checked{/if}>
{else}
<input id="telemetry-{$field.key}" name="settings[{$field.key}]" type="number" step="{$field.step}" min="{$field.min}" max="{$field.max}" value="{$field.value|escape}" data-default="{$field.default}" required> <span>{$field.unit|escape}</span>
{/if}
</div></div>{/foreach}
</details>{/foreach}
<div class="settings-actions"><button type="submit">Enregistrer les réglages</button>
<button type="button" onclick="this.form.querySelectorAll('[data-default]').forEach(function(input) { if (input.type === 'checkbox') input.checked = input.dataset.default === '1'; else input.value = input.dataset.default; })">Rétablir les valeurs par défaut</button></div>
</form>
<details><summary>Dernières modifications des réglages</summary>
{foreach $telemetrySettingsHistory as $entry}<p>{$entry.date|escape} — modérateur #{$entry.admin}</p><table>{foreach $entry.changes as $row}<tr><th>{$row.label|escape}</th><td>{$row.value|escape}</td></tr>{/foreach}</table>{/foreach}
</details>
</section>
{/if}
</div>
{include file="overall_footer.tpl"}
