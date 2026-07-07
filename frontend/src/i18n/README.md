# frontend/src/i18n

Global, lightweight i18n scaffolding.

This repo currently has no external i18n dependency (no `i18next` / `react-intl`).
We keep translations in a single language-first root and load them via a tiny helper.

## Layout

- `locales/{lang}/{namespace}.json`

## Namespaces

Namespaces should be feature-scoped (e.g. `admin`, `compliance`, `offline`).

## Helper

- `t(namespace, key, vars?)` in `src/i18n/t.ts`

