# SGF Web Devs Hackathon

Disclaimer: this code was written under duress and in no way should be reflection of how code should be written.

See here for more context: https://www.meetup.com/SGF-Web-Devs/events/260863460/

See also the [requirements.pdf](requirements.pdf) document for the requirements for this project.

To run the code in this repo:

```
# Clone repo
git clone git@github.com:erikhansen/sgfwebdevs-hackathon2019-05-01.git
cd sgfwebdevs-hackathon2019-05-01
composer install
php -f index.php
```

Example output:

```
Sending this data to server:
array(6) {
  'type' =>
  int(3)
  'reason' =>
  int(42)
  'description' =>
  string(86) "DreamTeam + Avoid South Belt and Pear due to a traffic incident involving loose cattle"
  'countyId' =>
  int(2097)
  'lat' =>
  double(39.7691377)
  'lng' =>
  double(-94.8538345)
}

Request from server:
{
    "data": {
        "latitude": 39.7691377,
        "longitude": -94.8538345,
        "alert_type": "3",
        "alert_sub_type": "",
        "alert_id": 179,
        "circle_radius": 500,
        "time_activation": "2019-05-02T11:43:14.000Z",
        "time_deactivation": "2019-05-02T13:43:14.000Z",
        "system_name": "Pia Society",
        "admin_name": "Talha ",
        "alert_description": "DreamTeam + Avoid South Belt and Pear due to a traffic incident involving loose cattle",
        "active": 1,
        "reason_description": "Timely Warning",
        "attachments": [],
        "update_attachments": []
    },
    "message": "Alert sent successfully",
    "error": false
}
…
```