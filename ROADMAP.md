# Menu Plugin Modernization Roadmap

Target version: **1.4.0**  
Working branch: **modernize-1.4.0**

## Baseline

Menu 1.4.0 starts from the validated Menu 1.3.0 codebase.

The 1.3.0 release established the compatibility, security and architecture baseline:

- Geeklog **2.1.1 through 2.2.2**
- PHP **5.6 through 8.1**
- MySQL / MariaDB
- single-site and multisite installations
- upgrades from Menu **1.2.5 through 1.2.8.1**
- multisite-safe private storage
- safe upgrade/migration plumbing
- stable resolved-tree API
- theme presentation hand-off
- native/theme preview
- hardened administration and rendering paths
- CI on PHP 5.6 and PHP 8.1

Menu 1.4.0 must preserve that baseline. New capabilities should build on the existing resolved-tree and theme-facing architecture instead of reintroducing presentation logic into the plugin core.

---

## Development principles

### Compatibility first

- Keep one shared codebase across the supported Geeklog and PHP range unless the project explicitly changes that policy.
- Prefer capability checks and compatibility helpers over version-specific source trees.
- Preserve legacy `MENU_getMenu()` behavior while extending modern APIs.

### Data safety first

- Never silently delete or rewrite user menu data during upgrades.
- Keep migrations idempotent and repeatable.
- Preserve unresolved or unavailable destinations in administration whenever possible.
- Export/import and multisite operations must be explicit and reversible where practical.

### Structure before presentation

- The plugin owns menu structure, destination resolution, permissions, conditions and metadata.
- Themes own markup, layout, responsive behavior, dropdown presentation, animations and visual styling.
- New API fields must remain presentation-neutral wherever possible.

### Incremental delivery

Large features should be introduced in small, testable layers. Prefer completing and validating one coherent capability before starting the next one.

---

## Priority order for 1.4.0

The recommended implementation order is:

1. destination integrity and diagnostics;
2. active/selected state;
3. modern link metadata;
4. import/export JSON;
5. multisite clone/export/import;
6. multilingual menu resolution and fallback;
7. richer display conditions;
8. reusable/contextual menu structures;
9. accessibility and cache refinements;
10. optional ecosystem/API extensions.

This order intentionally strengthens the data model and API contracts before adding more complex automation or inter-plugin behavior.

---

## Phase 1 — Destination integrity and administrator diagnostics

### Goal

Keep stored menu definitions intact even when their target disappears, while preventing broken public navigation.

### Planned work

- [ ] Preserve references to missing plugins, static pages, topics and other resolvable Geeklog destinations.
- [ ] Represent unavailable destinations in administration using a clear status such as `[Unavailable ...]`.
- [ ] Prevent unavailable destinations from generating broken public links by default.
- [ ] Keep labels, hierarchy, permissions and ordering intact when a destination becomes unavailable.
- [ ] Automatically restore normal behavior if the destination becomes available again.
- [ ] Add administrator diagnostics for:
  - [ ] unavailable destinations;
  - [ ] malformed or rejected URLs;
  - [ ] orphaned elements;
  - [ ] invalid parent references;
  - [ ] hierarchy cycles or structural inconsistencies;
  - [ ] duplicate or suspicious menu definitions where useful.
- [ ] Provide a safe diagnostic summary without changing data automatically.
- [ ] Add automated tests for destination disappearance/reappearance and malformed references.

### Acceptance criteria

A menu may survive removal and later restoration of a referenced destination without losing the original menu definition.

---

## Phase 2 — Active / selected navigation state

### Goal

Allow themes and other consumers to understand which menu node corresponds to the current request.

### Planned work

- [ ] Extend the resolved-tree contract with presentation-neutral current-state information.
- [ ] Detect direct matches for supported destinations.
- [ ] Mark ancestor nodes when a descendant is active.
- [ ] Define stable states such as `active`, `current`, `ancestor` or equivalent without requiring theme-specific CSS names.
- [ ] Preserve existing API behavior for consumers that ignore the new fields.
- [ ] Validate topic, static page, plugin and URL matching behavior.
- [ ] Document how Eclipse and other themes can consume the state.

### Future consumers

- current navigation highlighting;
- breadcrumbs;
- contextual navigation;
- section-aware layouts;
- structured navigation data.

---

## Phase 3 — Modern link metadata and icons

### Goal

Add modern link capabilities without coupling Menu to a CSS framework or icon library.

### Planned work

- [ ] Support safe `target` values.
- [ ] Support validated `rel` values.
- [ ] Support optional `aria-label`.
- [ ] Support optional CSS class metadata.
- [ ] Evaluate a restricted, safe `data-*` attribute model.
- [ ] Keep external-link protection compatible with explicit link metadata.
- [ ] Add optional icon metadata supporting one or more of:
  - [ ] emoji;
  - [ ] safe SVG references/content under a defined policy;
  - [ ] CSS class names.
- [ ] Do not impose Font Awesome, Bootstrap Icons or another external library.
- [ ] Expose link/icon metadata through the resolved-tree API.
- [ ] Keep legacy rendering backward compatible.

---

## Phase 4 — JSON import / export

### Goal

Provide a portable, versioned representation of menu structures for backup, transfer and duplication.

### Planned work

- [ ] Define a versioned Menu JSON schema.
- [ ] Export complete menu structure including:
  - [ ] hierarchy;
  - [ ] labels;
  - [ ] destinations;
  - [ ] permissions where portable;
  - [ ] ordering;
  - [ ] link metadata;
  - [ ] conditions when implemented;
  - [ ] icon metadata when implemented.
- [ ] Separate portable data from site-specific identifiers where necessary.
- [ ] Validate JSON strictly before import.
- [ ] Provide dry-run/validation feedback before modifying data.
- [ ] Prevent hierarchy cycles and invalid parents during import.
- [ ] Define collision behavior for existing menu IDs/names.
- [ ] Never overwrite an existing menu silently.
- [ ] Add round-trip tests: export → import → equivalent resolved tree.

### Security requirements

- Imported data must pass the same validation as manually edited menu data.
- PHP-function elements must never gain execution permission simply because imported JSON requests it.

---

## Phase 5 — Advanced clone and multisite transfer

### Goal

Build on JSON portability and existing multisite-safe storage to make menus transferable between sites.

### Planned work

- [ ] Improve same-site cloning where useful.
- [ ] Support explicit clone/export/import workflows between multisite instances.
- [ ] Re-map internal element/parent IDs safely.
- [ ] Detect site-specific destinations that do not exist on the target site.
- [ ] Preserve unavailable destinations instead of dropping them.
- [ ] Define handling of permissions/groups that do not exist on the target site.
- [ ] Define handling of images/custom CSS referenced by a menu.
- [ ] Provide confirmation/dry-run information before cross-site operations.
- [ ] Keep site data isolated throughout the operation.

---

## Phase 6 — Multilingual menu resolution

### Goal

Make language-specific navigation predictable and automatic while preserving current installations.

### Planned work

- [ ] Define an explicit language association strategy for menus.
- [ ] Support variants such as `navigation_fr`, `navigation_en` or a cleaner metadata-based equivalent.
- [ ] Resolve the preferred menu from the active Geeklog language.
- [ ] Add configurable fallback behavior.
- [ ] Support a generic/default menu when no localized version exists.
- [ ] Ensure language fallback cannot create recursive resolution loops.
- [ ] Expose resolved language/fallback metadata to themes when useful.
- [ ] Add cache separation by language where required.
- [ ] Test multilingual behavior in single-site and multisite configurations.

---

## Phase 7 — Rich display conditions

### Goal

Allow menu elements to appear according to controlled runtime conditions without embedding arbitrary presentation logic in themes.

### Candidate conditions

- [ ] authenticated / anonymous;
- [ ] Geeklog group membership;
- [ ] active language;
- [ ] plugin active/inactive;
- [ ] current destination/plugin/content type;
- [ ] topic or section context;
- [ ] date/time windows where appropriate;
- [ ] other conditions only when they can be evaluated safely and predictably.

### Design constraints

- [ ] Keep condition evaluation centralized.
- [ ] Avoid arbitrary PHP expressions.
- [ ] Keep conditions serializable for JSON import/export.
- [ ] Ensure permission checks remain authoritative even when conditions match.
- [ ] Define cache variation/invalidation for condition-dependent trees.

Device detection should be treated cautiously because responsive presentation normally belongs to the theme. Add it only if a concrete non-presentation use case justifies it.

---

## Phase 8 — Reusable and contextual menu structures

### Goal

Reduce duplication and enable centralized navigation components.

### Reusable structures

- [ ] Allow a menu or submenu structure to be referenced from more than one menu.
- [ ] Prevent recursive inclusion loops.
- [ ] Preserve permissions and destination resolution through included structures.
- [ ] Decide whether inclusion is live/reference-based or snapshot-based; prefer explicit semantics.

### Contextual menus

- [ ] Allow centralized menu definitions to be selected by context.
- [ ] Support use cases such as Store, Documents, Videos or other plugins requesting a contextual navigation tree.
- [ ] Keep plugin-specific presentation outside Menu.
- [ ] Build contextual behavior on the same resolved-tree contract rather than parallel rendering APIs.

---

## Phase 9 — Administration editing improvements

### Goal

Make the editor clearer as Menu supports richer element metadata.

### Planned work

- [ ] Continue specialized forms for element types:
  - [ ] Geeklog Action;
  - [ ] Geeklog Menu;
  - [ ] Plugin;
  - [ ] Static Page;
  - [ ] Topic;
  - [ ] URL;
  - [ ] Sub Menu;
  - [ ] Label;
  - [ ] PHP Function where enabled.
- [ ] Show only fields relevant to the selected type.
- [ ] Surface unavailable destination state clearly.
- [ ] Integrate diagnostics into editing without making the editor noisy.
- [ ] Improve preview for active/selected state and new metadata.
- [ ] Preserve keyboard ordering controls.

---

## Phase 10 — Accessibility refinement

### Goal

Strengthen the semantic information supplied to themes and improve the retained native renderer.

### Planned work

- [ ] Review semantic list/navigation structure.
- [ ] Improve keyboard interaction in native/legacy rendering where feasible.
- [ ] Support appropriate `aria-expanded` / submenu state metadata.
- [ ] Ensure active/current state can map cleanly to `aria-current`.
- [ ] Verify labels for icon-only navigation.
- [ ] Avoid forcing theme-specific ARIA behavior through the core API.
- [ ] Test basic keyboard and screen-reader behavior for native rendering.

---

## Phase 11 — Smarter caching

### Goal

Extend the stable 1.3.0 cache foundation to handle richer context without stale or cross-context navigation.

### Planned work

- [ ] Define cache keys from only the dimensions that affect resolved output.
- [ ] Evaluate variation by:
  - [ ] site;
  - [ ] language;
  - [ ] permissions/group context;
  - [ ] relevant display conditions;
  - [ ] menu version/state.
- [ ] Avoid unnecessary cache fragmentation.
- [ ] Invalidate only affected menu trees when practical.
- [ ] Keep cache disposable and separate from persistent data.
- [ ] Add tests preventing cross-user, cross-language and cross-site leakage.

---

## Phase 12 — Versioning, drafts and scheduled publishing

These are valuable editorial features but should follow the core data/API work above.

### History / rollback

- [ ] Define what constitutes a menu revision.
- [ ] Store revision metadata efficiently.
- [ ] Allow administrators to inspect and restore a previous version.
- [ ] Ensure restoring a revision creates a new revision rather than destroying history.

### Draft menus

- [ ] Allow editing without immediate public activation.
- [ ] Preview draft trees through native and theme preview paths.
- [ ] Define permissions for draft viewing/editing.

### Scheduled publishing

- [ ] Allow activation/publication at a defined date/time.
- [ ] Define timezone behavior using Geeklog conventions.
- [ ] Avoid requiring high-frequency cron execution where possible.
- [ ] Ensure cache invalidation occurs when scheduled state changes.

---

## Phase 13 — SEO and structured navigation

### Goal

Reuse the resolved hierarchy for navigation semantics without turning Menu into a full SEO plugin.

### Candidate work

- [ ] Provide breadcrumb-ready ancestry data.
- [ ] Evaluate optional breadcrumb helpers built on active/selected state.
- [ ] Evaluate schema.org/BreadcrumbList output or a presentation-neutral data helper.
- [ ] Avoid duplicate structured data when themes or SEO plugins already provide it.

---

## Phase 14 — Inter-plugin services and dynamic hubs

This phase should be attempted only after the tree, diagnostics, conditions and contextual APIs are stable.

### Inter-plugin services

- [ ] Define a narrow contract allowing plugins to advertise safe navigation destinations or navigation blocks.
- [ ] Prefer official Geeklog plugin APIs/services where available.
- [ ] Keep Menu in control of validation, permissions and final tree composition.
- [ ] Avoid tight compile-time dependencies on Store, Documents, Videos or other individual plugins.

### Pages pillars / hubs

- [ ] Explore automatically populated menu/submenu structures derived from content related to a pillar page/topic.
- [ ] Keep automatically generated relationships distinguishable from manually maintained menu nodes.
- [ ] Define update/invalidation behavior when related content changes.
- [ ] Avoid making Menu responsible for full content indexing; consume structured relationships from the appropriate plugin/service instead.

---

## Phase 15 — External / headless API

### Goal

Expose the resolved tree to non-theme consumers only after the internal API contract is mature.

### Candidate work

- [ ] Define a JSON representation based on the stable resolved-tree model.
- [ ] Decide authentication/permission behavior for anonymous and authenticated consumers.
- [ ] Ensure the API never bypasses Geeklog permissions.
- [ ] Support language/context parameters only when safely validated.
- [ ] Add cache headers/version information where useful.
- [ ] Document use by decoupled frontends, applications and AI/agent tooling.

This should reuse the same resolver as themes rather than introduce a second navigation engine.

---

## Deferred / optional work

The following remain optional unless a concrete need or maintenance problem justifies them:

- replace/remove SlickNav after legacy rendering stabilization;
- replace remaining legacy drag/drop dependencies;
- device-specific display conditions;
- arbitrary custom `data-*` attributes;
- direct structured-data rendering inside the plugin;
- plugin-specific adapters that would create tight dependencies.

---

## 1.4.0 scope recommendation

Do not require every phase in this document for the 1.4.0 release.

A coherent and realistic **1.4.0 core scope** would be:

- [ ] Phase 1 — destination integrity and diagnostics;
- [ ] Phase 2 — active/selected state;
- [ ] Phase 3 — modern link metadata;
- [ ] Phase 4 — JSON import/export;
- [ ] Phase 6 — multilingual resolution/fallback if implementation remains low-risk;
- [ ] targeted accessibility/cache work required by those features.

Phases 5 and 7–15 can move to 1.4.x or later releases depending on complexity and community feedback.

---

## Validation requirements for every 1.4.x release

- [ ] No regression in Geeklog 2.1.1 support unless the compatibility policy is explicitly changed.
- [ ] No regression in Geeklog 2.2.2 support.
- [ ] PHP 5.6 lint/tests remain green unless the PHP support policy is explicitly changed.
- [ ] PHP 8.1 lint/tests remain green.
- [ ] Existing 1.3.0 menus upgrade without destructive changes.
- [ ] Legacy rendering remains functional.
- [ ] Resolved-tree consumers remain backward compatible or receive an explicitly versioned contract.
- [ ] Multisite isolation tests remain green.
- [ ] Import/export, conditions and cache changes receive dedicated automated tests when introduced.
- [ ] Final runtime warning/error-log audit on supported Geeklog test installations.
- [ ] Upgrade through the actual generated release ZIP before stable publication.

## Compatibility principle

Do not maintain separate source trees for old and new Geeklog releases. Prefer small compatibility helpers and runtime capability checks so one Menu release supports the declared range.

## Data-safety principle

Existing user data takes precedence over cleanup convenience. Upgrade routines must remain non-destructive, repeatable and conservative. Unavailable destinations and legacy references should be preserved whenever possible rather than silently discarded.

## API principle

`MENU_getResolvedTree()` is the central structural contract for modern consumers. New features should extend that model carefully, preserve backward compatibility and avoid duplicating resolution logic across themes, plugins or external APIs.
