# Menu Plugin Modernization Roadmap

Target version: **1.4.0**  
Working branch: **modernize-1.4.0**

## Baseline and compatibility policy

Menu 1.4.0 continues the modernization started in 1.3.0 while preserving the established compatibility target:

- Geeklog **2.1.1 through 2.2.2**;
- PHP **5.6 through 8.1**;
- MySQL / MariaDB;
- single-site and multisite installations;
- conservative upgrades from supported legacy Menu installations.

One shared source tree should continue to support the declared range. Prefer capability checks and small compatibility helpers over duplicated old/new implementations.

---

## Completed or substantially implemented foundations

The following work is already present on `modernize-1.4.0` and is now part of the baseline rather than future roadmap work.

### Administration architecture

- [x] Split major administration responsibilities into dedicated modules.
- [x] Separate menu views from menu mutations.
- [x] Add dedicated element validation and element view modules.
- [x] Add dedicated administration security helpers.
- [x] Isolate image-upload handling.
- [x] Add configuration-validation helpers.
- [x] Keep administration content visible through progressive enhancement instead of depending on a JavaScript reveal shim.

### Security and input handling

- [x] Use Geeklog security/permission checks for administration.
- [x] Add CSRF protection helpers to mutation paths.
- [x] Normalize request handling with Geeklog input helpers where modernized.
- [x] Escape stored and user-controlled labels/values before administration rendering.
- [x] Harden CSS/configuration handling through dedicated validation helpers.
- [x] Keep PHP-function menu elements behind explicit configuration/security controls.

### Element architecture

- [x] Introduce explicit element-type helpers.
- [x] Separate editor/runtime behavior from large legacy administration functions.
- [x] Preserve legacy destination families while moving validation toward type-specific handling.
- [x] Preserve hierarchical ordering behavior and keyboard/non-drag alternatives where available.

### Runtime and theme integration

- [x] Maintain the legacy renderer for existing themes.
- [x] Provide a presentation-neutral resolved-tree path for modern themes.
- [x] Keep hierarchy, destination resolution, permissions and ordering in Menu rather than duplicating them in themes.
- [x] Support native/theme preview integration from administration.
- [x] Maintain the architectural hand-off required by modern themes such as Eclipse.

### Cache and multisite foundations

- [x] Separate runtime configuration helpers.
- [x] Provide runtime and filesystem cache helpers.
- [x] Keep cache disposable and separate from persistent menu data.
- [x] Use multisite-safe/site-specific plugin storage where appropriate.
- [x] Keep migration behavior conservative and non-destructive.

### Localization

- [x] Make English the canonical language contract.
- [x] Load English first and overlay the selected translation for safe fallback.
- [x] Extend French localization for modernized administration strings.
- [x] Add an automated language-contract test for PHP/INC language-key references.
- [x] Remove a set of hardcoded administration labels in favor of language keys.
- [ ] Extend the language-contract audit to templates so hardcoded or missing template strings are caught automatically.
- [ ] Finish localization of remaining hardcoded administration/security messages.

### Legacy menu configuration compatibility

- [x] Restore a dedicated RGB conversion helper used by menu configuration.
- [x] Accept historical `#RRGGBB`, `RRGGBB`, `#RGB` and `RGB` values.
- [x] Handle empty, `none` and malformed historical color values safely.
- [x] Keep the menu configuration page renderable on PHP 8.1 with historical stored configuration.
- [x] Remove temporary trace logging and diagnostic workflows after validation.

### Build and release workflow

- [x] Keep a permanent CI workflow for compatibility/security/tests.
- [x] Build an installable `menu-1.4.0.zip` through GitHub Actions.
- [x] Validate language files before packaging.
- [x] Validate the generated ZIP.
- [x] Publish the installable ZIP as a GitHub Actions artifact.
- [x] Keep a branch `dist/menu-1.4.0.zip` for installation testing.
- [x] Validate changes through real Geeklog plugin upload/install/upgrade testing.
- [ ] Add build metadata (commit SHA/date) to the development archive so successive `menu-1.4.0.zip` builds are easier to identify.

---

## Immediate 1.4.0 stabilization priorities

These items should take precedence over large new features before a stable 1.4.0 release.

### 1. Complete warning/error cleanup

- [ ] Run a final warning/error-log audit on Geeklog 2.1.1.
- [ ] Run a final warning/error-log audit on Geeklog 2.2.2.
- [ ] Test PHP 5.6 and PHP 8.1 paths for all administration screens.
- [ ] Remove remaining obsolete legacy loops or assumptions that can produce PHP 8 warnings.
- [ ] Confirm no temporary debug traces or one-off patch workflows remain.

### 2. Finish localization contract

- [ ] Audit all PHP, INC and template-visible interface text.
- [ ] Ensure every referenced key exists in `language/english.php`.
- [ ] Extend automated checks to `.thtml` templates where practical.
- [ ] Localize remaining hardcoded security/error messages.
- [ ] Keep other language files as optional overlays with English fallback.

### 3. Administration consistency

- [ ] Continue replacing legacy `current()/next()` iteration patterns with clearer `foreach` loops where behavior is equivalent and PHP 5.6 compatible.
- [ ] Verify every mutation path uses CSRF protection and validated input.
- [ ] Verify every administration output path escapes stored labels and URLs correctly.
- [ ] Remove obsolete duplicate reveal/presentation code left from historical templates.

### 4. Archive/install validation

- [ ] Test fresh installation from the generated ZIP on Geeklog 2.1.1.
- [ ] Test fresh installation from the generated ZIP on Geeklog 2.2.2.
- [ ] Test upgrade of an existing Menu installation on both Geeklog generations.
- [ ] Verify all newly added source files are included and installed in the expected private/public locations.
- [ ] Add build identification metadata to prevent confusion between same-named development ZIPs.

### 5. CI cleanup

- [ ] Resolve the remaining failing CI contract test(s), including the stored-label normalization regression currently reported by the test suite.
- [ ] Keep CI failures meaningful: temporary debugging workflows must not become permanent release infrastructure.

---

## Functional roadmap after stabilization

The following capabilities remain strategic work for 1.4.x or later. They should build on the resolved-tree, validation and multisite foundations above.

## Phase A — Destination integrity and diagnostics

### Goal

Preserve menu definitions when a destination disappears while preventing broken public navigation.

- [ ] Detect missing plugins, static pages, topics and other resolvable Geeklog destinations.
- [ ] Preserve stored destination references rather than deleting them automatically.
- [ ] Show unavailable destinations clearly in administration.
- [ ] Prevent unavailable destinations from producing broken public links by default.
- [ ] Restore normal behavior automatically if the destination returns.
- [ ] Detect orphaned elements, invalid parents and hierarchy cycles.
- [ ] Provide a non-destructive administrator diagnostic summary.

---

## Phase B — Active/current navigation state

### Goal

Expose which node corresponds to the current request without embedding theme-specific CSS logic in Menu.

- [ ] Extend the resolved-tree contract with presentation-neutral current-state metadata.
- [ ] Detect direct destination matches.
- [ ] Mark ancestors of the active node.
- [ ] Map cleanly to theme behavior such as `aria-current` and current-navigation highlighting.
- [ ] Validate topic, static-page, plugin and URL matching.

---

## Phase C — Modern link metadata and icons

- [ ] Support validated `target` values.
- [ ] Support validated `rel` values.
- [ ] Support optional `aria-label`.
- [ ] Support optional CSS-class metadata.
- [ ] Evaluate a restricted safe `data-*` model.
- [ ] Add presentation-neutral icon metadata without requiring a specific icon library.
- [ ] Expose the metadata through the resolved tree while retaining legacy compatibility.

---

## Phase D — Versioned JSON import/export

### Goal

Provide a portable and validated menu representation for backup, duplication and transfer.

- [ ] Define a versioned Menu JSON schema.
- [ ] Export hierarchy, labels, destinations, permissions where portable, ordering and metadata.
- [ ] Validate imports before any mutation.
- [ ] Provide dry-run feedback.
- [ ] Prevent invalid parents and hierarchy cycles.
- [ ] Define collision behavior explicitly.
- [ ] Never overwrite an existing menu silently.
- [ ] Add export → import → equivalent-tree tests.

---

## Phase E — Multisite clone and transfer

- [ ] Build cross-site transfer on the JSON format rather than direct table copying.
- [ ] Re-map element/parent identifiers safely.
- [ ] Detect target-site destinations or groups that do not exist.
- [ ] Preserve unavailable references instead of silently dropping them.
- [ ] Define handling for images and custom CSS.
- [ ] Keep site isolation authoritative throughout the operation.

---

## Phase F — Multilingual menu resolution

- [ ] Define explicit language association for menus.
- [ ] Resolve the preferred menu from the active Geeklog language.
- [ ] Add deterministic fallback to a generic/default menu.
- [ ] Prevent recursive fallback loops.
- [ ] Vary cache by language only when required.
- [ ] Expose useful language/fallback metadata to consumers.

This is separate from the plugin interface-language fallback already implemented for administration strings.

---

## Phase G — Rich display conditions

Candidate conditions:

- [ ] authenticated / anonymous;
- [ ] Geeklog group membership;
- [ ] active language;
- [ ] plugin active/inactive;
- [ ] current destination/content type;
- [ ] topic/section context;
- [ ] optional date/time windows.

Conditions must remain serializable, centrally validated and subordinate to Geeklog permissions. Arbitrary PHP expressions should not be introduced.

---

## Phase H — Reusable and contextual navigation

- [ ] Allow reusable menu/submenu structures without recursive inclusion loops.
- [ ] Define explicit live-reference versus snapshot semantics.
- [ ] Allow plugins such as Store, Documents or Videos to request contextual navigation through a generic contract.
- [ ] Keep plugin-specific HTML outside Menu.
- [ ] Reuse the same resolved-tree contract rather than creating parallel renderers.

---

## Phase I — Accessibility and smarter caching

- [ ] Review semantic navigation/list structure in retained native rendering.
- [ ] Improve keyboard submenu behavior where feasible.
- [ ] Expose metadata suitable for `aria-expanded` and `aria-current`.
- [ ] Verify icon-only labels remain accessible.
- [ ] Define cache variation only from dimensions that actually affect resolved output: site, language, permission context and future conditions.
- [ ] Prevent cross-site, cross-language or cross-user cache leakage.

---

## Phase J — Versioning, drafts and scheduled publication

- [ ] Define menu revisions and efficient history storage.
- [ ] Restore prior revisions without destroying history.
- [ ] Allow draft menu editing/preview.
- [ ] Add controlled scheduled activation if a concrete editorial need justifies it.

These features remain lower priority than destination integrity, portability and contextual APIs.

---

## Phase K — SEO/navigation semantics

- [ ] Provide breadcrumb-ready ancestry data from the resolved tree.
- [ ] Evaluate optional breadcrumb helpers.
- [ ] Evaluate presentation-neutral data for schema.org `BreadcrumbList`.
- [ ] Avoid duplicate structured data when themes or SEO plugins already provide it.

---

## Phase L — Inter-plugin and external APIs

### Inter-plugin services

- [ ] Define a narrow contract for plugins to advertise safe navigation destinations or contextual navigation blocks.
- [ ] Prefer existing Geeklog plugin/service APIs where they fit.
- [ ] Keep Menu responsible for validation, permissions and final tree composition.
- [ ] Avoid hard dependencies on individual plugins.

### Dynamic hubs

- [ ] Allow future Hub/page-pillar integrations to consume structured relationships rather than making Menu a content indexer.
- [ ] Keep automatically generated relationships distinguishable from manually maintained nodes.
- [ ] Define invalidation when related content changes.

### External/headless API

- [ ] Expose a JSON representation only after the internal resolved-tree contract is stable.
- [ ] Reuse the same permission-aware resolver used by themes.
- [ ] Define authentication/context behavior explicitly.
- [ ] Document use by decoupled frontends, applications and agent tooling.

---

## Release validation requirements

A stable 1.4.x release should not be published until the relevant items below are green:

- [ ] Geeklog 2.1.1 compatibility verified.
- [ ] Geeklog 2.2.2 compatibility verified.
- [ ] PHP 5.6 lint/tests verified for the declared support surface.
- [ ] PHP 8.1 lint/tests verified.
- [ ] Existing menus upgrade without destructive changes.
- [ ] Legacy rendering remains functional.
- [ ] Resolved-tree consumers remain backward compatible or receive an explicitly versioned contract.
- [ ] Multisite isolation remains intact.
- [ ] English language contract passes.
- [ ] No unexplained warnings/fatal errors in supported test environments.
- [ ] Fresh install and upgrade are both tested using the actual generated ZIP.
- [ ] Generated archive content is verified before publication.

## Guiding principles

**Compatibility:** avoid separate source trees for old and new Geeklog releases while the shared compatibility policy remains practical.

**Data safety:** existing user data takes precedence over cleanup convenience. Migrations must remain conservative, repeatable and non-destructive.

**Localization:** English is the canonical interface contract; translations overlay it and may fall back safely.

**Architecture:** `MENU_getResolvedTree()` remains the central presentation-neutral structural contract for modern consumers. New functionality should extend that model rather than duplicate menu-resolution logic.

**Release discipline:** test what users actually install — the generated plugin archive — not only the repository checkout.
