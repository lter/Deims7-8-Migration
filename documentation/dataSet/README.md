# Data Sets

1. Create new content type 'associated Party', machine name: associated_party
    1. Navigate in your D8 website to /admin/structure/types
    1. Add Content type
    1. Add needed fields 
    	* label: Person; machine name: field_assoc_party_person; type: Entity reference 	
    	* label: Role; machine name: field_assoc_party_role; type: Text (plain)
    1. Use [SQL script](https://github.com/lter/Deims7-8-Migration/blob/master/SQLexport_queries/exportAssocParty.sql) to export file exportAssocParty.csv
    1. Add a nid column with appropriate IDs - it's a new content type, so make sure it fits into the nid scheme of the site
    1. On the commandline inside the webroot of the new D8 website run:
    
    `drush cim -y --partial --source=modules/custom/deims_migrate/config/install/`
    	
	`drush migrate:import deims_csv_assocParty`

1. Create new content type 'Data Set', machine name: data_set
    1. Navigate in your D8 website to /admin/structure/types
    1. Add Content type
    1. Add needed fields 
    	* label: Abstract; machine name: field_data_set_abstract; type: Text (plain, long)
    	* label: Additional Information; machine name: field_data_set_addl_info; type: Text (plain, long)
    	* label: Associated Party; machine name: field_data_set_assoc_party; type: Entity reference
    	* label: Contact; machine name: field_data_set_contact; type: Entity reference
    	* label: Core Areas; machine name: field_data_set_core_areas; type: Entity reference
    	* label: Creator; machine name: field_data_set_creator; type: Entity reference
    	* label: Dataset ID; machine name: field_data_set_id; type: Text (plain
    	* label: Data Sources; machine name: field_data_set_data_sources; type: Entity reference
    	* label: Date Range; machine name: field_data_set_date_range; type: Date range 
    	* label: DOI; machine name: field_data_set_doi; type: Text (plain)
    	* label: Instrumentation; machine name: field_data_set_instrumentation; type: Text (plain, long)
    	* label: LTER Keywords; machine name: field_data_set_lter_keywords; type: Entity reference
    	* label: Maintenance; machine name: field_data_set_maintenance; type: Text (plain, long)
    	* label: Metadata Provider; machine name: field_data_set_mdata_prov; type: Entity reference
    	* label: Methods; machine name: field_data_set_methods; type: Text (plain, long)
    	* label: NTL Keyword; machine name: field_data_set_ntl_keyword; type: Entity reference
    	* label: NTL Themes; machine name: field_data_set_ntl_themes; type: Entity reference
    	* label: Project; machine name: field_data_set_project; type: Entity reference
    	* label: Publication Date; machine name: field_data_set_pub_date; type: Date
    	* label: Publisher; machine name: field_data_set_publisher; type: Entity reference
    	* label: Purpose; machine name: field_data_set_purpose; type: Text (plain, long)
    	* label: Quality Assurance; machine name: field_data_set_quality_assurance; type: Text (plain, long)
    	* label: Related Projects; machine name: field_data_set_related_projects; type: Entity reference
    	* label: Related Sites; machine name: field_data_set_related_sites; type: Entity reference
    	* label: Short Name; machine name: field_data_set_short_name; type: Text (plain)
    1. On the commandline inside the webroot of the new D8 website run `drush migrate:import deims_nodes_dataset` 
    
1. Connect associated party to datasets
	1. Use [SQL script](https://github.com/lter/Deims7-8-Migration/blob/master/SQLexport_queries/exportDatasetIDs.sql) to export nid/vid mappings for data sets
	1. Run [datasetAssocPartyRelation.R](https://github.com/lter/Deims7-8-Migration/blob/master/R%20scripts/datasetAssocPartyRelation.R) to generate table
	1. Manually upload data to table node__field_data_set_assoc_party 
