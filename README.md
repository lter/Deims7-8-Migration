# Deims7-8-Migration
 Step by step migration from DEIMS in Drupal7 to Drupal8. Inlcudes several changes to the basic DEIMS7 structure.

### Disclaimer:
This migration is not fully programmed and automatic but involves many manual steps. It requires knowledge in and access to the underlying databases (DEIMS7 and Drupal8) in MySQL. Preferably access via a client that allows querying the databases and exporting/importing data (e.g., PHPMyAdmin or a commercial client) but commandline access works. The R code can be improvde and everything has so far only been tested on the DEIMS7 installation at the North Temperate Lakes LTER.

## Getting Started

We recommend the following set up:

1. Get a copy of your current production website for testing without changing the production website
1. Set up a fresh Drupal8 site in a testing environment - https://www.drupal.org/docs/8/install
  1. If done on a Windows desk/lap top it works well with XAMPP https://www.apachefriends.org/index.html
1. Follow instruction for installing composer and drush

1. Use Composer to install the Drupal migration modules and enable them, but don't enable the migration examples, they only clutter up the database:
  1. Migrate Upgrade https://www.drupal.org/project/migrate_upgrade
  1. Migrate Plus https://www.drupal.org/project/migrate_plus
  1. Migrate Tools https://www.drupal.org/project/migrate_tools
  1. Migrate Source CSV https://www.drupal.org/project/migrate_source_csv
  
On the command line, navigate to your web root folder (e.g., ../xampp/htdocs/deims8). Everything in working well if the command <drush config:status> returns all migrate commands. On Windows that can be a little tricky and will involve various changes to the Path Environment Variable or diving into .dll hell.

