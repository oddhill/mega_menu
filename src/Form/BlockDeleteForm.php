<?php

namespace Drupal\mega_menu\Form;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mega_menu\Contract\MegaMenuInterface;
use Symfony\Component\HttpFoundation\Request;

class BlockDeleteForm extends ConfirmFormBase {

  /**
   * The mega menu instance.
   *
   * @var MegaMenuInterface
   */
  protected $megaMenu;

  /**
   * The block instance to be removed.
   *
   * @var BlockPluginInterface
   */
  protected $block;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mega_menu_block_delete_form¨';
  }

  /**
   * Returns the question to ask the user.
   *
   * @return string
   *   The form question. The page title will be set to this value.
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to remove the @label block?', [
      '@label' => $this->block->getConfiguration()['label'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->megaMenu->toUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL, MegaMenuInterface $mega_menu = NULL, $block_id = NULL) {
    $form_state->set('link', $request->query->get('link'));

    $this->megaMenu = $mega_menu;
    $this->block = $this->megaMenu->getBlock($form_state->get('link'), $block_id);

    $form = parent::buildForm($form, $form_state);

    // Remove the cancel button if this is an AJAX request since Drupals built
    // in modal dialogues does not handle buttons that are not a primary
    // button very well.
    if ($this->getRequest()->isXmlHttpRequest()) {
      unset($form['actions']['cancel']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $configuration = $this->block->getConfiguration();

    $this->megaMenu->removeBlock($form_state->get('link'), $configuration['id']);

    $this->megaMenu->save();

    drupal_set_message($this->t('The @label block has been removed.', [
      '@label' => $configuration['label']
    ]));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
