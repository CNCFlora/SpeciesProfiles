FROM cncflora/apache

ADD vendor /var/www/vendor
ADD config.yml /var/www/config.yml
ADD resources /var/www/resources
ADD html /var/www/html
ADD src /var/www/src

