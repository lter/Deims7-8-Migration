<?php

namespace Drupal\d3_lake_bio_zoom\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use core\lib\Drupal\Core\Database;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
* Controller routines for d3_lake_bio_zoom graphs and species lists.
*/

class D3_lake_bio_zoomController extends ControllerBase {
	
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
		$returned_data = $this->d3_lake_bio_zoom_get_data($lakeid, $variable);
		//Get a plain language string for title
		$returned_title = $this->d3_lake_bio_zoom_get_plain_title($lakeid, $variable);
		
		$plain_title = $returned_title['plot_title'];
		
		//and get the units out of that array
		$returned_data['plot_units'] = $returned_title['plot_units'];
		$returned_data['plot_title'] = $returned_title['plot_title'];
		
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
		
		//Create a table container
		$content['main_container']['table_container'] = array(
			'#type' => 'container',
			'#attributes' => array(
				'id' => array('tableDiv'),
				),
			);
		
		//Add a table container title
		$content['main_container']['table_container']['title'] = array(
			'#type' => 'markup',
			'#markup' => 'Species List',   
			'#prefix' => '<h4 style="margin-left:50px;">',
			'#suffix' => '</h4>',
			);
		
		
		//Add JS
		$content['main_container']['plot_container']['js_content'] = array(
			'#type' => 'markup',
			'#attached' => array(
				'library' => 'd3_lake_bio_zoom/d3_lake_bio_zoom_lib',
				'drupalSettings' => array(
					'd3_lake_bio_zoom' =>
						array(
							'plot_title' => $returned_data['plot_title'],
							'plot_units' => $returned_data['plot_units'],		 
							'Dates' => $returned_data['Dates'],
							'Richness' => $returned_data['Richness'],
							'Common' => $returned_data['Common'],
							'Species' => $returned_data['Species'],
							'dataset' => $returned_data['dataset'],
						),
					),
				),
			);
		
		
		//return $content;
		
		//Add a table of known species. 
		$Species = $returned_data['Species'];
		$Common = $returned_data['Common'];
		$numspecies = sizeof($Species);
		$Splinks = $this->d3_lake_bio_zoom_eol_links($Species,$Common);
		
		$rows = array();
		//For fish and macrophytes, include two columns: common and scientific names
		if ($variable == 'fish' || $variable == 'macro') {
			$header = array(t('Common Name'), t('Scientific Name'));
			for ($r=0; $r<$numspecies; $r++) {
				$rows[$r][0] = $Common[$r];
				$rows[$r][1] = $Splinks[$r];
			}  
		} else {  //for phyto and zoop, include only scientfic name
			$header = array(t('Scientific Name'));
			for ($r=0; $r<$numspecies; $r++) {
				$rows[$r][0] = $Splinks[$r]; 
			}		
		}  
		$content['main_container']['table_container']['table'] = array(
			'#theme' => 'table',
			'#header' => $header,
			'#rows' => $rows,
			'#attributes' => array('id' => array('speciesTable')),
			'#empty' =>t('No known species'),
			);
		
		return $content;
	}
	//Get the data from the database: sampledates and values
	private function d3_lake_bio_zoom_get_data($lakeid, $variable) { 
		
		$richness_array = array();
		//First get species richness by year
		$richness_array = $this->d3_lake_bio_zoom_get_richness_query($lakeid, $variable);
		
		$sqlstatement = $richness_array['sqlstatement'];
		
		$con = \Drupal\Core\Database\Database::getConnection('default','dbmaker');
		$db = \Drupal::database();
		
		$query = $con->query($sqlstatement);
		$result = $query->fetchAll();
		
		
		//Initialize arrays
		$return_array = array();
		$idx=0;
		$Dates = array();
		$Richness = array();
		
		foreach ($result as $row) {  
			$Dates[$idx] = $row->year4;
			$Richness[$idx++] = $row->spcount;
		}
		
		$return_array['Dates'] = $Dates;
		$return_array['Richness'] = $Richness;
		$return_array['dataset'] = $richness_array['dataset'];
		
		//Get historical species list	
		$sqlstatement = $this->d3_lake_bio_zoom_get_species_query($lakeid, $variable);	
		
		$query = $con->query($sqlstatement);
		$result = $query->fetchAll();
		
		$idx=0;
		$Common = array();
		$Species = array();
		foreach ($result as $row) {  
			$Common[$idx] = $row->common;
			$Species[$idx++] = $row->species;
		}
		
		$return_array['Common'] = $Common;
		$return_array['Species'] = $Species;
		
		return $return_array;
	}
	
	//Get the database query depending on the metric being plotted. The queries differ significantly between bio type and location
	private function d3_lake_bio_zoom_get_richness_query($lakeid, $variable) {
		
		//Defaults
		$dataset = "/node/";
		$lakestr = "'".$lakeid."'";
		switch ($variable) {
		case 'fish':
			$var_fieldname = 'richness';
			$sqlstatement = "(SELECT year4, richness as spcount FROM `fish_richness` WHERE richness IS NOT NULL AND lakeid=$lakestr)";	
			//		  $dataset = "/node/55087";
			break;
		case 'macro':
			if ($lakeid=='ME' || $lakeid=='MO' || $lakeid=='WI' || $lakeid=='FI') {
				$sqlstatement = "( SELECT DISTINCT macrophyte_rating_madison.year4, COUNT(DISTINCT macrophyte_rating_madison.spname) as spcount
				FROM `macrophyte_rating_madison`
				WHERE macrophyte_rating_madison.lakeid = $lakestr
				GROUP BY macrophyte_rating_madison.year4 
				ORDER BY macrophyte_rating_madison.year4 ASC)";		
				$dataset = "/node/55033";
			} else {
				$sqlstatement = "( SELECT DISTINCT macrophyte_tr_quad_sp.year4, COUNT(DISTINCT macrophyte_tr_quad_sp.species_name) as spcount
				FROM `macrophyte_tr_quad_sp`
				GROUP BY macrophyte_tr_quad_sp.year4 
				ORDER BY macrophyte_tr_quad_sp.year4 ASC)";
			}
			$dataset = "/node/55077";
			break;
		case 'phyto':
			if ($lakeid=='ME' || $lakeid=='MO' || $lakeid=='WI' || $lakeid=='FI') {
				$sqlstatement = "(SELECT DISTINCT phytoplankton_madison_v2.year4, COUNT(DISTINCT phytoplankton_madison_v2.taxa_name) as spcount
				FROM `phytoplankton_madison_v2`
				WHERE phytoplankton_madison_v2.lakeid = $lakestr
				GROUP BY phytoplankton_madison_v2.year4 
				ORDER BY phytoplankton_madison_v2.year4 ASC )";
				$dataset = "/node/55072";
			} else {
				$sqlstatement = "(SELECT DISTINCT phytoplankton_nl.year4, COUNT(DISTINCT phytoplankton_nl.taxa_name) as spcount
				FROM `phytoplankton_nl`
				WHERE phytoplankton_nl.lakeid = $lakestr
				GROUP BY phytoplankton_nl.year4 
				ORDER BY phytoplankton_nl.year4 ASC )";	
				$dataset = "/node/54976";
			}	   
			break;
		case 'zoop':
			if ($lakeid=='ME' || $lakeid=='MO' || $lakeid=='WI' || $lakeid=='FI') {
				$sqlstatement = "(SELECT DISTINCT zoop_allsl_summary_snap.year4, COUNT(DISTINCT zoop_allsl_summary_snap.species_code) as spcount
				FROM `zoop_allsl_summary_snap`
				WHERE zoop_allsl_summary_snap.lakeid = $lakestr
				GROUP BY zoop_allsl_summary_snap.year4 
				ORDER BY zoop_allsl_summary_snap.year4 ASC )"; 
				$dataset = "/node/55120";
			} else {
				$sqlstatement = "( SELECT DISTINCT zoop_allnl_summary_snap.year4, COUNT(DISTINCT zoop_allnl_summary_snap.species_code) as spcount
				FROM `zoop_allnl_summary_snap`
				WHERE zoop_allnl_summary_snap.lakeid = $lakestr
				GROUP BY zoop_allnl_summary_snap.year4 
				ORDER BY zoop_allnl_summary_snap.year4 ASC )";
				$dataset = "/node/55119";
			}	   
			break;
		}//switch $variable
		
		$richness_array = array();
		
		$richness_array['dataset'] = $dataset;
		$richness_array['sqlstatement'] = $sqlstatement;
		
		return $richness_array;
	}
	
	private function d3_lake_bio_zoom_get_species_query($lakeid, $variable) {
		
		$lakestr = "'".$lakeid."'";
		switch ($variable) {
		case 'fish':
			$sqlstatement = "(SELECT fish_abundance.spname, meta_taxon_cov.common_name as common, meta_taxon_cov.species as species 
			FROM `fish_abundance` INNER JOIN `meta_taxon_cov` ON fish_abundance.spname = meta_taxon_cov.ntl_taxon_code
			GROUP BY fish_abundance.lakeid, fish_abundance.spname, meta_taxon_cov.common_name, meta_taxon_cov.species
			HAVING (fish_abundance.lakeid = $lakestr AND meta_taxon_cov.common_name IS NOT Null)
			ORDER BY meta_taxon_cov.common_name)";	   
			break;
		case 'macro':
			if ($lakeid=='ME' || $lakeid=='MO' || $lakeid=='WI' || $lakeid=='FI') {
				$sqlstatement = "(SELECT DISTINCT macrophyte_rating_madison.spname as spname, meta_taxon_cov.common_name as common, meta_taxon_cov.species as species
				FROM `macrophyte_rating_madison`
				LEFT JOIN `meta_taxon_cov` ON macrophyte_rating_madison.spname = meta_taxon_cov.ntl_taxon_code
				WHERE (macrophyte_rating_madison.lakeid=$lakestr AND meta_taxon_cov.common_name IS NOT NULL) 
				ORDER BY meta_taxon_cov.common_name )";		  
			} else {  //Trout Lake only for macrophytes
				$sqlstatement = "(SELECT DISTINCT macrophyte_tr_quad_sp.species_name as spname, meta_taxon_cov.species AS species, meta_taxon_cov.common_name AS common
				FROM `macrophyte_tr_quad_sp`
				INNER JOIN `meta_taxon_cov` ON macrophyte_tr_quad_sp.species_name = meta_taxon_cov.ntl_taxon_code
				WHERE (macrophyte_tr_quad_sp.lakeid=$lakestr AND meta_taxon_cov.common_name IS NOT NULL)
				ORDER BY meta_taxon_cov.common_name )";		  
			}				   
			break;
		case 'phyto':
			if ($lakeid=='ME' || $lakeid=='MO' || $lakeid=='WI' || $lakeid=='FI') {
				$sqlstatement = "(SELECT DISTINCT meta_taxon_cov.common_name as common, meta_taxon_cov.species as species
				FROM `phytoplankton_madison_v2`
				INNER JOIN `meta_taxon_cov` ON phytoplankton_madison_v2.taxa_id = meta_taxon_cov.ntl_taxon_code
				WHERE (phytoplankton_madison_v2.lakeid = $lakestr AND meta_taxon_cov.species IS NOT NULL) 
				ORDER BY meta_taxon_cov.species)";
				
			} else {  //Northern lakes
				$sqlstatement = "(SELECT DISTINCT meta_taxon_cov.common_name as common, meta_taxon_cov.species as species
				FROM `meta_taxon_cov`
				INNER JOIN `phytoplankton_nl` ON phytoplankton_nl.taxa_id = meta_taxon_cov.ntl_taxon_code
				WHERE phytoplankton_nl.lakeid = $lakestr AND meta_taxon_cov.species IS NOT NULL
				ORDER BY meta_taxon_cov.species)";		  
			}		   	   
			break;
		case 'zoop':
			if ($lakeid=='ME' || $lakeid=='MO' || $lakeid=='WI' || $lakeid=='FI') {
				$sqlstatement = "(SELECT DISTINCT meta_taxon_cov.common_name as common, meta_taxon_cov.species as species
				FROM `zoop_allsl_summary_snap`
				INNER JOIN `meta_taxon_cov` ON zoop_allsl_summary_snap.species_code = meta_taxon_cov.ntl_taxon_code
				WHERE zoop_allsl_summary_snap.lakeid = $lakestr AND meta_taxon_cov.species IS NOT NULL
				ORDER BY meta_taxon_cov.species ASC)";
			} else {  //Northern lakes
				$sqlstatement = "(SELECT DISTINCT meta_taxon_cov.common_name as common, meta_taxon_cov.species as species
				FROM `zoop_allnl_summary_snap`
				INNER JOIN `meta_taxon_cov` ON zoop_allnl_summary_snap.species_code = meta_taxon_cov.ntl_taxon_code
				WHERE zoop_allnl_summary_snap.lakeid = $lakestr AND meta_taxon_cov.species IS NOT NULL
				ORDER BY meta_taxon_cov.species ASC)";		  
			}		   	   
			break;  		   
		}//switch $variable
		
		return $sqlstatement;
	}
	
	
	function d3_lake_bio_zoom_get_plain_title($lakeid, $variable) {
		
		$lakename = $this->d3_lake_bio_zoom_get_lakename($lakeid);
		switch ($variable) {
		case 'fish':
			$result_type = "Fish Species Richness by Year";
			$plot_units = "number of species";
			break;			
		case 'macro':
			$result_type = "Macrophyte Species Richness by Year";
			$plot_units = "number of species";
			break;
		case 'phyto':
			$result_type = "Phytoplankton Species Richness by Year";
			$plot_units = "number of species";
			break;
		case 'zoop':
			$result_type = "Zooplankton Species Richness by Year";
			$plot_units = "number of species";
			break;	  
		}
		
		$title_array = array();
		//The units get sent to the js file, so set as a global variable.
		$title_array['plot_units'] = $plot_units;
		$plot_title = $lakename.": ".$result_type;
		$title_array['plot_title'] = $plot_title;
		
		return $title_array;
	}
	//Get the plain language lake name from lakeid
	function d3_lake_bio_zoom_get_lakename($lakeid) {
		
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
	//Return the EOL.org links based on species name
	function d3_lake_bio_zoom_eol_links($Species, $Common) {
		
		$Splinks = array();  //This is array of species links, which will be put in the "Common Name" column of the table
		$numspecies = sizeof($Species);
		//Input two strings sciname (genus,species)
		for ($r=0; $r<$numspecies; $r++) {
			$sciname = $Species[$r];       //get the scientific name
			$genus = strtok($sciname," ");  //extract the first part (genus)
			$species = strtok(" ");         //extract the second part (species)
			$hybrid_parts = strtok(" ");    //extract the next part (exists if its a hybrid)
			if ( ($hybrid_parts == FALSE)&&($species==TRUE) ) {
				$link = Link::fromTextAndUrl($Species[$r], Url::fromUri("http://eol.org/search?q=". $genus ."+". $species, array('attributes' => array('target' => '_blank'))));
				$Splinks[$r] = $link->toString();
			} else {   //it's a hybrid -- don't show a link
				$Splinks[$r] = $Species[$r];
			}
		} //for  
		return $Splinks;
	}
	
	//debugging function for Chrome use //debug_to_console_bio( "Test" ); for debugging
	//open console in Chrome 'more tools' -> 'developer tools' 
	function debug_to_console_bio( $data ) {
		
		if ( is_array( $data ) )
			$output = "<script>console.log( 'Debug Objects: " . implode( ',', $data) . "' );</script>";
		else
        $output = "<script>console.log( 'Debug Objects: " . $data . "' );</script>";
        
        echo $output;
    }
}
