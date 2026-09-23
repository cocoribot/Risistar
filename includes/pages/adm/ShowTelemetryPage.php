<?php

require_once ROOT_PATH . 'includes/classes/TelemetryDetectors.class.php';
require_once ROOT_PATH . 'includes/classes/TelemetryReview.class.php';
require_once ROOT_PATH . 'includes/classes/TelemetryPresentation.class.php';

function ShowTelemetryPage()
{
	global $LNG;
	$session = Session::load();
	if (!allowedTo('ShowTelemetryPage') || $session->adminAccess != 1) {
		http_response_code(403);
		$template = new template();
		$template->message($LNG['telemetry_access']);
		return;
	}
	$universe = (int) Universe::getEmulated();
	$now = time();
	// Choosing a universe saves and closes the session; open it again to save the display timezone.
	if (session_status() !== PHP_SESSION_ACTIVE) {
		session_start();
	}
	$token = session_id();
	$timezone = telemetryTimezone($session);
	$view = new TelemetryPresentation($timezone);
	$settings = TelemetrySettings::get($universe);
	$showSettings = ($_GET['view'] ?? '') === 'settings' || ($_POST['action'] ?? '') === 'settings';
	$status = $_GET['status'] ?? 'open';
	if (!in_array($status, ['open', 'follow_up', 'dismissed'], true)) {
		$status = 'open';
	}
	$actor = max(0, (int) ($_GET['account'] ?? 0));
	$id = max(0, (int) ($_GET['id'] ?? $_POST['id'] ?? 0));
	$before = max(1, (int) ($_GET['before'] ?? PHP_INT_MAX));
	$message = '';
	$error = '';
	$case = null;
	$account = [];
	$warnings = [];
	$accounts = [];
	$names = [];
	$settingsHistory = [];
	try {
		$store = new TelemetryStore(TelemetryConnection::open());
		$review = new TelemetryReview($store);
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$message = telemetryHandlePost($review, $universe, $token, $now);
			$settings = TelemetrySettings::get($universe);
		}
		if ($id) {
			$case = telemetryCase($review, $view, $universe, $id);
		}
		if ($actor) {
			$account = telemetryAccount($store, $view, $universe, $actor, $timezone, $now);
			$warnings = $store->accountWarnings($universe, $actor, $before);
		} elseif (!$id && !$showSettings) {
			$accounts = $store->warnedAccounts($universe, $status, $before);
		}
		$names = telemetryNames($universe, telemetryAccountIds($case, $account, $warnings, $accounts));
		foreach ($accounts as &$row) {
			$row['name'] = $names[$row['account']];
			$row['kinds'] = implode(', ', array_map([TelemetryPresentation::class, 'label'], explode(',', $row['kinds'])));
			$row['date'] = $view->date((int) $row['last_seen']);
		}
		unset($row);
		foreach ($warnings as &$warning) {
			$warning['explanation'] = TelemetryPresentation::explanation($warning['kind']);
			$warning['kind_label'] = TelemetryPresentation::label($warning['kind']);
			$warning['strength_label'] = TelemetryPresentation::label($warning['strength']);
			$warning['status_label'] = TelemetryPresentation::label($warning['status']);
			$warning['dates'] = $view->date((int) $warning['observation_start']) . ' — ' . $view->date((int) $warning['observation_end']);
		}
		unset($warning);
		$settingsHistory = telemetrySettingsHistory($store, $view, $universe);
	} catch (Throwable $e) {
		if (!$e instanceof InvalidArgumentException) {
			error_log('Telemetry admin failed: ' . get_class($e) . ' (' . $e->getCode() . ')');
		}
		$error = $e instanceof InvalidArgumentException ? $e->getMessage() : $LNG['telemetry_unavailable'];
	}
	$pageSize = TelemetryStore::PAGE_SIZE;
	$next = 0;
	if (count($warnings) > $pageSize) {
		$next = $warnings[$pageSize - 1]['id'];
	} elseif (count($accounts) > $pageSize) {
		$next = $accounts[$pageSize - 1]['account'];
	}
	$template = new template();
	$template->assign_vars([
		'telemetryMessage' => $message,
		'telemetryError' => $error,
		'telemetryWarnings' => array_slice($warnings, 0, $pageSize),
		'telemetryAccounts' => array_slice($accounts, 0, $pageSize),
		'telemetryNames' => $names,
		'telemetryNext' => $next,
		'telemetryCase' => $case,
		'telemetryAccount' => $account,
		'telemetryShowSettings' => $showSettings,
		'telemetryEnabled' => (bool) $settings['enabled'],
		'telemetryMaxDays' => TelemetrySettings::DAILY_DAYS,
		'telemetryFields' => telemetrySettingFields($settings),
		'telemetryToken' => $token,
		'telemetryHealth' => telemetryHealth($view, $universe, $settings),
		'telemetryStatus' => $status,
		'telemetrySettingsHistory' => $settingsHistory,
		'telemetryTimezones' => ['UTC' => 'UTC'] + get_timezone_selector(),
		'telemetryTimezone' => $timezone->getName(),
		'telemetryGroups' => [
			'collection' => $LNG['telemetry_collection'],
			'activity' => $LNG['telemetry_group_activity'],
			'automation' => $LNG['telemetry_group_automation'],
			'pushing' => $LNG['telemetry_group_pushing'],
		],
	]);
	$template->show('TelemetryPage.tpl');
}

function telemetryTimezone($session): DateTimeZone
{
	$name = HTTP::_GP('timezone', $session->telemetryTimezone ?? 'Europe/Paris');
	if (!in_array($name, DateTimeZone::listIdentifiers(), true)) {
		$name = 'Europe/Paris';
	}
	$session->telemetryTimezone = $name;
	$session->save();
	return new DateTimeZone($name);
}

function telemetryHandlePost(TelemetryReview $review, int $universe, string $token, int $now): string
{
	global $USER, $LNG;
	if (!is_string($_POST['sid'] ?? null) || !hash_equals($token, $_POST['sid'])) {
		throw new InvalidArgumentException($LNG['telemetry_expired']);
	}
	$action = $_POST['action'] ?? '';
	if ($action === 'settings') {
		$input = $_POST['settings'] ?? [];
		if (!is_array($input)) {
			throw new InvalidArgumentException($LNG['telemetry_invalid_settings']);
		}
		foreach (TelemetrySettings::definitions() as $name => $definition) {
			$scale = TelemetrySettings::scale($definition);
			if ($scale !== 1 && is_numeric($input[$name] ?? null)) {
				$input[$name] = (float) $input[$name] / $scale;
			}
		}
		$review->saveSettings($universe, (int) $USER['id'], $input, $now);
		return $LNG['telemetry_settings_saved'];
	}
	if ($action === 'review') {
		$review->decide(
			$universe,
			(int) $USER['id'],
			(int) ($_POST['id'] ?? 0),
			(string) ($_POST['status'] ?? ''),
			(string) ($_POST['note'] ?? ''),
			$now
		);
		return $LNG['telemetry_decision_saved'];
	}
	return '';
}

function telemetryCase(TelemetryReview $review, TelemetryPresentation $view, int $universe, int $id): array
{
	global $LNG;
	$case = $review->evidence($universe, $id);
	$case['explanation'] = TelemetryPresentation::explanation($case['kind']);
	$case['formatted_first'] = $view->date((int) $case['first_seen']);
	$case['formatted_last'] = $view->date((int) $case['last_seen']);
	$case['kind_label'] = TelemetryPresentation::label($case['kind']);
	$case['strength_label'] = TelemetryPresentation::label($case['strength']);
	$case['status_label'] = TelemetryPresentation::label($case['status']);
	$case['evaluations'] = [];
	$evaluations = [
		'latest_evidence' => [$LNG['telemetry_latest_evaluation'], 'latest_settings'],
		'evidence' => [$LNG['telemetry_opening'], 'settings'],
	];
	foreach ($evaluations as $key => [$label, $settingsKey]) {
		$evidence = $case[$key];
		if (!$evidence) {
			continue;
		}
		$thresholds = [];
		$definitions = TelemetrySettings::definitions();
		foreach ($case[$settingsKey] as $name => $value) {
			if (isset($definitions[$name])) {
				$thresholds[] = ['label' => $definitions[$name]['label'], 'value' => TelemetryPresentation::setting($name, $value)];
			}
		}
		$case['evaluations'][] = [
			'label' => $label,
			'date' => $view->date((int) ($evidence['evaluated_at'] ?? $case['first_seen'])),
			'matches' => $evidence['matches'] ?? true,
			'exchange' => $view->exchange($evidence['metrics']),
			'metrics' => $view->rows($evidence['metrics']),
			'thresholds' => $thresholds,
			'timeline' => $view->timeline($evidence['timeline']),
			'sampled' => $evidence['timeline_sampled'] ?? false,
			'count' => $evidence['timeline_count'] ?? count($evidence['timeline']),
			'truncated' => !empty($evidence['truncated']),
		];
	}
	foreach ($case['review_history'] as &$entry) {
		$data = json_decode($entry['data'], true);
		$entry['date'] = $view->date((int) $entry['at']);
		$entry['status'] = TelemetryPresentation::label($data['after']);
		$entry['note'] = empty($data['reopened']) ? $data['note'] : $LNG['telemetry_reopened'];
	}
	unset($entry);
	return $case;
}

/** Activity of one account, or two side by side when "other" is set. */
function telemetryAccount(
	TelemetryStore $store,
	TelemetryPresentation $view,
	int $universe,
	int $actor,
	DateTimeZone $timezone,
	int $now
): array {
	global $LNG;
	$other = max(0, (int) ($_GET['other'] ?? 0));
	if ($other === $actor) {
		$other = 0;
	}
	$period = min(TelemetrySettings::DAILY_DAYS, max(1, (int) ($_GET['days'] ?? 7)));
	$from = (new DateTimeImmutable('@' . $now))->setTimezone($timezone)->setTime(0, 0)
		->modify('-' . ($period - 1) . ' days')->getTimestamp();
	$account = [
		'id' => $actor,
		'other' => $other,
		'period' => $period,
		'players' => [],
		'events' => [],
		'start' => $view->date($from),
		'end' => $view->date($now),
	];
	foreach (array_filter([$actor, $other]) as $accountId) {
		$player = Database::get()->selectSingle(
			'SELECT id,username FROM %%USERS%% WHERE universe=:universe AND id=:id',
			[':universe' => $universe, ':id' => $accountId]
		);
		if (!$player) {
			$player = ['id' => $accountId, 'username' => $LNG['telemetry_deleted_account']];
		}
		$counts = $store->actionCounts($universe, $accountId, $from, $now);
		$player['activity'] = $view->activity($store->daily($universe, $accountId, $from), $from, $now);
		$player['sends'] = $counts['fleet.send'] ?? 0;
		$player['recalls'] = $counts['fleet.recall'] ?? 0;
		$player['reads'] = $counts['galaxy.view'] ?? 0;
		$player['pages'] = ($counts['interaction'] ?? 0) + ($counts['reload'] ?? 0)
			+ ($counts['planet.switch'] ?? 0) + ($counts['alliance.view'] ?? 0);
		$player['switches'] = $counts['planet.switch'] ?? 0;
		$player['alliance'] = $counts['alliance.view'] ?? 0;
		$player['reloads'] = $counts['passive'] ?? 0;
		$player['network'] = $store->network($universe, $accountId, $from, $now);
		foreach ($player['network']['rows'] as &$network) {
			$network['client'] = $network['client'] === null ? null : TelemetryPresentation::client($network['client']);
			$network['first_seen'] = $view->date((int) $network['first_seen']);
			$network['last_seen'] = $view->date((int) $network['last_seen']);
		}
		unset($network);
		$account['players'][] = $player;
		$recent = $store->events($universe, $accountId, $from, 500, null, true);
		$account['events'] = array_merge($account['events'], array_slice($recent, 0, 500));
	}
	// Show both accounts day by day in the heatmap.
	$account['days'] = [];
	foreach (array_keys($account['players'][0]['activity']['days']) as $date) {
		foreach ($account['players'] as $player) {
			$account['days'][] = ['actor' => $player['id'], 'name' => $player['username']] + $player['activity']['days'][$date];
		}
	}
	$account['events'] = array_reverse($view->timeline($account['events']));
	return $account;
}

function telemetryAccountIds(?array $case, array $account, array $warnings, array $accounts): array
{
	$ids = array_merge(array_column($accounts, 'account'), array_column($account['players'] ?? [], 'id'));
	$events = $account['events'] ?? [];
	foreach ($case['evaluations'] ?? [] as $evaluation) {
		$events = array_merge($events, $evaluation['timeline']);
	}
	foreach ($events as $event) {
		$ids[] = $event['actor'] ?? 0;
		$ids[] = $event['target'] ?? 0;
	}
	foreach ($warnings as $warning) {
		$ids[] = $warning['actor'];
		$ids[] = $warning['other'];
	}
	if ($case) {
		$ids[] = $case['actor'];
		$ids[] = $case['other'];
	}
	return array_unique(array_filter(array_map('intval', $ids)));
}

function telemetryNames(int $universe, array $accountIds): array
{
	global $LNG;
	if (!$accountIds) {
		return [];
	}
	$players = Database::get()->select(
		'SELECT id,username FROM %%USERS%% WHERE universe=:universe AND id IN (' . implode(',', $accountIds) . ')',
		[':universe' => $universe]
	);
	$names = [];
	foreach ($players as $player) {
		$names[$player['id']] = $player['username'] . ' (' . $player['id'] . ')';
	}
	foreach ($accountIds as $accountId) {
		$names[$accountId] ??= $LNG['telemetry_deleted_account'] . ' (' . $accountId . ')';
	}
	return $names;
}

function telemetrySettingsHistory(TelemetryStore $store, TelemetryPresentation $view, int $universe): array
{
	$history = $store->settingsHistory($universe);
	foreach ($history as &$entry) {
		$entry['date'] = $view->date((int) $entry['at']);
		$entry['changes'] = [];
		foreach (json_decode($entry['data'], true) as $name => $change) {
			$before = TelemetryPresentation::setting($name, $change['before']);
			$after = TelemetryPresentation::setting($name, $change['after']);
			$entry['changes'][] = ['label' => TelemetryPresentation::label($name), 'value' => $before . ' → ' . $after];
		}
	}
	unset($entry);
	return $history;
}

function telemetryHealth(TelemetryPresentation $view, int $universe, array $settings): array
{
	global $LNG;
	$health = TelemetryStore::health();
	$analysis = $health['analysis_' . $universe] ?? [];
	$megabytes = ' ' . $LNG['telemetry_unit_mb'];
	$days = ' ' . $LNG['telemetry_unit_days'];

	if (!$settings['enabled']) {
		$collection = $LNG['telemetry_disabled'];
	} elseif (empty($health['suspended'])) {
		$collection = $LNG['telemetry_enabled'];
	} elseif (TelemetryConnection::shared()) {
		$collection = $LNG['telemetry_label_storage_full'];
	} else {
		$collection = $LNG['telemetry_label_storage_events'];
	}
	$allocated = $LNG['telemetry_measure_pending'];
	if (isset($health['allocated_mb'])) {
		$allocated = round($health['allocated_mb'], 1) . $megabytes . ' / ' . TelemetrySettings::BUDGET_MB . $megabytes;
	}

	$date = static fn($at, $none) => $at ? $view->date((int) $at) : $none;

	$rows = [
		['label' => $LNG['telemetry_collection'], 'value' => $collection],
		['label' => $LNG['telemetry_last_write'], 'value' => $date($health['success'] ?? 0, $LNG['telemetry_none'])],
		['label' => $LNG['telemetry_last_pass'], 'value' => $date($analysis['at'] ?? 0, $LNG['telemetry_none'])],
		['label' => $LNG['telemetry_last_analysis'], 'value' => $date($analysis['completed_at'] ?? 0, $LNG['telemetry_none_female'])],
		['label' => $LNG['telemetry_allocated'], 'value' => $allocated],
		['label' => $LNG['telemetry_event_retention'], 'value' => TelemetrySettings::deliveryDays($settings) . $days],
		['label' => $LNG['telemetry_network_retention'], 'value' => $settings['network_hours'] . ' ' . $LNG['telemetry_unit_hours']],
		['label' => $LNG['telemetry_activity_retention'], 'value' => TelemetrySettings::DAILY_DAYS . $days],
	];
	if (!empty($health['failure'])) {
		$failure = TelemetryPresentation::label($health['failure']);
		if (isset($health['failed_at'])) {
			$failure .= ' — ' . $view->date($health['failed_at']);
		}
		$rows[] = ['label' => $LNG['telemetry_collection_failure'], 'value' => $failure];
	}
	if (($health['analysis_failed_at'] ?? 0) > ($analysis['at'] ?? 0)) {
		$rows[] = ['label' => $LNG['telemetry_label_analysis_failed'], 'value' => $view->date((int) $health['analysis_failed_at'])];
	}
	return $rows;
}

function telemetrySettingFields(array $settings): array
{
	$fields = [];
	foreach (TelemetrySettings::definitions() as $key => $definition) {
		$scale = TelemetrySettings::scale($definition);
		$fields[$definition['group']][] = [
			'key' => $key,
			'value' => $settings[$key] * $scale,
			'default' => $definition['default'] * $scale,
			'min' => $definition['min'] * $scale,
			'max' => $definition['max'] * $scale,
			'unit' => TelemetrySettings::unitLabel($definition),
			'checkbox' => $definition['unit'] === '0/1',
			'label' => $definition['label'],
			'help' => $definition['help'],
			'step' => is_int($definition['default']) ? '1' : 'any',
		];
	}
	return $fields;
}
