DELETE from user_customuserfields where customid IN (select id from customuserfields where name='TABLESORT_snapshot_domains');
DELETE from customuserfields where name='TABLESORT_snapshot_domains';
DELETE from user_customuserfields where customid IN (select id from customuserfields where name='Dashboard graph legend');
DELETE from customuserfields where name='Dashboard graph legend';
DELETE from user_customuserfields where customid IN (select id from customuserfields where name='Dashboard graph totals');
DELETE from customuserfields where name='Dashboard graph totals';
DELETE from user_customuserfields where customid IN (select id from customuserfields where name='Dashboard graph totals');
DELETE from customuserfields where name='Dashboard graph totals';
DELETE from user_customuserfields where customid IN (select id from customuserfields where name='QUICK_REPORTS');
DELETE from customuserfields where name='QUICK_REPORTS';
DELETE from user_customuserfields where customid IN (select id from customuserfields where name='selectedDashboardTab');
DELETE from customuserfields where name='selectedDashboardTab';
DELETE from user_customuserfields where customid IN (select id from customuserfields where name='DASHBOARD_ARTICLESFILTER');
DELETE from customuserfields where name='DASHBOARD_ARTICLESFILTER';
DELETE from user_customuserfields where customid IN (select id from customuserfields where name='SupportTicketGridRefreshRate');
DELETE from customuserfields where name='SupportTicketGridRefreshRate';

ALTER TABLE  `files` ADD  `roomid` varchar(20) NOT NULL DEFAULT  '0';
ALTER TABLE `events_log` ADD INDEX `ip` (`ip`);

INSERT INTO autoresponders (`type`,`name`,subject,contents,contents_html,description,helpid) VALUES( 5, 'Activate Account Template','Confirm Account',"[CLIENTNAME],\r\n\r\nWelcome to [COMPANYNAME]. You have created a new account with us.\r\n\r\nBy clicking on the confirmation link below your account will be activated and an email will be sent with a new password. It is recommended to change it after logging in.\r\n\r\nConfirmation URL: [CONFIRMATION URL]\r\n\r\nThank you,\r\n[COMPANYNAME]","<HTML><head></head><body>[CLIENTNAME],<br />\r\n<br />\r\nWelcome to [COMPANYNAME]. You have created a new account with us.<br />\r\n<br />\r\nBy clicking on the confirmation link below your account will be activated and an email will be sent with a new password. It is recommended to change it after logging in.<br />\r\n<br />\r\nConfirmation URL: [CONFIRMATION URL]<br />\r\n<br />\r\nThank you,<br />\r\n[COMPANYNAME]</body></HTML>",'Initial E-mail customer receives to confirm an account activation.',53);
INSERT INTO autoresponders (`type`,`name`,subject,contents,contents_html,description,helpid) VALUES( 5, 'Get New Account Password Template','New Account Password',"[CLIENTNAME],\r\n\r\nYour account has been activated. Your new password is: [NEWPASSWORD]\r\n\r\nPlease goto [CLIENTEXEC URL] to login.\r\n\r\nThank you,\r\n[COMPANYNAME]\r\n[COMPANYEMAIL]","<HTML><head></head><body>[CLIENTNAME],<br />\r\n<br />\r\nYour account has been activated. Your new password is: [NEWPASSWORD]<br />\r\n<br />\r\nPlease goto [CLIENTEXEC URL] to login.<br />\r\n<br />\r\nThank you,<br />\r\n[COMPANYNAME]<br />\r\n[COMPANYEMAIL]</body></HTML>",'Initial E-mail the customer receives to know the password to their activated account.',54);

CREATE TABLE `webhooks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(400) NOT NULL,
  `eventtype` int(11) DEFAULT '1',
  `providertype` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8, ENGINE=MyISAM;

ALTER TABLE `kb_articles_comments` DROP PRIMARY KEY; 
ALTER TABLE `kb_articles_comments` ADD `commentid` INT NOT NULL AUTO_INCREMENT FIRST ,ADD PRIMARY KEY (`commentid`) ;