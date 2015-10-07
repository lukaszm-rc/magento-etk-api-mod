## Overview

This extension improves default features of Magento API.

It allows you to:

* Associate simple products to configurable or grouped product;
* Specify category names rather than the ids;
* Specify the name of the attribute set rather than the id;
* Specify options labels rather than the ids;
* Specify the website code rather than the id.

## Usage : 

Just add to Yours configurable product entity :
$product['associated_skus'] => ['sku_1', 'sku_2']; 

If attribute set has more configurable fields than You want, then You should add: 

$product['configurable_attributes'] = ['configurable_attribute_id', 'configurable_attribute_code'];

## Installation

### Magento 1.6 - 1.9.x

Install manually:

* Download latest version [here](https://github.com/jreinke/magento-improve-api/archive/master.zip)
* Unzip in Magento root folder
* Clear cache

## How to associate simple products to configurable/grouped product

Please refer to [this article](http://www.bubblecode.net/en/2012/04/20/magento-api-associate-simple-products-to-configurable-or-grouped-product/).