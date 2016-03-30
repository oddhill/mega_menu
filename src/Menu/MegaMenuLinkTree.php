<?php

namespace Drupal\mega_menu\Menu;

use Drupal\Core\Menu\MenuLinkTree;

class MegaMenuLinkTree extends MenuLinkTree {

  /**
   * {@inheritdoc}
   */
  public function build(array $tree) {
    $build = parent::build($tree);

    $build['#theme'] = 'menu__mega_menu';

    return $build;
  }
}
