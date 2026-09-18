<?php

declare(strict_types=1);

namespace Drupal\webapi\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
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
  ) {}

  /**
   * Implements hook_entity_operation().
   */
  #[Hook('entity_operation')]
  public function entityOperation(EntityInterface $entity): array {
    $operations = [];
    $config = $this->configFactory->get('webapi.settings');

    if ($config->get('entity_json')
      && $this->moduleHandler->moduleExists('jsonapi')
      && $this->currentUser->hasPermission('access view json entity operation')) {
      $url = $this->jsonApiUrl($entity);
      if ($url) {
        $operations['webapi_view_json'] = [
          'title' => $this->t('View JSON'),
          'url' => $url,
          'weight' => 90,
        ];
      }
    }

    if ($config->get('bundle_docs')
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
