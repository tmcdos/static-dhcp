<?php
define('DATABASE_HOST','127.0.0.1');
define('DATABASE_USER','demo');
define('DATABASE_PASSWORD','demo');
define('DATABASE_NAME','demo');

define('DHCP_LEASES','/var/lib/dnsmasq/dnsmasq.leases');
define('DOMAIN','lan.example.com');

/*
  For DNSmasq, there are some preparations for this web application to work correctly:
    1) you should uncomment "dhcp-leasefile" option in file "/etc/dnsmasq.conf" and put the same value in the above DHCP_LEASES constant
       (they can be different than the default example in config file, but PHP constant should be equal to the "dhcp-leasefile" value
    2) you should change the owner and group for directory "/etc/dnsmasq.d" (and create it, if not exists) to be the same as the user and group
       under which PHP is running; also change the mode to be 755
    3) you should delete the file "/etc/ethers" and instead create a symlink to "/etc/dnsmasq.d/ethers"
    4) you should put the option "addn-hosts=/etc/dnsmasq.d/dns_host" in file "/etc/dnsmasq.conf"
    5) you should put your domain in options "local" (e.g. "local=/lan.example.com/") and "domain" (e.g. "domain=lan.example.com") in file
       "/etc/dnsmasq.conf" and also update the above PHP constant "DOMAIN"
    6) you should uncomment the option "expand-hosts" in file "/etc/dnsmasq.conf"
    7) you should uncomment the option "read-ethers" in file "/etc/dnsmasq.conf"
  You should restart the DNSmasq daemon after these changes !
*/
?>