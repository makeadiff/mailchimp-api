# MailChimp API-PHP

## Purpose

MailChimp API is used to sync contacts for now.
* Get contact information from Database and update in the respective lists on Mailchimp

## Description/Usage

The structure of the call could be.
* /mailchimp-api/?contact_type="...." for syncing contacts

## Dependencies

* iFrame
* php-curl-class

create a file called curl.php in the parent directory which included the curl-class folder

Example
```sh
<?php
require 'php-curl-class/vendor/autoload.php';
 ?>
```

## Upcoming Features

* List - Create Tools.
