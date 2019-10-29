# Deims7-8-Migration

Step by step migration from DEIMS in Drupal7 to Drupal8. Inlcudes several changes to the basic DEIMS7 structure.

### Disclaimer:

This migration is not fully programmed and automatic but involves many manual steps. It requires knowledge in and access to the underlying databases (DEIMS7 and Drupal8) in MySQL. Preferably access via a client that allows querying the databases and exporting/importing data (e.g., PHPMyAdmin or a commercial client) but commandline access works. It also requires familiarity with running R scripts. We recommend to set up R projects in Rstudio especially to work with converting variables. The R code can be improvde and everything has so far only been tested on the DEIMS7 installation at the North Temperate Lakes LTER.

## Getting Started

We recommend the following set up:

1. Set up a copy of your current production website for testing without disturbing the production website
1. Make sure you have access to its MySQL database for querying and exporting data. Alternatively, load a copy of the database onto a local MySQL server (comes with XAMPP see below)
1. Follow instructions for installing Composer (https://www.drupal.org/docs/develop/using-composer/using-composer-to-install-drupal-and-manage-dependencies) and drush
	1. On windows 
		* composer needs to be on the path environment variable
		* Use composer to install drush
		* Put two files into the webroot directory [Instructions for drush launcher](https://github.com/drush-ops/drush-launcher)
			1. [drush.phar](https://github.com/drush-ops/drush-launcher/releases/download/0.6.0/drush.phar)
			1. create drush.bat with the content:
			```
			@echo off
			php "%~dp0\drush.phar" %*
			```
	1. In Ubuntu command-line, [these instructions work well for installing Composer](https://www.digitalocean.com/community/tutorials/how-to-install-and-use-composer-on-ubuntu-18-04)
		* The Composer executable should ultimately end up in /usr/local/bin with permissions *chmod 0755*.
		* You will **not** want to run Composer with "sudo", so all users should be able to execute it.
	
1. Set up a fresh Drupal8 site in a testing environment - https://www.drupal.org/docs/8/install
	1. If done on a Windows desk/lap top it works well with XAMPP https://www.apachefriends.org/index.html
	1. In Ubuntu using command-line, Drupal 8 is most easily installed with Composer. [Instructions link.](https://www.drupal.org/docs/develop/using-composer/using-composer-to-install-drupal-and-manage-dependencies#drupal-composer-drupal-project) Summary of steps: 
		1. Open permissions for the install directory so you can install without "sudo" (e.g. /var/www/newsite with *chmod 0777*)
		1. Run this command to install the latest version of Drupal 8:
		```
		composer create-project drupal-composer/drupal-project:8.x-dev /var/www/newsite --stability dev --no-interaction
		```
		1. In your browser go to your new URL (note: you will need to include the 'web' subdirectory). For example: "https://newsite.wisc.edu/web". You should arrive at a Drupal set-up page to configure the database, etc.
		1. Reset permissions to something more secure. [Here is some guidance from drupal.org](https://www.drupal.org/node/244924)
1. Use Composer to install the Drupal migration modules and enable them, but don't enable the migration examples, they only clutter up the database. In the D8 root directory (e.g., ../xampp/htdocs/deims8) use `composer require drupal/module_name`:
	1. [Migrate Upgrade](https://www.drupal.org/project/migrate_upgrade)
	1. [Migrate Plus](https://www.drupal.org/project/migrate_plus)
	1. [Migrate Tools](https://www.drupal.org/project/migrate_tools)
	1. [Migrate Source CSV](https://www.drupal.org/project/migrate_source_csv)
		
1. User Composer to install other Drupal modules. Needed in this migration are:
	1. [Key Value Field](https://www.drupal.org/project/key_value_field)
	
1. Enable telephone field (part of core, but may not be active)

1. Enable date range field (part of core, but may not be active)
  
1. On the command line, navigate to your web root folder (e.g., ../xampp/htdocs/deims8). Everything is working well if the command `drush config:status` returns all migrate commands. On Windows that can be a little tricky and will involve various changes to the Path Environment Variable or diving into .dll hell.

1. In the settings.php (at e.g., ../xampp/htdocs/deims8/web/sites/default) file add the database connection information to access the DEIMS7 database. Make sure to call it: migration_source_db, which is used throughout this migration.
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
1. If encountering the WSOD ('white screen of death' or 'The website encountered an unexpected error. Please try again later.')
	* On windows it was resolved by increasing the setting for innodb_log_file_size to 48M (innodb_log_file_size = 48M)
	* This is a seeting in the 'my.ini' file which resides in the ../mysql/bin folder:
		* Shut down MySQL server
		* Move old log files away (../mysql/data/ib_logfile0 and ib_logfile1
		* Increase innodb_log_file_size to 48M
		* Restart the MySQL server
	
1. Create a 'deims_migrate' folder under your new D8 webroot/web/modules/custom (e.g., ../xampp/htdocs/deims8/web/modules/custom/deims_migrate). Copy the file: deims_migrate.info.yml into this folder.
1. Create a 'config' folder inside the 'deims_migrate' folder and a 'install' folder inside the 'config' folder and paste the rest of the YML files from [this folder](https://github.com/lter/Deims7-8-Migration/tree/master/YMLmigration_sripts) into it:
	1. This sets up the connections
		1. migrate_plus.migration_group.deims_general.yml
	1. Migrating 'categories or taxonomies' (migration directly from DEIMS7 database)
		1. migrate_plus.migration.deims_category_core_areas.yml
		1. migrate_plus.migration.deims_category_lter.yml
		1. migrate_plus.migration.deims_category_ntl.yml (NTL specific)
		1. migrate_plus.migration.deims_category_ntl_themes.yml (NTL specific)
	1. Migrate file management information (this will not copy the actual files only the content of table file_managed)
		1. migrate_plus.migration.deims_files.yml
	1. Migrating all 'basic pages' (migration directly from DEIMS7 database)
		1. migrate_plus.migration.deims_nodes_page.yml
	1. NTL uses a few other content types that are based on the basic page and are tagged with taxonomy terms (these are not strictly DEIMS)
		1. migrate_plus.migration.deims_nodes_highlights.yml
		1. migrate_plus.migration.deims_nodes_protocol.yml
	1. Migrating content type 'person' (organization from DEIMS7 database, person from a csv file generated by query)
		1. migrate_plus.migration.deims_nodes_organization.yml
		1. migrate_plus.migration.deims_csv_person.yml
	1. Migrating content type 'research site' (csv file generated by query necessary due to geo-field migration problems)
		1. migrate_plus.migration.deims_csv_site.yml
	1. Migrating entities 'variables' (this involves creating new content types, see below)
		1. migrate_plus.migration.deims_csv_units.yml
		1. migrate_plus.migration.deims_csv_varcodedef.yml
		1. migrate_plus.migration.deims_csv_variables.yml
	1. Migrating content type 'data source' (currently this script relies on nids not changing during migration)
		1. migrate_plus.migration.deims_nodes_dsource.yml
		
1. The deims_migrate module should show up in the D8 website ../deims8/web/admin/modules where it needs to be enabled. After it has been enabled run drush install command every time something is changed in a YML file.

## Most important Drush migration commands

* All drush commands are run on the commandline inside the webroot of the D8 website. Although the URL is e.g.: localhost/deims8/web the webroot folder is deims8 (e.g.: C:\xampp\htdocs\deims8)

* after changing anything in a YML file run this with the correct path to install the changes:

	`drush cim -y --partial --source=modules/custom/deims_migrate/config/install/`
* run the migration (example_nodes is the ID in the migration YML file):

	`drush migrate:import example_nodes`
* roll back migration:

	`drush migrate:rollback example_nodes`
* reset migration status when it claims to be busy after a failed migration

	`drush migrate-reset-status example_nodes`

## Some general comments

* The migration needs to happen in a certain sequence to make sure that depencies are available. [Migration Step by Step](https://github.com/lter/Deims7-8-Migration/tree/master/documentation)
* All YML files are expecting nid integers (node ids). That means nids from the DEIMS7 database are being used and new ones for new content types will have to be created. I have not tried to run this migration without giving it nids, and don't know if Drupal would just handle them internally.
	* Make sure you know which nid ranges are available. The safest is to figure out the largest existing nid in DEIMS7 and add to that number.
	* When two uploads are using the same nid the older record will be overwritten without notice.
* At NTL only the admin changes web content, hence this migration does not include migrating users. If a different website requires other users, a good tutorial is [here](https://www.phase2technology.com/blog/managing-your-drupal)
	* All YML files are setting the user to 1 (admin). If that is not desired they have to be changed to read the uid from file or database.
* It doesn't look like anybody has developed the migration for a bounding box in geo-field yet. This may be an option later (see migrate research site)
* Obviously, many SQL queries that now produce csv files could be programmed directly into the R code - if anybody wants to do that.
* The DEIMS7 'data source' content type supports methods, coverage, etc. on the entity level, however, the EML generator does not. NTL has very little information on that level.

##[Migration Step by Step](https://github.com/lter/Deims7-8-Migration/tree/master/documentation)
