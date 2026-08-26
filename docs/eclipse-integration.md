# Eclipse integration contract

## Goal

Eclipse must use the Menu plugin `navigation` menu as the authoritative source for the theme's primary navigation.

> **Eclipse integration:** use the Menu plugin `navigation` menu as the authoritative source for the theme's primary navigation. Preserve the Menu plugin hierarchy, permissions, item types, ordering and URLs, while Eclipse only controls the responsive HTML/CSS/JavaScript presentation.

## Responsibilities

### Menu plugin

Menu remains responsible for:

- menu data and hierarchy;
- element ordering;
- permissions and activation;
- element types and subtypes;
- resolution of Geeklog Actions and Geeklog Core items;
- plugin, Static Page, URL and Topic destinations;
- the resolved navigation structure exposed to themes.

### Eclipse theme

Eclipse remains responsible for:

- semantic navigation markup;
- desktop dropdown presentation;
- responsive/mobile navigation;
- CSS and theme variables;
- JavaScript presentation behaviour;
- active/current visual states.

Eclipse must not independently rebuild Menu links from parallel rules. There must be one source of truth for navigation data.

## Reference menu

Integration tests should use a `navigation` menu containing at least:

- Home — type 2, Geeklog Action;
- Articles;
- Topics;
- Plugins;
- Static Page;
- external URL;
- Submenu;
  - Element 1;
  - Element 2.

The Home item is intentionally type 2 because it is the regression case that exposed the historical `MENU_editElement()` type selector bug.

## Rendering API direction

The Menu administration preview should remain a neutral rendering of the same resolved Menu structure. Eclipse should consume that same structure through a theme-facing API rather than scraping Menu's legacy HTML output.

A future Menu 1.3.0 API should therefore expose a resolved, permission-filtered tree for a named menu such as `navigation`, while preserving the existing `MENU_getMenu()` HTML API for backward compatibility.
