SELECT
node_field_data.nid,
node_field_data.vid,
migrate_map_deims_nodes_dsource.sourceid1
FROM
node_field_data
INNER JOIN migrate_map_deims_nodes_dsource ON node_field_data.nid = migrate_map_deims_nodes_dsource.destid1
WHERE
node_field_data.type = "data_source"