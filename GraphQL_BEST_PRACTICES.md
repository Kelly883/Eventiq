# GraphQL Best Practices

## Query limits

GraphQL now enforces depth and complexity limits via:
- `backend/config/graphql.php` security settings
- `backend/app/GraphQL/Concerns/ComplexityAnalyzer.php`

Environment variables:
- `GRAPHQL_QUERY_MAX_DEPTH` (default: `8`)
- `GRAPHQL_QUERY_MAX_COMPLEXITY` (default: `120`)

## Resolver optimization

Resolvers should:
- Scope data by organizer/user early
- Eager load known relations with `with(...)`
- Avoid per-row relationship lookups

Implemented helper:
- `backend/app/GraphQL/Concerns/QueryOptimizer.php`

## Result caching

Hot GraphQL list queries can be cached with a short TTL to reduce repeated database load.

Config:
- `GRAPHQL_QUERY_CACHE_TTL` (default: `120` seconds)

Use cache for list endpoints with stable filters, and invalidate with mutations when data freshness is critical.
