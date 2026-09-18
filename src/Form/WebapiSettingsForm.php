<?php

declare(strict_types=1);

namespace Drupal\webapi\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configures which entity types are exposed and which API links are shown.
 */
class WebapiSettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = parent::create($container);
    $form->entityTypeManager = $container->get('entity_type.manager');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webapi_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['webapi.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('webapi.settings');

    $form['entity_json'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show the View JSON link in entity operations'),
      '#default_value' => $config->get('entity_json'),
    ];

    $form['bundle_docs'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show the View API documentation link in bundle entity operations'),
      '#default_value' => $config->get('bundle_docs'),
    ];

    $options = [];
    foreach ($this->entityTypeManager->getDefinitions() as $id => $definition) {
      $options[$id] = $definition->getLabel();
    }
    natcasesort($options);

    $form['auto_enabled_entity_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Entity types whose new bundles are exposed automatically'),
      '#description' => $this->t('A new bundle of any other entity type is created as a disabled resource.'),
      '#options' => $options,
      '#default_value' => $config->get('auto_enabled_entity_types') ?: [],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $selected = array_values(array_filter($form_state->getValue('auto_enabled_entity_types')));

    $this->config('webapi.settings')
      ->set('entity_json', (bool) $form_state->getValue('entity_json'))
      ->set('bundle_docs', (bool) $form_state->getValue('bundle_docs'))
      ->set('auto_enabled_entity_types', $selected)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
