# Menu Plugin Modernization Roadmap

Target version: **1.3.0**

Working branch: **modernize-1.3.0**

## Goals

Modernize the Geeklog Menu plugin while preserving existing installations and menu data.

Primary compatibility target:

- Geeklog 2.1.1 through 2.2.2
- PHP 5.6 through 8.1
- MySQL / MariaDB
- Single-site and multisite Geeklog installations
- Direct upgrade from Menu 1.2.6, 1.2.7, 1.2.8 and 1.2.8.1

The modernization must remain conservative: stabilize and secure the existing plugin first, then add new functionality.

---

## Current implementation status — 2026-08-25

The checklist below remains the release checklist. This section records the actual state of the `modernize-1.3.0` branch so completed work is not confused with remaining release validation.

### Completed or substantially implemented

- Phase 1: multisite-safe `{path_data}-menu/` storage, non-destructive legacy migration, isolated cache/CSS storage and site-specific image helpers.
- Phase 3: Geeklog 2.1.1 → 2.2.2 compatibility helpers and runtime fallbacks; real-world testing is ongoing on both Geeklog 2.1.1 and 2.2.2.
- Phase 4: PHP 5.6 / 8.1 syntax CI and compatibility-oriented shared code.
- Phase 7.5: global Geeklog configuration exists for fresh installs and 1.3.0 upgrades; all eight 1.3.0 settings are now connected to runtime behavior through centralized compatibility-safe helpers.
- Phase 8: administration preview now supports native and theme preview tabs; element create/edit type UX has been normalized.
- Phase 9: `MENU_getResolvedTree('navigation')` exists with hierarchy, ordering, permissions, resolved destinations and representative regression tests.
- Phase 10: theme-presentation hand-off exists; Eclipse can own `navigation` rendering while legacy Menu rendering remains available for other themes/menus.
- Missing plugin/static-page/topic destinations are retained in admin data and omitted from the resolved public tree when the destination no longer exists. Existing private resources are not hidden merely because the current visitor lacks permission; links to private destinations may intentionally lead to Geeklog authentication.

### Partially implemented / still blocking stabilization

- Phase 2 CSRF: mutation inventory, explicit tokens and server-side enforcement are implemented; all state-changing controller modes are POST-only and destructive move/delete actions now use native POST forms. The transitional JavaScript token/GET bridge has been removed.
- Phase 2 SQL safety: numeric casting is partial; create/edit SQL and `DB_save()` string escaping still need a complete audit. Legacy element ID generation also remains to be reviewed.
- Phase 2 output/upload safety: not yet fully audited.
- Phase 5 database modernization: not started beyond preserving the existing schema during current work.
- Phase 6 cache/performance: storage separation is implemented, but cache keys, invalidation and query behavior still need a full audit.
- Phase 7 maintainability: compatibility/path/security helpers have been split out, but `admin/index.php` and the duplicated menu element classes remain major cleanup targets.
- Phase 7.5 validation: runtime wiring is implemented; manual regression testing of each setting, configuration save/reload and upgrade preservation on Geeklog 2.1.1 and 2.2.2 is still required before 1.3.0 stable.
- Phase 11: fresh installs are being exercised on Geeklog 2.1.1 and 2.2.2, but the complete upgrade matrix and release validation are not complete.

### Immediate implementation order

1. Finish Phase 2 element create/edit validation and SQL escaping, including safe empty-URL handling and validation that menu/parent/order IDs belong to the expected menu.
2. Audit remaining menu/config mutation validation now that all state-changing controller modes are POST-only with explicit CSRF tokens.
3. Continue output/upload security hardening and remove remaining legacy mutation assumptions.
4. Continue Phase 4 warning cleanup and Phase 3 runtime regression testing on Geeklog 2.1.1 and 2.2.2.
5. Validate the Phase 7.5 global settings on real Geeklog 2.1.1 and 2.2.2 installations, including save/reload and upgrade preservation.
6. Then continue Phase 5/6 database and cache audits before adding further Phase 9 functionality.

---

## Phase 0 — Baseline and regression reference

- [ ] Preserve the current `master` behaviour as the 1.2.8.1 reference.
- [ ] Use Menu 1.2.6 as the Geeklog 2.1.1 compatibility reference.
- [ ] Inventory all files, database tables, generated files and persistent data.
- [ ] Document all Geeklog APIs used by the plugin.
- [ ] Identify changes introduced between 1.2.6 and 1.2.8.1 that raised the declared Geeklog requirement from 1.8.0 to 2.1.2.
- [ ] Establish a compatibility matrix for Geeklog 2.1.1 / 2.1.2 / 2.2.2 and PHP 5.6 / 7.x / 8.0 / 8.1.
- [ ] Add syntax/lint checks that remain compatible with PHP 5.6.

## Phase 1 — Multisite-safe persistent storage

### Private plugin data

Replace direct use of:

```text
{path_data}/menu_data/
```

with a site-specific directory derived from `$_CONF['path_data']`:

```text
{path_data}-menu/
```

Example:

```text
data/ecologie/      -> data/ecologie-menu/
data/site2/         -> data/site2-menu/
```

Expected structure:

```text
{site}-menu/
├── cache/
└── css/
```

Rules:

- [ ] Add a single helper such as `MENU_dataDir()`.
- [ ] All private plugin paths must use that helper.
- [ ] `cache/` is disposable.
- [ ] `css/` is persistent and must never be removed by normal cache cleanup.
- [ ] Cache cleanup must operate only on the plugin cache directory.
- [ ] The implementation must work identically in single-site and multisite installations.

### Migration

- [ ] Detect legacy `{path_data}/menu_data/` installations.
- [ ] Migrate recursively to `{path_data}-menu/`.
- [ ] Never overwrite an existing destination file.
- [ ] Never automatically delete the legacy directory.
- [ ] Make migration idempotent and safe to run more than once.
- [ ] Run the migration before recording the new plugin version.
- [ ] Add tests for two different multisite `path_data` values to ensure isolation.

### Public menu images

Continue to use the site-specific Geeklog image path:

```php
$_CONF['path_images'] . 'menu/'
```

- [ ] Remove hard-coded uses of `$_CONF['path_html'] . 'images/menu/'`.
- [ ] Remove hard-coded `/images/menu/` URLs where they bypass site-specific image configuration.
- [ ] Centralize filesystem and public URL generation for menu images.
- [ ] Preserve existing uploaded menu images during upgrades.

## Phase 2 — Security hardening

### CSRF protection

- [ ] Inventory every state-changing admin action.
- [ ] Require `menu.admin` permission for every mutation.
- [ ] Convert destructive GET operations to POST where appropriate.
- [ ] Add Geeklog security tokens to all state-changing forms/actions.
- [ ] Validate tokens server-side before mutations.
- [ ] Protect menu deletion, element deletion, movement/reordering, enable/disable, clone, create, edit and configuration save operations.
- [ ] Protect AJAX drag-and-drop ordering requests.

### SQL safety

- [ ] Audit every SQL query and `DB_save()` call.
- [ ] Cast numeric IDs and enum-like values explicitly.
- [ ] Escape all string values using Geeklog database escaping APIs.
- [ ] Ensure labels, URLs, targets, menu names and configuration values cannot break SQL queries.
- [ ] Remove `SELECT MAX(id) + 1` element-ID generation and rely on database auto-increment where possible.

### Output/XSS safety

- [ ] Audit HTML output contexts.
- [ ] Escape menu names and labels according to output context.
- [ ] Validate URLs before rendering them.
- [ ] Encode JavaScript values safely rather than concatenating raw configuration values.
- [ ] Validate CSS configuration values according to their expected type.
- [ ] Review autotag output and plugin-provided menu items.

### Upload security

- [ ] Validate uploads with `is_uploaded_file()`.
- [ ] Detect MIME type server-side.
- [ ] Keep a strict PNG/GIF/JPEG allow-list unless formats are intentionally expanded.
- [ ] Validate image contents and dimensions.
- [ ] Add file-size and dimension limits.
- [ ] Generate server-side filenames.
- [ ] Fix inconsistent old-image deletion paths.

## Phase 3 — Geeklog 2.1.1 → 2.2.2 compatibility

- [ ] Preserve the working behaviour of Menu 1.2.6 on Geeklog 2.1.1.
- [ ] Preserve the Geeklog 2.2.0/2.2.2 adaptations introduced in Menu 1.2.8/1.2.8.1.
- [ ] Identify `Geeklog\Input` calls unavailable or behaviourally different in Geeklog 2.1.1.
- [ ] Add small compatibility wrappers where needed instead of maintaining separate branches.
- [ ] Preserve template compatibility across Geeklog 2.1.1 through 2.2.2.
- [ ] Preserve the Geeklog 2.2.2 topics/userindex compatibility fix.
- [ ] Make `plugin_compatible_with_this_version_menu()` perform meaningful compatibility checks.
- [ ] Set the declared minimum Geeklog version only after the compatibility tests pass.

## Phase 4 — PHP 5.6 → 8.1 compatibility

- [ ] Avoid syntax requiring PHP 7+.
- [ ] Remove undefined-variable and undefined-index warnings.
- [ ] Remove invalid assumptions about null values and array offsets.
- [ ] Audit deprecated/changed PHP behaviour.
- [ ] Avoid PHP 8-only APIs in shared code.
- [ ] Test install, upgrade, administration and frontend rendering on supported PHP versions.

## Phase 5 — Database modernization

- [ ] Preserve existing tables and data during upgrade.
- [ ] Review schema indexes.
- [ ] Evaluate safe migration from MyISAM to InnoDB.
- [ ] Ensure character set/collation follows Geeklog database conventions.
- [ ] Review orphan handling for `menu_config` and `menu_elements`.
- [ ] Ensure menu deletion reliably removes associated rows.
- [ ] Do not introduce foreign-key constraints unless they are proven compatible with all supported Geeklog/MySQL environments.

## Phase 6 — Cache and performance

- [ ] Separate disposable cache from persistent CSS.
- [ ] Ensure cache keys remain isolated by language, theme and permissions as required.
- [ ] Review cache invalidation after every menu mutation.
- [ ] Avoid rewriting cache files unnecessarily.
- [ ] Review repeated database queries during `MENU_initMenu()`.
- [ ] Avoid loading or generating CSS/JavaScript for inactive or unused menus where possible.
- [ ] Ensure cache cleanup cannot traverse outside the plugin cache directory.

## Phase 7 — Code cleanup and maintainability

- [ ] Remove obsolete IE6 support and unused assets.
- [ ] Review the bundled SlickNav version and dependency strategy.
- [ ] Prefer dependency-free/vanilla JavaScript for new code where practical.
- [x] Consolidate the duplicated Menu element class implementation into `classMenuElement.php`.
- [x] Remove the legacy pre-Geeklog-2 class split now that 1.3.0 supports Geeklog 2.1.1 through 2.2.2.
- [ ] Gradually split the ~95 KB `admin/index.php` into focused components without breaking old Geeklog versions.
- [ ] Centralize path handling, input handling and security checks.
- [ ] Keep the plugin installable using standard Geeklog plugin installation mechanisms.

## Phase 7.5 — Global plugin configuration

Use Geeklog's native `/admin/configuration.php` for settings that apply to the Menu plugin as a whole. Per-menu structure, visual options, permissions and menu-specific behavior remain in the Menu administration UI and plugin tables.

Principles:

- [x] Remove the obsolete Plugin Toolkit `samplesetting1` / `samplesetting2` defaults from fresh installations.
- [x] Add an idempotent 1.3.0 upgrade path that removes those legacy rows and preserves existing valid configuration values.
- [x] Keep one configuration definition compatible with Geeklog 2.1.1 through 2.2.2.
- [x] Provide shared configuration labels independently of the selected Menu language file so the configuration manager always receives valid arrays.
- [x] Every exposed option controls actual runtime behavior; no decorative or dead settings.
- [ ] Keep defaults conservative so upgrading from 1.2.x preserves legacy rendering unless the administrator explicitly changes it.
- [ ] Add regression tests for fresh install and upgrade from 1.2.6, 1.2.7, 1.2.8 and 1.2.8.1.
- [ ] Verify configuration rendering and saving on Geeklog 2.1.1 and 2.2.2.

Initial 1.3.0 global settings:

- [x] `enable_cache` — global Menu cache switch, default enabled.
- [x] `accessibility_markup` — accessibility / ARIA output switch, default enabled.
- [x] `external_link_protection` — secure external links opened in a new window, default enabled.
- [x] `allow_php_elements` — allow PHP-function menu elements, default disabled.
- [x] `legacy_rendering` — preserve the classic Menu rendering path, default enabled.
- [x] `load_legacy_css` — load legacy Menu CSS where legacy rendering is used, default enabled.
- [x] `load_legacy_js` — load legacy Menu JavaScript where legacy rendering is used, default enabled.
- [x] `debug` — plugin diagnostic logging, default disabled.

The eight settings are wired into runtime paths. Automated helper/syntax tests cover the configuration plumbing; real Geeklog 2.1.1/2.2.2 behavior and upgrade regression tests remain required before this phase is release-complete.

## Phase 8 — Administration UX

After stability and security are validated:

- [ ] Modernize drag-and-drop tree ordering.
- [ ] Improve menu hierarchy visualization.
- [ ] Add clear save/error/success feedback.
- [ ] Improve responsive administration layout.
- [ ] Add desktop/tablet/mobile preview where practical.
- [ ] Preserve accessibility for keyboard-only administration.

## Phase 9 — New functionality and theme-facing API

Potential additions after the compatibility baseline is stable:

- [ ] JSON import/export for menus.
- [ ] Clone/export/import workflows suitable for multisite installations.
- [ ] Optional SVG/emoji/CSS-class icons.
- [ ] Additional link attributes: `rel`, `aria-label`, CSS class and safe target handling.
- [ ] Richer display conditions by authentication state, group, language, page or plugin context.
- [ ] Reusable/inherited menu structures where useful.
- [ ] Add a stable resolved-tree API such as `MENU_getResolvedTree('navigation')` for themes and integrations.
- [ ] The resolved-tree API must preserve hierarchy and ordering and apply Menu permissions, activation and display-condition filtering before returning nodes.
- [ ] Resolve each supported element type through Menu before exposing it to themes: Geeklog Action, Geeklog Core, plugin, static page, URL, PHP function where applicable, topic and submenu/container elements.
- [ ] Return presentation-neutral node data including at least stable element ID, label, type, subtype where useful, resolved URL, target, selected/current state and recursively nested `children`.
- [ ] Do not expose raw database rows as the theme API contract.
- [ ] Keep `MENU_getMenu()` and existing template variables for backward compatibility with existing themes.
- [ ] Add regression tests for a representative `navigation` tree containing Home as type 2 — Geeklog Action, core/plugin/static-page/topic/URL items and nested submenu elements.

## Phase 10 — Theme integration

Separate menu content from visual presentation as much as possible.

Plugin responsibilities:

- menu structure
- hierarchy
- order
- permissions
- links and destinations
- element-type resolution
- active/current state resolution where possible
- display conditions

Theme responsibilities where possible:

- semantic HTML rendering
- colors
- spacing
- typography
- responsive behaviour
- dropdown interactions
- mobile navigation
- animations
- palette integration

- [ ] Preserve legacy visual configuration and `MENU_getMenu()` output for existing installations and themes.
- [ ] Make the Menu plugin `navigation` menu the authoritative source for primary navigation integrations.
- [ ] Allow modern themes such as Eclipse to consume the resolved-tree API and render their own HTML/CSS/JavaScript without duplicating menu data or URL-resolution logic.
- [ ] Eclipse must preserve Menu hierarchy, permissions, element types, ordering and resolved destinations while controlling only presentation and interaction.
- [ ] Keep the administration preview presentation-neutral so it validates the same underlying structure consumed by themes.
- [ ] Do not require physical modification of a theme template merely to make the resolved-tree API available.

## Phase 11 — Upgrade and release validation

Before declaring 1.3.0 stable:

- [ ] Fresh install tests.
- [ ] Upgrade test from 1.2.6.
- [ ] Upgrade test from 1.2.7.
- [ ] Upgrade test from 1.2.8.
- [ ] Upgrade test from 1.2.8.1.
- [ ] Verify existing menu structures and permissions remain unchanged.
- [ ] Verify existing custom CSS remains available after migration.
- [ ] Verify existing uploaded images remain available.
- [ ] Verify migration is idempotent.
- [ ] Verify two-site multisite isolation.
- [ ] Verify cache cleanup does not remove persistent plugin data.
- [ ] Verify uninstall behaviour separately from cache cleanup.
- [ ] Run compatibility tests across the supported Geeklog/PHP matrix.
- [ ] Build and validate an installable ZIP before release.

## Compatibility principle

The plugin must not solve compatibility by maintaining separate source trees for old and new Geeklog releases. Prefer small compatibility helpers and runtime capability/version checks so one release can support the full declared range.

## Data-safety principle

Existing user data must take precedence over cleanup or migration convenience. Upgrade routines must be non-destructive, repeatable and conservative. Legacy files/directories may be left in place after successful migration and documented for optional manual cleanup later.
