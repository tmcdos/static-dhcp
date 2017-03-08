<?php 
$dir = dirname(__FILE__);
include_once($dir.'/util.php');
include_once($dir.'/db.php');

$conn = db_connect();

// export hosts from database to Mikrotik or DNSmasq
if($_REQUEST['mikrotik'])
{
  // check if Mikrotik credentials are present
  $query = 'SELECT id, value FROM dhcp_var WHERE id IN (2,3,4,5)';
  $res = db_query($link, $query);
  while($row = mysqli_fetch_row($res))
    switch($row[0])
    {
      case 2:
        $mk_ip = $row[1];
        break;
      case 3:
        $mk_user = $row[1];
        break;
      case 4:
        $mk_pass = $row[1];
        break;
      case 5:
        $TTL = $row[1];
        break;
    }
  if($mk_ip != '' AND $mk_user != '' AND $mk_pass != '')
  {
    include($dir.'/routeros_api.class.php');
    $API = new RouterosAPI();
    if ($API->connect($mk_ip, $mk_user, $mk_pass)) 
    {
      // This could be very slow for more than a dozen hosts !!!!
      // It would be better to use the full export only the first time and then allow the "auto-sync" option
      // in settings.
      // Please be warned, that after you start using this web management application, you are STRONGLY
      // discouraged to manually edit DNSmasq or Mikrotik configuration regarding the static DHCP and DNS entries
      // Otherwise the synchronization with database will be violated and you will have to make a full export.
    	$query = 'SELECT CONCAT(ip_1, ".", ip_2, ".", ip_3, ".", ip_4) AS adr, mac, hostname FROM dhcp_host
    	  LEFT JOIN dhcp_subnet ON net_id = dhcp_subnet.id ORDER BY ip_1, ip_2, ip_3, ip_4';
     	$res = db_query($conn, $query);
     	while($row = mysqli_fetch_assoc($res))
     	{
     	  // clear DHCP entry by both IP and MAC
        $lease = $API->comm('/ip/dhcp-server/lease/print',Array('?address'=>$row['adr']));
        if($lease[0]['.id']!='') 
        {
          $API->comm('/ip/dhcp-server/lease/remove',Array('.id'=>$lease[0]['.id']));
        }
        $lease = $API->comm('/ip/dhcp-server/lease/print',Array('?mac-address'=>$row['mac']));
        if($lease[0]['.id']!='') 
        {
          $API->comm('/ip/dhcp-server/lease/remove',Array('.id'=>$lease[0]['.id']));
        }
        // create new DHCP static entry
        $API->comm('/ip/dhcp-server/lease/add',Array(
          'address'=>$row['adr'],
          'mac-address'=>$row['mac'],
          'comment'=>$row['hostname']
        ));
        // remove DNS entry
        $dns = $API->comm('/ip/dns/static/print',Array('?address'=>$row['adr']));
        if(count($dns)!=0) foreach($dns as $static)
          if($static['.id'] != '') $API->comm('/ip/dns/static/remove',Array('.id'=>$static['.id']));
        // create new static DNS entry
        $API->comm('/ip/dns/static/add',Array(
          'address'=>$row['adr'],
          'name'=>$row['hostname'].DOMAIN,
          'ttl'=>$TTL != '' ? $TTL : '08:00:00'
        ));
   		}
      $API->disconnect();
    }
    else 
    {
      echo '{"warn": "Can not connect to Mikrotik with the specified credentials"}';
      die;
    }
  }
  else 
  {
    echo '{"warn": "Not all Mikrotik credentials (IP, username, password) are present"}';
    die;
  }
}
else
{
	$query = 'SELECT CONCAT(ip_1, ".", ip_2, ".", ip_3, ".", ip_4) AS adr, mac, hostname FROM dhcp_host
	  LEFT JOIN dhcp_subnet ON net_id = dhcp_subnet.id ORDER BY ip_1, ip_2, ip_3, ip_4';
 	$res = db_query($conn, $query);
 	while($row = mysqli_fetch_assoc($res))
 	{
 		$dns_host.= $row['adr'].chr(9).chr(9).$row['hostname'].chr(10);
 		$ethers.= $row['mac'].'  '.$row['adr'].chr(10);
 	}
 	if(!@file_put_contents('/etc/dnsmasq.d/dns_host',$dns_host)) 
 	{
 	  echo '{"error": "Could not write to /etc/dnsmasq/dns_host"}';
 	  die;
 	}
 	if(!@file_put_contents('/etc/dnsmasq.d/ethers',$ethers)) 
 	{
 	  echo '{"error": "Could not write to /etc/dnsmasq/ethers"}';
 	  die;
 	}
}

echo '{"export": true}';

?>