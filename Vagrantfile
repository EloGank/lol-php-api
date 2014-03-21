# -*- mode: ruby -*-
# vi: set ft=ruby :

# ----------------------------------------
# https://github.com/Divi/VagrantBootstrap
# ----------------------------------------

VAGRANTFILE_API_VERSION = "2"

Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|
  # Core configurations
  # -------------------
  config.vm.box = "precise32"
  config.vm.box_url = "http://files.vagrantup.com/precise32.box"
  config.ssh.forward_agent = true
  config.vm.network :private_network, ip: "192.168.100.10"

  config.vm.provider :virtualbox do |v|
    v.customize ["modifyvm", :id, "--natdnshostresolver1", "on"]
    v.customize ["modifyvm", :id, "--memory", 2048]
    v.customize ["modifyvm", :id, "--name", "EloGank - PHP LoL API"]
  end
  
  # Running bootstrap
  # -----------------
  config.vm.provision :shell, :path => ".vagrant_bootstrap/bootstrap.sh"
  
  # Synced folders
  # --------------
  # config.vm.synced_folder "./.apache2_vhosts", "/etc/apache2/sites-available"
  
  # If you want to share your SSH key, uncomment this line (you must execute commands (like "git clone") in "sudo su", because the key will be in the root folder, otherwise put it in vagrant folder) :
  # config.vm.synced_folder "~/.ssh", "/root/.ssh"

  # Forwarding ports
  # ----------------
  # config.vm.network :forwarded_port, guest: 80, host: 8000
  # config.vm.network :forwarded_port, guest: 3306, host: 33060
end