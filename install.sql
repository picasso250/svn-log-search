
-- ----------------------------
-- Table structure for changed_path
-- ----------------------------
DROP TABLE IF EXISTS `changed_path`;
CREATE TABLE `changed_path` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rev` int(10) unsigned DEFAULT NULL,
  `text_mods` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `kind` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `action` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `prop_mods` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `file_path` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `path_rev_idx` (`rev`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for repo
-- ----------------------------
DROP TABLE IF EXISTS `repo`;
CREATE TABLE `repo` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `repo` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for rev
-- ----------------------------
DROP TABLE IF EXISTS `rev`;
CREATE TABLE `rev` (
  `rev` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `repo_id` int(10) unsigned DEFAULT NULL,
  `author` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `commit_date` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `line_num` smallint(6) DEFAULT NULL,
  `msg` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  PRIMARY KEY (`rev`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

delimiter $$

CREATE TABLE `diff` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `repo_id` int(10) unsigned DEFAULT NULL,
  `rev` int(10) unsigned DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL,
  `diff` blob,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8$$

delimiter $$

CREATE TABLE `blame` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `repo_id` int(10) unsigned DEFAULT NULL,
  `rev` int(10) unsigned DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL,
  `blame` blob,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8$$

