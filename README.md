# Extends Facebook PHP SDK to return file size of remote files

Currently the [Facebook SDK for PHP](https://github.com/facebookarchive/php-graph-sdk) does not return the file size of remote files for which remote files cannot be uploaded. This is a bug and though there is a pull request for it but it has not been merged yet. This package extends the Facebook SDK to return the file size and upload remote files.

## Install

You can install this package via composer.

``` bash
composer require muvi/facebook
```

## Usage
Instead of using Facebook\Facebook just use
```php
use FacebookExtended\Facebook;
...
$fbConfig = [
    'app_id' => 'YOUR-FACEBOOK-APP-ID',
    'app_secret' => 'YOUR-FACEBOOK-SECRET-KEY',
    'default_graph_version' => 'v6.0',
];

$fb = new Facebook($fbConfig);
```
This will handle all types of files i.e. local, remote and also stream wrappers.