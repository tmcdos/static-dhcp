<?php 
$dir = dirname(__FILE__);
include_once($dir.'/util.php');
include_once($dir.'/db.php');

$conn = db_connect();

if($_REQUEST['add'])
{
  // create a subnet
  $ip = explode('.',trim($_REQUEST['ip']));
  $title = trim($_REQUEST['name']);
  if(count($ip)!=3 OR $ip[0]<1 OR $ip[0]>254 OR $ip[1]<0 OR $ip[1]>254 OR $ip[2]<0 OR $ip[2]>254) $ajax = Array('error' => 'Invalid subnet address');
  elseif($title=='') $ajax = Array('error' => 'Missing title for the subnet');
  else
  {
    // try to add
    $query = 'INSERT INTO dhcp_subnet(ip_1, ip_2, ip_3, title) VALUES('.$ip[0].','.$ip[1].','.$ip[2].',"'.mysqli_real_escape_string($conn,$title).'")';
    $res = db_query($conn, $query, FALSE); // prevent default error handling
    if($db_err[0])
    {
      if($db_err[0] == 1062) $ajax = Array('error' => 'Duplicate IP or title');
        else $ajax = Array('db_err' => 'Query error - ('.$db_err[0].'): '.$db_err[1], 'query' => $query);
    }
    else 
    {
      $ajax = Array('add' => TRUE, 'id' => mysqli_insert_id($conn));
    }
  }

  echo json_encode($ajax);
}
elseif($_REQUEST['edit'])
{
  // edit a subnet
  $id = (int)$_REQUEST['id'];
  $ip = explode('.',trim($_REQUEST['ip']));
  $title = trim($_REQUEST['name']);
  if(count($ip)!=3 OR $ip[0]<1 OR $ip[0]>254 OR $ip[1]<0 OR $ip[1]>254 OR $ip[2]<0 OR $ip[2]>254) $ajax = Array('error' => 'Invalid subnet address');
  elseif($title=='') $ajax = Array('error' => 'Missing title for the subnet');
  else
  {
    // check if subnet exists
    $query = 'SELECT EXISTS(SELECT 1 FROM dhcp_subnet WHERE id = '.$id.')';
    $res = db_query($conn, $query);
    $row = mysqli_fetch_row($res);
    if($row[0])
    {
      // try to update
      $query = 'UPDATE dhcp_subnet SET ip_1 = '.$ip[0].', ip_2 = '.$ip[1].', ip_3 = '.$ip[2].', title="'.mysqli_real_escape_string($conn,$title).'" WHERE id = '.$id;
      $res = db_query($conn, $query, FALSE); // prevent default error handling
      if($db_err[0])
      {
        if($db_err[0] == 1062) $ajax = Array('error' => 'Duplicate IP or title');
          else $ajax = Array('db_err' => 'Query error - ('.$db_err[0].'): '.$db_err[1], 'query' => $query);
      }
      else 
      {
        $ajax = Array('edit' => TRUE);
      }
    }
    else $ajax = Array('error' => 'There is no subnet ID = '.$id);
  }

  echo json_encode($ajax);
}
elseif($_REQUEST['del'])
{
  // delete a subnet
  $net = (int)$_REQUEST['id'];
  $query = 'DELETE FROM dhcp_subnet WHERE id = '.$net.' AND NOT EXISTS(SELECT 1 FROM dhcp_host WHERE net_id = dhcp_subnet.id)';
  db_query($conn, $query);
 
  echo '{"del": '.(mysqli_affected_rows($conn) ? 'true' : 'false').'}';
}
else
{
  // return a list with all subnets
  $query = 'SELECT id, ip_1, ip_2, ip_3, title FROM dhcp_subnet';
  $res = db_query($conn, $query);
  $list = Array();
  while($row = mysqli_fetch_assoc($res))
  {
    $list[] = $row;
  }

  echo '{"list":'.json_encode($list).'}';
}

?>