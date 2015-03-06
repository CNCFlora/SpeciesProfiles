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
    d.run "cncflora/connect", name: "connect", args: "-p 8080:80 -v /var/connect:/var/floraconnect:rw"
    d.run "cncflora/elasticsearch", name: "elasticsearch",args: "-p 9200:9200"
    d.run "cncflora/couchdb", name: "couchdb", args: "-p 5984:5984 -p 9001:9001 --link elasticsearch:elasticsearch -v /var/couchdb:/var/lib/couchdb:rw"
    d.run "cncflora/checklist", name: "checklist", args: "-p 8001:80"
  end

  config.vm.provision :shell, :path => "vagrant.sh"
end

