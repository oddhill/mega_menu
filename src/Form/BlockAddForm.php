<?php

namespace Drupal\mega_menu\Form;

class BlockAddForm extends BlockFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mega_menu_block_add_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getSubmitValue() {
    return $this->t('Add block');
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareBlock($link_id, $block_id) {
    return $this->blockManager->createInstance($block_id);
  }
}
