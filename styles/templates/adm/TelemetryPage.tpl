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
.telemetry .heat {height:24px;background:#202c3b;position:relative;overflow:hidden;border-radius:3px}
.telemetry .heat:after {content:"";position:absolute;inset:0;pointer-events:none;background:repeating-linear-gradient(to right,transparent 0,transparent calc(4.16667% - 1px),#ffffff24 calc(4.16667% - 1px),#ffffff24 4.16667%)}
.telemetry .bit {background:#73cba6;position:absolute;top:0;bottom:0}
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
<h1>{if $telemetryShowSettings}{$LNG.telemetry_ui_settings_title}{else}{$LNG.modul_43}{/if}</h1>
{if $telemetryShowSettings}
<form class="timezone-form" method="get" action="admin.php">
<input type="hidden" name="page" value="telemetry">
<input type="hidden" name="view" value="settings">
<label for="telemetry-timezone">{$LNG.telemetry_ui_timezone}</label>
<select id="telemetry-timezone" name="timezone">{html_options options=$telemetryTimezones selected=$telemetryTimezone}</select>
<button type="submit">{$LNG.telemetry_ui_apply}</button>
</form>
{/if}
{if $telemetryMessage}<p class="notice">{$telemetryMessage|escape}</p>{/if}
{if $telemetryError}<p class="error" role="alert">{$telemetryError|escape}</p>{/if}
<nav><a href="?page=telemetry">{$LNG.telemetry_ui_review}</a><a href="?page=telemetry&amp;status=follow_up">{$LNG.telemetry_ui_follow_up}</a><a href="?page=telemetry&amp;status=dismissed">{$LNG.telemetry_ui_dismissed_plural}</a><a href="?page=telemetry&amp;view=settings#telemetry-settings">{$LNG.telemetry_ui_settings}</a>{if !$telemetryShowSettings}<a href="#telemetry-health" onclick="document.getElementById('telemetry-health').open=true">{$LNG.telemetry_ui_health}</a>{/if}</nav>
{if !$telemetryShowSettings}
{if $telemetryCase}
<p><a href="?page=telemetry&amp;account={$telemetryCase.actor}">← {$telemetryNames[$telemetryCase.actor]|escape}</a>{if $telemetryCase.other} · <a href="?page=telemetry&amp;account={$telemetryCase.other}">{$telemetryNames[$telemetryCase.other]|escape}</a>{/if}</p>
<h2>{$LNG.telemetry_ui_case}{$telemetryCase.id} — {$telemetryCase.kind_label|escape}</h2>
<p>{$telemetryCase.explanation|escape} {$LNG.telemetry_ui_strength} : <strong>{$telemetryCase.strength_label|escape}</strong>. {$LNG.telemetry_ui_status} : {$telemetryCase.status_label|escape}.</p>
<p>{$LNG.telemetry_ui_created}{$telemetryCase.formatted_first|escape}{$LNG.telemetry_ui_last_observation_on}{$telemetryCase.formatted_last|escape}.</p>
<p><a href="?page=telemetry&amp;account={$telemetryCase.actor}&amp;other={$telemetryCase.other}">{$LNG.telemetry_ui_view_activity}</a></p>
{foreach $telemetryCase.evaluations as $evaluation}
<details {if $evaluation@first}open{/if}><summary>{$evaluation.label|escape} — {$evaluation.date|escape}</summary>
{if !$evaluation.matches}<p>{$LNG.telemetry_ui_no_match}</p>{/if}
{if $evaluation.exchange}
<p><strong>{$telemetryNames[$evaluation.exchange.sender]|escape} → {$telemetryNames[$evaluation.exchange.recipient]|escape}</strong></p>
<table class="resources"><thead><tr><th>{$LNG.telemetry_ui_resources}</th><th>{$LNG.telemetry_ui_metal}</th><th>{$LNG.telemetry_ui_crystal}</th><th>{$LNG.telemetry_ui_deuterium}</th></tr></thead><tbody>
{foreach $evaluation.exchange.resources as $row}<tr><th>{$row.label|escape}</th><td>{$row.metal|escape}</td><td>{$row.crystal|escape}</td><td>{$row.deuterium|escape}</td></tr>{/foreach}
</tbody></table>
<table class="metrics exchange-summary"><tbody>{foreach $evaluation.exchange.summary as $row}<tr><th>{$row.label|escape}</th><td>{$row.value|escape}</td></tr>{/foreach}</tbody></table>
{else}
<table class="metrics"><tbody>{foreach $evaluation.metrics as $row}<tr><th>{$row.label|escape}</th><td>{$row.value|escape}</td></tr>{/foreach}</tbody></table>
{/if}
<details><summary>{$LNG.telemetry_ui_thresholds}</summary><table class="metrics">{foreach $evaluation.thresholds as $row}<tr><th>{$row.label|escape}</th><td>{$row.value|escape}</td></tr>{/foreach}</table></details>
{if $evaluation.truncated}<p class="notice">{$LNG.telemetry_ui_partial_batch}</p>{/if}
{if $evaluation.sampled}<p>{$LNG.telemetry_ui_sample_first}{$evaluation.count}{$LNG.telemetry_ui_sample_last}</p>{/if}
<div class="evidence"><table class="timeline"><thead><tr><th>{$LNG.telemetry_ui_date}</th><th>{$LNG.telemetry_ui_actor_target}</th><th>{$LNG.telemetry_ui_action_fleet}</th><th>{$LNG.telemetry_ui_context}</th></tr></thead><tbody>
{foreach $evaluation.timeline as $event}
<tr><td>{$event.date|escape}</td><td>{$telemetryNames[$event.actor|default:0]|default:'—'|escape} → {$telemetryNames[$event.target|default:0]|default:'—'|escape}</td><td>{$event.label|escape}</td><td>{foreach $event.details as $row}<span>{$row.label|escape} : {$row.value|escape}</span><br>{/foreach}</td></tr>
{/foreach}
</tbody></table></div>
</details>
{/foreach}
<form method="post" action="?page=telemetry&amp;id={$telemetryCase.id}">
<input type="hidden" name="sid" value="{$telemetryToken|escape}"><input type="hidden" name="action" value="review"><input type="hidden" name="id" value="{$telemetryCase.id}">
<label>{$LNG.telemetry_ui_decision}<select name="status"><option value="open" {if $telemetryCase.status=='open'}selected{/if}>{$LNG.telemetry_ui_review}</option><option value="follow_up" {if $telemetryCase.status=='follow_up'}selected{/if}>{$LNG.telemetry_ui_follow_up}</option><option value="dismissed" {if $telemetryCase.status=='dismissed'}selected{/if}>{$LNG.telemetry_ui_dismissed}</option></select></label>
<label>{$LNG.telemetry_ui_note}<textarea name="note" maxlength="4000"></textarea></label><button type="submit">{$LNG.telemetry_ui_save_decision}</button>
</form>
<h3>{$LNG.telemetry_ui_review_history}</h3>
{foreach $telemetryCase.review_history as $entry}<p>{$entry.date|escape}{if $entry.admin}{$LNG.telemetry_ui_moderator}{$entry.admin}{/if} : {$entry.status|escape} — {$entry.note|escape}</p>{/foreach}
{/if}
<details {if $telemetryAccount}open{/if}><summary>{$LNG.telemetry_ui_lookup}</summary>
<form method="get" action="admin.php">
<input type="hidden" name="page" value="telemetry">
<label>{$LNG.telemetry_ui_account_id}<input name="account" type="number" min="1" value="{$telemetryAccount.id|default:''}" required></label>
<label>{$LNG.telemetry_ui_compare_optional}<input name="other" type="number" min="1" value="{if !empty($telemetryAccount.other)}{$telemetryAccount.other}{/if}"></label>
<label>{$LNG.telemetry_ui_period}<input name="days" type="number" min="1" max="{$telemetryMaxDays}" value="{$telemetryAccount.period|default:7}">{$LNG.telemetry_ui_days}</label>
<button type="submit">{$LNG.telemetry_ui_show}</button>
</form></details>
{if $telemetryAccount}
<h2>{foreach $telemetryAccount.players as $player}{$player.username|escape} ({$player.id}){if !$player@last} / {/if}{/foreach}</h2>
<section class="panel" id="player-warnings">
<h3>{$LNG.telemetry_ui_warnings_for}{$telemetryAccount.players[0].username|escape}</h3>
<div class="scroll"><table class="warnings"><thead><tr><th>{$LNG.telemetry_ui_warning}</th><th>{$LNG.telemetry_ui_strength}</th><th>{$LNG.telemetry_ui_accounts_involved}</th><th>{$LNG.telemetry_ui_observations}</th><th>{$LNG.telemetry_ui_status}</th></tr></thead><tbody>
{foreach $telemetryWarnings as $warning}
<tr><td><a href="?page=telemetry&amp;id={$warning.id}">{$warning.kind_label|escape}</a><small>{$warning.explanation|escape}</small></td><td>{$warning.strength_label|escape}</td><td><a href="?page=telemetry&amp;account={$warning.actor}">{$telemetryNames[$warning.actor]|escape}</a>{if $warning.other} / <a href="?page=telemetry&amp;account={$warning.other}">{$telemetryNames[$warning.other]|escape}</a>{/if}</td><td>{$warning.dates|escape}</td><td>{$warning.status_label|escape}</td></tr>
{foreachelse}<tr><td colspan="5">{$LNG.telemetry_ui_no_warnings}</td></tr>{/foreach}
</tbody></table></div>
{if $telemetryNext}<a href="?page=telemetry&amp;account={$telemetryAccount.id}&amp;other={$telemetryAccount.other}&amp;days={$telemetryAccount.period}&amp;before={$telemetryNext}#player-warnings">{$LNG.telemetry_ui_next_warnings}</a>{/if}
</section>
<section class="panel"><h3>{$LNG.telemetry_ui_activity}</h3>
<p>{$telemetryAccount.start|escape} — {$telemetryAccount.end|escape}</p>
<table class="comparison"><thead><tr><th>{$LNG.telemetry_ui_activity}</th>{foreach $telemetryAccount.players as $player}<th>{$player.username|escape} ({$player.id})</th>{/foreach}</tr></thead><tbody>
<tr><th>{$LNG.telemetry_ui_average_time}</th>{foreach $telemetryAccount.players as $player}<td>{$player.activity.average|escape}</td>{/foreach}</tr>
<tr><th>{$LNG.telemetry_ui_active_days}</th>{foreach $telemetryAccount.players as $player}<td>{$player.activity.active_days} / {$telemetryAccount.period}</td>{/foreach}</tr>
<tr><th>{$LNG.telemetry_ui_total_time}</th>{foreach $telemetryAccount.players as $player}<td>{$player.activity.total|escape}</td>{/foreach}</tr>
<tr><th>{$LNG.telemetry_ui_last_interaction}</th>{foreach $telemetryAccount.players as $player}<td>{$player.activity.last_activity|escape}</td>{/foreach}</tr>
</tbody></table>
<h3>{$LNG.telemetry_ui_activity_daily}</h3>
<div class="scroll">
<table class="heatmap"><thead><tr><th>{$LNG.telemetry_ui_day}</th><th><div class="hours"><span>00</span><span>06</span><span>12</span><span>18</span><span>24 h</span></div></th><th>{$LNG.telemetry_ui_active_time}</th></tr></thead><tbody>
{foreach $telemetryAccount.days as $day}
<tr {if $day.actor==$telemetryAccount.id}class="new-day"{/if}><th>{$day.day|escape}{if $telemetryAccount.other}<small>{$day.name|escape}</small>{/if}</th><td><div class="heat" role="img" aria-label="{$day.name|escape}, {$day.day|escape} : {$day.duration|escape}">
{foreach $day.bars as $bar}<span class="bit" style="left:{$bar.start}%;width:{$bar.width}%" title="{$bar.date|escape}"></span>{/foreach}
</div></td><td>{$day.duration|escape}</td></tr>
{/foreach}
</tbody></table>
</div>
<span class="legend">{$LNG.telemetry_ui_estimated_activity}</span>
<details><summary>{$LNG.telemetry_ui_windows}</summary>
<table><thead><tr><th>{$LNG.telemetry_ui_day}</th>{if $telemetryAccount.other}<th>{$LNG.telemetry_ui_account}</th>{/if}<th>{$LNG.telemetry_ui_times}</th><th>{$LNG.telemetry_ui_duration}</th></tr></thead><tbody>
{foreach $telemetryAccount.days as $day}{foreach $day.windows as $window}<tr><td>{$day.day|escape}</td>{if $telemetryAccount.other}<td>{$day.name|escape}</td>{/if}<td>{$window.range|escape}</td><td>{$window.duration|escape}</td></tr>{/foreach}{/foreach}
</tbody></table>
</details>
</section>
<section class="panel"><h3>{$LNG.telemetry_ui_actions_since}{$telemetryAccount.start|escape}</h3>
<table class="comparison"><thead><tr><th>{$LNG.telemetry_ui_actions}</th>{foreach $telemetryAccount.players as $player}<th>{$player.username|escape}</th>{/foreach}</tr></thead><tbody>
<tr><th>{$LNG.telemetry_ui_sends}</th>{foreach $telemetryAccount.players as $player}<td>{$player.sends}</td>{/foreach}</tr>
<tr><th>{$LNG.telemetry_ui_recalls}</th>{foreach $telemetryAccount.players as $player}<td>{$player.recalls}</td>{/foreach}</tr>
<tr><th>{$LNG.telemetry_ui_ip_addresses}</th>{foreach $telemetryAccount.players as $player}<td>{$player.network.ips}</td>{/foreach}</tr>
<tr><th>{$LNG.telemetry_ui_clients}</th>{foreach $telemetryAccount.players as $player}<td>{$player.network.clients}</td>{/foreach}</tr>
<tr><th>{$LNG.telemetry_ui_galaxy_views}</th>{foreach $telemetryAccount.players as $player}<td>{$player.reads}</td>{/foreach}</tr>
<tr><th>{$LNG.telemetry_ui_page_loads}</th>{foreach $telemetryAccount.players as $player}<td>{$player.pages}</td>{/foreach}</tr>
<tr><th>{$LNG.telemetry_ui_planet_switches}</th>{foreach $telemetryAccount.players as $player}<td>{$player.switches}</td>{/foreach}</tr>
<tr><th>{$LNG.telemetry_ui_alliance_views}</th>{foreach $telemetryAccount.players as $player}<td>{$player.alliance}</td>{/foreach}</tr>
<tr><th>{$LNG.telemetry_ui_queue_reloads}</th>{foreach $telemetryAccount.players as $player}<td>{$player.reloads}</td>{/foreach}</tr>
</tbody></table>
<details><summary>{$LNG.telemetry_ui_network}</summary>
<p>{$LNG.telemetry_ui_client_reported}</p>
{foreach $telemetryAccount.players as $player}
<h4>{$player.username|escape}</h4>
<table class="network"><thead><tr><th>IP</th><th>Client</th><th>{$LNG.telemetry_ui_requests}</th><th>{$LNG.telemetry_ui_first_use}</th><th>{$LNG.telemetry_ui_last_use}</th></tr></thead><tbody>
{foreach $player.network.rows as $network}<tr><td>{$network.ip|default:'—'|escape}</td><td>{$network.client|default:'—'|escape}</td><td>{$network.requests}</td><td>{$network.first_seen|escape}</td><td>{$network.last_seen|escape}</td></tr>
{foreachelse}<tr><td colspan="5">{$LNG.telemetry_ui_no_observations}</td></tr>{/foreach}
</tbody></table>
{if $player.network.more}<p>{$LNG.telemetry_ui_network_limit}</p>{/if}
{/foreach}
</details>
<details><summary>{$LNG.telemetry_ui_recent_actions}</summary>
<table><tr><th>{$LNG.telemetry_ui_date}</th><th>{$LNG.telemetry_ui_actor_target}</th><th>{$LNG.telemetry_ui_action}</th><th>{$LNG.telemetry_ui_context}</th></tr>
{foreach $telemetryAccount.events as $event}<tr><td>{$event.date|escape}</td><td>{$telemetryNames[$event.actor]|default:'—'|escape} → {$telemetryNames[$event.target]|default:'—'|escape}</td><td>{$event.label|escape}</td><td>{foreach $event.details as $row}{$row.label|escape} : {$row.value|escape}<br>{/foreach}</td></tr>{/foreach}
</table></details>
</section>
{/if}
{if !$telemetryAccount && !$telemetryCase}
<h2>{$LNG.telemetry_ui_warnings_by_account}</h2>
<table class="account-list"><thead><tr><th>{$LNG.telemetry_ui_account}</th><th>{$LNG.telemetry_ui_warnings}</th><th>{$LNG.telemetry_ui_observations}</th><th>{$LNG.telemetry_ui_last_observation}</th></tr></thead><tbody>
{foreach $telemetryAccounts as $player}
<tr><td><a href="?page=telemetry&amp;account={$player.account}">{$player.name|escape}</a></td><td>{$player.total}</td><td>{$player.kinds|escape}</td><td>{$player.date|escape}</td></tr>
{foreachelse}<tr><td colspan="4">{$LNG.telemetry_ui_no_accounts}</td></tr>{/foreach}
</tbody></table>
{if $telemetryNext}<a href="?page=telemetry&amp;status={$telemetryStatus|escape}&amp;before={$telemetryNext}">{$LNG.telemetry_ui_next_accounts}</a>{/if}
{/if}
<details id="telemetry-health"><summary>{$LNG.telemetry_ui_health}</summary>

<table class="metrics">{foreach $telemetryHealth as $row}<tr><th>{$row.label|escape}</th><td>{$row.value|escape}</td></tr>{/foreach}</table>
</details>
{/if}
{if $telemetryShowSettings}
<section id="telemetry-settings">
{if allowedTo('ShowModulePage')}<p>{$LNG.telemetry_ui_collection_module}<strong>{if $telemetryEnabled}{$LNG.telemetry_ui_enabled_lower}{else}{$LNG.telemetry_ui_disabled_lower}{/if}</strong> · <a href="?page=module">{$LNG.telemetry_ui_edit_modules}</a></p>{/if}

<form class="settings-form" method="post" action="?page=telemetry&amp;view=settings">
<input type="hidden" name="sid" value="{$telemetryToken|escape}"><input type="hidden" name="action" value="settings">
{foreach $telemetryFields as $group=>$fields}<details class="panel" id="telemetry-group-{$group}" {if $group=='collection'}open{/if}><summary>{$telemetryGroups[$group]|escape}</summary>
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
<div class="settings-actions"><button type="submit">{$LNG.telemetry_ui_save_settings}</button>
<button type="button" onclick="this.form.querySelectorAll('[data-default]').forEach(function(input) { if (input.type === 'checkbox') input.checked = input.dataset.default === '1'; else input.value = input.dataset.default; })">{$LNG.telemetry_ui_reset_defaults}</button></div>
</form>
<details><summary>{$LNG.telemetry_ui_settings_history}</summary>
{foreach $telemetrySettingsHistory as $entry}<p>{$entry.date|escape}{$LNG.telemetry_ui_moderator}{$entry.admin}</p><table>{foreach $entry.changes as $row}<tr><th>{$row.label|escape}</th><td>{$row.value|escape}</td></tr>{/foreach}</table>{/foreach}
</details>
</section>
{/if}
</div>
{include file="overall_footer.tpl"}
