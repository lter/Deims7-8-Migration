# Deims7-8-Migration
 Step by step migration from DEIMS in Drupal7 to Drupal8. Inlcudes several changes to the basic DEIMS7 structure.

### Disclaimer:
This migration is not fully programmed and automatic but involves many manual steps. It requires knowledge in and access to the underlying databases (DEIMS7 and Drupal8) in MySQL. Preferably access via a client that allows querying the databases and exporting/importing data (e.g., PHPMyAdmin or a commercial client) but commandline access works. It also requires familiarity with running R scripts. We recommend to set up R projects in Rstudio especially to work with convertint variables. The R code can be improvde and everything has so far only been tested on the DEIMS7 installation at the North Temperate Lakes LTER.

## Getting Started

We recommend the following set up:

1. Set up a copy of your current production website for testing without disturbing the production website
1. Make sure you have access to its MySQL database for querying and exporting data. Alternatively, load a copy of the database onto a local MySQL server (comes with XAMPP see below)
1. Set up a fresh Drupal8 site in a testing environment - https://www.drupal.org/docs/8/install
	1. If done on a Windows desk/lap top it works well with XAMPP https://www.apachefriends.org/index.html
1. Follow instruction for installing composer (https://www.drupal.org/docs/develop/using-composer/using-composer-to-install-drupal-and-manage-dependencies) and drush

1. Use Composer to install the Drupal migration modules and enable them, but don't enable the migration examples, they only clutter up the database. In the D8 root directory (e.g., ../xampp/htdocs/deims8) use `composer require drupal/module_name`:
	1. Migrate Upgrade https://www.drupal.org/project/migrate_upgrade
	1. Migrate Plus https://www.drupal.org/project/migrate_plus
	1. Migrate Tools https://www.drupal.org/project/migrate_tools
	1. Migrate Source CSV https://www.drupal.org/project/migrate_source_csv
  
1. On the command line, navigate to your web root folder (e.g., ../xampp/htdocs/deims8). Everything is working well if the command `drush config:status` returns all migrate commands. On Windows that can be a little tricky and will involve various changes to the Path Environment Variable or diving into .dll hell.

1. In the settings.php (at e.g., ../xampp/htdocs/deims8/web/sites/default) file add the database connection information to access the DEIMS7 database.
	```
	$databases['migration_source_db']['default'] = array (
		'database' => 'database_name',
		'username' => 'user_name',
		'password' => 'user_password',
		'prefix' => '',
		'host' => 'localhost',
		'port' => '3306',
		'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
		'driver' => 'mysql',
	);
	```
	
1. Create a 'deims_migrate' folder under your new D8 webroot/web/modules/custom (e.g., ../xampp/htdocs/deims8/web/modules/custom/deims_migrate). Copy the deims_migrate.info.yml into this folder.
1. Create a 'config' folder inside the 'deims_migrate' folder and a 'install' folder inside the 'config' folder and paste the YML files into it:
	1. This sets up the connections
		1. migrate_plus.migration_group.deims_general.yml
	1. Migrating categories or taxonomies
		1. migrate_plus.migration.deims_category_core_areas.yml
		1. migrate_plus.migration.deims_category_lter.yml
		1. migrate_plus.migration.deims_category_ntl.yml (NTL specific)
		1. migrate_plus.migration.deims_category_ntl_themes.yml (NTL specific)
	1. Migrating all basic pages
		1. migrate_plus.migration.deims_nodes_page.yml
	1. NTL uses a few other content types that are based on the basic page and are tagged with taxonomy terms (these are not strictly DEIMS)
		1. migrate_plus.migration.deims_nodes_highlights.yml
		1. migrate_plus.migration.deims_nodes_protocol.yml
	1. Migrating content type 'person'
		1. migrate_plus.migration.deims_nodes_organization.yml
		1. migrate_plus.migration.deims_csv_person.yml
	1. Migrating content type 'research site'
		1. migrate_plus.migration.deims_csv_site.yml
	1. Migrating variables - this involves creating new content types, see below
		1. migrate_plus.migration.deims_csv_units.yml
		1. migrate_plus.migration.deims_csv_varcodedef.yml
		1. migrate_plus.migration.deims_csv_variables.yml


## Most important Drush migration commands

* after changing anything in a YML file run this with the correct path to install the changes:
	`drush cim -y --partial --source=modules/custom/deims_migrate/config/install/`
* run the migration (example_nodes is the ID in the migration YML file):
	`drush migrate:import example_nodes
* roll back migration:
	`drush migrate:rollback example_nodes`
* reset migration status when it claims to be busy after a failed migration
	`drush migrate-reset-status example_nodes`

## Some general comments
* The migration needs to happen in a certain sequence to make sure that depencies are available.
* All YML files are expecting a nid (node id). That means nids from the DEIMS7 database are being used and new ones for new content types will have to be created. I have not tried to run this migration without giving it nids, and don't know if Drupal would just handle them internally.
	* Make sure you know which nid ranges are available. The safest is to figure out the largest existing nid and add to that number.
	* When two uploads are using the same nid the older record will be overwritten without notice.
* At NTL only the admin changes web content, hence this migration does not include migrating users. If a different website requires other users, a good tutorial is [here](https://www.phase2technology.com/blog/managing-your-drupal)
	* All YML files are setting the user to 1. If that is not desired they have to be changed to read the uid from file or database.
* It doesn't look like anybody has developed the migration for a bounding box yet in geo-field yet. This may be an option later (see migrate research site)

## Sequence of migration steps:
1. If desired migrate users (see above general comments)
1. Migrate taxonomies
	1. At NTL we have a few specific ones, change for a different website
	1. Moving taxonomies separately allows to omit a few that seemed unnecessary
	1. On the commandline inside the webroot of the new D8 website run the command 
	`drush migrate:import deims_category_core_areas` etc.

1. Migrate basic pages and other custom content types that only need taxonomy tagging (e.g., research highlights, protocols, etc.)
	1. Create the desired content type in D8
    	1. Navigate in your D8 website to /admin/structure/types
    	1. Add Content type
    	1. Add needed fields 
    1. On the commandline inside the webroot of the new D8 website run the command 
    `drush migrate:import deims_nodes_highlights` etc.

1. Migrate organizations   
	1. Create content type in D8 name: Organization; machine name: organization
    	1. Navigate in your D8 website to /admin/structure/types
    	1. Add Content type
    	1. It doesn't need any fields, only the title
    1. On the commandline inside the webroot of the new D8 website run the command 
    `drush migrate:import deims_nodes_organization`.

1. Migrate Person
	1. Create content type in D8 name: Person; machine name: person
    	1. Navigate in your D8 website to /admin/structure/types
    	1. Add Content type
    	1. Add needed fields 
    	label: Administrative Area; machine name: field_address_admin_area; type: Text (plain)
    	label: City; machine name: field_address_locality; type: Text (plain)
    	label: Country; machine name: field_address_country; type: Text (plain)
    	label: Department; machine name: field_person_department; type: Text (plain)
    	label: e-mail; machine name: field_person_email; type: Email
    	label: Family Name; machine name: field_name_family; type: Text (plain)
    	label: Given Name; machine name: field_name_given; type: Text (plain)
    	label: List in directory; machine name: field_person_list_in_directory; type: Boolean
    	label: Middle Name; machine name: field_name_middle; type: Text (plain)
    	label: ORCID; machine name: field_person_orcid; type: Link
    	label: Organization; machine name: field_organization; type: Entity reference
    	label: Phone; machine name: field_person_phone; type: Telephone number
    	label: Postal Code; machine name: field_address_postal_code; type: Text (plain)
    	label: Premise; machine name: field_address_premise; type: Text (plain)
    	label: Project Role; machine name: field_person_project_role; type: List (text)
    	label: Specialty; machine name: field_person_specialty; type: Text (plain)
    	label: Street Address; machine name: field_address_street; type: Text (plain)
    1. Export person information from DEIMS7 database with [personExport.sql](https://github.com/lter/Deims7-8-Migration/blob/master/SQLexport_queries/personExport.sql) and save as personExport.csv
    1. On the commandline inside the webroot of the new D8 website run the command 
    `drush migrate:import deims_csv_person`

1. Migrate research site
	1. Create content type in D8 name: Research site machine name: research_site
    	1. Navigate in your D8 website to /admin/structure/types
    	1. Add Content type
    	1. Add needed fields 
    	label: Body; machine name: body; type: Text (formatted, long, with summary)
    	label: Bottom Latitude; machine name: field_coord_bottom_latitude; type: Number (float)
    	label: Elevation; machine name: field_elevation; type: Number (integer)
    	label: Left Longitude; machine name: field_coord_left_longitude; type: Number (float)
    	label: Rigth Longitude; machine name: field_coord_rigth_longitude; type: Number (float)
    	label: Top Latitude; machine name: field_coord_top_latitude; type: Number (float)
    1. Export research site information from DEIMS7 database with [research_siteExport.sql](https://github.com/lter/Deims7-8-Migration/blob/master/SQLexport_queries/research_siteExport.sql) and save as research_siteExport.csv
    1. On the commandline inside the webroot of the new D8 website run the command 
    `drush migrate:import deims_csv_site`
    	
1. Migrate variable - this require new content types and some R scripts