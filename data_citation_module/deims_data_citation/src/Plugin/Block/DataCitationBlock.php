<?php

namespace Drupal\deims_data_citation\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Database;

/**
 * Provides a block with a simple text.
 *
 * @Block(
 *   id = "deims_data_citation_block",
 *   admin_label = @Translation("Data Citation Suggestion"),
 *   context = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       label = @Translation("Current Node"),
 *       required = FALSE,
 *     )
 *   }
 * )
 */
class DataCitationBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
  	  // get the node information to find the dataset id
  	  $node = $this->getContextValue('node');
  	  $datasetID = $node->get('field_data_set_id')->getString();
  	  
  	  //run curl commands to get the latest version from PASTA
  	  $url_get_revision = "https://pasta.lternet.edu/package/eml/knb-lter-ntl/".$datasetID."/newest";
  	  $curl = curl_init();
  	  curl_setopt_array($curl, [
  	  	  CURLOPT_RETURNTRANSFER => 1,
  	  	  CURLOPT_URL => $url_get_revision
  	  	  ]);
  	  
  	  $result = curl_exec($curl);
  	  curl_close($curl);
  	  
  	  $revision_parts = explode("/", $result);

  	  $revision = end($revision_parts);
  	  
  	  //run more curl to get the citation suggestion from PASTA
  	  $url_get_citation = "https://cite.edirepository.org/cite/knb-lter-ntl.".$datasetID.".".trim($revision)."?access";
  	  $headers = array(
  	  	  'Accept: text/html'
  	  	  );
  	  $curl_cit = curl_init();
  	  curl_setopt_array($curl_cit, [
  	  	  CURLOPT_RETURNTRANSFER => 1,
  	  	  CURLOPT_URL => $url_get_citation,
  	  	  CURLOPT_HTTPHEADER => $headers
  	  	  ]);
  	  
  	  $result = curl_exec($curl_cit);
  	  curl_close($curl_cit);
  	  
    return [
    	'#markup' => "<span>$result</span>",
    ];
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