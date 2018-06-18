ALTER TABLE `autoresponders` DROP `contents`;
ALTER TABLE `autoresponders` CHANGE `contents_html` `contents` text NOT NULL AFTER `subject`;
