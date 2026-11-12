# Menu 1.5.0 — Planned Release Scope

> **Development planning document, not release notes for a shipped version.** This branch is `develop-1.5.0`. Do not announce Menu 1.5.0 as released or any planned capability as implemented.

## Baseline

Menu 1.5.0 builds upon the implemented Menu 1.4.0 hierarchy, administration improvements, theme integration and version 1 of the permission-filtered resolved-tree API. Review [1.4.0 release notes](RELEASE_NOTES_1.4.0.md) for existing behavior.

## Planned major capability: Dynamic Plugin Navigation Providers

- Provider plugins (first use case: Family Portfolio) define and own full hierarchical navigation, including stable IDs, URLs, user-specific visibility and translations.
- Menu reads these trees through a versioned Geeklog-native inter-plugin contract; no provider-specific hard dependency.
- Administrators can add their own entries at any level, including **inside provider-defined submenus**, or alongside them, without altering the provider's source hierarchy.
- An overlay model preserves manual entries across provider upgrades or temporary unavailability; invalid/missing anchors are flagged, not deleted.
- Final composed tree remains permission-aware, compatible with the Menu resolved-tree API and available to modern/legacy themes.
- Sites without dynamic provider support continue to use normal Menu navigation; provider plugins can function without Menu.

The detailed specification and testing criteria are in [docs/dynamic-plugin-navigation-providers.md](docs/dynamic-plugin-navigation-providers.md).

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
