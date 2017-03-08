<?php
include_once(dirname(__FILE__)."/conf.php");

function db_connect()
{
  $conn = @mysqli_connect(DATABASE_HOST,DATABASE_USER,DATABASE_PASSWORD,DATABASE_NAME);
  if(!$conn) 
  {
    echo '{"db_err":"Can not connect to database - ('. mysqli_connect_errno().'): '.mysqli_connect_error().'"}';
    die;
  }
  db_query($conn,'SET NAMES utf8');
  // auto create tables
  // local subnets in LAN - each department has a separate one
  db_query($conn,'CREATE TABLE IF NOT EXISTS `dhcp_subnet` (
    `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
    `ip_1` tinyint(3) unsigned NOT NULL,
    `ip_2` tinyint(3) unsigned NOT NULL,
    `ip_3` tinyint(3) unsigned NOT NULL,
    `title` varchar(100) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `title` (`title`),
    UNIQUE KEY `subnet` (`ip_1`,`ip_2`,`ip_3`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8');
  // all hosts in the LAN
  db_query($conn,'CREATE TABLE IF NOT EXISTS `dhcp_host` (
    `net_id` smallint(5) unsigned NOT NULL,
    `ip_4` tinyint(3) unsigned NOT NULL,
    `mac` char(17) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
    `hostname` varchar(64) NOT NULL,
    PRIMARY KEY (`net_id`,`ip_4`),
    UNIQUE KEY `hostname` (`hostname`),
    CONSTRAINT `FK_dhcp_host_dhcp_subnet` FOREIGN KEY (`net_id`) REFERENCES `dhcp_subnet` (`id`) ON UPDATE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8');
  // configuration options for this web application
  db_query($conn,'CREATE TABLE IF NOT EXISTS `dhcp_var` (
    `id` tinyint(3) unsigned NOT NULL,
    `value` varchar(50) NOT NULL,
    PRIMARY KEY (`id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8');
  return $conn;
}

function db_query($link, $query, $trigger = TRUE)
{
global $db_err;

  unset($db_err);
  $res = mysqli_query($link,$query);
  if(FALSE === $res)
  {
    if($trigger)
    {
      echo '{"db_err":"Query error - ('. mysqli_errno($link).'): '.mysqli_error($link).'", "query":"'.addslashes($query).'"}';
      die;
    }
    else $db_err = Array(mysqli_errno($link), mysqli_error($link));
  }
  else return $res;
}

?>