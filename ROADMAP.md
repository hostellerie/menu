# Menu Plugin Modernization Roadmap

Current release line: **1.4.x**  
Current development branch: **modernize-1.4.0**

## Compatibility policy

Menu 1.4.x preserves the established compatibility target:

- Geeklog **2.1.1 through 2.2.2**;
- PHP **5.6 through 8.1**;
- MySQL / MariaDB;
- single-site and multisite installations;
- conservative upgrades from supported legacy Menu installations.

One shared source tree should support the declared range while that remains practical. Prefer capability checks and small compatibility helpers over duplicated old/new implementations.

The plugin should follow the current recommendations in `Geeklog-Plugins/memorandum`, especially the multisite, persistent-storage, shared-files upgrade-safety, configuration-migration and plugin-metadata guidance.

---

# 1.4.0 baseline — implemented

The following work is part of the 1.4.0 baseline and should not be re-planned as future work.

## Administration and security

- [x] Split major administration responsibilities into dedicated modules.
- [x] Separate menu views from menu mutations.
- [x] Add dedicated element validation and element view modules.
- [x] Add administration security helpers.
- [x] Isolate image-upload handling.
- [x] Normalize request handling with Geeklog input helpers where modernized.
- [x] Escape stored and user-controlled labels/values in administration output.
- [x] Keep PHP-function menu elements behind explicit configuration/security controls.
- [x] Preserve progressive enhancement when optional JavaScript is unavailable.
- [x] Save drag ordering asynchronously without reloading the page.
- [x] Keep activation/deactivation on normal Geeklog POST/CSRF flow.
- [x] Show the real number of menus in the Geeklog administration menu instead of `N/A`.

## Runtime, themes and rendering

- [x] Maintain the legacy renderer for existing themes.
- [x] Provide a presentation-neutral resolved-tree path for modern themes.
- [x] Keep hierarchy, destination resolution, permissions and ordering in Menu.
- [x] Preserve presentation ownership when a theme explicitly owns a menu resource.
- [x] Preserve Menu styling when a theme merely embeds `[menu:...]`.
- [x] Preserve configured link/hover colors in highly specific theme/footer contexts.
- [x] Support `[menu:name]` and `[menu:numeric-id]`, while keeping exact-name lookup first.

## Frontend assets and generated CSS

- [x] Add a request-scoped asset-usage registry.
- [x] Demand-load legacy CSS/JS where the Geeklog document lifecycle permits it.
- [x] Load SlickNav only for used horizontal cascading menus that require it.
- [x] Avoid SlickNav for simple footer and vertical menus that do not need it.
- [x] Preserve `legacy_rendering`, `load_legacy_css` and `load_legacy_js` switches.
- [x] Preserve Geeklog 2.1.1 late-render compatibility with a conservative fallback.
- [x] Detect relevant theme templates without final-HTML scanning.
- [x] Publish generated per-menu CSS as public fingerprinted `.css` assets.
- [x] Use content fingerprints so browser cache invalidates automatically after style changes.
- [x] Keep inline `<style>` only as a fallback when the public CSS directory cannot be written.
- [x] Keep generated public CSS site-specific through Geeklog image paths.

## Cache, storage and multisite foundations

- [x] Separate runtime configuration helpers.
- [x] Provide runtime and filesystem cache helpers.
- [x] Keep disposable cache separate from persistent menu data.
- [x] Derive plugin-owned storage from the active site's Geeklog configuration.
- [x] Use site-specific plugin storage where appropriate.
- [x] Use active `$_TABLES` mappings instead of hard-coded database prefixes.
- [x] Keep filesystem/data migrations conservative and idempotent.
- [x] Keep upgrade work scoped to the active site.
- [x] Version generated CSS cache entries using presentation fingerprints.

## Configuration and localization

- [x] Make English the canonical interface language contract.
- [x] Load English first and overlay the selected translation.
- [x] Extend French localization for the modernized administration interface.
- [x] Add language-contract tests for PHP/INC references.
- [x] Add native Geeklog configuration tooltips through `plugin_getconfigtooltip_menu()`.
- [x] Provide French configuration help with English fallback.
- [x] Remove obsolete `samplesetting1` / `samplesetting2` configuration labels.
- [x] Remove obsolete `samplesetting1` / `samplesetting2` rows during 1.4.0 upgrade.
- [x] Preserve historical color values safely (`#RRGGBB`, `RRGGBB`, `#RGB`, `RGB`, empty, `none`, malformed values).
- [x] Use Geeklog's native Configuration API for global Menu settings.
- [x] Keep intentional select controls matched to `$LANG_configselects['menu']`.

## Metadata, build and release engineering

- [x] Keep permanent CI for PHP 5.6 and PHP 8.1.
- [x] Build an installable `menu-1.4.0.zip` through GitHub Actions.
- [x] Validate the generated archive.
- [x] Publish the ZIP as an Actions artifact.
- [x] Keep `dist/menu-1.4.0.zip` available for installation testing.
- [x] Regenerate the development archive automatically after release-source changes.
- [x] Keep plugin installer metadata synchronized with the 1.4.0 release line.
- [x] Provide root-level static `plugin.json` metadata.
- [x] Keep `plugin.json` id/name/icon aligned with native plugin metadata.
- [x] Declare minimum Geeklog **2.1.1** and PHP **5.6.0** requirements in `plugin.json`.
- [x] Avoid packaged files whose names begin with `.`.

---

# 1.4.0 release gate — validation only

No large feature should be added before the stable 1.4.0 release. Remaining work is validation and cleanup only.

- [ ] Latest branch-head CI green on PHP 5.6 and PHP 8.1.
- [ ] Fresh installation from the generated ZIP on Geeklog 2.1.1.
- [ ] Fresh installation from the generated ZIP on Geeklog 2.2.2.
- [ ] Upgrade from an existing Menu installation on Geeklog 2.1.1.
- [ ] Upgrade from an existing Menu installation on Geeklog 2.2.2.
- [ ] Warning/error-log audit on both supported Geeklog generations.
- [ ] Verify all administration screens on PHP 5.6 and PHP 8.1.
- [ ] Verify generated public CSS in writable and non-writable public-image-path scenarios.
- [ ] Verify multisite paths and generated CSS URLs remain site-specific.
- [ ] Verify two site contexts with different `path_data`, URLs and table mappings remain isolated.
- [ ] Test staggered shared-files upgrade: new 1.4.0 files active while one site still has the previous persisted plugin state.
- [ ] Verify frontend and administration remain safe before that site's explicit upgrade runs.
- [ ] Verify upgrading site A does not alter site B configuration, files or database records.
- [ ] Verify interrupted/retried upgrade remains idempotent where practical.
- [ ] Verify `[menu:name]`, `[menu:id]`, invalid ID, and numeric-name edge cases.
- [ ] Verify the administration menu count reflects the actual number of menus.
- [ ] Verify configuration tooltips in English and French.
- [ ] Verify upgrade removes obsolete `samplesetting1/2` rows without touching valid configuration.
- [ ] Confirm `plugin.json` remains consistent with `autoinstall.php` and `config.php` compatibility declarations.
- [ ] Confirm no temporary debugging workflow or trace remains.
- [ ] Update release notes with all final 1.4.0 changes.

Optional release-engineering improvement:

- [ ] Add build identification metadata (commit SHA/date) for same-named development ZIPs.

---

# 1.4.x — hardening without schema/API disruption

The 1.4.x line should prefer fixes, diagnostics and compatibility improvements over large new data models.

## 1.4.1 — diagnostics and cleanup

### Destination integrity

- [ ] Detect missing plugins, static pages, topics and other resolvable destinations.
- [ ] Preserve stored destination references instead of deleting them automatically.
- [ ] Show unavailable destinations clearly in administration.
- [ ] Prevent unavailable destinations from generating broken public links by default.
- [ ] Restore normal behavior automatically if the destination returns.
- [ ] Detect orphaned elements, invalid parents and hierarchy cycles.
- [ ] Provide a non-destructive administrator diagnostic summary.

### Remaining modernization cleanup

- [ ] Extend language-contract auditing to `.thtml` templates.
- [ ] Localize remaining hardcoded administration/security messages.
- [ ] Replace remaining fragile `current()/next()` iteration patterns where behavior is equivalent and PHP 5.6 compatible.
- [ ] Remove obsolete duplicate administration/presentation code where tests prove it is unused.
- [ ] Continue monitoring Geeklog cached-content/autotag behavior without adding final-HTML scanning hacks.
- [ ] Keep compatibility fallbacks isolated and documented so they can be removed only after the supported migration window closes.

## 1.4.2+ — active state and accessibility

### Active/current navigation state

- [ ] Extend the resolved-tree contract with presentation-neutral current-state metadata.
- [ ] Detect direct destination matches.
- [ ] Mark ancestors of the active node.
- [ ] Expose data suitable for `aria-current` and theme highlighting.
- [ ] Validate topic, static-page, plugin and URL matching.

### Accessibility and cache review

- [ ] Review semantic navigation/list structure in retained native rendering.
- [ ] Improve keyboard submenu behavior where feasible.
- [ ] Expose metadata suitable for `aria-expanded`.
- [ ] Verify icon-only labels remain accessible.
- [ ] Confirm cache variation never leaks across site, language or permission contexts.

---

# 1.5.0 — structured navigation capabilities

1.5.0 may introduce additive schema/API changes, but should remain backward compatible with 1.4.x consumers. Any persistent-state change must preserve shared-files staggered-upgrade safety.

## Modern link metadata

- [ ] Support validated `target` values.
- [ ] Support validated `rel` values.
- [ ] Support optional `aria-label`.
- [ ] Support optional CSS-class metadata.
- [ ] Evaluate a restricted safe `data-*` model.
- [ ] Add presentation-neutral icon metadata without requiring an icon library.
- [ ] Expose metadata through the resolved tree while retaining legacy compatibility.

## Versioned JSON import/export

- [ ] Define a versioned Menu JSON schema.
- [ ] Export hierarchy, labels, destinations, ordering, metadata and portable permissions.
- [ ] Validate imports before mutation.
- [ ] Provide dry-run feedback.
- [ ] Prevent invalid parents and hierarchy cycles.
- [ ] Define collision behavior explicitly.
- [ ] Never overwrite an existing menu silently.
- [ ] Add export → import → equivalent-tree tests.

## Multisite portability

Menu itself must remain scoped to the active site. It must not scan sibling site directories or directly modify another site's tables/configuration during normal plugin requests.

- [ ] Use the versioned JSON format as the portable exchange representation.
- [ ] Re-map element/parent identifiers safely during import into the active site.
- [ ] Detect destinations or groups that do not exist in the target site.
- [ ] Preserve unavailable references instead of silently dropping them.
- [ ] Define handling for images and custom CSS in a portable package.
- [ ] Keep site isolation authoritative during import/export.
- [ ] Leave direct site-A → site-B orchestration to a dedicated Multisite Manager or equivalent administrative tool.

## Multilingual menu resolution

- [ ] Define explicit language association for menus.
- [ ] Resolve the preferred menu from the active Geeklog language.
- [ ] Add deterministic fallback to a generic/default menu.
- [ ] Prevent recursive fallback loops.
- [ ] Vary cache by language only when required.
- [ ] Expose language/fallback metadata to consumers.

This remains separate from the plugin interface-language fallback already used by administration strings.

## Rich display conditions

Candidate conditions:

- [ ] authenticated / anonymous;
- [ ] Geeklog group membership;
- [ ] active language;
- [ ] plugin active/inactive;
- [ ] current destination/content type;
- [ ] topic/section context;
- [ ] optional date/time windows.

Conditions must remain serializable, centrally validated and subordinate to Geeklog permissions. Arbitrary PHP expressions must not be introduced.

---

# 1.6.0 — reusable and contextual navigation

This release should build only on a stable resolved-tree and condition model.

Menu is a navigation/structure plugin, not an addressable content plugin. It should therefore not mechanically implement content-oriented interoperability callbacks such as `plugin_getiteminfo_*()`, collection feeds or content lifecycle events unless a future concrete content model genuinely requires them. For navigation interoperability, prefer existing Geeklog Plugin APIs/services and the resolved-tree contract.

- [ ] Allow reusable menu/submenu structures without recursive inclusion loops.
- [ ] Define explicit live-reference versus snapshot semantics.
- [ ] Allow plugins such as Store, Documents or Videos to request contextual navigation through a generic contract.
- [ ] Keep plugin-specific HTML outside Menu.
- [ ] Reuse the resolved-tree contract rather than introducing parallel renderers.
- [ ] Define a narrow inter-plugin contract for advertising safe destinations or navigation blocks.
- [ ] Prefer existing Geeklog plugin/service APIs where they fit.
- [ ] Avoid hard dependencies on individual plugins.
- [ ] Document capability discovery so consumers can detect optional Menu integration without loading implementation-specific internals.

This phase is the appropriate place to revisit the earlier idea of a more dynamic Navigation block once its concrete use case is defined.

---

# 2.0.0 — advanced editorial and external APIs

2.0.0 should be reserved for features that materially expand the plugin contract or require stronger compatibility boundaries.

## Revisions, drafts and scheduled publication

- [ ] Define menu revisions and efficient history storage.
- [ ] Restore prior revisions without destroying history.
- [ ] Allow draft menu editing/preview.
- [ ] Add scheduled activation only when a concrete editorial need justifies it.

## SEO/navigation semantics

- [ ] Provide breadcrumb-ready ancestry data from the resolved tree.
- [ ] Evaluate optional breadcrumb helpers.
- [ ] Evaluate presentation-neutral data for schema.org `BreadcrumbList`.
- [ ] Avoid duplicate structured data when themes or SEO plugins already provide it.

## Dynamic hubs

- [ ] Allow Hub/page-pillar integrations to consume structured relationships rather than making Menu a content indexer.
- [ ] Keep automatically generated relationships distinguishable from manually maintained nodes.
- [ ] Define invalidation when related content changes.

## External/headless API

- [ ] Expose JSON only after the internal resolved-tree contract is stable and versioned.
- [ ] Reuse the same permission-aware resolver used by themes.
- [ ] Define authentication and request context explicitly.
- [ ] Document use by decoupled frontends, applications and agent tooling.

---

# Release validation requirements

Every stable release must validate the surface it claims to support.

- [ ] Geeklog 2.1.1 compatibility verified when still declared supported.
- [ ] Geeklog 2.2.2 compatibility verified when still declared supported.
- [ ] PHP 5.6 lint/tests verified while still declared supported.
- [ ] PHP 8.1 lint/tests verified.
- [ ] Existing menus upgrade without destructive changes.
- [ ] Legacy rendering remains functional while supported.
- [ ] Resolved-tree consumers remain backward compatible or receive an explicitly versioned contract.
- [ ] Multisite isolation remains intact.
- [ ] Shared-files staggered upgrade is safe whenever persistent state changes.
- [ ] New files remain operational with the previous supported persisted state until the active site's explicit upgrade completes.
- [ ] Failed/retried migrations preserve enough legacy state for recovery.
- [ ] English language contract passes.
- [ ] Static `plugin.json` metadata remains aligned with native installer/runtime metadata.
- [ ] No unexplained warnings/fatal errors in supported test environments.
- [ ] Fresh install and upgrade are tested using the actual generated ZIP.
- [ ] Generated archive content is verified before publication.

---

# Guiding principles

**Compatibility:** avoid separate source trees for old and new Geeklog releases while the shared compatibility policy remains practical.

**Data safety:** existing user data takes precedence over cleanup convenience. Migrations must remain conservative, repeatable and non-destructive.

**Site scope:** shared plugin files do not imply shared plugin state. Configuration, database state, persistent files and migrations belong to the active Geeklog site unless an explicit higher-level multisite tool coordinates otherwise.

**Upgrade safety:** deploying new files and migrating persisted state are separate events. New code must tolerate the previous supported persisted state until the active site's controlled upgrade succeeds.

**Storage:** distinguish persistent plugin data, generated assets and disposable cache. Cache cleanup must never remove persistent state.

**Localization:** English is the canonical interface contract; translations overlay it and may fall back safely.

**Architecture:** `MENU_getResolvedTree()` remains the central presentation-neutral structural contract for modern consumers. New functionality should extend that model rather than duplicate menu-resolution logic.

**Presentation ownership:** Menu owns structure; themes may own presentation only through an explicit contract.

**Interoperability:** use native Geeklog Plugin APIs/services where they fit. Do not force content-plugin contracts onto Menu when its role is navigation structure rather than addressable content.

**Release discipline:** test what users actually install — the generated plugin archive — not only the repository checkout.

**Version discipline:** patch releases should harden existing contracts; minor releases may add backward-compatible capabilities; major releases are reserved for contract-breaking or substantially expanded APIs.
