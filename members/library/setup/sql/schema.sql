# --------------------------------------------------------
#
# Table structure for table `events_log`
#

DROP TABLE IF EXISTS `events_log`;
CREATE TABLE `events_log` (
  `id` int(11) NOT NULL auto_increment,
  `date` datetime NOT NULL,
  `company_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `subject` int(11) NOT NULL,
  `ip` varchar(15) NOT NULL DEFAULT '0',
  `params` text NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;
ALTER TABLE `events_log` ADD INDEX `subject` ( `subject` );
ALTER TABLE `events_log` ADD INDEX `iDate` ( `date` );
ALTER TABLE `events_log` ADD INDEX `i_userid` (  `user_id` );
ALTER TABLE `events_log` ADD INDEX `i_event_type` (`entity_type`);
ALTER TABLE `events_log` ADD INDEX `ip` (`ip`);
ALTER TABLE `events_log` ADD INDEX (`entity_id`);

# --------------------------------------------------------

#
# Table structure for table `cache`
#

DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `cachekey` varchar(50) NOT NULL default '',
  `lastupdated` datetime NOT NULL default '0000-00-00 00:00:00',
  `content` mediumblob NOT NULL,
  PRIMARY KEY  (`cachekey`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

# --------------------------------------------------------

#
# Table structure for table `country`
#

DROP TABLE IF EXISTS `country`;
CREATE TABLE `country` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(80) NOT NULL default '',
  `iso` char(2) NOT NULL default '',
  `division` varchar(50) NOT NULL default 'Division',
  `division_plural` varchar(50) NOT NULL default 'Divisions',
  `phone_code` varchar(10) NOT NULL default '',
  `exists` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`id`),
  KEY `by_name` (`name`,`iso`),
  KEY `by_iso` (`iso`,`name`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

# --------------------------------------------------------

#
# Table structure for table `permissions`
#

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `subject_id` int(11) NOT NULL default '0',
  `is_group` tinyint(4) NOT NULL default '0',
  `permission` varchar(100) NOT NULL default '',
  `target_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`subject_id`,`is_group`,`permission`,`target_id`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

# --------------------------------------------------------

#
# Table structure for table `setting`
#

DROP TABLE IF EXISTS `setting`;
CREATE TABLE `setting` (
  `id` int(11) NOT NULL auto_increment,
  `company_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(100) default NULL,
  `value` text DEFAULT  NULL,
  `value_alternate` text DEFAULT  NULL,
  `description` text DEFAULT  NULL,
  `type` tinyint(4) NOT NULL default '0',
  `isrequired` tinyint(4) NOT NULL default '1',
  `istruefalse` tinyint(4) NOT NULL default '0',
  `istextarea` tinyint(4) NOT NULL default '0',
  `issmalltextarea` tinyint(4) NOT NULL default '0',
  `isfromoptions` tinyint(4) NOT NULL default '0',
  `myorder` int(11) NOT NULL default '0',
  `helpid` int(3) default '0',
  `plugin` tinyint(4) NOT NULL default '0',
  `ispassword` tinyint(4) NOT NULL default '0',
  `ishidden` tinyint(4) NOT NULL default '0',
  `issession` TINYINT NOT NULL DEFAULT '1',
  UNIQUE KEY `id` (`id`),
  KEY `plugin` (`plugin`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM AUTO_INCREMENT=133 ;
ALTER TABLE  `setting` ADD INDEX  `i_name` (  `name` );
ALTER TABLE  `setting` ADD INDEX  `session` (  `issession` );

# --------------------------------------------------------

#
# Table structure for table `versions`
#

DROP TABLE IF EXISTS `versions`;
CREATE TABLE `versions` (
    `module` VARCHAR( 50 ) NOT NULL ,
    `version` VARCHAR( 50 ) NOT NULL,
    PRIMARY KEY ( `module` )
) DEFAULT CHARACTER SET utf8, ENGINE = MYISAM ;

# --------------------------------------------------------

#
# Table structure for table `user_groups`
#

DROP TABLE IF EXISTS `user_groups`;
CREATE TABLE `user_groups` (
    `user_id` INT NOT NULL ,
    `group_id` INT NOT NULL ,
    PRIMARY KEY ( `user_id` , `group_id` )
) DEFAULT CHARACTER SET utf8, ENGINE = MYISAM ;

# --------------------------------------------------------

#
# Table structure for table `notifications_events`
#

DROP TABLE IF EXISTS `notifications_events`;
CREATE TABLE `notifications_events` (
  `id` int(11) NOT NULL auto_increment,
  `name` text NOT NULL,
  `templateid` int(11) NOT NULL,
  `rules` text NOT NULL,
  `enabled` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`id`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

# --------------------------------------------------------

#
# Table structure for table `user_notifications`
#

DROP TABLE IF EXISTS `user_notifications`;
CREATE TABLE `user_notifications` (
  `id` int(11) NOT NULL auto_increment,
  `object_type` varchar(50) NOT NULL,
  `object_id` int(11) NOT NULL,
  `rule_id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

# --------------------------------------------------------

CREATE TABLE `webhooks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(400) NOT NULL,
  `eventtype` int(11) DEFAULT '1',
  `providertype` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

CREATE TABLE `statusalias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `statusid` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `aliasto` int(11) DEFAULT NULL,
  `type` int(11) NOT NULL,
  `system` int(1) NOT NULL,
  `order` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

CREATE TABLE `password_resets` (
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  KEY `password_resets_email_index` (`email`),
  KEY `password_resets_token_index` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;