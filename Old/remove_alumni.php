<?php
require 'common.php';
require 'credentials.php';

/// Purpose : Go thru a list of volunteers in the given list, find out who all are NO longer volunteers and remove them from the Volunteer 2.0 List.
// NEVER USED. But working.

use \DrewM\MailChimp\MailChimp;
use \DrewM\MailChimp\Batch;
$mp = new MailChimp($mailchimp_api_key); 

$list_id = 'd7e6a0cde4'; //'193743'; // Volunteer 2.0

$list_subrcribers_csv_file = 'data/volunteers_2_0.csv'; // Is is the CSV exported from the MailChimp list we are trying to clean
$subscribers = [];
$row_count = 0;
if (($handle = fopen($list_subrcribers_csv_file, "r")) !== FALSE) {
  while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
    $row_count++;
    if($row_count == 1) continue; // Skip header.
    $subscribers[] = [
    	'email'	=> $row[0],
    	'name'	=> $row[1],
    	'user_id'=>$row[2],
    	'leid' 	=> $row[3],
    	'euid' 	=> $row[4]
    ];
  }
  fclose($handle);
}

$all_users = cacheQuery("SELECT id,name,email,mad_email FROM User WHERE user_type='volunteer' AND status='1'", "byid");
// dump($all_users);

// $batch = $mp->new_batch();
$unsubscribe_count = 0;
$active_count = 0;
foreach($subscribers as $index => $user) {
	if(!isset($all_users[$user['user_id']])) {
		$unsubscribe_count++;
		// print "Unsubcribe $user[name] : {$user['user_id']}\n";
		// $subscriber_hash = $mp->subscriberHash($user['email']);
		// $batch->delete("op_delete_{$user['user_id']}", "lists/{$list_id}/members/{$subscriber_hash}");
	} else {
		$active_count++;
		print "$user[name] is still a volunteer\n";
	}

	// if($index > 100) break;
}

dump($unsubscribe_count, $active_count);
// $result = $batch->execute();
// dump($result);