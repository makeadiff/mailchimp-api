<?php
require 'common.php';
require 'credentials.php';

// Things not in Contact, send to MailChimp
use \DrewM\MailChimp\MailChimp;
use \DrewM\MailChimp\Batch;
$mc = new MailChimp($mailchimp_api_key);

$audiances = $sql->getAll("SELECT id, name, mailchimp_list_id, source_table, data_fetch_query, segments FROM Mailchimp_Audience WHERE status = '1'");

foreach ($audiances as $aud) {
    print "Syncing $aud[name]\n";
    $query = $aud['data_fetch_query'];
    $list_id = $aud['mailchimp_list_id'];
    $mc_audiance_id = $aud['id'];
    $segments_raw = $aud['segments'];
    $source_table = $aud['source_table'];

    if(!$query or !$list_id or !$segments_raw or !$mc_audiance_id or !$source_table) {
        die("Necessary data point in Table missing. One from...\n"
                . "query: $query\nlist_id: $list_id\nsegments_raw: $segments_raw\nmc_audiance_id: $mc_audiance_id\nsource_table: $source_table\n");   
    }

    $segments = json_decode($segments_raw, true);

    // Get all data from DB
    $db_items = $sql->getById($query);

    // Get data in MailChimp_Contacts
    $mc_items = $sql->getById("SELECT item_id AS id, details FROM `Mailchimp_Contact` WHERE mailchimp_audience_id=$mc_audiance_id AND item_type='$source_table'");

    print "\t$source_table in DB: " . count($db_items) . " and $source_table in MC: " . count($mc_items) . "\n";

    // We have to diff the DB and MC List. First, create a copy that we'll use later.
    $copy_db_items = $db_items;

    $batch = $mc->new_batch();

    // Part 1 - Things in DB and NOT in Mailchimp...
    foreach($db_items as $item_id => $item) {
        if(isset($mc_items[$item_id])) { // This item is there in the MC List.
            unset($db_items[$item_id]); // So delete from array.
        }
    }

    print "\tNew $source_table: " . count($db_items) . "\n"; // exit;

    foreach($db_items as $item_id => $item) {
        $fields = [];
        foreach($segments as $seg_key => $seg_name) {
            if($item[$seg_key]) {
                $fields[$seg_name] = $item[$seg_key];
            }
        }

        $tags = [];
        if($mc_audiance_id == 1 or $mc_audiance_id == 2) {
            $tags = explode(',', $item['all_groups']);
        }

        // dump($tags, $fields);exit;
        $batch->post("op_" . $item_id, "lists/$list_id/members", [
            'email_address' => $item['email'],
            'merge_fields'  => $fields,
            'tags'          => $tags,
            'status'        => 'subscribed',
        ]);


        // And save to local table. :TODO: Save only if post successful - but that's tricky
        $insert = [
            'mailchimp_audience_id' => $mc_audiance_id,
            'item_type' => $source_table,
            'item_id'   => $item_id,
            'details'   => json_encode($item),
            'added_on'  => date('Y-m-d H:i:i')
        ];

        $sql->insert('Mailchimp_Contact', $insert);
    }

    // This second part of diff is needed only for Active Volunteer Audience.
    $items_to_delete = [];
    if($mc_audiance_id == 1) {

        // Part 2 - Things NOT in DB but in Mailchimp... We have to remove these from MC. Eg. Volunteers who have been alumni or let go.
        foreach($mc_items as $item_id => $item) {
            if(!isset($copy_db_items[$item_id])) { // This item is NOT there in the DB List.
                $items_to_delete[$item_id] = json_decode($item, true);
            }
        }

        print "\tTo be deleted from Mailchimp: " . count($items_to_delete) . "\n"; //exit;

        foreach($items_to_delete as $item_id => $item) {
            $member_hash = $mc->subscriberHash($item['email']);
            $batch->delete("op_" . $item_id, "lists/$list_id/members/$member_hash");

            // And detele from local table. :TODO: Save only if post successful - but that's tricky
            $sql->remove('Mailchimp_Contact', [
                'mailchimp_audience_id' => $mc_audiance_id,
                'item_id'   => $item_id,
                'item_type' => $source_table
            ]);
        }
    }

    if(count($db_items) or count($items_to_delete)) {
        $result = $batch->execute();
        $sql->update('Mailchimp_Audience', 
            [
                'mailchimp_batch_id'=> $result['id'],
                'last_synced_on'    => 'NOW()',
            ], 
            ['id' => $aud['id']]
        );
        print "\tSent data to MailChimp. Batch ID: " . $result['id']. "\n";
    } else {
        print "\tNo data to be synced.\n";
    }

}
