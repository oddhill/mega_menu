<?php

namespace Drupal\mega_menu\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
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
   * The configuration for the mega menu links. The mega menu does not actually
   * hold any links on it's own, it just has configuration for the links that
   * exists in the referenced menu.
   *
   * @var array
   */
  protected $links = [];

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
}
