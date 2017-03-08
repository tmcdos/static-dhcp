# Simple web form for static assignment of DHCP leases

This is a very simple web interface for management of static DHCP leases in ***DNSmasq*** and ***Mikrotik***. 
It generates config files for ***DNSmasq*** and uses ***RouterOS*** API to manage Mikrotik. 
Network devices (usually PCs) are separated into subnets by department and use triplets (hostname, MAC address, IP address) for identification and preventing duplicates. 
Information is stored in MySQL database and only exported by your explicit desire.
All unknown DHCP leases (not statically assigned) are shown on a separate screen.
