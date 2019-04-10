# Sequence of manual migration steps:

Content types may be imported as YML files [instructions are here](https://github.com/lter/Deims7-8-Migration/tree/master/documentation/SyncConfiguration)

1. If desired migrate users (see above general comments)
1. Migrate taxonomies
	1. At NTL we have a few specific ones, change for a different website
	1. Moving taxonomies separately allows to omit a few that seemed unnecessary
	1. Create taxonomies in D8 site: core_areas, lter_controlled_vocabulary, other custom ones
	1. On the commandline inside the webroot of the new D8 website run the command 
	
	`drush migrate:import deims_category_core_areas` change migration ID for the others.

1. Migrate file entity information
	1. This yml file will only migrate information in the table: file_managed. It will not actually copy the files.
    1. On the commandline inside the webroot of the new D8 website run the command 
    
    `drush migrate:import deims_files`
	
1. Migrate basic pages and other custom content types that only need taxonomy tagging (e.g., research highlights, protocols, etc.)
	1. Create the desired content type in D8
    	1. Navigate in your D8 website to /admin/structure/types
    	1. Add Content type
    	1. Add needed fields 
    		* label: File; machine name: field_file; type: File
    		* label: NTL Keyword; machine name: field_ntl_keyword; type: Entity reference
    1. On the commandline inside the webroot of the new D8 website run the command 
    
    `drush migrate:import deims_nodes_highlights` change migration ID for the others.

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
    		* label: Administrative Area; machine name: field_address_admin_area; type: Text (plain)
    		* label: City; machine name: field_address_locality; type: Text (plain)
    		* label: Country; machine name: field_address_country; type: Text (plain)
    		* label: Department; machine name: field_person_department; type: Text (plain)
    		* label: e-mail; machine name: field_person_email; type: Email
    		* label: Family Name; machine name: field_name_family; type: Text (plain)
    		* label: Given Name; machine name: field_name_given; type: Text (plain)
    		* label: List in directory; machine name: field_person_list_in_directory; type: Boolean
    		* label: Middle Name; machine name: field_name_middle; type: Text (plain)
    		* label: ORCID; machine name: field_person_orcid; type: Link
    		* label: Organization; machine name: field_organization; type: Entity reference
    		* label: Phone; machine name: field_person_phone; type: Telephone number
    		* label: Postal Code; machine name: field_address_postal_code; type: Text (plain)
    		* label: Premise; machine name: field_address_premise; type: Text (plain)
    		* label: Project Role; machine name: field_person_project_role; type: List (text)
    		* label: Specialty; machine name: field_person_specialty; type: Text (plain)
    		* label: Street Address; machine name: field_address_street; type: Text (plain)
    1. Export person information from DEIMS7 database with [personExport.sql](https://github.com/lter/Deims7-8-Migration/blob/master/SQLexport_queries/personExport.sql) and save as personExport.csv
    1. In the migration YML file make sure the path to that csv file is set correctly
    1. On the commandline inside the webroot of the new D8 website run 
    
    `drush cim -y --partial --source=modules/custom/deims_migrate/config/install/`
    
    `drush migrate:import deims_csv_person`

1. Migrate research site
	1. Create content type in D8 name: Research site machine name: research_site
    	1. Navigate in your D8 website to /admin/structure/types
    	1. Add Content type
    	1. Add needed fields 
    		* label: Body; machine name: body; type: Text (formatted, long, with summary)
    		* label: Bottom Latitude; machine name: field_coord_bottom_latitude; type: Number (float)
    		* label: Elevation; machine name: field_elevation; type: Number (integer)
    		* label: Left Longitude; machine name: field_coord_left_longitude; type: Number (float)
    		* label: Rigth Longitude; machine name: field_coord_rigth_longitude; type: Number (float)
    		* label: Top Latitude; machine name: field_coord_top_latitude; type: Number (float)
    1. Export research site information from DEIMS7 database with [research_siteExport.sql](https://github.com/lter/Deims7-8-Migration/blob/master/SQLexport_queries/research_siteExport.sql) and save as research_siteExport.csv
    1. In the migration YML file make sure the path to that csv file is set correctly
    1. On the commandline inside the webroot of the new D8 website run 
    
    `drush cim -y --partial --source=modules/custom/deims_migrate/config/install/`
    
    `drush migrate:import deims_csv_site`
    	
1. Migrate variable - this requires new content types and some R scripts. Detailed instructions are under  [parseVariables](https://github.com/lter/Deims7-8-Migration/tree/master/documentation/parseVariables)