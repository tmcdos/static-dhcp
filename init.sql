# HeidiSQL Dump 
#
# --------------------------------------------------------
# Host:                 185.53.131.27
# Database:             demo
# Server version:       10.1.13-MariaDB
# Server OS:            Linux
# Target-Compatibility: Same as source server (MySQL 10.1.13-MariaDB)
# max_allowed_packet:   16777216
# HeidiSQL version:     3.2 Revision: 1129
# --------------------------------------------------------

/*!40100 SET CHARACTER SET cp1251*/;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0*/;


DROP TABLE IF EXISTS `dhcp_host`;

#
# Table structure for table 'dhcp_host'
#

CREATE TABLE `dhcp_host` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `net_id` smallint(5) unsigned NOT NULL,
  `ip_4` tinyint(3) unsigned NOT NULL,
  `mac` char(17) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `hostname` varchar(64) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hostname` (`hostname`),
  UNIQUE KEY `net_host` (`net_id`,`ip_4`),
  UNIQUE KEY `mac` (`mac`),
  CONSTRAINT `FK_dhcp_host_dhcp_subnet` FOREIGN KEY (`net_id`) REFERENCES `dhcp_subnet` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 /*!40100 DEFAULT CHARSET=utf8*/;



#
# Dumping data for table 'dhcp_host'
#

LOCK TABLES `dhcp_host` WRITE;
/*!40000 ALTER TABLE `dhcp_host` DISABLE KEYS*/;
INSERT INTO `dhcp_host` (`id`, `net_id`, `ip_4`, `mac`, `hostname`) VALUES
	(2,1,9,'DF:12:90:14:A8:02','niky'),
	(4,1,8,'AD:F0:14:12:04:10','pepi'),
	(14,1,33,'DF:12:90:14:A8:03','penka3');
/*!40000 ALTER TABLE `dhcp_host` ENABLE KEYS*/;
UNLOCK TABLES;


DROP TABLE IF EXISTS `dhcp_subnet`;

#
# Table structure for table 'dhcp_subnet'
#

CREATE TABLE `dhcp_subnet` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `ip_1` tinyint(3) unsigned NOT NULL,
  `ip_2` tinyint(3) unsigned NOT NULL,
  `ip_3` tinyint(3) unsigned NOT NULL,
  `title` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `title` (`title`),
  UNIQUE KEY `subnet` (`ip_1`,`ip_2`,`ip_3`)
) ENGINE=InnoDB AUTO_INCREMENT=3 /*!40100 DEFAULT CHARSET=utf8*/;



#
# Dumping data for table 'dhcp_subnet'
#

LOCK TABLES `dhcp_subnet` WRITE;
/*!40000 ALTER TABLE `dhcp_subnet` DISABLE KEYS*/;
INSERT INTO `dhcp_subnet` (`id`, `ip_1`, `ip_2`, `ip_3`, `title`) VALUES
	(1,10,0,10,'Designers'),
	(2,10,0,22,'Accounting');
/*!40000 ALTER TABLE `dhcp_subnet` ENABLE KEYS*/;
UNLOCK TABLES;


DROP TABLE IF EXISTS `dhcp_var`;

#
# Table structure for table 'dhcp_var'
#

CREATE TABLE `dhcp_var` (
  `id` tinyint(3) unsigned NOT NULL,
  `value` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB /*!40100 DEFAULT CHARSET=utf8*/;



#
# Dumping data for table 'dhcp_var'
#

# (No data found.)

/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS*/;
