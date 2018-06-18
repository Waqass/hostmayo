ALTER TABLE `setting` ADD `company_id` INT(11) NOT NULL default '0' AFTER `id`;
ALTER TABLE `events_log` ADD `company_id` INT(11) NOT NULL default '0' AFTER `date`;

delete from setting where name='Default Country Name 2';

update setting set type=0 where type > 0;

ALTER TABLE  `setting` CHANGE  `value`  `value` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT  NULL;
ALTER TABLE  `setting` CHANGE  `value_alternate`  `value_alternate` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT  NULL;
ALTER TABLE  `setting` CHANGE  `description`  `description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT  NULL;

INSERT INTO `setting` (`id`,`name`,`issession`) VALUES (NULL,'Header HTML', '1');
INSERT INTO `setting` (`id`,`name`,`issession`) VALUES (NULL,'Footer HTML', '1');
INSERT INTO `setting` (`id`,`name`,`issession`) VALUES (NULL,'Body HTML', '1');