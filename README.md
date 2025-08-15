
[Available here](https://packagist.org/packages/bearname/nowpayments-api-php)

## Installation
This project using composer.
```
$ composer require bearname/nowpayments-api-php 
```

Compatible with php8 and ignore platform requirements
```
$ composer require bearname/nowpayments-api-php --ignore-platform-reqs
```

## Usage
Genrate random password.
```php
<?php

require ("vendor/autoload.php");

use NowPaymentsIO\NOWPaymentsApi;

$apiClient = new NOWPaymentsApi("YOUR API KEY");
echo $apiClient->status();
```


## Use with token
### 1. get token by username and password

```php
require ("vendor/autoload.php");

use NowPaymentsIO\NOWPaymentsApi;

$apiClient = new NOWPaymentsApi("YOUR API KEY");
echo $apiClient->auth('username', 'password');

```

### 2. Direct init with token 
```php
require ("vendor/autoload.php");

use NowPaymentsIO\NOWPaymentsApi;

$apiClient = new NOWPaymentsApi('apiKey', 'token');

``` 

## Direct call specific method
```php
require ("vendor/autoload.php");

use NowPaymentsIO\NOWPaymentsApi;

$apiClient = new NOWPaymentsApi('apiKey', 'token');
$params = [
    id => 111,
    offset => 0,
    limit => 10,
    order => 'DESC'
];

$apiClient.callGet('/sub-partner', $params);

``` 


```php
require ("vendor/autoload.php");

use NowPaymentsIO\NOWPaymentsApi;

$apiClient = new NOWPaymentsApi('apiKey', 'token');
$data = [
    id => 111,
    offset => 0,
    limit => 10,
    order => 'DESC'
];

$apiClient.callPost('/sub-partner/transfer', $data);

```