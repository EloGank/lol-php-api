#!/usr/bin/env bash

# ---------------------------------------------------
# https://github.com/EloGank/lol-php-api
# based on : https://github.com/Divi/VagrantBootstrap
# ---------------------------------------------------

# Include parameteres file
# ------------------------
source /vagrant/.vagrant_bootstrap/parameters.sh

# Update the box release repositories
# -----------------------------------
apt-get update


# Essential Packages
# ------------------
apt-get install -y build-essential git-core vim curl php5-dev pkg-config


# PHP 5.x (last official release)
# See: https://launchpad.net/~ondrej/+archive/php5
# ------------------------------------------------
apt-get install -y libcli-mod-php5
# Install "add-apt-repository" binaries
apt-get install -y python-software-properties
# Install PHP 5.x
# Use "ppa:ondrej/php5-oldstable" for old and stable release
add-apt-repository ppa:ondrej/php5
# Update repositories
apt-get update

# PHP tools
apt-get install -y php5-cli php5-curl php5-mcrypt
# APC (only with PHP < 5.5.0, use the "opcache" if >= 5.5.0)
# apt-get install -y php-apc
# Setting the timezone
sed 's#;date.timezone\([[:space:]]*\)=\([[:space:]]*\)*#date.timezone\1=\2\"'"$PHP_TIMEZONE"'\"#g' /etc/php5/cli/php.ini > /etc/php5/cli/php.ini.tmp
mv /etc/php5/cli/php.ini.tmp /etc/php5/cli/php.ini
# Showing error messages
sed 's#display_errors = Off#display_errors = On#g' /etc/php5/cli/php.ini > /etc/php5/cli/php.ini.tmp
mv /etc/php5/cli/php.ini.tmp /etc/php5/cli/php.ini
sed 's#display_startup_errors = Off#display_startup_errors = On#g' /etc/php5/cli/php.ini > /etc/php5/cli/php.ini.tmp
mv /etc/php5/cli/php.ini.tmp /etc/php5/cli/php.ini
sed 's#error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT#error_reporting = E_ALL#g' /etc/php5/cli/php.ini > /etc/php5/cli/php.ini.tmp
mv /etc/php5/cli/php.ini.tmp /etc/php5/cli/php.ini


# Redis
# -----
add-apt-repository -y ppa:rwky/redis
apt-get update
apt-get install -y redis-server

# Installing hiredis lib
cd /tmp
git clone https://github.com/redis/hiredis.git
cd hiredis
make && make install

# Installing phpiredis PHP lib (make Redis faster for un/serialization process)
cd /tmp
git clone https://github.com/nrk/phpiredis.git
cd phpiredis
phpize && ./configure --enable-phpiredis
make && make install
echo "extension=phpiredis.so" > /etc/php5/cli/conf.d/20-phpiredis.ini

# ZERO MQ
# -------
cd /tmp
wget http://download.zeromq.org/zeromq-$ZMQ_VERSION.tar.gz
tar -zxvf zeromq-4.0.4.tar.gz
cd zeromq-4.0.4/
./configure
make
make install

# PHP ZMQ Extension
# -----------------
yes '' | pecl install zmq-beta
echo "extension=zmq.so" > /etc/php5/cli/conf.d/20-zmq.ini
