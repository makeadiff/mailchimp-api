
<title>Mailchimp Integraion</title>

<?php

  date_default_timezone_set('Asia/Kolkata');


  if(isset($_GET['contact_type'])){
    $contact_type = $_GET['contact_type'];
  }
  else{
    $contact_type = 'volunteer';
  }


  require('common.php');
  include('curl.php');
  require("./credentials.php");
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
  
  $GLOBALS['apiKey'] = $apiKey;

  createtables($sql);


  switch ($contact_type) {
    case 'volunteer':
      $new = array();
      $users = getUsers($sql,$contact_type,$new);
      dump($users);
      populateList($volunteer,$users,$apiKey,$sql,$contact_type);
      break;    
    case 'donor':
      $donorsql = new Sql($config_data['db_host'], $config_data['db_user'], $config_data['db_password'], 'makeadiff_madapp');
      $users = getUsers($donorsql,$contact_type);
      dump($users);
      // populateList($donor,$users,$apiKey,$sql);
      break;
    case 'online_donor':
      $donorsql = new Sql($config_data['db_host'], $config_data['db_user'], $config_data['db_password'], 'makeadiff_mad');
      $users = getUsers($donorsql,$contact_type);
      populateList($online_donor,$users,$apiKey,$sql);
      break;
    case 'failed_online_donor':
      $donorsql = new Sql($config_data['db_host'], $config_data['db_user'], $config_data['db_password'], 'makeadiff_mad');
      $users = getUsers($donorsql,$contact_type);
      populateList($donor,$users,$apiKey,$sql);
      break;
    case 'alumni':
      $users = getUsers($sql,'alumni');
      populateList($alumni,$users,$apiKey,$sql);
      break;        
    case 'vertical_training':
      $users = getUsers($sql,$contact_type);
      patch($volunteer,$users,$apiKey,$sql);
      break;
    case 'sheltersensitisation1':
      $users = getUsers($sql,$contact_type);
      patch($volunteer,$users,$apiKey,$sql);
      break;
    case 'sheltersensitisation2':
      $users = getUsers($sql,$contact_type);
      patch($volunteer,$users,$apiKey,$sql);
      break;        
    case 'volunteer_type':
      $users = getUsers($sql,$contact_type);
      patch($volunteer,$users,$apiKey,$sql);
      break;
    case 'delete_old':
      $users = getUsers($sql,$contact_type);      
      delete($volunteer,$users,$apiKey,$sql);
      break;    
    // case 'fellows_strats':
    //   $users = getUsers($sql,$contact_type);
    //   populateList($fellows_strats,$users,$apiKey,$sql,$contact_type);
    //   break;
    // case 'vol_years':
    //   $users = getUsers($sql,$contact_type);
    //   patch($volunteer,$users,$apiKey,$sql);
    //   break;  
    default:
      // $new = clearList($sql,$volunteer,$apiKey);
      // if(!empty($new)) $users = getUsers($sql,$contact_type,$new);
      // populateList($volunteer,$users,$apiKey,$sql,$contact_type);
      break;
  }

  // dump($users);

 ?>
