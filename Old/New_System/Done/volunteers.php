<?php
require '../common.php';
require '../credentials.php';

// Active Volunteers
$volunteers_query = "SELECT DISTINCT U.id, U.name, U.email, U.sex, C.name AS city, YEAR(U.joined_on) AS joined_year, V.name as vertical, G.name AS main_group, CNT.name AS shelter, 
                        (SELECT GROUP_CONCAT(DISTINCT AG.name SEPARATOR ',')
                            FROM UserGroup AUG 
                            INNER JOIN `Group` AG ON AUG.group_id = AG.id WHERE AUG.year = 2021 AND AUG.user_id = U.id) AS all_groups
                        FROM User U
                        INNER JOIN City C ON C.id = U.city_id 
                        LEFT JOIN Center CNT ON U.center_id = CNT.id 
                        INNER JOIN UserGroup UG ON UG.user_id = U.id AND UG.main = '1' AND UG.year = 2021
                        INNER JOIN `Group` G ON G.id = UG.group_id 
                        INNER JOIN Vertical V ON G.vertical_id = V.id 
                        WHERE U.status='1' AND U.user_type='volunteer' AND U.city_id != 28";
$segments = [
    'name'          => 'FNAME',
    'id'            => 'MAD_ID',
    'city'          => 'CITY',
    'sex'           => 'SEX',
    'joined_year'   => 'JOIN_YEAR',
    'vertical'      => 'VERTICAL',
    'main_group'    => 'GROUP',
    'shelter'       => 'SHELTER'
];

$mc_audiance_id = 1;
$list_id = 'c89d9c33db';

// Get all data from DB
$db_volunteers = $sql->getById($volunteers_query);

// Get data in MailChimp_Contacts
$mc_volunteers = $sql->getById("SELECT item_id, details FROM `Mailchimp_Contact` WHERE mailchimp_audience_id=$mc_audiance_id AND item_type='User'");

print "Volunteers in DB: " . count($db_volunteers) . " and volunteers in MC: " . count($mc_volunteers) . "\n";

// Diff the two.
foreach($db_volunteers as $user_id => $user) {
    if(isset($mc_volunteers[$user_id])) { // This volunteer is there in the MC List.
        unset($db_volunteers[$user_id]); // So delete from array.
    }
}

print "New Volunteers: " . count($db_volunteers) . "\n"; // exit;

// Things not in Contact, send to MailChimp
use \DrewM\MailChimp\MailChimp;
use \DrewM\MailChimp\Batch;
$mc = new MailChimp($mailchimp_api_key);
$batch = $mc->new_batch();

foreach($db_volunteers as $user_id => $user) {
    $fields = [];
    foreach($segments as $seg_key => $seg_name) {
        $fields[$seg_name] = $user[$seg_key];
    }

    $batch->post("op_" . $user_id, "lists/$list_id/members", [
        'email_address' => $user['email'],
        'merge_fields'  => $fields,
        'tags'          => explode(',', $user['all_groups']),
        'status'        => 'subscribed',
    ]);

    // And save to local table. :TODO: Save only if post successful - but that's tricky
    $insert = [
        'mailchimp_audience_id' => $mc_audiance_id,
        'item_type' => 'User',
        'item_id'   => $user_id,
        'details'   => json_encode($user),
        'added_on'  => date('Y-m-d H:i:i')
    ];

    $sql->insert('Mailchimp_Contact', $insert);
}

$result = $batch->execute();
print "Sent data to MailChimp. Batch ID: " . $result['id']. "\n";
