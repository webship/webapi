# Web API

JSON:API with authentication, authorization and OpenAPI documentation, for easy
ingestion of content by other applications.

## Default recipe

`recipes/default` sets up:

- JSON:API, served from `/api`, with the resource list and counts.
- Read-only JSON:API. Write operations stay off until the site owner turns
  them on.
- HTTP Basic authentication, and OAuth 2.0 with Simple OAuth and consumers.
- OpenAPI documents for JSON:API and REST, rendered with Swagger UI at
  `/api-docs`.
  Swagger UI needs the `swagger-api/swagger-ui` library in
  `web/libraries/swagger-ui`; the Website and Webships project templates
  install it there.
- REST UI, to manage the core REST resources.

## OAuth 2.0 keys

Simple OAuth cannot issue a token without a key pair. When the module is
installed or updated and the site has a private files directory, it generates
the keys in `private://simple_oauth` and saves their paths, unless keys are
already configured. Without a private files directory, set `file_private_path`
in `settings.php` and run `drush simple-oauth:generate-keys private://simple_oauth`;
the status report says "OAuth 2.0 keys: Not available" until a readable key pair
is configured.

## Settings

`/admin/config/services/webapi` decides whether the **View JSON** and **View
API documentation** links appear in entity operations, and which entity types
expose their new bundles automatically.

## Write operations

JSON:API stays read-only. Read-only mode is what the Drupal Security Team
recommends, because past JSON:API advisories have had their root cause in write
handling. Turn writes on at `/admin/config/services/jsonapi` only when the site
needs them, and rebuild the cache afterwards.

## Cross-origin requests

CORS is not configuration: it lives in `sites/default/services.yml` under
`cors.config`, and no module can ship it. Set it per site.
