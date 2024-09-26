<?php

declare(strict_types=1);

namespace Drupal\about_us_module\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Cache\Cache;

/**
 * Returns responses for About us Module routes.
 */
final class AboutUsModuleController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new CheckTableController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
    );
  }

  /**
   * Builds the response.
   */
  public function render(): array {
    $query = $this->database->select('be_table', 's')
      ->fields('s', ['name', 'designation', 'profile_link']);
    $results = $query->execute()->fetchAll();

    $queryAnchor = $this->database->select('anchor_of_week','a');
    $result = $queryAnchor->fields('a',['anchor'])
      ->execute()->fetchAll();

    $anchorId = $result[0]->anchor;
    $user = User::load($anchorId)->getDisplayName();

    $query = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
      ->condition('type', 'news')
      ->condition('status', '1')
      ->condition('uid', $anchorId)
      ->accessCheck(TRUE)
      ->sort('created', 'DESC')
      ->range(0, 3);

      $nodeIds = $query->execute();

      $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nodeIds);
      $nodeLinks = [];
      foreach ($nodes as $node) {
        $nodeLinks[] = [
          'title' => $node->getTitle(),
          'url' => $node->toUrl()->toString(), // Generate the URL here
        ];
      }

    return[
      '#theme' => 'my_template',
      '#rows' => $results,
      '#nodes' => $nodeLinks,
      '#user' => $user,
      '#cache' => [
        'tags' => ['about_data'],
      ]
    ];
  }

}
