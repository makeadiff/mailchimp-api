<?php
require 'iframe.php';
require "vendor/autoload.php";
require 'credentials.php';

/// Purpose : Find who all have not yet signed the CPP - set it in the MailChimp account

use \DrewM\MailChimp\MailChimp;
use \DrewM\MailChimp\Batch;
$mp = new MailChimp($mailchimp_api_key);


$year = 2017;
require '/mnt/x/Data/www/MAD/apps/driller/models/Common.php';
require '/mnt/x/Data/www/MAD/apps/driller/models/CPP_Agreement.php';
$model = new Common;
$cpp_agreement_model = new CPP_Agreement;

$users = keyFormat($model->getUsers([]));
$aggreement_status = $cpp_agreement_model->getAgreementStatus(array_keys($users));
$unsigned = [];
foreach ($users as $id => $user) {
    if(isset($aggreement_status[$id])) continue;
    $unsigned[$id] = $user;
}

$batch = $mp->new_batch();
foreach ($unsigned as $user_id => $user) {
    $subscriber_hash = $mp->subscriberHash($user['email']);

    $batch->patch("op_" . $user_id, "lists/$volunteer/members/$subscriber_hash", [
        'merge_fields' => ['CPP' => 'No']
    ]);
}

$result = $batch->execute();

file_put_contents('Log.txt', var_export($result));