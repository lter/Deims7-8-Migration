<?php

/**
 * Implements hook_views_data_alter().
 */
function deims_data_search_views_data_alter(array &$data) {

  $data['node_field_data']['family_name'] = array(
    'title' => t('Dataset Author Filter'),
    'group' => t('Content'),
    'filter' => array(
      'title' => t('Dataset Author Filter'),
      'help' => t('Specify a list of authors for whom to find data sets.'),
      'field' => 'title',
      'id' => 'deims_family_name_filter'
    ),
  );
  
}