<?php

/**
 * Risistar - Functional Test & Scenario Manager
 */

require_once __DIR__ . '/ScenarioInterface.php';
require_once __DIR__ . '/UniverseSeeder.php';
require_once __DIR__ . '/Scenarios/AttackScenario.php';
require_once __DIR__ . '/Scenarios/AcsAttackScenario.php';
require_once __DIR__ . '/Scenarios/AcsDefenseScenario.php';
require_once __DIR__ . '/Scenarios/MoonDestructionScenario.php';
require_once __DIR__ . '/Scenarios/FlightCombinationsScenario.php';
require_once __DIR__ . '/Scenarios/AllScenarios.php';
require_once __DIR__ . '/Scenarios/MoonDestructionEdgeCasesScenario.php';
require_once __DIR__ . '/Scenarios/ExpeditionScenario.php';
require_once __DIR__ . '/Scenarios/MipScenario.php';
require_once __DIR__ . '/Scenarios/SpyScenario.php';
require_once __DIR__ . '/Scenarios/TelemetryScenario.php';
require_once __DIR__ . '/Scenarios/TelemetrySizeScenario.php';

class ScenarioManager
{
    /** @var array<string, ScenarioInterface> */
    private static array $scenarios = [];

    public static function init(): void
    {
        self::register(new AllScenarios());
        self::register(new AttackScenario());
        self::register(new ExpeditionScenario());
        self::register(new MipScenario());
        self::register(new SpyScenario());
        self::register(new AcsAttackScenario());
        self::register(new AcsDefenseScenario());
        self::register(new MoonDestructionScenario());
        self::register(new FlightCombinationsScenario());
        self::register(new MoonDestructionEdgeCasesScenario());
        self::register(new TelemetryScenario());
        self::register(new TelemetrySizeScenario());
    }

    public static function register(ScenarioInterface $scenario): void
    {
        self::$scenarios[$scenario->getKey()] = $scenario;
    }

    public static function ensureShips(int $planetId, array $ships): void
    {
        global $resource;
        $db = Database::get();
        $updates = [];
        $params = [':planetId' => $planetId];
        foreach ($ships as $shipId => $qty) {
            if (isset($resource[$shipId])) {
                $col = $resource[$shipId];
                $updates[] = "`{$col}` = `{$col}` + :qty_{$shipId}";
                $params[":qty_{$shipId}"] = (float)$qty;
            }
        }
        if (!empty($updates)) {
            $db->update("UPDATE %%PLANETS%% SET " . implode(', ', $updates) . " WHERE id = :planetId;", $params);
        }
    }

    public static function printHelp(): void
    {
        echo "=================================================================\n";
        echo "🚀 Risistar - Suite de Scénarios Fonctionnels & Peupleur d'Univers\n";
        echo "=================================================================\n\n";
        echo "UTILISATION :\n";
        echo "  php populate_universe.php [OPTIONS]\n\n";
        echo "OPTIONS DE SEEDING GLOBAL :\n";
        echo "  --reset                 Réinitialise complètement la BDD et re-peuple l'univers.\n\n";
        echo "OPTIONS D'INJECTION DE SCÉNARIOS (--scenario=NAME) :\n";
        echo "  --scenario=NAME         Exécute un scénario fonctionnel (ex: attack, expedition, mip, spy, all).\n";
        echo "  --direction=in|out      Sens de l'attaque pour attack/mip/spy : 'incoming' (défaut) ou 'outgoing'.\n";
        echo "  --user=NAME             Utilisateur cible (défaut: 'Admin').\n";
        echo "  --eta=SECONDS           Délai avant arrivée de la première flotte en secondes (défaut: 5).\n";
        echo "  --interval=SECONDS      Intervalle de temps entre chaque vague/scénario en secondes (défaut: 3 pour expéditions, 5 pour raids).\n";
        echo "  --count=NUMBER          Nombre de flottes à générer (défaut: 10 pour expéditions, 3 pour raids).\n";
        echo "  --target_type=planet|moon Type de destination (défaut: 'planet').\n\n";
        echo "LISTE DES SCÉNARIOS DISPONIBLES :\n";

        foreach (self::$scenarios as $key => $scenario) {
            echo sprintf("  %-30s : %s\n", $key, $scenario->getName());
            echo sprintf("  %-30s   %s\n\n", '', $scenario->getDescription());
        }
    }

    public static function handleRequest(array $args, bool $isCli): void
    {
        self::init();

        // Parser les arguments
        $options = [];
        foreach ($args as $arg) {
            if (strpos($arg, '--') === 0) {
                $pair = explode('=', substr($arg, 2), 2);
                $key = $pair[0];
                $val = $pair[1] ?? true;
                $options[$key] = $val;
            }
        }

        // Si --help ou pas d'argument précis
        if (isset($options['help']) || isset($options['h'])) {
            self::printHelp();
            return;
        }

        // Exécution d'un scénario spécifique
        if (!empty($options['scenario'])) {
            $scenarioKey = (string)$options['scenario'];

            if (!isset(self::$scenarios[$scenarioKey])) {
                echo "[ERROR] Scénario inconnu : '{$scenarioKey}'.\n";
                echo "Exécutez 'php populate_universe.php --help' pour voir la liste des scénarios disponibles.\n";
                return;
            }

            $scenario = self::$scenarios[$scenarioKey];
            echo "🎬 Lancement du scénario : " . $scenario->getName() . "\n";
            $scenario->execute($options, $isCli);
            return;
        }

        // Seeding par défaut ou reset
        $doReset = isset($options['reset']) || in_array('--reset', $args) || in_array('reset', $args);
        UniverseSeeder::seed($doReset, $isCli);
    }
}
