<?php

declare(strict_types=1);

namespace Drupal\about_us_module\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Cache\Cache;

/**
 * Provides a About us module form.
 */
final class BEForm extends FormBase {

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
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'about_us_module_b_e';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#required' => TRUE,
    ];

    $form['designation'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Designation'),
      '#required' => TRUE,
    ];

    $form['linkedin_link'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Linkedin Link'),
      '#required' => TRUE,
    ];

    $form['my_file'] = [
      '#type' => 'managed_file',
      '#title' => 'Profile Image',
      '#name' => 'my_custom_file',
      '#upload_location' => 'public://'
    ];

    $anchors = \Drupal::entityTypeManager()->getStorage('user')
      ->loadByProperties(['roles' => 'news_anchor']);
    
    $anchorOptions = [];
    foreach ($anchors as $anchor) {
      $anchorOptions[$anchor->id()] = $anchor->getDisplayName();
    }

    $form['anchor_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Best Anchor of Week'),
      '#options' => $anchorOptions,
    ];
    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Send'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // @todo Validate the form here.
    // Example:
    // @code
    //   if (mb_strlen($form_state->getValue('message')) < 10) {
    //     $form_state->setErrorByName(
    //       'message',
    //       $this->t('Message should be at least 10 characters.'),
    //     );
    //   }
    // @endcode
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    dd( $form_state);
    $this->database->insert('be_table')
      ->fields([
        'name' => $form_state->getValue('name'),
        'designation' => $form_state->getValue('designation'),
        'profile_link' => $form_state->getValue('linkedin_link'),
      ])
      ->execute();
    
    $queryAnchor = $this->database->select('anchor_of_week','a');
    $result = $queryAnchor->fields('a',['anchor'])
      ->execute()->fetchAll();
    
    if (!$result) {
      $this->database->insert('anchor_of_week')
      ->fields([
        'anchor' => $form_state->getValue('anchor_name'),
      ])
      ->execute();
    }
    
    else {
      $this->database->update('anchor_of_week')
        ->fields([
          'anchor' => $form_state->getValue('anchor_name'),
        ])
        ->execute();
    }
    
    Cache::invalidateTags(['about_data']);
  }

}
