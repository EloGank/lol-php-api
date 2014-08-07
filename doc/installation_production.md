League of Legends PHP API
=========================

## Installation (for production environment)

### Warning

**This page is only additional installation instructions and you must follow the [main installation instructions](./installation.md) before.**  
These instructions are only for production env because the API will be **more faster** and **reduce CPU usage**, but **less convenient**. Feel free to follow them for development environment if you want the same environment as production.

### Dedicated PHP version

PHP is more slower if useless extensions are loaded. So we will compile a fresh version, with only needed extensions and **increase speed by 25%**.

The dedicated PHP version will compiled in `/etc/php5-api` directory. Feel free to change this path.

#### Clone from Github

At the time of writing this documentation, the last PHP version is `5.5.15`, it's recommended to install the last version, please note the [last PHP version](https://php.net/downloads.php) and edit the third line below.

``` bash
git clone https://github.com/php/php-src.git /tmp/php-src
cd /tmp/php-src
git checkout tags/php-5.5.15
```

#### Compilation

``` bash
# Installing needed dependencies
apt-get install -y make autoconf re2c bison
# Installing needed libs
apt-get install -y libssl-dev libcurl4-openssl-dev
# Compile
./buildconf --force
./configure --prefix=/etc/php5-api --with-config-file-path=/etc/php5-api --with-config-file-scan-dir=/etc/php5-api/conf.d --disable-all --with-curl --with-openssl --enable-sockets --enable-ctype --enable-pcntl --enable-json --enable-posix
make
make install
```

If you have uBuntu 14.04 TLS or an error `WARNING: bison versions supported for regeneration of the Zend/PHP parsers: 2.4 2.4.1 2.4.2 2.4.3 2.5 2.5.1 2.6 2.6.1 2.6.2 2.6.3 2.6.4 2.6.5 2.7 (found: 3.0).`, just download the last package from links below and install them with dpkg (see below).

 * libbison-dev : http://packages.ubuntu.com/saucy/libbison-dev
 * bison : http://packages.ubuntu.com/saucy/bison

``` bash
wget http://launchpadlibrarian.net/140087283/libbison-dev_2.7.1.dfsg-1_amd64.deb
wget http://launchpadlibrarian.net/140087282/bison_2.7.1.dfsg-1_amd64.deb
dpkg -i libbison-dev_2.7.1.dfsg-1_amd64.deb
dpkg -i bison_2.7.1.dfsg-1_amd64.deb
```

#### Settings

You can change your timezone by editing the second line : replace `UTC` by your current timezone, like `Europe/Paris`.

``` bash
cp php.ini-production /etc/php5-api/php.ini
mkdir /etc/php5-api/conf.d
sed 's#;date.timezone\([[:space:]]*\)=\([[:space:]]*\)*#date.timezone\1=\2\"'"UTC"'\"#g' /etc/php5-api/php.ini > /etc/php5-api/php.ini.tmp
mv /etc/php5-api/php.ini.tmp /etc/php5-api/php.ini
```
In your `config.yml` file, edit the PHP executable path :

``` yml
# config/config.yml
php:
    executable: /etc/php5-api/bin/php
```

**Only if you want to install this dedicated PHP version on a DEVELOPMENT environment, you can show errors :**

``` bash
# ONLY FOR DEVELOPMENT ENVIRONMENT : Error messages
sed 's#display_errors = Off#display_errors = On#g' /etc/php5-api/php.ini > /etc/php5-api/php.ini.tmp
mv /etc/php5-api/php.ini.tmp /etc/php5-api/php.ini
sed 's#display_startup_errors = Off#display_startup_errors = On#g' /etc/php5-api/php.ini > /etc/php5-api/php.ini.tmp
mv /etc/php5-api/php.ini.tmp /etc/php5-api/php.ini
sed 's#error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT#error_reporting = E_ALL#g' /etc/php5-api/php.ini > /etc/php5-api/php.ini.tmp
mv /etc/php5-api/php.ini.tmp /etc/php5-api/php.ini
```

#### Reinstallation of ZeroMQ extensions

**Note :** the build directory (`20121212`) can be different in your OS.

``` bash
mkdir /etc/php5-api/lib/php/extensions/
mkdir /etc/php5-api/lib/php/extensions/no-debug-non-zts-20121212
cp /usr/lib/php5/20121212/zmq.so /etc/php5-api/lib/php/extensions/no-debug-non-zts-20121212/zmq.so
cp /etc/php5/cli/conf.d/20-zmq.ini /etc/php5-api/conf.d/
```

#### Usage

Start your api with this command : `/etc/php5-api/bin/php console elogank:api:start`
