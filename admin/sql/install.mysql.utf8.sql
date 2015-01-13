CREATE TABLE IF NOT EXISTS `#__cmmobile_sessions` (
  `token` varchar(200) NOT NULL DEFAULT '',
  `userid` int(10) unsigned DEFAULT '0',
  `username` varchar(150) DEFAULT '',
  `created` datetime DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`token`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;