CREATE PROCEDURE `account_purge`(IN `days` INT UNSIGNED)
    NO SQL
BEGIN

DELETE m FROM board_profile m
JOIN account_user u ON u.id = m.user
WHERE
	u.create_time < UNIX_TIMESTAMP() - days * 86400 AND
	u.pulse_time < UNIX_TIMESTAMP() - days * 86400 AND
	NOT EXISTS
	(
		SELECT 1 FROM account_memo m WHERE m.user = u.id UNION ALL
		SELECT 1 FROM board_favorite f WHERE f.profile = u.id UNION ALL
		SELECT 1 FROM board_permission_forum pf WHERE pf.profile = u.id UNION ALL
		SELECT 1 FROM board_permission_section ps WHERE ps.profile = u.id UNION ALL
		SELECT 1 FROM board_permission_topic pt WHERE pt.profile = u.id UNION ALL
		SELECT 1 FROM board_subscription s WHERE s.profile = u.id UNION ALL
		SELECT 1 FROM board_post p WHERE p.create_profile = u.id UNION ALL
		SELECT 1 FROM board_topic t WHERE t.create_profile = u.id UNION ALL
		SELECT 1 FROM account_message m WHERE m.sender = u.id UNION ALL
		SELECT 1 FROM account_message_copy mc WHERE mc.recipient = u.id
	);

DELETE u FROM account_user u WHERE NOT EXISTS (SELECT 1 FROM board_profile m WHERE m.user = u.id);

END

CREATE PROCEDURE `board_clean`()
    NO SQL
BEGIN

DELETE b FROM board_bookmark b LEFT JOIN board_profile m ON m.user = b.profile LEFT JOIN board_topic t ON t.id = b.topic WHERE m.user IS NULL OR t.id IS NULL;
DELETE f FROM board_favorite f LEFT JOIN board_profile m ON m.user = f.profile LEFT JOIN board_forum bf ON bf.id = f.forum WHERE f.profile <> 0 AND (m.user IS NULL OR bf.id IS NULL);
DELETE p FROM board_permission_forum p LEFT JOIN board_profile m ON m.user = p.profile LEFT JOIN board_forum f ON f.id = p.forum WHERE m.user IS NULL OR f.id IS NULL;
DELETE p FROM board_permission_section p LEFT JOIN board_profile m ON m.user = p.profile LEFT JOIN board_section s ON s.id = p.section WHERE m.user IS NULL OR s.id IS NULL;
DELETE p FROM board_permission_topic p LEFT JOIN board_profile m ON m.user = p.profile LEFT JOIN board_topic t ON t.id = p.topic WHERE m.user IS NULL OR t.id IS NULL;
DELETE s FROM board_subscription s LEFT JOIN board_profile m ON m.user = s.profile LEFT JOIN board_section bs ON bs.id = s.section WHERE m.user IS NULL OR bs.id IS NULL;

END

CREATE PROCEDURE `board_forum_popular`(IN `days` INT UNSIGNED)
    NO SQL
BEGIN

SET @row_number = 0;

DELETE FROM
	board_favorite
WHERE
	`profile` = 728;

INSERT INTO
	board_favorite (`profile`, `order`, `forum`)
SELECT
	728,
	(@row_number := @row_number + 1),
	f.id
FROM board_post p
JOIN board_reference r ON r.post = p.id
JOIN board_topic t ON t.id = r.topic
JOIN board_section s ON s.id = t.section
JOIN board_forum f ON f.id = s.forum
WHERE f.is_hidden = 0 AND p.create_time BETWEEN UNIX_TIMESTAMP() - @days * 86400 AND UNIX_TIMESTAMP()
GROUP BY f.id
ORDER BY COUNT(p.id) DESC
LIMIT 12;

END

CREATE PROCEDURE `board_profile_merge`(IN `profile_new` INT, IN `profile_old` INT)
    NO SQL
BEGIN

UPDATE account_message SET sender = @profile_new WHERE sender = @profile_old;
UPDATE account_message_copy SET recipient = @profile_new WHERE recipient = @profile_old;

UPDATE board_bookmark b
LEFT JOIN board_bookmark x ON x.profile = @profile_new AND x.topic = b.topic
SET b.profile = @profile_new
WHERE b.profile = @profile_old AND x.profile IS NULL;

DELETE FROM board_bookmark WHERE profile = @profile_old;

UPDATE board_permission_forum p
LEFT JOIN board_permission_forum x ON x.profile = @profile_new AND x.forum = p.forum
SET p.profile = @profile_new
WHERE p.profile = @profile_old AND x.profile IS NULL;

DELETE FROM board_permission_forum WHERE profile = @profile_old;

UPDATE board_permission_section p
LEFT JOIN board_permission_section x ON x.profile = @profile_new AND x.section = p.section
SET p.profile = @profile_new
WHERE p.profile = @profile_old AND x.profile IS NULL;

DELETE FROM board_permission_section WHERE profile = @profile_old;

UPDATE board_permission_topic p
LEFT JOIN board_permission_topic x ON x.profile = @profile_new AND x.topic = p.topic
SET p.profile = @profile_new
WHERE p.profile = @profile_old AND x.profile IS NULL;

DELETE FROM board_permission_topic WHERE profile = @profile_old;

UPDATE board_post SET create_profile = @profile_new WHERE create_profile = @profile_old;
UPDATE board_post SET edit_profile = @profile_new WHERE edit_profile = @profile_old;

UPDATE board_subscription p
LEFT JOIN board_subscription x ON x.profile = @profile_new AND x.section = p.section
SET p.profile = @profile_new
WHERE p.profile = @profile_old AND x.profile IS NULL;

DELETE FROM board_subscription WHERE profile = @profile_old;

DELETE FROM board_profile_cache WHERE user = @profile_old;

END

CREATE PROCEDURE `board_purge`()
    NO SQL
BEGIN

DELETE s FROM board_section s WHERE NOT EXISTS (SELECT 1 FROM board_topic t WHERE t.section = s.id);
DELETE b FROM board_block b WHERE b.section IS NOT NULL AND NOT EXISTS (SELECT 1 FROM board_section s WHERE s.id = b.section);
DELETE f FROM board_forum f WHERE NOT EXISTS (SELECT 1 FROM board_block b WHERE b.forum = f.id);

END

CREATE PROCEDURE `account_user_swap`(IN `from` INT, IN `to` INT)
	NO SQL
BEGIN

UPDATE `account_memo` SET user = 2147483647 WHERE user = @from;
UPDATE `account_memo` SET user = @from WHERE user = @to;
UPDATE `account_memo` SET user = @to WHERE user = 2147483647;

​UPDATE `account_message` SET sender = 2147483647 WHERE sender = @from;
UPDATE `account_message` SET sender = @from WHERE sender = @to;
UPDATE `account_message` SET sender = @to WHERE sender = 2147483647;

​UPDATE `account_message_copy` SET recipient = 2147483647 WHERE recipient = @from;
UPDATE `account_message_copy` SET recipient = @from WHERE recipient = @to;
UPDATE `account_message_copy` SET recipient = @to WHERE recipient = 2147483647;

​UPDATE `account_user` SET id = 2147483647 WHERE id = @from;
UPDATE `account_user` SET id = @from WHERE id = @to;
UPDATE `account_user` SET id = @to WHERE id = 2147483647;

​UPDATE `board_bookmark` SET profile = 2147483647 WHERE profile = @from;
UPDATE `board_bookmark` SET profile = @from WHERE profile = @to;
UPDATE `board_bookmark` SET profile = @to WHERE profile = 2147483647;

​UPDATE `board_favorite` SET profile = 2147483647 WHERE profile = @from;
UPDATE `board_favorite` SET profile = @from WHERE profile = @to;
UPDATE `board_favorite` SET profile = @to WHERE profile = 2147483647;

​UPDATE `board_ignore` SET profile = 2147483647 WHERE profile = @from;
UPDATE `board_ignore` SET profile = @from WHERE profile = @to;
UPDATE `board_ignore` SET profile = @to WHERE profile = 2147483647;
​UPDATE `board_ignore` SET target = 2147483647 WHERE target = @from;
UPDATE `board_ignore` SET target = @from WHERE target = @to;
UPDATE `board_ignore` SET target = @to WHERE target = 2147483647;

​UPDATE `board_log` SET profile = 2147483647 WHERE profile = @from;
UPDATE `board_log` SET profile = @from WHERE profile = @to;
UPDATE `board_log` SET profile = @to WHERE profile = 2147483647;

​UPDATE `board_permission_forum` SET profile = 2147483647 WHERE profile = @from;
UPDATE `board_permission_forum` SET profile = @from WHERE profile = @to;
UPDATE `board_permission_forum` SET profile = @to WHERE profile = 2147483647;

​UPDATE `board_permission_section` SET profile = 2147483647 WHERE profile = @from;
UPDATE `board_permission_section` SET profile = @from WHERE profile = @to;
UPDATE `board_permission_section` SET profile = @to WHERE profile = 2147483647;

​UPDATE `board_permission_topic` SET profile = 2147483647 WHERE profile = @from;
UPDATE `board_permission_topic` SET profile = @from WHERE profile = @to;
UPDATE `board_permission_topic` SET profile = @to WHERE profile = 2147483647;

​UPDATE `board_post` SET create_profile = 2147483647 WHERE create_profile = @from;
UPDATE `board_post` SET create_profile = @from WHERE create_profile = @to;
UPDATE `board_post` SET create_profile = @to WHERE create_profile = 2147483647;

​UPDATE `board_post` SET edit_profile = 2147483647 WHERE edit_profile = @from;
UPDATE `board_post` SET edit_profile = @from WHERE edit_profile = @to;
UPDATE `board_post` SET edit_profile = @to WHERE edit_profile = 2147483647;

​UPDATE `board_post_index` SET create_profile = 2147483647 WHERE create_profile = @from;
UPDATE `board_post_index` SET create_profile = @from WHERE create_profile = @to;
UPDATE `board_post_index` SET create_profile = @to WHERE create_profile = 2147483647;

​UPDATE `board_profile` SET user = 2147483647 WHERE user = @from;
UPDATE `board_profile` SET user = @from WHERE user = @to;
UPDATE `board_profile` SET user = @to WHERE user = 2147483647;

​UPDATE `board_profile_cache` SET user = 2147483647 WHERE user = @from;
UPDATE `board_profile_cache` SET user = @from WHERE user = @to;
UPDATE `board_profile_cache` SET user = @to WHERE user = 2147483647;

​UPDATE `board_search` SET profile = 2147483647 WHERE profile = @from;
UPDATE `board_search` SET profile = @from WHERE profile = @to;
UPDATE `board_search` SET profile = @to WHERE profile = 2147483647;

​UPDATE `board_section_read` SET profile = 2147483647 WHERE profile = @from;
UPDATE `board_section_read` SET profile = @from WHERE profile = @to;
UPDATE `board_section_read` SET profile = @to WHERE profile = 2147483647;

​UPDATE `board_subscription` SET profile = 2147483647 WHERE profile = @from;
UPDATE `board_subscription` SET profile = @from WHERE profile = @to;
UPDATE `board_subscription` SET profile = @to WHERE profile = 2147483647;

​UPDATE `board_topic_cache` SET create_profile = 2147483647 WHERE create_profile = @from;
UPDATE `board_topic_cache` SET create_profile = @from WHERE create_profile = @to;
UPDATE `board_topic_cache` SET create_profile = @to WHERE create_profile = 2147483647;

​UPDATE `board_topic_cache` SET last_profile = 2147483647 WHERE last_profile = @from;
UPDATE `board_topic_cache` SET last_profile = @from WHERE last_profile = @to;
UPDATE `board_topic_cache` SET last_profile = @to WHERE last_profile = 2147483647;

​UPDATE `survey_poll_vote` SET user = 2147483647 WHERE user = @from;
UPDATE `survey_poll_vote` SET user = @from WHERE user = @to;
UPDATE `survey_poll_vote` SET user = @to WHERE user = 2147483647;

END
