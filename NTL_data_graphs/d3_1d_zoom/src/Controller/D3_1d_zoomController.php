<?php

namespace Drupal\d3_1d_zoom\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use core\lib\Drupal\Core\Database;

/**
 * Controller routines for d3_1d_zoom graphs.
 */
class D3_1d_zoomController extends ControllerBase {

  /**
   * 
   * @param string $lakeid
   *   The lake for which to draw the graph.
   * @param string $variable
   *   The parameter for which to draw the graph.
   */
   
   public function generate($lakeid,$variable) {
   	   
   	   $returned_data = array();
   	   $returned_title = array();
   	   
   	   //Get data from the database
   	   $returned_data = $this -> get_data1dzoom($lakeid, $variable);
   	   //Get a plain language string for title
   	   $returned_title = $this -> get_plain_title_zoom($lakeid, $variable);
   	   $plain_title = $returned_title['title'];
   	   
   	   $returned_data['units'] = $returned_title['units'];
   	   
   	   //print_r($returned_data);
   	   
   	   $content = array();

   	   		//Create the main page container
		$content['main_container'] = array(
			'#type' => 'container',
			'#attributes' => array(
				'id' => array('mainDiv'),
				),
			);
		//Create a plot container
		$content['main_container']['plot_container'] = array(
			'#type' => 'container',
			'#attributes' => array(
				'class' => array('plotDiv'),
				),
			);
		
		$content['main_container']['plot_container']['plot_title'] = array(
			'#type' => 'markup',
			'#markup' => $plain_title,
			'#prefix' => '<h4 style="margin-left:50px;">',
			'#suffix' => '</h4>',
			);

   	   //Create a plot container
		//Add JS
		$content['main_container']['plot_container']['js_content'] = array(
			'#type' => 'markup',
			'#attached' => array(
   	   	   	   'library' => 'd3_1d_zoom/d3_1d_zoom_lib',
   	   	   	   'drupalSettings' => array(
   	   	   	   		'd3_1d_zoom' => array(
   	   	   	   			'numsamps' => $returned_data['numsamps'],
   	   	   	   			'dates' => $returned_data['Dates'],
   	   	   	   			'datetype' => $returned_data['datetype'],
   	   	   	   			'values' => $returned_data['Values'],
   	   	   	   			'units' => $returned_data['units'],
   	   	   	   			'dataset' => $returned_data['dataset'],
   	   	   	   			),
   	   	   	   		),
   	   	   	 ),
   	   );
   	      	   
   	   return $content;
   	   
   }
   
   //Get the data: sampledates and values
   private function get_data1dzoom($lakeid, $variable) { 
   	   
   	   //get data from our own database
   	   $returned = $this -> get_sql_query_zoom($lakeid, $variable);
   	   $sqlstatement = $returned['sqlstatement'];
   	   
   	   //print_r($sqlstatement);
   	   
   	   $con = \Drupal\Core\Database\Database::getConnection('default','dbmaker');
   	   $db = \Drupal::database();

   	   $query = $con->query($sqlstatement);
   	   $result = $query->fetchAll();
   	   
   	   //Database::setActiveConnection();
   	   
   	   //Initialize arrays
   	   $return_array = array();
   	   $idx = 0;
   	   $Dates = array();
   	   $Values = array();
   	   foreach ($result as $row) {  
   	   	   $Dates[$idx] = $row->sampledate;
   	   	   $Values[$idx++] = $row->$variable;
   	   }
   	   
   	   $return_array['datetype'] = $returned['datetype'];
	   $return_array['dataset'] = $returned['dataset'];
   	   $return_array['numsamps'] = $idx;
   	   $return_array['Dates'] = $Dates;
   	   $return_array['Values'] = $Values;
   	   
   	   //get lake level data for Mendota and Monona from USGS webservice	
   	   if (($lakeid == 'ME' || $lakeid == 'MO') && ($variable == 'lake_level')){
   	   	   if ($lakeid == 'ME') {
   	   	   	   $usgsid = '05428000';
   	   	   } else {
   	   	   	   $usgsid = '05429000';
   	   	   }
   	   	   
   	   	   $idx = 0;
   	   	   $url = 'http://waterservices.usgs.gov/nwis/dv/?format=json,1.1&sites='.$usgsid.'&period=P500W&siteType=LK';
   	   	   //echo $url;
   	   	   $client = curl_init($url);
   	   	   curl_setopt($client, CURLOPT_RETURNTRANSFER, 1);
   	   	   if(curl_exec($client) === false) {
   	   	   	   echo 'Error retrieving USGS data: ' . curl_error($client)."<br/>";
   	   	   }else{ 
   	   	   	   $datadownload = '<a href="http://waterservices.usgs.gov/nwis/dv/?format=rdb&sites='.$usgsid.'&period=P500W&siteType=LK" target="blank">Data Download</a>';
   	   	   	   
   	   	   	   $response = curl_exec($client);
   	   	   	   
   	   	   	   //parse json output to get gauge height and date
   	   	   	   $output = json_decode($response, True);
   	   	   	   
   	   	   	   foreach($output['value']['timeSeries'] as $timeseries){
   	   	   	   	   
   	   	   	   	   if ($timeseries['variable']['variableCode'][0]['value'] == '00065') {
   	   	   	   	   	   foreach($timeseries['values'][0]['value'] as $datarow){
   	   	   	   	   	   	   $lakelevel = ($datarow['value']+840)*0.3048; //convert to m above sea level
   	   	   	   	   	   	   $sampledate = $datarow['dateTime'];
   	   	   	   	   	   	   if((double)$lakelevel > -1 && (double)$lakelevel < 1000){ //filter out bad values
   	   	   	   	   	   	   	   $Dates[$idx] = substr($sampledate,0,10);
   	   	   	   	   	   	   	   $Values[$idx++] = $lakelevel;
   	   	   	   	   	   	   }
   	   	   	   	   	   }
   	   	   	   	   }
   	   	   	   }
   	   	   }
   	   	   curl_close($client);
   	   	   $datetype = 'Ymd';  //%Y-%m-%d
   	   	   $return_array['datetype'] = $datetype;
   	   	   $return_array['dataset'] = "http://waterdata.usgs.gov/nwis";
   	   	   $return_array['numsamps'] = $idx;
   	   	   $return_array['Dates'] = $Dates;
   	   	   $return_array['Values'] = $Values;
   	   }
   	   
   	   return $return_array;
   }
   //Create the database query based on the metric being plotted. Different variables use different tables, flag names, etc. 
   private function get_sql_query_zoom($lakeid, $variable) {
   	   
   	   //$lakeid = 'ME';        //test cases
   	   //$variable = 'totnuf';
   	   //Defaults
   	   $return_array = array();
   	   $lakestr = "'".$lakeid."'";
   	   $flag = 'flag'.$variable;
   	   $datetype = 'Ymd';  //%Y-%m-%d
   	   $dataset = "/node/55093";
   	   
   	   switch ($variable) {
	   case 'totnuf':
	   case 'totpuf':
	   case 'doc':
	   	   $table_name = 'chemphys'; 
	   	   $sqlstatement = "(SELECT sampledate , $variable FROM `".$table_name."` WHERE depth=0 AND $variable IS NOT NULL AND $flag IS NULL AND lakeid=$lakestr)";
	   	   break;	   
	   case 'chlor':
	   	   if($lakeid == 'ME' || $lakeid == 'MO' || $lakeid == 'FI' || $lakeid == 'WI'){
	   	   	   $sqlstatement = "(SELECT sampledate, tri_chl_spec as chlor FROM chlorophyll_madison WHERE lakeid = '".$lakeid."' AND depth_range_m = '0-2' AND rep = 1)";
	   	   	   $dataset = "/node/54999";
	   	   }else{
	   	   	   $table_name = 'chemphys'; 
	   	   	   $sqlstatement = "(SELECT sampledate , $variable FROM `".$table_name."` WHERE depth=0 AND $variable IS NOT NULL AND $flag IS NULL AND lakeid=$lakestr)";
	   	   }
	   	   break;
	   case 'ice_duration':
	   	   $lakename = $this -> get_lakename_zoom($lakeid);
	   	   $lakestr = "'".$lakename."'";
	   	   $table_name = 'icedata';
	   	   $datetype = 'Y';
	   	   $sqlstatement = "(SELECT start_year as sampledate, $variable FROM `".$table_name."` WHERE lakename=$lakestr AND $variable > -1 ORDER BY sampledate ASC)";
	   	   $dataset = "http://nsidc.org/data/G01377";
	   	   break;
	   case 'lake_level':
	   	   $dataset = "/node/55099";
	   	   $table_name = 'sample';
	   	   $var_fieldname = 'llevel_elevation';
	   	   $sqlstatement = "(SELECT sampledate , $var_fieldname as $variable FROM `".$table_name."` WHERE $var_fieldname IS NOT NULL AND lakeid=$lakestr)";
	   	   break;
	   case 'secchi':
	   	   $dataset = "/node/55095";
	   	   $table_name = 'sample';
	   	   $var_fieldname = 'secnview';
	   	   $sqlstatement = "(SELECT sampledate , $var_fieldname as $variable FROM `".$table_name."` WHERE $var_fieldname IS NOT NULL AND lakeid=$lakestr)";	     
	   	   break;
	   	   
	   }
	   
	   $return_array['datetype'] = $datetype;
	   $return_array['dataset'] = $dataset;
	   $return_array['sqlstatement'] = $sqlstatement;
	   return $return_array;
	   
   }
   
   private function get_plain_title_zoom($lakeid, $variable) {
   	   
   	   $return_array = array();
   	   
   	   $lakename = $this -> get_lakename_zoom($lakeid);
   	   
   	   switch ($variable) {
   	   case 'totnuf':
   	   	   $data_type = "Surface Concentration of Total N";
   	   	   $units = "micrograms per liter";
   	   	   break;			
   	   case 'totpuf':
   	   	   $data_type = "Surface Concentration of Total P";
   	   	   $units = "micrograms per liter";
   	   	   break;
   	   case 'doc':
   	   	   $data_type = "Surface Concentration of Dissolved Organic Carbon";
   	   	   $units = "milligrams per liter";
   	   	   break;
   	   case 'chlor':
   	   	   $data_type = "Surface Concentration of Chlorophyll";
   	   	   $units = "micrograms per liter";
   	   	   break;
   	   case 'ice_duration':
   	   	   $data_type = "Duration of Ice Cover";
   	   	   $units = "days";	  
   	   	   break;
   	   case 'lake_level':
   	   	   $data_type = "Lake Level Elevation";
   	   	   $units = "meters above sea level";	  
   	   	   break;
   	   case 'secchi':
   	   	   $data_type = "Secchi Depth";
   	   	   $units = "meters";	  
   	   	   break;
   	   case 'fish':
   	   	   $data_type = "Species Richness";
   	   	   $units = "number of species";
   	   	   break;
   	   }
   	   //The units get sent to the js file, so set as a global variable.
   	   $return_array['units'] = $units;
   	   
   	   $return_array['title'] = $lakename.": ".$data_type;
   	   return $return_array;
   	   
   }
   
   //Get the plain language lake name from lakeid
   private function get_lakename_zoom($lakeid) {
   	   
   	   //Get a lake name
   	   if ($lakeid == 'TR') $lakename = 'Trout Lake';
   	   if ($lakeid == 'SP') $lakename = 'Sparkling Lake';
   	   if ($lakeid == 'CR') $lakename = 'Crystal Lake';
   	   if ($lakeid == 'AL') $lakename = 'Allequash Lake';
   	   if ($lakeid == 'BM') $lakename = 'Big Muskellunge';
   	   if ($lakeid == 'CB') $lakename = 'Crystal Bog';
   	   if ($lakeid == 'TB') $lakename = 'Trout Bog';
   	   if ($lakeid == 'ME') $lakename = 'Lake Mendota';
   	   if ($lakeid == 'MO') $lakename = 'Lake Monona';
   	   if ($lakeid == 'WI') $lakename = 'Lake Wingra';
   	   if ($lakeid == 'FI') $lakename = 'Fish Lake';
   	   return $lakename;
   }
   
   //debugging function for Chrome use //debug_to_console( "Test" ); for debugging
   //open console in Chrome 'more tools' -> 'developer tools' 
   private function debug_to_console( $data ) {
   	   
   	   if ( is_array( $data ) )
   	   	   $output = "<script>console.log( 'Debug Objects: " . implode( ',', $data) . "' );</script>";
   	   else
   	   $output = "<script>console.log( 'Debug Objects: " . $data . "' );</script>";
   	   
   	   echo $output;
   }
   
}