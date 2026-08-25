# Menu Plugin Modernization Roadmap

Target version: **1.3.0**  
Working branch: **modernize-1.3.0**

## Compatibility target

Menu 1.3.0 must keep one shared codebase for:

- Geeklog 2.1.1 through 2.2.2
- PHP 5.6 through 8.1
- MySQL / MariaDB
- single-site and multisite installations
- upgrades from Menu 1.2.6, 1.2.7, 1.2.8 and 1.2.8.1

The modernization remains conservative: preserve existing menu data and legacy rendering while making the plugin safer, easier to maintain and usable by modern themes such as Eclipse.

---

## Current status — 2026-08-25

### Implemented and covered by automated tests

- multisite-safe private storage in `{path_data}-menu/`;
- non-destructive migration from legacy `menu_data/`;
- site-specific public image paths;
- centralized upload validation and generated image filenames;
- POST-only state-changing administration operations with CSRF validation;
- centralized `menu.admin` mutation authorization;
- strict validation of menu, parent, order and element IDs;
- SQL string escaping and numeric casting on modernized persistence paths;
- database `AUTO_INCREMENT` for new elements instead of `MAX(id)+1`;
- hierarchy-safe menu cloning with old-ID → new-ID parent remapping;
- reliable menu deletion without orphaned `menu_config` or `menu_elements` rows;
- safe CSS color/image validation;
- safe URL rendering and dangerous-scheme rejection;
- consolidated `classMenuElement.php` implementation;
- compatibility helpers for Geeklog 2.1.1 through 2.2.2 and PHP 5.6 through 8.1;
- idempotent database index upgrade for `(menu_id, pid, element_order)`;
- three-query runtime menu loading instead of the previous `1 + 2 × menus` pattern;
- centralized runtime cache invalidation;
- cache traversal/symlink protection;
- removal of the unused/unsafe HTML menu cache path;
- SlickNav loaded only for active legacy horizontal cascading menus that need it;
- native drag ordering with keyboard move controls;
- restored submenu creation/editing for all presentation types;
- default `Display After` selection at the end of the current sibling list;
- theme-facing resolved-tree API;
- theme presentation hand-off for modern themes;
- native/theme administration preview;
- global Geeklog configuration for all 1.3.0 runtime switches;
- minimal GitHub CI: one PHP 5.6 / PHP 8.1 lint-and-test workflow.

### Validated locally on Geeklog 2.1.1 and 2.2.2 during modernization

The following paths have been exercised successfully during development:

- menu administration loading;
- element creation/editing;
- submenu creation and parent selection;
- `Display After` ordering;
- drag-and-drop ordering;
- menu activation/deactivation;
- configuration/color save;
- image preview;
- menu cloning;
- menu/element deletion;
- public navigation rendering;
- footer rendering;
- authenticated/anonymous permission changes.

### Remaining blockers before 1.3.0 stable

1. Continue Phase 7 code decomposition, especially `admin/index.php` and legacy rendering responsibilities.
2. Audit and remove demonstrably unused legacy JavaScript/CSS/images.
3. Review the bundled SlickNav dependency and decide whether it remains the 1.3.0 compatibility implementation.
4. Complete manual validation of all eight global configuration switches on both Geeklog 2.1.1 and 2.2.2.
5. Run the complete upgrade matrix from 1.2.6, 1.2.7, 1.2.8 and 1.2.8.1.
6. Confirm fresh installation and uninstall behavior on both supported Geeklog generations.
7. Perform a final warning/error-log audit under PHP 5.6 and PHP 8.1.

There is **no GitHub Actions ZIP packaging requirement**. Release validation concerns the plugin source/package produced for an actual release, not continuous ZIP generation in CI.

---

## Phase 0 — Baseline and regression reference

- [ ] Preserve master/1.2.8.1 as the legacy behavior reference.
- [ ] Preserve 1.2.6 as the Geeklog 2.1.1 compatibility reference.
- [x] Maintain PHP 5.6-compatible lint and regression tests.
- [ ] Finish the documented compatibility/release matrix.

## Phase 1 — Multisite-safe persistent storage

- [x] Centralize private storage through `MENU_dataDir()`.
- [x] Store plugin data in site-specific `{path_data}-menu/`.
- [x] Keep disposable cache separate from persistent custom CSS.
- [x] Migrate legacy `menu_data/` non-destructively and idempotently.
- [x] Never overwrite migrated destination files.
- [x] Keep public menu images site-specific.
- [x] Test multisite path isolation.

## Phase 2 — Security hardening

### Administration / CSRF

- [x] Inventory and protect state-changing operations.
- [x] Require `menu.admin` before mutation processing.
- [x] Make destructive/mutating operations POST-only.
- [x] Validate Geeklog security tokens server-side.
- [x] Protect AJAX/native drag ordering.

### SQL and mutation validation

- [x] Cast numeric IDs and enum-like values on modernized mutation paths.
- [x] Escape stored string values before SQL persistence.
- [x] Validate parent/order ownership against the selected menu.
- [x] Replace active element `MAX(id)+1` creation with `AUTO_INCREMENT`.
- [x] Preserve hierarchy when cloning menus.

### Output / CSS / URLs

- [x] Escape stored labels in frontend rendering.
- [x] Reject unsafe URL schemes.
- [x] Encode JavaScript values safely where modernized.
- [x] Strictly validate legacy CSS colors and image filenames.
- [x] Prevent broken/missing legacy image previews.
- [ ] Continue contextual escaping cleanup as legacy view code is extracted.

### Uploads

- [x] Validate with `is_uploaded_file()`.
- [x] Detect image type from file contents.
- [x] Allow only PNG/GIF/JPEG.
- [x] Apply file-size and dimension limits.
- [x] Generate server-side filenames.
- [x] Prevent unsafe deletion/path traversal.

## Phase 3 — Geeklog 2.1.1 → 2.2.2 compatibility

- [x] Use a single shared source tree.
- [x] Provide compatibility helpers for changed Geeklog APIs.
- [x] Preserve templates across the supported range.
- [x] Preserve Geeklog 2.2.x topic/userindex adaptations.
- [ ] Complete the final install/upgrade regression matrix.

## Phase 4 — PHP 5.6 → 8.1 compatibility

- [x] Keep shared source syntax PHP 5.6 compatible.
- [x] Run permanent lint/tests under PHP 5.6 and PHP 8.1.
- [x] Avoid PHP 8-only APIs in shared code.
- [ ] Complete final runtime warning/error-log audit on both ends of the range.

## Phase 5 — Database modernization

- [x] Preserve existing tables/data during upgrade.
- [x] Review schema indexes.
- [x] Add idempotent `(menu_id, pid, element_order)` index support.
- [x] Evaluate MyISAM/InnoDB and retain Geeklog's current MySQL convention for 1.3.0.
- [x] Follow Geeklog charset/collation conventions rather than forcing a plugin-specific conversion.
- [x] Remove orphan-prone full-menu deletion logic.
- [x] Reliably delete associated config/elements.
- [x] Avoid foreign-key constraints for compatibility.

## Phase 6 — Cache and performance

- [x] Separate disposable cache from persistent CSS.
- [x] Centralize cache invalidation after mutations.
- [x] Reduce `MENU_initMenu()` to three database queries independent of menu count.
- [x] Remove the unused HTML rendering cache path whose context key was incomplete.
- [x] Retain safe CSS cache behavior.
- [x] Prevent cache cleanup traversal and symlink following.
- [x] Load SlickNav assets only when an appropriate menu requires them.
- [x] Extract generic cache infrastructure to `cache.php`.

## Phase 7 — Code cleanup and maintainability

- [x] Consolidate duplicated Menu element classes.
- [x] Remove obsolete legacy persistence paths superseded by dedicated endpoints.
- [x] Extract runtime DB loading to `runtime_loader.php`.
- [x] Extract cache filesystem/runtime/cache API responsibilities.
- [x] Extract menu list/clone/create administration view builders to `admin_menu_views.php`.
- [x] Centralize path, compatibility and security helpers.
- [ ] Continue reducing `admin/index.php` into focused components.
- [ ] Audit/remove unused `tableDnD` generations and other dead administration assets.
- [ ] Audit obsolete browser-specific code/assets.
- [ ] Review SlickNav version/dependency strategy.
- [ ] Prefer vanilla/dependency-free JavaScript for new administration code.

## Phase 7.5 — Global plugin configuration

Implemented settings:

- [x] `enable_cache`
- [x] `accessibility_markup`
- [x] `external_link_protection`
- [x] `allow_php_elements`
- [x] `legacy_rendering`
- [x] `load_legacy_css`
- [x] `load_legacy_js`
- [x] `debug`

Also completed:

- [x] Remove obsolete Plugin Toolkit sample settings.
- [x] Preserve conservative defaults.
- [x] Provide idempotent 1.3.0 configuration upgrade plumbing.
- [x] Keep labels/config definitions compatible with Geeklog 2.1.1 through 2.2.2.
- [ ] Manually validate every switch and save/reload behavior on both Geeklog generations.
- [ ] Validate preservation during each supported 1.2.x upgrade path.

## Phase 8 — Administration UX

- [x] Modernize drag ordering without relying on legacy `tableDnD` behavior.
- [x] Provide keyboard up/down ordering controls.
- [x] Normalize create/edit element type handling.
- [x] Restore submenu hierarchy editing across presentation types.
- [x] Default new element ordering to the end of its sibling list.
- [x] Provide native/theme preview tabs.
- [ ] Continue responsive/accessibility polish after structural cleanup.

## Phase 9 — Theme-facing API and future features

Implemented foundation:

- [x] Stable resolved-tree API.
- [x] Preserve hierarchy/order/permissions.
- [x] Resolve supported element destinations before exposing them to themes.
- [x] Return presentation-neutral node data.
- [x] Keep legacy `MENU_getMenu()` behavior for existing themes.

Possible post-stabilization additions:

- [ ] JSON import/export.
- [ ] Multisite clone/export/import workflows.
- [ ] Optional SVG/emoji/CSS-class icons.
- [ ] Additional safe link attributes.
- [ ] Richer display conditions.
- [ ] Reusable/inherited menu structures.

## Phase 10 — Theme integration

- [x] Keep menu structure/content responsibility in the plugin.
- [x] Allow modern themes to own HTML/CSS/JS presentation.
- [x] Expose `navigation` through the resolved-tree hand-off.
- [x] Preserve legacy rendering for themes that still use it.
- [x] Support Eclipse consuming Menu navigation without duplicating destination-resolution logic.
- [ ] Continue real-theme regression testing as Eclipse evolves.

## Phase 11 — Upgrade and release validation

Before 1.3.0 stable:

- [ ] Fresh install on Geeklog 2.1.1.
- [ ] Fresh install on Geeklog 2.2.2.
- [ ] Upgrade from 1.2.6.
- [ ] Upgrade from 1.2.7.
- [ ] Upgrade from 1.2.8.
- [ ] Upgrade from 1.2.8.1.
- [ ] Confirm existing menu structure and permissions remain unchanged.
- [ ] Confirm custom CSS survives migration.
- [ ] Confirm uploaded images remain available.
- [x] Automated migration idempotence coverage.
- [x] Automated multisite isolation coverage.
- [x] Automated cache/data separation coverage.
- [ ] Verify uninstall behavior separately from normal cache cleanup.
- [ ] Run final supported Geeklog/PHP regression matrix.
- [ ] Validate the actual release package/install procedure when preparing the release.

## Compatibility principle

Do not maintain separate source trees for old and new Geeklog releases. Prefer small compatibility helpers and runtime capability checks so one Menu release supports the full declared range.

## Data-safety principle

Existing user data takes precedence over cleanup convenience. Upgrade routines must remain non-destructive, repeatable and conservative. Legacy files/directories may remain after successful migration and can be documented for optional manual cleanup.
