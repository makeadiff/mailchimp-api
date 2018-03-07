<?php
require 'iframe.php';
require "vendor/autoload.php";
require 'credentials.php';
require '/mnt/x/Data/www/MAD/apps/driller/models/Common.php';
/// Purpose: These people have signed the CPP - but not showing in the DB. So manually update them.

$signed = [
'ankit dhank',
'arpita singh',
'ashutosh',
'ayush agarwal',
'ayushi misra',
'chitra gupta',
'kshitij gautam',
'maulshree srivastava',
'meenal wadhwa',
'palak agarwal',
'pashmeen arora ',
'payal tripathi',
'preeti kumari',
'rahul roy',
'rajat gupta',
'ramesh pal',
'shreshtha jain',
'shubham gupta',
'subhasha priyali awasthi',
'sudheer kumar',
'sukrati mishra',
'vinay krishna ojha',
];

$lucknow_id = 20;
$year = 2017;

$model = new Common;
// $cpp_agreement_model = new CPP_Agreement;
$users = keyFormat($model->getUsers(['city_id' => 20]));

use \DrewM\MailChimp\MailChimp;
use \DrewM\MailChimp\Batch;
$mp = new MailChimp($mailchimp_api_key);
$batch = $mp->new_batch();

$found = 0;
foreach($users as $user_id => $user) {
	if(in_array(strtolower($user['name']), $signed)) {
		// echo $user_id . "\n";
		$found++;

		$subscriber_hash = $mp->subscriberHash($user['email']);

	    $batch->patch("op_" . $user_id, "lists/$volunteer/members/$subscriber_hash", [
	        'merge_fields' => ['CPP' => 'Yes']
	    ]);
		// $sql->insert("UserData", [
		// 	'user_id'	=> $user_id,
		// 	'name'		=> 'child_protection_policy_signed',
		// 	'value'		=> '1',
		// 	'data'		=> '2018-02-23 04:20:00'
		// ]);
		// print $sql->_query . "\n";

	}
}

$result = $batch->execute();

var_dump($result);

print "Found $found out of " . count($signed) . " - after going thru " . count($users) . " users in Lucknow($lucknow_id)\n";
