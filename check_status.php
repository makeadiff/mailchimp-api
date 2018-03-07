<?php 
require 'iframe.php';
require "vendor/autoload.php";
require 'credentials.php';

/// Purpose: Check status of any batch operation on MailChimp

use \DrewM\MailChimp\MailChimp;
use \DrewM\MailChimp\Batch;
$mp = new MailChimp($mailchimp_api_key);

$batch_id = i($argv, 1);

if(!$batch_id) die("Usage: php check_status.php <batch_id>");

$batch = $mp->new_batch($batch_id);
$result = $batch->check_status();

dump($result);
