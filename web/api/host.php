<?php 
$dir = dirname(__FILE__);
include_once($dir.'/util.php');
include_once($dir.'/db.php');
include_once($dir.'/mikrotik.php');

$conn = db_connect();

if($_REQUEST['add'])
{
  // create a host
  $net = (int)$_REQUEST['net'];
  $ip = (int)$_REQUEST['ip'];
  $mac = strtoupper(str_replace('-',':',$_REQUEST['mac']));
  $name = strtolower($_REQUEST['name']);
  if($ip<1 OR $ip>254) $ajax = Array('error' => 'Invalid IP address');
  elseif(!preg_match('/^([0-9A-F]{2}:?){5}[0-9A-F]{2}$/',$mac)) $ajax = Array('error' => 'Invalid MAC address');
  elseif(!preg_match('/^[a-z]([\-_]?[\da-z]+)*$/',$name)) $ajax = Array('error' => 'Invalid hostname');
  if(!is_array($ajax))
  {
    // check if subnet exists
    $query = 'SELECT EXISTS(SELECT 1 FROM dhcp_subnet WHERE id = '.$net.')';
    $res = db_query($conn, $query);
    $row = mysqli_fetch_row($res);
    if($row[0])
    {
      if(strlen($mac)==12) $mac = substr($mac,0,2).':'.substr($mac,2,2).':'.substr($mac,4,2).':'.substr($mac,6,2).':'.substr($mac,8,2).':'.substr($mac,10,2);
      // try to add
      $query = 'INSERT INTO dhcp_host(net_id, ip_4, mac, hostname) VALUES('.$net.','.$ip.',"'.$mac.'","'.$name.'")';
      $res = db_query($conn, $query, FALSE); // prevent default error handling
      if($db_err[0])
      {
        if($db_err[0] == 1062) $ajax = Array('error' => 'Duplicate IP or MAC or hostname');
          else $ajax = Array('db_err' => 'Query error - ('.$db_err[0].'): '.$db_err[1], 'query' => $query);
      }
      else 
      {
        $ajax = Array('add' => TRUE, 'id' => mysqli_insert_id($conn));
        mk_sync($conn, $ajax, MK_APPEND, $net, $ip, $mac, $name);
      }
    }
    else $ajax = Array('error' => 'There is no subnet ID = '.$net);
  }
  
  echo json_encode($ajax);
}
elseif($_REQUEST['edit'])
{
  $id = (int)$_REQUEST['id'];
  $ip = (int)$_REQUEST['ip'];
  $mac = strtoupper(str_replace('-',':',$_REQUEST['mac']));
  $name = strtolower($_REQUEST['name']);
  if($ip<1 OR $ip>254) $ajax = Array('error' => 'Invalid IP address');
  elseif(!preg_match('/^([0-9A-F]{2}:?){5}[0-9A-F]{2}$/',$mac)) $ajax = Array('error' => 'Invalid MAC address');
  elseif(!preg_match('/^[a-z]([\-_]?[\da-z]+)*$/',$name)) $ajax = Array('error' => 'Invalid hostname');
  if(!is_array($ajax))
  {
    // no errors yet
    $query = 'SELECT net_id, ip_4 FROM dhcp_host WHERE id = '.$id;
    $res = db_query($conn, $query);
    if(mysqli_num_rows($res))
    {
      $row = mysqli_fetch_row($res);
      $old_net = $row[0];
      $old_ip = $row[1];
      if(strlen($mac)==12) $mac = substr($mac,0,2).':'.substr($mac,2,2).':'.substr($mac,4,2).':'.substr($mac,6,2).':'.substr($mac,8,2).':'.substr($mac,10,2);
      // try to update
      $query = 'UPDATE dhcp_host SET ip_4 = '.$ip.', mac="'.$mac.'", hostname="'.$name.'" WHERE id = '.$id;
      $res = db_query($conn, $query, FALSE); // prevent default error handling
      if($db_err[0])
      {
        if($db_err[0] == 1062) $ajax = Array('error' => 'Duplicate IP or MAC or hostname');
          else $ajax = Array('db_err' => 'Query error - ('.$db_err[0].'): '.$db_err[1], 'query' => $query);
      }
      else 
      {
        $ajax = Array('edit' => TRUE);
        // mk_sync() can handle movement of host to new subnet, but since the current GUI does not allow for this,
        // we use OLD_NET for both NEW and OLD arguments
        mk_sync($conn, $ajax, MK_UPDATE, $old_net, $ip, $mac, $name, $old_net, $old_ip);
      }
    }
    else $ajax = Array('error' => 'There is no host ID = '.$id);
  }
  
  echo json_encode($ajax);
}
elseif($_REQUEST['del'])
{
  // delete a host
  $net = (int)$_REQUEST['net'];
  $ip = (int)$_REQUEST['ip'];
  $query = 'DELETE FROM dhcp_host WHERE net_id = '.$net.' AND ip_4 = '.$ip;
  db_query($conn, $query);
  $del = mysqli_affected_rows($conn);
  $ajax = Array('del' => $del ? TRUE : FALSE);
  if($del) mk_sync($conn, $ajax, MK_DELETE, $net, $ip);
  
  echo json_encode($ajax);
}
else
{
  // return a list with all hosts for the subnet
  $net = (int)$_REQUEST['net'];
  /*
  $query = 'SELECT EXISTS(SELECT 1 FROM dhcp_subnet WHERE id = '.$net.')';
  $res = db_query($conn, $query);
  $row = mysqli_fetch_row($res);
  if(!$row[0])
  {
    echo '{"error":"There is no subnet ID = '.$net.'}';
    die;
  }
  */
  $query = 'SELECT id, net_id AS net, ip_4 AS ip, mac, hostname FROM dhcp_host'.($net ? ' WHERE net_id = '.$net : '');
  $res = db_query($conn, $query);
  $list = Array();
  while($row = mysqli_fetch_assoc($res))
  {
    $list[] = $row;
  }

  echo '{"list":'.json_encode($list).'}';
}

?>