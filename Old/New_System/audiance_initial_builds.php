<?php
require '../common.php';

/// Applicants
$applicants_query = "SELECT U.id, U.name, U.email, C.name AS city, YEAR(U.added_on) AS applied_year FROM User U 
						INNER JOIN City C ON U.id = C.city_id 
						WHERE U.status='1' AND U.user_type NOT IN ('volunteer', 'alumni', 'let_go')";


// Active Volunteers
$volunteers_query = "SELECT U.id, U.name, U.email, U.sex, C.name AS city, YEAR(U.joined_on) AS joined_year, V.name as vertical, G.name AS main_group, CNT.name AS shelter, 
						(SELECT GROUP_CONCAT(DISTINCT AG.name SEPARATOR ',') FROM UserGroup AUG INNER JOIN `Group` AG ON AUG.group_id = AG.id WHERE AUG.year = 2021 AND AUG.user_id = U.id) AS all_groups
						FROM User U
						INNER JOIN City C ON C.id = U.city_id 
						LEFT JOIN Center CNT ON U.center_id = CNT.id 
						INNER JOIN UserGroup UG ON UG.user_id = U.id AND UG.main = '1' AND UG.year = 2021
						INNER JOIN `Group` G ON G.id = UG.group_id 
						INNER JOIN Vertical V ON G.vertical_id = V.id 
						WHERE U.status='1' AND U.user_type='volunteer'";
// Not added...
// - CPP Signed
// - Fundraised Groups - 0, <2000, <12000, 12000+

// Alumni
$alumni_query = "SELECT DISTINCT U.id, U.name, U.email, U.sex, C.name AS city, YEAR(U.joined_on) AS joined_year, YEAR(U.left_on) AS left_year, CNT.name AS shelter, 
						GROUP_CONCAT(DISTINCT V.name SEPARATOR ',') AS verticals, GROUP_CONCAT(DISTINCT G.name SEPARATOR ',') AS roles
						FROM User U
						INNER JOIN City C ON C.id = U.city_id 
						LEFT JOIN Center CNT ON U.center_id = CNT.id 
						INNER JOIN UserGroup UG ON UG.user_id = U.id
						INNER JOIN `Group` G ON G.id = UG.group_id 
						INNER JOIN Vertical V ON G.vertical_id = V.id 
						WHERE U.status='1' AND U.user_type='alumni'
						GROUP BY U.id";

