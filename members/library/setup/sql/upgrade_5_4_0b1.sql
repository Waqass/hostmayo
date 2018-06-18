CREATE TABLE `currency_ignore` LIKE `currency`;
ALTER TABLE `currency_ignore` ADD UNIQUE(`abrv`);
INSERT IGNORE INTO `currency_ignore` SELECT * FROM `currency`;
DROP TABLE `currency`;
RENAME TABLE `currency_ignore` TO `currency`;

INSERT IGNORE INTO `currency` (`id`, `name`, `symbol`, `decimalssep`, `thousandssep`, `abrv`, `alignment`, `precision`, `rate`, `enabled`) VALUES
(NULL, 'Bolívar Fuerte', 'Bs.F.', ',', '.', 'VEF', 'left', 2, 1, 0),
(NULL, 'Colombian Peso', '$', '.', ',', 'COP', 'left', 2, 1, 0),
(NULL, 'Bangladeshi Taka', 'Tk', '.', ',', 'BDT', 'left', 2, 1, 0);

DELETE FROM `setting` WHERE `name`='Allow run services from URL';

ALTER TABLE `clients_notes` ADD `subject` VARCHAR(250) NOT NULL;

INSERT INTO `customuserfields` (`id`, `company_id`, `name`, `type`, `isrequired`, `isChangable`, `isAdminOnly`, `width`, `myOrder`, `showcustomer`, `showadmin`, `InSignup`, `showingridadmin`, `inSettings`, `dropdownoptions`, `desc`) VALUES
(NULL, 0, 'Billing-Profile-ID', 0, 0, 0, 0, 20, 0, 0, 0, 0, 0, 0, '', '');