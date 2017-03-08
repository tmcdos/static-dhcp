//===== Collection of small utilities


/*
 * Bind/Unbind events
 *
 * Usage:
 *   var el = document.getElementyById('#container');
 *   bnd(el, 'click', function() {
 *     console.log('clicked');
 *   });
 */

var bnd = function(
  d, // a DOM element
  e, // an event name such as "click"
  f  // a handler function
){
  d.addEventListener(e, f, false);
}

// createElement short-hand
elem = function(a) 
{ 
  return document.createElement(a); 
}

// dom element iterator: domForEach($(".some-class"), function(el) { ... });
function domForEach(els, fun) 
{ 
  return Array.prototype.forEach.call(els, fun); 
}

/*
 * DOM selector
 *
 * Usage:
 *   $('div');
 *   $('#name');
 *   $('.name');
 *
 * Copyright (C) 2011 Jed Schmidt <http://jed.is> - WTFPL
 * More: https://gist.github.com/991057
 */

var $ = function(
  a,                         // take a simple selector like "TagName", "#ID", or ".ClassName", and
  b                          // an optional context, and
){
  a = a.match(/^(\W)?(.*)/); // split the selector into name and symbol.
  return(                    // return an element or list, from within the scope of
    b                        // the passed context
    || document              // or document,
  )[
    "getElement" + (         // obtained by the appropriate method calculated by
      a[1]
        ? a[1] == "#"
          ? "ById"           // the node by ID,
          : "sByClassName"   // the nodes by class name, or
        : "sByTagName"       // the nodes by tag name,
    )
  ](
    a[2]                     // called with the name.
  )
}

// chain onload handlers
function onLoad(f) 
{
  var old = window.onload;
  if (typeof old != 'function') 
  {
    window.onload = f;
  } 
  else 
  {
    window.onload = function() 
    {
      old();
      f();
    }
  }
}

//===== helpers to add/remove/toggle/check HTML element classes

function addClass(el, cl) 
{
  //el.className += ' ' + cl;
  el.classList.add(cl);
}

function removeClass(el, cl) 
{
  /*
  var cls = el.className.split(/\s+/),
      l = cls.length;
  for (var i=0; i<l; i++) 
  {
    if (cls[i] === cl) cls.splice(i, 1);
  }
  el.className = cls.join(' ');
  return cls.length != l
  */
  return el.classList.remove(cl);
}

function toggleClass(el, cl) 
{
  //if (!removeClass(el, cl)) addClass(el, cl);
  el.classList.toggle(cl);
}

// check if style for target contains the specified class
function hasClass( target, className ) 
{
  //return new RegExp('(\\s|^)' + className + '(\\s|$)').test(target.className);
  return target.classList.contains(className);
}

function elemShow(el)
{
  removeClass(el,'hidden');
}

function elemHide(el)
{
  addClass(el,'hidden');
}

//===== AJAX

/*
 * Get cross browser xhr object
 *
 * Copyright (C) 2011 Jed Schmidt <http://jed.is>
 * More: https://gist.github.com/993585
 */

var ajax = function()
{
  for(a=0; a<4; a++) 
    try 
    {
      return a               // try returning
        ? new ActiveXObject( // a new ActiveXObject
            [                // reflecting
              ,              // (elided)
              "Msxml2",      // the various
              "Msxml3",      // working
              "Microsoft"    // options
            ][a] +           // for Microsoft implementations, and
            ".XMLHTTP"       // the appropriate suffix,
          )                  // but make sure to
        : new XMLHttpRequest // try the w3c standard first, and
    }
    catch(e){}               // ignore when it fails.
}

// DATA must be encoded - EncodeURIComponent
function ajaxReq(method, url, ok_cb, err_cb, data) 
{
  var xhr = ajax();
  xhr.open(method, url, true);
  var timeout = setTimeout(function() 
  {
    xhr.abort();
    console.log("XHR abort:", method, url);
    xhr.status = 599;
    xhr.responseText = "request time-out";
  }, 9000);
  xhr.onreadystatechange = function() 
  {
    if (xhr.readyState != 4) return;
    clearTimeout(timeout);
    if (xhr.status >= 200 && xhr.status < 300) 
    {
//      console.log("XHR done:", method, url, "->", xhr.status);
      ok_cb(xhr.responseText);
    } 
    else 
    {
      console.log("XHR ERR :", method, url, "->", xhr.status, xhr.responseText, xhr);
      err_cb(xhr.status, xhr.responseText);
    }
  }
//  console.log("XHR send:", method, url);
	xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded"); 
  try 
  {
    if(data !== undefined) xhr.send(data);
      else xhr.send();
  } 
  catch(err) 
  {
    console.log("XHR EXC :", method, url, "->", err);
    err_cb(599, err);
  }
}

function dispatchJson(resp, ok_cb, err_cb) 
{
  var j;
  try 
  { 
    j = JSON.parse(resp); 
  }
  catch(err) 
  {
    console.log("JSON parse error: " + err + ". In: " + resp);
    err_cb(500, "JSON parse error: " + err);
    return;
  }
  ok_cb(j);
}

// DATA must be encoded - EncodeURIComponent
function ajaxJson(method, url, ok_cb, err_cb, data) 
{
  ajaxReq(method, url, 
    function(resp) 
    { 
      dispatchJson(resp, ok_cb, err_cb); 
    }, err_cb, data);
}

// DATA must be encoded - EncodeURIComponent
function ajaxSpin(method, url, ok_cb, err_cb, data) 
{
  var spin = $("#spinner");
  elemShow(spin);
  ajaxReq(method, url, 
    function(resp) 
    {
      elemHide(spin);
      ok_cb(resp);
    }, 
    function(status, statusText) 
    {
      elemHide(spin);
      //showWarning("Error: " + statusText);
      err_cb(status, statusText);
    },
    data
  );
}

// DATA must be encoded - EncodeURIComponent
function ajaxJsonSpin(method, url, ok_cb, err_cb, data) 
{
  ajaxSpin(method, url, 
    function(resp) 
    { 
      dispatchJson(resp, ok_cb, err_cb); 
    }, 
    err_cb, data);
}

// ========= application ===========

var app = new Vue({
  el: '#app',
  data:
  {
    menu_id: 1,
    cur_net: {id:0}, // reference to the current subnet in NET_LIST array
    net_list: [],
    net_list_host: [], // for separate sorting of subnets on the HOSTS tab
    host_list: [],
    toggle_sort: false, // FALSE = sort subnets in HOSTS tab by IP; TRUE = sort by title
    host_sorting: 1, // 0 = none, 1 = by IP, 2 = by MAC, 3 = by Hostname (positive = ascending, negative = descending)
    subnet_sorting: 1, // 0 = none, 1 = by IP, 2 = by title (positive = ascending, negative = descending)
    find_sorting: 1, // 0 = none, 1 = by subnet title, 2 = by IP, 3 = by MAC, 4 = by hostname (positive = ascending, negative = descending)
    is_editing: null, // reference to the currently edited object (either host or subnet)
    edit_part: 0, // 0 = none, 1 = host IP, 2 = MAC, 3 = Hostname, 4 = subnet IP, 5 = subnet title
    new_value: '', // the currently edited value (IP, MAC, hostname, subnet IP, subnet title)
    // attributes for adding new host
    add_ip: '',
    add_mac: '',
    add_hostname: '',
    // attributes for adding new subnet
    add_subnet: '',
    add_title: '',
    find_host: '',
    find_id: '',
    net_uniq_cnt: 0,
    net_uniq: {},
    net_map: {}, // associative pointers to subnets in NET_LIST - used by sortedFind()
    notifTimeout: null, // timer for showing AJAX susccess / warnings
    warn_text: '', // warning from server
    info_text: '',  // success from server
    warn_time: 2000,
    info_time: 1450,
    ip_reg: /^(0{0,2}[1-9]|0?[1-9]\d|1\d\d|2[0-4]\d|25[0-4])$/, // check validity of new host IP
    mac_reg: /^[\da-fA-F]{2}([\-: ]?[\da-fA-F]{2}){5}$/, // check validity of new MAC
    name_reg: /^[a-z]([\-_]?[\da-z]+)*$/, // check validity of new Hostname
    net_reg: /^(0{0,2}[1-9]|0?[1-9]\d|1\d\d|2[0-4]\d|25[0-4])(\.(0{0,2}\d|0?[1-9]\d|1\d\d|2[0-4]\d|25[0-4])){2}$/, // check validity of new subnet IP
    max_host: 1000, // DEMO limitation
    max_net: 100 // DEMO limitation
  },
  mounted: function()
  {
    elemShow($('#app'));
    this.fetchSubnets();
  },
  computed:
  {
    // sort the subnets according to TOGGLE_SORT
    sorted_net: function()
    {
      var net_list_host = this.net_list.slice();
      if(this.toggle_sort) return net_list_host.sort(function(a,b)
        {
          return a.title == b.title ? true : (a.title < b.title ? -1 : 1);
        });
      else return net_list_host.sort(function(a,b)
        {
          return a.ip_1 == b.ip_1 ? 
            (a.ip_2 == b.ip_2 ? 
              (a.ip_3 == b.ip_3 ? 
                0 : 
                (a.ip_3 < b.ip_3 ? -1 : 1)) : 
              (a.ip_2 < b.ip_2 ? -1 : 1)) : 
            (a.ip_1 < b.ip_1 ? -1 : 1);
        });
    },
    // get the sorted list of subnets
    listNet: function()
    {
      return this.net_list.sort(this.sortedNet);
    },
    // get only the hosts for currently selected subnet
    listHost: function()
    {
      var self = this;
      return this.host_list.sort(this.sortedHost).filter(function(v)
      {
        return v.net == self.cur_net.id;
      });
    },
    // get the sorted list of ALL hosts
    listFind: function()
    {
      with(this)
      {
        var i, 
          h_find = host_list.slice(),
          rx1 = new RegExp(find_host,"i"),
          rx2 = new RegExp(find_host.replace(/[:\-]/g,''),"i");
        net_map = {};
        net_uniq = {};
        net_uniq_cnt = 0;
        for(i=0; i<net_list.length; i++) net_map[net_list[i].id] = net_list[i];
        return h_find.sort(sortedFind).filter(function(v)
        {
          var fnd = v.hostname.match(rx1) || v.mac.replace(/[:\-]/g,'').match(rx2);
          if(fnd && net_uniq[v.net] == null)
          {
            net_uniq[v.net] = true;
            net_uniq_cnt ++;
          }
          return fnd;
        });
      }
    },
    subnet_ID: function()
    {
      with(this.cur_net) return ip_1 + "." + ip_2 + "." + ip_3 + ".";
    }
  },
  filters:
  {
    // exchange IP and title of subnets depending on TOGGLE_SORT
    filterSubnet: function(value, alphabetical)
    {
      with(value)
        if(alphabetical) return title + "\xA0=\xA0" + ip_1 + "." + ip_2 + "." + ip_3;
          else return ip_1 + "." + ip_2 + "." + ip_3 + "\xA0=\xA0" + title;
    }
  },
  methods:
  {
    // negative answer from server (error)
    showWarn: function (text) 
    {
      var self = this;
      // \s\S is workaround for "." not matching on CRLF
      if(text.indexOf('<html') != -1) text = text.replace(/^[\s\S]+<body[^>]*>/i,'').replace(/<\/body>[\s\S]*$/i,'');
      this.warn_text = text;
      window.scrollTo(0, 0);
      if (this.notifTimeout != null) clearTimeout(this.notifTimeout);
      this.notifTimout = setTimeout(function() 
      {
        self.warn_text = '';
        self.notifTimout = null;
      }, this.warn_time);
    },
    // positive answer from server (success)
    showInfo: function (text)
    {
      var self = this;
      // \s\S is workaround for "." not matching on CRLF
      if(text.indexOf('<html') != -1) text = text.replace(/^[\s\S]+<body[^>]*>/i,'').replace(/<\/body>[\s\S]*$/i,'');
      this.info_text = text;
      window.scrollTo(0, 0);
      if (this.notifTimeout != null) clearTimeout(this.notifTimeout);
      this.notifTimout = setTimeout(function() 
      {
        self.info_text = '';
        self.notifTimout = null;
      }, this.info_time);
    },
    // request list of all subnets from database
    fetchSubnets: function()
    {
      var self = this;
      ajaxJsonSpin("GET", "api/subnet.php", 
        this.showSubnet, 
        function(stat, resp) 
        {
      		self.showWarn("Can not fetch the list of subnets\n"+resp);
        }
      );
    },
    // request list of all hosts from database
    fetchHosts: function()
    {
      var self = this;
      ajaxJsonSpin("GET", "api/host.php",
        function(resp)
        {
          with(self)
            if(checkErr(resp))
            {
              if(resp['list']) host_list = resp['list'].slice(0,resp['list'].length);
            }
        }, 
        function(stat, resp) 
        {
      		self.showWarn("Can not fetch the list of hosts\n"+resp);
        }
      );
    },
    // request deletion of host
    askHostDelete: function(h)
    {
      if(window.confirm("Do you really want to delete the host '" + h.hostname + "' ?"))
      {
        var self = this;
        ajaxJsonSpin("GET", "api/host.php?del=1&net="+this.cur_net.id+"&ip="+h.ip,
          function(resp) 
          {
            self.host_list.splice(self.host_list.indexOf(h),1); // use .filter for <IE9
            self.find_id = '';
        		self.showInfo("Host was deleted");
        	}, 
        	function(stat,resp) 
        	{
        		self.showWarn("ERROR - "+resp);
        	}
        );
      }
    },
    // request deletion of subnet
    askNetDelete: function(s)
    {
      if(window.confirm("Do you really want to delete the subnet '" + s.title + "' ?"))
      {
        var self = this;
        ajaxJsonSpin("GET", "api/subnet.php?del=1&id="+s.id,
          function(resp) 
          {
            if(resp.del)
            {
              self.net_list.splice(self.net_list.indexOf(s),1); // use .filter for <IE9
          		self.showInfo("Subnet was deleted");
          	}
          	else self.showWarn("Subnet was not deleted");
        	}, 
        	function(stat,resp) 
        	{
        		self.showWarn("ERROR - "+resp);
        	}
        );
      }
    },
    // try to add new host
    addHost: function()
    {
      var self = this,
        mac = this.add_mac.replace(/[^0-9a-fA-F]/g,'').toUpperCase().split('');
      this.add_mac = mac[0] + mac[1] + '-' + mac[2] + mac[3] + '-' + mac[4] + mac[5] + '-' + mac[6] + mac[7] + '-' + mac[8] + mac[9] + '-' + mac[10] + mac[11];
      ajaxJsonSpin("GET", "api/host.php?add=1&net="+this.cur_net.id
        +"&ip="+this.add_ip
        +"&mac="+this.add_mac
        +"&name="+this.add_hostname, 
        function(resp) 
        {
          with(self)
            if(checkErr(resp))
            {
              host_list.push({id: resp.id, net: cur_net.id, ip: add_ip, mac: add_mac, hostname: add_hostname});
              add_ip = '';
              add_mac = '';
              add_hostname = '';
              find_id = '';
          		showInfo("Host was added");
            }
      	}, 
      	function(stat,resp) 
      	{
      		self.showWarn("ERROR - "+resp);
      	}
      );
    },
    // try to add new subnet
    addSubnet: function()
    {
      var self = this, 
        net;
      ajaxJsonSpin("GET", "api/subnet.php?add=1"
        +"&ip="+this.add_subnet
        +"&name="+this.add_title,
        function(resp) 
        {
          with(self)
            if(checkErr(resp))
            {
              net = add_subnet.split('.');
              net_list.push({id: resp.id, ip_1: net[0], ip_2: net[1], ip_3: net[2], title: add_title});
              add_subnet = '';
              add_title = '';
          		showInfo("Subnet was added");
            }
      	}, 
      	function(stat,resp) 
      	{
      		self.showWarn("ERROR - "+resp);
      	}
      );
    },
    checkErr: function (json)
    {
    
      return true;
    },
    // check if subnet contains hosts
    empty_net: function(net_id)
    {
      var i, 
        empty = true, 
        h = this.host_list;
      for(i=0; i<h.length; i++)
      {
        if(h[i].net == net_id)
        {
          empty = false;
          break;
        }
      }
      return empty;
    },
    // populate data-model with list of subnets
    showSubnet: function(resp)
    {
      if(this.checkErr(resp))
      {
        if(resp['list']) this.net_list = resp['list'].slice(0,resp['list'].length);
        if(this.net_list.length) 
        {
          this.cur_net = this.net_list[0];
          this.fetchHosts();
        }
        else $('#tab-B').checked = true;
      }
    },
    // respond to column clicking
    hostSort: function(col)
    {
      with(this)
      {
        find_id = '';
        if(host_sorting == col || host_sorting == -col) host_sorting = -host_sorting;
          else host_sorting = col;
      }
    },
    // respond to column clicking
    netSort: function(col)
    {
      with(this)
        if(subnet_sorting == col || subnet_sorting == -col) subnet_sorting = -subnet_sorting;
          else subnet_sorting = col;
    },
    // respond to column clicking
    findSort: function(col)
    {
      with(this)
        if(find_sorting == col || find_sorting == -col) find_sorting = -find_sorting;
          else find_sorting = col;
    },
    // sort the list of hosts
    sortedHost: function(a,b)
    {
      switch(this.host_sorting)
      {
        case 1:
          if(a.ip*1 < b.ip*1) return -1;
          else if(a.ip*1 > b.ip*1) return 1;
          else return 0;
        case -1:
          if(a.ip*1 < b.ip*1) return 1;
          else if(a.ip*1 > b.ip*1) return -1;
          else return 0;
        case 2:
          var ma = a.mac.replace(/[^0-9a-fA-F]/g,''),
            mb = b.mac.replace(/[^0-9a-fA-F]/g,'');
          if(ma < mb) return -1;
          else if(ma > mb) return 1;
          else return 0;
        case -2:
          var ma = a.mac.replace(/[^0-9a-fA-F]/g,''),
            mb = b.mac.replace(/[^0-9a-fA-F]/g,'');
          if(ma < mb) return 1;
          else if(ma > mb) return -1;
          else return 0;
        case 3:
          if(a.hostname < b.hostname) return -1;
          else if(a.hostname > b.hostname) return 1;
          else return 0;
        case -3:
          if(a.hostname < b.hostname) return 1;
          else if(a.hostname > b.hostname) return -1;
          else return 0;
        default:
          return 0;
      }
    },
    // sort the list of subnets
    sortedNet: function(a,b)
    {
      switch(this.subnet_sorting)
      {
        case 1:
          if(a.ip_1*1 < b.ip_1*1) return -1;
          else if(a.ip_1*1 > b.ip_1*1) return 1;
          else
          {
            if(a.ip_2*1 < b.ip_2*1) return -1;
            else if(a.ip_2*1 > b.ip_2*1) return 1;
            else
            {
              if(a.ip_3*1 < b.ip_3*1) return -1;
              else if(a.ip_3*1 > b.ip_3*1) return 1;
              else return 0;
            }
          }
        case -1:
          if(a.ip_1*1 < b.ip_1*1) return 1;
          else if(a.ip_1*1 > b.ip_1*1) return -1;
          else
          {
            if(a.ip_2*1 < b.ip_2*1) return 1;
            else if(a.ip_2*1 > b.ip_2*1) return -1;
            else
            {
              if(a.ip_3*1 < b.ip_3*1) return 1;
              else if(a.ip_3*1 > b.ip_3*1) return -1;
              else return 0;
            }
          }
        case 2:
          if(a.title < b.title) return -1;
          else if(a.title > b.title) return 1;
          else return 0;
        case -2:
          if(a.title < b.title) return 1;
          else if(a.title > b.title) return -1;
          else return 0;
        default:
          return 0;
      }
    },
    // sort the list of hosts on the SEARCH tab
    sortedFind: function(a,b)
    {
      switch(this.find_sorting)
      {
        case 1:
          var n1 = this.net_map[a.net],
            n2 = this.net_map[b.net];
          if(n1.ip_1*1 < n2.ip_1*1) return -1;
          else if(n1.ip_1*1 > n2.ip_1*1) return 1;
          else
          {
            if(n1.ip_2*1 < n2.ip_2*1) return -1;
            else if(n1.ip_2*1 > n2.ip_2*1) return 1;
            else
            {
              if(n1.ip_3*1 < n2.ip_3*1) return -1;
              else if(n1.ip_3*1 > n2.ip_3*1) return 1;
              else
              {
                if(a.ip*1 < b.ip*1) return -1;
                else if(a.ip*1 > b.ip*1) return 1;
                else return 0;
              }
            }
          }
        case -1:
          var n1 = this.net_map[a.net],
            n2 = this.net_map[b.net];
          if(n1.ip_1*1 < n2.ip_1*1) return 1;
          else if(n1.ip_1*1 > n2.ip_1*1) return -1;
          else
          {
            if(n1.ip_2*1 < n2.ip_2*1) return 1;
            else if(n1.ip_2*1 > n2.ip_2*1) return -1;
            else
            {
              if(n1.ip_3*1 < n2.ip_3*1) return 1;
              else if(n1.ip_3*1 > n2.ip_3*1) return -1;
              else
              {
                if(a.ip*1 < b.ip*1) return 1;
                else if(a.ip*1 > b.ip*1) return -1;
                else return 0;
              }
            }
          }
        case 2:
          var ma = a.mac.replace(/[^0-9a-fA-F]/g,''),
            mb = b.mac.replace(/[^0-9a-fA-F]/g,'');
          if(ma < mb) return -1;
          else if(ma > mb) return 1;
          else return 0;
        case -2:
          var ma = a.mac.replace(/[^0-9a-fA-F]/g,''),
            mb = b.mac.replace(/[^0-9a-fA-F]/g,'');
          if(ma < mb) return 1;
          else if(ma > mb) return -1;
          else return 0;
        case 3:
          if(a.hostname < b.hostname) return -1;
          else if(a.hostname > b.hostname) return 1;
          else return 0;
        case -3:
          if(a.hostname < b.hostname) return 1;
          else if(a.hostname > b.hostname) return -1;
          else return 0;
        default:
          return 0;
      }
    },
    // in-place editing
    startEdit: function(type, obj)
    {
      this.is_editing = obj;
      this.edit_part = type;
      switch(type)
      {
        case 1: 
          this.new_value = obj.ip; 
          break;
        case 2:
          this.new_value = obj.mac.replace(/:/g,'-');
          break;
        case 3:
          this.new_value = obj.hostname;
          break;
        case 4:
          this.new_value = obj.ip_1 + "." + obj.ip_2 + "." + obj.ip_3;
          break;
        case 5:
          this.new_value = obj.title;
          break;
      }
    },
    cancelEdit: function()
    {
      this.is_editing = null;
      this.edit_part = 0;
    },
    // ENTER = save changes, ESC = ignore changes
    endEdit: function(type, obj, event)
    {
      if(event.keyCode == 13)
      {
        var self = this;
        if(type>0 && type<4)
        {
          // hosts
          var
            tmp_ip = obj.ip, 
            tmp_mac = obj.mac, 
            tmp_name = obj.hostname,
            mac;
          switch(type)
          {
            case 1:
              tmp_ip = this.new_value;
              if(!tmp_ip.match(this.ip_reg)) tmp_ip = '';
              break;
            case 2:
              mac = this.new_value.replace(/[^0-9a-fA-F]/g,'').toUpperCase().split('');
              tmp_mac = mac[0] + mac[1] + '-' + mac[2] + mac[3] + '-' + mac[4] + mac[5] + '-' + mac[6] + mac[7] + '-' + mac[8] + mac[9] + '-' + mac[10] + mac[11];
              if(!tmp_mac.match(this.mac_reg)) tmp_mac = '';
              break;
            case 3:
              tmp_name = this.new_value;
              if(!tmp_name.match(this.name_reg)) tmp_name = '';
              break;
          }
          if(tmp_ip!='' && tmp_mac!='' && tmp_name!='')
            ajaxJsonSpin("GET", "api/host.php?edit=1&id="+obj.id
              +"&net="+obj.net
              +"&ip="+tmp_ip
              +"&mac="+tmp_mac
              +"&name="+tmp_name, 
              function(resp) 
              {
                with(self)
                  if(checkErr(resp))
                  {
                    obj.ip = tmp_ip;
                    obj.mac = tmp_mac;
                    obj.hostname = tmp_name;
                    cancelEdit();
                    find_id = '';
                		showInfo("Host was updated");
                  }
            	}, 
            	function(stat,resp) 
            	{
            		self.showWarn("ERROR - "+resp);
            	}
            );
        }
        else
        {
          // subnets
          var net,
            tmp_ip = obj.ip_1 + "." + obj.ip_2 + "." + obj.ip_3,
            tmp_name = obj.title;
          switch(type)
          {
            case 4:
              tmp_ip = this.new_value;
              if(!tmp_ip.match(this.net_reg)) tmp_ip = '';
              break;
          }
          if(tmp_ip!='' && tmp_name!='')
            ajaxJsonSpin("GET", "api/subnet.php?edit=1&id="+obj.id
              +"&ip="+tmp_ip
              +"&name="+tmp_name, 
              function(resp) 
              {
                with(self)
                  if(checkErr(resp))
                  {
                    net = tmp_ip.split('.');
                    obj.ip_1 = net[0];
                    obj.ip_2 = net[1];
                    obj.ip_3 = net[2];
                    obj.title = tmp_name;
                    cancelEdit();
                		showInfo("Subnet was updated");
                  }
            	}, 
            	function(stat,resp) 
            	{
            		self.showWarn("ERROR - "+resp);
            	}
            );
        }
      }
      else if(event.keyCode == 27) this.cancelEdit();
    },
    clickHost: function(h)
    {
      with(this)
      {
        cur_net = net_map[h.net];
        menu_id = 1;
        find_id = h.id;
      }
    }
  }
});

Vue.directive('focus', 
{
  inserted: function (el) 
  {
    el.focus();
  }
});
