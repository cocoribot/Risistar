<?php

/**
 * Risistar - Universe Seeder Class
 * 
 * S'occupe de la réinitialisation et du peuplement global de l'univers de jeu
 * avec les comptes originaux du script (Admin, PlayerOne, LordVader, etc. | MDP: password123).
 */

class UniverseSeeder
{
    private static function outputLog(string $msg, string $type = 'info', bool $isCli = true): void
    {
        if ($isCli) {
            $prefix = ($type === 'success') ? '[SUCCESS] ' : (($type === 'warn') ? '[WARN] ' : '[INFO] ');
            echo $prefix . strip_tags($msg) . "\n";
        } else {
            $colorClass = ($type === 'success') ? 'log-success' : (($type === 'warn') ? 'log-warn' : 'log-info');
            echo '<div class="log-entry ' . $colorClass . '">' . $msg . '</div>';
            flush();
        }
    }

    private static function getFreeCoords(array &$occupiedCoords, int $maxGalaxy, int $maxSystem): array
    {
        for ($i = 0; $i < 1000; $i++) {
            $g = mt_rand(1, min(3, $maxGalaxy));
            $s = mt_rand(1, min(50, $maxSystem));
            $p = mt_rand(1, 12);
            $key = "{$g}:{$s}:{$p}";

            if (!isset($occupiedCoords[$key])) {
                $occupiedCoords[$key] = true;
                return [$g, $s, $p];
            }
        }
        return [1, 1, mt_rand(1, 15)];
    }

    public static function seed(bool $doReset = false, bool $isCli = true): void
    {
        global $resource;

        $db = Database::get();
        $config = Config::get(1);

        self::outputLog("🚀 Début de l'initialisation du monde Risistar...", 'info', $isCli);

        // 1. Nettoyage si réinitialisation demandée
        if ($doReset) {
            self::outputLog("🧹 Réinitialisation de la base de données (purge complète)...", 'warn', $isCli);

            $db->nativeQuery("TRUNCATE TABLE " . DB_PREFIX . "users;");
            $db->nativeQuery("TRUNCATE TABLE " . DB_PREFIX . "planets;");
            $db->nativeQuery("TRUNCATE TABLE " . DB_PREFIX . "alliance;");
            $db->nativeQuery("TRUNCATE TABLE " . DB_PREFIX . "fleets;");
            $db->nativeQuery("TRUNCATE TABLE " . DB_PREFIX . "fleet_event;");
            $db->nativeQuery("TRUNCATE TABLE " . DB_PREFIX . "log_fleets;");
            $db->nativeQuery("TRUNCATE TABLE " . DB_PREFIX . "statpoints;");
            $db->nativeQuery("TRUNCATE TABLE " . DB_PREFIX . "messages;");
            $db->nativeQuery("TRUNCATE TABLE " . DB_PREFIX . "aks;");
            $db->nativeQuery("TRUNCATE TABLE " . DB_PREFIX . "users_to_acs;");

            self::outputLog("  Tables vidées avec succès.", 'success', $isCli);
        }

        // 2. Définition des Alliances d'origine
        $alliancesData = [
            ['name' => 'Imperium Galactique', 'tag' => 'IMP', 'desc' => 'Domination et ordre.'],
            ['name' => 'Alliance des Etoiles', 'tag' => 'ADE', 'desc' => 'Explorateurs et scientifiques.'],
            ['name' => 'Pirates du Cosmos', 'tag' => 'PIR', 'desc' => 'Attaques surprises et pillages.'],
            ['name' => 'Risistar Vanguard', 'tag' => 'RIS', 'desc' => 'Force industrielle dominante.'],
        ];

        $createdAlliances = [];
        foreach ($alliancesData as $aIdx => $aData) {
            $existingAlly = $db->selectSingle("SELECT id FROM %%ALLIANCE%% WHERE ally_tag = :tag;", [':tag' => $aData['tag']]);
            if (!empty($existingAlly)) {
                $createdAlliances[$aIdx] = $existingAlly['id'];
            } else {
                $db->insert("INSERT INTO %%ALLIANCE%% SET
                    ally_name = :name,
                    ally_tag = :tag,
                    ally_owner = 0,
                    ally_register_time = :time,
                    ally_universe = 1,
                    ally_members = 1;", [
                    ':name' => $aData['name'],
                    ':tag' => $aData['tag'],
                    ':time' => TIMESTAMP,
                ]);
                $createdAlliances[$aIdx] = $db->lastInsertId();
                self::outputLog("🛡️ Alliance '{$aData['name']}' [{$aData['tag']}] créée (ID: {$createdAlliances[$aIdx]}).", 'success', $isCli);
            }
        }

        // 3. Définition des 15 comptes Joueurs d'origine (Mot de passe universel : password123)
        $playersConfig = [
            ['name' => 'Admin', 'tier' => 1, 'authlevel' => 3, 'ally_index' => 0, 'colonies_count' => 3, 'moons_count' => 2, 'darkmatter' => 1000000],
            ['name' => 'PlayerOne', 'tier' => 1, 'authlevel' => 0, 'ally_index' => 1, 'colonies_count' => 3, 'moons_count' => 2, 'darkmatter' => 750000],
            ['name' => 'LordVader', 'tier' => 1, 'authlevel' => 0, 'ally_index' => 0, 'colonies_count' => 3, 'moons_count' => 2, 'darkmatter' => 500000],
            ['name' => 'Starlight', 'tier' => 1, 'authlevel' => 0, 'ally_index' => 1, 'colonies_count' => 3, 'moons_count' => 2, 'darkmatter' => 500000],
            ['name' => 'CyberCommander', 'tier' => 1, 'authlevel' => 0, 'ally_index' => 2, 'colonies_count' => 2, 'moons_count' => 1, 'darkmatter' => 450000],
            ['name' => 'Hyperion', 'tier' => 1, 'authlevel' => 0, 'ally_index' => 3, 'colonies_count' => 3, 'moons_count' => 1, 'darkmatter' => 400000],
            ['name' => 'NovaEmpress', 'tier' => 1, 'authlevel' => 0, 'ally_index' => 0, 'colonies_count' => 2, 'moons_count' => 1, 'darkmatter' => 350000],
            ['name' => 'Astraea', 'tier' => 2, 'authlevel' => 0, 'ally_index' => 1, 'colonies_count' => 2, 'moons_count' => 1, 'darkmatter' => 200000],
            ['name' => 'Vortex', 'tier' => 2, 'authlevel' => 0, 'ally_index' => 2, 'colonies_count' => 1, 'moons_count' => 1, 'darkmatter' => 180000],
            ['name' => 'Apollo', 'tier' => 2, 'authlevel' => 0, 'ally_index' => 3, 'colonies_count' => 2, 'moons_count' => 0, 'darkmatter' => 150000],
            ['name' => 'OrionRider', 'tier' => 2, 'authlevel' => 0, 'ally_index' => 0, 'colonies_count' => 1, 'moons_count' => 0, 'darkmatter' => 120000],
            ['name' => 'SolarWind', 'tier' => 2, 'authlevel' => 0, 'ally_index' => 1, 'colonies_count' => 1, 'moons_count' => 0, 'darkmatter' => 100000],
            ['name' => 'NebulaSeeker', 'tier' => 2, 'authlevel' => 0, 'ally_index' => 2, 'colonies_count' => 1, 'moons_count' => 0, 'darkmatter' => 90000],
            ['name' => 'AsteroidMiner', 'tier' => 2, 'authlevel' => 0, 'ally_index' => 3, 'colonies_count' => 1, 'moons_count' => 0, 'darkmatter' => 80000],
            ['name' => 'Zeus', 'tier' => 2, 'authlevel' => 0, 'ally_index' => 0, 'colonies_count' => 1, 'moons_count' => 0, 'darkmatter' => 75000],
        ];

        // Charger positions déjà occupées
        $occupiedCoords = [];
        $existingPlanets = $db->select("SELECT galaxy, `system`, planet FROM %%PLANETS%%;");
        foreach ($existingPlanets as $ep) {
            $occupiedCoords["{$ep['galaxy']}:{$ep['system']}:{$ep['planet']}"] = true;
        }

        foreach ($playersConfig as $pConfig) {
            $existingUser = $db->selectSingle("SELECT id FROM %%USERS%% WHERE username = :name;", [
                ':name' => $pConfig['name']
            ]);

            if (!empty($existingUser)) {
                $userId = $existingUser['id'];
                self::outputLog("👤 Joueur '{$pConfig['name']}' déjà existant (User ID: {$userId}).", 'info', $isCli);
            } else {
                list($g, $s, $p) = self::getFreeCoords($occupiedCoords, $config->max_galaxy, $config->max_system);

                $authLevel = $pConfig['authlevel'] ?? 0;
                $userEmail = strtolower($pConfig['name']) . '@risistar.local';

                list($userId, $planetId) = PlayerUtil::createPlayer(
                    1, // Universe
                    $pConfig['name'],
                    PlayerUtil::cryptPassword('password123'),
                    $userEmail,
                    'fr',
                    $g,
                    $s,
                    $p,
                    'Planète Principale',
                    $authLevel
                );
                self::outputLog("👤 Joueur '{$pConfig['name']}' créé à {$g}:{$s}:{$p} (User ID: {$userId}, Planet ID: {$planetId}).", 'success', $isCli);

                // Alliance
                if ($pConfig['ally_index'] !== null && isset($createdAlliances[$pConfig['ally_index']])) {
                    $allyId = $createdAlliances[$pConfig['ally_index']];
                    $db->update("UPDATE %%USERS%% SET ally_id = :allyId WHERE id = :userId;", [
                        ':allyId' => $allyId,
                        ':userId' => $userId,
                    ]);
                    $allyOwner = $db->selectSingle("SELECT ally_owner FROM %%ALLIANCE%% WHERE id = :allyId;", [':allyId' => $allyId], 'ally_owner');
                    if ($allyOwner == 0) {
                        $db->update("UPDATE %%ALLIANCE%% SET ally_owner = :ownerId WHERE id = :allyId;", [
                            ':ownerId' => $userId,
                            ':allyId' => $allyId,
                        ]);
                    }
                }

                // Techs
                $tier = $pConfig['tier'];
                $techLevels = ($tier == 1) ? [
                    106 => 12, 108 => 12, 109 => 15, 110 => 14, 111 => 15, 113 => 14,
                    114 => 10, 115 => 14, 117 => 12, 118 => 10, 120 => 14, 121 => 10, 122 => 10, 124 => 15, 199 => 1
                ] : (($tier == 2) ? [
                    106 => 8, 108 => 9, 109 => 10, 110 => 9, 111 => 10, 113 => 10,
                    114 => 6, 115 => 10, 117 => 8, 118 => 6, 120 => 10, 121 => 6, 122 => 5, 124 => 9
                ] : [
                    106 => 4, 108 => 5, 109 => 5, 110 => 5, 111 => 5, 113 => 6, 115 => 6, 117 => 4, 120 => 6, 124 => 3
                ]);

                $userUpdates = ['darkmatter = :darkmatter'];
                $userParams = [':darkmatter' => $pConfig['darkmatter'], ':userId' => $userId];
                foreach ($techLevels as $techId => $lvl) {
                    if (isset($resource[$techId])) {
                        $col = $resource[$techId];
                        $userUpdates[] = "`{$col}` = :tech_{$techId}";
                        $userParams[":tech_{$techId}"] = $lvl;
                    }
                }
                $db->update("UPDATE %%USERS%% SET " . implode(', ', $userUpdates) . " WHERE id = :userId;", $userParams);

                // Planètes & colonies
                $playerPlanets = [];
                $mainPlanetData = $db->selectSingle("SELECT id, galaxy, `system`, planet FROM %%PLANETS%% WHERE id = :planetId;", [':planetId' => $planetId]);
                $playerPlanets[] = [
                    'id' => $planetId,
                    'galaxy' => $mainPlanetData['galaxy'],
                    'system' => $mainPlanetData['system'],
                    'planet' => $mainPlanetData['planet'],
                    'isHome' => true,
                ];

                for ($c = 0; $c < $pConfig['colonies_count']; $c++) {
                    list($cg, $cs, $cp) = self::getFreeCoords($occupiedCoords, $config->max_galaxy, $config->max_system);
                    try {
                        $colonyId = PlayerUtil::createPlanet($cg, $cs, $cp, 1, $userId, 'Colonie ' . ($c + 1), false, 0);
                        $playerPlanets[] = [
                            'id' => $colonyId,
                            'galaxy' => $cg,
                            'system' => $cs,
                            'planet' => $cp,
                            'isHome' => false,
                        ];
                    } catch (Exception $e) {}
                }

                // Equipement planètes
                $moonsCreatedCount = 0;
                foreach ($playerPlanets as $pInfo) {
                    $pId = $pInfo['id'];
                    $resourcesData = ($tier == 1) ? ['metal' => 2000000, 'crystal' => 1000000, 'deuterium' => 500000]
                        : (($tier == 2) ? ['metal' => 500000, 'crystal' => 250000, 'deuterium' => 100000]
                        : ['metal' => 50000, 'crystal' => 25000, 'deuterium' => 10000]);

                    $elements = ($tier == 1) ? [
                        1 => 32, 2 => 28, 3 => 25, 4 => 28, 12 => 8, 14 => 10, 15 => 4, 21 => 12, 22 => 10, 23 => 8, 24 => 8, 31 => 12,
                        202 => 800, 203 => 400, 204 => 3000, 205 => 1000, 206 => 500, 207 => 250, 208 => 5, 209 => 200, 210 => 150, 211 => 50, 212 => 100, 213 => 40, 214 => ($pInfo['isHome'] ? 2 : 0), 215 => 100, 219 => 5, 223 => 30000,
                        401 => 3000, 402 => 1500, 403 => 400, 404 => 100, 405 => 80, 406 => 30, 407 => 1, 408 => 1, 409 => 40, 410 => 15
                    ] : (($tier == 2) ? [
                        1 => 24, 2 => 20, 3 => 18, 4 => 22, 14 => 6, 15 => 2, 21 => 8, 22 => 7, 23 => 6, 24 => 5, 31 => 8,
                        202 => 200, 203 => 100, 204 => 600, 205 => 200, 206 => 100, 207 => 40, 208 => 2, 209 => 50, 210 => 50, 212 => 50, 215 => 20,
                        401 => 800, 402 => 400, 403 => 100, 404 => 25, 405 => 20, 406 => 5, 407 => 1, 408 => 1, 409 => 15
                    ] : [
                        1 => 15, 2 => 12, 3 => 10, 4 => 14, 14 => 4, 21 => 4, 22 => 4, 23 => 3, 24 => 2, 31 => 4,
                        202 => 30, 203 => 10, 204 => 80, 205 => 20, 209 => 10, 210 => 15, 212 => 20,
                        401 => 150, 402 => 60, 403 => 10, 407 => 1
                    ]);

                    $planetUpdates = ['`metal` = :metal', '`crystal` = :crystal', '`deuterium` = :deuterium'];
                    $planetParams = [
                        ':metal' => $resourcesData['metal'],
                        ':crystal' => $resourcesData['crystal'],
                        ':deuterium' => $resourcesData['deuterium'],
                        ':planetId' => $pId,
                    ];
                    foreach ($elements as $elemId => $val) {
                        if (isset($resource[$elemId])) {
                            $col = $resource[$elemId];
                            $planetUpdates[] = "`{$col}` = :elem_{$elemId}";
                            $planetParams[":elem_{$elemId}"] = $val;
                        }
                    }
                    $db->update("UPDATE %%PLANETS%% SET " . implode(', ', $planetUpdates) . " WHERE id = :planetId;", $planetParams);

                    // Créer lune
                    if ($moonsCreatedCount < $pConfig['moons_count']) {
                        try {
                            $moonId = PlayerUtil::createMoon(1, $pInfo['galaxy'], $pInfo['system'], $pInfo['planet'], $userId, 20, 8944, mb_substr('Lune de ' . $pConfig['name'], 0, 20));
                            if ($moonId !== false) {
                                $moonsCreatedCount++;
                                $moonElements = [
                                    41 => 4, 42 => 3, 43 => 1,
                                    204 => 500, 207 => 100, 213 => 20, 214 => 1, 210 => 50,
                                    401 => 1000, 402 => 500, 406 => 20, 408 => 1
                                ];
                                $moonUpdates = [];
                                $moonParams = [':moonId' => $moonId];
                                foreach ($moonElements as $elemId => $val) {
                                    if (isset($resource[$elemId])) {
                                        $col = $resource[$elemId];
                                        $moonUpdates[] = "`{$col}` = :m_elem_{$elemId}";
                                        $moonParams[":m_elem_{$elemId}"] = $val;
                                    }
                                }
                                if (!empty($moonUpdates)) {
                                    $db->update("UPDATE %%PLANETS%% SET " . implode(', ', $moonUpdates) . " WHERE id = :moonId;", $moonParams);
                                }
                                self::outputLog("    🌕 Lune créée sur {$pInfo['galaxy']}:{$pInfo['system']}:{$pInfo['planet']} (Moon ID: {$moonId}).", 'info', $isCli);
                            }
                        } catch (Exception $e) {}
                    }
                }
            }
        }

        // Mettre à jour classements
        $statBuilder = new statbuilder();
        $statBuilder->MakeStats();
        self::outputLog("📊 Statistiques et classements calculés.", 'success', $isCli);
        self::outputLog("✅ Seeding global terminé avec succès ! Mot de passe universel : password123", 'success', $isCli);
    }
}
