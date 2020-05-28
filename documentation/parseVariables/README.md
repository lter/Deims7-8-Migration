# Convert DEIMS7 variables (attributes) to Drupal8 content types

The process of converting DEIMS7 variables entities into content types involves several steps which are outlined in this document.  

1. __Query DEIMS7 MySQL database and export all information from variables as csv table__ The EML code/definition fields need their own content type set up in Drupal8.  The csv file to generate the nodes is built and then in a manual data import directly into the D8 database the actual code/definitions are added to the nodes. (This may be possible more elegantly with another YML file, but it works as described here.) The first step is to query the DEIMS7 MySQL database, export the information. Use SQL script   [variableExport.sql](https://github.com/lter/Deims7-8-Migration/blob/master/SQLexport_queries/variableExport.sql) and save as variableExport.csv

1. __Attributes of the enumeratedDomain type__ The information encoded in the BLOB field needs to be parsed into key/value pairs. Use R code [parseVariablesCodeDefinition.R](https://github.com/lter/Deims7-8-Migration/blob/master/R%20scripts/parseVariablesCodeDefinition.R)
    1. Create content type Variable Codes machine name: variable_codes
    	1. Navigate in your D8 website to /admin/structure/types
    	1. Add Content type
    	1. Add Field: 
    		* label: Code Definition; machine name: field_variable_code_definition; type: Key/value (plain)
    	
    1. Run the first part of the script up to saving upload_code_def_node.csv
    1. In the migration YML file make sure the path to that csv file is set correctly
    1. On the commandline inside the webroot of the new D8 website run 
    
    `drush cim -y --partial --source=modules/custom/deims_migrate/config/install/`
 
    `drush migrate:import deims_csv_varcodedef`
    
    1. Export IDs that Drupal has assigned with SQL script [exportVariableCodeIDs.sql](https://github.com/lter/Deims7-8-Migration/blob/master/SQLexport_queries/exportVariableCodeIDs.sql) save as nid_vid_mapping.csv
    1. Run the second part of the R script
    1. Manually import file upload_code_def_values.csv into D8 table node__field_variable_code_definition
    1. Make sure everything looks as expected
    
1. __Attributes of the physicalDomain type__ The units need to be turned into a content type. Use R code [parseVariablesUnits.R](https://github.com/lter/Deims7-8-Migration/blob/master/R%20scripts/parseVariablesUnits.R)
	1. Create content type Units; machine name: variables_units
    	1. Navigate in your D8 website to /admin/structure/types
    	1. Add Content type
    	1. Add Fields: 
    		* label: Abbreviation; machine name: field_variables_unit_abbrev; type: Text (formatted)
    		* label: Constant to SI; machine name: field_variables_unit_constant; type: Number (float)
    		* label: Description; machine name: field_variables_unit_description; type: Text (plain)
    		* label: Multiplier to SI; machine name: field_variables_unit_multiplier; type: Number (float)
    		* label: Parent SI; machine name: field_variables_unit_parent_si; type: Text (plain)
    		* label: Unit Type; machine name: field_variables_unit_type; type: Text (plain)
    1. Download the standard units csv file [units.csv](https://github.com/lter/Deims7-8-Migration/blob/master/data/units.csv)
    1. Check the units for typos, non-standard spelling etc., the R script contains a case block where these can be corrected
    1. Run R script 
    1. In a text editor remove special characters from csv file (micro, square, etc.)
    1. In the migration YML file make sure the path to that csv file is set correctly and that the column names exactly match those in the csv file.
    1. On the commandline inside the webroot of the new D8 website run 
    
    `drush cim -y --partial --source=modules/custom/deims_migrate/config/install/`
 
    `drush migrate:import deims_csv_units`
    
    1. Make sure everything looks as expected
    
1. __Variable content type__ I.e., all the rest of the information variables need. Use R script [parseVariablesNodes.R](https://github.com/lter/Deims7-8-Migration/blob/master/R%20scripts/parseVariablesNodes.R)
	1. Create content type Variable; machine name: variable 
    	1. Navigate in your D8 website to /admin/structure/types
    	1. Add Content type
    	1. Add Fields: 
    		* label: Code Definition; machine name: 	field_variables_code_definition; type: 	Entity reference
    		* label: Date Time Format; machine name: 	field_variables_date_time_format; type: 	Text (plain)
    		* label: DE expose; machine name: field_variable_de_expose; type: Boolean
    		* label: DE filter; machine name: field_variable_de_filter; type: Boolean
    		* label: Definition; machine name: 	field_variables_definition; type: 	Text (plain, long)
    		* label: Label; machine name: 	field_variables_label; type: 	Text (plain)
    		* label: Maximum; machine name: 	field_variables_maximum; type: 	Number (float)
    		* label: Minimum; machine name: 	field_variables_minimum; type: 	Number (float)
    		* label: Missing Value; machine name: 	field_variables_missing_value; type: 	Key / Value (plain)
    		* label: Precision; machine name: 	field_variables_precision; type: 	Number (float)
    		* label: Type; machine name: 	field_variables_type; type: 	List (text)
    			* add list items: 'physical|physical', 'code|code', 'date|date'
    		* label: Unit; machine name: 	field_variables_unit; type: 	Entity reference
    1. Run R script 
    1. In the migration YML file make sure the path to that csv file is set correctly
    1. On the commandline inside the webroot of the new D8 website run 
    
    `drush cim -y --partial --source=modules/custom/deims_migrate/config/install/`
 
    `drush migrate:import deims_csv_variables`
    
    1. Make sure everything looks as expected. Variables have two dependencies, units and code/definitions.
    
1. __Determine the sequence in which variables should appear in each data source__ This is done on the variables export file, but is needed for the data source migration. Use R script [datasourceVariablesReference.R](https://github.com/lter/Deims7-8-Migration/blob/master/R%20scripts/datasourceVariablesReference.R)
