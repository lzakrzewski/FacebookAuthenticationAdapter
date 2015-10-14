# FacebookAuthenticationAdapter
[![Build Status](https://travis-ci.org/Lucaszz/FacebookAuthenticationAdapter.svg)](https://travis-ci.org/Lucaszz/FacebookAuthenticationAdapter) [![Latest Stable Version](https://poser.pugx.org/lucaszz/facebook-authentication-adapter/v/stable)](https://packagist.org/packages/lucaszz/facebook-authentication-adapter) [![Total Downloads](https://poser.pugx.org/lucaszz/facebook-authentication-adapter/downloads)](https://packagist.org/packages/lucaszz/facebook-authentication-adapter) 

Adapter for communication with Facebook GRAPH API.

FacebookAuthenticationAdapter is simple library for communication with Facebook GRAPH API.
It returns access token and user node as array. [Read about facebook api access tokens](https://developers.facebook.com/docs/facebook-login/access-tokens/v2.5).

This library is independent part of [FacebookAuthenticationBundle](https://github.com/Lucaszz/FacebookAuthenticationBundle).

```php
<?php

namespace Lucaszz\FacebookAuthenticationAdapter\Adapter;

interface FacebookApi
{
    const GRAPH_API_ME_URL = 'https://graph.facebook.com/v2.5/me';
    const GRAPH_API_ACCESS_TOKEN_URL = 'https://graph.facebook.com/v2.5/oauth/access_token';

    /**
     * Returns access token during code exchange.
     *
     * @param $code
     *
     * @throws FacebookApiException
     *
     * @return string
     */
    public function accessToken($code);

    /**
     * Returns a single user node as array.
     *
     * @param string $accessToken
     * @param array  $fields
     *
     * @throws FacebookApiException
     *
     * @return array
     */
    public function me($accessToken, array $fields = array());
}
```

Requirements
------------
```json
  "require": {
    "php": ">=5.4",
    "guzzlehttp/guzzle": "~5.0"
  }
```

Installation
--------
Require the library with composer:

```sh
composer require lucaszz/facebook-authentication-adapter "~1.0"
```

Example
------------
```php
<?php

require 'vendor/autoload.php';

if (!isset($_GET['code'])) {
    header("Location: https://www.facebook.com/v2.5/dialog/oauth");
}

if (isset($_GET['code'])) {

    $client = new GuzzleHttp\Client();
    $adapter = new Lucaszz\FacebookAuthenticationAdapter\Adapter\GuzzleFacebookApi($client, 'http://my.host/login', 123123123123123, 'app-secret');

    $accessToken = $adapter->accessToken($_GET['code']);
    $userNode = $adapter->me($accessToken, array('first_name', 'last_name', 'gender', 'email', 'birthday', 'name'));

    //Your own logic to process facebook user node
}

```
