<?php

require_once ROOT_PATH . 'includes/classes/TelemetryDetectors.class.php';
require_once ROOT_PATH . 'includes/classes/TelemetryReview.class.php';
require_once ROOT_PATH . 'includes/classes/TelemetryPresentation.class.php';
function ShowTelemetryPage()
{
    global $USER;
    $session = Session::load();
    if (!TelemetryReview::allowed($USER, $session)) {
        http_response_code(403);
        throw new RuntimeException('Accès modérateur requis.');
    }
    $universe = (int) Universe::getEmulated();
    $now = time();
    // Universe selection saves and closes the session; persist the form token before review work.
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $session->telemetryToken ??= bin2hex(random_bytes(32));
    $token = $session->telemetryToken;
    $session->save();
    $message = '';
    $error = '';
    $warnings = [];
    $case = null;
    $account = [];
    $analysis = null;
    $settingsHistory = [];
    $status = $_GET['status'] ?? 'open';
    if (!in_array($status, ['open', 'follow_up', 'dismissed'], true)) {
        $status = 'open';
    }
    $s = TelemetrySettings::get($universe);
    try {
        $timezone = new DateTimeZone($USER['timezone'] ?: 'UTC');
    } catch (Exception $e) {
        $timezone = new DateTimeZone('UTC');
    }
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
                $review->saveSettings($universe, (int) $USER['id'], $_POST['settings'] ?? [], $now);
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
        $id = max(0, (int) ($_GET['id'] ?? $_POST['id'] ?? 0));
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
                    $definition = TelemetrySettings::definitions()[$name];
                    $thresholds[] = ['label' => $definition[5], 'value' => $value . ' ' . $definition[4]];
                }
                $case['evaluations'][] = [
                    'label' => $label,
                    'date' => $format($evidence['evaluated_at'] ?? $case['first_seen']),
                    'matches' => $evidence['matches'] ?? true,
                    'support' => array_map([TelemetryPresentation::class, 'label'], array_diff($evidence['supporting_checks'] ?? [], [$case['kind']])),
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
        $actor = max(0, (int) ($_GET['account'] ?? 0));
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
        if (!$id && !$actor) {
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
        $warnings = $store->query(
            'SELECT id,actor,other,kind,strength,explanation,observation_start,observation_end,status FROM telemetry_warnings WHERE universe=? AND status=? AND id<? ORDER BY id DESC LIMIT 51',
            [$universe, $status, $before]
        )->fetchAll(PDO::FETCH_ASSOC);
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
            $entry['changes'] = $view->rows(json_decode($entry['data'], true));
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
        $fields[$d[0]][] = [
            'key' => $key,
            'value' => $s[$key],
            'default' => $d[1],
            'min' => $d[2],
            'max' => $d[3],
            'unit' => $d[4],
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
        'telemetryNext' => count($warnings) > 50 ? $warnings[49]['id'] : 0,
        'telemetryCase' => $case,
        'telemetryAccount' => $account,
        'telemetryShowSettings' => ($_GET['view'] ?? '') === 'settings' || ($_POST['action'] ?? '') === 'settings',
        'telemetryMaxDays' => $s['daily_days'],
        'telemetryFields' => $fields,
        'telemetryToken' => $token,
        'telemetryAnalysis' => $analysis,
        'telemetryHealth' => $healthRows,
        'telemetryStatus' => $status,
        'telemetrySettingsHistory' => $settingsHistory,
        'telemetryGaps' => $view->gaps(TelemetryStore::interruptions($universe, $now - $s['daily_days'] * 86400, $now)),
        'telemetryMaster' => TelemetryConnection::masterEnabled(),
        'telemetryTimezone' => $timezone->getName(),
        'telemetryGroups' => ['activity' => 'Activité', 'automation' => 'Automatisation', 'pushing' => 'Échanges', 'storage' => 'Stockage'],
    ]);
    $template->show('TelemetryPage.tpl');
}
