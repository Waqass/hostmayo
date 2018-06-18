# --------------------------------
# ADD IP ADDRESS TO EVENT LOG
# --------------------------------
ALTER TABLE `events_log` ADD `ip` VARCHAR( 15 ) NOT NULL DEFAULT '0' AFTER `subject` ;
