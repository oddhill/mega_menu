<?php

namespace Drupal\mega_menu\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\mega_menu\Contract\MegaMenuInterface;

class MegaMenuEditForm extends MegaMenuFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $menu_links_elements = $this->getMenuLinkElements($this->entity->getTargetMenu());
    $layout_options = $this->getLayoutOptions();

    $table_header = [
      'label' => $this->t('Label'),
      'weight' => $this->t('Weight'),
      'operations' => $this->t('Operations'),
    ];

    $form['links'] = [
      '#type' => 'container',
      '#title' => $this->t('Menu links'),
      '#tree' => TRUE,
    ];

    foreach ($menu_links_elements as $link_element_id => $link_element) {
      $regions = $form_state->hasValue(['links', $link_element_id, 'layout'])
        ? $this->getLayoutRegions($form_state->getValue(['links', $link_element_id, 'layout']))
        : $this->getDefaultRegions();

      $link_id = Html::getId($link_element_id);

      $form['links'][$link_element_id] = [
        '#type' => 'fieldset',
        '#title' => $link_element->link->getTitle(),
      ];

      $form['links'][$link_element_id]['layout'] = [
        '#type' => 'select',
        '#title' => $this->t('Layout'),
        '#options' => $layout_options,
        '#ajax' => [
          'callback' => '::onLayoutSelect',
          'wrapper' => "mega-menu-link-{$link_id}-blocks-wrapper",
        ],
        '#attributes' => [
          'data-link-element-id' => $link_element_id,
        ],
      ];

      $form['links'][$link_element_id]['blocks'] = [
        '#prefix' => '<div id="mega-menu-link-'.$link_id.'-blocks-wrapper">',
        '#suffix' => '</div>',
        '#type' => 'table',
        '#header' => $table_header,
        '#empty' => $this->t('There are no regions for blocks.'),
      ];

      foreach ($regions as $region_key => $region_name) {
        // Tabledrag stuff.
        $form['links'][$link_element_id]['blocks']['#tabledrag'][] = [
          'action' => 'match',
          'relationship' => 'sibling',
          'group' => 'block-region-select',
          'subgroup' => 'block-region-' . $region_key,
          'hidden' => FALSE,
        ];

        $form['links'][$link_element_id]['blocks']['#tabledrag'][] = [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'block-weight',
          'subgroup' => 'block-weight-' . $region_key,
        ];

        // Regions.
        $form['links'][$link_element_id]['blocks'][$region_key] = [
          '#attributes' => [
            'class' => ['region-title', 'region-title-' . $region_key],
            'no_striping' => TRUE,
          ],
        ];

        if ($region_key === MegaMenuInterface::NO_REGION) {
          $form['links'][$link_element_id]['blocks'][$region_key]['title'] = [
            '#markup' => $region_name,
            '#wrapper_attributes' => [
              'colspan' => 3,
            ],
          ];
        }
        else {
          $form['links'][$link_element_id]['blocks'][$region_key]['title'] = [
            '#theme_wrappers' => [
              'container' => [
                '#attributes' => [
                  'class' => ['region-title__action']
                ],
              ]
            ],
            '#prefix' => $region_name,
            '#type' => 'link',
            '#title' => $this->t('Place block <span class="visually-hidden">in the %region region</span>', [
              '%region' => $region_name,
            ]),
            '#url' => Url::fromRoute('mega_menu.block_library', [
              'mega_menu' => $this->entity->id(),
            ], [
              'query' => [
                'link' => $link_element_id,
                'region' => $region_key,
              ],
            ]),
            '#wrapper_attributes' => [
              'colspan' => 3,
            ],
            '#attributes' => [
              'class' => ['use-ajax', 'button', 'button--small'],
              'data-dialog-type' => 'modal',
              'data-dialog-options' => Json::encode([
                'width' => 700,
              ]),
            ],
          ];
        }

        $form['links'][$link_element_id]['blocks'][$region_key . '-message'] = [
          '#attributes' => [
            'class' => [
              'region-message',
              'region-' . $region_key . '-message',
              empty($blocks) ? 'region-empty' : 'region-populated',
            ],
          ],
        ];

        $form['links'][$link_element_id]['blocks'][$region_key . '-message']['message'] = [
          '#markup' => '<em>' . $this->t('No blocks in this region') . '</em>',
          '#wrapper_attributes' => [
            'colspan' => 3,
          ],
        ];
      }
    }

    return $form;
  }

  public function onLayoutSelect(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $link_element_id = $triggering_element['#attributes']['data-link-element-id'];

    return $form['links'][$link_element_id]['blocks'];
  }
}
