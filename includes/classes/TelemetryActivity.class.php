<?php

final class TelemetryActivity
{
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

    /** Estimated intervals clipped to [from, until), including across UTC midnight. */
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
}
