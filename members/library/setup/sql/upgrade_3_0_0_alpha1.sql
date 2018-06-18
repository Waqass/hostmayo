# --------------------------------------------------------
# NEW PERMISSIONS TABLE
# --------------------------------------------------------
CREATE TABLE `permissions` (
  `subject_id` int(11) NOT NULL default '0',
  `is_group` tinyint(4) NOT NULL default '0',
  `permission` varchar(100) NOT NULL default '',
  `target_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`subject_id`,`is_group`,`permission`,`target_id`)
) ENGINE=MyISAM;

# --------------------------------------------------------
# NEW MODULE VERSIONS TABLE
# --------------------------------------------------------
CREATE TABLE `versions` (
    `module` VARCHAR( 50 ) NOT NULL ,
    `version` VARCHAR( 50 ) NOT NULL
) ENGINE = MYISAM ;

# ---------------------------------------------------------
# NEW PERMISSIONS
# ---------------------------------------------------------
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (3, 1, 'admin_view', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (3, 1, 'admin_view_services_status', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (4, 1, 'admin_view', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (4, 1, 'admin_view_services_status', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (5, 1, 'admin_view', 0);
INSERT INTO `permissions` (`subject_id`, `is_group`, `permission`, `target_id`) VALUES (5, 1, 'admin_view_services_status', 0);
