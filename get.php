<?php

  // if(isset($_GET['batch_id']))


  include('credentials.php');

  $dataCenter = substr($mailchimp_api_key,strpos($mailchimp_api_key,'-')+1);
  $url = 'https://' . $dataCenter . '.api.mailchimp.com/3.0/batches?count=100';

  include('./curl.php');

  use \Curl\Curl;

  $ch = new Curl();
  $ch->setOpt(CURLOPT_USERPWD, 'user:' . $mailchimp_api_key);
  $ch->setOpt(CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
  $ch->get($url);

  if ($ch->error) {
      echo 'Error: ' . $ch->errorCode . ': ' . $ch->errorMessage . "\n".$url;
  } else {
      print json_encode($ch->response);
  }

?>
