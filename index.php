
<title>Mailchimp Integraion</title>

<?php

  date_default_timezone_set('Asia/Kolkata');


  require('../common.php');
  require("credentials.php");
  include('../curl.php');

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


  use \Curl\Curl;
  use \Curl\MultiCurl;

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
    $users = getUsers($sql,$contact_type,$new);
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
  elseif ($contact_type=='list_status') {
    //
  }
  dump($users);
  $total = count($users);

  function populateList($listID,$users,$apiKey,$sql){ //parameter 1. List_id, 2. array of members, 3. apiKey, 4.sql Object for makeadiff_madapp, 5. Contact Type
    $dataCenter = substr($apiKey,strpos($apiKey,'-')+1);
    $batchoperations = array();
    $i=0;
    foreach ($users as $user) {
      $user = utf8ize($user); // dump(json_encode($user));  // echo json_last_error();
      $batchoperations['operations'][$i]['method']='POST';
      $batchoperations['operations'][$i]['path']='lists/' . $listID . '/members';
      $batchoperations['operations'][$i]['body']=json_encode($user);
      $i++;
    }

    $url = 'https://' . $dataCenter . '.api.mailchimp.com/3.0/batches';
    $batch_add = curl_post_data($apiKey,$url,$batchoperations);
    $count = count_members_in_list($listID,$apiKey);
    $list = $sql->getOne("SELECT id
                          FROM mailchimp_emaillist
                          WHERE mailchimp_list_id = '".$listID."'
                        ");

    if($list==''){
      $query = $sql->insert("mailchimp_emaillist",array(
        'list_name' => $contact_type,
        'mailchimp_list_id' => $listID,
        'total_user_count' => $count,
        'last_update_at' => date('Y-m-d H:i:s'),
        'created_at' => date('Y-m-d H:i:s'),
        'user_id' => '57184',
        'status' => 1,
      ));
    }
    else{
      $query = $sql->update("mailchimp_emaillist",array(
        'total_user_count' => $count,
        'last_update_at' => date('Y-m-d H:i:s'),
        'status' => 1,
      ),'id='.$list);
    }

  }

  function getUsers($sql,$contact_type='',$condition=array()) {

      if($contact_type=='donor'){

        $donors = $sql->getAll("SELECT
                                first_name,email_id,created_at
                               FROM Donours
                              ");

        $donors_ordered = array();
        $i = 0;
        foreach($donors as $donor) {
            $donors_ordered[$i] = array(
              'email_address' => $donor['email_id'],
              'status' => 'subscribed',
              'merge_fields' => array(
                'FNAME' => $donor['first_name'],
                'ADDEDON' => date('m/d/Y',strtotime($donor['created_at']))
              )
            );
            $i++;
        }
        return $donors_ordered;
      }
      else if($contact_type=='online_donor'){

        $donors = $sql->getAll("SELECT
                                name,email,donated_on
                               FROM mad_donation
                               WHERE status='success'
                              ");

        $donors_ordered = array();
        $i = 0;
        foreach($donors as $donor) {
            $donors_ordered[$i] = array(
              'email_address' => $donor['email'],
              'status' => 'subscribed',
              'merge_fields' => array(
                'FNAME' => $donor['name'],
                'DONATEDON' => date('m/d/Y',strtotime($donor['donated_on']))
              )
            );
            $i++;
        }
        return $donors_ordered;
      }
      else if($contact_type=='failed_online_donor'){

        $donors = $sql->getAll("SELECT
                                name,email,donated_on
                               FROM mad_donation
                               WHERE status='failed'
                              ");

        $donors_ordered = array();
        $i = 0;
        foreach($donors as $donor) {
            $donors_ordered[$i] = array(
              'email_address' => $donor['email'],
              'status' => 'subscribed',
              'merge_fields' => array(
                'FNAME' => $donor['first_name'],
                'DONATEDON' => date('m/d/Y',strtotime($donor['donated_on']))
              )
            );
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

        $this_year = get_year();

        if(!empty($condition)){
          $where = "AND User.id IN (".implode($condition).")";
        }
        else{
          $where = "";
        }

        $users =  $sql->getAll("SELECT
                                  User.id as id,User.name as name, email, mad_email,C.name as City, GROUP_CONCAT(G.name) as roles, GROUP_CONCAT(DISTINCT G.type) as type
                                FROM User
                                INNER JOIN City C on C.id=User.city_id
                                INNER JOIN UserGroup UG on UG.user_id = User.id
                                INNER JOIN `Group` G on G.id = UG.group_id
                                WHERE user_type = 'volunteer' AND User.status = 1 AND UG.year = ".$this_year." ".$where."
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

  function clearList($sql,$listID,$apiKey){

    $dataCenter = substr($apiKey,strpos($apiKey,'-')+1);
    $url = 'https://' . $dataCenter . '.api.mailchimp.com/3.0/lists/'.$listID.'/members';

    $current_volunteers = $sql->getAll('SELECT user_id as id FROM mailchimp_volunteers ORDER BY user_id');

    $curr_vol = array();
    foreach ($current_volunteers as $vol) {
      $curr_vol[] = $vol['id'];
    }

    $volunteers = $sql->getAll('SELECT id FROM User WHERE status=1 AND user_type="volunteer" ORDER BY id');

    $all_vol = array();
    foreach ($volunteers as $vol) {
      $all_vol[] = $vol['id'];
    }

    $delete_array = array_diff($curr_vol,$all_vol);
    $delete_users = $sql->getAll('SELECT email,mad_email FROM User WHERE id IN ('.implode(',',$delete_array).')');
    $delete = $sql->execQuery('DELETE FROM mailchimp_volunteers WHERE user_id IN ('.implode(',',$delete_array).')');
    $i=0;
    $batchoperations = array();
    foreach ($delete_users as $element) {
      if($element['mad_email']!=''){
        $email = $element['email'];
      }
      else{
        $email = $element['mad_email'];
      }
      $memberID = md5(strtolower($email));
      $batchoperations['operations'][$i]['method']='DELETE';
      $batchoperations['operations'][$i]['path']='lists/' . $listID . '/members/'.$memberID;
      $i++;
    }

    $url = 'https://' . $dataCenter . '.api.mailchimp.com/3.0/batches';
    $batch_delete = curl_post_data($apiKey,$url,$batchoperations);
    $new_users = array_diff($all_vol,$curr_vol);

    return $new_users;
  }

  function count_members_in_list($listID,$apiKey){ //Function to get the number of members in the list identified by the List ID
    $dataCenter = substr($apiKey,strpos($apiKey,'-')+1);
    $url = 'https://' . $dataCenter . '.api.mailchimp.com/3.0/lists/'.$listID.'/members?field=total_items';
    $data = curl_get_data($apiKey,$url);
    $count = $data->total_items;
    return $count;
  }

  function get_year() { /* Function get_year(): */
			$this_month = intval(date('m'));
			$months = array();
			$start_month = 4; // April
			$start_year = date('Y');
			if($this_month < $start_month) $start_year = date('Y')-1;
			return $start_year;
	}

  function curl_get_data($apiKey,$url){
    $ch = new Curl();
    $ch->setOpt(CURLOPT_USERPWD, 'user:' . $apiKey);
    $ch->setOpt(CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $ch->get($url);

    if ($ch->error) {
        echo 'Get Error: ' . $ch->errorCode . ': ' . $ch->errorMessage . "\n".$url;
    } else {
        return $ch->response;
    }
  }

  function curl_post_data($apiKey,$url,$data){
    $ch = new Curl();
    $ch->setOpt(CURLOPT_USERPWD, 'user:' . $apiKey);
    $ch->setHeader('Content-Type', 'application/json');
    $ch->post($url,json_encode($data));

    if ($ch->error) {
        echo 'Post Error: ' . $ch->errorCode . ': ' . $ch->errorMessage . "<br/>";
        var_dump($ch->response);
    } else {
        echo 'Data server received via POST:'. "\n";
        var_dump($ch->response);
    }
  }

  function curl_delete_data($apiKey,$url){
    $ch = new Curl();
    $ch->delete($url);
    if ($ch->error) {
        echo 'Delete Error: ' . $ch->errorCode . ': ' . $ch->errorMessage . "<br/>";
    } else {
        echo 'Data server received via DELETE:'."<br/>";
    }
  }

  function utf8ize($mixed) {
      if (is_array($mixed)) {
          foreach ($mixed as $key => $value) {
              $mixed[$key] = utf8ize($value);
          }
      } else if (is_string ($mixed)) {
          return utf8_encode($mixed);
      }
      return $mixed;
  }

  function createtables($sql){

                    // MailChimp Active Volunteer List
    $mc_vol = $sql->execQuery('CREATE TABLE IF NOT EXISTS `mailchimp_volunteers` (
                    	`id` INT (11)  unsigned NOT NULL auto_increment,
                    	`user_id` INT (11)  unsigned NOT NULL,
                    	PRIMARY KEY (`id`),
                    	KEY (`user_id`)
                    ) DEFAULT CHARSET=utf8');


                    // Mailchimp Email List
    $mc_el = $sql->execQuery("CREATE TABLE IF NOT EXISTS `mailchimp_emaillist` (
                    	`id` INT (11)  unsigned NOT NULL auto_increment,
                    	`list_name` VARCHAR (100)   NOT NULL,
                    	`mailchimp_list_id` INT (11)  unsigned NOT NULL,
                    	`total_user_count` VARCHAR (100)   NOT NULL,
                    	`last_udated_at` DATETIME    NOT NULL,
                    	`created_at` DATETIME    NOT NULL,
                    	`user_id` INT (11)  unsigned NOT NULL,
                    	`status` ENUM ('0','1') DEFAULT '1',
                    	PRIMARY KEY (`id`),
                    	KEY (`mailchimp_list_id`),
                    	KEY (`user_id`)
                    ) DEFAULT CHARSET=utf8");

    // $volunteers = $sql->getAll('SELECT id as user_id FROM User WHERE status=1 AND user_type="volunteer"');
    //
    // foreach ($volunteers as $volunteer) {
    //   $insert = $sql->insert('mailchimp_volunteers',$volunteer);
    // }
  }



 ?>
