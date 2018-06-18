# Add override auto suspend setting
INSERT INTO `customField` (`id`, `company_id`, `groupId`, `subGroupId`, `fieldType`, `name`, `isRequired`, `isChangeable`, `isAdminOnly`, `fieldOrder`, `showCustomer`, `showAdmin`, `dropDownOptions`, `inSettings`, `InSignup`, `partofproductidentifier`, `desc`, `isEncrypted`) VALUES (NULL, '0', '2', '0', '0', 'Override AutoSuspend', '0', '0', '0', '0', '0', '0', '', '0', '0', '0', '', '0');

# monthly cost and server provider fields to server table
ALTER TABLE  `server` ADD  `cost` FLOAT NOT NULL , ADD  `provider` VARCHAR( 250 ) NOT NULL;