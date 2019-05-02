<?php
/**
 * @category    KrakenCommerce
 * @copyright   Copyright (c) 2019 Kraken, LLC
 */

require 'vendor/autoload.php';

use PHPHtmlParser\Dom;

$counties = file_get_contents("https://alerts.sgf.dev/Counties");

$counties = json_decode($counties, true);
$countyMap = [];

foreach ($counties['data'] as $county) {
    $countyMap[$county['county']] = $county['id'];
}

$dom = new Dom;

$typeMap = ['Alert' => 3, 'Advisory' => 2];
$reasonMap = ['Alert' => 42, 'Advisory' => 41];

$alertUrl = "https://alerts.sgf.dev/";
$data = [];
function geocode($address)
{

    // url encode the address
    $address = urlencode($address);

    $apiKey = 'AIzaSyCE7idJgRotl465ooRS0VCaCCP43S3di3s';

    // google map geocode api url
    $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key=$apiKey";

    // get the json response
    $resp_json = file_get_contents($url);

    // decode the json
    $resp = json_decode($resp_json, true);

    // response status will be 'OK', if able to geocode given address
    if ($resp['status'] == 'OK') {

        $county = null;
        foreach ($resp['results'][0] as $component) {
            if (!is_array($component)) {
                continue;
            }
            foreach ($component as $subComponent) {
                if (!isset($subComponent['types']) || !is_array($subComponent['types'])) {
                    continue;
                }
                foreach ($subComponent['types'] as $type) {
                    if ($type === 'administrative_area_level_2') {
                        $county = $subComponent['long_name'];
                    }
                }
            }
        }

        // get the important data
        $lati = isset($resp['results'][0]['geometry']['location']['lat'])
            ? $resp['results'][0]['geometry']['location']['lat'] : "";
        $longi = isset($resp['results'][0]['geometry']['location']['lng'])
            ? $resp['results'][0]['geometry']['location']['lng'] : "";
        $formatted_address = isset($resp['results'][0]['formatted_address']) ? $resp['results'][0]['formatted_address']
            : "";

        // verify if data is complete
        if ($lati && $longi && $formatted_address) {

            // put the data in the array
            $data_arr = [];

            array_push(
                $data_arr,
                $lati,
                $longi,
                $county,
                $formatted_address
            );

            return $data_arr;

        } else {
            return false;
        }

    } else {
        echo "<strong>ERROR: {$resp['status']}</strong>";

        return false;
    }
}
function sendRequest($type, $reason, $description, $countyId, $lat, $lng) {
    $url = 'https://alerts.sgf.dev/SubmitAlert';

    $data = [
        'type' => $type,
        'reason' => $reason,
        'description' => $description,
        'countyId' => $countyId,
        'lat' => $lat,
        'lng' => $lng
    ];

    // use key 'http' even if you send the request to https://...
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    if ($result === FALSE) { /* Handle error */ }

    var_dump($result);
}

for ($i = 0; $i < 10; $i++) {
    $contents = file_get_contents($alertUrl);
    //echo $contents;
    $dom->load($contents);
    //    echo $dom->outerHtml;
    $data['details'] = "DreamTeam + " . $dom->find('.full_message_info h2')->firstChild()->innerHtml;

    $priority = $dom->find('.full_message_info .priority')->firstChild()->innerHtml;
    $agencies = $dom->find('[class=agency first_agency]');

    foreach ($agencies as $element) {

        $rawAddress = preg_replace('/<[^>]*>/', '', $element->innerHtml);
        $rawAddress = str_replace('Address/Location', '', $rawAddress);
        $parsedAddress = geocode($rawAddress);

    }

    $data['type'] = $typeMap[$priority];
    $data['reason'] = $reasonMap[$priority];
    $data['lat'] = $parsedAddress[0];
    $data['lng'] = $parsedAddress[1];
    $data['countyId'] = $countyMap[trim(str_replace('County', '', $parsedAddress[2]))];

    var_dump($data);
    sendRequest($data['type'], $data['reason'], $data['details'], $data['countyId'], $data['lat'], $data['lng']);
}




//var_dump($db->exec('SELECT * FROM first_table'));

# From https://github.com/maxmind/GeoIP2-php#city-example
// This creates the Reader object, which should be reused across
// lookups.
//$reader = new Reader('var/GeoLite2-City.mmdb');

// Replace "city" with the appropriate method for your database, e.g.,
// "country".
//$record = $reader->city('128.101.101.101');
//
//print($record->country->isoCode . "\n"); // 'US'
//print($record->city->name . "\n"); // 'US'
//print($record->country->name . "\n"); // 'United States'
//print($record->country->names['zh-CN'] . "\n"); // '美国'
