UPDATE customField SET fieldType=15 WHERE name IN ('Last Status Date', 'Expiration Date', 'Certificate Expiration Date', 'Cancellation Date');
ALTER TABLE  `customField` ADD  `usedbyplugin` VARCHAR( 60 ) NOT NULL DEFAULT  '';
