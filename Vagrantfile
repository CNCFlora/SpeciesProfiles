# -*- mode: ruby -*-
# vi: set ft=ruby :

# Vagrantfile API/syntax version. Don't touch unless you know what you're doing!
VAGRANTFILE_API_VERSION = "2"

Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|
  config.vm.box = "ubuntu/trusty64"

  config.vm.network "private_network", ip: "192.168.50.10"
  config.vm.network :forwarded_port, host: 8888, guest: 80, auto_correct: true

  config.vm.provider "virtualbox" do |v|
    v.memory = 2048
    v.cpus = 2
  end

  if Vagrant.has_plugin?("vagrant-cachier")
      config.cache.scope = :box
  end

  config.vm.provision "docker" do |d|
    d.run "192.168.50.1:5000/etcd", name: "etcd", args: "-p 4001:4001"
    d.run "192.168.50.1:5000/connect", name: "connect", args: "-P -v /var/connect:/var/floraconnect:rw"
    d.run "192.168.50.1:5000/elasticsearch", name: "elasticsearch",args: "-p 9200"
    d.run "192.168.50.1:5000/couchdb", name: "couchdb", args: "-P --link elasticsearch:elasticsearch -v /var/couchdb:/var/lib/couchdb:rw"
    d.run "192.168.50.1:5000/dwc-services", name: "dwc-services", args: "-P"
    d.run "192.168.50.1:5000/floradata", name: "floradata", args: "-P"
    d.run "192.168.50.1:5000/checklist", name: "checklist", args: "-P"
  end

  config.vm.provision :shell, :path => "vagrant.sh"
end

