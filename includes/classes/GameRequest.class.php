<?php

/** One shutdown owner: economy, session, commit, then optional telemetry. */
final class GameRequest
{
	private static $economy;
	private static $session;
	private static bool $registered = false;

	public static function register(): void
	{
		if (!self::$registered) {
			self::$registered = true;
			register_shutdown_function([self::class, 'finish']);
		}
	}

	public static function economy(callable $save): void
	{
		self::register();
		self::$economy = $save;
	}

	public static function session(callable $save): void
	{
		self::register();
		self::$session = $save;
	}

	public static function finish(): void
	{
		$db = Database::get();
		try {
			if ($db->getTransactionDepth() > 1 || $db->wasRequestRolledBack()) {
				$db->rollBackAll();
			} else {
				if (self::$economy) {
					(self::$economy)();
				}
				if (self::$session) {
					(self::$session)();
				}
				if ($db->getTransactionDepth() === 1) {
					$db->commit();
				}
			}
		} catch (Throwable $error) {
			$db->rollBackAll();
			error_log('Game persistence failed: ' . $error->getMessage());
		}
		// No gameplay transaction or PHP session lock may remain here.
		if (session_status() === PHP_SESSION_ACTIVE) {
			session_write_close();
		}
		PlayerTelemetry::flush();
	}
}
