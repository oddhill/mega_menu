<?php

namespace Drupal\mega_menu\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Block\BlockPluginInterface;
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
      'category' => $this->t('Category'),
      'region' => $this->t('Region'),
      'weight' => $this->t('Weight'),
      'operations' => $this->t('Operations'),
    ];

    $form['links'] = [
      '#type' => 'container',
      '#title' => $this->t('Menu links'),
      '#tree' => TRUE,
    ];

    $blocks = $this->entity->getAllBlocksSortedByLink();

    foreach ($menu_links_elements as $link_element_id => $link_element) {
      // Replace any dots with a underscore since dots are not supported as
      // keys in the configuration data.
      $link_element_id = str_replace('.', '_', $link_element_id);

      $link_layout = $this->entity->getLinkLayout($link_element_id);
      $regions = $this->getLayoutRegions($link_layout);

      $link_id = Html::getId($link_element_id);

      $form['links'][$link_element_id] = [
        '#type' => 'fieldset',
        '#title' => $link_element->link->getTitle(),
      ];

      $form['links'][$link_element_id]['layout'] = [
        '#type' => 'select',
        '#title' => $this->t('Layout'),
        '#options' => $layout_options,
        '#default_value' => $link_layout,
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

      // Add regions.
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
              'colspan' => 5,
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
              'colspan' => 5,
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

        $blocks_by_region = isset($blocks[$link_element_id])
          ? $blocks[$link_element_id]->getAllByRegion()
          : [];

        $region_message_class = empty($blocks_by_region[$region_key])
          ? 'region-empty'
          : 'region-populated';

        $form['links'][$link_element_id]['blocks'][$region_key . '-message'] = [
          '#attributes' => [
            'class' => [
              'region-message',
              'region-' . $region_key . '-message',
              $region_message_class
            ],
          ],
        ];

        $form['links'][$link_element_id]['blocks'][$region_key . '-message']['message'] = [
          '#markup' => '<em>' . $this->t('No blocks in this region') . '</em>',
          '#wrapper_attributes' => [
            'colspan' => 5,
          ],
        ];

        if (!isset($blocks_by_region[$region_key])) {
          continue;
        }

        /** @var BlockPluginInterface $block */
        foreach ($blocks_by_region[$region_key] as $block_id => $block) {
          if (!isset($form['links'][$link_element_id])) {
            continue;
          }

          $operations = [
            'edit' => [
              'title' => $this->t('Edit'),
              'url' => Url::fromRoute('mega_menu.block_edit', [
                'mega_menu' => $this->entity->id(),
                'block_id' => $block_id,
              ], [
                'query' => [
                  'link' => $link_element_id,
                  'region' => $region_key,
                ],
              ]),
              'attributes' => [
                'class' => ['use-ajax', 'button', 'button--small'],
                'data-dialog-type' => 'modal',
                'data-dialog-options' => Json::encode([
                  'width' => 700,
                ]),
              ],
            ],
            'delete' => [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('mega_menu.block_delete', [
                'mega_menu' => $this->entity->id(),
                'block_id' => $block_id,
              ], [
                'query' => [
                  'link' => $link_element_id,
                ],
              ]),
              'attributes' => [
                'class' => ['use-ajax', 'button', 'button--small'],
                'data-dialog-type' => 'modal',
                'data-dialog-options' => Json::encode([
                  'width' => 700,
                ]),
              ],
            ],
          ];

          $configuration = $block->getConfiguration();

          $form['links'][$link_element_id]['blocks'][$block_id] = [
            '#attributes' => [
              'class' => ['draggable'],
            ],
            'label' => [
              '#markup' => $block->label(),
            ],
            'category' => [
              '#markup' => $block->getPluginDefinition()['category'],
            ],
            'region' => [
              '#type' => 'select',
              '#title' => $this->t('Region for @block block', ['@block' => $block->label()]),
              '#title_display' => 'invisible',
              '#default_value' => $region_key,
              '#options' => $regions,
              '#attributes' => [
                'class' => [
                  'block-region-select',
                  'block-region-' . $region_key
                ],
              ],
            ],
            'weight' => [
              '#type' => 'weight',
              '#default_value' => isset($configuration['weight']) ? $configuration['weight'] : 0,
              '#title' => $this->t('Weight for @block block', ['@block' => $block->label()]),
              '#title_display' => 'invisible',
              '#attributes' => [
                'class' => ['block-weight', 'block-weight-' . $configuration['region']],
              ],
            ],
            'operations' => [
              '#type' => 'operations',
              '#links' => $operations,
            ],
          ];
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $entity = clone $this->entity;

    $entity->set('label', $form_state->getValue('label'));
    $entity->set('name', $form_state->getValue('name'));
    $entity->set('menu', $form_state->getValue('menu'));
    $entity->set('render_content_outside', $form_state->getValue('render_content_outside'));

    foreach ($form_state->getValue('links', []) as $link_key => $link) {
      $entity->setLinkLayout($link_key, $link['layout']);

      if (!is_array($link['blocks']) || !count($link['blocks'])) {
        continue;
      }

      foreach ($link['blocks'] as $block_id => $configuration) {
        $this->entity->updateBlock($link_key, $block_id, $configuration);
      }
    }

    return $entity;
  }

  /**
   * AJAX callback for when a layout is selected.
   *
   * @param array $form
   *   The form array.
   * @param FormStateInterface $form_state
   *   The form state instance.
   *
   * @return mixed
   */
  public function onLayoutSelect(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $link_element_id = $triggering_element['#attributes']['data-link-element-id'];

    $this->save($form, $form_state);

    return $form['links'][$link_element_id]['blocks'];
  }
}
