<?php

final class TelemetrySettings
{
    /** group, default, minimum, maximum, unit, label, explanation */
    public static function definitions(): array
    {
        return [
            'enabled' => ['storage', 0, 0, 1, '0/1', 'Collecte', 'Active la collecte dans la limite de l’interrupteur du déploiement.'],
            'events_enabled' => ['storage', 1, 0, 1, '0/1', 'Historique détaillé', 'Conserve les actions et les consultations individuelles.'],
            'network_enabled' => ['storage', 1, 0, 1, '0/1', 'Contexte réseau', 'Conserve l’adresse IP source et le navigateur, système et type d’appareil déclarés.'],
            'activity_days' => ['activity', 7, 2, 90, 'jours', 'Période d’activité', 'Fenêtre glissante de jours UTC terminés.'],
            'activity_hours' => ['activity', 20, 1, 24, 'heures/jour', 'Durée active estimée', 'Durée cumulée des fenêtres d’activité sur une journée.'],
            'activity_min_days' => ['activity', 4, 1, 90, 'jours', 'Jours requis', 'Nombre de jours atteignant la durée minimale.'],
            'activity_gap_hours' => ['activity', 3.0, 0.25, 24, 'heures', 'Pause observée', 'Sépare les longues amplitudes des journées aux petits intervalles observés.'],
            'automation_days' => ['automation', 7, 1, 30, 'jours', 'Période des séquences', 'Historique utilisé pour les rythmes, parcours et séquences.'],
            'timing_min' => ['automation', 30, 8, 5000, 'actions', 'Observations de rythme', 'Minimum avant de comparer les intervalles.'],
            'timing_tolerance' => ['automation', 0.1, 0.01, 0.5, 'fraction', 'Variation du rythme', 'Écart relatif admis dans une période répétée de 1 à 4 intervalles.'],
            'timing_share' => ['automation', 0.8, 0.5, 1, 'fraction', 'Rythmes concordants', 'Proportion minimale des intervalles concordants.'],
            'workflow_min' => ['automation', 8, 3, 1000, 'répétitions', 'Répétitions de la séquence', 'Séquences de 2 à 5 actions ; une action intercalée est admise.'],
            'workflow_share' => ['automation', 0.6, 0.2, 1, 'fraction', 'Part de la séquence', 'Part des actions couverte par des répétitions disjointes.'],
            'poll_min' => ['automation', 40, 5, 10000, 'consultations', 'Consultations répétées', 'Chaque requête est comptée, même si le contenu est inchangé.'],
            'poll_span_hours' => ['automation', 2.0, 0.1, 168, 'heures', 'Durée des consultations', 'Étendue minimale des consultations répétées.'],
            'traversal_min' => ['automation', 12, 4, 1000, 'systèmes', 'Parcours systématique', 'Nombre de passages consécutifs vers le système suivant ou précédent.'],
            'push_days' => ['pushing', 7, 3, 30, 'jours', 'Période des échanges', 'Fenêtre des livraisons ; les preuves déjà signalées restent conservées.'],
            'repayment_hours' => ['pushing', 48, 1, 168, 'heures', 'Délai de remboursement', 'Le remboursement partiel ne repousse pas l’échéance initiale.'],
            'rate_metal_min' => ['pushing', 2, 1, 10, 'métal / deutérium', 'Taux métal minimum', 'Borne incluse du corridor de taux autorisés.'],
            'rate_metal_max' => ['pushing', 4, 1, 10, 'métal / deutérium', 'Taux métal maximum', 'Borne incluse ; le taux le plus favorable à l’échange est retenu.'],
            'rate_crystal_min' => ['pushing', 1, 1, 10, 'cristal / deutérium', 'Taux cristal minimum', 'Les taux métal et cristal évoluent ensemble dans le corridor.'],
            'rate_crystal_max' => ['pushing', 2, 1, 10, 'cristal / deutérium', 'Taux cristal maximum', 'Corridor initial : 2:1:1 à 4:2:1.'],
            'imbalance_allowance' => ['pushing', 0.25, 0, 0.75, 'fraction', 'Tolérance', 'Part du bénéfice envoyé tolérée après valorisation favorable.'],
            'push_minimum' => ['pushing', 100000, 1, 1000000000000000.0, 'équivalent deutérium', 'Bénéfice minimum', 'Seul le bénéfice restant au-delà de la tolérance est comparé au seuil.'],
            'push_points_fraction' => ['pushing', 0.01, 0, 1, 'fraction', 'Seuil relatif aux points', 'Points du bénéficiaire × 1000 × cette fraction, valorisés au taux métal maximal.'],
            'moon_context_hours' => ['pushing', 6.0, 0.5, 48, 'heures', 'Contexte de combat', 'Combat proche de la livraison sur la planète concernée.'],
            'event_days' => ['storage', 7, 1, 30, 'jours', 'Historique récent', 'Rétention réduite automatiquement lorsque le stockage approche sa limite.'],
            'daily_days' => ['storage', 90, 7, 180, 'jours', 'Activité compacte', 'Conservation des fenêtres d’activité quotidiennes.'],
            'closed_days' => ['storage', 90, 7, 365, 'jours', 'Dossiers classés', 'Les dossiers ouverts et leurs preuves ne sont jamais purgés automatiquement.'],
            'budget_mb' => ['storage', 1800, 100, 1900, 'Mo', 'Budget de stockage', 'Allocation totale, index et espace libre inclus, sous la limite de l’hébergeur.'],
            'reserve_mb' => ['storage', 200, 20, 500, 'Mo', 'Réserve', 'Suspend les événements avant de consommer cette marge.'],
            'cleanup_rows' => ['storage', 1000, 100, 10000, 'lignes/table', 'Lot de nettoyage', 'Nombre maximal supprimé par table et par passage.'],
            'analysis_accounts' => ['storage', 10, 1, 50, 'comptes', 'Lot d’analyse', 'Nombre de comptes et de paires examinés à chaque passage.'],
            'analysis_events' => ['storage', 10000, 100, 50000, 'lignes/compte ou paire', 'Limite d’analyse', 'Une limite atteinte est affichée comme analyse incomplète.'],
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
        return array_replace(self::defaults(), is_array($saved) ? $saved : []);
    }

    public static function validate(array $input): array
    {
        $out = [];
        foreach (self::definitions() as $name => $d) {
            $value = $input[$name] ?? null;
            if (!is_scalar($value) || !is_numeric($value) || !is_finite((float) $value) || $value < $d[2] || $value > $d[3] || is_int($d[1]) && (float) $value != (int) $value) {
                throw new InvalidArgumentException($d[5] . ' : valeur invalide (' . $d[2] . '–' . $d[3] . ' ' . $d[4] . ').');
            }
            $out[$name] = is_int($d[1]) ? (int) $value : (float) $value;
        }
        if ($out['activity_min_days'] > $out['activity_days'] || $out['reserve_mb'] >= $out['budget_mb'] || $out['rate_metal_min'] > $out['rate_metal_max'] || $out['rate_crystal_min'] > $out['rate_crystal_max'] || $out['repayment_hours'] >= $out['push_days'] * 24 || $out['daily_days'] < $out['activity_days'] || $out['event_days'] < max($out['automation_days'], $out['push_days'])) {
            throw new InvalidArgumentException('Fenêtres, taux ou réserve incohérents. La rétention doit couvrir les analyses.');
        }
        return $out;
    }
}
