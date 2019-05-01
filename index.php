<?php
/**
 * @category    KrakenCommerce
 * @copyright   Copyright (c) 2019 Kraken, LLC
 */

require 'vendor/autoload.php';

use GeoIp2\Database\Reader;

$dbPassword = $_SERVER['HOSTNAME'] === 'dev-web72' ? '' : 'root';

$f3 = \Base::instance();
$db = new DB\SQL(
    'mysql:host=localhost;port=3306;dbname=emergencme',
    'root',
    $dbPassword
);

var_dump($db->exec('SELECT * FROM first_table'));

# From https://github.com/maxmind/GeoIP2-php#city-example
// This creates the Reader object, which should be reused across
// lookups.
$reader = new Reader('var/GeoLite2-City.mmdb');


// Replace "city" with the appropriate method for your database, e.g.,
// "country".
$record = $reader->city('128.101.101.101');

print($record->country->isoCode . "\n"); // 'US'
print($record->city->name . "\n"); // 'US'
print($record->country->name . "\n"); // 'United States'
print($record->country->names['zh-CN'] . "\n"); // '美国'
