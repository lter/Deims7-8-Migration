<?php

namespace Drupal\deims_data_search\Plugin\views\filter;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Filters by phase or status of project.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("deims_family_name_filter")
 */
class DeimsFamilyNameFilter extends InOperator {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
        parent::init($view, $display, $options);
        $this->valueTitle = t('Author Last Name');
        $this->definition['options callback'] = array($this, 'generateOptions');
      }
  /**
  * Override the query so that no filtering takes place if the user doesn't
  * select any options.
  */
  public function query() {
  	  if (!empty($this->value)) {
          parent::query();
      }
  }
  
  /**
  * Skip validation if no options have been chosen so we can use it as a
  * non-filter.
  */
  public function validate() {
  	  if (!empty($this->value)) {
          parent::validate();
      }
  }
  
  /**
  * Helper function that generates the options.
  * @return array
  */
  public function generateOptions() {
  	  // Array keys are used to compare with the table field values.
  	  $options = array();
  	  $database = \Drupal::database();
  	  $query = $database->select('node_field_data', 'n');
  	  $query->fields('n', ['title']);
  	  $query->condition('type', 'person', '=');
  	  $query->leftJoin('node__field_name_family', 'fn', 'fn.entity_id = n.nid');
  	  $query->innerJoin('node__field_data_set_creator', 'dsc', 'dsc.field_data_set_creator_target_id = n.nid');
  	  $query->addField('fn', 'field_name_family_value');
  	  $query->orderBy('fn.field_name_family_value');
  	  
  	  $result = $query->execute()->fetchAll();
  	  //print_r($result);
  	  foreach($result as $record){
  	  	  $options[$record->title] = $record->title; 
  	  }
  	  return $options;
  }

}