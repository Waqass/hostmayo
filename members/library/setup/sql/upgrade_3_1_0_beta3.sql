# --------------------------------------------------------------
# RENAME eventlog TABLE TO CONFORM WITH services_log TABLE NAME
# --------------------------------------------------------------
RENAME TABLE `eventlog`  TO `events_log` ;

# --------------------------------------------------------------
# NEW SETTING TYPE
# --------------------------------------------------------------
ALTER TABLE `setting` ADD `issmalltextarea` TINYINT NOT NULL DEFAULT '0' AFTER `istextarea` ;
