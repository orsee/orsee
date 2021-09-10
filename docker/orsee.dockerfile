FROM php:7.3.29-apache AS apache
# set workdir
RUN mkdir -p /var/www/
WORKDIR /var/www

# upgrades!
RUN apt-get update
RUN apt-get -y dist-upgrade
RUN apt-get install -y dos2unix

RUN apt-get install -y nano
RUN apt-get install -y git
RUN apt-get install -y zip unzip
RUN apt-get install -y libxml2-dev
RUN apt-get install -y libssh2-1
RUN apt-get install -y libssh2-1-dev
RUN apt-get install -y wget
RUN apt-get install -y sudo
RUN apt-get install -y iputils-ping

RUN apt-get clean -y

# set corrent TimeZone
ENV TZ=Europe/Amsterdam
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# install additional webserver packages
RUN a2enmod ssl
RUN a2enmod rewrite
RUN a2enmod headers

# install additional PHP extensions
RUN docker-php-ext-install pdo_mysql mysqli soap

# expose port
EXPOSE 9018

# copy httpd files
COPY ./docker/httpd.conf /etc/apache2/sites-enabled/000-default.conf

# copy webapp files
COPY ./ /var/www

# copy github token
COPY ./docker/auth.json /root/.composer/auth.json

# install self signed certifcates to thrust other local dev environments
COPY ./docker/certificates/other-environments/myCA.pem /etc/ssl/certs
RUN cd /usr/local/share/ca-certificates && update-ca-certificates

# entrypoint
COPY ./docker/entrypoint.sh /entrypoint.sh
RUN chmod ugo+x /entrypoint.sh
RUN dos2unix /entrypoint.sh

ENTRYPOINT /entrypoint.sh