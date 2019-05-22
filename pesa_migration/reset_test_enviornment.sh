#!/bin/bash

# clear pesa2019 tables
mysql -uroot -p -Nse 'show tables in pesa2019' | while read table; do mysql -uroot -p -e "truncate table pesa2019.$table"; done
# re-init tables
mysql pesa2019 -upesaUser -ppesa2019 < /var/www/html/pesa2019/install/install.sql

# clear and load old pesa
mysql pesa2019 -uroot -p -e 'drop database pesa'
mysql -uroot -p < pesa.20190402.sql

