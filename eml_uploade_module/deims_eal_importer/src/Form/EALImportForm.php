<?php
/**
 * @file
 * Contains \Drupal\deims_eal_importer\Form\EALImportForm.
 */
namespace Drupal\deims_eal_importer\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\Core\Archiver\Zip;
use Drupal\Core\Archiver\ArchiverException;



class EALImportForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eal_import_form';
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['description'] = array(
      '#markup' => '<p>Use this form to upload a ZIP file containing EML and csv data files</p>',
    );

    $form['import_zip'] = array(
    	'#type' => 'managed_file',
    	'#title' => t('Upload file here'),
    	'#upload_location' => 'public://eal_imports/',
    	'#default_value' => '',
    	'#upload_validators'  => array('file_validate_extensions' => array('zip')),
    	'#states' => array(
    		'visible' => array(
    			':input[name="File_type"]' => array('value' => t('Upload Your File')),
    			),
    		),
    	);
    
    $form['actions']['#type'] = 'actions';


    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Upload ZIP'),
      '#button_type' => 'primary',
    );

    return $form;
  }
  
  /**
  * {@inheritdoc}
  */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Fetch the array of the file stored temporarily in database
    $zip_file = $form_state->getValue('import_zip');

    // Load the object of the file by it's fid
    $file = File::load( $zip_file[0] );
    
    // Save the file temporarily in database
    $file->save();
    
    $file_system = \Drupal::service('file_system');
    
    //check that the zip file can be extracted
    $zip = new Zip($file_system->realpath($file->getFileUri()));
    
    if($zip->extract($file_system->realpath('public://eal_imports'))) {
    	$files = $zip->listContents();
    	\Drupal::messenger()->addMessage('check that the zip file can be extracted');
    	$zip->remove($file_system->realpath($file->getFileUri()));
    } else {
    	$form_state->setErrorByName('import_zip', $this->t('Could not extract zip file'));
    }
    
    //check that there is an xml file
    if(!preg_grep("/xml$/i", $files)){
    	$form_state->setErrorByName('import_zip', $this->t('No EML file was found'));
    }
    \Drupal::messenger()->addMessage('EML file is in zip');
    //check that the dataset ID is unique
    //get the eml file
    $metadata = preg_grep("/xml$/i", $files);
    $metadatafile = array_shift($metadata);
    $path_to_eml = $file_system->realpath('public://eal_imports/'.$metadatafile);
    $eml_string = file_get_contents($path_to_eml);
    $eml_string = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $eml_string);
    $eml = simplexml_load_string($eml_string);
    //find the dataset id
    $packageid = (string)$eml['packageId'];
    list($domain, $datasetid, $version) = explode(".", $packageid);
    //check the database if it exists
    
    $datasetid_query = \Drupal::entityQuery('node')
    ->condition('type', 'data_set')
    ->condition('field_data_set_id', $datasetid, "=")
    ->execute();
    
    if(empty($datasetid_query)){
    	\Drupal::messenger()->addMessage('dataset ID is new and unique');
    } else {
    	$form_state->setErrorByName('import_zip', $this->t('This dataset ID already exists'));
    }
  }
  
  /**
  * {@inheritdoc}
  */
  public function submitForm(array &$form, FormStateInterface $form_state) {
 	
    // Fetch the array of the file stored temporarily in database
    $zip_file = $form_state->getValue('import_zip');

    // Load the object of the file by it's fid
    $file = File::load( $zip_file[0] );

    //unzip 
    $file_system = \Drupal::service('file_system');
    $zip = new Zip($file_system->realpath($file->getFileUri()));
    $zip->extract($file_system->realpath('public://eal_imports'));
    $files = $zip->listContents();
    $zip->remove($file_system->realpath($file->getFileUri()));
    
    //get the metadata file
    $metadata = preg_grep("/xml$/i", $files);
    $metadatafile = array_shift($metadata);
    $path_to_eml = $file_system->realpath('public://eal_imports/'.$metadatafile);
    // \Drupal::messenger()->addMessage($path_to_eml);
    
    //parse the xml file
    $eml_string = file_get_contents($path_to_eml);
    //get the : out SimpleXML doesn't seem to like them
    $eml_string = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $eml_string);
    $eml = simplexml_load_string($eml_string);
    
    //example testing
    $title = (string)$eml['packageId'];
    // \Drupal::messenger()->addMessage($title);
    
    $new_dataset = $this->dataset_node($eml);
    
    \Drupal::messenger()->addMessage('New dataset ID is: '.$new_dataset);
    
 }

  public function dataset_node($eml){
  	  //basic dataset info
  	  $dataset['type'] = 'data_set';
  	  $dataset['title'] = (string)$eml->dataset->title;
  	  $dataset['field_data_set_abstract'] = $this->get_text_type($eml->dataset->abstract);
  	  $dataset['field_data_set_id'] = $this->get_datasetid($eml);
  	  $dataset['field_data_set_version_number'] = $this->get_version($eml);
  	  
  	  $temp = $eml->dataset->coverage->temporalCoverage->children();
  	  
  	  if($temp->rangeOfDates) {
  	  	  //\Drupal::messenger()->addMessage('range of dates');
  	  	  $startdate = (string)$temp->rangeOfDates->beginDate->calendarDate.'T10:00:00';
  	  	  $enddate = (string)$temp->rangeOfDates->endDate->calendarDate.'T10:00:00';
  	  	  $dataset['field_data_set_date_range']['value'] = $startdate;
  	  	  $dataset['field_data_set_date_range']['end_value'] = $enddate;
  	  }
  	   	  
  	  $dataset_node = Node::create($dataset);
  	  $dataset_node->setPublished(TRUE);
  	  $dataset_node->save();
  	  $dataset_node_id = $dataset_node->nid->value;
  	  
  	  foreach($eml->dataset->creator as $creator) {
  	  	  $creator_nid = $this->get_person($creator);
  	  	  $dataset_node->field_data_set_creator[] = ['target_id' => $creator_nid];
  	  	  $dataset_node->save();
  	  }
  	  
  	  foreach($eml->dataset->keywordSet as $keyword_set) {
  	  	  if($keyword_set->keywordThesaurus == 'LTER Controlled Vocabulary') {
  	  	  	  foreach($keyword_set->keyword as $lter_keyword) {
  	  	  	  	  $lter_keyword_tid = $this->get_lter_keyword($lter_keyword);
  	  	  	  	  $dataset_node->field_data_set_lter_keywords[] = ['target_id' => $lter_keyword_tid];
  	  	  	  	  $dataset_node->save();
   	  	  	  }
  	  	  } else {
  	  	  	  foreach($keyword_set->keyword as $keyword) {
  	  	  	  	  $keyword_tid = $this->get_keyword($keyword);
  	  	  	  	  $dataset_node->field_data_set_keywords[] = ['target_id' => $keyword_tid];
  	  	  	  	  $dataset_node->save();
   	  	  	  }
  	  	  }
  	  }
  	  
  	  foreach($eml->dataset->methods->methodStep->description as $method_step) {
  	  	  $dataset_node->field_data_set_methods[] = $this->get_text_type($method_step);
  	  	  $dataset_node->save();
  	  }
  	    	  
  	  foreach($eml->dataset->dataTable as $data_source) {
  	  	  $data_source_nid = $this->get_data_source($data_source);
  	  	  $dataset_node->field_data_set_data_sources[] = ['target_id' => $data_source_nid];
  	  	  $dataset_node->save();
  	  }
  	  
  	  foreach($eml->dataset->coverage->geographicCoverage as $research_site) {
  	  	  $research_site_nid = $this->get_research_site($research_site);
  	  	  $dataset_node->field_data_set_related_sites[] = ['target_id' => $research_site_nid];
  	  	  $dataset_node->save();
  	  	  
  	  }
  	  
  	  return $dataset_node_id;
  	  
  }
  
  public function get_datasetid($eml) {
  	  $packageid = (string)$eml['packageId'];
  	  list($domain, $datasetid, $version) = explode(".", $packageid);
  	  return $datasetid;
  }
  
  public function get_version($eml) {
  	  $packageid = (string)$eml['packageId'];
  	  list($domain, $datasetid, $version) = explode(".", $packageid);
  	  return $version;
  	  
  }
  
  public function get_person($person) {
  	  
  	  $person_data['type'] = 'person';
  	  
  	  if((string)$person->organizationName) {
  	  	  
  	  	  //first get the organization
  	  	  $organization_data['type'] = 'organization';
  	  	  $organization_data['title'] = (string)$person->organizationName;
  	  	  
  	  	  //check if it exists
  	  	  $organization_query = \Drupal::entityQuery('node')
  	  	  ->condition('type', 'organization')
  	  	  ->condition('title', (string)$person->organizationName, "=")
  	  	  ->execute();
  	  	  
  	  	  if(empty($organization_query)){
  	  	  	  $organization_node = Node::create($organization_data);
  	  	  	  $organization_node->setPublished(TRUE);
  	  	  	  $organization_node->save();
  	  	  	  $organization_nid = $organization_node->nid->value;
  	  	  } else {
  	  	  	  $organizations = array_values($organization_query);
  	  	  	  $organization_nid = $organizations[0];
  	  	  }
  	  	  
  	  	  $person_data['field_organization']['target_id'] = $organization_nid;
  	  }
  	  
  	  $title = (string)$person->individualName->givenName[0]." ".(string)$person->individualName->surName;
  	  $title = str_replace("  ", " ", $title);
  	  $person_data['title'] = $title;
  	  $person_data['field_name_given'] = (string)$person->individualName->givenName[0];
  	  if ((string)$person->individualName->givenName[1]) {
  	  	  $person_data['field_name_middle'] = (string)$person->individualName->givenName[1];
  	  }
  	  $person_data['field_name_family'] = (string)$person->individualName->surName;
  	  if ((string)$person->electronicMailAddress) {
  	  	  $person_data['field_person_email'] = (string)$person->electronicMailAddress;
  	  }
  	  if ((string)$person->userId) {
  	  	  $person_data['field_person_orcid']['uri'] = (string)$person->userId;
  	  	  $person_data['field_person_orcid']['title'] = preg_replace("/https:\/\/orcid.org\//", "", (string)$person->userId);
  	  }
  	  
  	  //echo(var_dump($person_data));
  	  	  
  	  $person_query = \Drupal::entityQuery('node')
  	  ->condition('type', 'person')
  	  ->condition('field_name_family', (string)$person->individualName->surName, '=')
  	  ->execute();
  	  
  	  if(empty($person_query)){
  	  	  $person_node = Node::create($person_data);
  	  	  $person_node->setPublished(TRUE);
  	  	  $person_node->save();
  	  	  $person_nid = $person_node->nid->value;
  	  } else {
  	  	  $person_query_data = array_values($person_query);
  	  	  $person_nid = $person_query_data[0];
  	  }
  	  
  	  return $person_nid;
  }
  
  public function get_lter_keyword($lter_keyword) {
  	  $vid = 'lter_controlled_vocabulary';
  	  $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
  	  ->loadByProperties(['name' => (string)$lter_keyword, 'vid' => $vid]);
  	  $term = reset($term);
  	  if (empty($term)) {
  	  	  $term = \Drupal\taxonomy\Entity\Term::create([
  	  	  		  'name' => (string)$lter_keyword,
  	  	  		  'vid' => $vid,
  	  	  		  ]);
  	  	  $term->save();
  	  }
  	  $lter_keyword_tid = $term->id();
  	  
  	  return $lter_keyword_tid;
  }

  public function get_keyword($keyword) {
  	  $vid = 'keywords';
  	  $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
  	  ->loadByProperties(['name' => (string)$keyword, 'vid' => $vid]);
  	  $term = reset($term);
  	  if (empty($term)) {
  	  	  $term = \Drupal\taxonomy\Entity\Term::create([
  	  	  		  'name' => (string)$keyword,
  	  	  		  'vid' => $vid,
  	  	  		  ]);
  	  	  $term->save();
  	  	  echo(' term has saved ');
  	  }
  	  $keyword_tid = $term->id();
  	  
  	  return $keyword_tid;
  }
  
  public function get_text_type($text) {
  	  $return_text = '';
  	  if($text->section->title){
  	  	  $return_text = $return_text.(string)$text->section->title.'<br/>';
  	  	  foreach($text->section->para as $para) {
  	  	  	  $return_text = $return_text.(string)$text->section->para.'<br/>';
  	  	  }
  	  }
  	  elseif($text->section->para) {
  	  	  foreach($text->section->para as $para) {
  	  	  	  $return_text = $return_text.(string)$text->section->para.'<br/>';
  	  	  }
  	  }
  	  elseif($text->para) {
  	  	  foreach($text->para as $para) {
  	  	  	  $return_text = $return_text.(string)$text->para.'<br/>';
  	  	  }
  	  }
  	  else {
  	  	  $return_text = $return_text.(string)$text.'<br/>';
  	  }
  	  
  	  return $return_text;
  }

  public function get_data_source($data_source) {
  	  
  	  $data_source_data['type'] = 'data_source';
  	  $data_source_data['title'] = (string)$data_source->entityName;
  	  $data_source_data['field_dsource_description'] = (string)$data_source->entityDescription;
  	  
  	  $ds_file_name = (string)$data_source->physical->objectName;
  	  
  	  //echo($ds_file_name);
  	  
  	  //$file_system = \Drupal::service('file_system');
  	  //$ds_file_path = $file_system->realpath('public://eal_imports/').$ds_file_name;
  	  $ds_file_path = 'public://eal_imports/'.$ds_file_name;
  	  
  	  //echo($ds_file_path);
  	  
  	  $ds_file = file_get_contents($ds_file_path);
  	  $ds_file_managed = file_save_data($ds_file, "public://data/".$ds_file_name);
  	  
  	  $data_source_data['field_dsource_file']['target_id'] = $ds_file_managed->fid->value;
  	  $data_source_data['field_dsource_file']['display'] = 1;
  	  $data_source_data['field_dsource_field_delimiter'] = (string)$data_source->physical->dataFormat->textFormat->simpleDelimited->fieldDelimiter;
  	  $data_source_data['field_dsource_quote_character'] = (string)$data_source->physical->dataFormat->textFormat->simpleDelimited->quoteCharacter;
  	  $data_source_data['field_dsource_header_lines'] = (string)$data_source->physical->dataFormat->textFormat->numHeaderLines;
  	  $data_source_data['field_dsource_record_delimiter'] = (string)$data_source->physical->dataFormat->textFormat->recordDelimiter;
  	  $data_source_data['field_dsource_orientation'] = (string)$data_source->physical->dataFormat->textFormat->attributeOrientation;
  	  $data_source_data['field_dsource_num_records'] = (int)$data_source->numberOfRecords;
  	  
  	  $data_source_node = Node::create($data_source_data);
  	  $data_source_node->setPublished(TRUE);
  	  $data_source_node->save();
  	  $data_source_nid = $data_source_node->nid->value;
  	  
  	  foreach($data_source->attributeList->attribute as $attribute) {
  	  	  $attribute_nid = $this->get_attribute($attribute);
  	  	  $data_source_node->field_dsource_variables[] = ['target_id' => $attribute_nid];
  	  	  $data_source_node->save();
  	  }
  	  
  	  return $data_source_nid;
  	  
  }
  
  public function get_attribute($attribute) {
  	  $attribute_data['type'] = 'variable';
  	  $attribute_data['title'] = (string)$attribute->attributeName;
  	  $attribute_data['field_variables_definition'] = (string)$attribute->attributeDefinition;
  	  //$attribute_data['field_variables_label'] = (string)$attribute->attributeLabel;
  	  
  	  if (!empty($attribute->measurementScale->ratio->unit)) {
  	  	  $attribute_data['field_variables_type'] = 'physical';
  	  	  
  	  	  $unit_title = '';
  	  	  if ($attribute->measurementScale->ratio->unit->standardUnit) {
  	  	  	  $unit_title = (string)$attribute->measurementScale->ratio->unit->standardUnit;
  	  	  } else {
  	  	  	  $unit_title = (string)$attribute->measurementScale->ratio->unit->customUnit;
  	  	  }
  	  	  $unit_query = \Drupal::entityQuery('node')
  	  	  ->condition('type', 'units')
  	  	  ->condition('title', $unit_title, "=")
  	  	  ->execute();
  	  	  
  	  	  $unit_nid = '';
  	  	  if(empty($unit_query)){
  	  	  	  $unit_data['type'] = 'units'; 
  	  	  	  $unit_data['title'] = $unit_title;
  	  	  	  $unit_node = Node::create($unit_data);
  	  	  	  $unit_node->setPublished(TRUE);
  	  	  	  $unit_node->save();
  	  	  	  $unit_nid = $unit_node->nid->value;
  	  	  }  else {
  	  	  	  $unit_query_data = array_values($unit_query);
  	  	  	  $unit_nid = $unit_query_data[0];
  	  	  }
  	  	  
  	  	  //\Drupal::messenger()->addMessage('unit nid: '.$unit_nid);
  	  	  
  	  	  $attribute_data['field_variables_unit'] = $unit_nid;
  	  	  $attribute_data['field_variables_missing_value']['key'] = (string)$attribute->missingValueCode->code;
  	  	  $attribute_data['field_variables_missing_value']['value'] = (string)$attribute->missingValueCode->codeExplanation;
  	  }
  	  
  	  elseif(!empty($attribute->measurementScale->dateTime)) {
  	  	  
  	  	  \Drupal::messenger()->addMessage('getting the date format');
  	  	  
  	  	  $attribute_data['field_variables_type'] = 'date';
  	  	  $attribute_data['field_variables_date_time_format'] = (string)$attribute->measurementScale->dateTime->formatString;
  	  }
  	  
  	  elseif(!empty($attribute->measurementScale->nominal->nonNumericDomain->enumeratedDomain)){
  	  	  $attribute_data['field_variables_type'] = 'codes';
  	  	  $code_data['type'] = 'variable_codes';
  	  	  $code_data['title'] = 'Code definition for '.(string)$attribute->attributeName;
  	  	  $code_node = Node::create($code_data);
 	  	  $code_node->setPublished(TRUE);
 	  	  $code_node->save();
 	  	  $code_nid = $code_node->nid->value;
 	  	  
  	  	  foreach($attribute->measurementScale->nominal->nonNumericDomain->enumeratedDomain->codeDefinition as $code_def) {
  	  		  
  	  	  	  $code_def_field = [
  	  	  	  	  'key' => (string)$code_def->code,
  	  	  	  	  'value' => (string)$code_def->definition,
  	  	  	  	  ];
  	  	  	  $code_node->field_variable_code_definition[] = $code_def_field;
  	  		  $code_node->save();
 	  	  }
 	  	  
 	  	  
  	  	  $attribute_data['field_variables_code_definition'] = $code_nid;
  	  }
  	  
  	  $attribute_node = Node::create($attribute_data);
  	  $attribute_node->setPublished(TRUE);
  	  $attribute_node->save();
  	  $attribute_nid = $attribute_node->nid->value;
  	  
  	  //\Drupal::messenger()->addMessage('saved the variable node: '.$attribute_nid);
  	  
  	  return $attribute_nid;
  	  
  }
  
  public function get_research_site($research_site) {
  	  
  	  $research_site_data['type'] = 'research_site';
  	  $title_parts = explode(",", (string)$research_site->geographicDescription);
  	  $title = $title_parts[0];
  	  $research_site_data['title'] = $title;
  	  $research_site_data['body'] = (string)$research_site->geographicDescription;
  	  $research_site_data['field_coord_bottom_latitude'] = (string)$research_site->boundingCoordinates->southBoundingCoordinate;
  	  $research_site_data['field_coord_top_latitude'] = (string)$research_site->boundingCoordinates->northBoundingCoordinate;
  	  $research_site_data['field_coord_left_longitude'] = (string)$research_site->boundingCoordinates->westBoundingCoordinate;
  	  $research_site_data['field_coord_rigth_longitude'] = (string)$research_site->boundingCoordinates->eastBoundingCoordinate;

  	  $research_site_query = \Drupal::entityQuery('node')
  	  ->condition('type', 'research_site')
  	  ->condition('title', $title, "=")
  	  ->execute();
  	  
  	  if(empty($research_site_query)){
  	  	  $research_site_node = Node::create($research_site_data);
  	  	  $research_site_node->setPublished(TRUE);
  	  	  $research_site_node->save();
  	  	  $research_site_nid = $research_site_node->nid->value;
  	  } else {
  	  	  $research_site_query_data = array_values($research_site_query);
  	  	  $research_site_nid = $research_site_query_data[0];
  	  	  
  	  }
  	  
  	  return $research_site_nid;
  }

}