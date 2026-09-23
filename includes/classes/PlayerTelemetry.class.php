<?php

final class PlayerTelemetry
{
	private const MAX_ACTIONS_PER_REQUEST = 256;
	private const WRITE_BATCH = 256;

	private static array $settings = [];
	private static array $ready = [];
	private static ?string $request = null;
	private static int $queued = 0;

	public static function enabled(int $universe): bool
	{
		try {
			if (!array_key_exists($universe, self::$settings)) {
				self::$settings[$universe] = TelemetrySettings::get($universe);
			}
			return !empty(self::$settings[$universe]['enabled']);
		} catch (Throwable $e) {
			return false;
		}
	}

	/**
	 * Sorts each page load by what it changes, and returns the page to remember in the session.
	 * The same page again or another planet keeps the planet activity (*) alive without playing.
	 * The alliance page shows fleets against every member, so any view of it is watching.
	 * Queue reloads are not activity time, but the marker comes from the browser, so they are
	 * still counted and checked. For the same reason a form or AJAX call is not an action by
	 * itself: real actions are recorded by the game code that runs them.
	 */
	public static function interaction(int $actor, int $universe, int $planet = 0, ?string $previous = null): ?string
	{
		$name = self::page();
		$page = $planet . ':' . $name;
		$kind = match (true) {
			in_array($name, ['buildings', 'research', 'overview', 'shipyard'], true)
				&& HTTP::_GP('passive_reload', '') === 'queue' => 'passive',
			$name === 'alliance' => 'alliance.view',
			$previous === null => 'interaction',
			$page === $previous => 'reload',
			explode(':', $previous)[0] !== (string)$planet => 'planet.switch',
			default => 'interaction',
		};
		self::record($actor, $universe, $kind, 0, 0, [], $kind !== 'passive');
		// Small AJAX calls do not replace the page the player is looking at.
		return HTTP::_GP('ajax', 0) ? $previous : $page;
	}

	/** Counts one game action of the current player. */
	public static function action(string $kind): void
	{
		global $USER;
		if (empty($USER['id']) || empty($USER['universe'])) {
			return;
		}
		self::record((int)$USER['id'], (int)$USER['universe'], $kind, 0, 0, [], true);
	}

	/**
	 * Showing the galaxy is free and updates the planet activity (*) like any page, so the same
	 * system again is only a reload. Returns the system to remember in the session.
	 */
	public static function galaxyView(int $galaxy, int $system, ?string $previous): string
	{
		$shown = $galaxy . ':' . $system;
		if ($shown !== $previous) {
			self::action('galaxy.view');
		}
		return $shown;
	}

	/**
	 * The page the game really opens, cleaned like in game.php, so other spellings of one page
	 * are the same page. Its tabs (mode) are ignored, and every unknown page is the error page.
	 */
	private static function page(): string
	{
		$page = str_replace(['_', '\\', '/', '.', "\0"], '', HTTP::_GP('page', 'overview'));
		return is_file(ROOT_PATH . 'includes/pages/game/Show' . ucwords($page) . 'Page.class.php') ? strtolower($page) : 'error';
	}

	public static function client(string $agent): string
	{
		$browser = match (true) {
			preg_match('~Edg(?:e|A|iOS)?/~', $agent) === 1 => 'Edge',
			str_contains($agent, 'OPR/') => 'Opera',
			str_contains($agent, 'Firefox/') || str_contains($agent, 'FxiOS/') => 'Firefox',
			str_contains($agent, 'Chrome/') || str_contains($agent, 'CriOS/') => 'Chrome',
			str_contains($agent, 'Safari/') => 'Safari',
			default => 'other',
		};
		$platform = match (true) {
			str_contains($agent, 'Android') => 'Android',
			str_contains($agent, 'iPhone') || str_contains($agent, 'iPad') => 'iOS',
			str_contains($agent, 'Windows') => 'Windows',
			str_contains($agent, 'Macintosh') => 'macOS',
			str_contains($agent, 'Linux') => 'Linux',
			default => 'other',
		};
		$device = match (true) {
			str_contains($agent, 'iPad') || ($platform === 'Android' && !str_contains($agent, 'Mobile')) => 'tablet',
			str_contains($agent, 'Mobile') || str_contains($agent, 'iPhone') => 'mobile',
			in_array($platform, ['Windows', 'macOS', 'Linux'], true) => 'desktop',
			default => 'unknown',
		};
		return $browser.' · '.$platform.' · '.$device;
	}

	public static function record(int $actor, int $universe, string $kind, int $target = 0,
		int $fleetId = 0, array $data = [], bool $interactive = false, ?int $at = null): void
	{
		if ($actor <= 0 || !self::enabled($universe)) {
			return;
		}
		try {
			$at ??= time();
			// A normal request records a few actions; ignore the rest instead of trusting the client.
			if ($interactive && ++self::$queued > self::MAX_ACTIONS_PER_REQUEST) {
				return;
			}
			self::$request ??= bin2hex(random_bytes(16));
			$ip = $_SERVER['REMOTE_ADDR'] ?? '';
			$agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
			$network = ($interactive || $kind === 'passive') && self::$settings[$universe]['network_enabled'];
			if ($fleetId > 0) {
				$data['fleet'] = $fleetId;
			}
			$event = [
				'request_id' => self::$request, 'universe' => $universe, 'actor' => $actor,
				'target' => $target, 'at' => $at, 'kind' => $kind, 'interactive' => $interactive,
				'ip' => $network && filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null,
				'client' => $network && $agent !== '' ? self::client(substr($agent, 0, 1024)) : null,
				'data' => $data,
			];
			// The callback only keeps the event in memory. SQL waits until all game locks are released.
			Database::get()->afterCommit(static function() use ($event) {
				self::$ready[] = $event;
			});
		} catch (Throwable $e) {
			TelemetryStore::health(['failure' => 'buffer_failed', 'failed_at' => time()]);
		}
	}

	public static function flush(): void
	{
		if (!self::$ready || Database::get()->inTransaction()) {
			return;
		}
		$events = self::$ready;
		self::$ready = [];
		$health = TelemetryStore::health();
		if (($health['retry_after'] ?? 0) > time()) {
			return;
		}
		if (!empty($health['suspended']) && TelemetryConnection::shared()) {
			return;
		}
		try {
			// Longer than a lock wait (1 to 2 s), so a busy row gives a lock error, not a lost connection.
			$store = new TelemetryStore(TelemetryConnection::open(5));
			foreach (array_chunk($events, self::WRITE_BATCH) as $batch) {
				if (!$store->write($batch)) {
					return;
				}
			}
			if (isset($health['failure']) || ($health['success'] ?? 0) <= time() - 60) {
				TelemetryStore::health(['success' => time(), 'retry_after' => null, 'failure' => null, 'failed_at' => null]);
			}
		} catch (Throwable $e) {
			// Another request of the same player held the row: only these counts are lost.
			if ($e instanceof PDOException && in_array($e->errorInfo[1] ?? null, [1205, 1213], true)) {
				return;
			}
			// Keep connection details and SQL out of the admin health record.
			TelemetryStore::health(['failure' => 'write_failed', 'failed_at' => time(), 'retry_after' => time() + 60]);
			error_log('Telemetry collection failed (' . get_class($e) . '). Gameplay already persisted.');
		}
	}
}
