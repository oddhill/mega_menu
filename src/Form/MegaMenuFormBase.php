<?php

namespace Drupal\mega_menu\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface;
use Drupal\mega_menu\Contract\MegaMenuInterface;
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
   * @var MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * MegaMenuFormBase constructor.
   *
   * @param LayoutPluginManagerInterface $layoutPluginManager
   *   The layout plugin manager.
   * @param MenuLinkTreeInterface $menuLinkTree
   *   The Drupal menu link tree builder.
   */
  public function __construct(
    LayoutPluginManagerInterface $layoutPluginManager,
    MenuLinkTreeInterface $menuLinkTree
  ) {
    $this->layoutPluginManager = $layoutPluginManager;
    $this->menuLinkTree = $menuLinkTree;
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

    $form['#attached']['library'][] = 'mega_menu/admin';

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

    $form['general']['render_content_outside'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Render content outside of list'),
      '#description' => $this->t('Check this box if you want to render the mega menu dropdown content outside of the list.'),
      '#default_value' => $this->entity->get('render_content_outside'),
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
   * Get a list of default regions. This includes the no region option.
   *
   * @return array
   */
  protected function getDefaultRegions() {
   return [];
  }

  /**
   * Get a list of regions for the specified layout.
   *
   * @param string $layout
   *
   * @return array
   */
  protected function getLayoutRegions($layout) {
    $default_regions = $this->getDefaultRegions();

    if ($layout === MegaMenuInterface::NO_LAYOUT) {
      return $default_regions;
    }

    $definition = $this->layoutPluginManager
      ->getDefinition($layout, FALSE);

    if (!$definition) {
      return $default_regions;
    }

    if (!isset($definition['region_names']) || !count($definition['region_names'])) {
      return $default_regions;
    }

    return $definition['region_names'] + $default_regions;
  }

  /**
   * Get a list of menu link elements for the specified menu.
   *
   * @param string $menu
   *
   * @return MenuLinkTreeElement[]
   */
  protected function getMenuLinkElements($menu) {
    $parameters = (new MenuTreeParameters())
      ->setMaxDepth(1);

    return $this->menuLinkTree->load($menu, $parameters);
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
