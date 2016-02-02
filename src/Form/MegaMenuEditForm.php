<?php

namespace Drupal\mega_menu\Form;

use Drupal\Core\Form\FormStateInterface;

class MegaMenuEditForm extends MegaMenuFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $menu_links = $this->getMenuLinkItems($this->entity->getTargetMenu());
    $layout_options = $this->getLayoutOptions();

    $table_header = [
      'label' => $this->t('Label'),
      'operations' => $this->t('Operations'),
    ];

    $form['links'] = [
      '#type' => 'container',
      '#title' => $this->t('Menu links'),
      '#tree' => TRUE,
    ];

    foreach ($menu_links as $link_id => $link) {
      $regions = $form_state->hasValue(['links', $link_id, 'layout'])
        ? $this->getLayoutRegions($form_state->getValue(['links', $link_id, 'layout']))
        : [];

      $form['links'][$link_id] = [
        '#type' => 'fieldset',
        '#title' => $this->t($link->label()),
        '#attributes' => [
          'id' => "mega-menu-link-fieldset-{$link_id}",
        ],
      ];

      $form['links'][$link_id]['layout'] = [
        '#type' => 'select',
        '#title' => $this->t('Layout'),
        '#options' => $layout_options,
        '#ajax' => [
          'callback' => '::onLayoutSelect',
          'wrapper' => "mega-menu-link-fieldset-{$link_id}",
        ],
      ];

      $form['links'][$link_id]['blocks'] = [
        '#type' => 'table',
        '#title' => $this->t('Blocks'),
        '#header' => $table_header,
        '#rows' => [],
        '#empty' => $this->t('There are no regions for blocks.'),
      ];
    }

    return $form;
  }

  public function onLayoutSelect(array $form, FormStateInterface $form_state) {

  }
}
