# 2019-04-04 GeoIP setup

Got link to City DB from https://dev.maxmind.com/geoip/geoip2/geolite2/

    cd /var/www/sites/2019-05-sgfwebdevs-hackathon.dev/
    mkdir var
    wget https://geolite.maxmind.com/download/geoip/database/GeoLite2-City.tar.gz
    tar -xzvf GeoLite2-City.tar.gz
    mv GeoLite2-City_20190402/GeoLite2-City.mmdb ./
    rm -rf GeoLite2-City_20190402 GeoLite2-City.tar.gz

See `index.php` for basic usage example

# Import DB Erik:

mysql -e 'DROP DATABASE IF EXISTS `emergencme`;'
mysql -e 'CREATE DATABASE IF NOT EXISTS `emergencme`;'
cd /var/www/sites/sgfhackathon.dev
pv var/emergencme.dump | mysql emergencme


#  Test change


Test