League of Legends PHP API
=========================

## Installation

### Virtual Machine

If you want to install/try this API with a Virtual Machine and avoid this installation process, read the [virtual machine documentation](./installation_vagrant.md).  
It will take you only two minutes.

**If you want to install/try this API on a Windows system, we advice you to choose the Virtual Machine installation process. Some dependencies are not easy to build on this operating system.**

### Manually

**Note:** these packages are needed to compile these dependencies : `build-essential git-core curl php5-dev pkg-config`

#### ZeroMQ (optional)

ZeroMQ is the technology used to allow the asynchronous client system in the API.  
This dependency is optional if you don't use the asynchronous system.

Here, for **Linux or OSX**, a CLI script to install this dependency :

``` bash
cd /tmp
# Note: use the last version here : http://zeromq.org/intro:get-the-software
wget http://download.zeromq.org/zeromq-4.0.4.tar.gz
tar -zxvf zeromq-4.0.4.tar.gz
cd zeromq-4.0.4/
./configure
make
make install
```

Then, install the PHP extension :
``` bash
yes '' | pecl install zmq-beta
echo "extension=zmq.so" > /etc/php5/cli/conf.d/20-zmq.ini
```
    
For **Windows**, please follow this official instructions : http://zeromq.org/docs:windows-installations and for the extension, install `PECL` and run `pecl install zmq-beta` in a CLI window.
    
#### Redis (optional)

Redis is a cache system. It allow, in this API, to communicate between all asynchronous client and with the main process logger.  
This dependency is optional if you don't use the asynchronous system.

Here, for **Linux or OSX**, a CLI script to install this dependency :

``` bash
# Installing the last Redis version
add-apt-repository -y ppa:rwky/redis
apt-get update
apt-get install -y redis-server

# Installing hiredis library dependency for the phpiredis
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
```

On **Windows**, download Redis here : https://github.com/mythz/redis-windows/tree/master/downloads and launch the "redis-server.exe" when you want to use this API.

#### Composer

Composer is a Command-Line Interface (CLI) dependency manager for PHP.

* Get [Composer](https://getcomposer.org) by copy/paste this line on your shell (in the project root directory) :  
  * On **Linux/OSX** : `curl -sS https://getcomposer.org/installer | php`
  * On **Windows** : `php -r "readfile('https://getcomposer.org/installer');" | php`
* Then, install all dependancies : `php composer.phar install`

### Next

Great ! Now, see the [configuration documentation](./configuration.md).