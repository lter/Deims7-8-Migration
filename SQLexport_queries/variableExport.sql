/*
*  This query parses the BLOB fields in which the variable (attribute) information is currently stored
*  After exporting it needs to be fully parsed via R scripts.
*/

SELECT
field_data_field_variables.entity_id,
field_data_field_variables.field_variables_name,
field_data_field_variables.field_variables_label,
field_data_field_variables.field_variables_definition,
field_data_field_variables.field_variables_type,
CONVERT(field_data_field_variables.field_variables_data USING utf8) as variables_data,
CONVERT(field_data_field_variables.field_variables_missing_values USING utf8) as variables_missing_values,
field_data_field_variables.field_variables_id
FROM
field_data_field_variables
ORDER BY
field_data_field_variables.field_variables_type ASC