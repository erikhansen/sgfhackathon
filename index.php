<?php
require 'vendor/autoload.php';

// Google Maps API key. Committed this key to the repo since it will be expired after 2019-05-01
const API_KEY = 'AIzaSyCE7idJgRotl465ooRS0VCaCCP43S3di3s';

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

    // google map geocode api url
    $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key=" . API_KEY;

    $respJson = file_get_contents($url);

    // decode the json
    $resp = json_decode($respJson, true);

    if ($resp['status'] !== 'OK') {
        throw new \Exception('Unable to parse address');
    }

    $county = null;

    // Get county name
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
                    break 3;
                }
            }
        }
    }

    $lati = isset($resp['results'][0]['geometry']['location']['lat'])
        ? $resp['results'][0]['geometry']['location']['lat'] : "";
    $longi = isset($resp['results'][0]['geometry']['location']['lng'])
        ? $resp['results'][0]['geometry']['location']['lng'] : "";

    // verify if data is complete
    if ($lati && $longi) {

        $dataArr = [];

        array_push(
            $dataArr,
            $lati,
            $longi,
            $county
        );

        return $dataArr;

    } else {
        throw new \Exception('Unable to parse address');
    }
}
function sendRequest($type, $reason, $description, $countyId, $lat, $lng)
{
    $url = 'https://alerts.sgf.dev/SubmitAlert';

    $data = [
        'type' => $type,
        'reason' => $reason,
        'description' => $description,
        'countyId' => $countyId,
        'lat' => $lat,
        'lng' => $lng
    ];

    echo "Sending this data to server:\n";
    var_dump($data);
    echo PHP_EOL;

    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    if ($result === false) {
        throw new \Exception('Unable to send request');
    }

    return $result;
}

for ($i = 0; $i < 10; $i++) {
    $contents = file_get_contents($alertUrl);
    $dom->load($contents);
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

    $result = sendRequest($data['type'], $data['reason'], $data['details'], $data['countyId'], $data['lat'], $data['lng']);

    echo "Result from server:\n";
    echo json_encode(json_decode($result), JSON_PRETTY_PRINT) . PHP_EOL;
}
