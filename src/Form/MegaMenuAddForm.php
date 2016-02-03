<?php

namespace Drupal\mega_menu\Form;

use Drupal\Core\Form\FormStateInterface;

class MegaMenuAddForm extends MegaMenuFormBase {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $form_state->setRedirectUrl($this->entity->toUrl('edit-form'));
  }
}
