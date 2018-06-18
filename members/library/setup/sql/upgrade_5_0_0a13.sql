# Insert new custom field for product/package cancellation request date
INSERT INTO `customField` (`id`, `company_id`, `groupId`, `subGroupId`, `fieldType`, `name`, `isRequired`, `isChangeable`, `isAdminOnly`, `fieldOrder`, `showCustomer`, `showAdmin`, `dropDownOptions`, `inSettings`, `InSignup`, `partofproductidentifier`, `desc`, `isEncrypted`) VALUES (NULL, '0', '2', '0', '0', 'Cancellation Date', '0', '0', '0', '0', '0', '0', '', '0', '0', '0', '', '0');
ALTER TABLE `versions` ADD PRIMARY KEY (`module`);
ALTER TABLE `troubleticket_files` ADD INDEX `i_ticketid` (`ticketid`);
UPDATE `setting` SET  `issession` =  '1' WHERE  `name` = 'Default Payment Type';
UPDATE `setting` SET  `issession` =  '1' WHERE  `name` = 'System Timezone';