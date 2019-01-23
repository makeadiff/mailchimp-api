<?php

use \Curl\Curl;
use \Curl\MultiCurl;

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

function patch($listID,$users,$apiKey,$sql){ //parameter 1. List_id, 2. array of members, 3. apiKey, 4.sql Object for makeadiff_madapp, 5. Contact Type
  $dataCenter = substr($apiKey,strpos($apiKey,'-')+1);
  $batchoperations = array();
  $i=0;
  foreach ($users as $user) {
    $user = utf8ize($user); // dump(json_encode($user));  // echo json_last_error();
    $batchoperations['operations'][$i]['method']='PATCH';
    $batchoperations['operations'][$i]['path']='lists/' . $listID . '/members/'.md5(strtolower($user['email_address']));
    $batchoperations['operations'][$i]['body']=json_encode($user);
    $i++;
  }

  $url = 'https://' . $dataCenter . '.api.mailchimp.com/3.0/batches';
  $batch_add = curl_post_data($apiKey,$url,$batchoperations);

}

function delete($listID,$users,$apiKey,$sql){ //parameter 1. List_id, 2. array of members, 3. apiKey, 4.sql Object for makeadiff_madapp, 5. Contact Type
  $dataCenter = substr($apiKey,strpos($apiKey,'-')+1);
  $batchoperations = array();
  $i=0;
  foreach ($users as $user) {
    $user = utf8ize($user); // dump(json_encode($user));  // echo json_last_error();
    $batchoperations['operations'][$i]['method']='DELETE';
    $batchoperations['operations'][$i]['path']='lists/' . $listID . '/members/'.md5(strtolower($user['email_address']));
    $i++;
  }

  $url = 'https://' . $dataCenter . '.api.mailchimp.com/3.0/batches';
  $batch_add = curl_post_data($apiKey,$url,$batchoperations);
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
