<?php

namespace Drupal\mega_menu\Menu;

use Drupal\Core\Menu\MenuLinkTree;

class MegaMenuLinkTree extends MenuLinkTree {

  /**
   * {@inheritdoc}
   */
  public function build(array $tree) {
    $build = parent::build($tree);

    /** @var \Drupal\Core\Menu\MenuLinkInterface $first_link */
    $first_link = reset($tree)->link;
    $menu_name = $first_link->getMenuName();

    // Add a more specific theme suggestion to differentiate this rendered
    // menu from others.
    $build['#menu_name'] = $menu_name;
    $build['#theme'] = 'menu__mega_menu__' . strtr($menu_name, '-', '_');

    return $build;
  }
}
