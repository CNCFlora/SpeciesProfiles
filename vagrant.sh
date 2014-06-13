#!/usr/bin/env bash

apt-get update
apt-get upgrade -y
apt-get autoremove -y

# add apache and php
if [[ ! -e ~/.apache_done ]]; then
    # install apache and php
    apt-get install apache2 libapache2-mod-php5 php5-pgsql php5 php5-cli php5-curl php5-common php5-gd php5-xdebug php5-sqlite php5-pgsql php5-mysql -y
    a2enmod rewrite
    service apache2 stop
    # use the project folder as main folder
    rm /var/www -Rf
    ln -s /vagrant /var/www
    chown vagrant /var/lock/apache2 -Rf
    # setup apache vars
    sed -i -e 's/RUN_USER=www-data/RUN_USER=vagrant/g' /etc/apache2/envvars
    cp /vagrant/default.conf /etc/apache2/sites-available/000-default.conf
    # setup some php env vars
    sed -i -e 's/memory_limit.*/memory_limit=512M/g' /etc/php5/apache2/php.ini
    sed -i -e 's/upload_max_filesize.*/upload_max_filesize=128M/g' /etc/php5/apache2/php.ini
    sed -i -e 's/post_max_size.*/post_max_size=128M/g' /etc/php5/apache2/php.ini
    sed -i -e 's/display_errors.*/display_erros=On/g' /etc/php5/apache2/php.ini
    echo "127.0.0.1 test.localhost" >> /etc/hosts
    # restart
    service apache2 start
    touch ~/.apache_done
fi

if [[ ! -e ~/.app_done ]]; then
    su vagrant -lc "cd /vagrant && curl -sS https://getcomposer.org/installer | php"
    su vagrant -lc "cd /vagrant && php composer.phar update && php composer.phar install"
    touch ~/.app_done
fi

# docker register to etcd
if [[ ! -e /usr/bin/docker2etcd ]]; then
    wget https://gist.githubusercontent.com/diogok/24cf050e880731783d40/raw/e0f0e05e532488fec803c68022d514975034e8d8/docker2etcd.rb \
          -O /usr/bin/docker2etcd 
    chmod +x /usr/bin/docker2etcd 
fi
/usr/bin/docker2etcd

# setup couchdb
HUB=$(docker ps | grep datahub | awk '{ print $10 }' | grep -e '[0-9]\{5\}' -o)
curl -X PUT http://localhost:$HUB/cncflora
curl -X PUT http://localhost:$HUB/cncflora_test

# done
echo "Done bootstraping"

