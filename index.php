
<title>Mailchimp Integraion</title>

<?php

  date_default_timezone_set('Asia/Kolkata');


  require('../common.php');
  include('../curl.php');
  require("credentials.php");
  include('includes/api_functions.php');
  include('includes/get_functions.php');



  //Include php-curl-class Library

  //---------- Sample Credentials.php File -----------------
  //
  // $mailchimp_email = "email@email.com";
  // $mailchimp_username = "username";
  // $mailchimp_api_key = "asdghjdfgredfgfd...";
  //
  // //List IDS
  //
  // $volunteer = "sdfd......";
  // $donor = "dfrfr.....";
  // $alumni = "refd......";
  // $online_donor = 'cedvdvb...';
  //

  $apiKey = $mailchimp_api_key;


  if(isset($_GET['contact_type'])){
    $contact_type = $_GET['contact_type'];
  }
  else{
    $contact_type = 'volunteer';
  }


  createtables($sql);


  if($contact_type=='volunteer'){
    $new = clearList($sql,$volunteer,$apiKey);
    // dump($new);
    // exit;
    if(!empty($new)){
      $users = getUsers($sql,$contact_type,$new);
    }
    populateList($volunteer,$users,$apiKey,$sql,$contact_type);
  }
  elseif($contact_type=='donor'){
    $donorsql = new Sql($config_data['db_host'], $config_data['db_user'], $config_data['db_password'], 'makeadiff_cfrapp');
    $users = getUsers($donorsql,$contact_type);
    populateList($donor,$users,$apiKey,$sql);
  }
  elseif($contact_type=='online_donor'){
    $donorsql = new Sql($config_data['db_host'], $config_data['db_user'], $config_data['db_password'], 'makeadiff_mad');
    $users = getUsers($donorsql,$contact_type);
    populateList($online_donor,$users,$apiKey,$sql);
  }
  elseif($contact_type=='failed_online_donor'){
    $donorsql = new Sql($config_data['db_host'], $config_data['db_user'], $config_data['db_password'], 'makeadiff_mad');
    $users = getUsers($donorsql,$contact_type);
    populateList($donor,$users,$apiKey,$sql);
  }
  elseif($contact_type=='alumni'){
    $users = getUsers($sql,'alumni');
    populateList($alumni,$users,$apiKey,$sql);
  }
  elseif ($contact_type=='credits') {
    $users = getUsers($sql,$contact_type);
    patch($volunteer,$users,$apiKey,$sql);
  }
  elseif ($contact_type=='cfrparticipation') {
    $users = getUsers($sql,$contact_type);
    patch($volunteer,$users,$apiKey,$sql);
  }
  elseif ($contact_type=='cframount') {
    $users = getUsers($sql,$contact_type);
    patch($volunteer,$users,$apiKey,$sql);
  }
  elseif ($contact_type=='citycircle') {
    $users = getUsers($sql,$contact_type);
    dump($users);
    exit;
    patch($volunteer,$users,$apiKey,$sql);
  }
  elseif ($contact_type=='sheltersensitisation') {
    $users = getUsers($sql,$contact_type);
    patch($volunteer,$users,$apiKey,$sql);
  }
  $total = count($users);






 ?>
