SELECT
node.nid,
node.vid,
node.type,
node.`language`,
node.title,
node.uid,
node.`status`,
node.created,
node.`changed`,
node.`comment`,
node.promote,
node.sticky,
node.tnid,
node.translate,
field_data_field_description.field_description_value,
field_data_field_description.field_description_format,
field_data_field_elevation.field_elevation_value,
field_data_field_coordinates.field_coordinates_left,
field_data_field_coordinates.field_coordinates_top,
field_data_field_coordinates.field_coordinates_right,
field_data_field_coordinates.field_coordinates_bottom
FROM
node
LEFT OUTER JOIN field_data_field_coordinates ON node.nid = field_data_field_coordinates.entity_id
LEFT OUTER JOIN field_data_field_description ON node.nid = field_data_field_description.entity_id
LEFT OUTER JOIN field_data_field_elevation ON node.nid = field_data_field_elevation.entity_id
WHERE
node.type = "research_site"