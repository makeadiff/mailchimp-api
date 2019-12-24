<?php

function getUsers($sql,$contact_type='',$condition=array()) {

    if($contact_type=='donor'){ //Donor Details

      $donors = $sql->getAll("SELECT
                              DD.name as name ,DD.email as email,DD.added_on as added_on, GROUP_CONCAT(DISTINCT DON.type) as type
                             FROM Donut_Donor DD
                             LEFT JOIN Donut_Donation DON ON DON.donor_id = DD.id
                             GROUP BY DD.id
                            ");

      $donors_ordered = array();
      $i = 0;
      foreach($donors as $donor) {
          $donors_ordered[$i] = array(
            'email_address' => $donor['email'],
            'status' => 'subscribed',
            'merge_fields' => array(
              'FNAME' => $donor['name'],
              'ADDEDON' => date('m/d/Y',strtotime($donor['added_on'])),
              'TYPE' => $donor['type']
            )
          );
          $i++;
      }
      return $donors_ordered;
    }    
    else if($contact_type=='volunteer'){
      $this_year = get_year();

      $where = '';      
      
 
      $users =  $sql->getAll("SELECT
                                User.id as id,
                                User.name as name, 
                                User.email as email, 
                                User.mad_email as mad_email,
                                C.name as City, 
                                GROUP_CONCAT(G.name) as roles, 
                                GROUP_CONCAT(DISTINCT G.type) as type,
                                CFR.amount as amount,
                                CC1.present as cc1,
                                CC2.present as cc2,
                                CPP.cpp as cpp
                              FROM User
                              INNER JOIN City C on C.id=User.city_id
                              INNER JOIN UserGroup UG on UG.user_id = User.id
                              INNER JOIN `Group` G on G.id = UG.group_id
                              LEFT JOIN (
                                SELECT fundraiser_user_id, Sum(D.amount) as amount
                                FROM Donut_Donation D 
                                WHERE D.added_on >= '".$this_year."-06-01'
                                GROUP BY D.fundraiser_user_id
                              )CFR on CFR.fundraiser_user_id = User.id  
                              LEFT JOIN (
                                SELECT
                                UE.user_id as user_id, MIN(UE.present) as present
                                FROM UserEvent UE
                                INNER JOIN Event E on E.id = UE.event_id
                                INNER JOIN Event_Type ET on ET.id = E.event_type_id
                                WHERE ET.id = '8'
                                  AND UE.present > '0'
                                  AND E.status = '1'
                                  AND E.starts_on > '".$this_year."-06-01'
                                GROUP BY UE.user_id
                              )CC1 ON CC1.user_id = User.id 
                              LEFT JOIN (
                                SELECT
                                UE.user_id as user_id, MIN(UE.present) as present
                                FROM UserEvent UE
                                INNER JOIN Event E on E.id = UE.event_id
                                INNER JOIN Event_Type ET on ET.id = E.event_type_id
                                WHERE ET.id = '9'
                                  AND UE.present > '0'
                                  AND E.status = '1'
                                  AND E.starts_on > '".$this_year."-06-01'
                                GROUP BY UE.user_id
                              )CC2 ON CC2.user_id = User.id  
                              LEFT JOIN (
                                SELECT UD.value as cpp, UD.user_id as user_id
                                FROM UserData UD 
                                WHERE UD.name = 'child_protection_policy_signed'
                              )CPP ON CPP.user_id = User.id                       
                              WHERE 
                                user_type = 'volunteer' 
                                AND User.status = 1 
                                AND UG.year = ".$this_year."
                                AND C.id <=26
                              GROUP BY User.id
                              ORDER BY User.name
                               ");

      $users_ordered = array();
      $i = 0;
      foreach($users as $user) {
          $cc2 = '';  
          if($user['cc2']==1)
            $cc2 = 'present';
          else if($user['cc2']==3)
            $cc2 = 'absent'; 
          else        
            $cc2 = ''; 
            
          $cc1 = '';  
          if($user['cc1']==1)
            $cc1 = 'present';
          else if($user['cc1']==3)
            $cc1 = 'absent'; 
          else        
            $cc1 = ''; 

          $q = "SELECT V.name
                FROM UserGroup UG
                INNER JOIN `Group` G ON G.id = UG.group_id
                INNER JOIN Vertical V ON V.id = G.vertical_id
                WHERE G.name <> 'Strat'
                AND G.group_type = 'normal'
                AND UG.user_id = ".$user['id']."
                ORDER BY FIELD(G.type,'executive','national','strat','fellow','volunteer') ASC";
          
          if($user['mad_email']) $users_ordered[$i]['email_address'] = $user['mad_email'];
          else $users_ordered[$i]['email_address'] = $user['email'];
          $users_ordered[$i]['status'] = 'subscribed';
          $users_ordered[$i]['merge_fields']['MADAPPID'] = $user['id'];
          $users_ordered[$i]['merge_fields']['FNAME'] = $user['name'];
          $users_ordered[$i]['merge_fields']['CITY'] = $user['City'];
          $users_ordered[$i]['merge_fields']['UGROUP'] = $user['roles'];
          $users_ordered[$i]['merge_fields']['TYPE'] = $user['type'];
          $users_ordered[$i]['merge_fields']['CFRAMOUNT'] = $user['amount'];                
          $users_ordered[$i]['merge_fields']['CC1'] = $cc1;
          $users_ordered[$i]['merge_fields']['CC2'] = $cc2;    
          $users_ordered[$i]['merge_fields']['PRI_VERT'] = $sql->getOne($q);                
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
    else if($contact_type=='sheltersensitisation1'){ //Volunteer with Shelter Sensitisation Attended
      $this_year = get_year();

      $users =  $sql->getAll("SELECT
                                User.id as id, email, mad_email, MIN(UE.present) as present, User.joined_on as joined_on
                              FROM User
                              INNER JOIN UserEvent UE on UE.user_id = User.id
                              INNER JOIN Event E on E.id = UE.event_id
                              INNER JOIN Event_Type ET on ET.id = E.event_type_id
                              WHERE User.user_type = 'volunteer'
                                AND User.status = 1
                                AND ET.id = '1'
                                AND UE.present > '0'
                                AND E.status = '1'
                                AND E.starts_on > '".$this_year."-06-01'
                              GROUP BY User.id
                              ORDER BY User.email
                               ");

      $users_ordered = array();
      $i = 0;
      foreach($users as $user) {
          $ss = '';
          if($user['mad_email']) $users_ordered[$i]['email_address'] = $user['mad_email'];
          else $users_ordered[$i]['email_address'] = $user['email'];
          if($user['present']==1){
            $ss = 'present';
          }
          else if($user['present']==3){
            $ss = 'absent';
          }

          if($user['joined_on']<=$this_year.'-03-01'){
            $ss = 'present';
          }

          $users_ordered[$i]['merge_fields']['SS1'] = $ss;

          $i++;
      }
      return $users_ordered;
    }
    else if($contact_type=='sheltersensitisation2'){ //Volunteer with Shelter Sensitisation Attended
      $this_year = get_year();

      $users =  $sql->getAll("SELECT
                                User.id as id, email, mad_email, MIN(UE.present) as present
                              FROM User
                              INNER JOIN UserEvent UE on UE.user_id = User.id
                              INNER JOIN Event E on E.id = UE.event_id
                              INNER JOIN Event_Type ET on ET.id = E.event_type_id
                              WHERE User.user_type = 'volunteer'
                                AND User.status = 1
                                AND ET.id = '35'
                                AND UE.present > '0'
                                AND E.status = '1'
                                AND E.starts_on > '".$this_year."-06-01'
                              GROUP BY User.id
                              ORDER BY User.email
                               ");

      $users_ordered = array();
      $i = 0;
      foreach($users as $user) {
          $cc = '';
          if($user['mad_email']) $users_ordered[$i]['email_address'] = $user['mad_email'];
          else $users_ordered[$i]['email_address'] = $user['email'];
          if($user['present']==1){$cc = 'present';}
          else if($user['present']==3){$cc = 'absent';}

          $users_ordered[$i]['merge_fields']['SS2'] = $cc;

          $i++;
      }
      return $users_ordered;
    }     
    else if($contact_type=='vertical_training'){ //Volunteer with Shelter Sensitisation Attended
      $this_year = get_year();

      $users =  $sql->getAll("SELECT
                                User.id as id, email, mad_email, MIN(UE.present) as present,
                                CASE E.event_type_id
                              		WHEN '17' THEN 'Ed Support'
                              		WHEN '21' THEN 'Transition Readiness'
                              		WHEN '29' THEN 'Foundational Programme'
                              		WHEN '30' THEN 'Aftercare'
                              		WHEN '31' THEN 'Fundraising'
                              	END as 'Vertical'
                              FROM User
                              INNER JOIN UserEvent UE on UE.user_id = User.id
                              INNER JOIN Event E on E.id = UE.event_id
                              INNER JOIN Event_Type ET on ET.id = E.event_type_id
                              WHERE User.user_type = 'volunteer'
                                AND User.status = 1
                                AND ET.id IN ('17','21','29','30','31')
                                AND UE.present > '0'
                                AND E.status = '1'
                              GROUP BY User.id
                              ORDER BY User.email
                               ");

      $users_ordered = array();
      $i = 0;
      foreach($users as $user) {
          $vt = '';
          if($user['mad_email']) $users_ordered[$i]['email_address'] = $user['mad_email'];
          else $users_ordered[$i]['email_address'] = $user['email'];
          if($user['present']==1){$vt = 'present';}
          else if($user['present']==3){$vt = 'absent';}
          $users_ordered[$i]['merge_fields']['VT'] = $vt;
          $users_ordered[$i]['merge_fields']['VERT_TRAIN'] = $user['Vertical'];
          $i++;
      }
      return $users_ordered;
    }       
    else if($contact_type=='fellowship_applicants'){
      $users =  $sql->getAll("SELECT
                                User.id as id, email, mad_email,G.name as first_pref
                              FROM User
                              INNER JOIN FAM_UserGroupPreference UF on UF.user_id = User.id
                              INNER JOIN `Group` G on G.id = UF.group_id
                              WHERE UF.preference = 1
                              GROUP BY User.id
                              ORDER BY User.email

                               ");
      $users_ordered = array();
      $i = 0;
      foreach($users as $user) {
          if($user['mad_email']) $users_ordered[$i]['email_address'] = $user['mad_email'];
          else $users_ordered[$i]['email_address'] = $user['email'];
            $users_ordered[$i]['merge_fields']['FIRST_PREF'] = $user['first_pref'];
          $i++;
      }
      return $users_ordered;
    }    
    else if($contact_type=='fellows_strats'){
      $this_year = get_year();

      $users =  $sql->getAll("SELECT
                                U.id as id,U.name as name, U.email as email, U.mad_email as mad_email, G.name as role
                              FROM User U
                              INNER JOIN UserGroup UG ON UG.user_id = U.id
                              INNER JOIN `Group` G ON G.id = UG.group_id
                              WHERE (G.type = 'fellow' OR G.type='strat')
                              AND U.user_type = 'volunteer'
                              AND G.name <> 'Strat'
                              AND G.id <> 382
                              AND U.status = 1
                              AND UG.year = 2018
                              GROUP BY U.id
                               ");


      $users_ordered = array();
      $i = 0;
      foreach($users as $user) {
          if($user['mad_email']) $users_ordered[$i]['email_address'] = $user['mad_email'];
          else $users_ordered[$i]['email_address'] = $user['email'];
          $users_ordered[$i]['status'] = 'subscribed';
          $users_ordered[$i]['merge_fields']['ROLE'] = $user['role'];
          $users_ordered[$i]['merge_fields']['NAME'] = $user['name'];
          $i++;
      }
      return $users_ordered;
    }        
    else if($contact_type=='volunteer_type'){
      $this_year = get_year();

      // To Check if the Volunteer is continuing or not
      $users =  $sql->getAll("SELECT
                                U.id as id,U.name as name, U.email as email, U.mad_email as mad_email,U.joined_on as joined_on
                              FROM User U
                              WHERE U.user_type = 'volunteer'
                              AND U.status = 1
                              GROUP BY U.id
                               ");      
      $users_ordered = array();
      $i = 0;
      // dump(date('Y-m-d H:i:s',strtotime($this_year.'-04-01')));
      foreach($users as $user) {
          if($user['mad_email']) $users_ordered[$i]['email_address'] = $user['mad_email'];
          else $users_ordered[$i]['email_address'] = $user['email'];
          if(strtotime($user['joined_on'])<=strtotime($this_year.'-04-01')){
            $users_ordered[$i]['merge_fields']['CONTINUE'] = 'Yes';
          }else{
            $users_ordered[$i]['merge_fields']['CONTINUE'] = 'No';
          }

          $i++;
      }
      return $users_ordered;
    }
    else if($contact_type=='primary_vertical'){
      $this_year = get_year();
      // Update the PRIMARY Vertical of a Volunteer
      $users =  $sql->getAll("SELECT
                                U.id as id,U.name as name, U.email as email, U.mad_email as mad_email
                              FROM User U
                              WHERE U.user_type = 'volunteer'
                              AND U.status = 1
                               ");
      $users_ordered = array();
      $i = 0;
      // dump(date('Y-m-d H:i:s',strtotime($this_year.'-04-01')));
      foreach($users as $user) {
          if($user['mad_email']) $users_ordered[$i]['email_address'] = $user['mad_email'];
          else $users_ordered[$i]['email_address'] = $user['email'];
          $q = "SELECT V.name
                FROM UserGroup UG
                INNER JOIN `Group` G ON G.id = UG.group_id
                INNER JOIN Vertical V ON V.id = G.vertical_id
                WHERE G.name <> 'Strat'
                AND G.group_type = 'normal'
                AND UG.user_id = ".$user['id']."
                ORDER BY FIELD(G.type,'executive','national','strat','fellow','volunteer') ASC";

          $users_ordered[$i]['merge_fields']['PRI_VERT'] = $sql->getOne($q);
          $i++;
      }
      return $users_ordered;
    }
    else if($contact_type=='delete_old'){
      $this_year = get_year();      
      $users =  $sql->getAll("SELECT
                                U.id as id,U.name as name, U.email as email, U.mad_email as mad_email
                              FROM User U
                              WHERE (U.user_type = 'alumni' OR U.user_type = 'let_go')                                                          
                               ");
      $users_ordered = array();
      $i = 0;
      foreach($users as $user) {
          if($user['mad_email']) $users_ordered[$i]['email_address'] = $user['mad_email'];
          else $users_ordered[$i]['email_address'] = $user['email'];
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
  foreach ($new_users as $new) {
    $sql->insert('mailchimp_volunteers',array(
      'user_id' => $vol['id']
    ));
  }
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
