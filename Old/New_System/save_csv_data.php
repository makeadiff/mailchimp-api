<?php
require '../common.php';
require '../credentials.php';

// Purpose: Save the details of the contacts uploaded to MailChimp by CSV Import to our contacts table.

// $csv_file = './Volunteer.csv';
// $mc_audiance_id = 1;
// $table = 'User';

// $csv_file = './Alumnai.csv';
// $mc_audiance_id = 2;
// $table = 'User';

// try {
//     $data = csv2array($csv_file);
// } catch (Exception $e) {
//     die ("Error: " . $e->getMessage() . "\n");
// }

$data = $sql->getAll("SELECT id, name, email, YEAR(added_on) AS first_donation_year
            FROM Donut_Donor
            WHERE email != 'noreply@makeadiff.in'");
$mc_audiance_id = 3;
$table = 'Donut_Donor';

foreach ($data as $index => $row) {
    $insert = [
        'mailchimp_audience_id' => $mc_audiance_id,
        'item_type' => $table,
        'item_id'   => $row['id'],
        'details'   => json_encode($row),
        'added_on'  => date('Y-m-d H:i:i')
    ];

    $sql->insert('Mailchimp_Contact', $insert);

    if($index % 50 == 0) print "Imported $index / " . count($data) . "\r";
}

print "\nDone\n";


function csv2array($file) {
    $handle = fopen($file,'r');
    if(!$handle) {
        throw new Exception("Could not open file '$file'.");
    }

    ini_set("auto_detect_line_endings", "1"); // For MAC Line endings.

    $row_index = 0;
    $headers = [];
    $return = [];
    while (($data = fgetcsv($handle, 5000, ",")) !== FALSE) {
        $row_index++;
        if($row_index == 1) {
            $headers = $data;
            continue;
        }

        $row = [];
        foreach($data as $key => $value) {
            $row[$headers[$key]] = $value;
        }
        $return[] = $row;
    }

    return $return;
}