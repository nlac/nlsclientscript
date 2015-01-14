nlsclientscript 7.0.0-beta5
===========================

a Yii 1.x CClientScript extension to prevent reloading javascript libraries and merging/minifying resources

What's new compared to 6.x
--------------------------
* huge refactor
* one-file code splitted into classes
* completely new css processing part: processes @import-s, url-s
* composer support
* demo app
* moved to github:)
* available on packagist.org
* tests (initial)

How to install with Composer
--------------------------
Composer require section: "nlac/nlsclientscript": "dev-master"

Tests
-----
Some important unit tests written, using *Codeception*. Code coverage will be increased later. 

**How to run the tests?**

1. Codeception framework needs to be installed globally
 * composer global require "codeception/codeception=2.0.*"
 * composer global require "codeception/specify=*"
 * composer global require "codeception/verify=*"
 * composer global require "codeception/aspect-mock=*"
2. make sure that %APPDATA%\Composer\vendor\bin is added to the PATH variable (to have codecept command)
3. go to the project root
4. composer install
5. codecept run unit --debug

Resources
---------

Official demo, tutorial: http://nlacsoft.net/nlsclientscript

Packagist home: https://packagist.org/packages/nlac/nlsclientscript

Old Yii page: http://www.yiiframework.com/extension/nlsclientscript