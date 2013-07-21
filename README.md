Recording Extractor
===================

This project is meant to be a class (and potentially an OpenVBX plugin) 
that makes it easy to automatically extract Recordings out of Twilio or Plivo
and ship them off to Amazon S3 buckets for permanent storage

Example
-------

```php

error_reporting(E_ALL ^ E_WARNING);
require("vendor/autoload.php");

require("Extractor.php");


$e = new Extractor(
    "<amazon key>",
    "<amazon secret>",
    "<twilio account sid>",
    "<twilio auth token>",
    "<s3 bucket name>"
);

$e->connect();

$urls = $e->extractAndRelocate(
    "<recording id>"
);

var_dump($urls);
```

will output something like this:

```
array(2) {
  ["mp3"]=>
  string(116) "https://s3.amazonaws.com/<bucket>/<twilio account sid>/<recording id>.mp3"
  ["wav"]=>
  string(116) "https://s3.amazonaws.com/<bucket>/<twilio account sid>/<recording id>.wav"
}
```