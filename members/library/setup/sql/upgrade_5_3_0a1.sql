DROP TABLE IF EXISTS `translations`;
CREATE TABLE `translations` (
  `type` int(11) NOT NULL,
  `itemid` int(11) NOT NULL,
  `language` varchar(200) NOT NULL,
  `value` longtext NOT NULL,
  PRIMARY KEY ( `type` , `itemid` , `language` )
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

ALTER TABLE  `users` CHANGE  `password`  `password` VARCHAR( 60 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ;
ALTER TABLE  `users` ADD  `remember_token` VARCHAR( 100 ) NULL AFTER  `password` ;
ALTER TABLE  `users` ADD  `updated_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE  `users` ADD  `created_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00';

#updating user customfields
ALTER TABLE `user_customuserfields` ADD  `updated_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE `user_customuserfields` ADD  `created_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE `user_customuserfields` DROP PRIMARY KEY;
ALTER TABLE `user_customuserfields` ADD  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT FIRST , ADD PRIMARY KEY (  `id` ) ;
ALTER TABLE `user_customuserfields` ADD UNIQUE INDEX `userid_customid` (`userid`,`customid`);

CREATE TABLE `password_resets` (
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  KEY `password_resets_email_index` (`email`),
  KEY `password_resets_token_index` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `troubleticket_type` ADD `allowclose` TINYINT(1) NOT NULL DEFAULT '1' ;

INSERT INTO `autoresponders` (`type`, `name`, `subject`, `contents`, `helpid`, `description`) VALUES (4, 'Domain Expiration Reminder Template', 'Final Notice: [DOMAIN] has expired on [EXPDATE]', 'ATTN: [CLIENTNAME],<br><br>We just wanted to let you know that [DOMAINNAME] expired on [EXPDATE].\r\n<br><br>You can still renew this name until it either gets deleted or goes into redemption status by logging into your account at [CLIENTAPPLICATIONURL]<br><br>[COMPANYNAME]<br>[BILLINGEMAIL]', 0, '');

#updating customfield types of prefences
UPDATE  `customuserfields` SET  `type` =  '100' WHERE  `name` = 'Support-TicketReplyTop';
UPDATE  `customuserfields` SET  `type` =  '101' WHERE  `name` = 'Sitewide-DefaultActiveUserPanel';
UPDATE  `customuserfields` SET  `desc` =  'Most recent replies will also appear on top' WHERE  `name` = 'Support-TicketReplyTop';
UPDATE  `customuserfields` SET  `desc` =  'Active customer will appear by default' WHERE  `name` = 'Sitewide-DefaultActiveUserPanel';
UPDATE  `customuserfields` SET  `name` =  'Sitewide-ShowActiveUserPanel' WHERE  `name` = 'Sitewide-DefaultActiveUserPanel';
UPDATE  `customuserfields` SET  `name` =  'Support-TicketReplyOnTop' WHERE  `name` = 'Support-TicketReplyTop';
