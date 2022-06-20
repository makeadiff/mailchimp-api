<?php
require '../../common.php';

// Get Donor data...
// This query takes too long.
// $donors = $sql->getAll("SELECT id, name, email, YEAR(added_on) AS first_donation_year,
// 			    COALESCE((SELECT SUM(amount) FROM Online_Donation WHERE donor_id = Donut_Donor.id AND payment='success'), 0) +
// 			    COALESCE((SELECT SUM(amount) FROM Donut_Donation WHERE donor_id = Donut_Donor.id AND (status='receipted' OR status='deposited')), 0) AS donated_amount
// 			FROM Donut_Donor
// 			WHERE email != 'noreply@makeadiff.in'");

// This will work 
// SELECT DD.id, DD.name, DD.email, YEAR(DD.added_on) AS first_donation_year, OD.donation_count, OD.donation_total
//     FROM (SELECT DISTINCT donor_id, COUNT(id) AS donation_count, SUM(amount) AS donation_total
//         FROM Online_Donation
//         WHERE payment='success'
//         GROUP BY donor_id) OD
//     INNER JOIN Donut_Donor DD ON OD.donor_id = DD.id
//     WHERE DD.email != 'noreply@makeadiff.in'



$donors = $sql->getById("SELECT id, name, email, YEAR(added_on) AS first_donation_year
			FROM Donut_Donor
			WHERE email != 'noreply@makeadiff.in'");


// Open CSV of unsubscribed donors.
$unsubscribed = csv2array('unsubscribed_members_export_4ce067d788.csv');
$unsubscribed_emails = array_column($unsubscribed, 'Email Address');

// Remove all unsubscribed people from donor list - and people with 0 donated_amount
print "Unsubcribed Donors: " . count($unsubscribed_emails) . "\n";
$total = count($donors);
$row = 0;
foreach($donors as $donor_id => $don) {
	$row++;
	if(in_array($don['email'], $unsubscribed_emails)) {
		unset($donors[$donor_id]);
	}

	if($row % 100 == 0) print "Processed $row / $total\r";
}
print "\nDonors after filtering: " . count($donors) . "\n";

file_put_contents('Donors.csv', array2csv($donors));


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