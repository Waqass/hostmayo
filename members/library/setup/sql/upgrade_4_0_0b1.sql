# ---------------------------------------------------------
# UPDATES TO Database tables for utf8 compliance
# ---------------------------------------------------------
ALTER TABLE `events_log` DEFAULT CHARACTER SET utf8;
ALTER TABLE `events_log` CHANGE `entity_type` `entity_type` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `events_log` CHANGE `action` `action` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `events_log` CHANGE `ip` `ip` varchar(15) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL  DEFAULT '0';
ALTER TABLE `events_log` CHANGE `params` `params` text CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `cache` DEFAULT CHARACTER SET utf8;
ALTER TABLE `cache` CHANGE `cachekey` `cachekey` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `country` DEFAULT CHARACTER SET utf8;
ALTER TABLE `country` CHANGE `name` `name` varchar(80) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `country` CHANGE `iso` `iso` char(2) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `country` CHANGE `phone_code` `phone_code` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `permissions` DEFAULT CHARACTER SET utf8;
ALTER TABLE `permissions` CHANGE `permission` `permission` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `setting` DEFAULT CHARACTER SET utf8;
ALTER TABLE `setting` CHANGE `name` `name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci  NULL  ;
ALTER TABLE `setting` CHANGE `value` `value` text CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `setting` CHANGE `value_alternate` `value_alternate` text CHARACTER SET utf8 COLLATE utf8_general_ci  NULL  ;
ALTER TABLE `setting` CHANGE `description` `description` text CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `versions` DEFAULT CHARACTER SET utf8;
ALTER TABLE `versions` CHANGE `module` `module` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `versions` CHANGE `version` `version` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL ;
ALTER TABLE `user_groups` DEFAULT CHARACTER SET utf8;