CREATE TABLE `eventlog` (
  `id` int(11) NOT NULL auto_increment,
  `date` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `subject` int(11) NOT NULL,
  `params` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM;

INSERT INTO `setting` VALUES (NULL, 'snapinsList', '', '', '', 0, 1, 0, 0, 0, 0, 0, 0, 0, 1);

CREATE TABLE `user_groups` (
    `user_id` INT NOT NULL ,
    `group_id` INT NOT NULL ,
    PRIMARY KEY ( `user_id` , `group_id` )
) ENGINE = MYISAM ;
