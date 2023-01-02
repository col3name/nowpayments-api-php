


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

$password = new NOWPaymentsApi("YOUR API");
echo $password->status();
```
