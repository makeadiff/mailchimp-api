<?php 
require 'iframe.php';
require "vendor/autoload.php";
require 'credentials.php';

use \DrewM\MailChimp\MailChimp;
use \DrewM\MailChimp\Batch;
$mp = new MailChimp($mailchimp_api_key);

$batch = $mp->new_batch('b66fe6543e');
$result = $batch->check_status();

dump($result);
