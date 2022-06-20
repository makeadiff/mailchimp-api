# MailChip Data Sync Redesign

The system used to sync MailChimp with our DB is too old - and is failing at multiple places. At this point, its easier to scrape it and rebuild the system. Need to do some think thru before we do this. Things to think about...

## Database

The tables that are needed for the sync.

```
Mailchimp_Audiance
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
	mailchimp_audiance_id
	item_type
	item_id
	added_on
```

## Audiances

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
- CPP Signed
- Fundraised Groups - 0, <2000, <12000, 12000+
- Joined Year

### Alumni

- City
- Shelter/CCI
- All Verticals they were a part of
- Joined Year
- Left Year

### Donors

- Online Donor
- Online Recurring Donor
- NACH/ECS
- First Donation Year
- Donated 10000(?)+
