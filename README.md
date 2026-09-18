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
- REST UI, to manage the core REST resources.

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
