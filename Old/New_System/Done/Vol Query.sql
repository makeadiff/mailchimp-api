SELECT DISTINCT U.id, U.name, U.email, U.sex, C.name AS city, YEAR(U.joined_on) AS joined_year, V.name as vertical, G.name AS main_group, CNT.name AS shelter, 
(SELECT GROUP_CONCAT(DISTINCT AG.name SEPARATOR ',')
    FROM UserGroup AUG 
    INNER JOIN `Group` AG ON AUG.group_id = AG.id WHERE AUG.year = %YEAR% AND AUG.user_id = U.id) AS all_groups,
SUM(
    (SELECT SUM(amount) FROM Online_Donation WHERE fundraiser_user_id = U.id AND added_on > '%YEAR_START_DATE%' AND payment='success'),
    (SELECT SUM(amount) FROM Donut_Donation WHERE fundraiser_user_id = U.id AND added_on > '%YEAR_START_DATE%')
) AS fundraised_amount
FROM User U
INNER JOIN City C ON C.id = U.city_id 
LEFT JOIN Center CNT ON U.center_id = CNT.id 
INNER JOIN UserGroup UG ON UG.user_id = U.id AND UG.main = '1' AND UG.year = %YEAR%
INNER JOIN `Group` G ON G.id = UG.group_id 
INNER JOIN Vertical V ON G.vertical_id = V.id 
WHERE U.status='1' AND U.user_type='volunteer' AND U.city_id != 28

---
# Query with Fundraised Amount - Both online and donuted. But very slow.

SELECT DISTINCT U.id, U.name, U.email, U.sex, C.name AS city, YEAR(U.joined_on) AS joined_year, V.name as vertical, G.name AS main_group, CNT.name AS shelter, 
(SELECT GROUP_CONCAT(DISTINCT AG.name SEPARATOR ',')
    FROM UserGroup AUG 
    INNER JOIN `Group` AG ON AUG.group_id = AG.id WHERE AUG.year = 2021 AND AUG.user_id = U.id) AS all_groups,
    COALESCE((SELECT SUM(amount) FROM Online_Donation WHERE fundraiser_user_id = U.id AND added_on > '2021-05-01 00:00:00' AND payment='success'), 0) +
    COALESCE((SELECT SUM(amount) FROM Donut_Donation WHERE fundraiser_user_id = U.id AND added_on > '2021-05-01 00:00:00'), 0) AS fundraised_amount
FROM User AS U
INNER JOIN City C ON C.id = U.city_id 
LEFT JOIN Center CNT ON U.center_id = CNT.id 
INNER JOIN UserGroup UG ON UG.user_id = U.id AND UG.main = '1' AND UG.year = 2021
INNER JOIN `Group` G ON G.id = UG.group_id 
INNER JOIN Vertical V ON G.vertical_id = V.id 
WHERE U.status='1' AND U.user_type='volunteer' AND U.city_id != 28

---

# 

SELECT id, name, email, YEAR(added_on) AS first_donation_year,
    COALESCE((SELECT SUM(amount) FROM Online_Donation WHERE donor_id = Donut_Donor.id AND payment='success'), 0) +
    COALESCE((SELECT SUM(amount) FROM Donut_Donation WHERE donor_id = Donut_Donor.id AND (status='receipted' OR status='deposited')), 0) AS donated_amount
FROM Donut_Donor
WHERE email != 'noreply@makeadiff.in'
HAVING donated_amount > 0