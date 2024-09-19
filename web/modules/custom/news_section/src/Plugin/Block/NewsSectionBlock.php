<?php

namespace Drupal\news_section\Plugin\Block;


use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\node\Entity\Node;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Block\Attribute\Block;

/**
 * Provides a news section block.
 *
 */
#[Block(
  id: "news_section",
  admin_label: new TranslatableMarkup("News Section"),
)]

final class NewsSectionBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new NewsBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    // Get the current logged-in user.
    $current_user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());

    // Assuming that the genre is a field on the user profile.
    $genre_field = $current_user->get('field_anchor_genre')->entity;

    if ($genre_field instanceof Term) {
      $genre_tid = $genre_field->id();
      
      $query = $this->entityTypeManager->getStorage('node')->getQuery()
        ->condition('type', 'news') 
        ->condition('status', 1)
        ->condition('field_anchor_genre', $genre_tid)
        ->condition('uid', $this->currentUser->id(), '<>')
        ->sort('created', 'DESC')
        ->range(0, 5)
        ->accessCheck(TRUE);
        
      $nids = $query->execute();
      if (!empty($nids)) {
        $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
        $items = [];
        foreach ($nodes as $node) {
          $items[] = [
            $node->toLink()->toString()
          ];
        }

        $build['content'] = [
          '#theme' => 'my_template',
          '#items' => $items,
        ];
      }
    }

    // Only render the block if there are news items to show.
    if (empty($build['content'])) {
      return [];
    }

    return $build;
  }
}
