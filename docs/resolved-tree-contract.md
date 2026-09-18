# Menu Resolved Tree Contract

## Status

Contract version: **1**  
Introduced with Menu **1.4.0**.

`MENU_getResolvedTree()` is the public, presentation-neutral representation of a Menu menu for themes and other trusted Geeklog consumers.

The contract is intentionally narrow. It describes what Menu guarantees to expose after resolving the current site, current visitor, permissions and available destinations. It is not a database export contract and must not be reconstructed by reading Menu tables directly.

## Security and visibility rule

The resolved tree is **permission-filtered for the current Geeklog request context**.

A consumer must never receive a menu or node merely because it exists in storage. Menu resolves visibility first and exposes only the resulting public/authorized tree.

The contract therefore guarantees that:

- inactive menus return an empty tree;
- menus unavailable to the current visitor return an empty tree;
- inactive elements are omitted;
- elements whose effective access is denied are omitted;
- group-restricted elements are omitted when the current visitor is not in the required Geeklog group;
- unavailable plugin destinations are omitted;
- unavailable or draft Static Pages are omitted;
- topic collections use Geeklog permission SQL and language filtering;
- Static Page collections use Geeklog permission SQL and exclude drafts;
- Geeklog actions whose core access rules deny the current visitor are omitted;
- administration children are produced through Geeklog-aware permission checks rather than by exposing raw administration records.

No ACL internals need to be exposed to consumers. In particular, consumers should not require raw group IDs, owner IDs, permission bitmasks or Menu database rows in order to render navigation.

A consumer must treat absence from the resolved tree as authoritative for the current request context.

## Public API

```php
MENU_getResolvedTree($name = 'navigation');
MENU_getResolvedTreeContractVersion();
```

`MENU_getResolvedTreeContractVersion()` returns `1` for this contract.

`MENU_getResolvedTree()` returns an array of top-level nodes. Each node may contain nested nodes in `children`.

## Version 1 node fields

Version 1 guarantees these fields for every returned node:

| Field | Type | Meaning |
| --- | --- | --- |
| `id` | integer | Menu element identifier. Synthetic Geeklog-generated nodes use `0`. |
| `parent_id` | integer | Parent element identifier when applicable. Synthetic nodes use `0`. |
| `label` | string | Plain-text display label. Raw/encoded HTML is not part of the contract. |
| `type` | integer | Menu element type or synthetic node type. |
| `subtype` | mixed | Type-specific destination identifier. Consumers should not assume one scalar subtype format across all types. |
| `url` | string | Resolved destination URL, `#`, or an empty string for non-link nodes. |
| `target` | string | Resolved link target metadata. |
| `rel` | string | Link relationship metadata derived by Menu. |
| `aria_label` | string | Accessibility label when accessibility markup is enabled, otherwise an empty string. |
| `active` | boolean | `true` for every exposed v1 node. Inactive stored nodes are filtered out before exposure. |
| `selected` | boolean | Whether the node directly matches the current request according to the current resolver. |
| `resolved` | boolean | Whether Menu could represent the node completely as structured navigation data. |
| `children` | array | Permission-filtered nested nodes using this same contract. |

Additional fields may appear on specialized or future nodes. Consumers must ignore unknown fields.

## Compatibility rules

Within contract version 1:

- existing field names will not be repurposed;
- existing field types will not intentionally change;
- existing field meanings will remain stable;
- new optional fields may be added;
- new node types or subtypes may be added while preserving the common fields;
- consumers must ignore fields they do not understand;
- consumers must not require internal array ordering beyond the explicit menu/child order returned by Menu;
- consumers must not infer hidden nodes from gaps in identifiers or parent relationships.

A future incompatible representation must use a new contract version rather than silently changing version 1 semantics.

## Authorization boundary

`MENU_getResolvedTree()` is a **read representation**, not an authorization oracle for unrelated operations.

A consumer may render or inspect the returned nodes in the same request/security context, but the presence of a node does not grant permission to perform arbitrary mutations on that node or its destination.

Future write capabilities such as creating, moving, updating or deleting menu elements must perform their own `menu.admin` and operation-specific checks. They must not rely solely on a node having appeared in a resolved tree.

Likewise, an external connector or agent must never bypass Menu by querying or mutating `menu`, `menu_elements` or related tables directly.

## PHP callback elements

Historical PHP-function elements are deliberately constrained.

When `allow_php_elements` is disabled, they are omitted from the resolved tree.

When enabled, a PHP-function element may be represented with:

```text
resolved = false
url = "#"
```

The callback itself is not exposed as an arbitrary executable capability through this contract. Future agent/service integrations must not treat this representation as permission to execute arbitrary PHP callbacks.

## Destination availability

The contract represents what is usable **now**, while stored Menu data remains non-destructive.

For example, if a plugin or Static Page disappears, Menu may keep the stored element but omit it from the resolved tree. If the destination becomes available again, the element may reappear without data recreation.

This separates persistent menu definitions from the public navigation representation.

## Consumers

Appropriate consumers include:

- Geeklog themes such as Eclipse;
- future contextual-navigation features;
- Hub or other plugins requiring permission-aware navigation structure;
- future Connector/Agent read capabilities;
- a future versioned JSON/headless representation built on the same resolver.

Consumers should depend on this contract rather than Menu SQL schema, legacy HTML renderers or theme-specific markup.

## Testing requirements

Changes to the resolved-tree implementation should keep contract tests for at least:

- contract version reporting;
- required v1 fields;
- nested children;
- inactive menu filtering;
- inactive element filtering;
- denied element access filtering;
- group restriction filtering;
- permission-aware topic/static-page collection paths;
- unavailable destination filtering;
- plain-text labels;
- unknown/future additive fields remaining safe for consumers.

## Guiding principle

> Menu owns authorization and navigation resolution. Consumers receive only the tree that the current Geeklog visitor is allowed to see.