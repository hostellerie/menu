# Menu 1.5.0 — Planned Release Scope

> **Development planning document, not release notes for a shipped version.** This branch is `develop-1.5.0`. Do not announce Menu 1.5.0 as released or any planned capability as implemented.

## Baseline

Menu 1.5.0 builds upon the implemented Menu 1.4.0 hierarchy, administration improvements, theme integration and version 1 of the permission-filtered resolved-tree API. Review [1.4.0 release notes](RELEASE_NOTES_1.4.0.md) for existing behavior.

## Dynamic Plugin Navigation Providers — development implementation

- **One-click menu installation:** Menu discovers compatible active plugins with a `main` tree and lets administrators add each complete navigation hierarchy through a single protected action. Repeating the action does not duplicate a provider entry.

- **Implemented in development:** provider plugins may define and own full hierarchical navigation, including stable IDs, URLs, user-filtered visibility and translations.
- **Implemented:** Menu reads provider trees through versioned Geeklog Plugin Services with validation; no provider-specific hard dependency.
- **Implemented (initial scope):** administrators add subtrees beneath provider nodes through `menu-anchor:` External URL placeholders, or alongside them as ordinary Menu entries. Before/after placement and a graphical provider editor are not implemented.
- **Implemented:** Menu-owned entries remain persisted if the provider or its anchor disappears; unresolved anchors are hidden, **not yet displayed in a dedicated diagnostic editor**.
- **Implemented:** composed tree integrates with the existing resolved-tree API. A basic escaped HTML fallback supports legacy rendering; full theme styling parity requires testing.
- Sites without dynamic provider support continue to use normal Menu navigation; provider plugins can function without Menu.

The [plugin author guide](docs/plugin-navigation-provider-guide.md) documents the required Geeklog callbacks and administrator setup. The [design](docs/dynamic-plugin-navigation-providers.md) records the full roadmap, remaining work and security constraints.

## Other planned 1.5.0 items

- Link metadata improvements (`target`, `rel`, ARIA and limited presentation-neutral metadata).
- Versioned JSON import/export.
- Multisite portability safeguards.
- Language-aware menu resolution.
- Rich, centrally validated display conditions.

All remain **unchecked roadmap items** pending implementation.

## Compatibility and release gates

- Preserve existing 1.4.x persisted menus, public rendering, native Geeklog hooks, themes and resolved-tree v1 consumers.
- Test declared Geeklog 2.1.1–2.2.2 and PHP 5.6–8.1 compatibility; do not revise these ranges without an explicit policy decision and updated metadata.
- Ensure migration paths are reversible/recoverable, idempotent and safe on shared-files multisite installations.
- Verify permissions for authenticated/anonymous users and private tenant/family navigation.
- Test installation/upgrade/disable on the final generated archive, not just a development checkout.
- Run regression tests for legacy menus, provider failure, anchor preservation, translations and cache isolation.

**No release date or release claim is made.**
