# shopwise-catalog

Products, variants, categories and storefront search for Shopwise. Conventions: `AGENTS.md`.

Run it from `shopwise-platform`: `tilt up -- --only=catalog`, then

```sh
kubectl exec deploy/catalog-http -- php artisan db:seed --force   # demo catalog, once
curl -s localhost:8102/api/categories
curl -s 'localhost:8102/api/products?q=merino&options[color]=Black'
curl -s localhost:8102/api/products/merino-crew-tee
```

Admin endpoints (`/api/admin/...`) need an access token with the `admin` role from identity
(`admin@shopwise.test` after seeding identity).
