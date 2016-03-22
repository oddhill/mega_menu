<?php

namespace Drupal\mega_menu\Entity;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\ctools\Plugin\BlockPluginCollection;
use Drupal\mega_menu\Contract\MegaMenuInterface;

/**
 * Defines the Context entity.
 *
 * @ConfigEntityType(
 *   id = "mega_menu",
 *   label = @Translation("Mega menu"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\mega_menu\Form\MegaMenuAddForm",
 *       "edit" = "Drupal\mega_menu\Form\MegaMenuEditForm",
 *       "delete" = "Drupal\mega_menu\Form\MegaMenuDeleteForm",
 *     },
 *     "list_builder" = "Drupal\mega_menu\MegaMenuListBuilder",
 *   },
 *   links = {
 *     "edit-form" = "/admin/structure/mega-menu/{mega_menu}",
 *     "delete-form" = "/admin/structure/mega-menu/{mega_menu}/delete",
 *     "collection" = "/admin/structure/mega-menu",
 *   },
 *   admin_permission = "administer mega menus",
 *   entity_keys = {
 *     "id" = "name",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "name",
 *     "label",
 *     "menu",
 *     "links",
 *     "render_content_outside",
 *   }
 * )
 */
class MegaMenu extends ConfigEntityBase implements MegaMenuInterface {

  /**
   * The machine name of this mega menu.
   *
   * @var string
   */
  protected $name;

  /**
   * The human readable label of this mega menu.
   *
   * @var string
   */
  protected $label;

  /**
   * The machine name of the menu this mega menu is configured for.
   *
   * @var string
   */
  protected $menu;

  /**
   * An array of block configuration values keyed by menu link id.
   *
   * @var array
   */
  protected $blocks = [];

  /**
   * Contains a block collection instance if the blocks has been fetched at
   * least once.
   *
   * @var BlockPluginCollection[]
   */
  protected $block_collections = [];

  /**
   * The configuration for the mega menu links. The mega menu does not actually
   * hold any links on it's own, it just has configuration for the links that
   * exists in the referenced menu.
   *
   * @var array
   */
  protected $links = [];

  /**
   * If the mega menu content should be rendered outside of the list.
   *
   * @var boolean
   */
  protected $render_content_outside = FALSE;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetMenu() {
    return $this->menu;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetMenuLabel() {
    return $this->entityTypeManager()
      ->getStorage('menu')
      ->load($this->menu)
      ->label();
  }

  /**
   * Get a list of block plugins associated with this mega menu.
   *
   * @return BlockPluginCollection
   */
  public function getBlocksByLink($link_id) {
    if (!isset($this->block_collections[$link_id])) {
      $block_manager = \Drupal::service('plugin.manager.block');

      $blocks = (isset($this->links[$link_id]['blocks']) && is_array($this->links[$link_id]['blocks']))
        ? $this->links[$link_id]['blocks']
        : [];

      $this->block_collections[$link_id] = new BlockPluginCollection($block_manager, $blocks);
    }

    return $this->block_collections[$link_id];
  }

  /**
   * Get an array of all blocks keyed by their link id.
   *
   * @return BlockPluginCollection[]
   */
  public function getAllBlocksSortedByLink() {
    foreach ($this->links as $link_key => $link) {
      $this->getBlocksByLink($link_key);
    }

    return $this->block_collections;
  }

  /**
   * Get the specified block.
   *
   * @param string $block_id
   *   The id of the block to get.
   *
   * @return BlockPluginInterface
   */
  public function getBlock($link_id, $block_id) {
    return $this->getBlocksByLink($link_id)->get($block_id);
  }

  /**
   * Add a new block.
   *
   * @param array $configuration
   *   An array of configuration values for this block.
   *
   * @return $this
   */
  public function addBlock($link_id, $block_id, $configuration) {
    $this->getBlocksByLink($link_id)->addInstanceId($block_id, $configuration);

    return $this;
  }

  /**
   * Update an existing block.
   *
   * @param $block_id
   *   The id of the block to update.
   * @param $configuration
   *   An array of configuration values for this block.
   *
   * @return $this
   */
  public function updateBlock($link_id, $block_id, $configuration) {
    $block = $this->getBlocksByLink($link_id)->get($block_id);
    $current_configuration = $block->getConfiguration();

    $this->getBlocksByLink($link_id)
      ->setInstanceConfiguration($block_id, $configuration + $current_configuration);

    return $this;
  }

  /**
   * Remove a block from the configuration.
   *
   * @param $block_id
   *   The id of the block to remove.
   *
   * @return $this
   */
  public function removeBlock($link_id, $block_id) {
    $this->getBlocksByLink($link_id)->removeInstanceId($block_id);

    return $this;
  }

  /**
   * Check if a block exists.
   *
   * @param string $link_id
   * @param string $block_id
   *
   * @return bool
   */
  public function hasBlock($link_id, $block_id) {
    return $this->getBlocksByLink($link_id)->has($block_id);
  }

  /**
   * Get the selected layout for a link.
   *
   * @param string $link_id
   *
   * @return string|null
   */
  public function getLinkLayout($link_id) {
    if (isset($this->links[$link_id]['layout'])) {
      return $this->links[$link_id]['layout'];
    }

    return MegaMenuInterface::NO_LAYOUT;
  }

  /**
   * Set the layout of a link.
   *
   * @param string $link_id
   * @param string $layout_id
   *
   * @return $this
   */
  public function setLinkLayout($link_id, $layout_id) {
    $this->links[$link_id]['layout'] = $layout_id;
    return $this;
  }

  /**
   * Pre-save hook.
   *
   * @param EntityStorageInterface $storage
   */
  public function preSave(EntityStorageInterface $storage) {
    // Save block configuration to the links.
    foreach ($this->block_collections as $key => $collection) {
      $this->links[$key]['blocks'] = $collection->getConfiguration();
    }
  }

  /**
   * Check to see if content should be rendered outside of the list.
   *
   * @return bool
   */
  public function shouldRenderContentOutside() {
    return $this->render_content_outside;
  }
}
