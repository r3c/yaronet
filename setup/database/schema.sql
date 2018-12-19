SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


CREATE TABLE `account_activity` (
  `address` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `user` int(10) UNSIGNED DEFAULT NULL,
  `group` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `create_time` int(10) UNSIGNED NOT NULL,
  `pulse_time` int(10) UNSIGNED NOT NULL,
  `expire_time` int(10) UNSIGNED NOT NULL,
  `location` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=MEMORY DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `account_application` (
  `id` int(10) UNSIGNED NOT NULL,
  `user` int(11) NOT NULL,
  `name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `key` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `account_memo` (
  `user` int(10) UNSIGNED NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `account_message` (
  `id` int(10) UNSIGNED NOT NULL,
  `sender` int(10) UNSIGNED NOT NULL,
  `time` int(10) UNSIGNED NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `account_message_copy` (
  `message` int(10) UNSIGNED NOT NULL,
  `recipient` int(10) UNSIGNED NOT NULL,
  `hidden` bit(1) NOT NULL,
  `read` bit(1) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `account_user` (
  `id` int(10) UNSIGNED NOT NULL,
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
  `options` varchar(600) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `board_ban` (
  `forum` int(10) UNSIGNED NOT NULL,
  `address` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `board_block` (
  `forum` int(10) UNSIGNED NOT NULL,
  `rank` smallint(5) UNSIGNED NOT NULL,
  `section` int(10) UNSIGNED DEFAULT NULL,
  `text` text COLLATE utf8mb4_unicode_ci
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `board_bookmark` (
  `profile` int(10) UNSIGNED NOT NULL,
  `topic` int(10) UNSIGNED NOT NULL,
  `position` int(10) UNSIGNED NOT NULL,
  `time` int(10) UNSIGNED NOT NULL,
  `fresh` bit(1) NOT NULL,
  `watch` bit(1) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `board_favorite` (
  `profile` int(10) UNSIGNED NOT NULL,
  `rank` smallint(5) UNSIGNED NOT NULL,
  `forum` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `board_forum` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alias` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `header` text COLLATE utf8mb4_unicode_ci,
  `preface` text COLLATE utf8mb4_unicode_ci,
  `template` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon_tag` int(10) UNSIGNED NOT NULL,
  `is_hidden` bit(1) NOT NULL,
  `is_illustrated` bit(1) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `board_ignore` (
  `profile` int(10) UNSIGNED NOT NULL,
  `target` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `board_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `forum` int(10) UNSIGNED NOT NULL,
  `time` int(10) UNSIGNED NOT NULL,
  `profile` int(10) UNSIGNED NOT NULL,
  `address` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `board_permission_forum` (
  `forum` int(10) UNSIGNED NOT NULL,
  `profile` int(10) UNSIGNED NOT NULL,
  `can_change` bit(1) DEFAULT NULL,
  `can_read` bit(1) DEFAULT NULL,
  `can_write` bit(1) DEFAULT NULL,
  `expire` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `board_permission_section` (
  `section` int(10) UNSIGNED NOT NULL,
  `profile` int(10) UNSIGNED NOT NULL,
  `can_change` bit(1) DEFAULT NULL,
  `can_read` bit(1) DEFAULT NULL,
  `can_write` bit(1) DEFAULT NULL,
  `expire` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `board_permission_topic` (
  `topic` int(10) UNSIGNED NOT NULL,
  `profile` int(10) UNSIGNED NOT NULL,
  `can_change` bit(1) DEFAULT NULL,
  `can_read` bit(1) DEFAULT NULL,
  `can_write` bit(1) DEFAULT NULL,
  `expire` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `board_post` (
  `id` int(10) UNSIGNED NOT NULL,
  `create_profile` int(10) UNSIGNED NOT NULL,
  `create_time` int(10) UNSIGNED NOT NULL,
  `edit_profile` int(10) UNSIGNED DEFAULT NULL,
  `edit_time` int(10) UNSIGNED DEFAULT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `caution` varchar(256) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` tinyint(2) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `board_post_index` (
  `post` int(10) UNSIGNED NOT NULL,
  `create_profile` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `board_profile` (
  `user` int(10) UNSIGNED NOT NULL,
  `forum` int(10) UNSIGNED DEFAULT NULL,
  `gender` tinyint(2) UNSIGNED NOT NULL,
  `signature` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar` tinyint(3) UNSIGNED NOT NULL,
  `avatar_tag` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `board_profile_cache` (
  `user` int(10) UNSIGNED NOT NULL,
  `posts` int(10) UNSIGNED NOT NULL
) ENGINE=MEMORY DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `board_reference` (
  `topic` int(10) UNSIGNED NOT NULL,
  `position` int(10) UNSIGNED NOT NULL,
  `post` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `board_search` (
  `id` int(10) UNSIGNED NOT NULL,
  `forum` int(10) UNSIGNED NOT NULL,
  `profile` int(10) UNSIGNED NOT NULL,
  `query` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `time` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `board_search_result` (
  `search` int(10) UNSIGNED NOT NULL,
  `position` int(10) UNSIGNED NOT NULL,
  `topic` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `board_section` (
  `id` int(10) UNSIGNED NOT NULL,
  `forum` int(10) UNSIGNED NOT NULL,
  `name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL,
  `header` text COLLATE utf8mb4_unicode_ci,
  `access` tinyint(3) UNSIGNED NOT NULL,
  `reach` tinyint(3) UNSIGNED NOT NULL,
  `is_delegated` bit(1) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `board_section_cache` (
  `id` int(10) UNSIGNED NOT NULL,
  `last_topic` int(10) UNSIGNED DEFAULT NULL,
  `topics` int(10) UNSIGNED NOT NULL,
  `hint` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=MEMORY DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `board_section_read` (
  `profile` int(10) UNSIGNED NOT NULL,
  `section` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `board_subscription` (
  `section` int(10) UNSIGNED NOT NULL,
  `profile` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `board_topic` (
  `id` int(10) UNSIGNED NOT NULL,
  `section` int(10) UNSIGNED NOT NULL,
  `name` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_time` int(10) UNSIGNED NOT NULL,
  `weight` tinyint(3) UNSIGNED NOT NULL,
  `is_closed` bit(1) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `board_topic_cache` (
  `id` int(10) UNSIGNED NOT NULL,
  `create_profile` int(10) UNSIGNED DEFAULT NULL,
  `create_time` int(10) UNSIGNED DEFAULT NULL,
  `last_profile` int(10) UNSIGNED DEFAULT NULL,
  `posts` int(10) UNSIGNED NOT NULL,
  `hint` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `chat_shout` (
  `id` int(10) UNSIGNED NOT NULL,
  `nick` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `time` int(10) UNSIGNED NOT NULL,
  `is_guest` bit(1) NOT NULL,
  `text` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `help_page` (
  `label` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `language` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `security_cost` (
  `address` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `active_amount` int(10) UNSIGNED NOT NULL,
  `active_expire` int(10) UNSIGNED NOT NULL,
  `decay_amount` int(10) UNSIGNED NOT NULL
) ENGINE=MEMORY DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `survey_poll` (
  `id` int(10) UNSIGNED NOT NULL,
  `type` tinyint(3) UNSIGNED NOT NULL,
  `question` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL,
  `votes` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `survey_poll_choice` (
  `poll` int(10) UNSIGNED NOT NULL,
  `rank` int(10) UNSIGNED NOT NULL,
  `text` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL,
  `score` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `survey_poll_vote` (
  `poll` int(10) UNSIGNED NOT NULL,
  `user` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE `account_activity`
  ADD PRIMARY KEY (`address`),
  ADD KEY `expire` (`expire_time`);

ALTER TABLE `account_application`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user` (`user`);

ALTER TABLE `account_memo`
  ADD PRIMARY KEY (`user`);

ALTER TABLE `account_message`
  ADD PRIMARY KEY (`id`),
  ADD KEY `time` (`time`);

ALTER TABLE `account_message_copy`
  ADD PRIMARY KEY (`message`,`recipient`),
  ADD KEY `recipient__hidden__time` (`recipient`,`hidden`),
  ADD KEY `recipient__read__time` (`recipient`,`read`);

ALTER TABLE `account_user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `login` (`login`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `pulse_time` (`pulse_time`),
  ADD KEY `is_admin` (`is_admin`);

ALTER TABLE `board_ban`
  ADD PRIMARY KEY (`forum`,`address`);

ALTER TABLE `board_block`
  ADD PRIMARY KEY (`forum`,`rank`),
  ADD KEY `section` (`section`);

ALTER TABLE `board_bookmark`
  ADD PRIMARY KEY (`profile`,`topic`),
  ADD KEY `topic__watch__fresh` (`topic`,`watch`,`fresh`),
  ADD KEY `profile__watch__fresh__time` (`profile`,`watch`,`fresh`,`time`);

ALTER TABLE `board_favorite`
  ADD PRIMARY KEY (`profile`,`rank`),
  ADD UNIQUE KEY `profile_order_forum` (`profile`,`rank`,`forum`);

ALTER TABLE `board_forum`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `alias` (`alias`);
ALTER TABLE `board_forum` ADD FULLTEXT KEY `name_full` (`name`);

ALTER TABLE `board_ignore`
  ADD PRIMARY KEY (`profile`,`target`);

ALTER TABLE `board_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `forum__time` (`forum`,`time`);

ALTER TABLE `board_permission_forum`
  ADD PRIMARY KEY (`forum`,`profile`),
  ADD KEY `board_permission_forum__profile` (`profile`),
  ADD KEY `expire` (`expire`);

ALTER TABLE `board_permission_section`
  ADD PRIMARY KEY (`section`,`profile`),
  ADD KEY `board_permission_section__profile` (`profile`),
  ADD KEY `expire` (`expire`);

ALTER TABLE `board_permission_topic`
  ADD PRIMARY KEY (`topic`,`profile`),
  ADD KEY `board_permission_topic__profile` (`profile`),
  ADD KEY `expire` (`expire`);

ALTER TABLE `board_post`
  ADD PRIMARY KEY (`id`),
  ADD KEY `create_profile` (`create_profile`),
  ADD KEY `create_time` (`create_time`),
  ADD KEY `edit_time` (`edit_time`);

ALTER TABLE `board_post_index`
  ADD PRIMARY KEY (`post`);
ALTER TABLE `board_post_index` ADD FULLTEXT KEY `text` (`text`);

ALTER TABLE `board_profile`
  ADD PRIMARY KEY (`user`),
  ADD KEY `forum` (`forum`);

ALTER TABLE `board_profile_cache`
  ADD PRIMARY KEY (`user`),
  ADD KEY `posts` (`posts`);

ALTER TABLE `board_reference`
  ADD PRIMARY KEY (`topic`,`position`),
  ADD KEY `post` (`post`);

ALTER TABLE `board_search`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `board_search_result`
  ADD PRIMARY KEY (`search`,`position`,`topic`);

ALTER TABLE `board_section`
  ADD PRIMARY KEY (`id`),
  ADD KEY `forum` (`forum`);

ALTER TABLE `board_section_cache`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `board_section_read`
  ADD PRIMARY KEY (`section`,`profile`);

ALTER TABLE `board_subscription`
  ADD PRIMARY KEY (`section`,`profile`),
  ADD KEY `board_subscription__profile` (`profile`);

ALTER TABLE `board_topic`
  ADD PRIMARY KEY (`id`),
  ADD KEY `last_time` (`last_time`),
  ADD KEY `section__weight__last_time` (`section`,`weight`,`last_time`),
  ADD KEY `section__last_time` (`section`,`last_time`);

ALTER TABLE `board_topic_cache`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `chat_shout`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `help_page`
  ADD PRIMARY KEY (`label`,`language`);

ALTER TABLE `security_cost`
  ADD PRIMARY KEY (`address`);

ALTER TABLE `survey_poll`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `survey_poll_choice`
  ADD PRIMARY KEY (`poll`,`rank`);

ALTER TABLE `survey_poll_vote`
  ADD PRIMARY KEY (`poll`,`user`);


ALTER TABLE `account_application`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `account_message`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `account_user`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `board_block`
  MODIFY `rank` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `board_favorite`
  MODIFY `rank` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `board_forum`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `board_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `board_post`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `board_reference`
  MODIFY `position` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `board_search`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `board_section`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `board_topic`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `chat_shout`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `survey_poll`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `survey_poll_choice`
  MODIFY `rank` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
