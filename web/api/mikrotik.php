<?php 

define(MK_APPEND,1);
define(MK_UPDATE,2);
define(MK_DELETE,3);

// check if Mikrotik auto-sync is required
function sync_enabled($link)
{
  $query = 'SELECT value FROM dhcp_var WHERE id = 1';
  $res = db_query($link, $query);
  if(mysqli_num_rows($res))
  {
    $row = mysqli_fetch_row($res);
    return (bool)$row[0];
  }
  else return FALSE;
}

// update DHCP and DNS information in MIKROTIK router using RouterOS API
// $link (resource) = MySQL connection
// $ajax (array) = associative array where to append errors, if any
// $kind (enumeration) = type of operation (Append / Update / Delete)
// $net (integer) = ID of the subnet in table DHCP_SUBNET
// $ip (integer) = last octet of the IP address
// $mac (string) = MAC address (using ":" as separator)
// $hostname (string) = name of the host
// $old_net (integer) = ID of the previous subnet in table DHCP_SUBNET (only for Update)
// $old_ip (integer) = last octet of the previous IP address (only for Update)
function mk_sync($link, &$ajax, $kind, $net, $ip, $mac = '', $hostname = '', $old_net = 0, $old_ip = 0)
{
  if(sync_enabled($link))
  {
    // check if Mikrotik credentials are present
    $query = 'SELECT id, value FROM dhcp_var WHERE id IN (2,3,4,5,6)';
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
      include(dirname(__FILE__).'/routeros_api.class.php');
      $API = new RouterosAPI();
      if ($API->connect($mk_ip, $mk_user, $mk_pass)) 
      {
        // prepare full IP for Mikrotik
        $query = 'SELECT CONCAT(ip_1, ".", ip_2, ".", ip_3, ".") AS adr FROM dhcp_subnet WHERE id = '.(int)$net;
        $res = db_query($conn, $query);
        if(mysqli_num_rows($res))
        {
          $row = mysqli_fetch_row($res);
          $adr = $row[0].$ip;
          switch($kind)
          {
            case MK_DELETE:
              // remove from static DHCP entries
              $lease = $API->comm('/ip/dhcp-server/lease/print',Array('?address'=>$adr));
              if($lease[0]['.id']!='') $API->comm('/ip/dhcp-server/lease/remove',Array('.id'=>$lease[0]['.id']));
              break;
              // remove from static DNS
              $dns = $API->comm('/ip/dns/static/print',Array('?address'=>$adr));
              foreach($dns as $static)
                if($static['.id'] != '') $API->comm('/ip/dns/static/remove',Array('.id'=>$static['.id']));
            case MK_APPEND:
              // update existing,
              $lease = $API->comm('/ip/dhcp-server/lease/print',Array('?address'=>$adr));
              if($lease[0]['.id']!='') $API->comm('/ip/dhcp-server/lease/set',Array(
                '.id'=>$lease[0]['.id'], 
                'address'=>$adr, 
                'mac-address'=>$mac,
                'comment'=>$hostname
              ));
              // or create new static DHCP entry
              else $API->comm('/ip/dhcp-server/lease/add',Array(
                'address'=>$adr,
                'mac-address'=>$mac,
                'comment'=>$hostname
              ));
              // first remove from static DNS (there is no UPDATE command in the API)
              $dns = $API->comm('/ip/dns/static/print',Array('?address'=>$adr));
              foreach($dns as $static)
                if($static['.id'] != '') $API->comm('/ip/dns/static/remove',Array('.id'=>$static['.id']));
              // then create new static DNS entry
              $API->comm('/ip/dns/static/add',Array(
                'address'=>$adr,
                'name'=>$hostname.DOMAIN,
                'ttl'=>$TTL != '' ? $TTL : '08:00:00'
              ));
              break;
            case MK_UPDATE:
              // prepare full IP for Mikrotik
              $query = 'SELECT CONCAT(ip_1, ".", ip_2, ".", ip_3, ".") AS adr FROM dhcp_subnet WHERE id = '.(int)$old_net;
              $res = db_query($conn, $query);
              if(mysqli_num_rows($res))
              {
                $row = mysqli_fetch_row($res);
                $old_adr = $row[0].$old_ip;
                // update existing,
                $lease = $API->comm('/ip/dhcp-server/lease/print',Array('?address'=>$old_adr));
                if($lease[0]['.id']!='') $API->comm('/ip/dhcp-server/lease/set',Array(
                  '.id'=>$lease[0]['.id'], 
                  'address'=>$adr, 
                  'mac-address'=>$mac,
                  'comment'=>$hostname
                ));
                // or create new static DHCP entry
                else $API->comm('/ip/dhcp-server/lease/add',Array(
                  'address'=>$adr,
                  'mac-address'=>$mac,
                  'comment'=>$hostname
                ));
                // remove from static DNS the old entry
                $dns = $API->comm('/ip/dns/static/print',Array('?address'=>$old_adr));
                foreach($dns as $static)
                  if($static['.id'] != '') $API->comm('/ip/dns/static/remove',Array('.id'=>$static['.id']));
                // create new static DNS entry
                $API->comm('/ip/dns/static/add',Array(
                  'address'=>$adr,
                  'name'=>$hostname.$domain,
                  'ttl'=>$TTL != '' ? $TTL : '08:00:00'
                ));
              }
              break;
          }
        }
        $API->disconnect();
      }
      else $ajax['warn'] = 'Mikrotik auto-sync is allowed, but can not connect with the specified credentials';
    }
    else $ajax['warn'] = 'Mikrotik auto-sync is allowed, but not all credentials (IP, username, password) are present';
  }
}

?>