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

INSERT INTO `statusalias` (`id`, `statusid`, `name`, `aliasto`, `type`, `system`, `order`) VALUES (1, 0,  'Pending',  NULL,   1,  1, 1);
INSERT INTO `statusalias` (`id`, `statusid`, `name`, `aliasto`, `type`, `system`, `order`) VALUES (2, 1,  'Active',   NULL,   1,  1, 2);
INSERT INTO `statusalias` (`id`, `statusid`, `name`, `aliasto`, `type`, `system`, `order`) VALUES (3, 2,  'Suspended',    NULL,   1,  1, 3);
INSERT INTO `statusalias` (`id`, `statusid`, `name`, `aliasto`, `type`, `system`, `order`) VALUES (4, 3,  'Cancelled',    NULL,   1,  1, 4);
INSERT INTO `statusalias` (`id`, `statusid`, `name`, `aliasto`, `type`, `system`, `order`) VALUES (5, 4,  'Pending Cancelation',  NULL,   1,  1, 5);
INSERT INTO `statusalias` (`id`, `statusid`, `name`, `aliasto`, `type`, `system`, `order`) VALUES (6, 5,  'Expired',  NULL,   1,  1, 6);

INSERT INTO `statusalias` (`id`, `statusid`, `name`, `aliasto`, `type`, `system`, `order`) VALUES (7, 0,  'Unassigned',   NULL,   2,  1, 1);
INSERT INTO `statusalias` (`id`, `statusid`, `name`, `aliasto`, `type`, `system`, `order`) VALUES (8, 1,  'Open', NULL,   2,  1, 2);
INSERT INTO `statusalias` (`id`, `statusid`, `name`, `aliasto`, `type`, `system`, `order`) VALUES (9, 2,  'In Progress',  NULL,   2,  1, 3);
INSERT INTO `statusalias` (`id`, `statusid`, `name`, `aliasto`, `type`, `system`, `order`) VALUES (10, 3,  'Waiting on Customer',  NULL,   2,  1, 4);
INSERT INTO `statusalias` (`id`, `statusid`, `name`, `aliasto`, `type`, `system`, `order`) VALUES (11, -1, 'Closed',   NULL,   2,  1, 5);

INSERT INTO `statusalias` (`id`, `statusid`, `name`, `aliasto`, `type`, `system`, `order`) VALUES (12,    0,  'Pending',  NULL,   3,  1, 1);
INSERT INTO `statusalias` (`id`, `statusid`, `name`, `aliasto`, `type`, `system`, `order`) VALUES (13,    1,  'Active',   NULL,   3,  1, 2);
INSERT INTO `statusalias` (`id`, `statusid`, `name`, `aliasto`, `type`, `system`, `order`) VALUES (14,    -1, 'Inactive', NULL,   3,  1, 3);
INSERT INTO `statusalias` (`id`, `statusid`, `name`, `aliasto`, `type`, `system`, `order`) VALUES (15,    -2, 'Cancelled',    NULL,   3,  1, 4);
INSERT INTO `statusalias` (`id`, `statusid`, `name`, `aliasto`, `type`, `system`, `order`) VALUES (16,    -3, 'Fraud',    NULL,   3,  1, 5);

ALTER TABLE  `kb_categories` ADD  `is_series` TINYINT( 1 ) NOT NULL DEFAULT  '0' COMMENT  'articles are meant to be followed as a series';
ALTER TABLE  `kb_categories` ADD  `is_global_series` TINYINT( 1 ) NOT NULL DEFAULT  '0' COMMENT  'view all global series together in sidebar';
ALTER TABLE  `kb_categories` ADD  `my_order` INT NOT NULL DEFAULT  '0';
ALTER TABLE  `users` ADD  `invoice_template` VARCHAR( 45 ) NOT NULL DEFAULT  '';