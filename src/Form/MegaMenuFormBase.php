<?php

namespace Drupal\mega_menu\Form;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface;
use Drupal\mega_menu\Contract\MegaMenuInterface;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class MegaMenuFormBase extends EntityForm {

  /**
   * The mega menu entity.
   *
   * @var MegaMenuInterface
   */
  protected $entity;

  /**
   * The layout plugin manager.
   *
   * @var LayoutPluginManagerInterface
   */
  protected $layoutPluginManager;

  /**
   * The Drupal menu link tree builder.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTreeInterface;

  /**
   * MegaMenuFormBase constructor.
   *
   * @param LayoutPluginManagerInterface $layoutPluginManager
   *   The layout plugin manager.
   * @param MenuLinkTreeInterface $menuLinkTreeInterface
   *   The Drupal menu link tree builder.
   */
  public function __construct(
    LayoutPluginManagerInterface $layoutPluginManager,
    MenuLinkTreeInterface $menuLinkTreeInterface
  ) {
    $this->layoutPluginManager = $layoutPluginManager;
    $this->menuLinkTreeInterface = $menuLinkTreeInterface;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.layout_plugin'),
      $container->get('menu.link_tree')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['general'] = [
      '#title' => $this->t('General details'),
      '#type' => 'fieldset',
    ];

    $form['general']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t('Enter label for this mega menu.'),
      '#required' => TRUE,
      '#default_value' => $this->entity->label(),
    ];

    $form['general']['name'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#machine_name' => [
        'source' => ['general', 'label'],
        'exists' => [$this, 'megaMenuExists'],
      ],
      '#default_value' => $this->entity->id(),
      '#disabled' => $this->entity->id() ? TRUE : FALSE,
    ];

    $form['general']['menu'] = [
      '#type' => 'select',
      '#title' => $this->t('Menu'),
      '#description' => $this->t('The menu that should be used as a base for the mega menu.'),
      '#empty_option' => $this->t('- Select a menu -'),
      '#options' => $this->getMenusListOptions(),
      '#required' => TRUE,
      '#default_value' => $this->entity->getTargetMenu(),
      '#disabled' => $this->entity->getTargetMenu() ? TRUE : FALSE,
    ];

    return $form;
  }

  /**
   * Get a list of options to be used for the menu select.
   *
   * @return array
   */
  protected function getMenusListOptions() {
    $list = [];

    $menus = $this->entityTypeManager
      ->getStorage('menu')
      ->loadMultiple();

    foreach ($menus as $menu_id => $menu) {
      $list[$menu_id] = $menu->label();
    }

    return $list;
  }

  /**
   * Get a list of layout options suitable for a select list.
   *
   * @return array
   */
  protected function getLayoutOptions() {
    $options = [
      MegaMenuInterface::NO_LAYOUT => $this->t('No layout'),
    ];

    return $options + $this->layoutPluginManager->getLayoutOptions();
  }

  /**
   * Get a list of regions for the specified layout.
   *
   * @param string $layout
   *
   * @return array
   */
  protected function getLayoutRegions($layout) {

    try {
      $definition = $this->layoutPluginManager
        ->getDefinition($layout);
    }
    catch (PluginNotFoundException $e) {
      return [];
    }

    if (!isset($definition['region_names']) || !count($definition['region_names'])) {
      return [];
    }

    return $definition['region_names'];
  }

  /**
   * Get a list of menu items for the specified menu.
   *
   * @param string $menu
   *
   * @return MenuLinkContentInterface[]
   */
  protected function getMenuLinkItems($menu) {
    $properties = [
      'menu_name' => $menu
    ];

    return $this->entityTypeManager
      ->getStorage('menu_link_content')
      ->loadByProperties($properties);
  }

  /**
   * Check to see if a mega menu already exists with the specified name.
   *
   * @param  string $name
   *   The machine name to check for.
   *
   * @return bool
   */
  public function megaMenuExists($name) {
    return $this->entityTypeManager
      ->getStorage('mega_menu')
      ->load($name);
  }
}
