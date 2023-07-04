[![Latest Stable Version](https://poser.pugx.org/monext/payline-sdk/v/stable)](https://packagist.org/packages/monext/payline-sdk)
[![Total Downloads](https://poser.pugx.org/monext/payline-sdk/downloads)](https://packagist.org/packages/monext/payline-sdk)
[![License](https://poser.pugx.org/monext/payline-sdk/license)](https://packagist.org/packages/monext/payline-sdk)

PaylineSDK - Payline library for PHP
====================================

Installation
-----

Use composer to install the monext package
```shell
composer require monext/payline-sdk
````

To update the package
```shell
composer require monext/payline-sdk
````

In order to install a specific version x.xx you can execute
```shell
composer require monext/payline-sdk:x.xx
````

Usage
-----

See sample code [here](doc/usage.md)

Docs
-----

More information available on
- https://docs.payline.com/display/DT/PHP+SDK
- http://support.payline.com

Prerequisites
-----

Compliant with PHP 5.6, 7.0, 7.1, 7.2, 7.3, 7.4, 8.0, 8.1, 8.2

Requires monolog/monolog and symfony/cache, just let Composer do the job

In order to make http call for failover, ext-curl is mark has required, it can be commented if you disable failover or set allow_url_fopen to true in php.ini (in order to use file_get_contents)

Author
------

Payline support - <support@payline.com>

License
-------

Payline is licensed under the LGPL-3.0+ License - see the LICENSE file for details
