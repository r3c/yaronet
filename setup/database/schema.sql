SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


CREATE TABLE IF NOT EXISTS `account_activity` (
  `address` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `user` int(10) UNSIGNED DEFAULT NULL,
  `group` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `create_time` int(10) UNSIGNED NOT NULL,
  `pulse_time` int(10) UNSIGNED NOT NULL,
  `expire_time` int(10) UNSIGNED NOT NULL,
  `location` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`address`),
  KEY `expire` (`expire_time`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `account_application` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `key` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user` (`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `account_memo` (
  `user` int(10) UNSIGNED NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `account_message` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sender` int(10) UNSIGNED NOT NULL,
  `time` int(10) UNSIGNED NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `time` (`time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `account_message_copy` (
  `message` int(10) UNSIGNED NOT NULL,
  `recipient` int(10) UNSIGNED NOT NULL,
  `state` tinyint(2) UNSIGNED NOT NULL,
  PRIMARY KEY (`message`,`recipient`),
  KEY `recipient__state__time` (`recipient`,`state`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `account_user` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `login` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mechanism` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `secret` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `create_time` int(10) UNSIGNED NOT NULL,
  `pulse_time` int(10) UNSIGNED NOT NULL,
  `recover_time` int(10) UNSIGNED NOT NULL,
  `language` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `template` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` bit(1) NOT NULL,
  `is_admin` bit(1) NOT NULL,
  `is_disabled` bit(1) NOT NULL,
  `is_favorite` bit(1) NOT NULL,
  `is_uniform` bit(1) NOT NULL,
  `options` varchar(600) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`),
  UNIQUE KEY `email` (`email`),
  KEY `pulse_time` (`pulse_time`),
  KEY `is_admin` (`is_admin`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `board_ban` (
  `forum` int(10) UNSIGNED NOT NULL,
  `address` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`forum`,`address`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `board_block` (
  `forum` int(10) UNSIGNED NOT NULL,
  `rank` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `section` int(10) UNSIGNED DEFAULT NULL,
  `text` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`forum`,`rank`),
  KEY `section` (`section`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `board_bookmark` (
  `profile` int(10) UNSIGNED NOT NULL,
  `topic` int(10) UNSIGNED NOT NULL,
  `position` int(10) UNSIGNED NOT NULL,
  `time` int(10) UNSIGNED NOT NULL,
  `fresh` bit(1) NOT NULL,
  `watch` bit(1) NOT NULL,
  PRIMARY KEY (`profile`,`topic`),
  KEY `topic__watch__fresh` (`topic`,`watch`,`fresh`),
  KEY `profile__watch__fresh__time` (`profile`,`watch`,`fresh`,`time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `board_favorite` (
  `profile` int(10) UNSIGNED NOT NULL,
  `rank` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `forum` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`profile`,`rank`),
  UNIQUE KEY `profile_order_forum` (`profile`,`rank`,`forum`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `board_forum` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alias` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `header` text COLLATE utf8mb4_unicode_ci,
  `preface` text COLLATE utf8mb4_unicode_ci,
  `template` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon_tag` int(10) UNSIGNED NOT NULL,
  `is_hidden` bit(1) NOT NULL,
  `is_illustrated` bit(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `alias` (`alias`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `board_ignore` (
  `profile` int(10) UNSIGNED NOT NULL,
  `target` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`profile`,`target`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `board_log` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `forum` int(10) UNSIGNED NOT NULL,
  `time` int(10) UNSIGNED NOT NULL,
  `profile` int(10) UNSIGNED NOT NULL,
  `address` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `forum__time` (`forum`,`time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `board_permission_forum` (
  `forum` int(10) UNSIGNED NOT NULL,
  `profile` int(10) UNSIGNED NOT NULL,
  `can_change` bit(1) DEFAULT NULL,
  `can_read` bit(1) DEFAULT NULL,
  `can_write` bit(1) DEFAULT NULL,
  `expire` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`forum`,`profile`),
  KEY `board_permission_forum__profile` (`profile`),
  KEY `expire` (`expire`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `board_permission_section` (
  `section` int(10) UNSIGNED NOT NULL,
  `profile` int(10) UNSIGNED NOT NULL,
  `can_change` bit(1) DEFAULT NULL,
  `can_read` bit(1) DEFAULT NULL,
  `can_write` bit(1) DEFAULT NULL,
  `expire` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`section`,`profile`),
  KEY `board_permission_section__profile` (`profile`),
  KEY `expire` (`expire`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `board_permission_topic` (
  `topic` int(10) UNSIGNED NOT NULL,
  `profile` int(10) UNSIGNED NOT NULL,
  `can_change` bit(1) DEFAULT NULL,
  `can_read` bit(1) DEFAULT NULL,
  `can_write` bit(1) DEFAULT NULL,
  `expire` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`topic`,`profile`),
  KEY `board_permission_topic__profile` (`profile`),
  KEY `expire` (`expire`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `board_post` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `create_profile` int(10) UNSIGNED NOT NULL,
  `create_time` int(10) UNSIGNED NOT NULL,
  `edit_profile` int(10) UNSIGNED DEFAULT NULL,
  `edit_time` int(10) UNSIGNED DEFAULT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `caution` varchar(256) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` tinyint(2) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `create_profile` (`create_profile`),
  KEY `create_time` (`create_time`),
  KEY `edit_time` (`edit_time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `board_post_index` (
  `post` int(10) UNSIGNED NOT NULL,
  `create_profile` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`post`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `board_profile` (
  `user` int(10) UNSIGNED NOT NULL,
  `forum` int(10) UNSIGNED DEFAULT NULL,
  `gender` tinyint(2) UNSIGNED NOT NULL,
  `signature` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar` tinyint(3) UNSIGNED NOT NULL,
  `avatar_tag` int(10) UNSIGNED NOT NULL,
  `score` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`user`),
  KEY `forum` (`forum`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `board_profile_cache` (
  `user` int(10) UNSIGNED NOT NULL,
  `posts` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`user`),
  KEY `posts` (`posts`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `board_reference` (
  `topic` int(10) UNSIGNED NOT NULL,
  `position` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `post` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`topic`,`position`),
  KEY `post` (`post`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `board_search` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `forum` int(10) UNSIGNED NOT NULL,
  `profile` int(10) UNSIGNED NOT NULL,
  `query` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `time` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `board_search_result` (
  `search` int(10) UNSIGNED NOT NULL,
  `position` int(10) UNSIGNED NOT NULL,
  `topic` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`search`,`position`,`topic`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `board_section` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `forum` int(10) UNSIGNED NOT NULL,
  `name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL,
  `header` text COLLATE utf8mb4_unicode_ci,
  `access` tinyint(3) UNSIGNED NOT NULL,
  `reach` tinyint(3) UNSIGNED NOT NULL,
  `is_delegated` bit(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `forum` (`forum`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `board_section_cache` (
  `id` int(10) UNSIGNED NOT NULL,
  `last_topic` int(10) UNSIGNED DEFAULT NULL,
  `topics` int(10) UNSIGNED NOT NULL,
  `hint` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `board_section_read` (
  `profile` int(10) UNSIGNED NOT NULL,
  `section` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`section`,`profile`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `board_subscription` (
  `section` int(10) UNSIGNED NOT NULL,
  `profile` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`section`,`profile`),
  KEY `board_subscription__profile` (`profile`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `board_topic` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `section` int(10) UNSIGNED NOT NULL,
  `name` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_time` int(10) UNSIGNED NOT NULL,
  `weight` tinyint(3) UNSIGNED NOT NULL,
  `is_closed` bit(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `last_time` (`last_time`),
  KEY `section__weight__last_time` (`section`,`weight`,`last_time`),
  KEY `section__last_time` (`section`,`last_time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `board_topic_cache` (
  `id` int(10) UNSIGNED NOT NULL,
  `create_profile` int(10) UNSIGNED DEFAULT NULL,
  `create_time` int(10) UNSIGNED DEFAULT NULL,
  `last_profile` int(10) UNSIGNED DEFAULT NULL,
  `posts` int(10) UNSIGNED NOT NULL,
  `hint` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chat_shout` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nick` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `time` int(10) UNSIGNED NOT NULL,
  `is_guest` bit(1) NOT NULL,
  `text` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `help_page` (
  `label` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `language` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`label`,`language`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `security_cost` (
  `address` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `active_amount` int(10) UNSIGNED NOT NULL,
  `active_expire` int(10) UNSIGNED NOT NULL,
  `decay_amount` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`address`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `survey_poll` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` tinyint(3) UNSIGNED NOT NULL,
  `question` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL,
  `votes` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `survey_poll_choice` (
  `poll` int(10) UNSIGNED NOT NULL,
  `rank` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `text` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL,
  `score` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`poll`,`rank`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `survey_poll_vote` (
  `poll` int(10) UNSIGNED NOT NULL,
  `user` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`poll`,`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tmp_gem_event` (
  `user` int(11) NOT NULL,
  `type` int(10) UNSIGNED NOT NULL,
  `key0` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `key1` int(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`user`,`type`,`key0`,`key1`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tmp_gem_score` (
  `user` int(10) UNSIGNED NOT NULL,
  `gem0` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `gem1` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `gem2` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `gem3` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `gem4` int(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- https://dba.stackexchange.com/questions/24531/mysql-create-index-if-not-exists

DELIMITER $$

DROP PROCEDURE IF EXISTS `CreateIndexIfNotExists` $$
CREATE PROCEDURE `CreateIndexIfNotExists`
(
  new_table_name VARCHAR(64),
  new_index_name VARCHAR(64),
  new_column_name VARCHAR(64)
)
BEGIN
  DECLARE has_index INTEGER;

  SELECT COUNT(1) INTO has_index
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = database()
  AND TABLE_NAME = new_table_name
  AND INDEX_NAME = new_index_name;

  IF has_index = 0 THEN
    SET @sqlstmt = CONCAT('ALTER TABLE `', new_table_name, '` ADD FULLTEXT KEY `', new_index_name, '` (`', new_column_name, '`)');
    PREPARE st FROM @sqlstmt;
    EXECUTE st;
    DEALLOCATE PREPARE st;
  END IF;

END $$

DELIMITER ;

CALL CreateIndexIfNotExists('board_forum', 'name_full', 'name');
CALL CreateIndexIfNotExists('board_post_index', 'text_full', 'text');

DROP PROCEDURE `CreateIndexIfNotExists`;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
