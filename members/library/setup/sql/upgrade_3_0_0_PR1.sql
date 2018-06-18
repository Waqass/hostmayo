# --------------------------------------------------------
# UPDATED COUNTRY TABLE
# --------------------------------------------------------
ALTER TABLE `country` ADD `exists` tinyint(4) NOT NULL default '1';

UPDATE `country` SET `exists` = '0' WHERE `iso` IN ('AI', 'AQ', 'BV', 'CX', 'FX', 'GF', 'GI', 'GP', 'MQ', 'MS', 'NF', 'PN', 'RE', 'SH', 'VG', 'VI', 'WF', 'YU');
UPDATE `country` SET `name` = 'Hong Kong, China' WHERE `iso` = 'HK';
UPDATE `country` SET `name` = 'Macau, China' WHERE `iso` = 'MO';
UPDATE `country` SET `iso` = 'CL' WHERE `iso` = ' C';
UPDATE `country` SET `iso` = 'TS', `name` = 'Timor-Leste' WHERE `iso` = 'TP';
UPDATE `country` SET `iso` = 'CS', `name` = 'Serbia and Montenegro' WHERE `iso` = 'YU';

UPDATE `user_customuserfields` SET `value` = 'CL' WHERE `value` = ' C' AND `customid` = 15;
UPDATE `user_customuserfields` SET `value` = 'TS' WHERE `value` = 'TP' AND `customid` = 15;
UPDATE `user_customuserfields` SET `value` = 'CS' WHERE `value` = 'YU' AND `customid` = 15;

INSERT INTO `country` VALUES (NULL, 'Congo (Dem. Rep.)', 'CD', '243', '1');
INSERT INTO `country` VALUES (NULL, 'Korea (North)', 'KP', '850', '1');
INSERT INTO `country` VALUES (NULL, 'Laos', 'LA', '856', '1');
INSERT INTO `country` VALUES (NULL, 'Macedonia (Frm. Yugoslav Rep.)', 'MK', '389', '1');
INSERT INTO `country` VALUES (NULL, 'Marshall Islands', 'MH', '692', '1');
INSERT INTO `country` VALUES (NULL, 'Micronesia (Fed. States)', 'FM', '691', '1');
INSERT INTO `country` VALUES (NULL, 'Moldova (Republic of)', 'MD', '373', '1');
INSERT INTO `country` VALUES (NULL, 'Montenegro', 'ME', '382', '1');
INSERT INTO `country` VALUES (NULL, 'Netherlands Antilles', 'AN', '599', '1');
INSERT INTO `country` VALUES (NULL, 'Northern Mariana Islands', 'MP', '1', '1');
INSERT INTO `country` VALUES (NULL, 'Saint Vincent and The Grenadines', 'VC', '1 784', '1');
INSERT INTO `country` VALUES (NULL, 'Serbia', 'RS', '381', '1');
INSERT INTO `country` VALUES (NULL, 'Tanzania (United Rep.)', 'TZ', '255', '1');
