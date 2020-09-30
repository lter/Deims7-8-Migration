# Sequence of manual migration steps:
Don't forget after any changes to .yml files in the install folder run
```
drush cim -y --partial --source=modules/custom/deims_migrate/config/install/
```
Note drush migrate is used to do the imports.  A GUI is also available, /admin/structure/migrate which will mirror the drush cammands
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
    			* add list items: e.g. 'LPI|Lead Principal Investigator', 'COPI|co-Principal Investigator',
    				'FA|Faculty Associate', 'PDA|Post Doctoral Associate', 'OP|Other Professional',
    				'GS|Graduate Student', 'US|Undergraduate Student', 'OS|Other Staff', 'SC|Secretary Clerical',
    				'DA|Data Manager', 'PUB|Publisher', 'CO|Contact Person'
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

1. Migrate Project and create new content type for funding in anticipating of EML 2.2
	1. Create content type in D8 name: Data Source machine name: data_source
    	1. Navigate in your D8 website to /admin/structure/types
    	1. Add Content type label: Project Funding; machine name: project_funding
    	1. Add needed fields
    		* label: Award URL; machine name: field_funder_award_url; type: Link
    		* label: Funder Award Number; machine name: field_funder_award_number; type: Text (plain)
    		* label: Funder Award Title; machine name: field_funder_award_title; type: Text (plain)
    		* label: Funder ID; machine name: field_funder_id; type: Text (plain) 	
    		* label: Funder Name; machine name: field_funder_name; type: Text (plain)
    	1. Add Content type label: Research Project; machine name: project
    	1. Add needed fields
    		* label: Body; machine name: body 	Text (formatted, long, with summary)
    		* label: Funding; machine name: field_project_funding; type: Entity reference
    		* label: Investigator; machine name: field_project_investigator; type: Entity reference
    		* label: LTER Keyword; machine name: field_project_lter_keyword; type: Entity reference
    		* label: NTL Keyword; machine name: field_project_ntl_keyword; type: Entity reference
    		* label: Timeline; machine name: field_project_timeline; type: Date range
    1. On the commandline inside the webroot of the new D8 website run `drush migrate:import deims_nodes_project`
    	* Currently the YML script relies on the fact that old DIEMS7 nids are being used in D8 and no migration_lookup is performed!
    	* No funding information is in DEIMS7
    	
1. Migrate variable - this requires new content types and some R scripts. Detailed instructions are: [parseVariables](https://github.com/lter/Deims7-8-Migration/tree/master/documentation/parseVariables)

1. Migrate data sources
	1. Create content type in D8 name: Data Source machine name: data_source
    	1. Navigate in your D8 website to /admin/structure/types
    	1. Add Content type
    	1. Add needed fields 
    		* label: Database; machine name: field_dsource_de_database;type: Text (plain)
    		* lable: database table; machine name: field_dsource_de_table; type: Text (plain)
    		* label: Date Range; machine name: field_dsource_date_range; type: Date range 	
    		* label: Description; machine name: field_dsource_description; type: Text (plain, long) 	
    		* label: Field Delimiter; machine name: field_dsource_field_delimiter; type: List (text)
    			* add list items: ',|Comma (,)', '\t|tab', ';|Semicolon (;)', 'other|other'
    			* set the default value
    		* label: File Upload; machine name: field_dsource_file; type: File 	
    		* label: Footer Lines; machine name: field_dsource_footer_lines; type: Number (integer) 	
    		* label: Header Lines; machine name: field_dsource_header_lines; type: Number (integer) 	
    		* label: Instrumentation; machine name: field_dsource_instrumentation; type: Text (plain, long) 	
    		* label: Methods; machine name: field_dsource_methods; type: Text (plain, long) 	
    		* label: Number of Records; machine name: field_dsource_num_records; type: Number (integer) 	
    		* label: Orientation; machine name: field_dsource_orientation; type: List (text)
    			* add list items: 'column|column', 'row|row'
    			* set the default value
    		* label: Quality Assurance; machine name: field_dsource_quality_assurance; type: Text (plain, long) 	
    		* label: Quote Character; machine name: field_dsource_quote_character; type: List (text)
    			* add list items: ''|single quote (')','"|double quote (")', 'other|other'
    			* set the default value
    		* label: Record Delimiter; machine name: field_dsource_record_delimiter; type: List (text)
    			* add list items: '\n|Newline (Unix \n)', '\r\n|Newline (Windows \r\n)', '\r|Newline (some macs \r)', ';|Semicolon (;)', 'other|other'
    			* set the default value
    		* label: Related Sites; machine name: field_dsource_related_sites; type: Entity reference 	
    		* label: Variables; machine name: field_dsource_variables; type: Entity reference
    1. On the commandline inside the webroot of the new D8 website run `drush migrate:import deims_nodes_dsource`
    	* Currently the YML script relies on the fact that old DIEMS7 nids are being used in D8 and no migration_lookup is performed!
    1. Use [SQL script](https://github.com/lter/Deims7-8-Migration/blob/master/SQLexport_queries/exportDataSourceIDs.sql) to get the new nid/vid mapping
    1. See last point in [parseVariables](https://github.com/lter/Deims7-8-Migration/tree/master/documentation/parseVariables). Run [R script](https://github.com/lter/Deims7-8-Migration/blob/master/R%20scripts/datasourceVariablesReference.R) to make the upload file needed to link variables to each data source.
    1. Manually upload file 'upload_dsourceVariablesReference.csv' to table: 'node__field_dsource_variables'
    1. Clear all caches in the D8 site and make sure the data sources look like they are supposed to:
    	* They have all variables linked
    	* They have the csv file linked
1. Migrate data sets - [see documentation](https://github.com/lter/Deims7-8-Migration/tree/master/documentation/dataSet)   
