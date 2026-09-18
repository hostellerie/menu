# Menu 1.4.0 Release Notes

Menu 1.4.0 is a modernization and stabilization release for the Geeklog Menu plugin. It keeps compatibility with older supported Geeklog installations while improving administration safety, theme integration, frontend asset loading, caching, localization and release engineering.

## Compatibility

Menu 1.4.0 targets:

- Geeklog **2.1.1 through 2.2.2**
- PHP **5.6 through 8.1**
- MySQL / MariaDB
- single-site and multisite installations

The plugin continues to use one shared source tree across the supported Geeklog and PHP range.

## Highlights

### Safer and more maintainable administration

The administration code has been split into smaller modules for views, mutations, validation, security and image handling. This reduces the amount of legacy logic concentrated in a few large files and makes future maintenance easier.

The current administration path includes:

- dedicated menu and element view modules;
- dedicated mutation handlers;
- dedicated element validation;
- centralized administration security helpers;
- stronger request normalization;
- CSRF protection on mutation paths;
- safer output escaping for stored labels and values;
- configuration validation helpers;
- progressive enhancement so administration content remains usable when optional JavaScript is unavailable.

### Theme integration and presentation ownership

Menu now has a clearer separation between structural responsibility and presentation responsibility.

Menu remains responsible for:

- hierarchy;
- destination resolution;
- permissions;
- ordering;
- resolved-tree data.

Modern themes may explicitly take ownership of presentation for selected Menu resources. When a theme does so, Menu avoids adding its legacy CSS/JavaScript for that resource.

When a theme merely embeds a Menu autotag such as `[menu:footer]`, presentation ownership remains with Menu. This is important for footer integrations such as Eclipse: the colors configured in Menu remain authoritative for the rendered menu.

### Versioned resolved-tree contract

Menu 1.4.0 now formalizes its presentation-neutral navigation representation as resolved-tree contract version 1.

Consumers can feature-detect:

```php
MENU_getResolvedTreeContractVersion();
MENU_getResolvedTree($name);
```

The resolved tree remains permission-filtered for the current Geeklog request context and exposes navigation structure without leaking raw group IDs, owner IDs or permission masks. Contract v1 is additive: consumers should ignore optional fields they do not understand.

The tree also exposes direct current-page selection through the presentation-neutral `selected` field. Themes remain free to derive presentation-only concepts such as active ancestor trails.

### Permission-aware menu discovery and Geeklog services

Menu can now expose which navigation structures are available to the current visitor without requiring consumers to inspect `$Menus` or query Menu tables directly.

```php
MENU_getAvailableMenus();
```

returns only active menus for which the current request context has access, with deliberately small metadata:

```text
id
name
type
```

Menu also exposes two read-only Geeklog Plugin Services:

```text
getMenuList
getMenuTree
```

through the existing `PLG_invokeService()` mechanism.

This gives themes and plugins a standard inter-plugin path:

```text
Menu runtime / permissions
        ↓
MENU_getAvailableMenus()
MENU_getResolvedTree()
        ↓
Geeklog Plugin Services
        ↓
Eclipse / Agent / other consumers
```

The services are thin facades: Menu remains authoritative for visibility, permissions and tree resolution. Consumers must not reconstruct hidden navigation from Menu tables.

Eclipse's current development line already knows how to consume these services, while retaining direct-function fallbacks for compatibility.

### Native configuration help

Menu now follows the same documentation-backed configuration-help path used by Geeklog Core plugins such as Static Pages. `plugin_getconfigtooltip_menu()` defers to Geeklog's standard help renderer, while `plugin_getdocumentationurl_menu()` points to Menu's own `config.html` documentation with `desc_*` anchors for current settings.

This avoids theme-specific tooltip markup and keeps configuration help consistent with Geeklog's native Configuration Manager.

### Incomplete legacy configuration hardening

Older, cloned or partially migrated menus may not contain every historical row in `menu_config`. The administration and runtime paths now tolerate missing values such as `menu_alignment` and `use_images` and restore safe historical defaults instead of emitting PHP warnings.

This also protects footer/vertical rendering paths that previously assumed `menu_alignment` was always present.

### Footer and embedded menu color fixes

Horizontal simple menus now preserve their configured link and hover colors even inside theme footers that use highly specific selectors such as `#footer a:link`.

Normal, visited and active states use the configured Menu text color. Hover and focus states use the configured Menu hover color.

This fixes cases where the same Menu rendered correctly in page content but inherited theme footer colors when rendered through `[menu:footer]`.

### Demand-loaded frontend assets

Menu 1.4.0 introduces a request-scoped asset-usage registry.

On Geeklog's modern document renderer, Menu considers legacy CSS and SlickNav only for menus that are actually used in the current request, while preserving a narrow compatibility fallback for canonical `navigation` and `footer` resources when header generation occurs before usage is known.

This means, for example:

- a simple footer menu does not load SlickNav;
- unused vertical or secondary menus do not contribute assets once request usage is known;
- theme-owned navigation does not receive Menu's legacy presentation assets;
- configuration switches such as `legacy_rendering`, `load_legacy_css` and `load_legacy_js` remain authoritative.

Geeklog 2.1.1 keeps a conservative compatibility fallback because its page lifecycle can finalize the head before arbitrary page-content autotags or direct `MENU_getMenu()` calls execute.

### Improved CSS cache invalidation

Generated Menu CSS cache keys now include a presentation-template fingerprint.

Conceptually, generated CSS is cached as:

`menu_css_<menu_id>__<theme>__<presentation-fingerprint>`

When Menu's presentation templates change, the fingerprint changes automatically and stale generated CSS is not reused.

This avoids a class of upgrade issues where updated template CSS could remain hidden behind an older generated cache entry even after plugin files were replaced.

### Legacy color configuration compatibility

A dedicated color helper restores and hardens support for historical Menu color values.

The configuration path safely handles:

- `#RRGGBB`;
- `RRGGBB`;
- `#RGB`;
- `RGB`;
- empty values;
- `none`;
- malformed historical values.

This prevents older stored configuration from breaking the administration interface on newer PHP versions.

### Localization improvements

English is now treated as the canonical language contract. The active translation overlays English so missing translated keys fall back safely instead of producing undefined-key warnings.

The branch also includes:

- expanded French administration strings;
- automated checks for referenced language keys;
- continued migration away from hardcoded interface text.

### Multisite and storage improvements

Plugin-owned private data uses site-specific Geeklog paths where appropriate.

Migration behavior remains conservative, repeatable and non-destructive so shared-code multisite installations can upgrade one Geeklog site without intentionally overwriting another site's plugin-owned data.

### Build and release workflow

Menu 1.4.0 adds a permanent CI and packaging workflow.

The development branch now:

- runs tests on PHP 5.6 and PHP 8.1;
- validates language contracts;
- builds an installable `menu-1.4.0.zip`;
- verifies the generated archive;
- publishes the ZIP as a GitHub Actions artifact;
- keeps a branch `dist/menu-1.4.0.zip` for installation testing;
- regenerates the development archive automatically after release-source changes.

## Metadata and installer

Plugin metadata now reports version **1.4.0**.

Historical migration functions and thresholds that belong to 1.3.0 remain named and versioned as 1.3.0 migrations. They are intentionally not renamed, because they describe the version boundary at which those migrations were introduced.

## Upgrade notes

Before upgrading a production installation:

1. Back up the Geeklog database and site files.
2. Install the generated Menu 1.4.0 archive through Geeklog's standard plugin upgrade path.
3. Open `Admin → Plugins → Menu` and verify the existing menus and configuration.
4. Clear Geeklog caches if required by the local deployment.
5. Check the Geeklog and PHP error logs for warnings or fatal errors.
6. Verify menus in both normal content and theme-specific areas such as headers and footers.

Existing menu data should be preserved. The 1.4.0 work is intended to remain non-destructive and backward compatible across the declared support range.

## Validation status before stable release

The codebase includes automated PHP 5.6 / PHP 8.1 tests and an installable archive build. Before publishing the final stable release, maintainers should still complete the final real-environment matrix documented in `ROADMAP.md`, including fresh install and upgrade tests using the generated ZIP on both Geeklog 2.1.1 and Geeklog 2.2.2.

## Known follow-up work

The following items remain roadmap work for later 1.4.x or future releases rather than blockers already implemented in 1.4.0:

- destination integrity diagnostics;
- optional standardized ancestor active-trail metadata beyond the implemented direct `selected` state;
- richer link metadata and icons;
- versioned JSON import/export;
- multisite menu transfer;
- explicit multilingual menu resolution;
- richer display conditions;
- broader contextual navigation capabilities beyond the implemented `getMenuList` / `getMenuTree` service facade;
- additional accessibility metadata;
- revisions, drafts and scheduled publication;
- breadcrumb/SEO-oriented structural helpers;
- public remote/headless APIs after authentication and request-context rules are defined.

See `ROADMAP.md` for the full development plan.
