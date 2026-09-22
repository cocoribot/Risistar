<?php

require_once ROOT_PATH . 'includes/classes/TelemetryDetectors.class.php';
require_once ROOT_PATH . 'includes/classes/TelemetryReview.class.php';
require_once ROOT_PATH . 'includes/classes/TelemetryPresentation.class.php';
function ShowTelemetryPage()
{
    global $USER;
    $session = Session::load();
    if (!allowedTo('ShowTelemetryPage') || $session->adminAccess != 1) {
        http_response_code(403);
        $template = new template();
        $template->message('Accès modérateur requis.');
        return;
    }
    $universe = (int) Universe::getEmulated();
    $now = time();
    // Universe selection saves and closes the session; persist the form token before review work.
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $session->telemetryToken ??= bin2hex(random_bytes(32));
    $token = $session->telemetryToken;
    $timezoneName = HTTP::_GP('timezone', $session->telemetryTimezone ?? 'Europe/Paris');
    if (!in_array($timezoneName, DateTimeZone::listIdentifiers(), true)) {
        $timezoneName = 'Europe/Paris';
    }
    $timezone = new DateTimeZone($timezoneName);
    $session->telemetryTimezone = $timezoneName;
    $session->save();
    $message = '';
    $error = '';
    $warnings = [];
    $accounts = [];
    $names = [];
    $actor = max(0, (int) ($_GET['account'] ?? 0));
    $id = max(0, (int) ($_GET['id'] ?? $_POST['id'] ?? 0));
    $case = null;
    $account = [];
    $analysis = null;
    $settingsHistory = [];
    $showSettings = ($_GET['view'] ?? '') === 'settings' || ($_POST['action'] ?? '') === 'settings';
    $status = $_GET['status'] ?? 'open';
    if (!in_array($status, ['open', 'follow_up', 'dismissed'], true)) {
        $status = 'open';
    }
    $s = TelemetrySettings::get($universe);
    $view = new TelemetryPresentation($timezone);
    $format = static fn($at) => $view->date((int) $at);
    try {
        $store = new TelemetryStore(TelemetryConnection::open());
        $review = new TelemetryReview($store);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!is_string($_POST['token'] ?? null) || !hash_equals($token, $_POST['token'])) {
                throw new InvalidArgumentException('Formulaire expiré. Rechargez la page.');
            }
            $action = $_POST['action'] ?? '';
            if ($action === 'settings') {
                $input = $_POST['settings'] ?? [];
                if (!is_array($input)) {
                    throw new InvalidArgumentException('Réglages invalides.');
                }
                foreach (TelemetrySettings::definitions() as $name => $definition) {
                    if ($definition[4] === 'fraction' && is_numeric($input[$name] ?? null)) {
                        $input[$name] = (float) $input[$name] / 100;
                    }
                }
                $review->saveSettings($universe, (int) $USER['id'], $input, $now);
                $s = TelemetrySettings::get($universe);
                $message = 'Réglages enregistrés.';
            } elseif ($action === 'review') {
                $review->decide(
                    $universe,
                    (int) $USER['id'],
                    (int) ($_POST['id'] ?? 0),
                    (string) ($_POST['status'] ?? ''),
                    (string) ($_POST['note'] ?? ''),
                    $now
                );
                $message = 'Décision enregistrée.';
            }
        }
        if ($id) {
            $case = $review->refresh($universe, $id, $s, $now);
            $case['formatted_first'] = $format($case['first_seen']);
            $case['formatted_last'] = $format($case['last_seen']);
            $case['kind_label'] = TelemetryPresentation::label($case['kind']);
            $case['strength_label'] = TelemetryPresentation::label($case['strength']);
            $case['status_label'] = TelemetryPresentation::label($case['status']);
            $case['evaluations'] = [];
            foreach (['latest_evidence' => 'Dernière évaluation', 'evidence' => 'À l’ouverture'] as $key => $label) {
                if (!$case[$key]) {
                    continue;
                }
                $evidence = $case[$key];
                $thresholds = [];
                foreach ($case[$key === 'evidence' ? 'settings' : 'latest_settings'] as $name => $value) {
                    $definition = TelemetrySettings::definitions()[$name] ?? null;
                    if ($definition === null) {
                        continue;
                    }
                    $thresholds[] = ['label' => $definition[5], 'value' => TelemetryPresentation::setting($name, $value)];
                }
                $case['evaluations'][] = [
                    'label' => $label,
                    'date' => $format($evidence['evaluated_at'] ?? $case['first_seen']),
                    'matches' => $evidence['matches'] ?? true,
                    'support' => array_map([TelemetryPresentation::class, 'label'], array_diff($evidence['supporting_checks'] ?? [], [$case['kind']])),
                    'exchange' => $view->exchange($evidence['metrics']),
                    'metrics' => $view->rows($evidence['metrics']),
                    'thresholds' => $thresholds,
                    'timeline' => $view->timeline($evidence['timeline']),
                    'sampled' => $evidence['timeline_sampled'] ?? false,
                    'count' => $evidence['timeline_count'] ?? count($evidence['timeline']),
                    'gaps' => $view->gaps($evidence['coverage']['gaps'] ?? []),
                    'truncated' => !empty($evidence['coverage']['truncated']),
                ];
            }
            foreach ($case['review_history'] as &$entry) {
                $entry['date'] = $format($entry['at']);
                $data = json_decode($entry['data'], true);
                $entry['status'] = TelemetryPresentation::label($data['after']);
                $entry['note'] = $data['note'];
            }
            unset($entry);
        }
        if ($actor) {
            $other = max(0, (int) ($_GET['other'] ?? 0));
            if ($other === $actor) {
                $other = 0;
            }
            $period = min((int) $s['daily_days'], max(1, (int) ($_GET['days'] ?? 7)));
            $from = (new DateTimeImmutable('@' . $now))->setTimezone($timezone)->setTime(0, 0)->modify('-' . ($period - 1) . ' days')->getTimestamp();
            $eventFrom = max($from, $now - $s['event_days'] * 86400);
            $account = [
                'id' => $actor,
                'other' => $other,
                'period' => $period,
                'players' => [],
                'events' => [],
                'start' => $format($from),
                'end' => $format($now),
                'event_start' => $format($eventFrom),
            ];
            foreach (array_filter([$actor, $other]) as $accountId) {
                $player = Database::get()->selectSingle(
                    'SELECT id,username FROM %%USERS%% WHERE universe=:universe AND id=:id',
                    [':universe' => $universe, ':id' => $accountId]
                );
                if (!$player) {
                    throw new InvalidArgumentException('Compte introuvable dans cet univers.');
                }
                $activity = $view->activity($store->daily($universe, $accountId, $from), $from, $now);
                $counts = $store->actionCounts($universe, $accountId, $eventFrom, $now);
                $reads = 0;
                foreach ($counts as $kind => $count) {
                    if ($kind === 'galaxy.view') {
                        $reads += (int) $count;
                    }
                }
                $player['activity'] = $activity;
                $player['sends'] = $counts['fleet.send'] ?? 0;
                $player['recalls'] = $counts['fleet.recall'] ?? 0;
                $player['reads'] = $reads;
                $player['network'] = $store->network($universe, $accountId, $eventFrom, $now);
                foreach ($player['network']['rows'] as &$network) {
                    $network['first_seen'] = $format((int) $network['first_seen']);
                    $network['last_seen'] = $format((int) $network['last_seen']);
                }
                unset($network);
                $account['players'][] = $player;
                $account['events'] = array_merge($account['events'], array_slice($store->events($universe, $accountId, $eventFrom, 500, null, true), 0, 500));
            }
            $account['days'] = [];
            foreach ($account['players'][0]['activity']['days'] as $date => $unused) {
                foreach ($account['players'] as $player) {
                    $account['days'][] = ['actor' => $player['id'], 'name' => $player['username']] + $player['activity']['days'][$date];
                }
            }
            $account['events'] = array_reverse($view->timeline($account['events']));
            $account['gaps'] = $view->gaps(TelemetryStore::interruptions($universe, $from, $now, false));
            $account['event_gaps'] = $view->gaps(TelemetryStore::interruptions($universe, $eventFrom, $now));
        }
        if (!$id && !$actor && !$showSettings) {
            $analysis = $review->evaluate(
                $universe,
                $s,
                $now,
                max(0, (int) ($_GET['cursor'] ?? 0)),
                max(0, (int) ($_GET['pair_a'] ?? 0)),
                max(0, (int) ($_GET['pair_b'] ?? 0))
            );
        }
        $before = max(1, (int) ($_GET['before'] ?? PHP_INT_MAX));
        if ($actor) {
            $warnings = $store->query(
                'SELECT id,actor,other,kind,strength,explanation,observation_start,observation_end,status FROM telemetry_warnings WHERE universe=? AND (actor=? OR other=?) AND id<? ORDER BY id DESC LIMIT 51',
                [$universe, $actor, $actor, $before]
            )->fetchAll(PDO::FETCH_ASSOC);
        } elseif (!$id && !$showSettings) {
            $accounts = $store->query(
                'SELECT account,COUNT(*) AS total,GROUP_CONCAT(DISTINCT kind ORDER BY kind) AS kinds,MAX(observation_end) AS last_seen FROM (
                    SELECT actor AS account,kind,observation_end FROM telemetry_warnings WHERE universe=? AND status=?
                    UNION ALL
                    SELECT other AS account,kind,observation_end FROM telemetry_warnings WHERE universe=? AND status=? AND other>0 AND other<>actor
                ) AS warnings WHERE account<? GROUP BY account ORDER BY account DESC LIMIT 51',
                [$universe, $status, $universe, $status, $before]
            )->fetchAll(PDO::FETCH_ASSOC);
        }
        $accountIds = array_merge(array_column($accounts, 'account'), array_column($account['players'] ?? [], 'id'));
        $events = $account['events'] ?? [];
        foreach ($case['evaluations'] ?? [] as $evaluation) {
            $events = array_merge($events, $evaluation['timeline']);
        }
        foreach ($events as $event) {
            $accountIds[] = $event['actor'] ?? 0;
            $accountIds[] = $event['target'] ?? 0;
        }
        foreach ($warnings as $warning) {
            $accountIds[] = $warning['actor'];
            $accountIds[] = $warning['other'];
        }
        if ($case) {
            $accountIds[] = $case['actor'];
            $accountIds[] = $case['other'];
        }
        $accountIds = array_unique(array_filter(array_map('intval', $accountIds)));
        if ($accountIds) {
            $players = Database::get()->select(
                'SELECT id,username FROM %%USERS%% WHERE universe=:universe AND id IN (' . implode(',', $accountIds) . ')',
                [':universe' => $universe]
            );
            foreach ($players as $player) {
                $names[$player['id']] = $player['username'] . ' (' . $player['id'] . ')';
            }
            foreach ($accountIds as $accountId) {
                $names[$accountId] ??= 'Compte supprimé (' . $accountId . ')';
            }
        }
        foreach ($accounts as &$row) {
            $row['name'] = $names[$row['account']];
            $row['kinds'] = implode(', ', array_map([TelemetryPresentation::class, 'label'], explode(',', $row['kinds'])));
            $row['date'] = $format($row['last_seen']);
        }
        unset($row);
        foreach ($warnings as &$warning) {
            $warning['kind_label'] = TelemetryPresentation::label($warning['kind']);
            $warning['strength_label'] = TelemetryPresentation::label($warning['strength']);
            $warning['status_label'] = TelemetryPresentation::label($warning['status']);
            $warning['dates'] = $format($warning['observation_start']) . ' — ' . $format($warning['observation_end']);
        }
        unset($warning);
        $settingsHistory = $store->query("SELECT admin,at,data FROM telemetry_audit WHERE universe=? AND action='settings' ORDER BY at DESC LIMIT 20", [$universe])->fetchAll(PDO::FETCH_ASSOC);
        foreach ($settingsHistory as &$entry) {
            $entry['date'] = $format($entry['at']);
            $entry['changes'] = [];
            foreach (json_decode($entry['data'], true) as $name => $change) {
                $entry['changes'][] = [
                    'label' => TelemetryPresentation::label($name),
                    'value' => TelemetryPresentation::setting($name, $change['before']) . ' → ' . TelemetryPresentation::setting($name, $change['after']),
                ];
            }
        }
        unset($entry);
    } catch (Throwable $e) {
        $error = $e instanceof InvalidArgumentException ? $e->getMessage() : 'Collecte ou analyse indisponible. Vérifiez la connexion et les tables de la base dédiée.';
    }
    $health = TelemetryStore::health();
    $healthRows = [
        ['label' => 'Collecte', 'value' => TelemetryConnection::masterEnabled() && $s['enabled'] ? 'Active' : 'Désactivée'],
        ['label' => 'Événements', 'value' => !empty($health['suspended']) ? 'Suspendus : stockage plein' : ($s['events_enabled'] ? 'Activés' : 'Désactivés')],
        ['label' => 'Dernier enregistrement', 'value' => isset($health['success']) ? $format($health['success']) : 'Aucun'],
        ['label' => 'Dernière analyse', 'value' => isset($health['analysis_' . $universe]) ? $format($health['analysis_' . $universe]['at']) : 'Aucune'],
        ['label' => 'Stockage alloué', 'value' => isset($health['allocated_mb']) ? round($health['allocated_mb'], 1) . ' Mo / ' . $s['budget_mb'] . ' Mo' : 'Mesure au prochain nettoyage'],
        ['label' => 'Historique détaillé conservé', 'value' => ($health['effective_event_days'] ?? $s['event_days']) . ' jours'],
        ['label' => 'Activité compacte conservée', 'value' => $s['daily_days'] . ' jours'],
    ];
    if (!empty($health['failure'])) {
        $healthRows[] = ['label' => 'Incident de collecte', 'value' => TelemetryPresentation::label($health['failure'])];
    }
    $fields = [];
    foreach (TelemetrySettings::definitions() as $key => $d) {
        $scale = $d[4] === 'fraction' ? 100 : 1;
        $fields[$d[0]][] = [
            'key' => $key,
            'value' => $s[$key] * $scale,
            'default' => $d[1] * $scale,
            'min' => $d[2] * $scale,
            'max' => $d[3] * $scale,
            'unit' => $scale === 100 ? '%' : $d[4],
            'checkbox' => $d[4] === '0/1',
            'label' => $d[5],
            'help' => $d[6],
            'step' => is_int($d[1]) ? '1' : 'any',
        ];
    }
    $template = new template();
    $template->assign_vars([
        'telemetryMessage' => $message,
        'telemetryError' => $error,
        'telemetryWarnings' => array_slice($warnings, 0, 50),
        'telemetryAccounts' => array_slice($accounts, 0, 50),
        'telemetryNames' => $names,
        'telemetryNext' => count($warnings) > 50 ? $warnings[49]['id'] : (count($accounts) > 50 ? $accounts[49]['account'] : 0),
        'telemetryCase' => $case,
        'telemetryAccount' => $account,
        'telemetryShowSettings' => $showSettings,
        'telemetryEnabled' => (bool) $s['enabled'],
        'telemetryMaxDays' => $s['daily_days'],
        'telemetryFields' => $fields,
        'telemetryToken' => $token,
        'telemetryAnalysis' => $analysis,
        'telemetryHealth' => $healthRows,
        'telemetryStatus' => $status,
        'telemetrySettingsHistory' => $settingsHistory,
        'telemetryGaps' => $view->gaps(TelemetryStore::interruptions($universe, $now - $s['daily_days'] * 86400, $now)),
        'telemetryMaster' => TelemetryConnection::masterEnabled(),
        'telemetryTimezones' => ['UTC' => 'UTC'] + get_timezone_selector(),
        'telemetryTimezone' => $timezone->getName(),
        'telemetryGroups' => ['collection' => 'Collecte', 'activity' => 'Longues périodes d’activité', 'automation' => 'Actions répétées', 'pushing' => 'Échanges de ressources', 'storage' => 'Conservation', 'advanced' => 'Réglages avancés'],
    ]);
    $template->show('TelemetryPage.tpl');
}
