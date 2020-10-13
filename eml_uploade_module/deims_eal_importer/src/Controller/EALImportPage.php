<?php

namespace Drupal\deims_eal_importer\Controller;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Access\AccessResult; 
use Drupal\Core\Form\FormInterface;

class EALImportPage extends ControllerBase {
  /**
   * Display the markup.
   *
   * @return array
   */
  public function content(Request $request) {

    $form = \Drupal::formBuilder()->getForm('Drupal\deims_eal_importer\Form\EALImportForm');
    
    return $form;
  }
}