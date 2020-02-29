<?php

namespace Drupal\lake_longterm_zoom\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Database;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;

/**
* Provides a block with a simple text.
*
* @Block(
*   id = "lake_long_term_zoom_block",
*   admin_label = @Translation("Icons to datasets"),
*   context = {
*     "node" = @ContextDefinition(
*       "entity:node",
*       label = @Translation("Current Node"),
*       required = FALSE,
*     )
*   }
* )
*/
class LakeLongTermZoomBlock extends BlockBase {
	/**
	* {@inheritdoc}
	*/
	public function build() {
		// get the node information to find the lake name
		$node = $this->getContextValue('node');
		$lake_name = $node->getTitle();
		//var_dump($lake_name);
		
		switch($lake_name){
		case 'Fish Lake': $lakeid = 'FI';
			break;
		case 'Lake Mendota': $lakeid = 'ME';
			break;
		case 'Lake Monona': $lakeid = 'MO';
			break;
		case 'Lake Wingra': $lakeid = 'WI';
			break;
		case 'Allequash Lake': $lakeid = 'AL';
			break;
		case 'Big Muskellunge Lake': $lakeid = 'BM';
			break;
		case 'Crystal Bog': $lakeid = 'CB';
			break;
		case 'Crystal Lake': $lakeid = 'CR';
			break;
		case 'Sparkling Lake': $lakeid = 'SP';
			break;
		case 'Trout Bog': $lakeid = 'TB';
			break;
		case 'Trout Lake': $lakeid = 'TR';
			break;
		}
		
		//get path to current module to find the images later
		$module_handler = \Drupal::service('module_handler');
		$module_path = $module_handler->getModule('lake_longterm_zoom')->getPath();
		
		
		//figure out available styles
		$style = \Drupal::entityTypeManager()->getStorage('image_style')->load('original');
		//var_dump($styles);
		
		$content = array();
		
		//These variable are included only in the lakes specified
		$macrophytes = array('ME', 'MO', 'WI', 'FI', 'TR'); 
		$phytoplankton = array('ME', 'MO', 'TR', 'CR', 'SP');
		$lakelevels = array('AL', 'BM', 'CR', 'CB', 'SP', 'TB', 'TR');
		
		
		$content['main_block'] = array(
			'#type' => 'container',
			'#attributes' => array(
				'id' => array('lakelt_main'),
				),
			);
		$content['main_block']['title'] = array(
			'#type' => 'markup',
			'#markup' => '<h3>Long-Term Data:</h3>click on icon to explore data',
			);
		$content['main_block']['css_content'] = array(
			'#type' => 'markup',
			'#attached' => array(
				'library' => 'lake_longterm_zoom/lake_longterm_zoom_lib',
				),  
			);
		
		
		
		/****************************************************************
		CHEMICAL CONTAINERS
		****************************************************************/
		//
		//Create a main div for chemical limnology
		$content['main_block']['container_chemical'] = array(
			'#type' => 'container',
			'#attributes' => array(
				'id' => array('lakelt_chem'),
				'class' => array('lakelt'),
				),
			);
		//Subcontainer for nitrogen
		$content['main_block']['container_chemical']['chemical_nitrogen'] = array(
			'#type' => 'container',
			'#attributes' => array(
				'id' => array('lakelt_n'),
				'class' => array('lakelt_sub'),
				),
			);
		//Subcontainer for phosphorus
		$content['main_block']['container_chemical']['chemical_phosphorus'] = array(
			'#type' => 'container',
			'#attributes' => array(
				'id' => array('lakelt_p'),
				'class' => array('lakelt_sub'),
				),
			);
		//Subcontainer for doc
		$content['main_block']['container_chemical']['chemical_doc'] = array(
			'#type' => 'container',
			'#attributes' => array(
				'id' => array('lakelt_doc'),
				'class' => array('lakelt_sub'),
				),
			); 
		
		/****************************************************************
		CHEMICAL CONTENT
		****************************************************************/
		//Nitrogen
		
		$uri = $this->getImageUri('n.jpg');
		
		$content['main_block']['container_chemical']['chemical_nitrogen']['image']['link'] = array(
			'#type' => 'link',
			'#title' => array(
				'#theme' => 'image_style',
				'#style_name' => 'thumbnail',
				'#uri' => $uri,
				),
			'#url' => Url::fromUri('internal:/1dPlotzoom/'.$lakeid.'/totnuf'),
			'#options' => array(
				'attributes' => array(),
				'html' => TRUE,
				),
			);
		
		$content['main_block']['container_chemical']['chemical_nitrogen']['label']['link'] = array(
			'#type' => 'link',
			'#title' => t('<p class="lt_label">Total Nitrogen</p>'),
			'#url' => Url::fromUri('internal:/1dPlotzoom/'.$lakeid.'/totnuf'),
			'#options' => array(
				'attributes' => array(),
				'html' => TRUE,
				),
			);
		
		//Phosphorus
		
		$uri = $this->getImageUri('p.jpg');
		
		$content['main_block']['container_chemical']['chemical_phosphorus']['image']['link'] = array(
			'#type' => 'link',
			'#title' => array(
				'#theme' => 'image_style',
				'#style_name' => 'thumbnail',
				'#uri' => $uri,
				),
			'#url' => Url::fromUri('internal:/1dPlotzoom/'.$lakeid.'/totpuf'),
			'#options' => array(
				'attributes' => array(),
				'html' => TRUE,
				),
			);
		$content['main_block']['container_chemical']['chemical_phosphorus']['label']['link'] = array(
			'#type' => 'link',
			'#title' => t('<p class="lt_label">Total Phosphorus</p>'),
			'#url' => Url::fromUri('internal:/1dPlotzoom/'.$lakeid.'/totpuf'),
			'#options' => array(
				'attributes' => array(),
				'html' => TRUE,
				),
			);
		
		//DOC
	    
		$uri = $this->getImageUri('c.jpg');
		
		$content['main_block']['container_chemical']['chemical_doc']['image']['link'] = array(
			'#type' => 'link',
			'#title' => array(
				'#theme' => 'image_style',
				'#style_name' => 'thumbnail',
				'#uri' => $uri,
				),
			'#url' => Url::fromUri('internal:/1dPlotzoom/'.$lakeid.'/doc'),
			'#options' => array(
				'attributes' => array(),
				'html' => TRUE,
				),
			);
		$content['main_block']['container_chemical']['chemical_doc']['label']['link'] = array(
			'#type' => 'link',
			'#title' => t('<p class="lt_label">Dissolved Organic Carbon</p>'),
			'#url' => Url::fromUri('internal:/1dPlotzoom/'.$lakeid.'/doc'),
			'#options' => array(
				'attributes' => array(),
				'html' => TRUE,
				),
			);
		
		
		/****************************************************************
		BIOLOGY CONTAINERS
		****************************************************************/
		
		// 
		//Create a main div for biology
		$content['main_block']['container_biology'] = array(
			'#type' => 'container',
			'#attributes' => array(
				'id' => array('lakelt_bio'),
				'class' => array('lakelt'),
				),
			);
		/*  $content['main_block']['container_biology']['title'] = array(
		'#type' => 'markup',
		'#markup' => '<h5 class="lakelt_header">Biology</h5>'
		);
		*/
		//Subcontainer for fish
		$content['main_block']['container_biology']['biology_fish'] = array(
			'#type' => 'container',
			'#attributes' => array(
				'id' => array('lakelt_fish'),
				'class' => array('lakelt_sub'),
				),
			);
		
		//Subcontainer for macrophytes  
		if (in_array($lakeid, $macrophytes)) {
			$content['main_block']['container_biology']['biology_macro'] = array(
				'#type' => 'container',
				'#attributes' => array(
					'id' => array('lakelt_macro'),
					'class' => array('lakelt_sub'),
					),
				);
		}
		//Subcontainer for phytoplankton
		if (in_array($lakeid, $phytoplankton)) {
			$content['main_block']['container_biology']['biology_phyto'] = array(
				'#type' => 'container',
				'#attributes' => array(
					'id' => array('lakelt_phyto'),
					'class' => array('lakelt_sub'),
					),
				);
		}
		//Subcontainer for zooplankton
		$content['main_block']['container_biology']['biology_zoop'] = array(
			'#type' => 'container',
			'#attributes' => array(
				'id' => array('lakelt_zoop'),
				'class' => array('lakelt_sub'),
				),
			);
		if ( $lakeid != 'CB') {
			//Subcontainer for chlorophyll
			$content['main_block']['container_biology']['biology_chlor'] = array(
				'#type' => 'container',
				'#attributes' => array(
					'id' => array('lakelt_chlor'),
					'class' => array('lakelt_sub'),
					),
				);
		}
		/****************************************************************
		BIOLOGY CONTENT
		****************************************************************/
		
		//Fish
		
		$uri = $this->getImageUri('fishIcon.jpg');
		
		$content['main_block']['container_biology']['biology_fish']['image']['link'] = array(
			'#type' => 'link',
			'#title' => array(
				'#theme' => 'image_style',
				'#style_name' => 'thumbnail',
				'#uri' => $uri,
				),
			'#url' => Url::fromUri('internal:/lake-bio-zoom/'.$lakeid.'/fish'),
			'#options' => array(
				'attributes' => array(),
				'html' => TRUE,
				),
			);
		$content['main_block']['container_biology']['biology_fish']['label']['link'] = array(
			'#type' => 'link',
			'#title' => t('<p class="lt_label">Fish</p>'),
			'#url' => Url::fromUri('internal:/lake-bio-zoom/'.$lakeid.'/fish'),
			'#options' => array(
				'attributes' => array(),
				'html' => TRUE,
				),
			);
		//Macrophytes
		
		if ( in_array($lakeid, $macrophytes) ) {
			
			$uri = $this->getImageUri('macrophyteIcon.jpg');
			
			$content['main_block']['container_biology']['biology_macro']['image']['link'] = array(
				'#type' => 'link',
				'#title' => array(
					'#theme' => 'image_style',
					'#style_name' => 'thumbnail',
					'#uri' => $uri,
					),
				'#url' => Url::fromUri('internal:/lake-bio-zoom/'.$lakeid.'/macro'),
				'#options' => array(
					'attributes' => array(),
					'html' => TRUE,
					),
				);
			$content['main_block']['container_biology']['biology_macro']['label']['link'] = array(
				'#type' => 'link',
				'#title' => t('<p class="lt_label">Macrophytes</p>'),
				'#url' => Url::fromUri('internal:/lake-bio-zoom/'.$lakeid.'/macro'),
				'#options' => array(
					'attributes' => array(),
					'html' => TRUE,
					),
				);
		}
		//Phytoplankton
		if (in_array($lakeid, $phytoplankton)) {
			
			$uri = $this->getImageUri('phytoplanktonIcon.jpg');
			
			$content['main_block']['container_biology']['biology_phyto']['image']['link'] = array(
				'#type' => 'link',
				'#title' => array(
					'#theme' => 'image_style',
					'#style_name' => 'thumbnail',
					'#uri' => $uri,
					),
				'#url' => Url::fromUri('internal:/lake-bio-zoom/'.$lakeid.'/phyto'),
				'#options' => array(
					'attributes' => array(),
					'html' => TRUE,
					),
				);
			$content['main_block']['container_biology']['biology_phyto']['label']['link'] = array(
				'#type' => 'link',
				'#title' => t('<p class="lt_label">Phytoplankton</p>'),
				'#url' => Url::fromUri('internal:/lake-bio-zoom/'.$lakeid.'/phyto'),
				'#options' => array(
					'attributes' => array(),
					'html' => TRUE,
					),
				);
		}
			//Zooplankton
			
			$uri = $this->getImageUri('zooplanktonIcon.jpg');
			
			$content['main_block']['container_biology']['biology_zoop']['image']['link'] = array(
				'#type' => 'link',
				'#title' => array(
					'#theme' => 'image_style',
					'#style_name' => 'thumbnail',
					'#uri' => $uri,
					),
				'#url' => Url::fromUri('internal:/lake-bio-zoom/'.$lakeid.'/zoop'),
				'#options' => array(
					'attributes' => array(),
					'html' => TRUE,
					),
				);  
			$content['main_block']['container_biology']['biology_zoop']['label']['link'] = array(
				'#type' => 'link',
				'#title' => t('<p class="lt_label">Zooplankton</p>'),
				'#url' => Url::fromUri('internal:/lake-bio-zoom/'.$lakeid.'/zoop'),
				'#options' => array(
					'attributes' => array(),
					'html' => TRUE,
					),
				);  
			
			//Chlorophyll
			if ( $lakeid != 'CB') {
				
				$uri = $this->getImageUri('chlor.jpg');
				
				$content['main_block']['container_biology']['biology_chlor']['image']['link'] = array(
					'#type' => 'link',
					'#title' => array(
						'#theme' => 'image_style',
						'#style_name' => 'thumbnail',
						'#uri' => $uri,
						),
					'#url' => Url::fromUri('internal:/1dPlotzoom/'.$lakeid.'/chlor'),
					'#options' => array(
						'attributes' => array(),
						'html' => TRUE,
						),
					);
				$content['main_block']['container_biology']['biology_chlor']['label']['link'] = array(
					'#type' => 'link',
					'#title' => t('<p class="lt_label">Chlorophyll</p>'),
					'#url' => Url::fromUri('internal:/1dPlotzoom/'.$lakeid.'/chlor'),
					'#options' => array(
						'attributes' => array(),
						'html' => TRUE,
						),
					);
			}
			
			/****************************************************************
			PHYSICAL CONTAINERS
			****************************************************************/
			// 
			// 
			//Create a main div for physical
			$content['main_block']['container_physical'] = array(
				'#type' => 'container',
				'#attributes' => array(
					'id' => array('lakelt_phys'),
					'class' => array('lakelt'),
					),
				);
			//Subcontainer for ice cover
			if($lakeid != 'FI') {
				$content['main_block']['container_physical']['physical_ice'] = array(
					'#type' => 'container',
					'#attributes' => array(
						'id' => array('lakelt_ice'),
						'class' => array('lakelt_sub'),
						),
					);
			}
			
			//Subcontainer for water level
			if ( in_array($lakeid, $lakelevels) ) {
				//if($lakeid != 'WI' && $lakeid != 'FI') {
				$content['main_block']['container_physical']['physical_wlevel'] = array(
					'#type' => 'container',
					'#attributes' => array(
						'id' => array('lakelt_wlevel'),
						'class' => array('lakelt_sub'),
						),
					);
			}
			//Subcontainer for secchi depths
			$content['main_block']['container_physical']['physical_secchi'] = array(
				'#type' => 'container',
				'#attributes' => array(
					'id' => array('lakelt_secchi'),
					'class' => array('lakelt_sub'),
					),
				);
			/****************************************************************
			PHYSICAL CONTENT
			****************************************************************/
			
			//Ice Duration
			if($lakeid != 'FI') {
				$uri = $this->getImageUri('iceIcon.jpg');
				
				$content['main_block']['container_physical']['physical_ice']['image']['link'] = array(
					'#type' => 'link',
					'#title' => array(
						'#theme' => 'image_style',
						'#style_name' => 'thumbnail',
						'#uri' => $uri,
						),
					'#url' => Url::fromUri('internal:/1dPlotzoom/'.$lakeid.'/ice_duration'),
					'#options' => array(
						'attributes' => array(),
						'html' => TRUE,
						),
					);
				$content['main_block']['container_physical']['physical_ice']['label']['link'] = array(
					'#type' => 'link',
					'#title' => t('<p class="lt_label">Ice Duration</p>'),
					'#url' => Url::fromUri('internal:/1dPlotzoom/'.$lakeid.'/ice_duration'),
					'#options' => array(
						'attributes' => array(),
						'html' => TRUE,
						),
					);
			}
			if ( in_array($lakeid, $lakelevels) ) {
				//if($lakeid != 'WI' && $lakeid != 'FI') {
				//Lake Levels
				
				$uri = $this->getImageUri('waterlevelIcon.jpg');
				
				$content['main_block']['container_physical']['physical_wlevel']['image']['link'] = array(
					'#type' => 'link',
					'#title' => array(
						'#theme' => 'image_style',
						'#style_name' => 'thumbnail',
						'#uri' => $uri,
						),
					'#url' => Url::fromUri('internal:/1dPlotzoom/'.$lakeid.'/lake_level'),
					'#options' => array(
						'attributes' => array(),
						'html' => TRUE,
						),
					);
				$content['main_block']['container_physical']['physical_wlevel']['label']['link'] = array(
					'#type' => 'link',
					'#title' => t('<p class="lt_label">Lake Level</p>'),
					'#url' => Url::fromUri('internal:/1dPlotzoom/'.$lakeid.'/lake_level'),
					'#options' => array(
						'attributes' => array(),
						'html' => TRUE,
						),
					);
			}
			//Secchi Depth
			
			$uri = $this->getImageUri('secchiIcon.jpg');
			
			$content['main_block']['container_physical']['physical_secchi']['image']['link'] = array(
				'#type' => 'link',
				'#title' => array(
					'#theme' => 'image_style',
					'#style_name' => 'thumbnail',
					'#uri' => $uri,
					),
				'#url' => Url::fromUri('internal:/1dPlotzoom/'.$lakeid.'/secchi'),
				'#options' => array(
					'attributes' => array(),
					'html' => TRUE,
					),
				);  
			$content['main_block']['container_physical']['physical_secchi']['label']['link'] = array(
				'#type' => 'link',
				'#title' => t('<p class="lt_label">Secchi Depth</p>'),
				'#url' => Url::fromUri('internal:/1dPlotzoom/'.$lakeid.'/secchi'),
				'#options' => array(
					'attributes' => array(),
					'html' => TRUE,
					),
				);  
			
			return $content;
	}
	
	private function getImageUri($filename) {
		
		$image_info = \Drupal::entityTypeManager()
		->getStorage('file')
		->loadByProperties(['filename' => $filename]);
		
		//turn the array into a file entity
		$image = reset($image_info);
		
		//get the file url
		$uri = $image->getFileUri();
		
		return $uri;
		
	}
	
	/**
	* {@inheritdoc}
	*/
	protected function blockAccess(AccountInterface $account) {
		return AccessResult::allowedIfHasPermission($account, 'access content');
	}
	
	/**
	* {@inheritdoc}
	*/
	public function blockForm($form, FormStateInterface $form_state) {
		$config = $this->getConfiguration();
		
		return $form;
	}
	
	/**
	* {@inheritdoc}
	*/
	public function blockSubmit($form, FormStateInterface $form_state) {
		$this->configuration['my_block_settings'] = $form_state->getValue('my_block_settings');
	}
}