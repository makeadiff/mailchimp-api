<?php

function getUsers($sql,$contact_type='',$condition=array()) {

    if($contact_type=='donor'){ //Donor Details

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
    else if($contact_type=='online_donor'){ //Online Donations

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
    else if($contact_type=='alumni'){ //Alumni Data

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
    else if($contact_type=='credits'){ //Volunteers with Credits
      $this_year = get_year();

      if(!empty($condition)){
        $where = "AND User.id IN (".implode($condition).")";
      }
      else{
        $where = "";
      }

      $users =  $sql->getAll("SELECT
                                User.id as id, email, mad_email, User.credit as credits
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
          $users_ordered[$i]['merge_fields']['CREDITS'] = $user['credits'];
          $i++;
      }
      return $users_ordered;
    }
    else if($contact_type=='cfrparticipation'){ //Volunteer with CFR Participation
      $this_year = get_year();

      if(!empty($condition)){
        $where = "AND User.id IN (".implode($condition).")";
      }
      else{
        $where = "";
      }

      $users =  $sql->getAll("SELECT
                                User.id as id, email, mad_email, sum(D.amount) as amount
                              FROM User
                              INNER JOIN Donut_Donation D on D.fundraiser_user_id = User.id
                              INNER JOIN UserGroup UG on UG.user_id = User.id
                              WHERE user_type = 'volunteer'
                                AND User.status = 1
                                AND UG.year = ".$this_year." ".$where."
                                AND D.added_on >= '2017-08-01'
                              GROUP BY User.id
                              ORDER BY User.name
                               ");

      $users_ordered = array();
      $i = 0;
      foreach($users as $user) {
          if($user['mad_email']) $users_ordered[$i]['email_address'] = $user['mad_email'];
          else $users_ordered[$i]['email_address'] = $user['email'];
          if($user['amount']>0){
            $users_ordered[$i]['merge_fields']['CFR'] = "Yes";
          }
          $i++;
      }
      return $users_ordered;
    }
    else if($contact_type=='cframount'){ //Volunteer with CFR Participation
      $this_year = get_year();

      if(!empty($condition)){
        $where = "AND User.id IN (".implode($condition).")";
      }
      else{
        $where = "";
      }

      $users =  $sql->getAll("SELECT
                                User.id as id, email, mad_email, SUM(D.amount) as amount
                              FROM User
                              INNER JOIN Donut_Donation D on D.fundraiser_user_id = User.id
                              WHERE user_type = 'volunteer'
                                AND User.status = 1
                                AND D.added_on >= '2017-08-01'
                              GROUP BY User.id
                              ORDER BY User.name
                               ");

      $users_ordered = array();
      $i = 0;
      foreach($users as $user) {
          if($user['mad_email']) $users_ordered[$i]['email_address'] = $user['mad_email'];
          else $users_ordered[$i]['email_address'] = $user['email'];
          $users_ordered[$i]['merge_fields']['CFRAMOUNT'] = $user['amount'];
          $i++;
      }
      return $users_ordered;
    }
    else if($contact_type=='sheltersensitisation'){ //Volunteer with Shelter Sensitisation Attended
      $this_year = get_year();

      $users =  $sql->getAll("SELECT
                                User.id as id, email, mad_email, UD.value as ss
                              FROM User
                              INNER JOIN UserData UD on UD.user_id = User.id
                              WHERE user_type = 'volunteer'
                                AND User.status = 1
                                AND UD.name='shelter_sensitisation_2017'
                              GROUP BY User.id
                              ORDER BY User.email
                               ");

      // dump($users);
      // exit;
      $users_ordered = array();
      $i = 0;
      foreach($users as $user) {
          if($user['mad_email']) $users_ordered[$i]['email_address'] = $user['mad_email'];
          else $users_ordered[$i]['email_address'] = $user['email'];
          if($user['ss']==1){
            $users_ordered[$i]['merge_fields']['SS'] = 'Attended';
          }
          else{
            $users_ordered[$i]['merge_fields']['SS'] = 'Not Attended';
          }
          $i++;
      }
      return $users_ordered;
    }
    else if($contact_type=='citycircle'){ //Volunteer with Shelter Sensitisation Attended
      $this_year = get_year();

      $users =  $sql->getAll("SELECT
                                User.id as id, email, mad_email, MIN(UE.present) as present
                              FROM User
                              INNER JOIN UserEvent UE on UE.user_id = User.id
                              INNER JOIN Event E on E.id = UE.event_id
                              INNER JOIN Event_Type ET on ET.id = E.event_type_id
                              INNER JOIN UserGroup UG on UG.user_id = User.id
                              WHERE user_type = 'volunteer'
                                AND User.status = 1
                                AND UG.year = ".$this_year."
                                AND ET.id = 9
                                AND UE.present > 0
                              GROUP BY User.id
                              ORDER BY User.email
                               ");


      $users_ordered = array();
      $i = 0;
      foreach($users as $user) {
          if($user['mad_email']) $users_ordered[$i]['email_address'] = $user['mad_email'];
          else $users_ordered[$i]['email_address'] = $user['email'];
          $cc_query = 'SELECT value from UserData WHERE name="city_circle1_2017" AND user_id='.$user['id'];
          $cc1 = $sql->getOne($cc_query);
          if($cc1==''){$cc1=0;}
          $cc2 = 0;
          if($user['present']==1){$cc2 = 1;}
          else if($user['present']==3){$cc2 = 0;}

          $users_ordered[$i]['merge_fields']['CC'] = $cc1+$cc2;

          $i++;
      }
      return $users_ordered;
    }
    else if($contact_type=='tra_training'){ //Volunteer with Shelter Sensitisation Attended
      $this_year = get_year();

      $users =  $sql->getAll("SELECT
                                User.id as id, email, mad_email, UD.value as tra_training
                              FROM User
                              INNER JOIN UserData UD on UD.user_id = User.id
                              WHERE UD.name = 'tra_training_2017'
                              ORDER BY User.email
                               ");



      $users_ordered = array();
      $i = 0;
      foreach($users as $user) {
          if($user['mad_email']) $users_ordered[$i]['email_address'] = $user['mad_email'];
          else $users_ordered[$i]['email_address'] = $user['email'];

          $users_ordered[$i]['merge_fields']['TRATRAININ'] = $user['tra_training'];
          $i++;
      }
      return $users_ordered;
    }
    else if($contact_type=='ed_training'){ //Volunteer with Shelter Sensitisation Attended
      $this_year = get_year();

      $users =  $sql->getAll("SELECT
                                User.id as id, email, mad_email, UD.value as tra_training
                              FROM User
                              INNER JOIN UserData UD on UD.user_id = User.id
                              WHERE UD.name = 'ed_training_2017'
                              ORDER BY User.email
                               ");

      $users_ordered = array();
      $i = 0;
      foreach($users as $user) {
          if($user['mad_email']) $users_ordered[$i]['email_address'] = $user['mad_email'];
          else $users_ordered[$i]['email_address'] = $user['email'];

          $users_ordered[$i]['merge_fields']['EDTRAINING'] = $user['tra_training'];
          $i++;
      }
      return $users_ordered;
    }
    else if($contact_type=='fr_training'){ //Volunteer with Shelter Sensitisation Attended
      $this_year = get_year();

      $users =  $sql->getAll("SELECT
                                User.id as id, email, mad_email, UD.value as tra_training
                              FROM User
                              INNER JOIN UserData UD on UD.user_id = User.id
                              WHERE UD.name = 'fr_training_2017'
                              ORDER BY User.email
                               ");



      $users_ordered = array();
      $i = 0;
      foreach($users as $user) {
          if($user['mad_email']) $users_ordered[$i]['email_address'] = $user['mad_email'];
          else $users_ordered[$i]['email_address'] = $user['email'];

          $users_ordered[$i]['merge_fields']['FRTRAINING'] = $user['tra_training'];
          $i++;
      }
      return $users_ordered;
    }
    else if($contact_type=='childprotection'){ //Volunteer with Shelter Sensitisation Attended
      $this_year = get_year();

      $users =  $sql->getAll("SELECT
                                User.id as id, email, mad_email, UD.value as cpp
                              FROM User
                              INNER JOIN UserData UD on UD.user_id = User.id
                              WHERE UD.name = 'child_protection_policy_signed'
                              ORDER BY User.email
                               ");



      $users_ordered = array();
      $i = 0;
      foreach($users as $user) {
          if($user['mad_email']) $users_ordered[$i]['email_address'] = $user['mad_email'];
          else $users_ordered[$i]['email_address'] = $user['email'];
          if($user['cpp']){
            $users_ordered[$i]['merge_fields']['CPP'] = "Yes";
          }
          else{
            $users_ordered[$i]['merge_fields']['CPP'] = "No";
          }
          $i++;
      }
      return $users_ordered;
    }
    else if($contact_type=='user_credits'){ //Volunteer with Shelter Sensitisation Attended
      $this_year = get_year();

      $users =  $sql->getAll("SELECT
                                User.id as id, email, mad_email, UD.value as credits
                              FROM User
                              INNER JOIN UserData UD on UD.user_id = User.id
                              WHERE UD.name = 'user_credit_update'
                              ORDER BY User.email
                               ");



      $users_ordered = array();
      $i = 0;
      foreach($users as $user) {
          if($user['mad_email']) $users_ordered[$i]['email_address'] = $user['mad_email'];
          else $users_ordered[$i]['email_address'] = $user['email'];
          $users_ordered[$i]['merge_fields']['USERCREDIT'] = $user['credits'];
          $i++;
      }
      return $users_ordered;
    }
    else if($contact_type=='tra_participation_data'){ //Volunteer with Shelter Sensitisation Attended
      $this_year = get_year();

      $users =  $sql->getAll("SELECT
                                User.id as id, email, mad_email, UD.value as participation
                              FROM User
                              INNER JOIN UserData UD on UD.user_id = User.id
                              WHERE UD.name = 'tra_participation_data'
                              ORDER BY User.email
                               ");



      $users_ordered = array();
      $i = 0;
      foreach($users as $user) {
          if($user['mad_email']) $users_ordered[$i]['email_address'] = $user['mad_email'];
          else $users_ordered[$i]['email_address'] = $user['email'];
          $users_ordered[$i]['merge_fields']['TRASESSION'] = $user['participation'];
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
  if(!empty($delete_array)){
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
  }
  $new_users = array_diff($all_vol,$curr_vol);

  return $new_users;
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
}