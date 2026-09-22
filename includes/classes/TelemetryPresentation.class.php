<?php

final class TelemetryPresentation
{
    public function __construct(private DateTimeZone $timezone)
    {
    }

    public function date(int $at): string
    {
        return (new DateTimeImmutable('@' . $at))->setTimezone($this->timezone)->format('d/m/Y H:i:s T');
    }

    public static function duration(int $seconds): string
    {
        $parts = [];
        foreach ([86400 => 'j', 3600 => 'h', 60 => 'min', 1 => 's'] as $unit => $label) {
            $value = intdiv($seconds, $unit);
            if ($value > 0) {
                $parts[] = $value . ' ' . $label;
            }
            $seconds %= $unit;
        }
        return $parts ? implode(' ', $parts) : '0 s';
    }

    public static function setting(string $key, $value): string
    {
        $unit = TelemetrySettings::definitions()[$key][4] ?? '';
        if ($unit === '0/1' || $key === 'enabled') {
            return $value ? 'Activé' : 'Désactivé';
        }
        return $unit === 'fraction' ? round($value * 100, 3) . ' %' : trim($value . ' ' . $unit);
    }

    public function activity(array $daily, int $from, int $to): array
    {
        $start = (new DateTimeImmutable('@' . $from))->setTimezone($this->timezone)->setTime(0, 0);
        $end = (new DateTimeImmutable('@' . $to))->setTimezone($this->timezone);
        $windows = TelemetryActivity::windows($daily);
        $days = [];
        $total = 0;
        $activeDays = 0;
        for ($date = $start; $date <= $end; $date = $date->modify('+1 day')) {
            $intervals = TelemetryActivity::intervals($windows, max($from, $date->getTimestamp()), min($to + 60, $date->modify('+1 day')->getTimestamp()));
            $day = ['day' => $date->format('d/m/Y'), 'slots' => [], 'windows' => [], 'seconds' => 0];
            foreach ($intervals as [$first, $last]) {
                $a = (new DateTimeImmutable('@' . $first))->setTimezone($this->timezone);
                $b = (new DateTimeImmutable('@' . $last))->setTimezone($this->timezone);
                $day['seconds'] += $last - $first;
                $label = $this->date($first) . ' – ' . $this->date($last);
                $day['windows'][] = ['range' => $a->format('H:i:s') . ' – ' . $b->format('H:i:s'), 'duration' => self::duration($last - $first)];
                for ($at = intdiv($first, 300) * 300; $at < $last; $at += 300) {
                    $local = (new DateTimeImmutable('@' . $at))->setTimezone($this->timezone);
                    $slot = (int) $local->format('G') * 12 + intdiv((int) $local->format('i'), 5);
                    $day['slots'][$slot] = ['minute' => $slot, 'date' => isset($day['slots'][$slot]) ? $day['slots'][$slot]['date'] . ' / ' . $label : $label];
                }
            }
            $total += $day['seconds'];
            $activeDays += (int) ($day['seconds'] > 0);
            $day['duration'] = self::duration($day['seconds']);
            $days[$date->format('Y-m-d')] = $day;
        }
        $last = null;
        foreach ($windows as [$first, $at]) {
            if ($at >= $from && $at <= $to) {
                $last = max($last ?? 0, $at);
            }
        }
        $average = (int) round($total / max(1, count($days)));
        return [
            'days' => array_reverse($days, true),
            'active_days' => $activeDays,
            'total_seconds' => $total,
            'total' => self::duration($total),
            'average_seconds' => $average,
            'average' => self::duration($average),
            'last_activity' => $last === null ? '—' : $this->date($last),
        ];
    }

    public static function label(string $key): string
    {
        $labels = [
            'enabled' => 'Collecte',
            'availability' => 'Amplitude d’activité',
            'timing' => 'Rythme régulier',
            'workflow' => 'Séquence répétée',
            'polling' => 'Consultations répétées',
            'traversal' => 'Parcours de systèmes',
            'weak' => 'Faible',
            'moderate' => 'Modérée',
            'uncertain' => 'Incertaine',
            'pending' => 'Remboursement attendu',
            'open' => 'À examiner',
            'follow_up' => 'À suivre',
            'dismissed' => 'Classé',
            'interaction' => 'Navigation',
            'client' => 'Client déclaré',
            'login' => 'Connexion',
            'fleet.send' => 'Envoi de flotte',
            'fleet.recall' => 'Rappel de flotte',
            'queue.buildings' => 'Construction',
            'queue.research' => 'Recherche',
            'queue.shipyard' => 'Chantier spatial',
            'account.officer' => 'Recrutement',
            'account.settings' => 'Réglages du compte',
            'galaxy.view' => 'Consultation de galaxie',
            'delivery' => 'Livraison',
            'combat' => 'Combat',
            'days' => 'Jours',
            'day' => 'Jour',
            'active_seconds' => 'Durée active estimée',
            'windows' => 'Fenêtres d’activité',
            'largest_gap_seconds' => 'Plus grand intervalle',
            'short_gap_days' => 'Jours aux intervalles courts',
            'required_days' => 'Jours requis',
            'share' => 'Proportion',
            'period' => 'Intervalles par motif',
            'seconds' => 'Durées',
            'observations' => 'Observations',
            'repeats' => 'Répétitions',
            'pattern' => 'Séquence',
            'starts' => 'Débuts des répétitions',
            'checks' => 'Consultations',
            'span_seconds' => 'Période couverte',
            'consecutive_systems' => 'Systèmes consécutifs',
            'sender' => 'Expéditeur',
            'recipient' => 'Destinataire',
            'sent' => 'Ressources envoyées',
            'overdue_sent' => 'Envois arrivés à échéance',
            'returned' => 'Ressources reçues en retour',
            'balance' => 'Solde arrivé à échéance',
            'total_balance' => 'Solde total',
            'remaining' => 'Bénéfice après tolérance (équivalent deutérium)',
            'sent_value' => 'Valeur envoyée',
            'returned_value' => 'Valeur reçue en retour',
            'rate' => 'Taux retenu M:C:D',
            'minimum' => 'Bénéfice minimum',
            'awaiting_repayment' => 'Remboursement attendu',
            'deadline' => 'Échéance',
            'allowed_rates' => 'Taux autorisés M:C:D',
            'allowance' => 'Tolérance',
            'metal' => 'Métal',
            'crystal' => 'Cristal',
            'deuterium' => 'Deutérium',
            'planet' => 'Planète',
            'galaxy' => 'Galaxie',
            'system' => 'Système',
            'mission' => 'Mission',
            'command' => 'Commande',
            'moon_chance' => 'Chance de lune (%)',
            'building' => 'Bâtiment',
            'research' => 'Recherche',
            'before' => 'Avant',
            'after' => 'Après',
            'note' => 'Note',
            'ip' => 'Adresse source',
            'write_failed' => 'Échec d’enregistrement',
            'buffer_failed' => 'Échec de préparation',
            'request_limit' => 'Limite d’événements par requête',
            'disabled' => 'Collecte désactivée',
            'events_disabled' => 'Historique détaillé désactivé',
            'storage_events' => 'Stockage : événements suspendus',
            'retention' => 'Historique supprimé par rétention',
            'health_unavailable' => 'État de collecte inaccessible',
        ];
        if (str_starts_with($key, 'pushing.')) {
            return 'Échange unilatéral';
        }
        return $labels[$key] ?? TelemetrySettings::definitions()[$key][5] ?? $key;
    }

    public function exchange(array $metrics): ?array
    {
        if (!isset($metrics['sent'], $metrics['balance'], $metrics['total_balance'])) {
            return null;
        }
        $resources = [];
        foreach (['sent' => 'Envoyé', 'returned' => 'Reçu en retour', 'overdue_sent' => 'Dont arrivé à échéance'] as $key => $label) {
            if ($key === 'overdue_sent' && $metrics[$key] == $metrics['sent']) {
                continue;
            }
            $row = ['label' => $label];
            foreach (['metal', 'crystal', 'deuterium'] as $resource) {
                $row[$resource] = pretty_number($metrics[$key][$resource] ?? 0);
            }
            $resources[] = $row;
        }
        $pending = !empty($metrics['awaiting_repayment']);
        $balance = $metrics[$pending ? 'total_balance' : 'balance'];
        return [
            'sender' => (int) $metrics['sender'],
            'recipient' => (int) $metrics['recipient'],
            'resources' => $resources,
            'summary' => [
                ['label' => $pending ? 'Bénéfice en attente après tolérance' : 'Bénéfice non remboursé après tolérance', 'value' => pretty_number($balance['remaining']) . ' équiv. deut.'],
                ['label' => 'Seuil de signalement', 'value' => pretty_number($metrics['minimum']) . ' équiv. deut.'],
                ['label' => 'Tolérance', 'value' => round($metrics['allowance'] * 100, 3) . ' %'],
                ['label' => 'Taux retenu M:C:D', 'value' => implode(':', $balance['rate'])],
                ['label' => 'Échéance', 'value' => $this->date((int) $metrics['deadline'])],
            ],
        ];
    }

    public function rows(array $data, string $prefix = '', bool $dates = false): array
    {
        $rows = [];
        foreach ($data as $key => $value) {
            // Combat details are already in the timeline.
            if ($key === 'combat_context' || $key === 'slots') {
                continue;
            }
            $label = $prefix . ($prefix ? ' / ' : '') . (is_int($key) ? (string) ($key + 1) : self::label($key));
            if ($key === 'windows') {
                foreach ($value as [$first, $last]) {
                    $rows[] = ['label' => $label, 'value' => $this->date($first) . ' – ' . $this->date($last)];
                }
                continue;
            }
            if (is_array($value)) {
                if ($key === 'seconds') {
                    $value = implode(' → ', array_map([self::class, 'duration'], $value));
                } elseif ($key === 'rate') {
                    $value = implode(':', $value);
                } elseif ($key === 'pattern') {
                    $value = implode(' → ', array_map(static fn($v) => is_string($v) ? self::label(rtrim($v, ':')) : $v, $value));
                } else {
                    $rows = array_merge($rows, $this->rows($value, $label, $key === 'starts'));
                    continue;
                }
            }
            if (in_array($key, ['active_seconds', 'largest_gap_seconds', 'span_seconds'], true) && is_numeric($value)) {
                $value = self::duration((int) $value);
            } elseif (($dates || in_array($key, ['deadline'], true)) && $value) {
                $value = $this->date((int) $value);
            } elseif (is_bool($value)) {
                $value = $value ? 'Oui' : 'Non';
            } elseif ($value === null) {
                $value = '—';
            } elseif (in_array($key, ['share', 'allowance'], true) && is_numeric($value)) {
                $value = round($value * 100, 3) . ' %';
            } elseif (in_array($key, ['metal', 'crystal', 'deuterium', 'remaining', 'minimum', 'sent_value', 'returned_value', 'observations', 'repeats', 'checks'], true) && is_numeric($value)) {
                $value = pretty_number($value);
            } elseif (is_float($value)) {
                $value = round($value, 3);
            }
            $rows[] = ['label' => $label, 'value' => $value];
        }
        return $rows;
    }

    public function timeline(array $events): array
    {
        usort($events, static fn($a, $b) => $a['at'] <=> $b['at']);
        foreach ($events as &$event) {
            $event['date'] = $this->date((int) $event['at']);
            $event['label'] = self::label($event['kind'] ?? 'availability');
            $data = $event['data'] ?? array_intersect_key($event, array_flip(['active_seconds', 'largest_gap_seconds']));
            if (!empty($event['ip'])) {
                $data['ip'] = $event['ip'];
            }
            $event['details'] = $this->rows($data);
        }
        return $events;
    }

    public function gaps(array $gaps): array
    {
        foreach ($gaps as &$gap) {
            $gap['start'] = $this->date((int) $gap['from']);
            $gap['end'] = $this->date((int) $gap['to']);
            $gap['label'] = self::label($gap['reason']);
        }
        return $gaps;
    }
}
