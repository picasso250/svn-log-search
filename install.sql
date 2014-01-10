
-- ----------------------------
-- Table structure for blame
-- ----------------------------
DROP TABLE IF EXISTS `blame`;
CREATE TABLE `blame` (
  `file_id` int(10) unsigned NOT NULL,
  `blame` text,
  PRIMARY KEY (`file_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for changed_path
-- ----------------------------
DROP TABLE IF EXISTS `changed_path`;
CREATE TABLE `changed_path` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rev_id` int(10) unsigned DEFAULT NULL,
  `text_mods` varchar(255) DEFAULT NULL,
  `kind` varchar(255) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `prop_mods` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `path_rev_idx` (`rev_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for diff
-- ----------------------------
DROP TABLE IF EXISTS `diff`;
CREATE TABLE `diff` (
  `file_id` int(10) unsigned NOT NULL,
  `diff` text,
  PRIMARY KEY (`file_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for repo
-- ----------------------------
DROP TABLE IF EXISTS `repo`;
CREATE TABLE `repo` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `repo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for rev
-- ----------------------------
DROP TABLE IF EXISTS `rev`;
CREATE TABLE `rev` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rev` int(10) unsigned NOT NULL,
  `repo_id` int(10) unsigned DEFAULT NULL,
  `author` varchar(255) DEFAULT NULL,
  `commit_date` varchar(255) DEFAULT NULL,
  `line_num` smallint(6) DEFAULT NULL,
  `msg` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rev` (`rev`),
  KEY `idx_repo` (`repo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
