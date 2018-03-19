<?php
require 'iframe.php';
require "vendor/autoload.php";
require 'credentials.php';

/// Purpose : Find who all HAVE SIGNED the CPP - set it in the MailChimp account

use \DrewM\MailChimp\MailChimp;
use \DrewM\MailChimp\Batch;
$mp = new MailChimp($mailchimp_api_key);


$year = 2017;
require '../../driller/models/Common.php';
require '../../driller/models/CPP_Agreement.php';
$model = new Common;
$cpp_agreement_model = new CPP_Agreement;

$users = keyFormat($model->getUsers([]));
$aggreement_status = $cpp_agreement_model->getAgreementStatus(array_keys($users));
$signed = [];
foreach ($users as $id => $user) {
    if(isset($aggreement_status[$id])) $signed[$id] = $user;
}

$batch = $mp->new_batch();
foreach ($signed as $user_id => $user) {
    $subscriber_hash = $mp->subscriberHash($user['email']);

    $batch->patch("op_" . $user_id, "lists/$volunteer/members/$subscriber_hash", [
        'merge_fields' => ['CPP' => 'Yes']
    ]);
}

$result = $batch->execute();

file_put_contents('Log.txt', var_export($result));

