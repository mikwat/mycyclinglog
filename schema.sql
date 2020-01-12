-- Server version	5.7.28

--
-- Table structure for table `errors`
--

DROP TABLE IF EXISTS `errors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `errors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `description` text,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM AUTO_INCREMENT=1815 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `title` varchar(64) DEFAULT NULL,
  `body` text,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sent` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`,`sent`),
  KEY `sent_uid` (`sent`,`uid`)
) ENGINE=MyISAM AUTO_INCREMENT=20427 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `training_bike`
--

DROP TABLE IF EXISTS `training_bike`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_bike` (
  `bid` int(11) NOT NULL AUTO_INCREMENT,
  `make` varchar(64) NOT NULL DEFAULT '',
  `model` varchar(64) NOT NULL DEFAULT '',
  `year` int(11) DEFAULT NULL,
  `enabled` enum('T','F') NOT NULL DEFAULT 'T',
  `is_default` enum('T','F') NOT NULL DEFAULT 'F',
  `uid` int(11) NOT NULL DEFAULT '0',
  `iid` int(11) DEFAULT NULL,
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`bid`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM AUTO_INCREMENT=21471 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `training_bike_service`
--

DROP TABLE IF EXISTS `training_bike_service`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_bike_service` (
  `bsid` int(11) NOT NULL AUTO_INCREMENT,
  `bid` int(11) NOT NULL,
  `service_date` date NOT NULL,
  `odometer` float DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`bsid`),
  KEY `bid` (`bid`)
) ENGINE=MyISAM AUTO_INCREMENT=8650 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `training_comment`
--

DROP TABLE IF EXISTS `training_comment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_comment` (
  `cid` int(11) NOT NULL AUTO_INCREMENT,
  `lid` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `last_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `comment` text NOT NULL,
  PRIMARY KEY (`cid`),
  KEY `lid` (`lid`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM AUTO_INCREMENT=9225 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `training_event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_event` (
  `eid` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `etid` int(11) NOT NULL,
  `title` varchar(256) NOT NULL,
  `link` varchar(256) NOT NULL,
  `addressed` enum('T','F') NOT NULL DEFAULT 'F',
  `entry_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`eid`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM AUTO_INCREMENT=11807 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `training_event_type`
--

DROP TABLE IF EXISTS `training_event_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_event_type` (
  `etid` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(64) NOT NULL,
  PRIMARY KEY (`etid`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `training_goal`
--

DROP TABLE IF EXISTS `training_goal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_goal` (
  `gid` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `distance` double NOT NULL,
  `is_ride` enum('T','F') DEFAULT NULL,
  PRIMARY KEY (`gid`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM AUTO_INCREMENT=10965 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `training_group`
--

DROP TABLE IF EXISTS `training_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_group` (
  `gid` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL DEFAULT '',
  `password` varchar(32) NOT NULL DEFAULT '',
  `description` text,
  `link` varchar(64) DEFAULT NULL,
  `is_shown` enum('Y','N') NOT NULL DEFAULT 'Y',
  `twitter_username` varchar(15) DEFAULT NULL,
  `twitter_password` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`gid`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=940 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `training_group_event`
--

DROP TABLE IF EXISTS `training_group_event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_group_event` (
  `geid` int(11) NOT NULL AUTO_INCREMENT,
  `gid` int(11) NOT NULL,
  `title` varchar(128) NOT NULL,
  `location` varchar(128) DEFAULT NULL,
  `distance` float DEFAULT NULL,
  `description` varchar(3000) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `repeat_days` mediumint(8) unsigned DEFAULT NULL,
  `rid` int(11) DEFAULT NULL,
  `uid` int(11) NOT NULL,
  `created_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`geid`),
  KEY `gid` (`gid`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `training_group_message`
--

DROP TABLE IF EXISTS `training_group_message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_group_message` (
  `mid` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT '0',
  `gid` int(11) NOT NULL DEFAULT '0',
  `title` varchar(64) NOT NULL DEFAULT '',
  `body` text NOT NULL,
  `entry_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`mid`),
  KEY `gid` (`gid`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM AUTO_INCREMENT=3639 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `training_group_request`
--

DROP TABLE IF EXISTS `training_group_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_group_request` (
  `grid` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `gid` int(11) NOT NULL,
  `note` varchar(256) DEFAULT NULL,
  `status` enum('Accepted','Denied') DEFAULT NULL,
  `request_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `response_date` timestamp NULL DEFAULT NULL,
  `eid` int(11) DEFAULT NULL,
  PRIMARY KEY (`grid`)
) ENGINE=MyISAM AUTO_INCREMENT=2196 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `training_image`
--

DROP TABLE IF EXISTS `training_image`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_image` (
  `iid` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `type` varchar(16) NOT NULL,
  `width` int(11) NOT NULL,
  `height` int(11) NOT NULL,
  `mime` varchar(16) NOT NULL,
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`iid`)
) ENGINE=MyISAM AUTO_INCREMENT=1311 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `training_invite`
--

DROP TABLE IF EXISTS `training_invite`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_invite` (
  `iid` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `gid` int(11) DEFAULT NULL,
  `sent_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `email` varchar(256) NOT NULL,
  `accepted` enum('Y','N') NOT NULL DEFAULT 'N',
  `new_uid` int(11) DEFAULT NULL,
  PRIMARY KEY (`iid`)
) ENGINE=MyISAM AUTO_INCREMENT=34081 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `training_log`
--

DROP TABLE IF EXISTS `training_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_log` (
  `lid` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT '0',
  `event_date` date NOT NULL DEFAULT '0000-00-00',
  `last_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `type` int(11) NOT NULL DEFAULT '0',
  `time` time DEFAULT NULL,
  `distance` double DEFAULT NULL,
  `notes` text,
  `max_speed` float DEFAULT NULL,
  `weather` int(11) DEFAULT NULL,
  `heart_rate` varchar(32) DEFAULT NULL,
  `avg_cadence` float DEFAULT NULL,
  `weight` float DEFAULT NULL,
  `calories` float DEFAULT NULL,
  `bid` int(11) DEFAULT NULL,
  `rid` int(11) DEFAULT NULL,
  `elevation` float DEFAULT NULL,
  `is_ride` enum('T','F') NOT NULL DEFAULT 'T',
  `source` enum('FB','CSV') DEFAULT NULL,
  PRIMARY KEY (`lid`),
  KEY `uid` (`uid`),
  KEY `event_date` (`event_date`),
  KEY `bid` (`bid`),
  KEY `last_modified` (`last_modified`),
  KEY `rid` (`rid`),
  KEY `is_ride_uid` (`is_ride`,`uid`)
) ENGINE=MyISAM AUTO_INCREMENT=1397140 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `training_log_tag`
--

DROP TABLE IF EXISTS `training_log_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_log_tag` (
  `lid` int(11) NOT NULL DEFAULT '0',
  `tid` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`lid`,`tid`),
  KEY `lid` (`lid`),
  KEY `tid` (`tid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `training_message`
--

DROP TABLE IF EXISTS `training_message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_message` (
  `mid` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT '0',
  `title` varchar(64) NOT NULL DEFAULT '',
  `body` text NOT NULL,
  `entry_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `removed` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`mid`),
  KEY `uid` (`uid`),
  KEY `removed` (`removed`)
) ENGINE=MyISAM AUTO_INCREMENT=1300 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `training_online`
--

DROP TABLE IF EXISTS `training_online`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_online` (
  `uid` int(11) NOT NULL,
  `ip_address` varchar(255) NOT NULL,
  `last_active_dt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`uid`,`ip_address`,`last_active_dt`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `training_route`
--

DROP TABLE IF EXISTS `training_route`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_route` (
  `rid` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `url` varchar(256) DEFAULT NULL,
  `notes` text,
  `enabled` enum('T','F') NOT NULL DEFAULT 'T',
  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`rid`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM AUTO_INCREMENT=22538 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `training_status`
--

DROP TABLE IF EXISTS `training_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_status` (
  `sid` int(11) NOT NULL AUTO_INCREMENT,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `message` text NOT NULL,
  PRIMARY KEY (`sid`)
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `training_tag`
--

DROP TABLE IF EXISTS `training_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_tag` (
  `tid` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(64) NOT NULL,
  PRIMARY KEY (`tid`),
  UNIQUE KEY `title` (`title`)
) ENGINE=MyISAM AUTO_INCREMENT=39663 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `training_user`
--

DROP TABLE IF EXISTS `training_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_user` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(32) NOT NULL DEFAULT '',
  `first_name` varchar(64) NOT NULL DEFAULT '',
  `last_name` varchar(64) NOT NULL DEFAULT '',
  `middle_name` varchar(64) DEFAULT NULL,
  `email` varchar(64) NOT NULL DEFAULT '',
  `password` varchar(64) NOT NULL DEFAULT '',
  `last_login` datetime DEFAULT NULL,
  `login_count` int(11) NOT NULL DEFAULT '0',
  `auth_code` int(9) NOT NULL DEFAULT '0',
  `enabled` enum('T','F') NOT NULL DEFAULT 'F',
  `signup_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `location` varchar(64) DEFAULT NULL,
  `longitude` float DEFAULT NULL,
  `latitude` float DEFAULT NULL,
  `referrer` varchar(8) DEFAULT NULL,
  `unit` enum('mi','km') DEFAULT 'mi',
  `timezone` varchar(64) NOT NULL DEFAULT 'America/Los_Angeles',
  `ext_cookie` varchar(256) DEFAULT NULL,
  `mpd` float NOT NULL DEFAULT '6.9',
  `mpg` float NOT NULL DEFAULT '21',
  `flickr_username` varchar(64) DEFAULT NULL,
  `twitter_username` varchar(15) DEFAULT NULL,
  `facebook_uid` bigint(20) DEFAULT NULL,
  `facebook_key` varchar(150) DEFAULT NULL,
  `bebo_uid` bigint(20) DEFAULT NULL,
  `locale` varchar(32) DEFAULT NULL,
  `week_start` tinyint(4) NOT NULL DEFAULT '1',
  `hide_name` enum('T','F') NOT NULL DEFAULT 'F',
  `iid` int(11) DEFAULT NULL,
  `banned` tinyint(4) NOT NULL DEFAULT '0',
  `cancelled` tinyint(4) NOT NULL DEFAULT '0',
  `email_optout` tinyint(4) DEFAULT '0',
  `email_bounced` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`uid`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `bebo_uid` (`bebo_uid`),
  KEY `facebook_uid` (`facebook_uid`),
  KEY `banned` (`banned`)
) ENGINE=MyISAM AUTO_INCREMENT=62006 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `training_user_group`
--

DROP TABLE IF EXISTS `training_user_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_user_group` (
  `uid` int(11) NOT NULL DEFAULT '0',
  `gid` int(11) NOT NULL DEFAULT '0',
  `admin` enum('Y','N') NOT NULL DEFAULT 'N',
  `join_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `email_updates` enum('Y','N') NOT NULL DEFAULT 'Y',
  PRIMARY KEY (`uid`,`gid`),
  KEY `uid` (`uid`),
  KEY `gid` (`gid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `training_user_message`
--

DROP TABLE IF EXISTS `training_user_message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_user_message` (
  `mid` int(11) NOT NULL AUTO_INCREMENT,
  `from_uid` int(11) NOT NULL,
  `to_uid` int(11) NOT NULL,
  `title` varchar(64) NOT NULL,
  `body` text NOT NULL,
  `entry_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `read` enum('Y','N') NOT NULL DEFAULT 'N',
  PRIMARY KEY (`mid`),
  KEY `from_uid` (`from_uid`,`to_uid`)
) ENGINE=MyISAM AUTO_INCREMENT=3340 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
