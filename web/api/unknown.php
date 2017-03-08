<?php 
$dir = dirname(__FILE__);
include_once($dir.'/util.php');
include_once($dir.'/db.php');

$conn = db_connect();
$ajax = Array();

// show unknown (dynamic/non-managed) hosts
if($_REQUEST['mikrotik'])
{
  // check if Mikrotik credentials are present
  $query = 'SELECT id, value FROM dhcp_var WHERE id IN (2,3,4)';
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
    }
  if($mk_ip != '' AND $mk_user != '' AND $mk_pass != '')
  {
    include($dir.'/routeros_api.class.php');
    $API = new RouterosAPI();
    if ($API->connect($mk_ip, $mk_user, $mk_pass)) 
    {
      $API->write('/ip/dhcp-server/lease/getall');
      $dhcp = $API->parseResponse($API->read(false));
  		if(is_array($dhcp)) foreach($dhcp as $item)
  		{
  		  //$ip = explode('.',$item['address']);
  		  if($item['dynamic']) $ajax[] = Array('mac' => strtoupper($item['mac-address']), 'ip' => $item['address'], 'host' => ($item['host-name'] !='' ? $item['host-name'] : 'N/A'));
  		}
      $API->disconnect();
    }
    else die('{"warn": "Can not connect to Mikrotik with the specified credentials"}');
  }
  else die('{"warn": "Not all Mikrotik credentials (IP, username, password) are present"}');
}
else
{
	if(file_exists(DHCP_LEASES))
	{
	  // process the leases file
		$arr = file($f,FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		foreach($arr as $line)
		{
			list($stamp,$mac,$ip,$pc,$ident,$more) = explode(' ',$line);
			if($ident!='*') $ajax[] = Array('mac' => strtoupper($mac), 'ip' => $ip, 'host' => ($pc=='*' ? 'N/A' : $pc));
		}
	}
}

echo '{"list":'.json_encode($ajax).'}';

?>