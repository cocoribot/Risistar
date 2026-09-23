<?php

final class TelemetryActivity
{
	// Gaps are counted in buckets 5 % wide, so a second of network delay does not matter.
	public const GAP_STEP = 1.05;
	// Longer pauses are breaks, not part of a rhythm.
	public const MAX_GAP = 3600;

	/** Store first/last interaction timestamps, before adding the final minute. */
	public static function merge(array $windows): array
	{
		usort($windows, static fn($a, $b) => $a[0] <=> $b[0]);
		$merged = [];
		foreach ($windows as [$first, $last]) {
			$index = count($merged) - 1;
			if ($index >= 0 && $first <= $merged[$index][1] + 300) {
				$merged[$index][1] = max($merged[$index][1], $last);
			} else {
				$merged[] = [$first, $last];
			}
		}
		return $merged;
	}

	public static function windows(array $daily): array
	{
		$windows = [];
		foreach ($daily as $row) {
			array_push($windows, ...json_decode($row['windows'], true, 512, JSON_THROW_ON_ERROR));
		}
		return self::merge($windows);
	}

	/** Active periods cut to [from, until), also across midnight UTC. */
	public static function intervals(array $windows, int $from, int $until): array
	{
		$intervals = [];
		foreach ($windows as [$first, $last]) {
			$start = max($from, $first);
			$end = min($until, $last + 60);
			if ($start < $end) {
				$intervals[] = [$start, $end];
			}
		}
		return $intervals;
	}

	/**
	 * Adds the gaps between these request times to the histogram. Returns it with the last time
	 * seen, and the shortest and longest wait before the requests of each hour (UTC).
	 */
	public static function addGaps(array $gaps, array $times, int $last): array
	{
		sort($times);
		$waits = [];
		foreach ($times as $at) {
			$gap = $at - $last;
			if ($last > 0 && $gap >= 1) {
				$hour = (int) gmdate('G', $at);
				$waits[$hour] = [min($waits[$hour][0] ?? $gap, $gap), max($waits[$hour][1] ?? $gap, $gap)];
				if ($gap < self::MAX_GAP) {
					$bucket = (int) round(log($gap) / log(self::GAP_STEP));
					$gaps[$bucket] = ($gaps[$bucket] ?? 0) + 1;
				}
			}
			$last = max($last, $at);
		}
		return [$gaps, $last, $waits];
	}

	/** Shortest of two waits, where 0 means no wait was seen. */
	public static function shortest(int $first, int $second): int
	{
		return $first && $second ? min($first, $second) : max($first, $second);
	}

	public static function gapSeconds(int $bucket): float
	{
		return self::GAP_STEP ** $bucket;
	}
}
