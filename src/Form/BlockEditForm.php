<?php

namespace Drupal\mega_menu\Form;

class BlockEditForm extends BlockFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mega_menu_block_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getSubmitValue() {
    return $this->t('Update block');
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareBlock($link_id, $block_id) {
    return $this->megaMenu->getBlock($link_id, $block_id);
  }
}
