{include file="overall_header.tpl"}
<style>
{literal}
.telemetry {max-width:1200px;margin:20px auto;text-align:left;color:#ddd}
.telemetry table {width:100%;margin:12px 0;border-collapse:collapse}
.telemetry td,.telemetry th {padding:8px;text-align:left;vertical-align:top}
.telemetry fieldset {margin:16px 0;padding:16px}
.telemetry input[type=number] {width:110px}
.telemetry .evidence {max-height:480px;overflow:auto}
.telemetry details {margin:14px 0}
.telemetry summary {cursor:pointer}
.telemetry .metrics th {width:45%}
.telemetry .error {color:#ffb4a4}.telemetry .notice {color:#c4e5ac}
.telemetry .heat {display:grid;grid-template-columns:repeat(288,1fr);height:22px;background:#343b44;min-width:576px}
.telemetry .bit {background:#89cb91}
.telemetry .hours {display:flex;justify-content:space-between;min-width:576px}
.telemetry .scroll {overflow:auto}
.telemetry small {display:block;color:#bbb}
.telemetry textarea {width:95%;min-height:80px}
.telemetry nav {display:flex;gap:18px;margin:20px 0}
{/literal}
</style>
<div class="telemetry">
<h1>Activité des joueurs</h1>
<p>Historique d’activité et signalements à examiner.</p>
<p>Fuseau d’affichage : {$telemetryTimezone|escape}. {if !$telemetryMaster}Interrupteur de déploiement désactivé.{/if}</p>
{if $telemetryMessage}<p class="notice">{$telemetryMessage|escape}</p>{/if}
{if $telemetryError}<p class="error" role="alert">{$telemetryError|escape}</p>{/if}
<nav><a href="?page=telemetry">À examiner</a><a href="?page=telemetry&amp;status=follow_up">À suivre</a><a href="?page=telemetry&amp;status=dismissed">Classés</a><a href="?page=telemetry&amp;view=settings#telemetry-settings">Réglages</a><a href="#telemetry-health">État de collecte</a></nav>
{if $telemetryAnalysis}
<p>Analyse : {$telemetryAnalysis.findings} signalements mis à jour.</p>
{if $telemetryAnalysis.incomplete}
<p class="notice">Analyse incomplète : d’autres comptes ou échanges restent à examiner.
<a href="?page=telemetry&amp;cursor={$telemetryAnalysis.cursor}&amp;pair_a={$telemetryAnalysis.pair_a}&amp;pair_b={$telemetryAnalysis.pair_b}">Analyser le lot suivant</a></p>
{/if}
{if $telemetryAnalysis.truncated}<p class="error">Limite d’événements atteinte : {implode(', ', $telemetryAnalysis.truncated)|escape}. Les observations sont partielles.</p>{/if}
{/if}
{if $telemetryCase}
<h2>Dossier #{$telemetryCase.id} — {$telemetryCase.kind_label|escape}</h2>
<p>{$telemetryCase.explanation|escape} Force : <strong>{$telemetryCase.strength_label|escape}</strong>. Statut : {$telemetryCase.status_label|escape}.</p>
<p>Créé le {$telemetryCase.formatted_first|escape}, dernière observation le {$telemetryCase.formatted_last|escape}.</p>
<p><a href="?page=telemetry&amp;account={$telemetryCase.actor}&amp;other={$telemetryCase.other}">Voir l’activité</a></p>
{foreach $telemetryCase.evaluations as $evaluation}
<details {if $evaluation@first}open{/if}><summary>{$evaluation.label|escape} — {$evaluation.date|escape}</summary>
{if !$evaluation.matches}<p>Les seuils ne sont plus atteints sur la période analysée. La décision de revue reste inchangée.</p>{/if}
{if $evaluation.support}<p>Autres observations : {implode(', ', $evaluation.support)|escape}.</p>{/if}
<table class="metrics"><tbody>{foreach $evaluation.metrics as $row}<tr><th>{$row.label|escape}</th><td>{$row.value|escape}</td></tr>{/foreach}</tbody></table>
<details><summary>Seuils utilisés</summary><table class="metrics">{foreach $evaluation.thresholds as $row}<tr><th>{$row.label|escape}</th><td>{$row.value|escape}</td></tr>{/foreach}</table></details>
{foreach $evaluation.gaps as $gap}<p class="notice">{$gap.start|escape} — {$gap.end|escape} : {$gap.label|escape}.</p>{/foreach}
{if $evaluation.truncated}<p class="notice">Limite d’analyse atteinte : ce dossier couvre un lot partiel.</p>{/if}
{if $evaluation.sampled}<p>Premières et dernières observations sur {$evaluation.count} événements analysés.</p>{/if}
<div class="evidence"><table class="timeline"><thead><tr><th>Date</th><th>Acteur → cible</th><th>Action / flotte</th><th>Contexte</th></tr></thead><tbody>
{foreach $evaluation.timeline as $event}
<tr><td>{$event.date|escape}</td><td>{$event.actor|default:''|escape} → {$event.target|default:''|escape}</td><td>{$event.label|escape} {if $event.fleet_id}#{$event.fleet_id}{/if}</td><td>{foreach $event.details as $row}<span>{$row.label|escape} : {$row.value|escape}</span><br>{/foreach}</td></tr>
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
<label>Comparer avec (facultatif) <input name="other" type="number" min="1" value="{$telemetryAccount.other|default:''}"></label>
<label>Période <input name="days" type="number" min="1" max="{$telemetryMaxDays}" value="{$telemetryAccount.period|default:7}"> jours</label>
<button type="submit">Afficher</button>
</form></details>
{if $telemetryAccount}
<h2>Activité #{$telemetryAccount.id}{if $telemetryAccount.other} / #{$telemetryAccount.other}{/if}</h2>
<p>{$telemetryAccount.start|escape} — {$telemetryAccount.end|escape}</p>
<table class="comparison"><thead><tr><th>Activité</th>{foreach $telemetryAccount.players as $player}<th>{$player.username|escape} (#{$player.id})</th>{/foreach}</tr></thead><tbody>
<tr><th>Durée active estimée / jour</th>{foreach $telemetryAccount.players as $player}<td>{$player.activity.average|escape}</td>{/foreach}</tr>
<tr><th>Jours actifs</th>{foreach $telemetryAccount.players as $player}<td>{$player.activity.active_days} / {$telemetryAccount.period}</td>{/foreach}</tr>
<tr><th>Durée active estimée totale</th>{foreach $telemetryAccount.players as $player}<td>{$player.activity.total|escape}</td>{/foreach}</tr>
<tr><th>Dernière interaction</th>{foreach $telemetryAccount.players as $player}<td>{$player.activity.last_activity|escape}</td>{/foreach}</tr>
</tbody></table>
<p>Une interaction compte pour une minute. Les interactions espacées de cinq minutes au plus forment une même fenêtre, sans compter deux fois les chevauchements. La moyenne inclut tous les jours affichés.</p>
<p>Fenêtres d’activité estimées, dans votre fuseau horaire.</p>
<div class="scroll">
<div class="hours"><span>00 h</span><span>06 h</span><span>12 h</span><span>18 h</span><span>24 h</span></div>
{foreach $telemetryAccount.days as $day}
<p>{$day.name|escape} (#{$day.actor}) — {$day.day|escape} — {$day.duration|escape}</p><div class="heat">
{foreach $day.slots as $slot}<span class="bit" style="grid-column:{$slot.minute+1}" title="{$slot.date|escape}"></span>{/foreach}
</div>
<p>{foreach $day.windows as $window}<span>{$window.range|escape} ({$window.duration|escape})</span>{if !$window@last} · {/if}{foreachelse}Aucune activité.{/foreach}</p>
{/foreach}
</div>
{foreach $telemetryAccount.gaps as $gap}<p class="notice">{$gap.start|escape} — {$gap.end|escape} : {$gap.label|escape}.</p>{/foreach}
<h3>Actions depuis le {$telemetryAccount.event_start|escape}</h3>
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
{foreach $telemetryAccount.events as $event}<tr><td>{$event.date|escape}</td><td>{$event.actor} → {$event.target}</td><td>{$event.label|escape} {if $event.fleet_id}#{$event.fleet_id}{/if}</td><td>{foreach $event.details as $row}{$row.label|escape} : {$row.value|escape}<br>{/foreach}</td></tr>{/foreach}
</table></details>
{/if}
<h2>File de revue</h2>
<table><thead><tr><th>Comptes concernés</th><th>Signalement</th><th>Force</th><th>Explication</th><th>Observations</th><th>Statut</th></tr></thead><tbody>
{foreach $telemetryWarnings as $warning}
<tr><td><a href="?page=telemetry&amp;account={$warning.actor}&amp;other={$warning.other}">#{$warning.actor}{if $warning.other} / #{$warning.other}{/if}</a></td><td><a href="?page=telemetry&amp;id={$warning.id}">{$warning.kind_label|escape}</a></td><td>{$warning.strength_label|escape}</td><td>{$warning.explanation|escape}</td><td>{$warning.dates|escape}</td><td>{$warning.status_label|escape}</td></tr>
{foreachelse}<tr><td colspan="6">Aucun dossier dans cette vue.</td></tr>{/foreach}
</tbody></table>
{if $telemetryNext}<a href="?page=telemetry&amp;status={$telemetryStatus|escape}&amp;before={$telemetryNext}">Dossiers suivants</a>{/if}
<h2 id="telemetry-health">État de collecte</h2>
<p>Dernière collecte, analyses, stockage et interruptions enregistrées.</p>
<table class="metrics">{foreach $telemetryHealth as $row}<tr><th>{$row.label|escape}</th><td>{$row.value|escape}</td></tr>{/foreach}</table>
{foreach $telemetryGaps as $gap}<p class="notice">{$gap.start|escape} — {$gap.end|escape} : {$gap.label|escape}.</p>{/foreach}
<details id="telemetry-settings" {if $telemetryShowSettings}open{/if}><summary>Réglages</summary>

<form method="post" action="?page=telemetry#telemetry-settings">
<input type="hidden" name="token" value="{$telemetryToken|escape}"><input type="hidden" name="action" value="settings">
{foreach $telemetryFields as $group=>$fields}<fieldset><legend>{$telemetryGroups[$group]|escape}</legend>
{foreach $fields as $field}<p><label for="telemetry-{$field.key}">{$field.label|escape}</label>
<input id="telemetry-{$field.key}" name="settings[{$field.key}]" type="number" step="{$field.step}" min="{$field.min}" max="{$field.max}" value="{$field.value}" required> {$field.unit|escape}
<button type="button" onclick="document.getElementById('telemetry-{$field.key}').value='{$field.default}'">Valeur par défaut ({$field.default})</button>
<small>{$field.help|escape} Limites : {$field.min}–{$field.max}.</small></p>{/foreach}
</fieldset>{/foreach}
<button type="submit">Enregistrer les réglages</button>
</form>
<details><summary>Dernières modifications des réglages</summary>
{foreach $telemetrySettingsHistory as $entry}<p>{$entry.date|escape} — modérateur #{$entry.admin}</p><table>{foreach $entry.changes as $row}<tr><th>{$row.label|escape}</th><td>{$row.value|escape}</td></tr>{/foreach}</table>{/foreach}
</details>
</details>
</div>
{include file="overall_footer.tpl"}
