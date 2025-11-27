-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:8889
-- Généré le : jeu. 27 nov. 2025 à 15:33
-- Version du serveur : 8.0.40
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `English-Game`
--

-- --------------------------------------------------------

--
-- Structure de la table `friends`
--

CREATE TABLE `friends` (
  `id` int NOT NULL,
  `requester_id` int NOT NULL,
  `requested_id` int NOT NULL,
  `status` enum('pending','accepted','rejected','blocked') DEFAULT 'pending',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `friends`
--

INSERT INTO `friends` (`id`, `requester_id`, `requested_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'accepted', '2025-11-25 21:05:23', '2025-11-25 21:37:13'),
(2, 3, 1, 'accepted', '2025-11-25 21:38:28', '2025-11-25 21:38:37');

-- --------------------------------------------------------

--
-- Structure de la table `game_history`
--

CREATE TABLE `game_history` (
  `id` int NOT NULL,
  `game_id` int DEFAULT NULL,
  `exported_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `json_snapshot` longtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `game_invitations`
--

CREATE TABLE `game_invitations` (
  `id` int NOT NULL,
  `game_id` int NOT NULL,
  `inviter_id` int NOT NULL,
  `invited_id` int NOT NULL,
  `status` enum('pending','accepted','declined') DEFAULT 'pending',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `game_invitations`
--

INSERT INTO `game_invitations` (`id`, `game_id`, `inviter_id`, `invited_id`, `status`, `created_at`) VALUES
(1, 13, 3, 1, 'accepted', '2025-11-26 09:45:14'),
(2, 16, 1, 2, 'declined', '2025-11-26 11:01:56'),
(3, 17, 1, 3, 'declined', '2025-11-26 11:26:45'),
(4, 18, 1, 3, 'accepted', '2025-11-26 11:34:49'),
(5, 19, 1, 3, 'accepted', '2025-11-26 11:37:09'),
(6, 22, 1, 2, 'accepted', '2025-11-26 11:48:01'),
(7, 23, 1, 2, 'declined', '2025-11-26 11:49:27'),
(8, 23, 1, 2, 'declined', '2025-11-26 11:49:48'),
(9, 24, 1, 2, 'accepted', '2025-11-26 11:50:08'),
(10, 25, 1, 2, 'accepted', '2025-11-26 11:59:31'),
(11, 26, 1, 3, 'pending', '2025-11-26 13:44:36'),
(12, 26, 1, 2, 'accepted', '2025-11-26 13:44:53'),
(13, 27, 1, 2, 'accepted', '2025-11-26 13:56:09'),
(14, 28, 1, 2, 'accepted', '2025-11-26 14:14:19'),
(15, 29, 1, 2, 'accepted', '2025-11-26 15:27:13'),
(16, 30, 2, 1, 'accepted', '2025-11-26 15:47:50'),
(17, 31, 2, 1, 'accepted', '2025-11-26 16:10:04'),
(18, 32, 2, 1, 'accepted', '2025-11-26 16:17:36'),
(19, 34, 1, 2, 'accepted', '2025-11-26 16:30:16'),
(20, 35, 2, 1, 'accepted', '2025-11-27 12:05:51'),
(21, 36, 1, 2, 'declined', '2025-11-27 12:17:26'),
(22, 37, 2, 1, 'accepted', '2025-11-27 12:24:28'),
(23, 38, 2, 1, 'accepted', '2025-11-27 12:26:15'),
(24, 39, 1, 2, 'accepted', '2025-11-27 12:33:01'),
(25, 40, 2, 1, 'accepted', '2025-11-27 12:36:21'),
(26, 42, 1, 2, 'accepted', '2025-11-27 13:47:02'),
(27, 43, 1, 2, 'accepted', '2025-11-27 13:52:18'),
(28, 44, 1, 2, 'accepted', '2025-11-27 14:34:21'),
(29, 45, 1, 2, 'accepted', '2025-11-27 14:38:48'),
(30, 46, 1, 2, 'accepted', '2025-11-27 14:44:57'),
(31, 48, 1, 2, 'accepted', '2025-11-27 15:20:59');

-- --------------------------------------------------------

--
-- Structure de la table `game_players`
--

CREATE TABLE `game_players` (
  `id` int NOT NULL,
  `game_id` int NOT NULL,
  `user_id` int NOT NULL,
  `total_points` int DEFAULT '0',
  `manches_gagnees` int DEFAULT '0',
  `position_finale` smallint DEFAULT NULL,
  `added_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `game_players`
--

INSERT INTO `game_players` (`id`, `game_id`, `user_id`, `total_points`, `manches_gagnees`, `position_finale`, `added_at`) VALUES
(1, 1, 2, 0, 0, NULL, '2025-11-25 20:27:36'),
(2, 2, 1, 0, 0, NULL, '2025-11-25 22:52:29'),
(3, 2, 3, 0, 0, NULL, '2025-11-25 22:53:12'),
(4, 3, 1, 0, 0, 1, '2025-11-25 23:00:32'),
(5, 3, 2, 0, 0, 2, '2025-11-25 23:00:32'),
(6, 4, 1, 0, 0, NULL, '2025-11-25 23:06:19'),
(7, 4, 2, 0, 0, NULL, '2025-11-25 23:06:19'),
(8, 4, 3, 0, 0, NULL, '2025-11-25 23:06:22'),
(9, 5, 1, 0, 0, NULL, '2025-11-26 09:02:57'),
(10, 6, 1, 0, 0, NULL, '2025-11-26 09:05:53'),
(11, 6, 3, 0, 0, NULL, '2025-11-26 09:05:53'),
(12, 7, 1, 10, 1, 1, '2025-11-26 09:06:00'),
(13, 7, 3, 1, 0, 2, '2025-11-26 09:06:00'),
(14, 8, 3, 0, 0, NULL, '2025-11-26 09:21:20'),
(15, 9, 1, 0, 0, NULL, '2025-11-26 09:33:11'),
(16, 9, 3, 0, 0, NULL, '2025-11-26 09:33:11'),
(17, 10, 1, 0, 0, NULL, '2025-11-26 09:33:23'),
(18, 10, 3, 0, 0, NULL, '2025-11-26 09:33:31'),
(19, 11, 1, 0, 0, NULL, '2025-11-26 09:44:25'),
(20, 11, 2, 0, 0, NULL, '2025-11-26 09:44:25'),
(21, 12, 1, 0, 0, NULL, '2025-11-26 09:44:28'),
(22, 12, 3, 0, 0, NULL, '2025-11-26 09:44:28'),
(23, 13, 3, 0, 0, NULL, '2025-11-26 09:45:10'),
(24, 13, 1, 0, 0, NULL, '2025-11-26 09:45:32'),
(25, 14, 3, 0, 0, NULL, '2025-11-26 10:24:50'),
(26, 14, 1, 0, 0, NULL, '2025-11-26 10:24:50'),
(27, 15, 1, 0, 0, NULL, '2025-11-26 10:29:10'),
(28, 15, 3, 0, 0, NULL, '2025-11-26 10:29:10'),
(29, 16, 1, 0, 0, NULL, '2025-11-26 11:01:56'),
(30, 17, 1, 0, 0, NULL, '2025-11-26 11:01:59'),
(31, 18, 1, 0, 0, NULL, '2025-11-26 11:34:49'),
(32, 18, 3, 0, 0, NULL, '2025-11-26 11:35:01'),
(33, 19, 1, 0, 0, NULL, '2025-11-26 11:37:09'),
(34, 19, 3, 0, 0, NULL, '2025-11-26 11:37:15'),
(35, 20, 4, 0, 0, NULL, '2025-11-26 11:39:39'),
(36, 21, 4, 0, 0, NULL, '2025-11-26 11:39:53'),
(37, 22, 1, 0, 0, NULL, '2025-11-26 11:48:01'),
(38, 22, 2, 0, 0, NULL, '2025-11-26 11:48:08'),
(39, 23, 1, 0, 0, NULL, '2025-11-26 11:49:27'),
(40, 24, 1, 0, 0, NULL, '2025-11-26 11:50:06'),
(41, 24, 2, 0, 0, NULL, '2025-11-26 11:50:23'),
(42, 25, 1, 0, 0, NULL, '2025-11-26 11:59:25'),
(43, 25, 2, 0, 0, NULL, '2025-11-26 11:59:35'),
(44, 26, 1, 0, 0, NULL, '2025-11-26 13:44:14'),
(45, 26, 2, 0, 0, NULL, '2025-11-26 13:44:55'),
(46, 27, 1, 0, 0, NULL, '2025-11-26 13:56:09'),
(47, 27, 2, 0, 0, NULL, '2025-11-26 13:56:15'),
(48, 28, 1, 15, 1, NULL, '2025-11-26 14:14:19'),
(49, 28, 2, 0, 0, NULL, '2025-11-26 14:14:24'),
(50, 29, 1, 0, 0, NULL, '2025-11-26 15:27:13'),
(51, 29, 2, 0, 0, NULL, '2025-11-26 15:27:17'),
(52, 30, 2, 0, 0, NULL, '2025-11-26 15:47:28'),
(53, 30, 1, 0, 0, NULL, '2025-11-26 15:47:56'),
(54, 31, 2, 0, 0, NULL, '2025-11-26 16:10:02'),
(55, 31, 1, 0, 0, NULL, '2025-11-26 16:10:09'),
(56, 32, 2, 0, 0, NULL, '2025-11-26 16:17:36'),
(57, 32, 1, 0, 0, NULL, '2025-11-26 16:17:40'),
(58, 33, 2, 0, 0, NULL, '2025-11-26 16:18:09'),
(59, 33, 1, 0, 0, NULL, '2025-11-26 16:18:15'),
(60, 34, 1, 57, 2, 1, '2025-11-26 16:30:16'),
(61, 34, 2, 7, 0, 2, '2025-11-26 16:30:20'),
(62, 35, 2, 0, 0, NULL, '2025-11-27 12:05:51'),
(63, 35, 1, 0, 0, NULL, '2025-11-27 12:05:55'),
(64, 36, 1, 0, 0, NULL, '2025-11-27 12:17:26'),
(65, 37, 2, 0, 0, NULL, '2025-11-27 12:24:28'),
(66, 37, 1, 0, 0, NULL, '2025-11-27 12:24:32'),
(67, 38, 2, 0, 0, NULL, '2025-11-27 12:26:15'),
(68, 38, 1, 0, 0, NULL, '2025-11-27 12:26:18'),
(69, 39, 1, 0, 0, NULL, '2025-11-27 12:32:57'),
(70, 39, 2, 0, 0, NULL, '2025-11-27 12:33:08'),
(71, 40, 2, 0, 0, NULL, '2025-11-27 12:36:21'),
(72, 40, 1, 0, 0, NULL, '2025-11-27 12:36:26'),
(73, 41, 2, 0, 0, NULL, '2025-11-27 13:43:48'),
(74, 41, 1, 0, 0, NULL, '2025-11-27 13:44:12'),
(75, 42, 1, 0, 0, NULL, '2025-11-27 13:47:02'),
(76, 42, 2, 0, 0, NULL, '2025-11-27 13:47:26'),
(77, 43, 1, 20, 0, NULL, '2025-11-27 13:52:18'),
(78, 43, 2, 9, 0, NULL, '2025-11-27 13:52:22'),
(79, 44, 1, 94, 0, NULL, '2025-11-27 14:34:21'),
(80, 44, 2, 0, 0, NULL, '2025-11-27 14:34:24'),
(81, 45, 1, 22, 0, NULL, '2025-11-27 14:38:48'),
(82, 45, 2, 15, 0, NULL, '2025-11-27 14:38:51'),
(83, 46, 1, 0, 0, NULL, '2025-11-27 14:44:55'),
(84, 46, 2, 0, 0, NULL, '2025-11-27 14:45:21'),
(85, 47, 1, 15, 0, NULL, '2025-11-27 14:57:01'),
(86, 47, 4, 0, 0, NULL, '2025-11-27 14:57:15'),
(87, 48, 1, 0, 0, NULL, '2025-11-27 15:20:59'),
(88, 48, 2, 0, 0, NULL, '2025-11-27 15:21:02');

-- --------------------------------------------------------

--
-- Structure de la table `game_rounds`
--

CREATE TABLE `game_rounds` (
  `id` int NOT NULL,
  `game_player_id` int NOT NULL,
  `round_number` tinyint NOT NULL,
  `points` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `game_rounds`
--

INSERT INTO `game_rounds` (`id`, `game_player_id`, `round_number`, `points`, `created_at`) VALUES
(1, 4, 1, 0, '2025-11-25 23:05:48'),
(2, 5, 1, 0, '2025-11-25 23:05:48'),
(3, 4, 2, 0, '2025-11-25 23:05:52'),
(4, 5, 2, 0, '2025-11-25 23:05:52'),
(5, 12, 1, 10, '2025-11-26 09:17:15'),
(6, 13, 1, 1, '2025-11-26 09:17:15'),
(7, 48, 1, 15, '2025-11-26 14:20:26'),
(8, 49, 1, 0, '2025-11-26 14:20:26'),
(9, 61, 1, 2, '2025-11-26 16:35:52'),
(10, 60, 1, 39, '2025-11-26 16:35:56'),
(11, 60, 2, 18, '2025-11-26 16:41:56'),
(12, 61, 2, 5, '2025-11-26 16:42:03'),
(13, 77, 1, 20, '2025-11-27 14:04:10'),
(14, 78, 1, 9, '2025-11-27 14:04:23'),
(15, 79, 1, 12, '2025-11-27 14:39:50'),
(16, 82, 1, 15, '2025-11-27 14:44:18'),
(17, 81, 1, 22, '2025-11-27 14:44:24'),
(18, 79, 2, 82, '2025-11-27 14:45:06'),
(19, 85, 1, 15, '2025-11-27 15:03:37');

-- --------------------------------------------------------

--
-- Structure de la table `game_session`
--

CREATE TABLE `game_session` (
  `id` int NOT NULL,
  `host_id` int NOT NULL,
  `code` char(6) NOT NULL,
  `nom` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `finished_at` datetime DEFAULT NULL,
  `duration_seconds` int DEFAULT NULL,
  `status` enum('open','in_progress','finished') DEFAULT 'open'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `game_session`
--

INSERT INTO `game_session` (`id`, `host_id`, `code`, `nom`, `created_at`, `finished_at`, `duration_seconds`, `status`) VALUES
(1, 2, '242EFF', '', '2025-11-25 20:27:36', NULL, NULL, 'open'),
(2, 1, '751821', 'Surf\'It Game', '2025-11-25 22:52:29', NULL, NULL, 'in_progress'),
(3, 1, '924313', 'Game avec Evanzezette', '2025-11-25 23:00:32', '2025-11-25 23:05:52', 320, 'finished'),
(4, 1, '537231', 'Game avec Evanzezette', '2025-11-25 23:06:19', NULL, NULL, 'open'),
(5, 1, '019283', 'Surf\'It Game', '2025-11-26 09:02:57', NULL, NULL, 'open'),
(6, 1, '632192', 'Game avec Eliott', '2025-11-26 09:05:53', NULL, NULL, 'open'),
(7, 1, '611420', 'Game avec Eliott', '2025-11-26 09:06:00', '2025-11-26 09:17:15', 675, 'finished'),
(8, 3, '392185', 'Surf\'It Game', '2025-11-26 09:21:20', NULL, NULL, 'open'),
(9, 1, '350831', 'Game avec Eliott', '2025-11-26 09:33:11', NULL, NULL, 'open'),
(10, 1, '114569', 'Surf\'It Game', '2025-11-26 09:33:23', NULL, NULL, 'in_progress'),
(11, 1, '703452', 'Game avec Evanzezette', '2025-11-26 09:44:25', NULL, NULL, 'open'),
(12, 1, '249934', 'Game avec Eliott', '2025-11-26 09:44:28', NULL, NULL, 'open'),
(13, 3, '485269', 'Surf\'It Game', '2025-11-26 09:45:10', NULL, NULL, 'open'),
(14, 3, '482442', 'Game avec Test', '2025-11-26 10:24:50', NULL, NULL, 'open'),
(15, 1, '898808', 'Game avec Eliott', '2025-11-26 10:29:10', NULL, NULL, 'open'),
(16, 1, '904174', 'Partie Surf It', '2025-11-26 11:01:56', NULL, NULL, 'open'),
(17, 1, '883098', 'Partie Surf It', '2025-11-26 11:01:59', NULL, NULL, 'open'),
(18, 1, '650223', 'Partie Surf It', '2025-11-26 11:34:49', NULL, NULL, 'open'),
(19, 1, '641238', 'Partie Surf It', '2025-11-26 11:37:09', NULL, NULL, 'open'),
(20, 4, '476203', 'Partie Surf It', '2025-11-26 11:39:39', NULL, NULL, 'open'),
(21, 4, '987097', 'Partie Surf It', '2025-11-26 11:39:53', NULL, NULL, 'open'),
(22, 1, '611831', 'Partie Surf It', '2025-11-26 11:48:01', NULL, NULL, 'open'),
(23, 1, '875821', 'Partie Surf It', '2025-11-26 11:49:27', NULL, NULL, 'open'),
(24, 1, '996979', 'Partie Surf It', '2025-11-26 11:50:06', NULL, NULL, 'open'),
(25, 1, '297819', 'Partie Surf It', '2025-11-26 11:59:25', NULL, NULL, 'in_progress'),
(26, 1, '799838', 'Partie Surf It', '2025-11-26 13:44:14', NULL, NULL, 'in_progress'),
(27, 1, '304950', 'Partie Surf It', '2025-11-26 13:56:09', NULL, NULL, 'in_progress'),
(28, 1, '488134', 'Partie Surf It', '2025-11-26 14:14:19', NULL, NULL, 'in_progress'),
(29, 1, '519824', 'Partie Surf It', '2025-11-26 15:27:13', NULL, NULL, 'in_progress'),
(30, 2, '448328', 'Partie Surf It', '2025-11-26 15:47:28', NULL, NULL, 'in_progress'),
(31, 2, '391823', 'Partie Surf It', '2025-11-26 16:10:02', NULL, NULL, 'in_progress'),
(32, 2, '770770', 'Partie Surf It', '2025-11-26 16:17:36', NULL, NULL, 'in_progress'),
(33, 2, '565940', 'Partie Surf It', '2025-11-26 16:18:09', NULL, NULL, 'in_progress'),
(34, 1, '736679', 'Partie Surf It', '2025-11-26 16:30:16', '2025-11-26 16:42:09', 713, 'finished'),
(35, 2, '671111', 'Partie Surf It', '2025-11-27 12:05:51', NULL, NULL, 'in_progress'),
(36, 1, '177778', 'Partie Surf It', '2025-11-27 12:17:26', NULL, NULL, 'open'),
(37, 2, '357097', 'Partie Surf It', '2025-11-27 12:24:28', NULL, NULL, 'open'),
(38, 2, '699534', 'Partie Surf It', '2025-11-27 12:26:15', NULL, NULL, 'open'),
(39, 1, '925030', 'Partie Surf It', '2025-11-27 12:32:57', NULL, NULL, 'in_progress'),
(40, 2, '804923', 'Partie Surf It', '2025-11-27 12:36:21', NULL, NULL, 'in_progress'),
(41, 2, '706375', 'Partie Surf It', '2025-11-27 13:43:48', NULL, NULL, 'in_progress'),
(42, 1, '774589', 'Partie Surf It', '2025-11-27 13:47:02', NULL, NULL, 'in_progress'),
(43, 1, '131197', 'Partie Surf It', '2025-11-27 13:52:18', NULL, NULL, 'in_progress'),
(44, 1, '390952', 'Partie Surf It', '2025-11-27 14:34:21', NULL, NULL, 'in_progress'),
(45, 1, '487048', 'Partie Surf It', '2025-11-27 14:38:48', NULL, NULL, 'in_progress'),
(46, 1, '933387', 'Partie Surf It', '2025-11-27 14:44:55', NULL, NULL, 'in_progress'),
(47, 1, '906502', 'Partie Surf It', '2025-11-27 14:57:01', NULL, NULL, 'in_progress'),
(48, 1, '481960', 'Partie Surf It', '2025-11-27 15:20:59', NULL, NULL, 'in_progress');

-- --------------------------------------------------------

--
-- Structure de la table `joueurs`
--

CREATE TABLE `joueurs` (
  `user_id` int NOT NULL,
  `nom` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `points_totaux` int DEFAULT '0',
  `victoires_totales` int DEFAULT '0',
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `avatar_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `joueurs`
--

INSERT INTO `joueurs` (`user_id`, `nom`, `email`, `mot_de_passe`, `points_totaux`, `victoires_totales`, `date_creation`, `avatar_path`) VALUES
(1, 'Test', 'test@gmail.com', '$2y$10$auBbRS3EaJ6CAa7Q.Po13OuPhhxWM7jI45KmuCaQZXHzbrSkJHtCC', 0, 0, '2025-11-25 14:06:13', 'avatars/avatar8.png'),
(2, 'Evanzezette', 'evan.guenier@icloud.com', '$2y$10$1G2U42LE7Ji/K9jWjF352evEsGdXCTcR4159YFCAkLWATwmr03qL2', 0, 0, '2025-11-25 20:26:42', 'avatars/avatar4.png'),
(3, 'Eliott', 'prout@gmail.com', '$2y$10$2Thb0MKsvjv0KBbWSLQJeeJ4hU8epFNSzAVP397Tqf9NYelJmstFK', 0, 0, '2025-11-25 21:37:52', 'avatars/avatar8.png'),
(4, 'tristan', 'tristan@tristan.com', '$2y$10$/EHze1DIiR9ul95DxAqBfOADET.sWsPJSnrypjpUIX2GcD7JA0dKO', 0, 0, '2025-11-26 11:39:11', 'avatars/avatar10.png');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `friends`
--
ALTER TABLE `friends`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `requester_id` (`requester_id`,`requested_id`),
  ADD KEY `requested_id` (`requested_id`);

--
-- Index pour la table `game_history`
--
ALTER TABLE `game_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`);

--
-- Index pour la table `game_invitations`
--
ALTER TABLE `game_invitations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_inv_game` (`game_id`),
  ADD KEY `fk_inv_inviter` (`inviter_id`),
  ADD KEY `fk_inv_invited` (`invited_id`);

--
-- Index pour la table `game_players`
--
ALTER TABLE `game_players`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `game_id` (`game_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `game_rounds`
--
ALTER TABLE `game_rounds`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `game_player_id` (`game_player_id`,`round_number`);

--
-- Index pour la table `game_session`
--
ALTER TABLE `game_session`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `host_id` (`host_id`);

--
-- Index pour la table `joueurs`
--
ALTER TABLE `joueurs`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `friends`
--
ALTER TABLE `friends`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `game_history`
--
ALTER TABLE `game_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `game_invitations`
--
ALTER TABLE `game_invitations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT pour la table `game_players`
--
ALTER TABLE `game_players`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT pour la table `game_rounds`
--
ALTER TABLE `game_rounds`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT pour la table `game_session`
--
ALTER TABLE `game_session`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT pour la table `joueurs`
--
ALTER TABLE `joueurs`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `friends`
--
ALTER TABLE `friends`
  ADD CONSTRAINT `friends_ibfk_1` FOREIGN KEY (`requester_id`) REFERENCES `joueurs` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `friends_ibfk_2` FOREIGN KEY (`requested_id`) REFERENCES `joueurs` (`user_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `game_history`
--
ALTER TABLE `game_history`
  ADD CONSTRAINT `game_history_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `game_session` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `game_invitations`
--
ALTER TABLE `game_invitations`
  ADD CONSTRAINT `fk_inv_game` FOREIGN KEY (`game_id`) REFERENCES `game_session` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_inv_invited` FOREIGN KEY (`invited_id`) REFERENCES `joueurs` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_inv_inviter` FOREIGN KEY (`inviter_id`) REFERENCES `joueurs` (`user_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `game_players`
--
ALTER TABLE `game_players`
  ADD CONSTRAINT `game_players_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `game_session` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `game_players_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `joueurs` (`user_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `game_rounds`
--
ALTER TABLE `game_rounds`
  ADD CONSTRAINT `game_rounds_ibfk_1` FOREIGN KEY (`game_player_id`) REFERENCES `game_players` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `game_session`
--
ALTER TABLE `game_session`
  ADD CONSTRAINT `game_session_ibfk_1` FOREIGN KEY (`host_id`) REFERENCES `joueurs` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
