UPDATE `package` p SET p.`pricing` = REPLACE(p.`pricing`, 's:15:"price48included";s:1:"1";', 's:15:"price48included";i:0;') WHERE p.`planid` IN (SELECT pr.`id` FROM `promotion` pr WHERE pr.`type` = 2);
UPDATE `package` p SET p.`pricing` = REPLACE(p.`pricing`, 's:15:"price48included";i:1;', 's:15:"price48included";i:0;') WHERE p.`planid` IN (SELECT pr.`id` FROM `promotion` pr WHERE pr.`type` = 2);
UPDATE `package` p SET p.`pricing` = REPLACE(p.`pricing`, 's:15:"price60included";s:1:"1";', 's:15:"price60included";i:0;') WHERE p.`planid` IN (SELECT pr.`id` FROM `promotion` pr WHERE pr.`type` = 2);
UPDATE `package` p SET p.`pricing` = REPLACE(p.`pricing`, 's:15:"price60included";i:1;', 's:15:"price60included";i:0;') WHERE p.`planid` IN (SELECT pr.`id` FROM `promotion` pr WHERE pr.`type` = 2);

INSERT INTO `country` (`name`, `iso`, `phone_code`, `exists`) VALUES ('South Sudan', 'SS', '211', '1');