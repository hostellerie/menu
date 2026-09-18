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
- normalization of presentation-bearing labels returned by Geeklog/plugin callbacks;
- preservation of semantic states such as warnings without exposing theme-specific HTML;
- the resolved navigation structure exposed to themes.

### Eclipse theme

Eclipse remains responsible for:

- semantic navigation markup;
- desktop dropdown presentation;
- responsive/mobile navigation;
- CSS and theme variables;
- JavaScript presentation behaviour;
- active/current visual states;
- visual presentation of optional semantic node states exposed by Menu.

Eclipse must not independently rebuild Menu links from parallel rules. There must be one source of truth for navigation data.

## Semantic status metadata

Resolved nodes may contain an optional `status` value. The field is presentation-neutral and allows Menu to preserve intent from Geeklog controls without passing HTML into a theme-facing label.

Currently recognized values are:

- `warning` — for Geeklog warning controls, including Denim's `uk-text-danger` / `uk-text-warning` output;
- `success` — for `uk-text-success` output;
- `info` — for `uk-text-primary` or `uk-text-muted` output;
- an empty string when no semantic state is present.

For example, Geeklog 2.2.x reCAPTCHA may return an administrative label rendered by `COM_createControl('display-text-warning', ...)` as:

```html
<span class="uk-text-danger">reCAPTCHA</span>
```

Menu exposes that as presentation-neutral data:

```php
array(
    'label' => 'reCAPTCHA',
    'status' => 'warning',
)
```

A consuming theme such as Eclipse can then choose its own warning color, icon or badge without depending on UIKit classes. Legacy Menu renderers can continue using Geeklog's original HTML path for backward compatibility.

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

`MENU_getResolvedTree()` exposes a resolved, permission-filtered tree for a named menu such as `navigation`, while the existing `MENU_getMenu()` HTML API remains available for backward compatibility.
