# Menu 1.3.0 Release Notes

Menu 1.3.0 is a compatibility, security and maintainability release for the Geeklog Menu plugin.

The release preserves the existing Menu model and legacy rendering while preparing the plugin for current Geeklog installations, multisite deployments and modern themes.

## Compatibility

- Geeklog 2.1.1 through 2.2.2
- PHP 5.6 through 8.1
- MySQL / MariaDB
- single-site and multisite installations
- upgrades from Menu 1.2.5 through 1.2.8.1

## Highlights

### Safer administration

- state-changing administration operations are POST-only;
- Geeklog security tokens are validated server-side;
- `menu.admin` authorization is centralized;
- menu IDs, element IDs, hierarchy, parent and ordering values are validated;
- hierarchy cycles are rejected;
- SQL strings are escaped and numeric identifiers are cast on modernized paths;
- new elements use database `AUTO_INCREMENT` instead of `MAX(id)+1`.

### Safer output and uploads

- stored labels and URLs are handled more defensively;
- dangerous URL schemes are rejected;
- legacy CSS colors and image filenames are validated;
- uploaded menu images are validated from file contents;
- PNG, GIF and JPEG are supported with size/dimension limits;
- server-side filenames are generated for uploads;
- deletion and cache-cleanup paths are protected against traversal and symlink issues.

### Multisite and persistent data

- plugin-owned private data uses site-specific `{path_data}-menu/` storage;
- legacy `menu_data/` content is migrated non-destructively;
- existing destination files are never overwritten during migration;
- public menu images continue to use each site's Geeklog image path;
- sites sharing the same plugin code can be upgraded individually.

### Runtime and performance

- runtime menu loading is reduced to three database queries independent of menu count;
- cache invalidation is centralized;
- the unused HTML rendering cache path has been removed;
- safe legacy CSS caching is retained;
- SlickNav is loaded only for legacy horizontal cascading menus that require it.

SlickNav remains bundled in 1.3.0 strictly as a legacy compatibility layer. Its replacement or removal is deferred to a later release.

### Administration improvements

- submenu creation/editing is restored consistently;
- `Display After` ordering defaults to the end of the sibling list;
- drag ordering uses the retained TableDnD 0.6 compatibility implementation;
- keyboard up/down ordering controls are available;
- native and theme-provided menu previews are available from administration;
- obsolete administration and IE6-specific assets have been removed.

### Modern theme integration

Menu 1.3.0 introduces a presentation-neutral resolved-tree API.

Modern themes can consume the resolved menu structure while keeping hierarchy, ordering, permissions and destination resolution inside the plugin. Existing themes can continue using the legacy `MENU_getMenu()` rendering path.

Eclipse integration can therefore render Menu navigation without duplicating Menu's destination-resolution logic.

## Global configuration

Menu 1.3.0 adds eight Geeklog configuration switches:

- `enable_cache`
- `accessibility_markup`
- `external_link_protection`
- `allow_php_elements`
- `legacy_rendering`
- `load_legacy_css`
- `load_legacy_js`
- `debug`

Conservative defaults preserve historical behavior after upgrade. Existing values are not overwritten by the 1.3.0 configuration migration.

## Database upgrade

The database migration is deliberately small and idempotent.

It adds the following index when missing:

```sql
(menu_id, pid, element_order)
```

No destructive table or column conversion is required for the supported Menu 1.2.5 through 1.2.8.1 upgrade range.

## Upgrade notes

Back up the Geeklog database and site files before upgrading a production installation.

The 1.3.0 upgrade is designed to preserve:

- existing menus and hierarchy;
- permissions;
- menu configuration;
- custom CSS;
- uploaded images;
- legacy rendering behavior.

The upgrade path and installable ZIP have been exercised against the supported legacy range during final validation.

For multisite installations sharing one plugin source tree, deploying the 1.3.0 code before upgrading every individual site is supported by design. Sites pending their database/plugin upgrade use conservative runtime defaults and the legacy storage fallback.

## Testing and packaging

The development branch is continuously linted and tested under PHP 5.6 and PHP 8.1.

CI also builds the installable `menu-1.3.0.zip` package used for final release validation.

## Project

Official repository, documentation, releases and issue tracking:

https://github.com/Geeklog-Plugins/menu
