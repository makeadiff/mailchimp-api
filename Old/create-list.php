<?php

  require("../common.php");
  require("credentials.php");


  $contact_type = $_POST['contact_type'];
  $vertical_id = $_POST['vertical_id'];
  $city_id = $_POST['city_id'];


  $users = getCity($sql);

  $success = 0;
  $total = count($users);

  foreach ($users as $user) {

    $json = json_encode($user);

    $apiKey = $mailchimp_api_key;
    $listID = $volunteer;

    $memberID = md5(strtolower($email));
    $dataCenter = substr($apiKey,strpos($apiKey,'-')+1);
    $url = 'https://' . $dataCenter . '.api.mailchimp.com/3.0/lists/' . $listID . '/members';



    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_USERPWD, 'user:' . $apiKey);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200) {
      $success++;
    } else {
        switch ($httpCode) {
            case 214:
                break;
            default:
                // echo 'Error';
                break;
        }

    }
  }

  function getUsers($sql,$contact_type='',$city_id='',$vertical_id='') {

      $users =  $sql->getAll("SELECT
                                name, email, mad_email
                              FROM User
                              WHERE user_type = 'volunteer' AND status = 1 order by name ");

      $users_ordered = array();
      $i = 0;
      foreach($users as $user) {
          // $users_ordered[$i]['name'] = $user['name'];
          if($user['mad_email']) $users_ordered[$i]['email_address'] = $user['mad_email'];
          else $users_ordered[$i]['email_address'] = $user['email'];
          $users_ordered[$i]['status'] = 'subscribed';
          $users_ordered[$i]['merge_fields']['FNAME'] = $user['name'];
          $i++;
      }
      return $users_ordered;
  }

  function clearLists($sql,$list_id){

  }


  // redirect to homepage

 ?>
