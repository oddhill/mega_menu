<?php

namespace Drupal\mega_menu\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\layout_plugin\Plugin\Layout\LayoutInterface;
use Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface;
use Drupal\mega_menu\Contract\MegaMenuInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Mega menu' block.
 *
 * @Block(
 *   id = "mega_menu_block",
 *   admin_label = @Translation("Mega menu"),
 *   category = @Translation("Mega menu"),
 *   deriver = "Drupal\mega_menu\Plugin\Derivative\MegaMenuBlock"
 * )
 */
class MegaMenuBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * @var LayoutPluginManagerInterface
   */
  protected $layoutPluginManager;

  /**
   * @var ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * @var ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * @var AccountInterface
   */
  protected $account;

  /**
   * MegaMenuBlock constructor.
   *
   * {@inheritdoc}
   *
   * @param EntityTypeManagerInterface $entityTypeManager
   *   The Drupal entity type manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entityTypeManager,
    MenuLinkTreeInterface $menuLinkTree,
    LayoutPluginManagerInterface $layoutPluginManager,
    ContextHandlerInterface $contextHandler,
    ContextRepositoryInterface $contextRepository,
    AccountInterface $account
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->menuLinkTree = $menuLinkTree;
    $this->layoutPluginManager = $layoutPluginManager;
    $this->contextHandler = $contextHandler;
    $this->contextRepository = $contextRepository;
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager'),
      $container->get('mega_menu.link_tree'),
      $container->get('plugin.manager.layout_plugin'),
      $container->get('context.handler'),
      $container->get('context.repository'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $mega_menu = $this->loadMegaMenu($this->getDerivativeId());

    // Use the menu tree as the base build.
    $build = $this->buildMegaMenuTree($mega_menu);

    $build['#attributes'] = [
      'data-mega-menu' => $mega_menu->id(),
    ];

    $build['#attached']['library'][] = 'mega_menu/menu';

    return $build;
  }

  /**
   * Build the mega menu link/content tree.
   *
   * @param MegaMenuInterface $mega_menu
   *
   * @return array
   */
  private function buildMegaMenuTree(MegaMenuInterface $mega_menu) {
    $tree = $this->loadMenuTree($mega_menu->getTargetMenu());

    $build = $this->menuLinkTree->build($tree);

    $build['#mega_menu'] = $mega_menu;

    $cacheability = CacheableMetadata::createFromRenderArray($build);
    $cacheability->addCacheableDependency($mega_menu);

    // Add content from the mega menus to the link tree.
    foreach ($build['#items'] as $item_key => $item) {
      $safe_item_key = str_replace('.', '_', $item_key);

      $layout = $mega_menu->getLinkLayout($safe_item_key);

      if ($layout === MegaMenuInterface::NO_LAYOUT) {
        continue;
      }

      $build['#items'][$item_key]['attributes']['data-mega-menu-content-target'] = $item_key;

      /** @var LayoutInterface $layout_plugin */
      $layout_plugin = $this->layoutPluginManager->createInstance($layout);
      $plugin_definition = $layout_plugin->getPluginDefinition();

      // Build an array of the region names in the right order.
      $empty = array_fill_keys(array_keys($plugin_definition['region_names']), []);
      $full = $mega_menu->getBlocksByLink($safe_item_key)->getAllByRegion();

      // Merge it with the actual values to maintain the ordering.
      $block_assignments = array_intersect_key(array_merge($empty, $full), $empty);

      $build['#items'][$item_key]['content'] = [
        '#prefix' => '<div data-mega-menu-content="'.$item_key.'" class="mega-menu-content">',
        '#suffix' => '</div>',
        '#theme' => $plugin_definition['theme'],
        '#settings' => [],
        '#layout' => $plugin_definition,
      ];

      if (isset($plugin_definition['library'])) {
        $build['#items'][$item_key]['content']['#attached']['library'][] = $plugin_definition['library'];
      }

      foreach ($block_assignments as $region => $blocks) {
        $build['#items'][$item_key]['content'][$region] = [];

        /** @var \Drupal\Core\Block\BlockPluginInterface[] $blocks */
        foreach ($blocks as $block_id => $block) {

          if ($block instanceof ContextAwarePluginInterface) {
            $contexts = $this->contextRepository->getRuntimeContexts($block->getContextMapping());
            $this->contextHandler->applyContextMapping($block, $contexts);
          }

          // Make sure the user is allowed to view the block.
          $access = $block->access($this->account, TRUE);
          $cacheability->addCacheableDependency($access);

          // If the user is not allowed then do not render the block.
          if (!$access->isAllowed()) {
            continue;
          }

          $configuration = $block->getConfiguration();

          // Create the render array for the block as a whole.
          // @see template_preprocess_block().
          $block_build = [
            '#theme' => 'block',
            '#attributes' => [],
            '#weight' => $configuration['weight'],
            '#configuration' => $configuration,
            '#plugin_id' => $block->getPluginId(),
            '#base_plugin_id' => $block->getBaseId(),
            '#derivative_plugin_id' => $block->getDerivativeId(),
            '#block_plugin' => $block,
            '#pre_render' => [[$this, 'preRenderBlock']],
            '#cache' => [
              'keys' => ['mega_menu', $mega_menu->id(), 'block', $block_id],
              'tags' => Cache::mergeTags($mega_menu->getCacheTags(), $block->getCacheTags()),
              'contexts' => $block->getCacheContexts(),
              'max-age' => $block->getCacheMaxAge(),
            ],
          ];

          $build['#items'][$item_key]['content'][$region][$block_id] = $block_build;

          $cacheability->addCacheableDependency($block);
        }
      }
    }

    $cacheability->applyTo($build);

    return $build;
  }

  /**
   * Renders the content using the provided block plugin.
   *
   * @param array $build
   *
   * @return array
   */
  public function preRenderBlock($build) {
    $content = $build['#block_plugin']->build();

    unset($build['#block_plugin']);

    // Abort rendering: render as the empty string and ensure this block is
    // render cached, so we can avoid the work of having to repeatedly
    // determine whether the block is empty. E.g. modifying or adding entities
    // could cause the block to no longer be empty.
    if (is_null($content) || Element::isEmpty($content)) {
      $build = [
        '#markup' => '',
        '#cache' => $build['#cache'],
      ];

      // If $content is not empty, then it contains cacheability metadata, and
      // we must merge it with the existing cacheability metadata. This allows
      // blocks to be empty, yet still bubble cacheability metadata, to indicate
      // why they are empty.
      if (!empty($content)) {
        CacheableMetadata::createFromRenderArray($build)
          ->merge(CacheableMetadata::createFromRenderArray($content))
          ->applyTo($build);
      }
    }
    else {
      $build['content'] = $content;
    }

    return $build;
  }

  /**
   * Load a mega menu entity.
   *
   * @param $mega_menu_id
   *
   * @return MegaMenuInterface|null
   */
  private function loadMegaMenu($mega_menu_id) {
    return $this->entityTypeManager
      ->getStorage('mega_menu')
      ->load($mega_menu_id);
  }

  /**
   * Load a list of menu tree elements.
   *
   * @param $menu_id
   *
   * @return MenuLinkTreeElement[]
   */
  private function loadMenuTree($menu_id) {
    $parameters = (new MenuTreeParameters())
      ->setMaxDepth(1);

    return $this->menuLinkTree->load($menu_id, $parameters);
  }

  /**
   * Get a list of options for the mega menu select.
   *
   * @return array
   */
  private function getMegaMenuOptions() {
    $options = [];

    $mega_menus = $this->entityTypeManager
      ->getStorage('mega_menu')
      ->loadMultiple();

    foreach ($mega_menus as $mega_menu) {
      $options[$mega_menu->id()] = $mega_menu->label();
    }

    return $options;
  }
}
