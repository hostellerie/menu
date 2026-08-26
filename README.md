# Menu Plugin for Geeklog

Menu is a navigation management plugin for the [Geeklog CMS](https://www.geeklog.net/).

It allows administrators to build and manage reusable navigation structures for headers, footers, blocks and content areas, while keeping compatibility with existing Geeklog themes and providing a cleaner integration path for modern themes.

> **Official project:** https://github.com/Geeklog-Plugins/menu  
> Documentation, releases, issues and future project information will be maintained there.

## Menu 1.3.0

Menu 1.3.0 is a compatibility and modernization release focused on preserving existing installations while making the plugin safer and easier to integrate with current Geeklog sites.

### Compatibility target

- Geeklog **2.1.1 through 2.2.2**
- PHP **5.6 through 8.1**
- MySQL / MariaDB
- single-site and multisite installations
- upgrades from Menu **1.2.5 through 1.2.8.1**

One shared codebase is used across the supported Geeklog versions.

## Main features

- create horizontal, vertical and footer navigation menus;
- build hierarchical menus and submenus;
- reorder menu items from the administration interface;
- control menu visibility and permissions;
- use Geeklog destinations such as topics, static pages and plugin-provided links;
- add menus to content with the `[menu:]` autotag;
- customize legacy menu presentation with colors, images and CSS;
- preview native and theme-provided menu rendering from administration;
- expose a presentation-neutral resolved menu tree for modern themes;
- retain legacy rendering for existing sites and themes.

## Modern theme integration

Menu 1.3.0 separates menu structure from presentation more clearly.

Modern themes can consume the resolved menu tree and provide their own HTML, CSS and JavaScript without duplicating Menu's destination, hierarchy, permission and ordering logic.

Legacy themes can continue using the traditional Menu rendering path.

## Global configuration

Menu 1.3.0 adds global Geeklog configuration switches for:

- runtime cache;
- accessibility markup;
- external-link protection;
- PHP menu elements;
- legacy rendering;
- legacy CSS loading;
- legacy JavaScript loading;
- debug logging.

Conservative defaults preserve the historical Menu behavior after an upgrade.

## Multisite support

Menu 1.3.0 improves support for Geeklog installations where several sites share the same plugin code.

Plugin data is stored using site-specific Geeklog paths. Legacy `menu_data/` content is migrated non-destructively when the plugin is upgraded.

Deploying the new shared code and upgrading each Geeklog site individually is supported by design: sites that have not yet completed the plugin upgrade continue to use conservative runtime defaults and the legacy storage fallback until their own upgrade is performed.

## Upgrading

Always back up the database and site files before upgrading a production installation.

The 1.3.0 upgrade is designed to preserve existing menu structures, permissions, custom CSS and uploaded images.

The database migration is deliberately small and idempotent. It adds the composite `menu_parent_order` index to `menu_elements` when missing and initializes the new configuration entries without overwriting existing values.

Legacy plugin-owned files are copied to the preferred site-specific storage location without deleting or overwriting the originals.

Upgrade validation now covers the supported legacy range from Menu 1.2.5 through 1.2.8.1 on the supported Geeklog generations.

## Installation

Install Menu through the standard Geeklog plugin administration interface.

After installation, open:

`Admin → Plugins → Menu`

and create or configure the menus required by the site.

For current installation and upgrade instructions, use the official project repository:

https://github.com/Geeklog-Plugins/menu

## Development and testing

The modernization branch is continuously linted and tested with PHP 5.6 and PHP 8.1.

The release validation also includes real Geeklog 2.1.1 and 2.2.2 installations, legacy upgrade paths, multisite behavior, administration workflows and frontend rendering.

The development roadmap is available in [`ROADMAP.md`](ROADMAP.md).

## Bugs and feature requests

Please report bugs, compatibility issues and feature requests on the official project tracker:

https://github.com/Geeklog-Plugins/menu/issues

When reporting an issue, include the Geeklog version, PHP version, database server/version, Menu version and any relevant Geeklog `error.log` entries.

## Contributing

Contributions, testing reports and documentation improvements are welcome.

1. Fork the official repository.
2. Create a dedicated branch for the change.
3. Keep compatibility with the supported Geeklog/PHP range unless the project roadmap explicitly changes it.
4. Test the change on the relevant supported environments.
5. Open a pull request describing the problem and the proposed solution.

## License

Menu is free software distributed under the GNU General Public License, version 2 or, at your option, any later version.
