<?php
const API_KEY = 'AIzaSyCE7idJgRotl465ooRS0VCaCCP43S3di3s';

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

    // google map geocode api url
    $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key=" . API_KEY;

    $respJson = file_get_contents($url);

    // decode the json
    $resp = json_decode($respJson, true);

    if ($resp['status'] !== 'OK') {
        throw new \Exception('Unable to parse address');
    }

    $county = null;

    print_r($resp);
    die();

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

    // verify if data is complete
    if ($lati && $longi) {

        // put the data in the array
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
