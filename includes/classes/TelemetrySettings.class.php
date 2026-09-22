<?php

final class TelemetrySettings
{
    /** group, default, minimum, maximum, unit, label, explanation */
    public static function definitions(): array
    {
        return [
            'events_enabled' => ['collection', 1, 0, 1, '0/1', 'Enregistrer les actions détaillées', ''],
            'network_enabled' => ['collection', 1, 0, 1, '0/1', 'Enregistrer les IP et profils client', 'Navigateur, système et type d’appareil.'],
            'activity_days' => ['activity', 7, 2, 90, 'jours', 'Période analysée', 'Journées complètes en UTC.'],
            'activity_hours' => ['activity', 20, 1, 24, 'heures/jour', 'Durée active minimale par jour', ''],
            'activity_min_days' => ['activity', 4, 1, 90, 'jours', 'Jours requis pour un signal modéré', ''],
            'activity_gap_hours' => ['activity', 3.0, 0.25, 24, 'heures', 'Pause maximale considérée comme courte', ''],
            'automation_days' => ['automation', 7, 1, 30, 'jours', 'Période analysée', ''],
            'timing_min' => ['automation', 30, 8, 5000, 'actions', 'Actions avant analyse du rythme', ''],
            'timing_tolerance' => ['automation', 0.1, 0.01, 0.5, 'fraction', 'Variation tolérée entre les intervalles', ''],
            'timing_share' => ['automation', 0.8, 0.5, 1, 'fraction', 'Part minimale d’intervalles réguliers', ''],
            'workflow_min' => ['automation', 8, 3, 1000, 'répétitions', 'Répétitions minimales d’une séquence', ''],
            'workflow_share' => ['automation', 0.6, 0.2, 1, 'fraction', 'Part minimale d’actions dans la séquence', ''],
            'poll_min' => ['automation', 40, 5, 10000, 'consultations', 'Consultations minimales de la galaxie', ''],
            'poll_span_hours' => ['automation', 2.0, 0.1, 168, 'heures', 'Durée minimale des consultations', ''],
            'traversal_min' => ['automation', 12, 4, 1000, 'systèmes', 'Systèmes successifs parcourus', ''],
            'push_days' => ['pushing', 7, 3, 30, 'jours', 'Période analysée', ''],
            'repayment_hours' => ['pushing', 48, 1, 168, 'heures', 'Délai de remboursement', ''],
            'rate_metal_min' => ['pushing', 2, 1, 10, 'métal / deutérium', 'Taux métal minimum', ''],
            'rate_metal_max' => ['pushing', 4, 1, 10, 'métal / deutérium', 'Taux métal maximum', ''],
            'rate_crystal_min' => ['pushing', 1, 1, 10, 'cristal / deutérium', 'Taux cristal minimum', ''],
            'rate_crystal_max' => ['pushing', 2, 1, 10, 'cristal / deutérium', 'Taux cristal maximum', ''],
            'imbalance_allowance' => ['pushing', 0.25, 0, 0.75, 'fraction', 'Déséquilibre toléré', 'Part des ressources envoyées admise sans remboursement.'],
            'push_minimum' => ['pushing', 100000, 1, 1000000000000000.0, 'équivalent deutérium', 'Bénéfice minimum à signaler', 'Après prise en compte des remboursements et de la tolérance.'],
            'push_points_fraction' => ['pushing', 0.01, 0, 1, 'fraction', 'Seuil relatif aux points du bénéficiaire', 'Pourcentage des points convertis en ressources. Le plus élevé des deux seuils est retenu.'],
            'moon_context_hours' => ['pushing', 6.0, 0.5, 48, 'heures', 'Délai entre livraison et combat', 'Un combat proche peut indiquer une tentative de lune.'],
            'event_days' => ['storage', 7, 1, 30, 'jours', 'Conserver les actions détaillées', 'Doit couvrir les périodes d’analyse des séquences et des échanges.'],
            'daily_days' => ['storage', 90, 7, 180, 'jours', 'Conserver les fenêtres d’activité', ''],
            'closed_days' => ['storage', 90, 7, 365, 'jours', 'Conserver les signalements classés', 'Les signalements ouverts sont conservés.'],
            'budget_mb' => ['advanced', 1800, 100, 1900, 'Mo', 'Espace maximal de la base', 'Tables et index compris.'],
            'reserve_mb' => ['advanced', 200, 20, 500, 'Mo', 'Espace à garder libre', 'Les actions détaillées ne sont plus enregistrées lorsque cette réserve est atteinte.'],
            'cleanup_rows' => ['advanced', 1000, 100, 10000, 'lignes par table', 'Suppressions par passage de nettoyage', ''],
            'analysis_accounts' => ['advanced', 10, 1, 50, 'comptes', 'Comptes ou échanges analysés par passage', ''],
            'analysis_events' => ['advanced', 10000, 100, 50000, 'événements', 'Événements analysés par compte ou échange', ''],
        ];
    }

    public static function defaults(): array
    {
        return array_map(static fn($d) => $d[1], self::definitions());
    }

    public static function get(int $universe): array
    {
        $config = Config::get($universe);
        $saved = isset($config->telemetry_settings) ? json_decode($config->telemetry_settings, true) : [];
        $modules = explode(';', $config->moduls);
        return ['enabled' => (int) ($modules[MODULE_TELEMETRY] ?? 0)]
            + array_replace(self::defaults(), array_intersect_key(is_array($saved) ? $saved : [], self::defaults()));
    }

    public static function validate(array $input): array
    {
        $out = [];
        foreach (self::definitions() as $name => $d) {
            $value = $input[$name] ?? null;
            if (!is_scalar($value) || !is_numeric($value) || !is_finite((float) $value) || $value < $d[2] || $value > $d[3] || is_int($d[1]) && (float) $value != (int) $value) {
                $scale = $d[4] === 'fraction' ? 100 : 1;
                $unit = $scale === 100 ? '%' : $d[4];
                throw new InvalidArgumentException($d[5] . ' : valeur attendue entre ' . $d[2] * $scale . ' et ' . $d[3] * $scale . ' ' . $unit . '.');
            }
            $out[$name] = is_int($d[1]) ? (int) $value : (float) $value;
        }
        if ($out['activity_min_days'] > $out['activity_days']) {
            throw new InvalidArgumentException('Le nombre de jours requis dépasse la période analysée.');
        }
        if ($out['reserve_mb'] >= $out['budget_mb']) {
            throw new InvalidArgumentException('L’espace à garder libre doit être inférieur à l’espace maximal.');
        }
        if ($out['rate_metal_min'] > $out['rate_metal_max'] || $out['rate_crystal_min'] > $out['rate_crystal_max']) {
            throw new InvalidArgumentException('Les taux minimums ne peuvent pas dépasser les maximums.');
        }
        if ($out['repayment_hours'] >= $out['push_days'] * 24) {
            throw new InvalidArgumentException('La période d’analyse des échanges doit dépasser le délai de remboursement.');
        }
        if ($out['daily_days'] < $out['activity_days']) {
            throw new InvalidArgumentException('La conservation des fenêtres d’activité doit couvrir la période analysée.');
        }
        if ($out['event_days'] < max($out['automation_days'], $out['push_days'])) {
            throw new InvalidArgumentException('Conservez les actions détaillées au moins ' . max($out['automation_days'], $out['push_days']) . ' jours pour couvrir les analyses.');
        }
        return $out;
    }
}
