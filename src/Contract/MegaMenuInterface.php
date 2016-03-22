<?php

namespace Drupal\mega_menu\Contract;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\ctools\Plugin\BlockPluginCollection;

interface MegaMenuInterface extends ConfigEntityInterface {

  /**
   * Represents a mega menu link item that does not use a layout.
   */
  const NO_LAYOUT = 'mega_menu.no_layout';

  /**
   * Represents a mega menu link item that does not have a region. And also
   * used to represent a no region in lists.
   */
  const NO_REGION = 'mega_menu.no_region';

  /**
   * Get the machine name of the target menu.
   *
   * @return string
   */
  public function getTargetMenu();

  /**
   * Get the label of the target menu entity.
   *
   * @return null|string
   */
  public function getTargetMenuLabel();

  /**
   * Get a list of all blocks associated with a link.
   *
   * @return BlockPluginCollection
   */
  public function getBlocksByLink($link_id);

  /**
   * Get an array of all blocks keyed by their link id.
   *
   * @return BlockPluginCollection[]
   */
  public function getAllBlocksSortedByLink();

  /**
   * Get the specified block.
   *
   * @param $link_id
   *   The id of the link to get blocks for.
   * @param string $block_id
   *   The id of the block to get.
   *
   * @return BlockPluginCollection
   */
  public function getBlock($link_id, $block_id);

  /**
   * Add a new block.
   *
   * @param $link_id
   *   The id of the link to add the block to.
   * @param array $configuration
   *   An array of configuration values for this block.
   *
   * @return $this
   */
  public function addBlock($link_id, $block_id, $configuration);

  /**
   * Update a block instance.
   *
   * @param $link_id
   *   The id of the link to add the block to.
   * @param $block_id
   *   The id of the block to update.
   * @param $configuration
   *   An array of configuration values for this block.
   *
   * @return $this
   */
  public function updateBlock($link_id, $block_id, $configuration);

  /**
   * Remove a block from the configuration.
   *
   * @param $link_id
   * @param $block_id
   *   The id of the block to remove.
   *
   * @return $this
   */
  public function removeBlock($link_id, $block_id);

  /**
   * Check if a block exists.
   *
   * @param string $link_id
   * @param string $block_id
   *
   * @return bool
   */
  public function hasBlock($link_id, $block_id);

  /**
   * Get the selected layout for a link.
   *
   * @param string $link_id
   *
   * @return string|null
   */
  public function getLinkLayout($link_id);

  /**
   * Set the layout of a link.
   *
   * @param string $link_id
   * @param string $layout_id
   *
   * @return $this
   */
  public function setLinkLayout($link_id, $layout_id);

  /**
   * Check to see if content should be rendered outside of the list.
   *
   * @return bool
   */
  public function shouldRenderContentOutside();
}
