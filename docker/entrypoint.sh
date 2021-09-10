#!/usr/bin/env bash

# check if .env file exists, if not this is a initial install, so install everything
if [ ! -f "/var/www/conifg.settings.php" ]; then
    # copy .env file
    echo "⭐️ Copy settings.php file";
    cp /var/www/install/settings-docker.php /var/www/config/settings.php
fi

# run apache in foreground
apache2-foreground