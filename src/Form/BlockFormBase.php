<?php

namespace Drupal\mega_menu\Form;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\mega_menu\Contract\MegaMenuInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class BlockFormBase extends FormBase {

  /**
   * The mega menu instance.
   *
   * @var MegaMenuInterface
   */
  protected $megaMenu;

  /**
   * The block instance.
   *
   * @var BlockPluginInterface
   */
  protected $block;

  /**
   * The Drupal block manager.
   *
   * @var PluginManagerInterface
   */
  protected $blockManager;

  /**
   * The Drupal context repository.
   *
   * @var ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * Constructs a new VariantPluginFormBase.
   *
   * @param PluginManagerInterface $blockManager
   *   The Drupal block manager.
   * @param ContextRepositoryInterface $contextRepository
   *   The Drupal context repository.
   */
  public function __construct(
    PluginManagerInterface $blockManager,
    ContextRepositoryInterface $contextRepository
  )
  {
    $this->blockManager = $blockManager;
    $this->contextRepository = $contextRepository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('context.repository')
    );
  }

  /**
   * Prepares the block plugin based on the block ID.
   *
   * @param string $link_id
   *   The id of the link.
   *
   * @param string $block_id
   *   Either a block ID, or the plugin ID used to create a new block.
   *
   * @return BlockPluginInterface
   *   The block plugin.
   */
  abstract protected function prepareBlock($link_id, $block_id);

  /**
   * Get the value to use for the submit button.
   *
   * @return TranslatableMarkup
   */
  abstract protected function getSubmitValue();

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param FormStateInterface $form_state
   *   The current state of the form.
   * @param MegaMenuInterface $mega_menu
   *   The mega menu the block should be added to.
   * @param string|null $block_id
   *   The ID of the block to show a configuration form for.
   *
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL, MegaMenuInterface $mega_menu = NULL, $block_id = NULL) {
    $this->megaMenu = $mega_menu;

    // Get the query parameters needed.
    $form_state->set('link', $request->query->get('link'));
    $form_state->set('region', $request->query->get('region'));

    $this->block = $this->prepareBlock($form_state->get('link'), $block_id);

    // Some blocks require contexts, set a temporary value with gathered
    // contextual values.
    $form_state->setTemporaryValue('gathered_contexts', $this->contextRepository->getAvailableContexts());

    $form['#tree'] = TRUE;

    $form['settings'] = $this->block->buildConfigurationForm([], $form_state);

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->getSubmitValue(),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = (new FormState())->setValues($form_state->getValue('settings'));

    // Call the plugin submit handler.
    $this->block->submitConfigurationForm($form, $settings);

    // Update the original form values.
    $form_state->setValue('settings', $settings->getValues());

    // Add available contexts if this is a context aware block.
    if ($this->block instanceof ContextAwarePluginInterface) {
      $this->block->setContextMapping($form_state->getValue(['settings', 'context_mapping'], []));
    }

    $link = $form_state->get('link');
    $region = $form_state->get('region');

    $configuration = array_merge($this->block->getConfiguration(), [
      'link' => $link,
      'region' => $region,
    ]);

    if ($this->megaMenu->hasBlock($link, $configuration['id'])) {
      $this->megaMenu->updateBlock($link, $configuration['id'], $configuration);
    }
    else {
      $this->megaMenu->addBlock($link, $configuration['id'], $configuration);
    }

    $this->megaMenu->save();

    $form_state->setRedirectUrl(Url::fromRoute('entity.mega_menu.edit_form', [
      'mega_menu' => $this->megaMenu->id(),
    ]));
  }
}

