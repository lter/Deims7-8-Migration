SELECT
migrate_map_deims_csv_varcodedef.sourceid1 AS link_id,
node_field_data.vid,
node_field_data.nid AS entity_id
FROM
node_field_data
INNER JOIN migrate_map_deims_csv_varcodedef ON node_field_data.nid = migrate_map_deims_csv_varcodedef.destid1
WHERE
node_field_data.type = "variable_codes"