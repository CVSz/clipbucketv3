-- Please Put all the DB changes here
-- Also add those changes that have been made and not been added

ALTER TABLE `video` ADD `userid` INT NOT NULL AFTER `username` 

ALTER TABLE `video_comments` ADD `userid` INT NOT NULL AFTER `username` 

ALTER TABLE `channel_comments` ADD `userid` INT NOT NULL AFTER `username` 

ALTER TABLE `groups` ADD `userid` INT NOT NULL AFTER `username` 

ALTER TABLE `group_members` ADD `userid` INT NOT NULL AFTER `username` 

ALTER TABLE `group_posts` ADD `userid` INT NOT NULL AFTER `username` 

ALTER TABLE `group_topics` ADD `userid` INT NOT NULL AFTER `username` 

ALTER TABLE `group_videos` ADD `userid` INT NOT NULL AFTER `username` 

ALTER TABLE `messages` ADD `inbox_user_id` INT NOT NULL AFTER `sender` ,
ADD `outbox_user_id` INT NOT NULL AFTER `inbox_user_id` ,
ADD `sender_id` INT NOT NULL AFTER `outbox_user_id` ,
ADD `reciever_id` INT NOT NULL AFTER `sender_id` 

ALTER TABLE `subscriptions` ADD `subscriber_id` INT NOT NULL AFTER `subscribed_user` ,
ADD `userid` INT NOT NULL AFTER `subscriber_id` 

ALTER TABLE  `plugins` ADD  `plugin_folder` TEXT NOT NULL AFTER  `plugin_file` ;

ALTER TABLE `user_permissions` ADD `input_type` ENUM( 'text', 'radio', 'select', 'textarea' ) NOT NULL DEFAULT 'radio';
CREATE TABLE IF NOT EXISTS `wallets` (
  `wallet_id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `userid` INT(11) NOT NULL,
  `balance` DECIMAL(12,2) NOT NULL DEFAULT '0.00',
  `date_added` DATETIME NOT NULL,
  `last_updated` DATETIME NOT NULL,
  PRIMARY KEY (`wallet_id`),
  UNIQUE KEY `userid_unique` (`userid`),
  KEY `userid_idx` (`userid`)
);

CREATE TABLE IF NOT EXISTS `wallet_transactions` (
  `transaction_id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `userid` INT(11) NOT NULL,
  `related_userid` INT(11) DEFAULT NULL,
  `transaction_type` ENUM('credit','debit') NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `date_added` DATETIME NOT NULL,
  PRIMARY KEY (`transaction_id`),
  KEY `wallet_userid_idx` (`userid`),
  KEY `wallet_related_userid_idx` (`related_userid`)
);
