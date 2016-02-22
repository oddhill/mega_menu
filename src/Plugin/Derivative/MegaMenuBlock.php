<?php

namespace Drupal\mega_menu\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\mega_menu\Contract\MegaMenuInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MegaMenuBlock extends DeriverBase implements ContainerDeriverInterface {

  /**
   * @var EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * MegaMenuBlock constructor.
   *
   * @param EntityTypeManagerInterface $entityTypeManager
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->getMegaMenus() as $name => $mega_menu) {
      $this->derivatives[$name] = $base_plugin_definition;
      $this->derivatives[$name]['admin_label'] = $mega_menu->label();
      $this->derivatives[$name]['config_dependencies']['config'] = [$mega_menu->getConfigDependencyName()];
    }

    return $this->derivatives;
  }

  /**
   * Get all mega menus.
   *
   * @return MegaMenuInterface[]
   */
  private function getMegaMenus() {
    return $this->entityTypeManager
      ->getStorage('mega_menu')
      ->loadMultiple();
  }
}
