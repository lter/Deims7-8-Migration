# Importing content type configurations

Drupal8 allows to synchronize between sites (e.g., development, staging, production) via exporting and importing YML files. However, that only works if the sites have the same UUID. Which they only have if following the exact cloning instructions:
https://www.drupal.org/docs/8/configuration-management/managing-your-sites-configuration
https://www.drupal.org/node/2416591 

It is possible to change a site's uuid with this command and this particular uuid will allow to import all YML files in this git repository.

1. Getting the current uuid: `drush config-get "system.site" uuid` and save it somewhere to replace later again. (I haven't tried that yet)

1. Change it with `drush config-set "system.site" uuid '1bba7e8f-0b4d-4981-ab4e-efcfaff7f0ee'`

1. Export current configuration `drush config:export` . All YML files will be stored in web/config/sync which is determined in the settings.php file and can be changed.

1. Copy all additional YML files into that directory and run `drush config:import` (or do it one content type at a time, but make sure you get all YML files necessary for each content type.
	
	They need to all go into the same folder. Here in the Git repo I introduced folders for more clarity.