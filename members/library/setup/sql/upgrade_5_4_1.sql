CREATE TABLE `customField_ignore` LIKE `customField`;
ALTER TABLE `customField_ignore` ADD CONSTRAINT groupId_subGroupId_name UNIQUE(`groupId`, `subGroupId`, `name`);
INSERT IGNORE INTO `customField_ignore` SELECT * FROM `customField`;
DROP TABLE `customField`;
RENAME TABLE `customField_ignore` TO `customField`;

INSERT IGNORE INTO `customField` (`id` ,`groupId` ,`subGroupId` ,`fieldType` ,`name` ,`isRequired` ,`isChangeable` ,`isAdminOnly` ,`fieldOrder` ,`showCustomer` ,`showAdmin` ,`dropDownOptions` ,`inSettings` ,`InSignup`) VALUES (NULL , '2', '3', '0', 'Transfer Update Date', '0', '0', '0', '0', '0', '0', '', '0', '0');

UPDATE `users` SET `autopayment` = '1' WHERE `paymenttype` IN ('stripecheckout');

ALTER TABLE `chatvisitor` CHANGE `ref_host` `ref_host` VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;