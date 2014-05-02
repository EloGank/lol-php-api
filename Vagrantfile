# -*- mode: ruby -*-
# vi: set ft=ruby :

# --------------------------------------
# https://github.com/EloGank/lol-php-api
# --------------------------------------

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
    v.customize ["modifyvm", :id, "--memory", 1024]
    v.customize ["modifyvm", :id, "--name", "EloGank - PHP LoL API"]
  end
  
  # Running bootstrap
  # -----------------
  config.vm.provision :shell, :path => ".vagrant_bootstrap/bootstrap.sh"
end