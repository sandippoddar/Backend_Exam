<?php

declare(strict_types=1);

namespace Drupal\about_us_module\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
    return[
      '#theme' => 'my_template',
      '#rows' => $results,
      '#cache' => [
        'tags' => ['about_data'],
      ]
    ];
  }

}
