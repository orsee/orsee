#!/bin/bash

#Please adapt all the variables to your needs.

mysqluser=mysqlusername
mysqlpwd=mysqlpwd

orseedbname=orseedbname
orseedbusername=orseedbuser
orseedbpwd=orseedbpwd

echo CREATING DATABASE
mysql -u"'$mysqluser'" -p$mysqlpwd <<EOF
CREATE DATABASE $orseedbname;
GRANT ALL PRIVILEGES ON $orseedbname.* TO $orseedbusername@localhost IDENTIFIED BY '$orseedbpwd';
FLUSH PRIVILEGES;
quit
EOF

echo INITIALISING DATABASE WITH PREDUMP
mysql $orseedbname -u$orseedbusername -p$orseedbpwd < install.sql
