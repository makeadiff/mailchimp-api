<?php

  if(isset($_GET['batch_id']))


  require('credentials.php');
  $apiKey = $mailchimp_api_key;

  $dataCenter = substr($apiKey,strpos($apiKey,'-')+1);
  $url = 'https://' . $dataCenter . '.api.mailchimp.com/3.0/batches?count=100';

  include('../curl.php');

  use \Curl\Curl;

  $ch = new Curl();
  $ch->setOpt(CURLOPT_USERPWD, 'user:' . $apiKey);
  $ch->setOpt(CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
  $ch->get($url);

  if ($ch->error) {
      echo 'Error: ' . $ch->errorCode . ': ' . $ch->errorMessage . "\n".$url;
  } else {
      var_dump($ch->response);
  }

?>
