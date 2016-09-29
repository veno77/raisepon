RAISEPON 1.1

Raisepon is Opensource php/mysql software written to manage Subscriber base on RAISECOM's EPON OLTs ISCOM5508, ISCOM5800,ISCOM6800. Supported are most of RAISECOM ONUs.
Currently there is no support for RAISECOM's GPON solution.

Installation:

I suggest using latest FreeBSD Stable Release.
You need:

Apache 2.2 or later
PHP 5.5 
PHP 5.5 Extensions + Mysql PDO
Mysql 5.5 
net-snmp + php-snmp
rrdtool + pecl-rrd

Copy the files to your web folder.
Create database "raisecom" and load in it the supplied raisecom.sql file
Grant permissions to user for databse "raisecom". Modify common.php  to match the mysql user and pass.


Add the following to your crontab:

*/5 * * * *     www     /usr/local/bin/php -f /path/to/your/webcontent/update_rrd.php > /dev/null 2>&1

Configure your OLTs to send logs to your syslogd server. 
Edit your sylogd.conf:

local7.*                                        /var/log/raisecom.log

Create rrd/ directory under the raisepon root tree.

Default username/password admin/admin123.

Usage:
1. You need to add at least One OLT and one pon port to be able to add customers.
2. ONU types are predefined, you can add new ONU types supported by RAISECOM.
3. Clicking on different parts in index.html when you load the customers on selected OLT and PON will give you more information.
4. You can use parse.php to parse existing ONUs on your OLTs.

I am not professional programmer, so I do not pretend the code is pretty. 

The software is provided under the MIT License. Read below:

The MIT License (MIT)

Copyright (c) 2015 Ventsislav Velkov a.k.a. Veno - vtvelkov@gmail.com 

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.



