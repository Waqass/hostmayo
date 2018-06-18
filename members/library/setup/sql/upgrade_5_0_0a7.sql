ALTER TABLE `customField` CHANGE `desc` `desc` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';
ALTER TABLE `customuserfields` CHANGE `desc` `desc` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';
DELETE from user_customuserfields where customid IN (select id from customuserfields where name = 'DashboardState');
DELETE from user_customuserfields where customid IN (select id from customuserfields where name = 'InvoiceTicketGridState');
DELETE from user_customuserfields where customid IN (select id from customuserfields where name = 'SupportTicketGridState');
DELETE from user_customuserfields where customid IN (select id from customuserfields where name = 'ClientListGridState');
DELETE from user_customuserfields where customid IN (select id from customuserfields where name = 'DomainsListGridState');
DELETE from user_customuserfields where customid IN (select id from customuserfields where name = 'AnnouncementGridState');
Delete from customuserfields where name = 'InvoiceTicketGridState';
Delete from customuserfields where name = 'SupportTicketGridState';
Delete from customuserfields where name = 'ClientListGridState';
Delete from customuserfields where name = 'DomainsListGridState';
Delete from customuserfields where name = 'AnnouncementGridState';
ALTER TABLE `calendar`
    CHANGE `allday` `allday` BOOLEAN DEFAULT '0' NOT NULL,
    CHANGE `start` `start` DATETIME NULL,
    CHANGE `end` `end` DATETIME NULL,
    CHANGE `url` `url` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
    CHANGE `className` `className` VARCHAR(56) CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
    CHANGE `appliesto` `appliesto` INT(11) DEFAULT '0' NULL,
    CHANGE `description` `description` TEXT NULL,
    CHANGE `isprivate` `isprivate` BOOLEAN DEFAULT '0' NOT NULL,
    CHANGE `isrepeating` `isrepeating` BOOLEAN DEFAULT '0' NOT NULL,
    ADD COLUMN `company_id` INT DEFAULT '0' NOT NULL AFTER `isrepeating`;
UPDATE `calendar` c JOIN `users` u ON u.`id` = c.`userid` SET c.`company_id` = u.`company_id` ;