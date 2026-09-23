SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
--

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%aks`
--

CREATE TABLE `%PREFIX%aks` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `target` int(11) UNSIGNED NOT NULL,
  `ankunft` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%alliance`
--

CREATE TABLE `%PREFIX%alliance` (
  `id` int(11) UNSIGNED NOT NULL,
  `ally_name` varchar(50) DEFAULT '',
  `ally_tag` varchar(20) DEFAULT '',
  `ally_owner` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `ally_register_time` int(11) NOT NULL DEFAULT '0',
  `ally_description` text,
  `ally_web` varchar(255) DEFAULT '',
  `ally_text` text,
  `ally_image` varchar(255) DEFAULT '',
  `ally_request` varchar(1000) DEFAULT NULL,
  `ally_request_notallow` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `ally_request_min_points` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `ally_owner_range` varchar(32) DEFAULT '',
  `ally_members` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `ally_stats` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  `ally_diplo` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  `ally_universe` tinyint(3) UNSIGNED NOT NULL,
  `ally_max_members` int(5) UNSIGNED NOT NULL DEFAULT '20',
  `ally_events` varchar(55) NOT NULL DEFAULT '1,2,3,4,5,6,7,8,9,10,11,15'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%alliance_ranks`
--

CREATE TABLE `%PREFIX%alliance_ranks` (
  `rankID` int(11) NOT NULL,
  `rankName` varchar(32) NOT NULL,
  `allianceID` int(10) UNSIGNED NOT NULL,
  `MEMBERLIST` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `ONLINESTATE` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `TRANSFER` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `SEEAPPLY` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `MANAGEAPPLY` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `ROUNDMAIL` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `ADMIN` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `KICK` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `DIPLOMATIC` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `RANKS` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `MANAGEUSERS` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `EVENTS` tinyint(3) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%alliance_request`
--

CREATE TABLE `%PREFIX%alliance_request` (
  `applyID` int(10) UNSIGNED NOT NULL,
  `text` text NOT NULL,
  `userID` int(10) UNSIGNED NOT NULL,
  `allianceID` int(10) UNSIGNED NOT NULL,
  `time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%banned`
--

CREATE TABLE `%PREFIX%banned` (
  `id` int(11) UNSIGNED NOT NULL,
  `who` varchar(64) NOT NULL DEFAULT '',
  `theme` varchar(500) NOT NULL,
  `time` int(11) NOT NULL DEFAULT '0',
  `longer` int(11) NOT NULL DEFAULT '0',
  `author` varchar(64) NOT NULL DEFAULT '',
  `email` varchar(64) NOT NULL DEFAULT '',
  `universe` tinyint(3) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%buddy`
--

CREATE TABLE `%PREFIX%buddy` (
  `id` int(11) UNSIGNED NOT NULL,
  `sender` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `owner` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `universe` tinyint(3) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%buddy_request`
--

CREATE TABLE `%PREFIX%buddy_request` (
  `id` int(11) UNSIGNED NOT NULL,
  `text` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%chat_bans`
--

CREATE TABLE `%PREFIX%chat_bans` (
  `userID` int(11) NOT NULL,
  `userName` varchar(64) NOT NULL,
  `dateTime` datetime NOT NULL,
  `ip` varbinary(16) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%chat_invitations`
--

CREATE TABLE `%PREFIX%chat_invitations` (
  `userID` int(11) NOT NULL,
  `channel` int(11) NOT NULL,
  `dateTime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%chat_messages`
--

CREATE TABLE `%PREFIX%chat_messages` (
  `id` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `userName` varchar(64) NOT NULL,
  `userRole` int(1) NOT NULL,
  `channel` int(11) NOT NULL,
  `dateTime` datetime NOT NULL,
  `ip` varbinary(16) NOT NULL,
  `text` text CHARACTER SET utf8 COLLATE utf8_bin
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%chat_online`
--

CREATE TABLE `%PREFIX%chat_online` (
  `userID` int(11) NOT NULL,
  `userName` varchar(64) NOT NULL,
  `userRole` int(1) NOT NULL,
  `channel` int(11) NOT NULL,
  `dateTime` datetime NOT NULL,
  `ip` varbinary(16) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%config`
--

CREATE TABLE `%PREFIX%config` (
  `telemetry_settings` VARCHAR(4096) NOT NULL DEFAULT '{}',
  `uni` int(11) NOT NULL,
  `VERSION` varchar(8) NOT NULL,
  `sql_revision` int(11) NOT NULL DEFAULT '0',
  `users_amount` int(11) UNSIGNED NOT NULL DEFAULT '1',
  `game_speed` bigint(20) UNSIGNED NOT NULL DEFAULT '2500',
  `fleet_speed` bigint(20) UNSIGNED NOT NULL DEFAULT '2500',
  `resource_multiplier` smallint(5) UNSIGNED NOT NULL DEFAULT '1',
  `storage_multiplier` smallint(5) UNSIGNED NOT NULL DEFAULT '1',
  `message_delete_behavior` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `message_delete_days` tinyint(3) UNSIGNED NOT NULL DEFAULT '7',
  `halt_speed` smallint(5) UNSIGNED NOT NULL DEFAULT '1',
  `Fleet_Cdr` tinyint(3) UNSIGNED NOT NULL DEFAULT '30',
  `Defs_Cdr` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `initial_fields` smallint(5) UNSIGNED NOT NULL DEFAULT '163',
  `uni_name` varchar(30) NOT NULL,
  `game_name` varchar(30) NOT NULL,
  `game_disable` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  `close_reason` text NOT NULL,
  `metal_basic_income` int(11) NOT NULL DEFAULT '20',
  `crystal_basic_income` int(11) NOT NULL DEFAULT '10',
  `deuterium_basic_income` int(11) NOT NULL DEFAULT '0',
  `energy_basic_income` int(11) NOT NULL DEFAULT '0',
  `LastSettedGalaxyPos` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `LastSettedSystemPos` smallint(5) UNSIGNED NOT NULL DEFAULT '1',
  `LastSettedPlanetPos` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `noobprotection` int(11) NOT NULL DEFAULT '0',
  `noobprotectiontime` int(11) NOT NULL DEFAULT '5000',
  `noobprotectionmulti` int(11) NOT NULL DEFAULT '5',
  `forum_url` varchar(128) NOT NULL DEFAULT 'http://2moons.cc',
  `adm_attack` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `debug` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `lang` varchar(2) NOT NULL DEFAULT '',
  `stat` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `stat_level` tinyint(3) UNSIGNED NOT NULL DEFAULT '2',
  `stat_last_update` int(11) NOT NULL DEFAULT '0',
  `stat_settings` int(11) UNSIGNED NOT NULL DEFAULT '1000',
  `stat_update_time` tinyint(3) UNSIGNED NOT NULL DEFAULT '25',
  `stat_last_db_update` int(11) NOT NULL DEFAULT '0',
  `stats_fly_lock` int(11) NOT NULL DEFAULT '0',
  `cron_lock` int(11) NOT NULL DEFAULT '0',
  `ts_modon` tinyint(1) NOT NULL DEFAULT '0',
  `ts_server` varchar(64) NOT NULL DEFAULT '',
  `ts_tcpport` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `ts_udpport` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `ts_timeout` tinyint(1) NOT NULL DEFAULT '1',
  `ts_version` tinyint(1) NOT NULL DEFAULT '2',
  `ts_cron_last` int(11) NOT NULL DEFAULT '0',
  `ts_cron_interval` smallint(5) NOT NULL DEFAULT '5',
  `ts_login` varchar(32) NOT NULL DEFAULT '',
  `ts_password` varchar(32) NOT NULL DEFAULT '',
  `reg_closed` tinyint(1) NOT NULL DEFAULT '0',
  `OverviewNewsFrame` tinyint(1) NOT NULL DEFAULT '1',
  `OverviewNewsText` text NOT NULL,
  `capaktiv` tinyint(1) NOT NULL DEFAULT '0',
  `cappublic` varchar(42) NOT NULL DEFAULT '',
  `capprivate` varchar(42) NOT NULL DEFAULT '',
  `min_build_time` tinyint(2) NOT NULL DEFAULT '1',
  `mail_active` tinyint(1) NOT NULL DEFAULT '0',
  `mail_use` tinyint(1) NOT NULL DEFAULT '0',
  `smtp_host` varchar(64) NOT NULL DEFAULT '',
  `smtp_port` smallint(5) NOT NULL DEFAULT '0',
  `smtp_user` varchar(64) NOT NULL DEFAULT '',
  `smtp_pass` varchar(32) NOT NULL DEFAULT '',
  `smtp_ssl` enum('','ssl','tls') NOT NULL DEFAULT '',
  `smtp_sendmail` varchar(64) NOT NULL DEFAULT '',
  `smail_path` varchar(30) NOT NULL DEFAULT '/usr/sbin/sendmail',
  `user_valid` tinyint(1) NOT NULL DEFAULT '0',
  `fb_on` tinyint(1) NOT NULL DEFAULT '0',
  `fb_apikey` varchar(42) NOT NULL DEFAULT '',
  `fb_skey` varchar(42) NOT NULL DEFAULT '',
  `ga_active` varchar(42) NOT NULL DEFAULT '0',
  `ga_key` varchar(42) NOT NULL DEFAULT '',
  `moduls` varchar(100) NOT NULL DEFAULT '',
  `trade_allowed_ships` varchar(255) NOT NULL DEFAULT '202,401',
  `trade_charge` varchar(5) NOT NULL DEFAULT '30',
  `chat_closed` tinyint(1) NOT NULL DEFAULT '0',
  `chat_allowchan` tinyint(1) NOT NULL DEFAULT '1',
  `chat_allowmes` tinyint(1) NOT NULL DEFAULT '1',
  `chat_allowdelmes` tinyint(1) NOT NULL DEFAULT '1',
  `chat_logmessage` tinyint(1) NOT NULL DEFAULT '1',
  `chat_nickchange` tinyint(1) NOT NULL DEFAULT '1',
  `chat_botname` varchar(15) NOT NULL DEFAULT '2Moons',
  `chat_channelname` varchar(15) NOT NULL DEFAULT '2Moons',
  `chat_socket_active` tinyint(1) NOT NULL DEFAULT '0',
  `chat_socket_host` varchar(64) NOT NULL DEFAULT '',
  `chat_socket_ip` varchar(40) NOT NULL DEFAULT '',
  `chat_socket_port` smallint(5) NOT NULL DEFAULT '0',
  `chat_socket_chatid` tinyint(1) NOT NULL DEFAULT '1',
  `max_galaxy` tinyint(3) UNSIGNED NOT NULL DEFAULT '9',
  `max_system` smallint(5) UNSIGNED NOT NULL DEFAULT '400',
  `max_planets` tinyint(3) UNSIGNED NOT NULL DEFAULT '15',
  `planet_factor` float(2,1) NOT NULL DEFAULT '1.0',
  `max_elements_build` tinyint(3) UNSIGNED NOT NULL DEFAULT '5',
  `max_elements_tech` tinyint(3) UNSIGNED NOT NULL DEFAULT '2',
  `max_elements_ships` tinyint(3) UNSIGNED NOT NULL DEFAULT '10',
  `min_player_planets` tinyint(3) UNSIGNED NOT NULL DEFAULT '9',
  `planets_tech` tinyint(4) NOT NULL DEFAULT '11',
  `planets_officier` tinyint(4) NOT NULL DEFAULT '5',
  `planets_per_tech` float(2,1) NOT NULL DEFAULT '0.5',
  `max_fleet_per_build` bigint(20) UNSIGNED NOT NULL DEFAULT '1000000',
  `deuterium_cost_galaxy` int(11) UNSIGNED NOT NULL DEFAULT '10',
  `max_dm_missions` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `max_overflow` float(2,1) NOT NULL DEFAULT '1.0',
  `moon_factor` float(2,1) NOT NULL DEFAULT '1.0',
  `moon_chance` tinyint(3) UNSIGNED NOT NULL DEFAULT '20',
  `darkmatter_cost_trader` int(11) UNSIGNED NOT NULL DEFAULT '750',
  `factor_university` tinyint(3) UNSIGNED NOT NULL DEFAULT '8',
  `max_fleets_per_acs` tinyint(3) UNSIGNED NOT NULL DEFAULT '16',
  `debris_moon` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `vmode_min_time` int(11) NOT NULL DEFAULT '259200',
  `gate_wait_time` int(11) NOT NULL DEFAULT '3600',
  `metal_start` int(11) UNSIGNED NOT NULL DEFAULT '500',
  `crystal_start` int(11) UNSIGNED NOT NULL DEFAULT '500',
  `deuterium_start` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `darkmatter_start` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `ttf_file` varchar(128) NOT NULL DEFAULT 'styles/resource/fonts/DroidSansMono.ttf',
  `ref_active` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `ref_bonus` int(11) UNSIGNED NOT NULL DEFAULT '1000',
  `ref_minpoints` bigint(20) UNSIGNED NOT NULL DEFAULT '2000',
  `ref_max_referals` tinyint(1) UNSIGNED NOT NULL DEFAULT '5',
  `del_oldstuff` tinyint(3) UNSIGNED NOT NULL DEFAULT '3',
  `del_user_manually` tinyint(3) UNSIGNED NOT NULL DEFAULT '7',
  `del_user_automatic` tinyint(3) UNSIGNED NOT NULL DEFAULT '30',
  `del_user_sendmail` tinyint(3) UNSIGNED NOT NULL DEFAULT '21',
  `sendmail_inactive` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `silo_factor` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  `timezone` varchar(32) NOT NULL DEFAULT 'Europe/London',
  `dst` enum('0','1','2') NOT NULL DEFAULT '2',
  `energySpeed` smallint(6) NOT NULL DEFAULT '1',
  `disclamerAddress` text NOT NULL,
  `disclamerPhone` text NOT NULL,
  `disclamerMail` text NOT NULL,
  `disclamerNotice` text NOT NULL,
  `alliance_create_min_points` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `achievements_mines` int(11) NOT NULL DEFAULT '4',
  `achievements_tech` int(11) NOT NULL DEFAULT '4',
  `achievements_engine` int(11) NOT NULL DEFAULT '4',
  `achievements_colonization` int(11) NOT NULL DEFAULT '3',
  `achievements_moon` int(11) NOT NULL DEFAULT '3',
  `achievements_war` int(11) NOT NULL DEFAULT '3',
  `achievements_destroy` int(11) NOT NULL DEFAULT '4',
  `achievements_time` int(11) NOT NULL DEFAULT '3',
  `achievements_storage` int(11) NOT NULL DEFAULT '4',
  `achievements_community` int(11) NOT NULL DEFAULT '3',
  `achievements_fleet` int(11) NOT NULL DEFAULT '4',
  `achievements_darkmatter` int(11) NOT NULL DEFAULT '5',
  `achievements_planet` int(11) NOT NULL DEFAULT '4',
  `achievements_lab` int(11) NOT NULL DEFAULT '4'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

--
-- Déchargement des données de la table `%PREFIX%config`
--

INSERT INTO `%PREFIX%config` (`uni`, `VERSION`, `sql_revision`, `users_amount`, `game_speed`, `fleet_speed`, `resource_multiplier`, `storage_multiplier`, `message_delete_behavior`, `message_delete_days`, `halt_speed`, `Fleet_Cdr`, `Defs_Cdr`, `initial_fields`, `uni_name`, `game_name`, `game_disable`, `close_reason`, `metal_basic_income`, `crystal_basic_income`, `deuterium_basic_income`, `energy_basic_income`, `LastSettedGalaxyPos`, `LastSettedSystemPos`, `LastSettedPlanetPos`, `noobprotection`, `noobprotectiontime`, `noobprotectionmulti`, `forum_url`, `adm_attack`, `debug`, `lang`, `stat`, `stat_level`, `stat_last_update`, `stat_settings`, `stat_update_time`, `stat_last_db_update`, `stats_fly_lock`, `cron_lock`, `ts_modon`, `ts_server`, `ts_tcpport`, `ts_udpport`, `ts_timeout`, `ts_version`, `ts_cron_last`, `ts_cron_interval`, `ts_login`, `ts_password`, `reg_closed`, `OverviewNewsFrame`, `OverviewNewsText`, `capaktiv`, `cappublic`, `capprivate`, `min_build_time`, `mail_active`, `mail_use`, `smtp_host`, `smtp_port`, `smtp_user`, `smtp_pass`, `smtp_ssl`, `smtp_sendmail`, `smail_path`, `user_valid`, `fb_on`, `fb_apikey`, `fb_skey`, `ga_active`, `ga_key`, `moduls`, `trade_allowed_ships`, `trade_charge`, `chat_closed`, `chat_allowchan`, `chat_allowmes`, `chat_allowdelmes`, `chat_logmessage`, `chat_nickchange`, `chat_botname`, `chat_channelname`, `chat_socket_active`, `chat_socket_host`, `chat_socket_ip`, `chat_socket_port`, `chat_socket_chatid`, `max_galaxy`, `max_system`, `max_planets`, `planet_factor`, `max_elements_build`, `max_elements_tech`, `max_elements_ships`, `min_player_planets`, `planets_tech`, `planets_officier`, `planets_per_tech`, `max_fleet_per_build`, `deuterium_cost_galaxy`, `max_dm_missions`, `max_overflow`, `moon_factor`, `moon_chance`, `darkmatter_cost_trader`, `factor_university`, `max_fleets_per_acs`, `debris_moon`, `vmode_min_time`, `gate_wait_time`, `metal_start`, `crystal_start`, `deuterium_start`, `darkmatter_start`, `ttf_file`, `ref_active`, `ref_bonus`, `ref_minpoints`, `ref_max_referals`, `del_oldstuff`, `del_user_manually`, `del_user_automatic`, `del_user_sendmail`, `sendmail_inactive`, `silo_factor`, `timezone`, `dst`, `energySpeed`, `disclamerAddress`, `disclamerPhone`, `disclamerMail`, `disclamerNotice`, `alliance_create_min_points`, `achievements_mines`, `achievements_tech`, `achievements_engine`, `achievements_colonization`, `achievements_moon`, `achievements_war`, `achievements_destroy`, `achievements_time`, `achievements_storage`, `achievements_community`, `achievements_fleet`, `achievements_darkmatter`, `achievements_planet`, `achievements_lab`) VALUES
(1, '1.8.git', 0, 131, 37500, 10000, 12, 4, 0, 7, 1, 50, 0, 400, 'Risistar', 'Risistar', 1, 'La V19 est arrivée!', 20, 10, 0, 0, 1, 132, 7, 1, 10000, 10, 'https://discord.gg/Jh9fftx', 0, 0, 'fr', 1, 1, 0, 1000, 25, 0, 0, 0, 0, '', 0, 0, 1, 2, 0, 5, '', '', 0, 1, 'La V19 se prépare!', 1, '6Ld1w5YbAAAAABRnjwih2dctE67xZwGwEOGmk6Ur', '6Ld1w5YbAAAAAJ_9UT3-o_Fp9GJ1rPoAn0Bwcgtz', 1, 0, 0, '', 0, 'Paratonnerre', 'marchombre2172', '', '', '/usr/sbin/sendmail', 0, 0, '', '', '0', '', '1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;1;0;1', '202,203,204,205,206,207,208,209,210,211,212,213,214,215,216,217,218,219,220,221,222,223,224,225,226,227,228,229,230,231,232,233,234,235,236,237,238,239,400,401,402,403,404,405,406,407,408,409,410,411,412,413', '100', 0, 1, 1, 0, 1, 1, '2Moons', 'Risistar', 0, '', '', 0, 1, 3, 300, 15, 1.5, 5, 3, 5, 1, 7, 5, 0.5, 1000000, 10, 2, 1.0, 2.0, 20, 100, 50, 5, 1, 259200, 3600, 500, 500, 100, 0, 'styles/resource/fonts/DroidSansMono.ttf', 0, 0, 0, 0, 10, 7, 200, 199, 0, 1, 'Europe/Paris', '0', 1, '', '', '', '', 0, 4, 4, 4, 3, 3, 3, 4, 3, 4, 3, 4, 5, 4, 4);

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%cronjobs`
--

CREATE TABLE `%PREFIX%cronjobs` (
  `cronjobID` int(11) UNSIGNED NOT NULL,
  `name` varchar(32) NOT NULL,
  `isActive` tinyint(1) NOT NULL DEFAULT '1',
  `min` varchar(32) NOT NULL,
  `hours` varchar(32) NOT NULL,
  `dom` varchar(32) NOT NULL,
  `month` varchar(32) NOT NULL,
  `dow` varchar(32) NOT NULL,
  `class` varchar(32) NOT NULL,
  `nextTime` int(11) DEFAULT NULL,
  `lock` varchar(32) DEFAULT NULL,
  `lockedAt` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

--
-- Déchargement des données de la table `%PREFIX%cronjobs`
--

INSERT INTO `%PREFIX%cronjobs` (`cronjobID`, `name`, `isActive`, `min`, `hours`, `dom`, `month`, `dow`, `class`, `nextTime`, `lock`, `lockedAt`) VALUES
(1, 'referral', 1, '*', '*', '*', '*', '*', 'ReferralCronjob', 1784926860, NULL, NULL),
(2, 'statistic', 1, '*/5', '*', '*', '*', '*', 'StatisticCronjob', 1785012900, NULL, NULL),
(3, 'daily', 1, '25', '2', '*', '*', '*', 'DailyCronjob', 1784939100, NULL, NULL),
(4, 'cleaner', 1, '5', '2', '*', '*', '*', 'CleanerCronjob', 1784937900, NULL, NULL),
(5, 'inactive', 1, '30', '1', '*', '*', '0,3,6', 'InactiveMailCronjob', 1785022200, NULL, NULL),
(6, 'teamspeak', 0, '*', '*', '*', '*', '*', 'TeamSpeakCronjob', 1784926860, NULL, NULL),
(7, 'databasedump', 1, '30', '1', '*', '*', '1', 'DumpCronjob', 1785108600, NULL, NULL),
(8, 'tracking', 1, '8', '21', '*', '*', '0', 'TrackingCronjob', 1785092880, NULL, NULL),
(9, 'DMForMail', 1, '0', '23', '*', '*', '*', 'DmMailCronjob', 1785013200, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%cronjobs_log`
--

CREATE TABLE `%PREFIX%cronjobs_log` (
  `cronjobId` int(11) UNSIGNED NOT NULL,
  `executionTime` datetime NOT NULL,
  `lockToken` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%diplo`
--

CREATE TABLE `%PREFIX%diplo` (
  `id` int(11) UNSIGNED NOT NULL,
  `owner_1` int(11) UNSIGNED NOT NULL,
  `owner_2` int(11) UNSIGNED NOT NULL,
  `level` tinyint(1) UNSIGNED NOT NULL,
  `accept` tinyint(1) UNSIGNED NOT NULL,
  `accept_text` varchar(255) NOT NULL,
  `universe` tinyint(3) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%fleets`
--

CREATE TABLE `%PREFIX%fleets` (
  `fleet_id` bigint(11) UNSIGNED NOT NULL,
  `fleet_owner` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `fleet_mission` tinyint(3) UNSIGNED NOT NULL DEFAULT '3',
  `fleet_amount` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `fleet_array` text,
  `fleet_universe` tinyint(3) UNSIGNED NOT NULL,
  `fleet_start_time` int(11) NOT NULL DEFAULT '0',
  `fleet_start_id` int(11) UNSIGNED NOT NULL,
  `fleet_start_galaxy` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `fleet_start_system` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `fleet_start_planet` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `fleet_start_type` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `fleet_end_time` int(11) NOT NULL DEFAULT '0',
  `fleet_end_stay` int(11) NOT NULL DEFAULT '0',
  `fleet_end_id` int(11) UNSIGNED NOT NULL,
  `fleet_end_galaxy` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `fleet_end_system` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `fleet_end_planet` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `fleet_end_type` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `fleet_target_obj` smallint(3) UNSIGNED NOT NULL DEFAULT '0',
  `fleet_resource_metal` double(50,0) UNSIGNED NOT NULL DEFAULT '0',
  `fleet_resource_crystal` double(50,0) UNSIGNED NOT NULL DEFAULT '0',
  `fleet_resource_deuterium` double(50,0) UNSIGNED NOT NULL DEFAULT '0',
  `fleet_resource_darkmatter` double(50,0) UNSIGNED NOT NULL DEFAULT '0',
  `fleet_target_owner` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `fleet_group` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `fleet_mess` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `start_time` int(11) DEFAULT NULL,
  `fleet_busy` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `hasCanceled` tinyint(1) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%fleet_event`
--

CREATE TABLE `%PREFIX%fleet_event` (
  `fleetID` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  `lock` varchar(32) DEFAULT NULL,
  `lockedAt` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%log`
--

CREATE TABLE `%PREFIX%log` (
  `id` int(11) UNSIGNED NOT NULL,
  `mode` tinyint(3) UNSIGNED NOT NULL,
  `admin` int(11) UNSIGNED NOT NULL,
  `target` int(11) NOT NULL,
  `time` int(11) UNSIGNED NOT NULL,
  `data` text NOT NULL,
  `universe` tinyint(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%log_fleets`
--

CREATE TABLE `%PREFIX%log_fleets` (
  `fleet_id` bigint(11) UNSIGNED NOT NULL,
  `fleet_owner` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `fleet_mission` tinyint(3) UNSIGNED NOT NULL DEFAULT '3',
  `fleet_amount` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `fleet_array` text,
  `fleet_universe` tinyint(3) UNSIGNED NOT NULL,
  `fleet_start_time` int(11) NOT NULL DEFAULT '0',
  `fleet_start_id` int(11) UNSIGNED NOT NULL,
  `fleet_start_galaxy` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `fleet_start_system` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `fleet_start_planet` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `fleet_start_type` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `fleet_end_time` int(11) NOT NULL DEFAULT '0',
  `fleet_end_stay` int(11) NOT NULL DEFAULT '0',
  `fleet_end_id` int(11) UNSIGNED NOT NULL,
  `fleet_end_galaxy` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `fleet_end_system` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `fleet_end_planet` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `fleet_end_type` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `fleet_target_obj` smallint(3) UNSIGNED NOT NULL DEFAULT '0',
  `fleet_resource_metal` double(50,0) UNSIGNED NOT NULL DEFAULT '0',
  `fleet_resource_crystal` double(50,0) UNSIGNED NOT NULL DEFAULT '0',
  `fleet_resource_deuterium` double(50,0) UNSIGNED NOT NULL DEFAULT '0',
  `fleet_resource_darkmatter` double(50,0) UNSIGNED NOT NULL DEFAULT '0',
  `fleet_target_owner` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `fleet_group` varchar(15) NOT NULL DEFAULT '0',
  `fleet_mess` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `start_time` int(11) DEFAULT NULL,
  `fleet_busy` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `hasCanceled` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `fleet_state` tinyint(3) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%lostpassword`
--

CREATE TABLE `%PREFIX%lostpassword` (
  `userID` int(10) UNSIGNED NOT NULL,
  `key` varchar(32) NOT NULL,
  `time` int(10) UNSIGNED NOT NULL,
  `hasChanged` tinyint(1) NOT NULL DEFAULT '0',
  `fromIP` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%messages`
--

CREATE TABLE `%PREFIX%messages` (
  `message_id` bigint(20) UNSIGNED NOT NULL,
  `message_owner` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `message_sender` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `message_time` int(11) NOT NULL DEFAULT '0',
  `message_type` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `message_from` varchar(128) DEFAULT NULL,
  `message_subject` varchar(255) DEFAULT NULL,
  `message_text` text,
  `message_unread` tinyint(4) NOT NULL DEFAULT '1',
  `message_universe` tinyint(3) UNSIGNED NOT NULL,
  `message_deleted` int(11) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%multi`
--

CREATE TABLE `%PREFIX%multi` (
  `multiID` int(11) NOT NULL,
  `userID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%news`
--

CREATE TABLE `%PREFIX%news` (
  `id` int(11) UNSIGNED NOT NULL,
  `user` varchar(64) NOT NULL,
  `date` int(11) NOT NULL,
  `title` varchar(64) NOT NULL,
  `text` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%notes`
--

CREATE TABLE `%PREFIX%notes` (
  `id` int(11) NOT NULL,
  `owner` int(11) UNSIGNED DEFAULT NULL,
  `time` int(11) DEFAULT NULL,
  `priority` tinyint(1) UNSIGNED DEFAULT '1',
  `title` varchar(32) DEFAULT NULL,
  `text` text,
  `universe` tinyint(3) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%planets`
--

CREATE TABLE `%PREFIX%planets` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(20) DEFAULT 'Hauptplanet',
  `id_owner` int(11) UNSIGNED DEFAULT NULL,
  `universe` tinyint(3) UNSIGNED NOT NULL,
  `galaxy` tinyint(3) NOT NULL DEFAULT '0',
  `system` smallint(5) NOT NULL DEFAULT '0',
  `planet` tinyint(3) NOT NULL DEFAULT '0',
  `last_update` int(11) DEFAULT NULL,
  `planet_type` enum('1','3') NOT NULL DEFAULT '1',
  `destruyed` int(11) NOT NULL DEFAULT '0',
  `b_building` int(11) NOT NULL DEFAULT '0',
  `b_building_id` text,
  `b_hangar` int(11) NOT NULL DEFAULT '0',
  `b_hangar_id` text,
  `b_hangar_plus` int(11) NOT NULL DEFAULT '0',
  `image` varchar(32) NOT NULL DEFAULT 'normaltempplanet01',
  `diameter` int(11) UNSIGNED NOT NULL DEFAULT '12800',
  `field_current` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `field_max` smallint(5) UNSIGNED NOT NULL DEFAULT '163',
  `temp_min` int(3) NOT NULL DEFAULT '-17',
  `temp_max` int(3) NOT NULL DEFAULT '23',
  `eco_hash` varchar(32) NOT NULL DEFAULT '',
  `metal` double(50,6) UNSIGNED NOT NULL DEFAULT '0.000000',
  `metal_perhour` double(50,6) NOT NULL DEFAULT '0.000000',
  `metal_max` double(50,0) UNSIGNED DEFAULT '100000',
  `crystal` double(50,6) UNSIGNED NOT NULL DEFAULT '0.000000',
  `crystal_perhour` double(50,6) NOT NULL DEFAULT '0.000000',
  `crystal_max` double(50,0) UNSIGNED DEFAULT '100000',
  `deuterium` double(50,6) UNSIGNED NOT NULL DEFAULT '0.000000',
  `deuterium_perhour` double(50,6) NOT NULL DEFAULT '0.000000',
  `deuterium_max` double(50,0) UNSIGNED DEFAULT '100000',
  `energy_used` double(50,0) NOT NULL DEFAULT '0',
  `energy` double(50,0) UNSIGNED NOT NULL DEFAULT '0',
  `metal_mine` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `crystal_mine` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `deuterium_sintetizer` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `solar_plant` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `fusion_plant` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `robot_factory` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `nano_factory` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `hangar` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `metal_store` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `crystal_store` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `deuterium_store` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `laboratory` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `terraformer` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `university` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `ally_deposit` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `silo` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `mondbasis` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `phalanx` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `sprungtor` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `small_ship_cargo` bigint(20) NOT NULL DEFAULT '0',
  `big_ship_cargo` bigint(20) NOT NULL DEFAULT '0',
  `light_hunter` bigint(20) NOT NULL DEFAULT '0',
  `heavy_hunter` bigint(20) NOT NULL DEFAULT '0',
  `crusher` bigint(20) NOT NULL DEFAULT '0',
  `experiment_carrier` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `air_superiority_carrier` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `antiship_carrier` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `multi_carrier` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `battle_ship` bigint(20) NOT NULL DEFAULT '0',
  `colonizer` bigint(20) NOT NULL DEFAULT '0',
  `recycler` bigint(20) NOT NULL DEFAULT '0',
  `spy_sonde` bigint(20) NOT NULL DEFAULT '0',
  `bomber_ship` bigint(20) NOT NULL DEFAULT '0',
  `solar_satelit` bigint(20) NOT NULL DEFAULT '0',
  `destructor` bigint(20) NOT NULL DEFAULT '0',
  `advanced_air_superiority_carrier` bigint(20) DEFAULT '0',
  `advanced_antiship_carrier` bigint(20) DEFAULT '0',
  `advanced_mixed_carrier` bigint(20) DEFAULT '0',
  `omni_carrier` bigint(20) NOT NULL DEFAULT '0',
  `prototype_moon_crusher` bigint(20) NOT NULL DEFAULT '0',
  `battleship` bigint(20) NOT NULL DEFAULT '0',
  `lune_noir` bigint(20) NOT NULL DEFAULT '0',
  `fs_r91` bigint(20) NOT NULL DEFAULT '0',
  `ev_transporter` bigint(20) NOT NULL DEFAULT '0',
  `star_crasher` bigint(20) NOT NULL DEFAULT '0',
  `giga_recykler` bigint(20) NOT NULL DEFAULT '0',
  `interceptor` bigint(20) NOT NULL DEFAULT '0',
  `viauldout` bigint(20) NOT NULL DEFAULT '0',
  `heavy_cruiser` bigint(20) NOT NULL DEFAULT '0',
  `battleship_two` bigint(20) NOT NULL DEFAULT '0',
  `thanatos_class` bigint(20) NOT NULL DEFAULT '0',
  `kamikaze_sonde` bigint(20) NOT NULL DEFAULT '0',
  `impulsion_mine` bigint(20) NOT NULL DEFAULT '0',
  `emp_mine` bigint(20) NOT NULL DEFAULT '0',
  `plasma_mine` bigint(20) NOT NULL DEFAULT '0',
  `graviton_mine` bigint(20) NOT NULL DEFAULT '0',
  `dm_ship` bigint(20) NOT NULL DEFAULT '0',
  `orbital_station` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `space_shipyard` bigint(20) NOT NULL DEFAULT '0',
  `Daedalus` bigint(20) NOT NULL DEFAULT '0',
  `QuatSansDisse` bigint(20) NOT NULL DEFAULT '0',
  `edlm` bigint(20) NOT NULL DEFAULT '0',
  `Epinglaj` bigint(20) NOT NULL DEFAULT '0',
  `hunter` bigint(20) NOT NULL DEFAULT '0',
  `enforcer` bigint(20) NOT NULL DEFAULT '0',
  `demolisher` bigint(20) NOT NULL DEFAULT '0',
  `gilbert` bigint(20) NOT NULL DEFAULT '0',
  `essaim` bigint(20) NOT NULL DEFAULT '0',
  `antictransport` bigint(20) NOT NULL DEFAULT '0',
  `defender` bigint(20) NOT NULL DEFAULT '0',
  `warrior` bigint(20) NOT NULL DEFAULT '0',
  `reckless` bigint(20) NOT NULL DEFAULT '0',
  `striker` bigint(20) NOT NULL DEFAULT '0',
  `overgrowth` bigint(20) NOT NULL DEFAULT '0',
  `dawn` bigint(20) NOT NULL DEFAULT '0',
  `arrow` bigint(20) NOT NULL DEFAULT '0',
  `exaltedtransport` bigint(20) NOT NULL DEFAULT '0',
  `guardian` bigint(20) NOT NULL DEFAULT '0',
  `blade` bigint(20) NOT NULL DEFAULT '0',
  `mirage` bigint(20) NOT NULL DEFAULT '0',
  `divinehand` bigint(20) NOT NULL DEFAULT '0',
  `avatar` bigint(20) NOT NULL DEFAULT '0',
  `ragnarok` bigint(20) NOT NULL DEFAULT '0',
  `logistics_drone` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `recycle_drone` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `advanced_recycle_drone` bigint(20) UNSIGNED DEFAULT '0',
  `experimental_fighter` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `air_superiority_fighter` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `torpedo_fighter` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `advanced_air_superiority_fighter` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `advanced_torpedo_fighter` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `rafale` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `carried_bomber` bigint(20) NOT NULL DEFAULT '0',
  `viauldout_shipment` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `heavy_cruiser_shipment` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `destructor_shipment` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `interceptor_shipment` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `battleship_two_shipment` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `misil_launcher` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `small_laser` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `big_laser` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `gauss_canyon` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `ionic_canyon` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `planetary_hangar` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `antiair_cannon` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `ionic_propulsion` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `ionic_collision` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `buster_canyon` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `ionic_cannon` bigint(20) NOT NULL DEFAULT '0',
  `small_protection_shield` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `hoash_shield` bigint(20) NOT NULL DEFAULT '0',
  `planet_protector` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `big_protection_shield` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `graviton_canyon` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `interceptor_misil` bigint(20) NOT NULL DEFAULT '0',
  `interplanetary_misil` bigint(20) NOT NULL DEFAULT '0',
  `metal_mine_porcent` enum('0','1','2','3','4','5','6','7','8','9','10') NOT NULL DEFAULT '10',
  `crystal_mine_porcent` enum('0','1','2','3','4','5','6','7','8','9','10') NOT NULL DEFAULT '10',
  `deuterium_sintetizer_porcent` enum('0','1','2','3','4','5','6','7','8','9','10') NOT NULL DEFAULT '10',
  `solar_plant_porcent` enum('0','1','2','3','4','5','6','7','8','9','10') NOT NULL DEFAULT '10',
  `fusion_plant_porcent` enum('0','1','2','3','4','5','6','7','8','9','10') NOT NULL DEFAULT '10',
  `solar_satelit_porcent` enum('0','1','2','3','4','5','6','7','8','9','10') NOT NULL DEFAULT '10',
  `last_jump_time` int(11) NOT NULL DEFAULT '0',
  `der_metal` double(50,0) UNSIGNED NOT NULL DEFAULT '0',
  `der_crystal` double(50,0) UNSIGNED NOT NULL DEFAULT '0',
  `id_luna` int(11) NOT NULL DEFAULT '0',
  `b_hangar_time` int(11) NOT NULL DEFAULT '0',
  `LastMIP` bigint(20) NOT NULL DEFAULT '0',
  `percentShield` int(11) NOT NULL DEFAULT '100'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%raports`
--

CREATE TABLE `%PREFIX%raports` (
  `rid` varchar(32) NOT NULL,
  `raport` text NOT NULL,
  `time` int(11) NOT NULL,
  `attacker` varchar(255) NOT NULL DEFAULT '',
  `defender` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%records`
--

CREATE TABLE `%PREFIX%records` (
  `userID` int(10) UNSIGNED NOT NULL,
  `elementID` smallint(5) UNSIGNED NOT NULL,
  `level` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%session`
--

CREATE TABLE `%PREFIX%session` (
  `sessionID` varchar(32) NOT NULL,
  `userID` int(10) UNSIGNED NOT NULL,
  `userIP` varchar(40) NOT NULL,
  `lastonline` int(11) NOT NULL,
  `lastplusone` int(11) NOT NULL DEFAULT '0',
  `lastplustwo` int(11) NOT NULL DEFAULT '0',
  `lastplusthree` int(11) NOT NULL DEFAULT '0',
  `lastplusfour` int(11) NOT NULL DEFAULT '0',
  `lastplusfive` int(11) NOT NULL DEFAULT '0',
  `lastplussix` int(11) NOT NULL DEFAULT '0',
  `created` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%shortcuts`
--

CREATE TABLE `%PREFIX%shortcuts` (
  `shortcutID` int(10) UNSIGNED NOT NULL,
  `ownerID` int(10) UNSIGNED NOT NULL,
  `name` varchar(32) NOT NULL,
  `galaxy` tinyint(3) UNSIGNED NOT NULL,
  `system` smallint(5) UNSIGNED NOT NULL,
  `planet` tinyint(3) UNSIGNED NOT NULL,
  `type` tinyint(1) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%statpoints`
--

CREATE TABLE `%PREFIX%statpoints` (
  `id_owner` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `id_ally` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `stat_type` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `universe` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `tech_rank` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `tech_old_rank` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `tech_points` double(50,0) UNSIGNED NOT NULL DEFAULT '0',
  `tech_count` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `build_rank` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `build_old_rank` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `build_points` double(50,0) UNSIGNED NOT NULL DEFAULT '0',
  `build_count` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `defs_rank` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `defs_old_rank` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `defs_points` double(50,0) UNSIGNED NOT NULL DEFAULT '0',
  `defs_count` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `fleet_rank` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `fleet_old_rank` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `fleet_points` double(50,0) UNSIGNED NOT NULL DEFAULT '0',
  `fleet_count` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `total_rank` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `total_old_rank` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `total_points` double(50,0) UNSIGNED NOT NULL DEFAULT '0',
  `total_count` bigint(20) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%system`
--

CREATE TABLE `%PREFIX%system` (
  `dbVersion` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

--
-- Déchargement des données de la table `%PREFIX%system`
--

INSERT INTO `%PREFIX%system` (`dbVersion`) VALUES
(8);

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%ticket`
--

CREATE TABLE `%PREFIX%ticket` (
  `ticketID` int(10) UNSIGNED NOT NULL,
  `universe` tinyint(3) UNSIGNED NOT NULL,
  `ownerID` int(10) UNSIGNED NOT NULL,
  `categoryID` tinyint(1) UNSIGNED NOT NULL,
  `subject` varchar(255) NOT NULL,
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `time` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%ticket_answer`
--

CREATE TABLE `%PREFIX%ticket_answer` (
  `answerID` int(10) UNSIGNED NOT NULL,
  `ownerID` int(10) UNSIGNED NOT NULL,
  `ownerName` varchar(32) NOT NULL,
  `ticketID` int(10) UNSIGNED NOT NULL,
  `time` int(10) UNSIGNED NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` mediumtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%ticket_category`
--

CREATE TABLE `%PREFIX%ticket_category` (
  `categoryID` int(11) NOT NULL,
  `name` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

--
-- Déchargement des données de la table `%PREFIX%ticket_category`
--

INSERT INTO `%PREFIX%ticket_category` (`categoryID`, `name`) VALUES
(1, 'Ticket');

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%topkb`
--

CREATE TABLE `%PREFIX%topkb` (
  `rid` varchar(32) NOT NULL,
  `units` double(50,0) UNSIGNED NOT NULL,
  `result` varchar(1) NOT NULL,
  `time` int(11) NOT NULL,
  `universe` tinyint(3) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%users`
--

CREATE TABLE `%PREFIX%users` (
  `id` int(11) UNSIGNED NOT NULL,
  `username` varchar(32) NOT NULL DEFAULT '',
  `password` varchar(60) NOT NULL DEFAULT '',
  `email` varchar(64) NOT NULL DEFAULT '',
  `email_2` varchar(64) NOT NULL DEFAULT '',
  `lang` varchar(2) NOT NULL DEFAULT 'fr',
  `authattack` tinyint(1) NOT NULL DEFAULT '0',
  `authlevel` tinyint(1) NOT NULL DEFAULT '0',
  `rights` text,
  `id_planet` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `universe` tinyint(3) UNSIGNED NOT NULL,
  `galaxy` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `system` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `planet` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `darkmatter` double(50,0) NOT NULL DEFAULT '0',
  `user_lastip` varchar(40) NOT NULL DEFAULT '',
  `ip_at_reg` varchar(40) NOT NULL DEFAULT '',
  `register_time` int(11) NOT NULL DEFAULT '0',
  `onlinetime` int(11) NOT NULL DEFAULT '0',
  `dpath` varchar(20) NOT NULL DEFAULT 'gow',
  `timezone` varchar(32) NOT NULL DEFAULT 'Europe/London',
  `planet_sort` tinyint(1) NOT NULL DEFAULT '0',
  `planet_sort_order` tinyint(1) NOT NULL DEFAULT '0',
  `spio_anz` int(10) UNSIGNED NOT NULL DEFAULT '1',
  `settings_fleetactions` tinyint(2) UNSIGNED NOT NULL DEFAULT '3',
  `settings_esp` tinyint(1) NOT NULL DEFAULT '1',
  `settings_wri` tinyint(1) NOT NULL DEFAULT '1',
  `settings_bud` tinyint(1) NOT NULL DEFAULT '1',
  `settings_mis` tinyint(1) NOT NULL DEFAULT '1',
  `settings_blockPM` tinyint(1) NOT NULL DEFAULT '0',
  `urlaubs_modus` tinyint(1) NOT NULL DEFAULT '0',
  `urlaubs_until` int(11) NOT NULL DEFAULT '0',
  `db_deaktjava` int(11) NOT NULL DEFAULT '0',
  `b_tech_planet` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `b_tech` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `b_tech_id` smallint(2) UNSIGNED NOT NULL DEFAULT '0',
  `b_tech_queue` text,
  `spy_tech` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `computer_tech` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `military_tech` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `defence_tech` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `shield_tech` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `energy_tech` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `hyperspace_tech` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `combustion_tech` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `impulse_motor_tech` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `hyperspace_motor_tech` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `laser_tech` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `ionic_tech` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `buster_tech` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `intergalactic_tech` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `expedition_tech` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `metal_proc_tech` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `crystal_proc_tech` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `deuterium_proc_tech` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `graviton_tech` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `zoulman_tech` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `potato_crown` tinyint(4) NOT NULL DEFAULT '0',
  `centralisation` tinyint(4) NOT NULL DEFAULT '0',
  `escalation` int(11) NOT NULL DEFAULT '0',
  `larrysluckycoin` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `profitincrease` int(11) NOT NULL DEFAULT '0',
  `exploration_radar` tinyint(3) NOT NULL DEFAULT '0',
  `carried_operations` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `quantum_radar` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `post_mail` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `dm_application` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `ally_id` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `ally_register_time` int(11) NOT NULL DEFAULT '0',
  `ally_rank_id` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `rpg_geologue` tinyint(2) UNSIGNED NOT NULL DEFAULT '0',
  `rpg_amiral` tinyint(3) NOT NULL DEFAULT '0',
  `rpg_ingenieur` tinyint(2) NOT NULL DEFAULT '0',
  `rpg_technocrate` tinyint(2) NOT NULL DEFAULT '0',
  `rpg_espion` tinyint(2) NOT NULL DEFAULT '0',
  `rpg_constructeur` tinyint(2) NOT NULL DEFAULT '0',
  `rpg_scientifique` tinyint(2) NOT NULL DEFAULT '0',
  `rpg_commandant` tinyint(2) NOT NULL DEFAULT '0',
  `rpg_stockeur` tinyint(2) NOT NULL DEFAULT '0',
  `rpg_defenseur` tinyint(2) NOT NULL DEFAULT '0',
  `rpg_destructeur` tinyint(2) NOT NULL DEFAULT '0',
  `rpg_general` tinyint(2) NOT NULL DEFAULT '0',
  `rpg_bunker` tinyint(2) NOT NULL DEFAULT '0',
  `rpg_raideur` tinyint(2) NOT NULL DEFAULT '0',
  `rpg_empereur` tinyint(22) NOT NULL DEFAULT '0',
  `rpg_horus` int(11) NOT NULL DEFAULT '0',
  `class_explorer` tinyint(4) NOT NULL DEFAULT '0',
  `class_raider` tinyint(4) NOT NULL DEFAULT '0',
  `class_miner` tinyint(4) NOT NULL DEFAULT '0',
  `class_lone` tinyint(4) NOT NULL DEFAULT '0',
  `class_merchant` int(11) NOT NULL DEFAULT '0',
  `class_ability_bruiser` int(11) NOT NULL DEFAULT '0',
  `class_ability_engineer` int(11) NOT NULL DEFAULT '0',
  `class_ability_lucky` int(11) NOT NULL DEFAULT '0',
  `class_ability_electrician` int(11) NOT NULL DEFAULT '0',
  `class_ability_researcher` int(11) NOT NULL DEFAULT '0',
  `class_ability_experimentor` int(11) NOT NULL DEFAULT '0',
  `class_ability_potato_crown` int(11) NOT NULL DEFAULT '0',
  `class_ability_negociator` int(11) NOT NULL DEFAULT '0',
  `class_ability_lunar` int(11) NOT NULL DEFAULT '0',
  `npc_class_pirate` int(11) NOT NULL DEFAULT '0',
  `admin` bigint(20) NOT NULL DEFAULT '0',
  `bana` tinyint(1) NOT NULL DEFAULT '0',
  `banaday` int(11) NOT NULL DEFAULT '0',
  `hof` tinyint(1) NOT NULL DEFAULT '1',
  `spyMessagesMode` tinyint(1) NOT NULL DEFAULT '0',
  `wons` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `loos` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `draws` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `kbmetal` double(50,0) UNSIGNED NOT NULL DEFAULT '0',
  `kbcrystal` double(50,0) UNSIGNED NOT NULL DEFAULT '0',
  `lostunits` double(50,0) UNSIGNED NOT NULL DEFAULT '0',
  `desunits` double(50,0) UNSIGNED NOT NULL DEFAULT '0',
  `uctime` int(11) NOT NULL DEFAULT '0',
  `setmail` int(11) NOT NULL DEFAULT '0',
  `dm_attack` int(11) NOT NULL DEFAULT '0',
  `dm_defensive` int(11) NOT NULL DEFAULT '0',
  `dm_buildtime` int(11) NOT NULL DEFAULT '0',
  `dm_researchtime` int(11) NOT NULL DEFAULT '0',
  `dm_resource` int(11) NOT NULL DEFAULT '0',
  `dm_energie` int(11) NOT NULL DEFAULT '0',
  `dm_fleettime` int(11) NOT NULL DEFAULT '0',
  `dm_noobboost` tinyint(2) NOT NULL DEFAULT '0',
  `ref_id` int(11) NOT NULL DEFAULT '0',
  `ref_bonus` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `inactive_mail` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `achievements` int(11) NOT NULL DEFAULT '0',
  `achievements_mines` int(11) NOT NULL DEFAULT '0',
  `achievements_mines1` int(11) NOT NULL DEFAULT '0',
  `achievements_mines2` int(11) NOT NULL DEFAULT '0',
  `achievements_mines3` int(11) NOT NULL DEFAULT '0',
  `achievements_mines4` int(11) NOT NULL DEFAULT '0',
  `achievements_tech` int(11) NOT NULL DEFAULT '0',
  `achievements_tech1` int(11) NOT NULL DEFAULT '0',
  `achievements_tech2` int(11) NOT NULL DEFAULT '0',
  `achievements_tech3` int(11) NOT NULL DEFAULT '0',
  `achievements_tech4` int(11) NOT NULL DEFAULT '0',
  `achievements_engine` int(11) NOT NULL DEFAULT '0',
  `achievements_engine1` int(11) NOT NULL DEFAULT '0',
  `achievements_engine2` int(11) NOT NULL DEFAULT '0',
  `achievements_engine3` int(11) NOT NULL DEFAULT '0',
  `achievements_engine4` int(11) NOT NULL DEFAULT '0',
  `achievements_colonization` int(11) NOT NULL DEFAULT '0',
  `achievements_colonization1` int(11) NOT NULL DEFAULT '0',
  `achievements_colonization2` int(11) NOT NULL DEFAULT '0',
  `achievements_colonization3` int(11) NOT NULL DEFAULT '0',
  `achievements_moon` int(11) NOT NULL DEFAULT '0',
  `achievements_moon1` int(11) NOT NULL DEFAULT '0',
  `achievements_moon2` int(11) NOT NULL DEFAULT '0',
  `achievements_moon3` int(11) NOT NULL DEFAULT '0',
  `achievements_war` int(11) NOT NULL DEFAULT '0',
  `achievements_war1` int(11) NOT NULL DEFAULT '0',
  `achievements_war2` int(11) NOT NULL DEFAULT '0',
  `achievements_war3` int(11) NOT NULL DEFAULT '0',
  `achievements_destroy` int(11) NOT NULL DEFAULT '0',
  `achievements_destroy1` int(11) NOT NULL DEFAULT '0',
  `achievements_destroy2` int(11) NOT NULL DEFAULT '0',
  `achievements_destroy3` int(11) NOT NULL DEFAULT '0',
  `achievements_destroy4` int(11) NOT NULL DEFAULT '0',
  `achievements_time` int(11) NOT NULL DEFAULT '0',
  `achievements_time1` int(11) NOT NULL DEFAULT '0',
  `achievements_time2` int(11) NOT NULL DEFAULT '0',
  `achievements_time3` int(11) NOT NULL DEFAULT '0',
  `achievements_storage` int(11) NOT NULL DEFAULT '0',
  `achievements_storage1` int(11) NOT NULL DEFAULT '0',
  `achievements_storage2` int(11) NOT NULL DEFAULT '0',
  `achievements_storage3` int(11) NOT NULL DEFAULT '0',
  `achievements_storage4` int(11) NOT NULL DEFAULT '0',
  `achievements_community` int(11) NOT NULL DEFAULT '0',
  `achievements_community1` int(11) NOT NULL DEFAULT '0',
  `achievements_community2` int(11) NOT NULL DEFAULT '0',
  `achievements_community3` int(11) NOT NULL DEFAULT '0',
  `achievements_fleet` int(11) NOT NULL DEFAULT '0',
  `achievements_fleet1` int(11) NOT NULL DEFAULT '0',
  `achievements_fleet2` int(11) NOT NULL DEFAULT '0',
  `achievements_fleet3` int(11) NOT NULL DEFAULT '0',
  `achievements_fleet4` int(11) NOT NULL DEFAULT '0',
  `achievements_darkmatter` int(11) NOT NULL DEFAULT '0',
  `achievements_darkmatter1` int(11) NOT NULL DEFAULT '0',
  `achievements_darkmatter2` int(11) NOT NULL DEFAULT '0',
  `achievements_darkmatter3` int(11) NOT NULL DEFAULT '0',
  `achievements_darkmatter4` int(11) NOT NULL DEFAULT '0',
  `achievements_darkmatter5` int(11) NOT NULL DEFAULT '0',
  `achievements_planet` int(11) NOT NULL DEFAULT '0',
  `achievements_planet1` int(11) NOT NULL DEFAULT '0',
  `achievements_planet2` int(11) NOT NULL DEFAULT '0',
  `achievements_planet3` int(11) NOT NULL DEFAULT '0',
  `achievements_planet4` int(11) NOT NULL DEFAULT '0',
  `achievements_lab` int(11) NOT NULL DEFAULT '0',
  `achievements_lab1` int(11) NOT NULL DEFAULT '0',
  `achievements_lab2` int(11) NOT NULL DEFAULT '0',
  `achievements_lab3` int(11) NOT NULL DEFAULT '0',
  `achievements_lab4` int(11) NOT NULL DEFAULT '0',
  `user_lastclient` varchar(255) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%users_to_acs`
--

CREATE TABLE `%PREFIX%users_to_acs` (
  `userID` int(10) UNSIGNED NOT NULL,
  `acsID` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%users_to_extauth`
--

CREATE TABLE `%PREFIX%users_to_extauth` (
  `id` int(11) NOT NULL,
  `account` varchar(64) NOT NULL,
  `mode` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%users_to_topkb`
--

CREATE TABLE `%PREFIX%users_to_topkb` (
  `rid` varchar(32) NOT NULL,
  `uid` int(11) NOT NULL,
  `username` varchar(128) NOT NULL,
  `role` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%users_valid`
--

CREATE TABLE `%PREFIX%users_valid` (
  `validationID` int(11) UNSIGNED NOT NULL,
  `userName` varchar(64) NOT NULL,
  `validationKey` varchar(32) NOT NULL,
  `password` varchar(60) NOT NULL,
  `email` varchar(64) NOT NULL,
  `date` int(11) NOT NULL,
  `ip` varchar(40) NOT NULL,
  `language` varchar(3) NOT NULL,
  `universe` tinyint(3) UNSIGNED NOT NULL,
  `referralID` int(11) DEFAULT NULL,
  `externalAuthUID` varchar(128) DEFAULT NULL,
  `externalAuthMethod` varchar(32) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%vars`
--

CREATE TABLE `%PREFIX%vars` (
  `elementID` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(32) NOT NULL,
  `class` int(11) NOT NULL,
  `onPlanetType` set('1','3') NOT NULL,
  `onePerPlanet` tinyint(4) NOT NULL,
  `factor` float(4,2) NOT NULL,
  `maxLevel` int(11) DEFAULT NULL,
  `cost901` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `cost902` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `cost903` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `cost911` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `cost921` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `consumption1` int(11) UNSIGNED DEFAULT NULL,
  `consumption2` int(11) UNSIGNED DEFAULT NULL,
  `speedTech` int(11) UNSIGNED DEFAULT NULL,
  `speed1` int(11) UNSIGNED DEFAULT NULL,
  `speed2` int(11) UNSIGNED DEFAULT NULL,
  `speed2Tech` int(10) UNSIGNED DEFAULT NULL,
  `speed2onLevel` int(10) UNSIGNED DEFAULT NULL,
  `speed3Tech` int(10) UNSIGNED DEFAULT NULL,
  `speed3onLevel` int(10) UNSIGNED DEFAULT NULL,
  `capacity` int(11) UNSIGNED DEFAULT NULL,
  `attack` int(10) UNSIGNED DEFAULT NULL,
  `defend` int(10) UNSIGNED DEFAULT NULL,
  `timeBonus` int(11) UNSIGNED DEFAULT NULL,
  `bonusAttack` float(4,2) NOT NULL DEFAULT '0.00',
  `bonusDefensive` float(4,2) NOT NULL DEFAULT '0.00',
  `bonusShield` float(4,2) NOT NULL DEFAULT '0.00',
  `bonusBuildTime` float(4,2) NOT NULL DEFAULT '0.00',
  `bonusResearchTime` float(4,2) NOT NULL DEFAULT '0.00',
  `bonusShipTime` float(4,2) NOT NULL DEFAULT '0.00',
  `bonusDefensiveTime` float(4,2) NOT NULL DEFAULT '0.00',
  `bonusResource` float(4,2) NOT NULL DEFAULT '0.00',
  `bonusEnergy` float(4,2) NOT NULL DEFAULT '0.00',
  `bonusResourceStorage` float(4,2) NOT NULL DEFAULT '0.00',
  `bonusShipStorage` float(4,2) NOT NULL DEFAULT '0.00',
  `bonusFlyTime` float(4,2) NOT NULL DEFAULT '0.00',
  `bonusFleetSlots` float(4,2) NOT NULL DEFAULT '0.00',
  `bonusPlanets` float(4,2) NOT NULL DEFAULT '0.00',
  `bonusSpyPower` float(4,2) NOT NULL DEFAULT '0.00',
  `bonusExpedition` float(4,2) NOT NULL DEFAULT '0.00',
  `bonusGateCoolTime` float(4,2) NOT NULL DEFAULT '0.00',
  `bonusMoreFound` float(4,2) NOT NULL DEFAULT '0.00',
  `bonusAttackUnit` smallint(1) NOT NULL DEFAULT '0',
  `bonusDefensiveUnit` smallint(1) NOT NULL DEFAULT '0',
  `bonusShieldUnit` smallint(1) NOT NULL DEFAULT '0',
  `bonusBuildTimeUnit` smallint(1) NOT NULL DEFAULT '0',
  `bonusResearchTimeUnit` smallint(1) NOT NULL DEFAULT '0',
  `bonusShipTimeUnit` smallint(1) NOT NULL DEFAULT '0',
  `bonusDefensiveTimeUnit` smallint(1) NOT NULL DEFAULT '0',
  `bonusResourceUnit` smallint(1) NOT NULL DEFAULT '0',
  `bonusEnergyUnit` smallint(1) NOT NULL DEFAULT '0',
  `bonusResourceStorageUnit` smallint(1) NOT NULL DEFAULT '0',
  `bonusShipStorageUnit` smallint(1) NOT NULL DEFAULT '0',
  `bonusFlyTimeUnit` smallint(1) NOT NULL DEFAULT '0',
  `bonusFleetSlotsUnit` smallint(1) NOT NULL DEFAULT '0',
  `bonusPlanetsUnit` smallint(1) NOT NULL DEFAULT '0',
  `bonusSpyPowerUnit` smallint(1) NOT NULL DEFAULT '0',
  `bonusExpeditionUnit` smallint(1) NOT NULL DEFAULT '0',
  `bonusGateCoolTimeUnit` smallint(1) NOT NULL DEFAULT '0',
  `bonusMoreFoundUnit` smallint(1) NOT NULL DEFAULT '0',
  `speedFleetFactor` float(4,2) DEFAULT NULL,
  `production901` varchar(255) DEFAULT NULL,
  `production902` varchar(255) DEFAULT NULL,
  `production903` varchar(255) DEFAULT NULL,
  `production911` varchar(255) DEFAULT NULL,
  `production921` varchar(255) DEFAULT NULL,
  `storage901` varchar(255) DEFAULT NULL,
  `storage902` varchar(255) DEFAULT NULL,
  `storage903` varchar(255) DEFAULT NULL,
  `shipclass` int(11) DEFAULT NULL,
  `nodebris` int(11) NOT NULL DEFAULT '0',
  `carriercapacity` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

--
-- Déchargement des données de la table `%PREFIX%vars`
--

INSERT INTO `%PREFIX%vars` (`elementID`, `name`, `class`, `onPlanetType`, `onePerPlanet`, `factor`, `maxLevel`, `cost901`, `cost902`, `cost903`, `cost911`, `cost921`, `consumption1`, `consumption2`, `speedTech`, `speed1`, `speed2`, `speed2Tech`, `speed2onLevel`, `speed3Tech`, `speed3onLevel`, `capacity`, `attack`, `defend`, `timeBonus`, `bonusAttack`, `bonusDefensive`, `bonusShield`, `bonusBuildTime`, `bonusResearchTime`, `bonusShipTime`, `bonusDefensiveTime`, `bonusResource`, `bonusEnergy`, `bonusResourceStorage`, `bonusShipStorage`, `bonusFlyTime`, `bonusFleetSlots`, `bonusPlanets`, `bonusSpyPower`, `bonusExpedition`, `bonusGateCoolTime`, `bonusMoreFound`, `bonusAttackUnit`, `bonusDefensiveUnit`, `bonusShieldUnit`, `bonusBuildTimeUnit`, `bonusResearchTimeUnit`, `bonusShipTimeUnit`, `bonusDefensiveTimeUnit`, `bonusResourceUnit`, `bonusEnergyUnit`, `bonusResourceStorageUnit`, `bonusShipStorageUnit`, `bonusFlyTimeUnit`, `bonusFleetSlotsUnit`, `bonusPlanetsUnit`, `bonusSpyPowerUnit`, `bonusExpeditionUnit`, `bonusGateCoolTimeUnit`, `bonusMoreFoundUnit`, `speedFleetFactor`, `production901`, `production902`, `production903`, `production911`, `production921`, `storage901`, `storage902`, `storage903`, `shipclass`, `nodebris`, `carriercapacity`) VALUES
(1, 'metal_mine', 0, '1', 0, 1.50, 255, 60, 15, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, '(30 * $BuildLevel * pow((1.1), $BuildLevel)) * (0.1 * $BuildLevelFactor)', NULL, NULL, '-(10 * $BuildLevel * pow((1.1), $BuildLevel)) * (0.1 * $BuildLevelFactor)', NULL, NULL, NULL, NULL, NULL, 0, NULL),
(2, 'crystal_mine', 0, '1', 0, 1.60, 255, 48, 24, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, '(20 * $BuildLevel * pow((1.1), $BuildLevel)) * (0.1 * $BuildLevelFactor)', NULL, '-(10 * $BuildLevel * pow((1.1), $BuildLevel)) * (0.1 * $BuildLevelFactor);', NULL, NULL, NULL, NULL, NULL, 0, NULL),
(3, 'deuterium_sintetizer', 0, '1', 0, 1.50, 255, 225, 75, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, '(10 * $BuildLevel * pow((1.1), $BuildLevel) * (0.1 * $BuildLevelFactor))', '- (20 * ($BuildLevel) * pow((1.1), ($BuildLevel))) * (0.1 * $BuildLevelFactor)', NULL, NULL, NULL, NULL, NULL, 0, NULL),
(4, 'solar_plant', 0, '1', 0, 1.50, 255, 75, 30, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, '(20 * $BuildLevel * pow((1.1), $BuildLevel)) * (0.1 * $BuildLevelFactor)', NULL, NULL, NULL, NULL, NULL, 0, NULL),
(6, 'university', 0, '1', 0, 2.25, 255, 1000000, 750000, 2000000, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(12, 'fusion_plant', 0, '1', 0, 2.00, 255, 900, 360, 180, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, '- (10 * $BuildLevel * pow(1.1,$BuildLevel) * (0.05 * $BuildLevelFactor)) *(1.05 + $BuildEnergy * 0.04) * $BuildLevel ', '(30 * $BuildLevel * pow((1.05 + $BuildEnergy * 0.01), $BuildLevel)) * (0.1 * $BuildLevelFactor) + (0.1 * $BuildLevelFactor)', NULL, NULL, NULL, NULL, NULL, 0, NULL),
(14, 'robot_factory', 0, '1,3', 0, 2.00, 255, 400, 120, 200, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(15, 'nano_factory', 0, '1', 0, 2.10, 255, 1000000, 500000, 100000, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(21, 'hangar', 0, '1,3', 0, 2.00, 255, 400, 200, 100, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(22, 'metal_store', 0, '1,3', 0, 2.00, 255, 2000, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'floor(2.5 * pow(1.8331954764, $BuildLevel)) * 5000', NULL, NULL, NULL, 0, NULL),
(23, 'crystal_store', 0, '1,3', 0, 2.00, 255, 2000, 1000, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'floor(2.5 * pow(1.8331954764, $BuildLevel)) * 5000', NULL, NULL, 0, NULL),
(24, 'deuterium_store', 0, '1,3', 0, 2.00, 255, 2000, 2000, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'floor(2.5 * pow(1.8331954764, $BuildLevel)) * 5000 ', NULL, 0, NULL),
(31, 'laboratory', 0, '1', 0, 2.00, 255, 200, 400, 200, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(33, 'terraformer', 0, '1', 0, 2.00, 255, 0, 50000, 100000, 1000, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(34, 'ally_deposit', 0, '1', 1, 2.00, 1, 2000, 4000, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(41, 'mondbasis', 0, '3', 0, 2.00, 255, 20000, 40000, 20000, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(42, 'phalanx', 0, '3', 0, 2.00, 255, 20000, 40000, 20000, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(43, 'sprungtor', 0, '3', 0, 2.00, 255, 2000000, 4000000, 2000000, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(44, 'silo', 0, '1', 0, 2.00, 255, 20000, 20000, 1000, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(106, 'spy_tech', 100, '1,3', 0, 2.00, 255, 200, 1000, 200, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(108, 'computer_tech', 100, '1,3', 0, 2.00, 255, 0, 400, 600, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(109, 'military_tech', 100, '1,3', 0, 2.00, 255, 800, 200, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(110, 'defence_tech', 100, '1,3', 0, 2.00, 255, 1000, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(111, 'shield_tech', 100, '1,3', 0, 2.00, 255, 200, 600, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(113, 'energy_tech', 100, '1,3', 0, 2.00, 255, 0, 800, 400, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(114, 'hyperspace_tech', 100, '1,3', 0, 2.00, 255, 0, 4000, 2000, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(115, 'combustion_tech', 100, '1,3', 0, 2.00, 255, 400, 0, 600, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(117, 'impulse_motor_tech', 100, '1,3', 0, 2.00, 255, 2000, 4000, 600, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(118, 'hyperspace_motor_tech', 100, '1,3', 0, 2.00, 255, 10000, 20000, 6000, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(120, 'laser_tech', 100, '1,3', 0, 1.55, 255, 200, 100, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(121, 'ionic_tech', 100, '1,3', 0, 2.00, 255, 1000, 300, 100, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(122, 'buster_tech', 100, '1,3', 0, 2.00, 255, 2000, 4000, 1000, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(123, 'intergalactic_tech', 100, '1,3', 0, 2.00, 255, 240000, 400000, 160000, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(124, 'expedition_tech', 100, '1,3', 0, 2.25, 13, 4000, 8000, 4000, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(131, 'metal_proc_tech', 100, '1,3', 0, 2.00, 255, 750, 500, 250, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(132, 'crystal_proc_tech', 100, '1,3', 0, 2.00, 255, 1000, 750, 500, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(133, 'deuterium_proc_tech', 100, '1,3', 0, 2.00, 255, 1250, 1000, 750, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(134, 'zoulman_tech', 100, '1,3', 0, 2.00, 255, 600000, 800000, 1500000, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(135, 'post_mail', 100, '1,3', 0, 2.00, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(136, 'dm_application', 100, '1,3', 0, 2.00, 255, 25000, 25000, 20000, 15000, 100, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(137, 'potato_crown', 100, '1,3', 0, 2.00, 255, 4500, 3500, 2000, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.05, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(138, 'centralisation', 100, '1,3', 0, 2.00, 255, 4500, 3500, 2000, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.10, 0.10, 0.10, -0.02, -0.02, -0.02, -0.02, 0.10, 0.10, 0.10, 0.10, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(139, 'escalation', 100, '1,3', 0, 2.00, 255, 4500, 3500, 2000, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.10, 0.10, 0.10, 0.00, 0.00, -0.04, 0.00, 0.00, 0.00, 0.00, 0.10, -0.01, 1.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(140, 'exploration_radar', 100, '1,3', 0, 6.00, 7, 5000, 4000, 1500, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(141, 'carried_operations', 100, '1,3', 0, 2.00, 255, 5000, 4000, 1500, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(142, 'quantum_radar', 100, '1,3', 0, 2.00, 1, 50000000, 40000000, 15000000, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(143, 'larrysluckycoin', 100, '1,3', 0, 2.00, 255, 4500, 3500, 2000, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, -0.02, 0.00, 0.00, 0.00, 0.05, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(144, 'profitincrease', 100, '1,3', 0, 2.00, 255, 4500, 3500, 2000, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, -0.04, 0.00, 0.00, 0.00, 0.00, 0.10, -0.01, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(199, 'graviton_tech', 100, '1,3', 0, 3.00, 255, 0, 0, 0, 300000, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(202, 'small_ship_cargo', 200, '1,3', 0, 1.00, NULL, 2000, 2000, 0, 0, 0, 10, 20, 4, 5000, 10000, NULL, NULL, NULL, NULL, 5000, 5, 10, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, ''),
(203, 'big_ship_cargo', 200, '1,3', 0, 1.00, NULL, 6000, 6000, 0, 0, 0, 50, 50, 1, 7500, 7500, NULL, NULL, NULL, NULL, 25000, 5, 25, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, ''),
(204, 'light_hunter', 200, '1,3', 0, 1.00, NULL, 3000, 1000, 0, 0, 0, 20, 20, 1, 12500, 12500, NULL, NULL, NULL, NULL, 50, 50, 10, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 0, NULL),
(205, 'heavy_hunter', 200, '1,3', 0, 1.00, NULL, 6000, 4000, 0, 0, 0, 75, 75, 2, 10000, 15000, NULL, NULL, NULL, NULL, 100, 150, 25, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 0, NULL),
(206, 'crusher', 200, '1,3', 0, 1.00, NULL, 20000, 7000, 2000, 0, 0, 300, 300, 2, 15000, 15000, NULL, NULL, NULL, NULL, 800, 400, 50, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, 0, NULL),
(207, 'battle_ship', 200, '1,3', 0, 1.00, NULL, 45000, 15000, 0, 0, 0, 250, 250, 3, 10000, 10000, NULL, NULL, NULL, NULL, 1500, 1000, 200, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, 0, NULL),
(208, 'colonizer', 200, '1,3', 0, 1.00, NULL, 10000, 20000, 10000, 0, 0, 1000, 1000, 2, 2500, 2500, NULL, NULL, NULL, NULL, 7500, 50, 100, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL),
(209, 'recycler', 200, '1,3', 0, 1.00, NULL, 10000, 6000, 2000, 0, 0, 300, 300, 1, 3000, 3000, NULL, NULL, NULL, NULL, 20000, 1, 10, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, ''),
(210, 'spy_sonde', 200, '1,3', 0, 1.00, NULL, 0, 1000, 0, 0, 0, 1, 1, 1, 100000000, 100000000, NULL, NULL, NULL, NULL, 10, 0, 0, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL),
(211, 'bomber_ship', 200, '1,3', 0, 1.00, NULL, 50000, 25000, 15000, 0, 0, 1000, 1000, 2, 6000, NULL, NULL, NULL, NULL, NULL, 500, 1200, 500, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, 0, NULL),
(212, 'solar_satelit', 200, '1,3', 0, 1.00, NULL, 0, 2000, 500, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, '((($BuildTemp + 160) / 6) * (0.1 * $BuildLevelFactor) * $BuildLevel)', NULL, NULL, NULL, NULL, 3, 0, NULL),
(213, 'destructor', 200, '1,3', 0, 1.00, NULL, 60000, 50000, 15000, 0, 0, 1000, 1000, 3, 6000, 6000, NULL, NULL, NULL, NULL, 2000, 2000, 650, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, 0, NULL),
(214, 'prototype_moon_crusher', 200, '1,3', 0, 1.00, NULL, 50000, 40000, 1000000, 0, 0, 10000, 10000, 3, 200, 200, NULL, NULL, NULL, NULL, 1000000, 200, 500, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 6, 0, NULL),
(215, 'battleship', 200, '1,3', 0, 1.00, NULL, 30000, 40000, 15000, 0, 0, 250, 250, 3, 10000, 10000, NULL, NULL, NULL, NULL, 750, 1500, 400, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, 0, NULL),
(216, 'lune_noir', 200, '1,3', 0, 1.00, NULL, 8000000, 2000000, 1500000, 0, 0, 15000, 15000, 3, 900, 900, NULL, NULL, NULL, NULL, 15000000, 150000, 70000, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 6, 0, NULL),
(217, 'ev_transporter', 200, '1,3', 0, 1.00, NULL, 35000, 20000, 1500, 0, 0, 90, 90, 3, 6000, 6000, NULL, NULL, NULL, NULL, 400000000, 50, 120, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, ''),
(218, 'star_crasher', 200, '1,3', 0, 1.00, NULL, 275000000, 130000000, 60000000, 0, 0, 10000, 10000, 3, 10, 10, NULL, NULL, NULL, NULL, 50000000, 35000000, 2000000, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 6, 0, NULL),
(219, 'giga_recykler', 200, '1,3', 0, 1.00, NULL, 1000000, 600000, 500000, 0, 0, 300, 600, 3, 7500, 7500, NULL, NULL, NULL, NULL, 200000000, 1, 1000, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, ''),
(220, 'dm_ship', 200, '1,3', 0, 1.00, NULL, 150000, 223000, 500000, 0, 0, 10000, 100000, 3, 100, 100, NULL, NULL, NULL, NULL, 6000000, 5, 50000, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL),
(221, 'battleship_two', 200, '1,3', 0, 1.00, NULL, 60000, 30000, 0, 0, 0, 400, 400, 3, 11000, 11000, NULL, NULL, NULL, NULL, 1000, 1300, 600, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, 0, NULL),
(222, 'interceptor', 200, '1,3', 0, 1.00, NULL, 30000, 40000, 40000, 0, 0, 250, 250, 3, 13000, 13000, NULL, NULL, NULL, NULL, 1200, 1800, 800, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, 0, NULL),
(223, 'viauldout', 200, '1,3', 0, 1.00, NULL, 4000, 1500, 1000, 0, 0, 20, 20, 3, 12500, 12500, NULL, NULL, NULL, NULL, 50, 200, 100, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 0, NULL),
(224, 'heavy_cruiser', 200, '1,3', 0, 1.00, NULL, 40000, 14000, 5000, 0, 0, 350, 350, 3, 15000, 15000, NULL, NULL, NULL, NULL, 1000, 700, 400, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, 0, NULL),
(225, 'thanatos_class', 200, '1,3', 0, 1.00, NULL, 5000000, 4000000, 1000000, 0, 0, 10000, 10000, 3, 600, 600, NULL, NULL, NULL, NULL, 1000000, 110000, 50000, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 6, 0, ''),
(226, 'kamikaze_sonde', 200, '1,3', 0, 1.00, NULL, 1, 1, 5000000, 0, 7500000, 5, 5, 3, 10000000, 10000000, NULL, NULL, NULL, NULL, 25, 10000000, 0, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1000, 0, NULL),
(227, 'impulsion_mine', 200, '1,3', 0, 1.00, NULL, 2000, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 120, 0, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 0, NULL),
(228, 'emp_mine', 200, '1,3', 0, 1.00, NULL, 8000, 2000, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 400, 100, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 0, NULL),
(229, 'plasma_mine', 200, '1,3', 0, 1.00, NULL, 65000, 50000, 14500, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 3000, 300, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 0, NULL),
(230, 'graviton_mine', 200, '1,3', 0, 1.00, NULL, 5500000, 850000, 50000, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 80000, 55000, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 0, ''),
(231, 'space_shipyard', 200, '1,3', 1, 1.00, NULL, 5000000, 4500000, 1000000, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 20000, 75000, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, 0, NULL),
(232, 'Daedalus', 200, '1,3', 0, 1.00, NULL, 20000, 30000, 100000, 0, 0, 600, 600, 3, 13000, 13000, NULL, NULL, NULL, NULL, 10000, 800, 2500, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL),
(233, 'edlm', 200, '1,3', 0, 1.00, NULL, 10000000, 4000000, 2000000, 0, 500, 20000, 20000, 3, 1200, 1200, NULL, NULL, NULL, NULL, 25000000, 250000, 150000, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 6, 0, NULL),
(234, 'Epinglaj', 200, '1,3', 0, 1.00, NULL, 4000, 1600, 1750, 0, 1, 20, 20, 3, 23000, 23000, NULL, NULL, NULL, NULL, 200, 250, 250, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 0, NULL),
(235, 'hunter', 200, '1,3', 0, 1.00, NULL, 30000, 42500, 50000, 0, 5, 250, 250, 3, 25000, 25000, NULL, NULL, NULL, NULL, 2000, 2500, 1500, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, 0, NULL),
(236, 'enforcer', 200, '1,3', 0, 1.00, NULL, 65000, 35000, 0, 0, 5, 400, 400, 3, 21000, 21000, NULL, NULL, NULL, NULL, 2200, 1800, 1350, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, 0, NULL),
(237, 'demolisher', 200, '1,3', 0, 1.00, NULL, 70000, 60000, 17000, 0, 10, 1000, 1000, 3, 16000, 16000, NULL, NULL, NULL, NULL, 4000, 2700, 1700, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, 0, NULL),
(238, 'QuatSansDisse', 200, '1,3', 0, 1.00, NULL, 10000000000000, 10000000000000, 0, 0, 0, 1, 1, 3, 4294967295, 4294967295, NULL, NULL, NULL, NULL, 4294967295, 4294967295, 4294967295, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 7, 0, '226,2000'),
(239, 'gilbert', 200, '1,3', 0, 1.00, 25000, 20000, 25000, 5000, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.01, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 7, 0, NULL),
(240, 'essaim', 200, '1,3', 0, 1.00, NULL, 2000, 1000, 0, 0, 0, 20, 20, 1, 14000, 14000, NULL, NULL, NULL, NULL, 50, 40, 25, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 0, NULL),
(241, 'antictransport', 200, '1,3', 0, 1.00, NULL, 2000, 1500, 0, 0, 0, 10, 20, 4, 6000, 12000, NULL, NULL, NULL, NULL, 4000, 0, 28, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL),
(242, 'defender', 200, '1,3', 0, 1.00, NULL, 16000, 7000, 2000, 0, 0, 300, 300, 2, 17000, 17000, NULL, NULL, NULL, NULL, 800, 400, 75, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, 0, NULL),
(243, 'warrior', 200, '1,3', 0, 1.00, NULL, 50000, 30000, 0, 0, 0, 400, 400, 3, 13000, 13000, NULL, NULL, NULL, NULL, 1000, 1000, 800, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, 0, NULL),
(244, 'reckless', 200, '1,3', 0, 1.00, NULL, 10000, 42500, 50000, 0, 5, 250, 250, 3, 27500, 27500, NULL, NULL, NULL, NULL, 2000, 2000, 1200, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, 0, NULL),
(245, 'striker', 200, '1,3', 0, 1.00, NULL, 60000, 60000, 17000, 0, 5, 1000, 1000, 3, 17000, 17000, NULL, NULL, NULL, NULL, 4000, 2300, 2700, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, 0, NULL),
(246, 'overgrowth', 200, '1,3', 0, 1.00, NULL, 7000000, 2000000, 1500000, 0, 0, 15000, 15000, 3, 1500, 1500, NULL, NULL, NULL, NULL, 15000000, 120000, 100000, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 6, 0, NULL),
(247, 'dawn', 200, '1,3', 0, 1.00, NULL, 225000000, 130000000, 60000000, 0, 0, 10000, 10000, 3, 50, 50, NULL, NULL, NULL, NULL, 50000000, 25000000, 3000000, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 6, 0, NULL),
(248, 'arrow', 200, '1,3', 0, 1.00, NULL, 6000, 2000, 0, 0, 0, 20, 20, 1, 16000, 16000, NULL, NULL, NULL, NULL, 75, 100, 25, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 0, NULL),
(249, 'exaltedtransport', 200, '1,3', 0, 1.00, NULL, 5000, 5000, 0, 0, 0, 10, 20, 4, 8000, 16000, NULL, NULL, NULL, NULL, 10000, 10, 100, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL),
(250, 'guardian', 200, '1,3', 0, 1.00, NULL, 35000, 10000, 5000, 0, 0, 350, 350, 3, 19000, 19000, NULL, NULL, NULL, NULL, 1000, 600, 200, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, 0, NULL),
(251, 'blade', 200, '1,3', 0, 1.00, NULL, 80000, 30000, 0, 0, 0, 400, 400, 3, 15000, 15000, NULL, NULL, NULL, NULL, 1000, 1800, 1000, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, 0, NULL),
(252, 'mirage', 200, '1,3', 0, 1.00, NULL, 40000, 42500, 50000, 0, 100, 150, 150, 3, 30000, 30000, NULL, NULL, NULL, NULL, 2000, 2750, 2000, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, 0, NULL),
(253, 'divinehand', 200, '1,3', 0, 1.00, NULL, 90000, 60000, 17000, 0, 100, 1000, 1000, 3, 19000, 19000, NULL, NULL, NULL, NULL, 4000, 3000, 2500, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 5, 0, NULL),
(254, 'avatar', 200, '1,3', 0, 1.00, NULL, 10000000, 2000000, 1500000, 0, 0, 15000, 15000, 3, 2000, 2000, NULL, NULL, NULL, NULL, 15000000, 175000, 110000, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 6, 0, NULL),
(255, 'ragnarok', 200, '1,3', 0, 1.00, NULL, 375000000, 130000000, 60000000, 0, 0, 100, 100, 3, 200, 200, NULL, NULL, NULL, NULL, 50000000, 55000000, 4000000, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 6, 0, NULL),
(256, 'logistics_drone', 200, '1,3', 0, 1.00, NULL, 1, 0, 0, 0, 0, 0, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, 1, 0, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1000, 0, NULL),
(257, 'recycle_drone', 200, '1,3', 0, 1.00, NULL, 1, 0, 0, 0, 0, 0, 0, 1, 0, 0, NULL, NULL, NULL, NULL, 0, 1, 0, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1000, 0, NULL),
(258, 'advanced_recycle_drone', 200, '1,3', 0, 1.00, NULL, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, 0, 2, 0, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1000, 0, NULL),
(259, 'experimental_fighter', 200, '1,3', 0, 1.00, NULL, 1, 0, 0, 0, 0, 10, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, 10, 0, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1000, 0, NULL),
(260, 'air_superiority_fighter', 200, '1,3', 0, 1.00, NULL, 1, 0, 0, 0, 0, 0, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, 20, 5, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1000, 0, NULL),
(261, 'torpedo_fighter', 200, '1,3', 0, 1.00, NULL, 1, 0, 0, 0, 0, 0, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, 150, 5, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1000, 0, NULL),
(262, 'advanced_air_superiority_fighter', 200, '1,3', 0, 1.00, NULL, 1, 0, 0, 0, 0, 0, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, 20, 15, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1000, 0, NULL),
(263, 'advanced_torpedo_fighter', 200, '1,3', 0, 1.00, NULL, 1, 0, 0, 0, 0, 0, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, 250, 15, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1000, 0, NULL),
(264, 'rafale', 200, '1,3', 0, 1.00, NULL, 1, 0, 0, 0, 0, 0, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, 300, 50, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1000, 0, NULL),
(265, 'carried_bomber', 200, '1,3', 0, 1.00, NULL, 1, 0, 0, 0, 0, 0, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, 55, 5, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1000, 0, NULL),
(266, 'experiment_carrier', 200, '1,3', 0, 1.00, NULL, 10000, 5000, 5000, 0, 0, 300, 300, 2, 10000, 15000, NULL, NULL, NULL, NULL, 1000, 10, 25, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, 0, '259,2'),
(267, 'air_superiority_carrier', 200, '1,3', 0, 1.00, NULL, 15000, 7000, 2500, 0, 0, 300, 300, 2, 12000, 15000, NULL, NULL, NULL, NULL, 1000, 15, 25, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, 0, '260,5'),
(268, 'antiship_carrier', 200, '1,3', 0, 1.00, NULL, 15000, 7000, 2500, 0, 0, 300, 300, 2, 12000, 15000, NULL, NULL, NULL, NULL, 1000, 15, 25, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, 0, '261,2'),
(269, 'multi_carrier', 200, '1,3', 0, 1.00, NULL, 14000, 8000, 3000, 0, 0, 300, 300, 2, 12000, 15000, NULL, NULL, NULL, NULL, 1000, 15, 25, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, 0, '260,3;261,3'),
(270, 'advanced_air_superiority_carrier', 200, '1,3', 0, 1.00, NULL, 30000, 20000, 7500, 0, 0, 400, 400, 3, 14000, 15000, NULL, NULL, NULL, NULL, 2500, 20, 250, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, 0, '262,15'),
(271, 'advanced_antiship_carrier', 200, '1,3', 0, 1.00, NULL, 30000, 20000, 7500, 0, 0, 400, 400, 3, 14000, 15000, NULL, NULL, NULL, NULL, 2500, 20, 250, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, 0, '263,10'),
(272, 'advanced_mixed_carrier', 200, '1,3', 0, 1.00, NULL, 27500, 22500, 8000, 0, 0, 400, 400, 3, 14000, 15000, NULL, NULL, NULL, NULL, 2500, 20, 250, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, 0, '262,10;263,5'),
(273, 'omni_carrier', 200, '1,3', 0, 1.00, NULL, 60000, 40000, 10000, 0, 0, 400, 400, 3, 14000, 15000, NULL, NULL, NULL, NULL, 3500, 35, 400, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, 0, '264,5'),
(274, 'fs_r91', 200, '1,3', 0, 1.00, NULL, 5000000, 4000000, 2000000, 0, 0, 10000, 10000, 3, 600, 600, NULL, NULL, NULL, NULL, 1000000, 4000, 25000, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 6, 0, '262,2500;263,7500;264,200;265,250'),
(275, 'viauldout_shipment', 200, '1,3', 0, 1.00, NULL, 4000000, 1500000, 1000000, 0, 0, 20000, 20000, 3, 12500, 12500, NULL, NULL, NULL, NULL, 50000, 0, 0, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1001, 1, '223,1000'),
(276, 'heavy_cruiser_shipment', 200, '1,3', 0, 1.00, NULL, 4000000, 1400000, 500000, 0, 0, 35000, 35000, 3, 15000, 15000, NULL, NULL, NULL, NULL, 100000, 0, 0, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1001, 1, '224,100'),
(277, 'destructor_shipment', 200, '1,3', 0, 1.00, NULL, 1500000, 1250000, 375000, 0, 0, 25000, 25000, 3, 6000, 6000, NULL, NULL, NULL, NULL, 50000, 0, 0, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1001, 1, '213,25'),
(278, 'interceptor_shipment', 200, '1,3', 0, 1.00, NULL, 750000, 1000000, 1000000, 0, 0, 6250, 6250, 3, 13000, 13000, NULL, NULL, NULL, NULL, 30000, 0, 0, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1001, 1, '222,25');
INSERT INTO `%PREFIX%vars` (`elementID`, `name`, `class`, `onPlanetType`, `onePerPlanet`, `factor`, `maxLevel`, `cost901`, `cost902`, `cost903`, `cost911`, `cost921`, `consumption1`, `consumption2`, `speedTech`, `speed1`, `speed2`, `speed2Tech`, `speed2onLevel`, `speed3Tech`, `speed3onLevel`, `capacity`, `attack`, `defend`, `timeBonus`, `bonusAttack`, `bonusDefensive`, `bonusShield`, `bonusBuildTime`, `bonusResearchTime`, `bonusShipTime`, `bonusDefensiveTime`, `bonusResource`, `bonusEnergy`, `bonusResourceStorage`, `bonusShipStorage`, `bonusFlyTime`, `bonusFleetSlots`, `bonusPlanets`, `bonusSpyPower`, `bonusExpedition`, `bonusGateCoolTime`, `bonusMoreFound`, `bonusAttackUnit`, `bonusDefensiveUnit`, `bonusShieldUnit`, `bonusBuildTimeUnit`, `bonusResearchTimeUnit`, `bonusShipTimeUnit`, `bonusDefensiveTimeUnit`, `bonusResourceUnit`, `bonusEnergyUnit`, `bonusResourceStorageUnit`, `bonusShipStorageUnit`, `bonusFlyTimeUnit`, `bonusFleetSlotsUnit`, `bonusPlanetsUnit`, `bonusSpyPowerUnit`, `bonusExpeditionUnit`, `bonusGateCoolTimeUnit`, `bonusMoreFoundUnit`, `speedFleetFactor`, `production901`, `production902`, `production903`, `production911`, `production921`, `storage901`, `storage902`, `storage903`, `shipclass`, `nodebris`, `carriercapacity`) VALUES
(279, 'battleship_two_shipment', 200, '1,3', 0, 1.00, NULL, 3000000, 1500000, 0, 0, 0, 20000, 20000, 3, 11000, 11000, NULL, NULL, NULL, NULL, 50000, 0, 0, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1001, 1, '221,50'),
(401, 'misil_launcher', 400, '1,3', 0, 1.00, NULL, 2000, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 80, 20, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(402, 'small_laser', 400, '1,3', 0, 1.00, NULL, 1500, 500, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 100, 25, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(403, 'big_laser', 400, '1,3', 0, 1.00, NULL, 6000, 2000, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 250, 100, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(404, 'gauss_canyon', 400, '1,3', 0, 1.00, NULL, 20000, 15000, 2000, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 1100, 200, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(405, 'ionic_canyon', 400, '1,3', 0, 1.00, NULL, 2000, 6000, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 50, 500, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(406, 'buster_canyon', 400, '1,3', 0, 1.00, NULL, 50000, 50000, 30000, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 3000, 300, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(407, 'small_protection_shield', 400, '1,3', 1, 1.00, NULL, 10000, 10000, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 1, 200, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(408, 'big_protection_shield', 400, '1,3', 1, 1.00, NULL, 50000, 50000, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 1, 1000, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(409, 'planet_protector', 400, '1,3', 1, 1.00, NULL, 100000000, 50000000, 25000000, 0, 100, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 1, 1000000, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(410, 'graviton_canyon', 400, '1,3', 0, 1.00, NULL, 15000000, 15000000, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 500000, 80000, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(411, 'orbital_station', 400, '1,3', 1, 1.00, NULL, 5000000000, 2000000000, 500000000, 1000000, 100, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 400000000, 2000000000, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(412, 'hoash_shield', 400, '1,3', 1, 1.00, NULL, 200000, 200000, 100000, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 1, 1500, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(413, 'ionic_cannon', 400, '1,3', 0, 1.00, NULL, 600, 7000, 100000, 0, 100, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 250000, 100, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(414, 'ionic_propulsion', 400, '1,3', 0, 1.00, NULL, 5000, 10000, 1000, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 200, 750, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(415, 'ionic_collision', 400, '1,3', 0, 1.00, NULL, 20000, 30000, 5000, 0, 100, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 400, 2000, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(416, 'antiair_cannon', 400, '1,3', 0, 1.00, NULL, 500, 1500, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 20, 40, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(417, 'planetary_hangar', 400, '1,3', 0, 1.00, NULL, 35000, 30000, 10000, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 500, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '262,5;263,5;264,1;'),
(502, 'interceptor_misil', 500, '1,3', 0, 1.00, NULL, 10000, 1000, 2000, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 1, 1, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(503, 'interplanetary_misil', 500, '1,3', 0, 1.00, NULL, 8500, 2500, 5000, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 12000, 1, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(601, 'rpg_geologue', 600, '1,3', 0, 1.00, 20, 0, 0, 0, 0, 100, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.03, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(602, 'rpg_amiral', 600, '1,3', 0, 1.00, 20, 0, 0, 0, 0, 100, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.05, 0.05, 0.05, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(603, 'rpg_ingenieur', 600, '1,3', 0, 1.00, 10, 0, 0, 0, 0, 100, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.05, 0.05, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.05, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(604, 'rpg_technocrate', 600, '1,3', 0, 1.00, 10, 0, 0, 0, 0, 100, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, -0.02, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(605, 'rpg_constructeur', 600, '1,3', 0, 1.00, 5, 0, 0, 0, 0, 100, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, -0.02, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(606, 'rpg_scientifique', 600, '1,3', 0, 1.00, 5, 0, 0, 0, 0, 100, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, -0.05, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(607, 'rpg_stockeur', 600, '1,3', 0, 1.00, 2, 0, 0, 0, 0, 100, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.10, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(608, 'rpg_defenseur', 600, '1,3', 0, 1.00, 20, 0, 0, 0, 0, 100, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, -0.03, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(609, 'rpg_bunker', 600, '1,3', 0, 1.00, 1, 0, 0, 0, 0, 100, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(610, 'rpg_espion', 600, '1,3', 0, 1.00, 10, 0, 0, 0, 0, 100, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.07, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(611, 'rpg_commandant', 600, '1,3', 0, 1.00, 10, 0, 0, 0, 0, 100, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(612, 'rpg_destructeur', 600, '1,3', 0, 1.00, 1, 0, 0, 0, 0, 100, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.05, 0.05, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(613, 'rpg_general', 600, '1,3', 0, 1.00, 3, 0, 0, 0, 0, 100, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, -0.03, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(614, 'rpg_raideur', 600, '1,3', 0, 1.00, 1, 0, 0, 0, 0, 100, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(615, 'rpg_empereur', 600, '1,3', 0, 1.00, 1, 0, 0, 0, 0, 100, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(616, 'rpg_horus', 600, '1,3', 0, 1.00, 1, 0, 0, 0, 0, 100, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.25, 0.25, 0.25, 0.00, 0.00, 0.00, 0.00, 0.25, 0.00, 0.00, 0.00, -0.25, 15.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 15, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(651, 'admin', 600, '1,3', 0, 1.00, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(652, 'class_explorer', 600, '1,3', 0, 1.00, 1, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(653, 'class_raider', 600, '1,3', 0, 1.00, 1, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(654, 'class_miner', 600, '1,3', 0, 1.00, 1, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(655, 'class_lone', 600, '1,3', 0, 1.00, 1, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(656, 'class_merchant', 600, '1,3', 0, 1.00, 1, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(657, 'npc_class_pirate', 600, '1,3', 0, 1.00, 1, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(658, 'class_ability_bruiser', 600, '1,3', 0, 2.00, 5, 0, 0, 0, 0, 100, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.10, 0.10, 0.10, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(659, 'class_ability_engineer', 600, '1,3', 0, 2.00, 5, 0, 0, 0, 0, 100, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(660, 'class_ability_lucky', 600, '1,3', 0, 2.00, 5, 0, 0, 0, 0, 100, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(661, 'class_ability_electrician', 600, '1,3', 0, 2.00, 5, 0, 0, 0, 0, 100, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.50, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(662, 'class_ability_researcher', 600, '1,3', 0, 2.00, 5, 0, 0, 0, 0, 100, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(663, 'class_ability_experimentor', 600, '1,3', 0, 2.00, 4, 0, 0, 0, 0, 200, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(664, 'class_ability_potato_crown', 600, '1,3', 0, 2.00, 5, 0, 0, 0, 0, 100, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.10, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(665, 'class_ability_negociator', 600, '1,3', 0, 2.00, 5, 0, 0, 0, 0, 100, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(666, 'class_ability_lunar', 600, '1,3', 0, 2.00, 5, 0, 0, 0, 0, 100, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(701, 'dm_attack', 700, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 100, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 86400, 0.25, 0.25, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(702, 'dm_defensive', 700, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 100, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 86400, 0.00, 0.00, 0.25, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(703, 'dm_buildtime', 700, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 100, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 86400, 0.00, 0.00, 0.00, -0.25, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(704, 'dm_resource', 700, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 100, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 86400, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.25, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(705, 'dm_energie', 700, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 100, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 86400, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.25, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(706, 'dm_researchtime', 700, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 100, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 86400, 0.00, 0.00, 0.00, 0.00, -0.25, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(707, 'dm_fleettime', 700, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 100, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 86400, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, -0.25, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1111, 'achievements_mines1', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.01, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1112, 'achievements_mines2', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.02, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1113, 'achievements_mines3', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.05, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1114, 'achievements_mines4', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.10, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1115, 'achievements_tech1', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.01, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1116, 'achievements_tech2', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.02, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1117, 'achievements_tech3', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.05, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1118, 'achievements_tech4', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.10, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1119, 'achievements_engine1', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, -0.01, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1120, 'achievements_engine2', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, -0.02, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1121, 'achievements_engine3', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, -0.03, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1122, 'achievements_engine4', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, -0.04, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1123, 'achievements_colonization1', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, -0.01, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1124, 'achievements_colonization2', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, -0.02, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1125, 'achievements_colonization3', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1126, 'achievements_moon1', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.01, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1127, 'achievements_moon2', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.02, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1128, 'achievements_moon3', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1129, 'achievements_war1', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.02, 0.02, 0.02, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1130, 'achievements_war2', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.05, 0.05, 0.05, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1131, 'achievements_war3', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.10, 0.10, 0.10, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1132, 'achievements_destroy1', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, -0.01, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1133, 'achievements_destroy2', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, -0.02, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1134, 'achievements_destroy3', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, -0.03, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1135, 'achievements_destroy4', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, -0.04, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1136, 'achievements_time1', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, -0.05, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1137, 'achievements_time2', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, -0.05, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1138, 'achievements_time3', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, -0.05, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1139, 'achievements_storage1', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.01, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1140, 'achievements_storage2', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.02, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1141, 'achievements_storage3', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.05, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1142, 'achievements_storage4', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.10, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1143, 'achievements_community2', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, -0.01, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1144, 'achievements_community3', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.01, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1145, 'achievements_fleet1', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, -0.02, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1146, 'achievements_fleet2', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, -0.01, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1147, 'achievements_fleet3', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, -0.01, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1148, 'achievements_fleet4', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, -0.01, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1149, 'achievements_darkmatter1', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.01, 0.01, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1150, 'achievements_darkmatter2', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.02, 0.02, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1151, 'achievements_darkmatter3', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.05, 0.05, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1152, 'achievements_darkmatter4', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.10, 0.10, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1153, 'achievements_planet1', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.01, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1154, 'achievements_planet2', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.02, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1155, 'achievements_planet3', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.05, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1156, 'achievements_planet4', 800, '1,3', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.10, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1157, 'achievements_lab1', 800, '', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, -0.03, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1158, 'achievements_lab2', 800, '', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, -0.03, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1159, 'achievements_lab3', 800, '', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 0.00, -0.03, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(1160, 'achievements_lab4', 800, '', 0, 1.00, NULL, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, -0.05, -0.05, -0.05, -0.05, 0.05, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%vars_lock`
--

CREATE TABLE `%PREFIX%vars_lock` (
  `elementID` int(11) NOT NULL,
  `lockID` int(11) NOT NULL,
  `locked` int(11) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `%PREFIX%vars_lock`
--

INSERT INTO `%PREFIX%vars_lock` (`elementID`, `lockID`, `locked`) VALUES
(652, 653, 1),
(652, 654, 1),
(652, 655, 1),
(653, 652, 1),
(653, 654, 1),
(653, 655, 1),
(654, 652, 1),
(654, 653, 1),
(654, 655, 1),
(655, 652, 1),
(655, 653, 1),
(655, 654, 1),
(232, 653, 1),
(232, 654, 1),
(232, 655, 1),
(239, 652, 1),
(239, 653, 1),
(239, 654, 1),
(208, 655, 1),
(615, 616, 1),
(616, 615, 1),
(240, 21, 1),
(241, 21, 1),
(242, 21, 1),
(242, 21, 1),
(243, 21, 1),
(244, 21, 1),
(245, 21, 1),
(246, 21, 1),
(247, 21, 1),
(248, 21, 1),
(249, 21, 1),
(250, 21, 1),
(251, 21, 1),
(252, 21, 1),
(253, 21, 1),
(254, 21, 1),
(255, 21, 1),
(250, 21, 1),
(251, 21, 1),
(250, 21, 1),
(251, 21, 1),
(256, 21, 1),
(257, 21, 1),
(258, 21, 1),
(259, 21, 1),
(260, 21, 1),
(261, 21, 1),
(262, 21, 1),
(263, 21, 1),
(264, 21, 1),
(265, 21, 1),
(652, 656, 1),
(653, 656, 1),
(654, 656, 1),
(655, 656, 1),
(656, 652, 1),
(656, 653, 1),
(656, 654, 1),
(656, 655, 1),
(658, 659, 1),
(658, 660, 1),
(659, 658, 1),
(659, 660, 1),
(660, 658, 1),
(660, 659, 1),
(661, 662, 1),
(661, 663, 1),
(662, 661, 1),
(662, 663, 1),
(663, 661, 1),
(663, 662, 1),
(664, 665, 1),
(664, 666, 1),
(665, 664, 1),
(665, 666, 1),
(666, 664, 1),
(666, 665, 1);

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%vars_rapidfire`
--

CREATE TABLE `%PREFIX%vars_rapidfire` (
  `elementID` int(11) NOT NULL,
  `rapidfireID` int(11) NOT NULL,
  `shoots` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

--
-- Déchargement des données de la table `%PREFIX%vars_rapidfire`
--

INSERT INTO `%PREFIX%vars_rapidfire` (`elementID`, `rapidfireID`, `shoots`) VALUES
(202, 210, 5),
(202, 212, 5),
(203, 210, 5),
(203, 212, 5),
(204, 210, 5),
(204, 212, 5),
(205, 202, 3),
(205, 210, 5),
(205, 212, 5),
(206, 204, 6),
(206, 401, 10),
(206, 210, 5),
(206, 212, 5),
(207, 210, 5),
(207, 212, 5),
(208, 210, 5),
(208, 212, 5),
(209, 210, 5),
(209, 212, 5),
(265, 210, 5),
(265, 212, 5),
(265, 401, 20),
(265, 402, 20),
(405, 204, 5),
(265, 405, 10),
(213, 210, 5),
(213, 212, 5),
(213, 215, 2),
(213, 402, 10),
(225, 210, 1250),
(225, 212, 1250),
(225, 202, 250),
(225, 203, 250),
(225, 208, 250),
(225, 209, 250),
(225, 204, 200),
(225, 205, 100),
(225, 206, 33),
(225, 207, 30),
(225, 211, 25),
(225, 215, 15),
(225, 213, 5),
(225, 401, 200),
(225, 402, 200),
(225, 403, 100),
(225, 404, 50),
(225, 405, 100),
(215, 202, 3),
(215, 203, 3),
(215, 205, 4),
(215, 206, 4),
(215, 207, 10),
(215, 210, 5),
(215, 212, 5),
(216, 210, 1250),
(216, 212, 1250),
(216, 202, 250),
(216, 203, 250),
(216, 204, 200),
(216, 205, 100),
(216, 206, 33),
(216, 207, 30),
(216, 208, 250),
(216, 209, 250),
(216, 211, 25),
(216, 213, 5),
(216, 225, 2),
(216, 215, 15),
(216, 401, 400),
(216, 402, 200),
(216, 403, 100),
(216, 404, 50),
(216, 405, 100),
(216, 414, 25),
(216, 415, 5),
(217, 210, 5),
(217, 212, 5),
(218, 210, 1250),
(218, 212, 1250),
(218, 202, 250),
(218, 203, 250),
(218, 204, 200),
(218, 205, 100),
(218, 206, 33),
(218, 207, 30),
(218, 208, 250),
(218, 209, 250),
(218, 211, 25),
(218, 213, 5),
(218, 215, 15),
(218, 401, 400),
(218, 402, 200),
(218, 403, 100),
(218, 404, 50),
(218, 405, 100),
(219, 210, 5),
(219, 212, 5),
(220, 210, 5),
(220, 212, 5),
(221, 210, 5),
(221, 212, 5),
(225, 221, 30),
(216, 221, 30),
(218, 221, 30),
(222, 210, 20),
(222, 212, 20),
(222, 215, 10),
(222, 221, 5),
(222, 204, 5),
(222, 205, 5),
(222, 206, 5),
(213, 222, 5),
(225, 222, 20),
(216, 222, 20),
(218, 222, 20),
(223, 210, 10),
(223, 212, 10),
(223, 204, 4),
(223, 206, 5),
(222, 223, 10),
(225, 223, 100),
(216, 223, 100),
(218, 223, 100),
(224, 204, 12),
(224, 401, 15),
(224, 210, 10),
(224, 212, 10),
(224, 206, 5),
(224, 223, 6),
(222, 224, 5),
(225, 224, 30),
(216, 224, 30),
(218, 224, 30),
(226, 202, 1000),
(226, 203, 1000),
(226, 204, 1000),
(226, 205, 1000),
(226, 206, 1000),
(226, 207, 1000),
(226, 208, 1000),
(226, 209, 1000),
(226, 210, 1000),
(226, 211, 1000),
(226, 212, 1000),
(226, 213, 1000),
(226, 214, 1000),
(226, 215, 1000),
(226, 216, 10),
(226, 217, 1000),
(226, 218, 10),
(226, 219, 1000),
(226, 220, 1000),
(226, 221, 1000),
(226, 222, 1000),
(226, 223, 1000),
(226, 224, 1000),
(226, 225, 10),
(226, 226, 1),
(225, 226, 1),
(224, 226, 1),
(223, 226, 1),
(222, 226, 1),
(221, 226, 1),
(220, 226, 1),
(219, 226, 1),
(218, 226, 1),
(217, 226, 1),
(216, 226, 1),
(215, 226, 1),
(214, 226, 1),
(213, 226, 1),
(212, 226, 1),
(265, 226, 1),
(210, 226, 1),
(209, 226, 1),
(208, 226, 1),
(207, 226, 1),
(206, 226, 1),
(205, 226, 1),
(204, 226, 1),
(203, 226, 1),
(202, 226, 1),
(231, 204, 500),
(231, 205, 500),
(231, 206, 50),
(231, 210, 1250),
(231, 211, 10),
(216, 231, 10),
(218, 231, 10),
(225, 231, 10),
(238, 202, 1000000),
(238, 203, 1000000),
(238, 204, 1000000),
(238, 205, 1000000),
(238, 206, 1000000),
(238, 207, 1000000),
(238, 208, 1000000),
(238, 209, 1000000),
(238, 210, 1000000),
(238, 211, 1000000),
(238, 212, 1000000),
(238, 213, 1000000),
(238, 214, 1000000),
(238, 215, 1000000),
(238, 216, 1000000),
(238, 217, 1000000),
(238, 218, 1000000),
(238, 219, 1000000),
(238, 220, 1000000),
(238, 221, 1000000),
(238, 222, 1000000),
(238, 223, 1000000),
(238, 224, 1000000),
(238, 225, 1000000),
(238, 226, 1000000),
(238, 231, 1000000),
(238, 232, 1000000),
(238, 401, 1000000),
(238, 402, 1000000),
(238, 403, 1000000),
(238, 404, 1000000),
(238, 405, 1000000),
(238, 406, 1000000),
(238, 407, 1000000),
(238, 408, 1000000),
(238, 409, 1000000),
(238, 410, 1000000),
(238, 411, 1000000),
(238, 412, 1000000),
(238, 413, 1000000),
(265, 413, 5),
(227, 210, 5),
(227, 226, 1),
(206, 227, 6),
(225, 227, 200),
(216, 227, 200),
(218, 227, 200),
(222, 227, 5),
(224, 227, 12),
(226, 227, 1000),
(231, 227, 500),
(238, 227, 1000000),
(228, 226, 1),
(213, 228, 2),
(225, 228, 80),
(216, 228, 80),
(218, 228, 80),
(222, 228, 10),
(226, 228, 1000),
(238, 228, 1000000),
(229, 210, 5),
(229, 226, 1),
(226, 229, 1000),
(238, 229, 1000000),
(230, 210, 1250),
(230, 202, 250),
(230, 203, 250),
(230, 208, 250),
(230, 209, 250),
(230, 204, 200),
(230, 205, 100),
(230, 206, 33),
(230, 207, 30),
(230, 211, 25),
(230, 215, 15),
(230, 213, 5),
(230, 401, 200),
(230, 402, 200),
(230, 403, 100),
(230, 404, 50),
(230, 405, 100),
(230, 221, 30),
(230, 222, 20),
(230, 223, 100),
(230, 224, 30),
(230, 226, 1),
(230, 231, 10),
(216, 230, 2),
(226, 230, 10),
(238, 230, 1000000),
(221, 204, 4),
(221, 205, 4),
(221, 206, 4),
(265, 404, 10),
(265, 407, 10),
(265, 408, 10),
(265, 412, 7),
(234, 210, 10),
(234, 212, 10),
(234, 204, 10),
(234, 205, 10),
(234, 223, 2),
(234, 206, 5),
(234, 226, 1),
(234, 227, 4),
(222, 234, 5),
(215, 234, 2),
(225, 234, 75),
(216, 234, 100),
(218, 234, 100),
(224, 234, 6),
(226, 234, 1000),
(238, 234, 1000000),
(235, 210, 20),
(235, 212, 20),
(235, 215, 10),
(235, 221, 10),
(235, 207, 10),
(235, 204, 5),
(235, 205, 5),
(235, 206, 5),
(235, 223, 15),
(235, 224, 5),
(235, 226, 1),
(235, 227, 10),
(235, 228, 15),
(235, 234, 10),
(213, 235, 2),
(225, 235, 20),
(216, 235, 20),
(218, 235, 20),
(226, 235, 1000),
(238, 235, 1000000),
(230, 235, 20),
(236, 210, 5),
(236, 212, 5),
(236, 226, 1),
(236, 204, 4),
(236, 205, 4),
(236, 206, 4),
(226, 236, 1000),
(238, 236, 1000000),
(230, 236, 30),
(235, 236, 5),
(225, 236, 20),
(216, 236, 30),
(218, 236, 30),
(222, 236, 2),
(237, 210, 5),
(237, 212, 5),
(237, 215, 10),
(237, 402, 10),
(237, 222, 10),
(237, 226, 1),
(237, 228, 2),
(237, 235, 5),
(225, 237, 2),
(216, 237, 5),
(218, 237, 5),
(226, 237, 1000),
(238, 237, 1000000),
(230, 237, 5),
(230, 234, 75),
(405, 205, 2),
(414, 204, 10),
(414, 205, 5),
(414, 223, 2),
(415, 204, 20),
(415, 205, 10),
(415, 223, 5),
(415, 234, 2),
(238, 414, 1000000),
(233, 228, 15),
(233, 230, 2),
(233, 234, 100),
(233, 235, 20),
(233, 236, 30),
(233, 237, 5),
(233, 210, 1250),
(233, 212, 1250),
(233, 202, 250),
(233, 203, 250),
(233, 204, 200),
(233, 205, 100),
(233, 206, 33),
(233, 207, 30),
(233, 208, 250),
(233, 209, 250),
(233, 211, 25),
(233, 213, 5),
(233, 225, 2),
(233, 215, 15),
(233, 401, 400),
(233, 402, 200),
(233, 403, 100),
(233, 404, 50),
(233, 405, 100),
(233, 414, 50),
(233, 415, 25),
(233, 216, 2),
(233, 221, 30),
(233, 222, 20),
(233, 223, 100),
(233, 224, 30),
(226, 233, 10),
(233, 226, 1),
(233, 231, 10),
(238, 233, 1000000),
(233, 227, 200),
(238, 415, 1000000),
(204, 239, 5),
(205, 239, 5),
(206, 239, 10),
(207, 239, 10),
(265, 239, 15),
(213, 239, 15),
(215, 239, 15),
(216, 239, 20),
(218, 239, 100),
(221, 239, 15),
(22, 239, 30),
(223, 239, 10),
(224, 239, 20),
(225, 239, 100),
(226, 239, 1000),
(239, 226, 1000),
(233, 239, 100),
(234, 239, 20),
(235, 239, 25),
(236, 239, 30),
(237, 239, 40),
(238, 239, 1000000),
(215, 211, 5),
(222, 211, 10),
(235, 211, 25),
(241, 210, 5),
(241, 210, 5),
(241, 212, 5),
(205, 241, 3),
(225, 241, 250),
(215, 241, 3),
(216, 241, 250),
(218, 241, 250),
(226, 241, 1000),
(241, 226, 1),
(238, 241, 1000000),
(230, 241, 250),
(233, 241, 250),
(249, 210, 5),
(249, 212, 5),
(205, 249, 3),
(225, 249, 250),
(215, 249, 3),
(216, 249, 250),
(218, 249, 250),
(226, 249, 1000),
(249, 226, 1),
(238, 249, 1000000),
(230, 249, 250),
(233, 249, 250),
(240, 210, 5),
(240, 212, 5),
(206, 240, 6),
(405, 240, 5),
(225, 240, 200),
(216, 240, 200),
(218, 240, 200),
(222, 240, 5),
(223, 240, 4),
(224, 240, 12),
(226, 240, 1000),
(240, 226, 1),
(231, 240, 500),
(238, 240, 1000000),
(230, 240, 200),
(221, 240, 4),
(234, 240, 10),
(235, 240, 5),
(236, 240, 4),
(414, 240, 10),
(415, 240, 20),
(233, 240, 200),
(240, 239, 5),
(248, 210, 5),
(248, 204, 5),
(248, 205, 2),
(248, 212, 5),
(206, 248, 6),
(405, 248, 5),
(225, 248, 200),
(216, 248, 200),
(218, 248, 200),
(222, 248, 5),
(223, 248, 4),
(224, 248, 12),
(226, 248, 1000),
(248, 226, 1),
(231, 248, 500),
(238, 248, 1000000),
(230, 248, 200),
(221, 248, 4),
(234, 248, 10),
(235, 248, 5),
(236, 248, 4),
(414, 248, 10),
(415, 248, 20),
(233, 248, 200),
(248, 239, 5),
(242, 204, 12),
(242, 401, 15),
(242, 210, 10),
(242, 212, 10),
(242, 223, 6),
(222, 242, 5),
(225, 242, 30),
(216, 242, 30),
(218, 242, 30),
(226, 242, 1000),
(242, 226, 1),
(238, 242, 1000000),
(242, 227, 12),
(230, 242, 30),
(242, 234, 6),
(235, 242, 5),
(233, 242, 30),
(242, 239, 20),
(242, 239, 20),
(242, 239, 20),
(242, 240, 12),
(242, 248, 12),
(250, 204, 12),
(250, 401, 15),
(250, 210, 10),
(250, 212, 10),
(250, 206, 5),
(250, 223, 6),
(222, 250, 5),
(225, 250, 30),
(216, 250, 30),
(218, 250, 30),
(226, 250, 1000),
(250, 226, 1),
(238, 250, 1000000),
(250, 227, 12),
(230, 250, 30),
(250, 234, 6),
(235, 250, 5),
(233, 250, 30),
(250, 239, 20),
(250, 239, 20),
(250, 239, 20),
(250, 240, 12),
(250, 248, 12),
(243, 210, 5),
(243, 212, 5),
(243, 226, 1),
(243, 204, 4),
(243, 205, 4),
(243, 206, 4),
(226, 243, 1000),
(238, 243, 1000000),
(230, 243, 30),
(235, 243, 5),
(225, 243, 20),
(216, 243, 30),
(218, 243, 30),
(222, 243, 2),
(233, 243, 30),
(243, 239, 30),
(243, 240, 4),
(243, 248, 4),
(251, 210, 5),
(251, 212, 5),
(251, 226, 1),
(251, 204, 4),
(251, 205, 4),
(251, 206, 4),
(226, 251, 1000),
(238, 251, 1000000),
(230, 251, 30),
(235, 251, 5),
(225, 251, 20),
(216, 251, 30),
(218, 251, 30),
(222, 251, 2),
(233, 251, 30),
(251, 239, 30),
(251, 240, 4),
(251, 248, 4),
(244, 210, 20),
(244, 212, 20),
(244, 215, 10),
(244, 221, 10),
(244, 207, 10),
(244, 204, 5),
(244, 205, 5),
(244, 206, 5),
(244, 223, 15),
(244, 224, 5),
(244, 226, 1),
(244, 227, 10),
(244, 228, 15),
(244, 234, 10),
(213, 244, 2),
(225, 244, 20),
(216, 244, 20),
(218, 244, 20),
(226, 244, 1000),
(238, 244, 1000000),
(230, 244, 20),
(244, 236, 5),
(237, 244, 5),
(233, 244, 20),
(244, 239, 25),
(244, 211, 25),
(244, 240, 5),
(244, 248, 5),
(244, 242, 5),
(244, 250, 5),
(244, 243, 5),
(244, 251, 5),
(252, 210, 20),
(252, 212, 20),
(252, 215, 10),
(252, 221, 10),
(252, 207, 10),
(252, 204, 5),
(252, 205, 5),
(252, 206, 5),
(252, 223, 15),
(252, 224, 5),
(252, 226, 1),
(252, 227, 10),
(252, 228, 15),
(252, 234, 10),
(213, 252, 2),
(225, 252, 20),
(216, 252, 20),
(218, 252, 20),
(226, 252, 1000),
(238, 252, 1000000),
(230, 252, 20),
(252, 236, 5),
(237, 252, 5),
(233, 252, 20),
(252, 239, 25),
(252, 211, 25),
(252, 240, 5),
(252, 248, 5),
(252, 242, 5),
(252, 250, 5),
(252, 243, 5),
(252, 251, 5),
(245, 210, 5),
(245, 212, 5),
(245, 215, 10),
(245, 402, 10),
(245, 222, 10),
(245, 226, 1),
(245, 228, 2),
(245, 235, 5),
(225, 245, 2),
(216, 245, 5),
(218, 245, 5),
(226, 245, 1000),
(238, 245, 1000000),
(230, 245, 5),
(233, 245, 5),
(245, 239, 40),
(245, 244, 5),
(245, 252, 5),
(253, 210, 5),
(253, 212, 5),
(253, 215, 10),
(253, 402, 10),
(253, 222, 10),
(253, 226, 1),
(253, 228, 2),
(253, 235, 5),
(225, 253, 2),
(216, 253, 5),
(218, 253, 5),
(226, 253, 1000),
(238, 253, 1000000),
(230, 253, 5),
(233, 253, 5),
(253, 239, 40),
(253, 244, 5),
(253, 252, 5),
(246, 210, 1250),
(246, 212, 1250),
(246, 202, 250),
(246, 203, 250),
(246, 204, 200),
(246, 205, 100),
(246, 206, 33),
(246, 207, 30),
(246, 208, 250),
(246, 209, 250),
(246, 211, 25),
(246, 213, 5),
(246, 225, 2),
(246, 215, 15),
(246, 401, 400),
(246, 402, 200),
(246, 403, 100),
(246, 404, 50),
(246, 405, 100),
(246, 414, 25),
(246, 415, 5),
(246, 221, 30),
(246, 222, 20),
(246, 223, 100),
(246, 224, 30),
(226, 246, 10),
(246, 226, 1),
(246, 231, 10),
(238, 246, 1000000),
(246, 227, 200),
(246, 228, 80),
(246, 230, 2),
(246, 234, 100),
(246, 235, 20),
(246, 236, 30),
(246, 237, 5),
(233, 246, 2),
(246, 239, 20),
(246, 241, 250),
(246, 249, 250),
(246, 240, 200),
(246, 248, 200),
(246, 242, 30),
(246, 250, 30),
(246, 243, 30),
(246, 251, 30),
(246, 244, 20),
(246, 252, 20),
(246, 245, 5),
(246, 253, 5),
(254, 210, 1250),
(254, 212, 1250),
(254, 202, 250),
(254, 203, 250),
(254, 204, 200),
(254, 205, 100),
(254, 206, 33),
(254, 207, 30),
(254, 208, 250),
(254, 209, 250),
(254, 211, 25),
(254, 213, 5),
(254, 225, 2),
(254, 215, 15),
(254, 401, 400),
(254, 402, 200),
(254, 403, 100),
(254, 404, 50),
(254, 405, 100),
(254, 414, 25),
(254, 415, 5),
(254, 221, 30),
(254, 222, 20),
(254, 223, 100),
(254, 224, 30),
(226, 254, 10),
(254, 226, 1),
(254, 231, 10),
(238, 254, 1000000),
(254, 227, 200),
(254, 228, 80),
(254, 230, 2),
(254, 234, 100),
(254, 235, 20),
(254, 236, 30),
(254, 237, 5),
(233, 254, 2),
(254, 239, 20),
(254, 241, 250),
(254, 249, 250),
(254, 240, 200),
(254, 248, 200),
(254, 242, 30),
(254, 250, 30),
(254, 243, 30),
(254, 251, 30),
(254, 244, 20),
(254, 252, 20),
(254, 245, 5),
(254, 253, 5),
(247, 210, 1250),
(247, 212, 1250),
(247, 202, 250),
(247, 203, 250),
(247, 204, 200),
(247, 205, 100),
(247, 206, 33),
(247, 207, 30),
(247, 208, 250),
(247, 209, 250),
(247, 211, 25),
(247, 213, 5),
(247, 215, 15),
(247, 401, 400),
(247, 402, 200),
(247, 403, 100),
(247, 404, 50),
(247, 405, 100),
(247, 221, 30),
(247, 222, 20),
(247, 223, 100),
(247, 224, 30),
(226, 247, 10),
(247, 226, 1),
(247, 231, 10),
(238, 247, 1000000),
(247, 227, 200),
(247, 228, 80),
(247, 234, 100),
(247, 235, 20),
(247, 236, 30),
(247, 237, 5),
(247, 239, 100),
(247, 241, 250),
(247, 249, 250),
(247, 240, 200),
(247, 248, 200),
(247, 242, 30),
(247, 250, 30),
(247, 243, 30),
(247, 251, 30),
(247, 244, 20),
(247, 252, 20),
(247, 245, 5),
(247, 253, 5),
(255, 210, 1250),
(255, 212, 1250),
(255, 202, 250),
(255, 203, 250),
(255, 204, 200),
(255, 205, 100),
(255, 206, 33),
(255, 207, 30),
(255, 208, 250),
(255, 209, 250),
(255, 211, 25),
(255, 213, 5),
(255, 215, 15),
(255, 401, 400),
(255, 402, 200),
(255, 403, 100),
(255, 404, 50),
(255, 405, 100),
(255, 221, 30),
(255, 222, 20),
(255, 223, 100),
(255, 224, 30),
(226, 255, 10),
(255, 226, 1),
(255, 231, 10),
(238, 255, 1000000),
(255, 227, 200),
(255, 228, 80),
(255, 234, 100),
(255, 235, 20),
(255, 236, 30),
(255, 237, 5),
(255, 239, 100),
(255, 241, 250),
(255, 249, 250),
(255, 240, 200),
(255, 248, 200),
(255, 242, 30),
(255, 250, 30),
(255, 243, 30),
(255, 251, 30),
(255, 244, 20),
(255, 252, 20),
(255, 245, 5),
(255, 253, 5),
(265, 403, 10),
(211, 210, 5),
(211, 212, 5),
(211, 239, 15),
(260, 256, 2),
(260, 257, 2),
(260, 258, 2),
(260, 259, 2),
(260, 260, 2),
(260, 261, 2),
(260, 262, 2),
(260, 263, 2),
(260, 265, 2),
(262, 256, 4),
(262, 257, 4),
(262, 258, 4),
(262, 259, 4),
(262, 260, 4),
(262, 261, 4),
(262, 262, 2),
(262, 263, 4),
(262, 264, 2),
(262, 265, 4),
(264, 256, 5),
(264, 257, 5),
(264, 258, 5),
(264, 259, 5),
(264, 260, 5),
(264, 261, 5),
(264, 262, 5),
(264, 263, 5),
(264, 264, 3),
(264, 265, 5),
(206, 256, 2),
(206, 257, 2),
(206, 258, 2),
(206, 259, 2),
(206, 260, 2),
(206, 261, 2),
(206, 262, 2),
(206, 263, 2),
(206, 264, 2),
(206, 265, 2),
(224, 256, 5),
(224, 257, 5),
(224, 258, 5),
(224, 259, 5),
(224, 260, 5),
(224, 261, 5),
(224, 262, 5),
(224, 263, 5),
(224, 264, 5),
(224, 265, 5),
(238, 256, 1000000),
(238, 257, 1000000),
(238, 258, 1000000),
(238, 259, 1000000),
(238, 260, 1000000),
(238, 261, 1000000),
(238, 262, 1000000),
(238, 263, 1000000),
(238, 264, 1000000),
(238, 265, 1000000),
(228, 256, 2),
(228, 257, 2),
(228, 258, 2),
(228, 259, 2),
(228, 260, 2),
(228, 261, 2),
(228, 262, 2),
(228, 263, 2),
(228, 264, 2),
(228, 265, 2),
(207, 256, 5),
(207, 257, 5),
(207, 258, 4),
(207, 259, 4),
(207, 260, 4),
(207, 261, 2),
(207, 262, 2),
(207, 263, 2),
(207, 264, 2),
(207, 265, 4),
(416, 256, 25),
(416, 257, 25),
(416, 258, 20),
(416, 259, 20),
(416, 260, 20),
(416, 261, 15),
(416, 262, 15),
(416, 263, 15),
(416, 264, 15),
(416, 265, 10),
(221, 256, 10),
(221, 257, 10),
(221, 258, 10),
(221, 259, 8),
(221, 260, 8),
(221, 261, 8),
(221, 262, 5),
(221, 263, 5),
(221, 264, 2),
(221, 265, 4),
(204, 256, 5),
(204, 257, 5),
(204, 258, 5),
(204, 259, 2),
(204, 260, 2),
(204, 261, 2),
(204, 262, 2),
(204, 263, 2),
(204, 264, 2),
(204, 265, 2),
(205, 256, 5),
(205, 257, 5),
(205, 258, 5),
(205, 259, 2),
(205, 260, 2),
(205, 261, 2),
(205, 262, 2),
(205, 263, 2),
(205, 264, 2),
(205, 265, 2),
(240, 256, 5),
(240, 257, 5),
(240, 258, 5),
(240, 259, 5),
(240, 260, 5),
(240, 261, 5),
(240, 262, 2),
(240, 263, 2),
(240, 264, 2),
(240, 265, 2),
(248, 256, 5),
(248, 257, 5),
(248, 258, 5),
(248, 259, 5),
(248, 260, 5),
(248, 261, 5),
(248, 262, 2),
(248, 263, 2),
(248, 264, 2),
(248, 265, 2),
(234, 256, 5),
(234, 257, 5),
(234, 258, 5),
(234, 259, 5),
(234, 260, 5),
(234, 261, 5),
(234, 262, 2),
(234, 263, 2),
(234, 264, 2),
(234, 265, 2),
(223, 256, 5),
(223, 257, 5),
(223, 258, 5),
(223, 259, 5),
(223, 260, 5),
(223, 261, 5),
(223, 262, 2),
(223, 263, 2),
(223, 264, 2),
(223, 265, 2),
(141, 256, 10),
(141, 257, 10),
(141, 258, 10),
(141, 259, 10),
(141, 260, 10),
(141, 261, 10),
(141, 262, 5),
(141, 263, 5),
(141, 264, 2),
(141, 265, 5),
(225, 256, 200),
(225, 257, 200),
(225, 258, 200),
(225, 259, 25),
(225, 260, 25),
(225, 261, 25),
(225, 262, 15),
(225, 263, 15),
(225, 264, 5),
(225, 265, 15),
(230, 256, 200),
(230, 257, 200),
(230, 258, 200),
(230, 259, 25),
(230, 260, 25),
(230, 261, 25),
(230, 262, 15),
(230, 263, 15),
(230, 264, 5),
(230, 265, 15),
(216, 256, 200),
(216, 257, 200),
(216, 258, 200),
(216, 259, 100),
(216, 260, 100),
(216, 261, 100),
(216, 262, 50),
(216, 263, 50),
(216, 264, 25),
(216, 265, 50),
(218, 256, 500),
(218, 257, 500),
(218, 258, 500),
(218, 259, 200),
(218, 260, 200),
(218, 261, 200),
(218, 262, 100),
(218, 263, 100),
(218, 264, 50),
(218, 265, 100),
(225, 266, 33),
(215, 266, 4),
(216, 266, 33),
(218, 266, 33),
(222, 266, 5),
(223, 266, 5),
(224, 266, 5),
(226, 266, 1000),
(231, 266, 50),
(238, 266, 1000000),
(230, 266, 33),
(221, 266, 4),
(234, 266, 5),
(235, 266, 5),
(236, 266, 4),
(233, 266, 33),
(250, 266, 5),
(243, 266, 4),
(251, 266, 4),
(244, 266, 5),
(252, 266, 5),
(246, 266, 33),
(254, 266, 33),
(247, 266, 33),
(255, 266, 33),
(225, 267, 33),
(215, 267, 4),
(216, 267, 33),
(218, 267, 33),
(222, 267, 5),
(223, 267, 5),
(224, 267, 5),
(226, 267, 1000),
(231, 267, 50),
(238, 267, 1000000),
(230, 267, 33),
(221, 267, 4),
(234, 267, 5),
(235, 267, 5),
(236, 267, 4),
(233, 267, 33),
(250, 267, 5),
(243, 267, 4),
(251, 267, 4),
(244, 267, 5),
(252, 267, 5),
(246, 267, 33),
(254, 267, 33),
(247, 267, 33),
(255, 267, 33),
(225, 268, 33),
(215, 268, 4),
(216, 268, 33),
(218, 268, 33),
(222, 268, 5),
(223, 268, 5),
(224, 268, 5),
(226, 268, 1000),
(231, 268, 50),
(238, 268, 1000000),
(230, 268, 33),
(221, 268, 4),
(234, 268, 5),
(235, 268, 5),
(236, 268, 4),
(233, 268, 33),
(250, 268, 5),
(243, 268, 4),
(251, 268, 4),
(244, 268, 5),
(252, 268, 5),
(246, 268, 33),
(254, 268, 33),
(247, 268, 33),
(255, 268, 33),
(225, 269, 33),
(215, 269, 4),
(216, 269, 33),
(218, 269, 33),
(222, 269, 5),
(223, 269, 5),
(224, 269, 5),
(226, 269, 1000),
(231, 269, 50),
(238, 269, 1000000),
(230, 269, 33),
(221, 269, 4),
(234, 269, 5),
(235, 269, 5),
(236, 269, 4),
(233, 269, 33),
(250, 269, 5),
(243, 269, 4),
(251, 269, 4),
(244, 269, 5),
(252, 269, 5),
(246, 269, 33),
(254, 269, 33),
(247, 269, 33),
(255, 269, 33),
(225, 270, 33),
(215, 270, 4),
(216, 270, 33),
(218, 270, 33),
(222, 270, 5),
(223, 270, 5),
(224, 270, 5),
(226, 270, 1000),
(231, 270, 50),
(238, 270, 1000000),
(230, 270, 33),
(221, 270, 4),
(234, 270, 5),
(235, 270, 5),
(236, 270, 4),
(233, 270, 33),
(250, 270, 5),
(243, 270, 4),
(251, 270, 4),
(244, 270, 5),
(252, 270, 5),
(246, 270, 33),
(254, 270, 33),
(247, 270, 33),
(255, 270, 33),
(225, 271, 33),
(215, 271, 4),
(216, 271, 33),
(218, 271, 33),
(222, 271, 5),
(223, 271, 5),
(224, 271, 5),
(226, 271, 1000),
(231, 271, 50),
(238, 271, 1000000),
(230, 271, 33),
(221, 271, 4),
(234, 271, 5),
(235, 271, 5),
(236, 271, 4),
(233, 271, 33),
(250, 271, 5),
(243, 271, 4),
(251, 271, 4),
(244, 271, 5),
(252, 271, 5),
(246, 271, 33),
(254, 271, 33),
(247, 271, 33),
(255, 271, 33),
(225, 272, 33),
(215, 272, 4),
(216, 272, 33),
(218, 272, 33),
(222, 272, 5),
(223, 272, 5),
(224, 272, 5),
(226, 272, 1000),
(231, 272, 50),
(238, 272, 1000000),
(230, 272, 33),
(221, 272, 4),
(234, 272, 5),
(235, 272, 5),
(236, 272, 4),
(233, 272, 33),
(250, 272, 5),
(243, 272, 4),
(251, 272, 4),
(244, 272, 5),
(252, 272, 5),
(246, 272, 33),
(254, 272, 33),
(247, 272, 33),
(255, 272, 33),
(225, 273, 33),
(215, 273, 4),
(216, 273, 33),
(218, 273, 33),
(222, 273, 5),
(223, 273, 5),
(224, 273, 5),
(226, 273, 1000),
(231, 273, 50),
(238, 273, 1000000),
(230, 273, 33),
(221, 273, 4),
(234, 273, 5),
(235, 273, 5),
(236, 273, 4),
(233, 273, 33),
(250, 273, 5),
(243, 273, 4),
(251, 273, 4),
(244, 273, 5),
(252, 273, 5),
(246, 273, 33),
(254, 273, 33),
(247, 273, 33),
(255, 273, 33),
(226, 274, 10),
(238, 274, 1000000),
(233, 274, 2),
(246, 274, 2),
(216, 274, 2),
(254, 274, 2),
(274, 256, 5),
(274, 257, 5),
(274, 258, 5),
(274, 259, 5),
(274, 260, 5),
(274, 261, 5),
(274, 262, 5),
(274, 263, 5),
(274, 264, 5),
(274, 265, 5),
(265, 416, 20),
(213, 416, 10),
(225, 416, 200),
(216, 416, 200),
(218, 416, 200),
(238, 416, 1000000),
(230, 416, 200),
(237, 416, 10),
(233, 416, 200),
(245, 416, 10),
(253, 416, 10),
(246, 416, 200),
(254, 416, 200),
(247, 416, 200),
(255, 416, 200),
(265, 417, 15),
(213, 417, 10),
(225, 417, 25),
(216, 417, 25),
(218, 417, 25),
(238, 417, 1000000),
(237, 417, 10),
(233, 417, 25),
(245, 417, 10),
(253, 417, 10),
(246, 417, 200),
(254, 417, 200),
(247, 417, 200),
(255, 417, 200),
(211, 401, 20),
(211, 402, 20),
(211, 405, 10),
(211, 226, 1),
(211, 413, 5),
(211, 404, 10),
(211, 407, 10),
(211, 408, 10),
(211, 412, 7),
(211, 239, 15),
(211, 403, 10),
(222, 207, 15);

-- --------------------------------------------------------

--
-- Structure de la table `%PREFIX%vars_requriements`
--

CREATE TABLE `%PREFIX%vars_requriements` (
  `elementID` int(11) NOT NULL,
  `requireID` int(11) NOT NULL,
  `requireLevel` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

--
-- Déchargement des données de la table `%PREFIX%vars_requriements`
--

INSERT INTO `%PREFIX%vars_requriements` (`elementID`, `requireID`, `requireLevel`) VALUES
(6, 14, 11),
(6, 31, 12),
(6, 15, 4),
(6, 108, 12),
(6, 123, 3),
(12, 3, 5),
(12, 113, 3),
(15, 14, 10),
(15, 108, 10),
(21, 14, 2),
(33, 15, 1),
(33, 113, 12),
(42, 41, 1),
(43, 41, 1),
(43, 114, 7),
(44, 21, 1),
(106, 31, 3),
(108, 31, 1),
(109, 31, 4),
(111, 113, 3),
(111, 31, 6),
(110, 31, 2),
(113, 31, 1),
(114, 113, 5),
(114, 111, 5),
(114, 31, 7),
(115, 113, 1),
(115, 31, 1),
(117, 113, 1),
(117, 31, 2),
(118, 114, 3),
(118, 31, 7),
(120, 31, 1),
(120, 113, 2),
(121, 31, 4),
(121, 120, 5),
(121, 113, 4),
(122, 31, 5),
(122, 113, 8),
(122, 120, 10),
(122, 121, 5),
(123, 31, 10),
(123, 108, 8),
(123, 114, 8),
(124, 106, 3),
(124, 117, 3),
(124, 31, 3),
(131, 31, 8),
(131, 113, 5),
(132, 31, 8),
(132, 113, 5),
(133, 31, 8),
(133, 113, 5),
(199, 31, 12),
(202, 21, 2),
(202, 115, 2),
(203, 21, 4),
(203, 115, 6),
(204, 21, 1),
(204, 115, 1),
(205, 21, 3),
(205, 110, 2),
(205, 117, 2),
(206, 21, 5),
(206, 117, 4),
(206, 121, 2),
(207, 21, 7),
(207, 118, 4),
(208, 21, 4),
(208, 117, 3),
(209, 21, 4),
(209, 115, 6),
(209, 111, 2),
(210, 21, 3),
(210, 115, 3),
(210, 106, 2),
(211, 117, 6),
(211, 21, 8),
(211, 122, 5),
(212, 21, 1),
(213, 21, 9),
(213, 118, 6),
(213, 114, 5),
(214, 21, 12),
(214, 118, 7),
(214, 114, 6),
(214, 199, 1),
(215, 114, 5),
(215, 120, 12),
(215, 118, 5),
(215, 21, 8),
(216, 106, 12),
(216, 21, 15),
(216, 109, 14),
(216, 111, 14),
(216, 110, 15),
(216, 114, 10),
(216, 120, 20),
(216, 199, 2),
(217, 110, 10),
(217, 21, 14),
(217, 114, 10),
(217, 111, 14),
(217, 117, 15),
(218, 21, 18),
(218, 109, 20),
(218, 111, 20),
(218, 110, 20),
(218, 114, 15),
(218, 118, 20),
(218, 120, 25),
(218, 199, 3),
(219, 21, 15),
(219, 109, 15),
(219, 111, 15),
(219, 110, 15),
(219, 118, 8),
(220, 21, 9),
(220, 114, 5),
(220, 118, 6),
(401, 21, 1),
(402, 113, 1),
(402, 21, 2),
(402, 120, 3),
(403, 113, 3),
(403, 21, 4),
(403, 120, 6),
(404, 21, 6),
(404, 113, 6),
(404, 109, 3),
(404, 111, 1),
(405, 21, 4),
(405, 121, 4),
(406, 21, 8),
(406, 122, 7),
(407, 111, 2),
(407, 21, 1),
(408, 111, 6),
(408, 21, 6),
(409, 609, 1),
(410, 199, 4),
(410, 21, 18),
(410, 109, 20),
(411, 199, 5),
(411, 111, 22),
(411, 122, 20),
(411, 108, 15),
(411, 110, 25),
(411, 113, 20),
(411, 608, 2),
(411, 21, 20),
(502, 44, 2),
(502, 21, 1),
(503, 44, 4),
(503, 21, 1),
(503, 117, 1),
(603, 601, 5),
(604, 602, 5),
(605, 601, 10),
(605, 603, 2),
(606, 601, 10),
(606, 603, 2),
(607, 605, 1),
(608, 606, 1),
(609, 601, 20),
(609, 603, 10),
(609, 605, 3),
(609, 606, 3),
(609, 607, 2),
(609, 608, 2),
(610, 602, 10),
(610, 604, 5),
(611, 602, 10),
(611, 604, 5),
(612, 610, 1),
(613, 611, 1),
(614, 602, 20),
(614, 604, 10),
(614, 610, 2),
(614, 611, 2),
(614, 612, 1),
(614, 613, 3),
(615, 614, 1),
(615, 609, 1),
(221, 21, 9),
(221, 118, 6),
(134, 31, 12),
(134, 199, 1),
(134, 114, 8),
(134, 123, 3),
(222, 114, 7),
(222, 118, 7),
(222, 21, 10),
(222, 120, 13),
(223, 134, 1),
(223, 118, 5),
(223, 21, 8),
(224, 134, 1),
(224, 121, 6),
(224, 118, 7),
(224, 21, 8),
(412, 111, 10),
(412, 21, 10),
(225, 21, 13),
(225, 118, 7),
(225, 114, 6),
(225, 199, 1),
(413, 121, 10),
(413, 113, 8),
(413, 136, 4),
(226, 21, 9),
(226, 199, 1),
(226, 118, 3),
(231, 21, 10),
(231, 113, 7),
(231, 120, 8),
(231, 121, 6),
(231, 122, 4),
(231, 199, 1),
(214, 231, 1),
(216, 231, 1),
(218, 231, 1),
(225, 231, 1),
(232, 21, 6),
(232, 124, 5),
(232, 118, 1),
(232, 120, 5),
(238, 651, 1),
(234, 113, 4),
(234, 110, 2),
(234, 21, 3),
(234, 117, 2),
(227, 21, 1),
(227, 115, 1),
(228, 114, 5),
(228, 120, 12),
(228, 118, 5),
(228, 21, 8),
(229, 21, 9),
(229, 118, 6),
(229, 114, 5),
(230, 21, 13),
(230, 118, 7),
(230, 114, 6),
(230, 199, 1),
(136, 31, 8),
(136, 135, 1),
(235, 114, 7),
(235, 118, 7),
(235, 21, 10),
(235, 120, 13),
(236, 21, 9),
(236, 118, 6),
(237, 21, 9),
(237, 118, 6),
(237, 114, 5),
(135, 135, 1),
(234, 136, 2),
(235, 136, 3),
(236, 136, 3),
(237, 136, 4),
(225, 136, 2),
(214, 136, 1),
(216, 136, 2),
(218, 136, 6),
(226, 651, 1),
(414, 121, 7),
(414, 21, 6),
(415, 121, 10),
(233, 136, 4),
(233, 106, 12),
(233, 21, 16),
(233, 109, 15),
(233, 111, 15),
(233, 110, 17),
(233, 114, 12),
(233, 120, 20),
(233, 199, 2),
(233, 231, 1),
(415, 21, 8),
(239, 131, 8),
(239, 132, 7),
(239, 133, 6),
(232, 656, 1),
(239, 655, 1),
(137, 654, 1),
(138, 655, 1),
(139, 653, 1),
(616, 614, 1),
(616, 609, 1),
(655, 651, 1),
(141, 108, 6),
(141, 111, 5),
(141, 31, 6),
(416, 141, 3),
(266, 141, 2),
(267, 141, 4),
(268, 141, 4),
(269, 141, 5),
(270, 141, 7),
(271, 141, 7),
(272, 141, 8),
(273, 141, 9),
(274, 141, 10),
(266, 21, 5),
(267, 21, 6),
(268, 21, 6),
(269, 21, 7),
(270, 21, 9),
(271, 21, 9),
(272, 21, 9),
(273, 21, 10),
(274, 21, 12),
(266, 115, 5),
(267, 117, 5),
(268, 117, 5),
(269, 117, 6),
(270, 118, 2),
(271, 118, 2),
(272, 118, 3),
(273, 118, 4),
(274, 118, 7),
(266, 109, 3),
(267, 109, 5),
(268, 109, 5),
(269, 109, 6),
(270, 109, 8),
(271, 109, 8),
(272, 109, 8),
(273, 109, 9),
(274, 109, 12),
(274, 199, 1),
(274, 231, 1),
(142, 21, 18),
(142, 109, 20),
(142, 111, 20),
(142, 110, 20),
(142, 114, 15),
(142, 118, 20),
(142, 120, 25),
(142, 199, 3),
(142, 136, 6),
(417, 141, 8),
(241, 140, 1),
(249, 140, 1),
(242, 140, 2),
(250, 140, 2),
(243, 140, 3),
(251, 140, 3),
(244, 140, 4),
(252, 140, 4),
(245, 140, 5),
(253, 140, 5),
(246, 140, 6),
(254, 140, 6),
(247, 140, 7),
(255, 140, 7),
(247, 142, 1),
(255, 142, 1),
(143, 652, 1),
(144, 656, 1),
(657, 651, 1),
(275, 134, 1),
(275, 118, 5),
(275, 21, 8),
(275, 656, 1),
(276, 134, 1),
(276, 121, 6),
(276, 118, 7),
(276, 21, 8),
(276, 656, 1),
(277, 21, 9),
(277, 118, 6),
(277, 114, 5),
(277, 656, 1),
(278, 114, 7),
(278, 118, 7),
(278, 21, 10),
(278, 120, 13),
(278, 656, 1),
(279, 21, 9),
(279, 118, 6),
(279, 656, 1),
(141, 651, 1),
(652, 651, 1),
(653, 651, 1),
(654, 651, 1),
(656, 651, 1),
(234, 663, 1),
(235, 663, 2),
(236, 663, 3),
(237, 663, 4);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `%PREFIX%aks`
--
ALTER TABLE `%PREFIX%aks`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `%PREFIX%alliance`
--
ALTER TABLE `%PREFIX%alliance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ally_tag` (`ally_tag`),
  ADD KEY `ally_name` (`ally_name`),
  ADD KEY `ally_universe` (`ally_universe`);

--
-- Index pour la table `%PREFIX%alliance_ranks`
--
ALTER TABLE `%PREFIX%alliance_ranks`
  ADD PRIMARY KEY (`rankID`),
  ADD KEY `allianceID` (`allianceID`,`rankID`);

--
-- Index pour la table `%PREFIX%alliance_request`
--
ALTER TABLE `%PREFIX%alliance_request`
  ADD PRIMARY KEY (`applyID`),
  ADD KEY `allianceID` (`allianceID`,`userID`);

--
-- Index pour la table `%PREFIX%banned`
--
ALTER TABLE `%PREFIX%banned`
  ADD KEY `ID` (`id`),
  ADD KEY `universe` (`universe`);

--
-- Index pour la table `%PREFIX%buddy`
--
ALTER TABLE `%PREFIX%buddy`
  ADD PRIMARY KEY (`id`),
  ADD KEY `universe` (`universe`),
  ADD KEY `sender` (`sender`,`owner`);

--
-- Index pour la table `%PREFIX%buddy_request`
--
ALTER TABLE `%PREFIX%buddy_request`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `%PREFIX%chat_messages`
--
ALTER TABLE `%PREFIX%chat_messages`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `%PREFIX%chat_online`
--
ALTER TABLE `%PREFIX%chat_online`
  ADD KEY `dateTime` (`dateTime`,`channel`);

--
-- Index pour la table `%PREFIX%config`
--
ALTER TABLE `%PREFIX%config`
  ADD PRIMARY KEY (`uni`);

--
-- Index pour la table `%PREFIX%cronjobs`
--
ALTER TABLE `%PREFIX%cronjobs`
  ADD UNIQUE KEY `cronjobID` (`cronjobID`),
  ADD KEY `isActive` (`isActive`,`nextTime`,`lock`,`cronjobID`);

--
-- Index pour la table `%PREFIX%cronjobs_log`
--
ALTER TABLE `%PREFIX%cronjobs_log`
  ADD KEY `cronjobId` (`cronjobId`,`executionTime`);

--
-- Index pour la table `%PREFIX%diplo`
--
ALTER TABLE `%PREFIX%diplo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `universe` (`universe`),
  ADD KEY `owner_1` (`owner_1`,`owner_2`,`accept`);

--
-- Index pour la table `%PREFIX%fleets`
--
ALTER TABLE `%PREFIX%fleets`
  ADD PRIMARY KEY (`fleet_id`),
  ADD KEY `fleet_target_owner` (`fleet_target_owner`,`fleet_mission`),
  ADD KEY `fleet_owner` (`fleet_owner`,`fleet_mission`),
  ADD KEY `fleet_group` (`fleet_group`);

--
-- Index pour la table `%PREFIX%fleet_event`
--
ALTER TABLE `%PREFIX%fleet_event`
  ADD PRIMARY KEY (`fleetID`),
  ADD KEY `lock` (`lock`,`time`);

--
-- Index pour la table `%PREFIX%log`
--
ALTER TABLE `%PREFIX%log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mode` (`mode`);

--
-- Index pour la table `%PREFIX%log_fleets`
--
ALTER TABLE `%PREFIX%log_fleets`
  ADD PRIMARY KEY (`fleet_id`),
  ADD KEY `BashRule` (`fleet_owner`,`fleet_end_id`,`fleet_start_time`,`fleet_mission`,`fleet_state`);

--
-- Index pour la table `%PREFIX%lostpassword`
--
ALTER TABLE `%PREFIX%lostpassword`
  ADD PRIMARY KEY (`key`),
  ADD UNIQUE KEY `userID` (`userID`,`key`,`time`,`hasChanged`),
  ADD KEY `time` (`time`);

--
-- Index pour la table `%PREFIX%messages`
--
ALTER TABLE `%PREFIX%messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `message_sender` (`message_sender`),
  ADD KEY `message_deleted` (`message_deleted`),
  ADD KEY `message_owner` (`message_owner`,`message_type`,`message_unread`,`message_deleted`);

--
-- Index pour la table `%PREFIX%multi`
--
ALTER TABLE `%PREFIX%multi`
  ADD PRIMARY KEY (`multiID`),
  ADD KEY `userID` (`userID`);

--
-- Index pour la table `%PREFIX%news`
--
ALTER TABLE `%PREFIX%news`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `%PREFIX%notes`
--
ALTER TABLE `%PREFIX%notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `universe` (`universe`),
  ADD KEY `owner` (`owner`,`time`,`priority`);

--
-- Index pour la table `%PREFIX%planets`
--
ALTER TABLE `%PREFIX%planets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_luna` (`id_luna`),
  ADD KEY `id_owner` (`id_owner`),
  ADD KEY `destruyed` (`destruyed`),
  ADD KEY `universe` (`universe`,`galaxy`,`system`,`planet`,`planet_type`);

--
-- Index pour la table `%PREFIX%raports`
--
ALTER TABLE `%PREFIX%raports`
  ADD PRIMARY KEY (`rid`),
  ADD KEY `time` (`time`);

--
-- Index pour la table `%PREFIX%session`
--
ALTER TABLE `%PREFIX%session`
  ADD PRIMARY KEY (`userID`),
  ADD KEY `sessionID` (`sessionID`);

--
-- Index pour la table `%PREFIX%shortcuts`
--
ALTER TABLE `%PREFIX%shortcuts`
  ADD PRIMARY KEY (`shortcutID`),
  ADD KEY `ownerID` (`ownerID`);

--
-- Index pour la table `%PREFIX%statpoints`
--
ALTER TABLE `%PREFIX%statpoints`
  ADD KEY `id_owner` (`id_owner`),
  ADD KEY `universe` (`universe`),
  ADD KEY `stat_type` (`stat_type`);

--
-- Index pour la table `%PREFIX%ticket`
--
ALTER TABLE `%PREFIX%ticket`
  ADD PRIMARY KEY (`ticketID`),
  ADD KEY `ownerID` (`ownerID`),
  ADD KEY `universe` (`universe`,`status`);

--
-- Index pour la table `%PREFIX%ticket_answer`
--
ALTER TABLE `%PREFIX%ticket_answer`
  ADD PRIMARY KEY (`answerID`);

--
-- Index pour la table `%PREFIX%ticket_category`
--
ALTER TABLE `%PREFIX%ticket_category`
  ADD PRIMARY KEY (`categoryID`);

--
-- Index pour la table `%PREFIX%topkb`
--
ALTER TABLE `%PREFIX%topkb`
  ADD KEY `time` (`universe`,`rid`,`time`);

--
-- Index pour la table `%PREFIX%users`
--
ALTER TABLE `%PREFIX%users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `authlevel` (`authlevel`),
  ADD KEY `ref_bonus` (`ref_bonus`),
  ADD KEY `universe` (`universe`,`username`,`password`,`onlinetime`,`authlevel`),
  ADD KEY `ally_id` (`ally_id`);

--
-- Index pour la table `%PREFIX%users_to_acs`
--
ALTER TABLE `%PREFIX%users_to_acs`
  ADD KEY `userID` (`userID`),
  ADD KEY `acsID` (`acsID`);

--
-- Index pour la table `%PREFIX%users_to_extauth`
--
ALTER TABLE `%PREFIX%users_to_extauth`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id` (`id`),
  ADD KEY `account` (`account`,`mode`);

--
-- Index pour la table `%PREFIX%users_to_topkb`
--
ALTER TABLE `%PREFIX%users_to_topkb`
  ADD KEY `rid` (`rid`,`role`);

--
-- Index pour la table `%PREFIX%users_valid`
--
ALTER TABLE `%PREFIX%users_valid`
  ADD PRIMARY KEY (`validationID`,`validationKey`);

--
-- Index pour la table `%PREFIX%vars`
--
ALTER TABLE `%PREFIX%vars`
  ADD PRIMARY KEY (`elementID`),
  ADD KEY `class` (`class`);

--
-- Index pour la table `%PREFIX%vars_rapidfire`
--
ALTER TABLE `%PREFIX%vars_rapidfire`
  ADD KEY `elementID` (`elementID`),
  ADD KEY `rapidfireID` (`rapidfireID`);

--
-- Index pour la table `%PREFIX%vars_requriements`
--
ALTER TABLE `%PREFIX%vars_requriements`
  ADD KEY `elementID` (`elementID`),
  ADD KEY `requireID` (`requireID`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `%PREFIX%aks`
--
ALTER TABLE `%PREFIX%aks`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `%PREFIX%alliance`
--
ALTER TABLE `%PREFIX%alliance`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `%PREFIX%alliance_ranks`
--
ALTER TABLE `%PREFIX%alliance_ranks`
  MODIFY `rankID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `%PREFIX%alliance_request`
--
ALTER TABLE `%PREFIX%alliance_request`
  MODIFY `applyID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `%PREFIX%banned`
--
ALTER TABLE `%PREFIX%banned`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `%PREFIX%buddy`
--
ALTER TABLE `%PREFIX%buddy`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `%PREFIX%chat_messages`
--
ALTER TABLE `%PREFIX%chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `%PREFIX%config`
--
ALTER TABLE `%PREFIX%config`
  MODIFY `uni` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `%PREFIX%cronjobs`
--
ALTER TABLE `%PREFIX%cronjobs`
  MODIFY `cronjobID` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `%PREFIX%diplo`
--
ALTER TABLE `%PREFIX%diplo`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `%PREFIX%fleets`
--
ALTER TABLE `%PREFIX%fleets`
  MODIFY `fleet_id` bigint(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `%PREFIX%log`
--
ALTER TABLE `%PREFIX%log`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `%PREFIX%messages`
--
ALTER TABLE `%PREFIX%messages`
  MODIFY `message_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `%PREFIX%multi`
--
ALTER TABLE `%PREFIX%multi`
  MODIFY `multiID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `%PREFIX%news`
--
ALTER TABLE `%PREFIX%news`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `%PREFIX%notes`
--
ALTER TABLE `%PREFIX%notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `%PREFIX%planets`
--
ALTER TABLE `%PREFIX%planets`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `%PREFIX%shortcuts`
--
ALTER TABLE `%PREFIX%shortcuts`
  MODIFY `shortcutID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `%PREFIX%ticket`
--
ALTER TABLE `%PREFIX%ticket`
  MODIFY `ticketID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `%PREFIX%ticket_answer`
--
ALTER TABLE `%PREFIX%ticket_answer`
  MODIFY `answerID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `%PREFIX%ticket_category`
--
ALTER TABLE `%PREFIX%ticket_category`
  MODIFY `categoryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `%PREFIX%users`
--
ALTER TABLE `%PREFIX%users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `%PREFIX%users_valid`
--
ALTER TABLE `%PREFIX%users_valid`
  MODIFY `validationID` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;
CREATE TABLE IF NOT EXISTS `%PREFIX%telemetry_daily` (
    universe INT UNSIGNED NOT NULL,
    actor INT UNSIGNED NOT NULL,
    day DATE NOT NULL,
    windows JSON NOT NULL,
    PRIMARY KEY (universe,actor,day),
    KEY cleanup (day)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `%PREFIX%telemetry_events` (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    event_key VARCHAR(48) CHARACTER SET ascii NOT NULL,
    request_id CHAR(32) CHARACTER SET ascii NOT NULL,
    universe INT UNSIGNED NOT NULL,
    actor INT UNSIGNED NOT NULL,
    target INT UNSIGNED NOT NULL DEFAULT 0,
    pair_a INT UNSIGNED NOT NULL,
    pair_b INT UNSIGNED NOT NULL,
    at BIGINT UNSIGNED NOT NULL,
    kind VARCHAR(40) CHARACTER SET ascii NOT NULL,
    result VARCHAR(12) CHARACTER SET ascii NOT NULL,
    fleet_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
    interactive TINYINT NOT NULL DEFAULT 0,
    ip VARCHAR(45) CHARACTER SET ascii NULL,
    data JSON NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY event_key (event_key),
    KEY actor_time (universe,actor,at,id),
    KEY pair_time (universe,pair_a,pair_b,at,id),
    KEY kind_time (universe,kind,at,id),
    KEY delivery_pairs (universe,kind,pair_a,pair_b,at),
    KEY cleanup (at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `%PREFIX%telemetry_warnings` (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    universe INT UNSIGNED NOT NULL,
    actor INT UNSIGNED NOT NULL,
    other INT UNSIGNED NOT NULL DEFAULT 0,
    kind VARCHAR(40) CHARACTER SET ascii NOT NULL,
    strength VARCHAR(20) NOT NULL,
    explanation TEXT NOT NULL,
    first_seen BIGINT UNSIGNED NOT NULL,
    last_seen BIGINT UNSIGNED NOT NULL,
    observation_start BIGINT UNSIGNED NOT NULL,
    observation_end BIGINT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'open',
    settings JSON NOT NULL,
    evidence JSON NOT NULL,
    latest_settings JSON NULL,
    latest_evidence JSON NULL,
    PRIMARY KEY (id),
    UNIQUE KEY one_case (universe,actor,other,kind),
    KEY queue (universe,status,id),
    KEY cleanup (last_seen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `%PREFIX%telemetry_audit` (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    universe INT UNSIGNED NOT NULL,
    admin INT UNSIGNED NOT NULL,
    at BIGINT UNSIGNED NOT NULL,
    warning_id BIGINT UNSIGNED NULL,
    action VARCHAR(32) NOT NULL,
    data JSON NOT NULL,
    PRIMARY KEY (id),
    KEY warning_history (universe,warning_id,at),
    KEY settings_history (universe,action,at),
    KEY cleanup (at),
    FOREIGN KEY (warning_id) REFERENCES `%PREFIX%telemetry_warnings`(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `%PREFIX%cronjobs` (name,isActive,min,hours,dom,month,dow,class,nextTime)
SELECT 'Player activity',1,'*/5','*','*','*','*','TelemetryCronjob',0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `%PREFIX%cronjobs` WHERE class='TelemetryCronjob');

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
