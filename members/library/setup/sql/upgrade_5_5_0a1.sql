INSERT INTO `customField` (`id`, `company_id`, `groupId`, `subGroupId`, `fieldType`, `name`, `isRequired`, `isChangeable`, `isAdminOnly`, `fieldOrder`, `showCustomer`, `showAdmin`, `dropDownOptions`, `inSettings`, `InSignup`, `showingridadmin`, `showingridportal`, `partofproductidentifier`, `desc`, `isEncrypted`, `usedbyplugin`) VALUES
(NULL, 0, 3, 0, 0, 'CC', 0, 0, 1, 1, 0, 0, '', 0, 0, 0, 0, 0, 'Enter a comma separated list of email addresses to be CC''ed on this ticket.', 0, ''),
(NULL, 0, 3, 0, 0, 'BCC', 0, 0, 1, 2, 0, 0, '', 0, 0, 0, 0, 0, 'Enter a comma separated list of email addresses to be BCC''ed on this ticket.', 0, '');

ALTER TABLE `email_queue` ADD `cc` TEXT NOT NULL AFTER `attachment`;