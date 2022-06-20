<?php
require 'common.php';
require 'credentials.php';

/// Purpose : Send all donors to MailChimp

use \DrewM\MailChimp\MailChimp;
use \DrewM\MailChimp\Batch;
$mp = new MailChimp($mailchimp_api_key);

$list_id = '96237'; //Donors

// Tags
// - online_donor
// - recurring_donor
// - nach_ecs
// - crowdfunding

$donors = $sql->getAll("SELECT D.id, D.name, D.email, D.phone FROM Donut_Donor D INNER JOIN Online_Donation OD ON OD.donor_id=D.id LIMIT 0,10");

dump($donors);
exit;

