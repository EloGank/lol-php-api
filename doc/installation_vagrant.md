League of Legends PHP API
=========================

## Virutal Machine Installation

### Virtual Machine

This API has a ready-to-use Virtual Machine (VM) installation process. But before, you need to install the VM binaries. We using [Vagrant](http://www.vagrantup.com/).

**If you want to install/try this API on a Windows system, we advice you to choose the Virtual Machine installation process. Some dependencies are not easy to build on this operating system.**

#### Download & Install

* Download Vagrant : http://www.vagrantup.com/downloads.html
* Download Virtual Box : https://www.virtualbox.org/wiki/Downloads

#### Run

Edit your environment parameters in the file `.vagrant_bootstrap/parameters.sh`.  
Now, open the Command-Line Interface (CLI) window, and, in the project root directory run this command :

    vagrant up
    
This command install the VM and all the API dependencies. It takes generally 2~3 minutes, it depends on your Internet connection speed.  
When you want to shutdown the VM, just run this command :

    vagrant halt
    
We advise you to read the [Vagrant documentation](http://docs.vagrantup.com/v2/getting-started/index.html) for more information.

### Next

Great ! Now, see the [configuration documentation](./configuration.md).