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

    $routeMatch = \Drupal::routeMatch();
    $userId = $routeMatch->getRawParameter('user');
    
    if ($userId) {
      $current_user = $this->entityTypeManager->getStorage('user')->load($userId);
      if ($current_user->get('field_anchor_genre')->isEmpty()) {
        return [];
      }
      $genreField = $current_user->get('field_anchor_genre')->getValue();
  
      $query = $this->entityTypeManager->getStorage('user')->getQuery()
        ->condition('field_anchor_genre', $genreField)
        ->condition('roles', 'news_anchor')
        ->condition('uid', $userId, '!=')
        ->accessCheck(TRUE);
      
      $userLists = $query->execute();
      if (!empty($userLists)) {

        $query1 = $this->entityTypeManager->getStorage('node')->getQuery()
          ->condition('type', 'news')
          ->condition('uid', $userLists, 'IN')
          ->accessCheck(TRUE);
    
        $nodeLists = $query1->execute();
  
        if (!empty($nodeLists)) {
          $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nodeLists);
      
          $nodeLinks = [];
          foreach ($nodes as $node) {
            $nodeLinks[] = [
              'title' => $node->getTitle(),
              'url' => $node->toUrl()->toString(),
            ];
          }
          
          return [
            '#theme' => 'my_template',
            '#items' => $nodeLinks,
            '#cache' => [
              'contexts' => ['url.path']
            ]
          ];
        }
        return [];
      }

      return [];
    }

    return [];
  }
}
