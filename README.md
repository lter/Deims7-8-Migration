# Deims7-8-Migration

Step by step migration from DEIMS in Drupal7 to Drupal8. Includes several changes to the basic DEIMS7 structure.

### Disclaimer:

This migration is not fully programmed and automatic but involves many manual steps. It requires knowledge in and access to the underlying databases (DEIMS7 and Drupal8) in MySQL. Preferably access via a client that allows querying the databases and exporting/importing data (e.g., PHPMyAdmin or a commercial client) but commandline access works. It also requires familiarity with running R scripts. We recommend to set up R projects in Rstudio especially to work with converting variables. The R code can be improvde and everything has so far only been tested on the DEIMS7 installation at the North Temperate Lakes LTER.

## Getting Started 
It is assummed that a [XAMPP]( https://www.apachefriends.org/index.html) or a [LAMP](https://tecadmin.net/install-lamp-ubuntu-20-04/) stack has already been installed.

### Installing Drupal 8 with composer
Composer is recommended for installing Drupal as it will manage Drupal and all dependencies (modules, themes, libraries).

We recommend the following set up:

1. Composer Installation (https://www.drupal.org/docs/develop/using-composer/using-composer-to-install-drupal-and-manage-dependencies). 
	1. **Windows** 10/server
		* composer needs to be on the path environment variable
		* Use composer to install drush
		* Put two files into the webroot directory [Instructions for drush launcher](https://github.com/drush-ops/drush-launcher)
			1. [drush.phar](https://github.com/drush-ops/drush-launcher/releases/download/0.6.0/drush.phar)
			1. create drush.bat with the content:
			```
			@echo off
			php "%~dp0\drush.phar" %*
			```
	1. **Ubuntu** command-line, [these instructions work well for installing Composer](https://www.digitalocean.com/community/tutorials/how-to-install-and-use-composer-on-ubuntu-18-04)
		* The Composer executable should ultimately end up in /usr/local/bin with permissions *chmod 0755*.
		* You will **not** want to run Composer with "sudo", so all users should be able to execute it.
		* Install drush launcher.  With the drush launcher you can simply type drush on the command line. [Instructions for drush launcher](https://github.com/drush-ops/drush-launcher) 
	
2. Install Drupal 8 site in a testing environment - [Overview of installing Drupal with composer](https://www.drupal.org/docs/develop/using-composer/using-composer-to-install-drupal-and-manage-dependencies) 
	1. Windows 10/server works well with XAMPP https://www.apachefriends.org/index.html
	1. Ubuntu command line installation with Composer. [Instructions link.](https://www.drupal.org/docs/develop/using-composer/using-composer-to-install-drupal-and-manage-dependencies#drupal-composer-drupal-project)
	Summary of steps: 
		1. Open permissions for the install directory so you can install without "sudo" (e.g. /var/www with *chmod 0777* or change the owner to user installing drupal)
		2. Download  drupal/recommended-project composer.json and composer.lock to /var/www
		3. Do a modified installation. https://www.drupal.org/docs/develop/using-composer/using-composer-to-install-drupal-and-manage-dependencies#s-to-do-a-modified-install 
		```
		cd /var/www
		composer create-project --no-install drupal/recommended-project newsite
		```
		* Note: composer will create the site 'newsite' directory and several subdirectories and a new 'composer.json' file in the 'newsite' directory. 
		  (drupal root will be in newsite/web)
		* Modify the /var/www/newsite/composer.json with an editor to include the following list of required projects 
		```
		"drupal/backup_migrate": "^4.0",
		"drupal/console": "^1.0.2",
		"drupal/devel": "~1.0",
		"drupal/ds": "^3.5",
		"drupal/filehash": "^1.2",
		"drupal/imce": "^1.7", 
		"drupal/key_value_field": "^1.0",
		"drupal/geofield": "1.x-dev", 
		"drupal/geofield_map": "^2.52"
		"drupal/leaflet": "^1.23",
		"drupal/migrate_plus": "^5.1",
		"drupal/migrate_source_csv": "2.2", 
		"drupal/migrate_tools": "^5.0",
		"drupal/migrate_upgrade": "^3.2",
		"drupal/views_bulk_operations": "^3.3",
		"drush/drush": "^9.7.1 | ^10.0.0" 
		```
		* Review the above list to check if newer versions are availabe. Note migrate_source_csv is version 2.2 because the 3.x version uses a slightly different 
		  format of the YML file.
		* Run the following to create the site. 
		
		```
		Composer update
		```
		* Drush 10 is now installed in vendor/bin/drush. If the drush launcher is installed you can just type drush in the 'newsite' directory .  
		
		4. Add a virtual host in apache2 for the ‘newsite’ URL. Document root will be ‘/var/www/newsite/web’. Modify the directive for symbolic links. Add web
		   site user to www-data group
		5. In your browser go to your new site. You should arrive at a Drupal set-up page to configure the database, etc.
		6. Reset permissions to something more secure. [Here is some guidance from drupal.org](https://www.drupal.org/node/244924)
3. To install additional modules use Composer. See https://www.drupal.org/docs/develop/using-composer/using-composer-to-install-drupal-and-manage-dependencies\#adding-modules The above modified composer.json has already installed the Drupal migration modules and additional modules needed for migration. Note for [Migrate Source CSV](https://www.drupal.org/project/migrate_source_csv) -- Use version 2.2 (composer require 'drupal/migrate_source_csv:2.2') because the 3.x version uses a slightly different format of the YML file.
 4. Enable the migration and key_value modules using drush or the web-interface but don't enable the migration examples, they only clutter up the database.
	 1. Enable telephone field (part of core, but may not be active)
	 1. Enable date range field (part of core, but may not be active)
 
5. On the command line, navigate to your web root folder (e.g., ../xampp/htdocs/deims8). Everything is working well if the command `drush migrate:status` returns all migrate commands. On Windows that can be a little tricky and will involve various changes to the Path Environment Variable or diving into .dll hell. It's straightforward in Linux.

6. In the settings.php (at e.g., ../xampp/htdocs/deims8/web/sites/default) file add the database connection information to access the DEIMS7 database. Make sure to call it: migration_source_db, which is used throughout this migration. Also specify the 'config_syncdirectory' to be outside the web directory.
	```
	$settings['config_sync_directory'] = '../config/sync';
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
	1. You may also have to add the following database connection if you get an error `The specified database connection is not defined: upgrade` from the migrate-upgrade command.
	```
	$settings['config_sync_directory'] = '../config/sync';
        // Database entry for `drush migrate-upgrade --configure-only`
        $databases['migrate']['default'] = array (
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

7. Create a 'deims_migrate' folder under your new D8 webroot/web/modules/custom (e.g., ../xampp/htdocs/deims8/web/modules/custom/deims_migrate). Copy the file: deims_migrate.info.yml into this folder. Create a 'config/install' folder inside the 'deims_migrate' folder. The install directory is where the .yml files are places.

8. Create .yml files using 'drush migrate-upgrade'. (Before this step you should review your D7 site and decide which taxonomy vocabularies, content types, users you will migrate and/or rename.)
	* Create the config/sync directory `mkdir /var/www/newsite/config/sync`
	* Run command `drush migrate-upgrade --legacy-db-key=migrate --configure-only`
	* Export the migrations using `drush config:export` The .yml files will be in /var/www/newsite/config/sync. 
	* You only need to copy and edit the files that begin with migrate_plus.migration.
	
## Next step is creating the content types and editing the .yml files [Migration Step by Step](https://github.com/lter/Deims7-8-Migration/tree/master/documentation)

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
