<?php

namespace Drupal\deims_data_explorer\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\node\Entity\Node;
use Drupal\Core\Database;

/**
 * Implements an deims_data_explorer form.
 */
 class deims_data_explorerForm extends FormBase {
 	 
 	 /**
 	 * {@inheritdoc}
 	 */
 	 public function getFormId() {
 	 	 return 'deims_data_explorer_form';
 	 }
 	 
 	 /**
 	 * {@inheritdoc}
 	 */
 	 public function buildForm(array $form, FormStateInterface $form_state, $nodeid = NULL) {
 	 	 // load the node to read out field contents
 	 	 $node = node::load($nodeid);
 	 	 
 	 	 //make sure it's the right content type
 	 	 if ($node->bundle() != 'data_source') {
 	 	 	 throw new NotFoundHttpException();
 	 	 }
 	 	 
 	 	 $node_title = $node->getTitle();
 	 	 $table_name = $node->get('field_dsource_de_table')->getString();
 	 	 
 	 	 // get the variables
 	 	 $variables = $node->get('field_dsource_variables')->referencedEntities();
 	 	 
 	 	 // print a few debug messages out
 	 	 $form['debug_messages'] = array(
 	 	 	 '#value' => "My Value Goes Here",
 	 	 	 );
 	 	 
 	 	 
 	 	 // START: Create the table to select columns.
 	 	 $form['columns_set'] = array(
 	 	 	 '#type' => 'fieldset',
 	 	 	 '#title' => t('Please select what columns you would like to include from '.$node_title),
 	 	 	 );
 	 	 
 	 	 $header  = array(
 	 	 	 'name' => t('Column'),
 	 	 	 'definition' => t('Definition'),
 	 	 	 'unit' => t('Unit'),
 	 	 	 );
 	 	 
 	 	 $options = array();
 	 	 foreach ($variables as $variable) {
 	 	 	 $unit = $variable -> get('field_variables_unit') -> referencedEntities();
 	 	 	 $unit_name = '';
 	 	 	 if (!empty($unit)) {
 	 	 	 	 //drupal_set_message(print_r($unit));
 	 	 	 	 //$unit_name = count($unit[0]);
 	 	 	 	 $unit_name = $unit[0] ->getTitle();
 	 	 	 }
 	 	 	 
 	 	 	 $options[$variable -> getTitle()] = array(
 	 	 	 	 'name' => $variable -> getTitle(),
 	 	 	 	 'definition' => $variable -> get('field_variables_definition') -> getString(),
 	 	 	 	 'unit' => $unit_name,
 	 	 	 	 );
 	 	 }
 	 	 $form['columns_set']['columns_table'] = array(
 	 	 	 '#type' => 'tableselect',
 	 	 	 '#header' => $header,
 	 	 	 '#options' => $options,
 	 	 	 '#empty' => t('No columns found'),
 	 	 	 '#required' => TRUE,
 	 	 	 );
 	 	 

 	 	 // END: Create the table to select columns.
 	 	 
 	 	 // figure out which variables are to be used as filters
 	 	 $filterVariables = [];
 	 	 foreach ($variables as $variable) {
 	 	 	 $asFilter = $variable->get('field_variable_de_filter')->getString();
 	 	 	 if ($asFilter == '1'){
 	 	 	 	 $filterVariables[] = $variable;
 	 	 	 }
 	 	 }
 	 	 
 	 	 // START: Create the table to select filters.
 	 	 $form['filter_set'] = array(
 	 	 	 '#type' => 'fieldset',
 	 	 	 '#title' => t('Please choose any filters you would like to add.'),
 	 	 	 );
 	 	 

 	 	 // for each variable figure out what type it is and then create the appropiate input fields
 	 	 $options = array();
 	 	 foreach ($filterVariables as $variable) {
 	 	 	 $variable_type = $variable->get('field_variables_type')->getString();
 	 	 	 if ($variable_type == '') {
 	 	 	 	 $variable_type = 'nominal';
 	 	 	 } //end if
 	 	 	 
 	 	 	 $name = $variable->getTitle();
 	 	 	 
 	 	 	 //when the type is code
 	 	 	 if($variable_type == 'codes'){
 	 	 	 	 
 	 	 	 	 $codes = $variable->get('field_variables_code_definition')->referencedEntities();
 	 	 	 	 
 	 	 	 	 foreach ($codes as $code) {
 	 	 	 	 	 $code_list = $code->get('field_variable_code_definition');
 	 	 	 	 	 foreach ($code_list as $key_value){
 	 	 	 	 	 	 $key = $key_value->get('key')->getString();
 	 	 	 	 	 	 $code_options[$key] = $key_value->get('value')->getString();
 	 	 	 	 	 } //end foreach
 	 	 	 	 } //end foreach
 	 	 	 	 
 	 	 	 	 $form['filter_set']['filters_' . $name] = array(
 	 	 	 	 	 '#type' => 'select',
 	 	 	 	 	 '#title' => t("Limit by $name:"),
 	 	 	 	 	 '#options' => $code_options,
 	 	 	 	 	 //'#name' => 'filters_' . $name,
 	 	 	 	 	 '#multiple' => TRUE,
 	 	 	 	 	 '#attributes' => array('multiple' => 'multiple')
 	 	 	 	 	 );
 	 	 	 	 	 
 	 	 	 } //end if code
 	 	 	 
 	 	 	 //when the type is numeric (physical)
 	 	 	 if($variable_type == 'physical'){
 	 	 	 	 
 	 	 	 	 if (strlen($variable->get('field_variables_minimum')->getString()) && strlen($variable->get('field_variables_maximum')->getString())) {
 	 	 	 	 	 $minimum = $variable->get('field_variables_minimum')->getString();
 	 	 	 	 	 $maximum = $variable->get('field_variables_maximum')->getString();
 	 	 	 	 	 $physical_options = array();
 	 	 	 	 	 foreach (range($minimum, $maximum) as $number) {
 	 	 	 	 	 	 $physical_options[$number] = $number;
 	 	 	 	 	 }
 	 	 	 	 	 $form['filter_set']['filters_' . $name . '_min'] = array(
 	 	 	 	 	 	 '#type'          => 'select',
 	 	 	 	 	 	 '#title'         => t("Choose a minimum $name:"),
 	 	 	 	 	 	 '#options'       => array('' => t('- NA -')) + $physical_options,
 	 	 	 	 	 	 '#default_value' => $minimum,
 	 	 	 	 	 	 //'#name' => 'filters_' . $variable->getTitle() . '_min',
 	 	 	 	 	 	 );
 	 	 	 	 	 $form['filter_set']['filters_' . $name . '_max'] = array(
 	 	 	 	 	 	 '#type'          => 'select',
 	 	 	 	 	 	 '#title'         => t("Choose a maximum $name:"),
 	 	 	 	 	 	 '#options'       => array('' => t('- NA -')) + $physical_options,
 	 	 	 	 	 	 '#default_value' => $maximum,
 	 	 	 	 	 	 //'#name' => 'filters_' . $variable->getTitle() . '_max',
 	 	 	 	 	 	 );
 	 	 	 	 }
 	 	 	 	 else {
 	 	 	 	 	 $form['filter_set']['filters_' . $name . '_min'] = array(
 	 	 	 	 	 	 '#type'          => 'textfield',
 	 	 	 	 	 	 '#title'         => t("Choose a minimum $name:"),
 	 	 	 	 	 	 //'#name' => 'filters_' . $variable->getTitle() . '_min',
 	 	 	 	 	 	 '#size' => 10,
 	 	 	 	 	 	 );
 	 	 	 	 	 $form['filter_set']['filters_' . $name . '_max'] = array(
 	 	 	 	 	 	 '#type'          => 'textfield',
 	 	 	 	 	 	 '#title'         => t("Choose a maximum $name:"),
 	 	 	 	 	 	 //'#name' => 'filters_' . $variable->getTitle() . '_max',
 	 	 	 	 	 	 '#size' => 10,
 	 	 	 	 	 	 );
 	 	 	 	 }
 	 	 	 	 
     		 }//end if physical
     		 
     		 if($variable_type == 'date'){
     		 	  	 	 	 	 
 	 	 	 	 $form['filter_set']['filters_' . $name . '_min'] = array(
 	 	 	 	 	 '#type'          => 'date',
 	 	 	 	 	 '#title'         => t("Choose a minimum $name:"),
 	 	 	 	 	 //'#name' => 'filters_' . $variable->getTitle() . '_min',
 	 	 	 	 	 '#size' => 30,
 	 	 	 	 	 );
 	 	 	 	 $form['filter_set']['filters_' . $name . '_max'] = array(
 	 	 	 	 	 '#type'          => 'date',
 	 	 	 	 	 '#title'         => t("Choose a maximum $name"),
 	 	 	 	 	 //'#name' => 'filters_' . $variable->getTitle() . '_max',
 	 	 	 	 	 '#size' => 30,
 	 	 	 	 	 );
 	 	 	 	 
     		 	 
     		 }//end if date
     	 
 	 	 } //end foreach filter variables
 	 	 
 	 	 // END: Create the table to select filters.
 	 	 
 	 	 //hidden table name information
 	 	 
 	 	 $form['table_name'] = array(
 	 	 	 '#type'	=> 'value',
 	 	 	 '#value'	=> $table_name,
 	 	 	 );
 	 	 
 	 	 
 	 	 // Submit buttons
 	 	 $form['actions']['#type'] = 'actions'; // group two submit buttons
 	 	 $form['actions']['submit'] = [
 	 	 '#type' => 'submit',
 	 	 '#value' => $this->t('Download'),
 	 	 '#button_type' => 'primary',
 	 	 ];
 	 	 $form['actions']['preview'] = [
 	 	 '#type' => 'submit',
 	 	 '#value' => $this->t('Preview'),
 	 	 '#button_type' => 'primary',
 	 	 ];
 	 	 $form['actions']['reset'] = [
 	 	 '#type' => 'submit',
 	 	 '#value' => $this->t('Reset'),
 	 	 '#button_type' => 'primary',
 	 	 ];
 	 	 
 	 	 if($form_state->getValue('op') == 'Preview'){

 	 	 	 $form['table'] = array(
 	 	 	 	 	 '#theme' => 'table',
 	 	 	 	 	 '#caption' => $this -> t('showing a max of 50 rows'),
 	 	 	 	 	 '#header' => $form_state->getValue('header'), 
 	 	 	 	 	 '#rows' => $form_state->getValue('rows'),
 	 	 	 	 );
 	 	 }
 	 	 	 
 	 	 	 return $form;
 	 } // end function build form
 	 
 	 
 	 /**
 	 * {@inheritdoc}
 	 */
 	 public function validateForm(array &$form, FormStateInterface $form_state) {
 	 	 
 	 	 $columns_array = $form_state->getValue('columns_table');
 	 	 $nothing_chosen = 0;
 	 	 foreach ($columns_array as $key => $value) {
 	 	 	 if($value <> '0'){
 	 	 	 	 $nothing_chosen = 1;
 	 	 	 }
 	 	 }
 	 	 if($nothing_chosen == 0){
 	 	 	 $form_state->setErrorByName('lakeid',$this->t('Please select at least one column.'));
 	 	 }
 	 	 
 	 	  	 	 
 	 } //end function validate form
 	 
 	 /**
 	 * {@inheritdoc}
 	 */
 	 public function submitForm(array &$form, FormStateInterface $form_state) {
 	 	 

		 $table_name = $form_state->getValue('table_name');
 	 	 $fields = array();
 	 	 
 	 	 foreach ($form_state->getValue('columns_table') as $key => $value) {
 	 	 	 if(!empty($value)){ 
 	 	 	 	 array_push($fields, $value);
 	 	 	 }
 	 	 }
 	 	 
 	 	 try{
 	 	 	 $connection = \Drupal\Core\Database\Database::getConnection('dbmaker', 'dbmaker');
 	 	 	 
 	 	 	 $query = $connection->select($table_name, 'tn')
 	 	 	 ->fields('tn', $fields);
 	 	 	 
 	 	 	 
 	 	 	 foreach ($form_state->getValues() as $key => $value) {
 	 	 	 	 if(strpos($key, 'filters') !== false){
 	 	 	 	 	 $column_name = substr($key, 8, strlen($key));
 	 	 	 	 	 if(!empty($value) && !stristr($key, 'max') && !stristr($key, 'min')){
 	 	 	 	 	 	 $orGroup = $query->orConditionGroup();
 	 	 	 	 	 	 foreach($form_state->getValue($key) as $key => $value){
 	 	 	 	 	 	 	 $orGroup->condition($column_name, $value, '=');
 	 	 	 	 	 	 }
 	 	 	 	 	 	 $query->condition($orGroup);
 	 	 	 	 	 } elseif(!empty($value)) {
 	 	 	 	 	 	 $column_name = substr($column_name, 0, -4);
 	 	 	 	 	 	 if(strpos($key, 'max') !== false) {
 	 	 	 	 	 	 	 $query->condition($column_name, $value, '<=');
 	 	 	 	 	 	 } elseif(strpos($key, 'min') !== false) {
 	 	 	 	 	 	 	 $query->condition($column_name, $value, '>=');
 	 	 	 	 	 	 }
 	 	 	 	 	 }
 	 	 	 	 }
 	 	 	 }
 	 	 	 
 	 	 	 //drupal_set_message(dump($query->__toString()));
 	 	 	 
 	 	 	 $result = $query->execute();
 	 	 	 
 	 	 	 $data_table = array();
 	 	 	 $header = array();
 	 	 	 
 	 	 	 foreach($result as $record){
 	 	 	 	 $record = $result->fetchAssoc();
 	 	 	 	 if(is_array($record)){
 	 	 	 	 	 $header = array_keys($record);
 	 	 	 	 	 $data_row = array_values($record);
 	 	 	 	 	 array_push($data_table, $data_row);
 	 	 	 	 }
 	 	 	 }
  	 	 }
 	 	 catch (Exception $e) {
 	 	 	 watchdog_exception('deims_data_explorer', $e);
 	 	 	 // Do not rethrow the exception. Fail gracefully by returning nothing.
 	 	 }
 	 	  	 	 
 	 	 if($form_state->getValue('op') == 'Preview'){
 	 	 	 
 	 	 	 $rows = array_slice($data_table, 0, 50);
 	 	 	 
 	 	 	 $markup = array(
 	 	 	 	 '#theme' => 'table', 
 	 	 	 	 '#header' => $header,
 	 	 	 	 '#rows' => $rows,
 	 	 	 	 );
 	 	 	 
 	 	 	  	 	 	 
 	 	 	 $form_state->setValue('header', $header);
 	 	 	 $form_state->setValue('rows', $data_table);
 	 	 	 $form_state->setRebuild(true);
 	 	 	 
 	 	 } //end if preview
 	 	 
 	 	 if($form_state->getValue('op') == 'Reset'){
 	 	 	 
 	 	 	 $form_state->setUserInput([]);
 	 	 	 
 	 	 } //end if reset
 	 	 
 	 	 if($form_state->getValue('op') == 'Download') {
 	 	 	 
 	 	 	 $header_csv = '';
 	 	 	 $file_content = '';
 	 	 	 
 	 	 	 foreach($header as $key => $value){
 	 	 	 	 $header_csv = $header_csv . $value . ',';
 	 	 	 }
 	 	 	 
 	 	 	 $header_csv = substr($header_csv, 0, -1);
 	 	 	 $header_csv = $header_csv . PHP_EOL;
 	 	 	 
 	 	 	 $file_content = $header_csv;
 	 	 	 
 	 	 	 foreach($data_table as $row){
 	 	 	 	 $row_csv = '';
 	 	 	 	 foreach($row as $key => $value){
 	 	 	 	 	 $row_csv = $row_csv . $value . ',';
 	 	 	 	 }
 	 	 	 	 $row_csv = substr($row_csv, 0, -1);
 	 	 	 	 $row_csv = $row_csv . PHP_EOL;
 	 	 	 	 $file_content = $file_content . $row_csv;
 	 	 	 }
 	 	 	 
 	 	 	 $response = new Response($file_content);
 	 	 	 $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $table_name.'.csv');
 	 	 	 $response->headers->set('Content-type', 'text/csv; utf-8');
 	 	 	 $response->headers->set('Content-Disposition', $disposition);
 	 	 	 $response->headers->set('Content-Transfer-Encoding', 'binary');
 	 	 	 $response->headers->set('Content-length', strlen($file_content));
 	 	 	 $form_state->setResponse($response);
 	 	 	 
 	 	 } //end if download
 	 	 
 	 } //end function submit form
 	 	 
 }
