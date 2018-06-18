INSERT INTO `setting` (`company_id`, `name`, `value`, `value_alternate`, `description`, `type`, `isrequired`, `istruefalse`, `istextarea`, `issmalltextarea`, `isfromoptions`, `myorder`, `helpid`, `plugin`, `ispassword`, `ishidden`, `issession`) VALUES (0, 'Recalculate Next Due Dates Related To Packages', '0', '', '', 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);

UPDATE `currency` SET `precision`= 2 WHERE `abrv` = 'EGP';

CREATE TABLE `currency_ignore` LIKE `currency`;
ALTER TABLE `currency_ignore` ADD UNIQUE(`abrv`);
INSERT IGNORE INTO `currency_ignore` SELECT * FROM `currency`;
DROP TABLE `currency`;
RENAME TABLE `currency_ignore` TO `currency`;

ALTER TABLE `currency` ADD PRIMARY KEY(`id`);
ALTER TABLE `currency` DROP INDEX id;