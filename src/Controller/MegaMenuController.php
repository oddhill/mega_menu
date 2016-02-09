<?php

namespace Drupal\mega_menu\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\mega_menu\Contract\MegaMenuInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class MegaMenuController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * @var BlockManagerInterface
   */
  private $blockManager;
  /**
   * @var ContextRepositoryInterface
   */
  private $contextRepository;

  /**
   * MegaMenuController constructor.
   *
   * @param BlockManagerInterface $blockManager
   * @param ContextRepositoryInterface $contextRepository
   */
  public function __construct(
    BlockManagerInterface $blockManager,
    ContextRepositoryInterface $contextRepository
  ) {
    $this->blockManager = $blockManager;
    $this->contextRepository = $contextRepository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('context.repository')
    );
  }

  /**
   * Get a list of blocks that can be placed in a mega menu.
   *
   * @param Request $request
   * @param MegaMenuInterface $mega_menu
   * @return array
   */
  public function blockLibrary(Request $request, MegaMenuInterface $mega_menu) {

    // Get the query parameters needed.
    $link = $request->query->get('link');
    $region = $request->query->get('region');

    // Only add blocks which work without any available context.
    $blocks = $this->blockManager
      ->getDefinitionsForContexts($this->contextRepository->getAvailableContexts());

    // Order by category, and then by admin label.
    $blocks = $this->blockManager
      ->getSortedDefinitions($blocks);

    $build['filter'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter'),
      '#title_display' => 'invisible',
      '#size' => 30,
      '#placeholder' => $this->t('Filter by block name'),
      '#attributes' => [
        'class' => ['block-filter-text'],
        'data-element' => '.block-add-table',
        'title' => $this->t('Enter a part of the block name to filter by.'),
      ],
    ];

    $headers = [
      $this->t('Block'),
      $this->t('Category'),
      $this->t('Operations'),
    ];

    $build['blocks'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => [],
      '#empty' => $this->t('No blocks available.'),
      '#attributes' => [
        'class' => ['block-add-table'],
      ],
    ];

    // Add each block definition to the table.
    foreach ($blocks as $block_id => $block) {
      $links = [
        'add' => [
          'title' => $this->t('Place block'),
          'url' => Url::fromRoute('mega_menu.block_add', [
            'mega_menu' => $mega_menu->id(),
            'block_id' => $block_id,
          ], [
            'query' => [
              'link' => $link,
              'region' => $region,
            ],
          ]),
          'attributes' => [
            'class' => ['use-ajax'],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => Json::encode([
              'width' => 700,
            ]),
          ],
        ],
      ];

      $build['blocks']['#rows'][] = [
        'title' => [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '<div class="block-filter-text-source">{{ label }}</div>',
            '#context' => [
              'label' => $block['admin_label'],
            ],
          ],
        ],
        'category' => [
          'data' => $block['category'],
        ],
        'operations' => [
          'data' => [
            '#type' => 'operations',
            '#links' => $links,
          ],
        ],
      ];
    }

    return $build;
  }
}
