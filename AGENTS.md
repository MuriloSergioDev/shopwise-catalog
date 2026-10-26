# shopwise-catalog

Products, variants, categories and storefront search. Prices and stock are not here: pricing and inventory key
them by SKU and learn SKUs from catalog's events. Created from `shopwise-service-template`: structure, checks and
how to run PHP are described there and still apply.

## Service facts
- **Owns**: `categories` (tree via `parent_id`), `products` (draft/published, images as JSON, Postgres `search` tsvector), `variants` (SKU unique, never reused), `variant_options` (one row per option name/value). Database `catalog`.
- **Publishes** (key = productId):
  - `catalog.variant-changed` on variant create/update, full state (`schemas/events/catalog/variant-changed.v1.json`). Not recorded when an update changes nothing.
  - `catalog.product-published` when a draft goes live, with its active variants (`schemas/events/catalog/product-published.v1.json`).
- **Consumes**: nothing.
- **HTTP** (BFF only) — contract `shopwise-contracts/openapi/catalog.yaml`. Public: `GET /api/categories`, `GET /api/products` (search, filters, facets), `GET /api/products/{slug}`. Admin (`auth.jwt:admin`): `POST /api/admin/products`, `POST /api/admin/products/{id}/publish`, `PATCH /api/admin/variants/{id}`.
- **REST dependencies**: identity's JWKS (kit `Jwks`, `IDENTITY_JWKS_URL`) for admin routes only.
- **Local URL**: http://localhost:8102 (Tilt port-forward).

## Rules
- Storefront endpoints only show published products and active variants. Drafts exist so inventory and pricing can prepare SKUs before launch.
- Reads go through `app/Queries` (no writes, no outbox); writes through `app/Actions`.
- Search: Postgres uses the generated `products.search` column (`websearch_to_tsquery('english', …)`, ranked); SQLite tests fall back to LIKE per word. Keep both paths behaving the same for the tests.
- Facets are counted without their own filter (category facet ignores `category`, `color` facet ignores `options[color]`).
- Options are a map in the HTTP API (rendered as an object so empty is `{}`) but a sorted `{name, value}` list in events: the kit's outbox decodes envelopes to PHP arrays, which would turn an empty map into `[]`.

## Data
- `db:seed` creates a demo catalog (Apparel > Tops/Bottoms, Home > Kitchen; 7 published products) through the actions, so it also records events. Running it again does nothing.
