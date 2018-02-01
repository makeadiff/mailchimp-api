<title>Mailchimp Integraion</title>

<?php

  require("../common.php");
  require("credentials.php");

  $apiKey = $mailchimp_api_key;


  if(isset($_GET['contact_type'])){
    $contact_type = $_GET['contact_type'];
  }
  else{
    $contact_type = 'volunteer';
  }
  // $vertical_id = $_GET['vertical_id'];
  // $city_id = $_GET['city_id'];


  if($contact_type=='volunteer'){
    $users = getUsers($sql);
    clearList($volunteer,$apiKey);
    populateList($volunteer,$users,$apiKey);
  }
  elseif($contact_type=='donor'){
    $donorsql = new Sql($config_data['db_host'], $config_data['db_user'], $config_data['db_password'], 'makeadiff_cfrapp');
    $users = getUsers($donorsql,'donor');
    populateList($donor,$users,$apiKey);
  }
  elseif($contact_type=='alumni'){
    $users = getUsers($sql,'alumni');
    populateList($alumni,$users,$apiKey);
  }

  // dump($users);

  $total = count($users);

  function populateList($listID,$users,$apiKey){
    $success = 0;
    foreach ($users as $user) {

      $json = json_encode($user);

      $memberID = md5(strtolower($user['email_address']));
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
                  break;
          }

      }
    }
    echo $success.' Uses have been added to '.$listID;
  }

  function getUsers($sql,$contact_type='',$city_id='',$vertical_id='') {

      if($contact_type=='donor'){

        $donors = $sql->getAll("SELECT
                                first_name,email_id
                               FROM Donours
                              ");


        $donors_ordered = array();
        $i = 0;
        foreach($donors as $donor) {
            $donors_ordered[$i]['email_address'] = $donor['email_id'];
            $donors_ordered[$i]['status'] = 'subscribed';
            $donors_ordered[$i]['merge_fields']['FNAME'] = $donor['first_name'];
            $i++;
        }
        return $donors_ordered;
      }
      else if($contact_type=='alumni'){

        $users =  $sql->getAll("SELECT
                                  User.name as name, email,C.name as City,left_on
                                FROM User
                                INNER JOIN City C on C.id=User.city_id
                                WHERE user_type = 'alumni'
                                ORDER BY User.name
                                 ");

        $users_ordered = array();
        $i = 0;
        foreach($users as $user) {
            $users_ordered[$i]['email_address'] = $user['email'];
            $users_ordered[$i]['status'] = 'subscribed';
            $users_ordered[$i]['merge_fields']['FNAME'] = $user['name'];
            $users_ordered[$i]['merge_fields']['CITY'] = $user['City'];
            $users_ordered[$i]['merge_fields']['LEFTON'] = $user['left_on'];
            $i++;
        }
        return $users_ordered;
      }
      else{
        $users =  $sql->getAll("SELECT
                                  User.name as name, email, mad_email,C.name as City, GROUP_CONCAT(G.name) as roles, GROUP_CONCAT(DISTINCT G.type) as type
                                FROM User
                                INNER JOIN City C on C.id=User.city_id
                                INNER JOIN UserGroup UG on UG.user_id = User.id
                                INNER JOIN `Group` G on G.id = UG.group_id
                                WHERE user_type = 'volunteer' AND User.status = 1
                                GROUP BY User.id
                                ORDER BY User.name
                                 ");

        $users_ordered = array();
        $i = 0;
        foreach($users as $user) {
            if($user['mad_email']) $users_ordered[$i]['email_address'] = $user['mad_email'];
            else $users_ordered[$i]['email_address'] = $user['email'];
            $users_ordered[$i]['status'] = 'subscribed';
            $users_ordered[$i]['merge_fields']['FNAME'] = $user['name'];
            $users_ordered[$i]['merge_fields']['CITY'] = $user['City'];
            $users_ordered[$i]['merge_fields']['UGROUP'] = $user['roles'];
            $users_ordered[$i]['merge_fields']['TYPE'] = $user['type'];
            $i++;
        }
        return $users_ordered;
      }
  }

  function clearList($listID,$apiKey){

    $dataCenter = substr($apiKey,strpos($apiKey,'-')+1);
    $url = 'https://' . $dataCenter . '.api.mailchimp.com/3.0/lists/'.$listID.'/members';

    $ch = curl_init($url.'?field=total_items');
    curl_setopt($ch, CURLOPT_USERPWD, 'user:' . $apiKey);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $result = json_decode($result);

    $count = $result->total_items;
    // echo $count;

    $offset = 0;
    $getcount = 100;

    while ($count > 0){

      $get_url = $url.'?offset='.$offset.'&count='.$getcount;
      $ch = curl_init($get_url);
      curl_setopt($ch, CURLOPT_USERPWD, 'user:' . $apiKey);
      curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_TIMEOUT, 10);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

      $result = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);
      $result = json_decode($result);

      // echo $httpCode;

      $users = $result->members;

      foreach ($users as $user) {
        $email = $user->email_address;
        $memberID = md5(strtolower($email));
        $delete_url = $url.'/'.$memberID;

        $ch = curl_init($delete_url);
        curl_setopt($ch, CURLOPT_USERPWD, 'user:' . $apiKey);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
      }

      $count -= $getcount;

    }

  }

  function get_year() { /* Function get_year(): Source: madapp/system/helper/misc_helper.php Line 123 */
			$this_month = intval(date('m'));
			$months = array();
			$start_month = 4; // May - Temporarily changed to August
			$start_year = date('Y');
			if($this_month < $start_month) $start_year = date('Y')-1;
			return $start_year;
	}


 ?>
