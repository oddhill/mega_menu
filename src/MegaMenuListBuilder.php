<?php

namespace Drupal\mega_menu;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

class MegaMenuListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'label' => $this->t('Label'),
      'menu' => $this->t('Menu'),
    ];

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [
      'label' => $entity->label(),
      'menu' => $entity->getTargetMenuLabel()
    ];

    return $row + parent::buildRow($entity);
  }
}
