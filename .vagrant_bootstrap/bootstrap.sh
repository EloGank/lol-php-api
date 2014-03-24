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
apt-get install -y build-essential git-core vim curl


# PHP with Thread Safe build
# --------------------------
echo "################################################################"
echo "#                                                              #"
echo "#            CLONING PHP REPOSITORY, PLEASE WAIT               #"
echo "#            ===================================               #"
echo "#                                                              #"
echo "################################################################"
git clone https://github.com/php/php-src.git /home/php-src
cd /home/php-src
git checkout tags/php-5.5.10
apt-get install -y make autoconf re2c bison
# If you add some configuration command, add the dependancies here
apt-get install -y libicu-dev libmcrypt-dev libssl-dev libcurl4-openssl-dev libbz2-dev libxml2-dev libpng-dev libjpeg-dev libedit-dev
./buildconf --force
./configure --prefix=$PHP_DIRECTORY --with-config-file-path=$PHP_DIRECTORY --with-config-file-scan-dir=$PHP_DIRECTORY/conf.d --enable-maintainer-zts --with-curl --with-openssl --with-gd --enable-gd-native-ttf --enable-intl --enable-mbstring --with-mcrypt --with-mysqli=mysqlnd --with-zlib --with-bz2 --enable-exif --with-pdo-mysql=mysqlnd --with-libedit --enable-zip --enable-pdo --enable-pcntl --enable-sockets --enable-mbregex --with-tsrm-pthreads --enable-sysvshm --enable-sysvmsg
# If you need FPM mode, add : --enable-fpm --with-fpm-group=www-data --with-fpm-user=www-data
make
make install
cp php.ini-production /etc/php5ts/php.ini
echo "alias phpts=\"/etc/php5ts/bin/php\"" > /home/vagrant/.bash_profile


# pthreads Extension build
# ------------------------
git clone https://github.com/krakjoe/pthreads.git /home/pthreads
cd /home/pthreads
apt-get install -y php5-dev
phpize
./configure --with-php-config=$PHP_DIRECTORY/bin/php-config
make
make install
mkdir $PHP_DIRECTORY/conf.d
echo "extension=pthreads.so" > /etc/php5ts/conf.d/pthreads.ini

# Setting PHP configurations
# --------------------------
# Date timezone
sed 's#;date.timezone\([[:space:]]*\)=\([[:space:]]*\)*#date.timezone\1=\2\"'"$PHP_TIMEZONE"'\"#g' $PHP_DIRECTORY/php.ini > $PHP_DIRECTORY/php.ini.tmp
mv $PHP_DIRECTORY/php.ini.tmp $PHP_DIRECTORY/php.ini
# Error messages
sed 's#display_errors = Off#display_errors = On#g' $PHP_DIRECTORY/php.ini > $PHP_DIRECTORY/php.ini.tmp
mv $PHP_DIRECTORY/php.ini.tmp $PHP_DIRECTORY/php.ini
sed 's#display_startup_errors = Off#display_startup_errors = On#g' $PHP_DIRECTORY/php.ini > $PHP_DIRECTORY/php.ini.tmp
mv $PHP_DIRECTORY/php.ini.tmp $PHP_DIRECTORY/php.ini
sed 's#error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT#error_reporting = E_ALL#g' $PHP_DIRECTORY/php.ini > $PHP_DIRECTORY/php.ini.tmp
mv $PHP_DIRECTORY/php.ini.tmp $PHP_DIRECTORY/php.ini
