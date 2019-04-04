# Deims7-8-Migration
 Step by step migration from DEIMS in Drupal7 to Drupal8. Inlcudes several changes to the basic DEIMS7 structure.

### Disclaimer:
This migration is not fully programmed and automatic but involves many manual steps. It requires knowledge in and access to the underlying databases (DEIMS7 and Drupal8) in MySQL. Preferably access via a client that allows querying the databases and exporting/importing data (e.g., PHPMyAdmin or a commercial client) but commandline access works. The R code can be improvde and everything has so far only been tested on the DEIMS7 installation at the North Temperate Lakes LTER.

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
* reset migration status when it claims to be busy
	`drush migrate-reset-status example_nodes`

## Sequence of migration steps:

