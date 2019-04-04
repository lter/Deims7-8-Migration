# Convert DEIMS7 variables (attributes) Code/Definition to Drupal8 content type

The process of converting DEIMS7 variables entities into content types involves several steps which are outlined in this document.  The EML code/definition fields need their own content type set up in Drupal8.  The csv file to generate the nodes is built and then in a manual data import directly into the D8 database the actual code/definitions are added to the nodes. (This may be possible more elegantly with another YML file, but it works as described here.)

1. __Query DEIMS7 MySQL database and export all information from variables as csv table__ The first step is to query the DEIMS7 MySQL database, export the information. Use SQL script   [variableExport.sql](https://github.com/lter/Deims7-8-Migration/blob/master/SQLexport_queries/variableExport.sql) and save as variableExport.csv

1. __Attributes of the enumeratedDomain type__ The information encoded in the BLOB field needs to be parsed into key/value pairs. Use R code parseVariablesCodeDefinition.R
    1. Create content type Variable Codes machine name: variable_codes
    	1. Navigate in your D8 website to /admin/structure/types
    	1. Add Content type
    	1. Add Fields: 
    	label | machine name | type
    	----------------|--------------------------------|------------------
    	Code Definition | field_variable_code_definition | Key/value (plain)
    	
    1. Run the first part of the script up to saving upload_code_def_node.csv
    1. On the commandline in the webroot run `drush migrate:import deims_csv_varcodedef`
    1. Export ID that Drupal has assigned with SQL script export... save as nid_vid_mapping.csv
    1. Run the second part of the R script
    1. Manually import file upload_code_def_values.csv into D8 table node__field_variable_code_definition
    
1. __Attributes of the physicalDomain type__ Specifically the units need to be turned into a content type.
	1. Create content type 
    

