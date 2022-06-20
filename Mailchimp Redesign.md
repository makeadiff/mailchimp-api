# MailChip Data Sync Redesign

The system used to sync MailChimp with our DB is too old - and is failing at multiple places. At this point, its easier to scrape it and rebuild the system. Need to do some think thru before we do this. Things to think about...

## Database

The tables that are needed for the sync.

```
Mailchimp_Audience
	id
	name
	mailchimp_list_id
	data_fetch_query
	segments
	total
	last_updated_on
	added_on
	status

Mailchimp_Contact
	id
	mailchimp_audience_id
	item_type
	item_id
	details
	added_on
```

## Audiences

### List: Applicants

Definition: People who were not interviewed or Rejected at interview

- City
- Applied Year

### List: All Active Volunteers

- Primary Vertical
- Role/UserGroup
- Gender
- City
- Shelter/CCI
- Joined Year
- CPP Signed - NOT DONE
- Fundraised Groups - 0, <2000, <12000, 12000+ - NOT DONE

### Alumni

- City
- Shelter/CCI
- Joined Year
- Left Year
- All Verticals they were a part of

### Donors

- Online Donor
- Online Recurring Donor
- NACH/ECS
- First Donation Year
- Donated 10000(?)+


```sql
CREATE TABLE IF NOT EXISTS `Mailchimp_Audience` (
	`id` INT (11)  unsigned NOT NULL auto_increment,
	`name` VARCHAR (100)   NOT NULL,
	`mailchimp_list_id` VARCHAR (20)  NOT NULL,
	`data_fetch_query` VARCHAR (250)   NOT NULL,
	`segments` VARCHAR (200)   NOT NULL,
	`total` INT (10)   NOT NULL,
	`last_updated_on` DATETIME    NOT NULL,
	`added_on` DATETIME    NOT NULL,
	`status` ENUM ('0','1') DEFAULT '1',
	PRIMARY KEY (`id`),
	KEY (`mailchimp_list_id`)
) DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `Mailchimp_Contact` (
	`id` INT (11)  unsigned NOT NULL auto_increment,
	`mailchimp_audience_id` INT (11)  unsigned NOT NULL,
	`item_type` VARCHAR (100)   NOT NULL,
	`item_id` INT (11)  unsigned NOT NULL,
	`details` VARCHAR (250)   NOT NULL,
	`added_on` DATETIME    NOT NULL,
	PRIMARY KEY (`id`),
	KEY (`mailchimp_audience_id`),
	KEY (`item_id`)
) DEFAULT CHARSET=utf8 ;

```