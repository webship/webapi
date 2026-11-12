<?php

declare(strict_types=1);

namespace Drupal\webapi\Hook;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Hook implementations for the Web API module.
 */
class WebapiHooks {

  use StringTranslationTrait;

  public function __construct(
    #[Autowire(service: 'config.factory')]
    protected ConfigFactoryInterface $configFactory,
    #[Autowire(service: 'module_handler')]
    protected ModuleHandlerInterface $moduleHandler,
    #[Autowire(service: 'current_user')]
    protected AccountProxyInterface $currentUser,
    #[Autowire(service: 'file_system')]
    protected FileSystemInterface $fileSystem,
  ) {}

  /**
   * Implements hook_entity_operation().
   */
  #[Hook('entity_operation')]
  public function entityOperation(EntityInterface $entity, CacheableMetadata $cacheability): array {
    $operations = [];
    $config = $this->configFactory->get('webapi.settings');
    $cacheability->addCacheableDependency($config)->addCacheContexts(['user.permissions']);

    if ($config->get('entity_json')
      && $this->moduleHandler->moduleExists('jsonapi')
      && $this->currentUser->hasPermission('access view json entity operation')) {
      $url = $this->jsonApiUrl($entity);
      if ($url) {
        $operations['webapi_view_json'] = [
          'title' => $this->t('View JSON'),
          'url' => $url,
          'weight' => 90,
          // The operations builder of a view adds a destination, which
          // JSON:API refuses; linkAlter() removes it again.
          'webapi_no_destination' => TRUE,
        ];
      }
    }

    // The setting is about bundle entities, such as content types and
    // vocabularies, the entity types that are the bundles of another one.
    if ($config->get('bundle_docs')
      && $entity->getEntityType()->getBundleOf()
      && $this->moduleHandler->moduleExists('openapi_ui_swagger')
      && $this->currentUser->hasPermission('access view api docs entity operation')) {
      $operations['webapi_view_api_docs'] = [
        'title' => $this->t('View API documentation'),
        'url' => Url::fromRoute('openapi.documentation', [
          'openapi_ui' => 'swagger',
          'openapi_generator' => 'jsonapi',
        ]),
        'weight' => 95,
      ];
    }

    return $operations;
  }

  /**
   * Implements hook_runtime_requirements().
   *
   * Simple OAuth does not report missing keys, and /oauth/token then answers
   * 500 "You need to set the OAuth2 private key."
   */
  #[Hook('runtime_requirements')]
  public function runtimeRequirements(): array {
    if (!$this->moduleHandler->moduleExists('simple_oauth')) {
      return [];
    }
    $config = $this->configFactory->get('simple_oauth.settings');
    foreach (['private_key', 'public_key'] as $name) {
      $path = $config->get($name);
      if ($path && is_readable($this->fileSystem->realpath($path) ?: $path)) {
        continue;
      }
      return [
        'webapi_oauth_keys' => [
          'title' => $this->t('OAuth 2.0 keys'),
          'value' => $this->t('Not available'),
          'description' => $this->t('Simple OAuth cannot issue tokens without a readable key pair. Set a private file path in settings.php and run <code>drush simple-oauth:generate-keys private://simple_oauth</code>, then set the key paths at <a href=":url">the Simple OAuth settings</a>.', [
            ':url' => Url::fromRoute('oauth2_token.settings')->toString(),
          ]),
          'severity' => RequirementSeverity::Warning,
        ],
      ];
    }
    return [
      'webapi_oauth_keys' => [
        'title' => $this->t('OAuth 2.0 keys'),
        'value' => $this->t('Configured'),
        'severity' => RequirementSeverity::OK,
      ],
    ];
  }

  /**
   * Implements hook_link_alter().
   *
   * JSON:API answers 400 to any query parameter it does not know, and the
   * operations builder adds "destination" to every operation link. A link that
   * sets the webapi_no_destination option goes without it.
   */
  #[Hook('link_alter')]
  public function linkAlter(array &$variables): void {
    if (empty($variables['options']['webapi_no_destination'])) {
      return;
    }
    unset($variables['options']['query']['destination'], $variables['options']['webapi_no_destination']);
  }

  /**
   * Builds the JSON:API link of an entity, when it has one.
   */
  protected function jsonApiUrl(EntityInterface $entity): ?Url {
    $route_name = 'jsonapi.' . $entity->getEntityTypeId() . '--' . $entity->bundle() . '.individual';
    try {
      return Url::fromRoute($route_name, ['entity' => $entity->uuid()]);
    }
    catch (\Exception) {
      // The entity type is not exposed as a JSON:API resource.
      return NULL;
    }
  }

}
