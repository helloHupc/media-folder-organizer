# Architecture and Design Notes

This document describes the architecture, data model, integration points, and design constraints of Media Folder Organizer.

## Project Goals

- Organize WordPress attachments without moving physical files.
- Support unlimited folder depth and explicit sibling ordering.
- Integrate with native Media Library and editor workflows.
- Avoid custom database tables and external services.
- Preserve standard WordPress permissions, nonces, and attachment APIs.

## Plugin Structure

```text
media-folder-organizer.php
includes/
  class-mfo-plugin.php
  class-mfo-taxonomy.php
  class-mfo-rest-controller.php
  class-mfo-admin.php
assets/
  js/admin.js
  css/admin.css
tests/
  wordpress-smoke.php
uninstall.php
```

### Bootstrap

`media-folder-organizer.php` defines plugin constants, loads classes, registers activation behavior, and starts the singleton plugin instance.

### Taxonomy and Domain Logic

`MFO_Taxonomy` owns folder storage and domain rules:

- Registers `mfo_media_folder` as a private hierarchical taxonomy for attachments.
- Builds ordered trees and flattened folder lists.
- Aggregates direct and descendant attachment counts.
- Creates, renames, reorders, reparents, and recursively deletes folders.
- Enforces one folder relationship per attachment.
- Rejects cycles, incomplete reorder payloads, invalid parents, and duplicate sibling names.

Sibling order is stored in term metadata under `_mfo_order`.

### REST Controller

`MFO_REST_Controller` exposes authenticated routes under `/mfo/v1` for:

- Reading the folder tree.
- Creating, updating, deleting, and reordering folders.
- Assigning attachments to a folder or to Uncategorized.

All mutation routes require `upload_files` and a valid WordPress REST nonce.

### Administration Integration

`MFO_Admin` connects the domain layer to WordPress:

- Loads assets on Media Library and post-editor screens.
- Adds folder filters to media queries and list views.
- Assigns new uploads to a selected folder.
- Adds folder information to attachment data and edit fields.
- Provides localized configuration and interface strings to JavaScript.

### Browser Interface

`assets/js/admin.js` implements:

- The folder sidebar and tree rendering.
- Custom create, rename, and delete dialogs.
- Folder sorting and parent changes.
- Attachment dragging and multi-selection moves.
- Media Library and media-modal filtering.
- Upload destination propagation to WordPress uploaders.
- REST updates and refreshed counts.

`assets/css/admin.css` contains sidebar, tree, drop-target, dialog, notification, responsive, and media-toolbar styles.

## Data Model

Folders are WordPress terms in `mfo_media_folder`.

| Concern | WordPress storage |
| --- | --- |
| Folder name and slug | `wp_terms` |
| Parent-child hierarchy | `wp_term_taxonomy` |
| Attachment assignment | `wp_term_relationships` |
| Sibling order | `wp_termmeta` / `_mfo_order` |
| Installed version | `wp_options` / `mfo_version` |

No custom table is created. Table prefixes are resolved by WordPress.

## Folder Semantics

- `-1` represents All media and is never a valid assignment destination.
- `0` represents Uncategorized and removes the folder relationship.
- Positive integers are taxonomy term IDs.
- Each attachment has zero or one `mfo_media_folder` relationship.
- Filtering a folder includes its descendants.
- Deleting a folder recursively removes descendants; attachments remain and become uncategorized.

## Media Query Integration

WordPress strips unknown fields from media Ajax queries before running `ajax_query_attachments_args`. The plugin therefore reads `mfo_folder` from the original `query` request and then adds a taxonomy query.

For Uncategorized, the taxonomy query uses `NOT EXISTS`. For a real folder, it queries the term ID with `include_children` enabled. All media adds no taxonomy condition.

List mode uses `pre_get_posts` with the same folder semantics.

## Upload Integration

The selected folder ID is added to WordPress Plupload multipart parameters. After WordPress creates an attachment, the `add_attachment` hook assigns it to that folder.

The plugin works with virtual relationships only and never changes upload paths.

## Drag-and-Drop Design

Folder rows accept both folder and attachment drags through one jQuery UI droppable handler. Keeping these behaviors in one handler is important: registering two droppable instances on the same element would cause the later configuration to overwrite the earlier one.

Folder hierarchy updates are serialized as a complete tree and validated server-side before terms are updated. The server rejects cycles and partial trees even if client-side drag validation fails.

## Security Boundaries

- Administration assets load only for users who can upload files.
- REST mutations require `upload_files`.
- Attachment assignment checks edit permission for every attachment.
- REST requests use a WordPress nonce.
- Input is sanitized and rendered values are escaped.
- There are no anonymous mutation endpoints or external data transfers.

## Uninstall Behavior

`uninstall.php` removes plugin terms, relationships, term metadata, and the version option. It does not delete attachments or physical media files.

## Testing

The integration smoke test covers:

- Nested folder creation.
- Duplicate-name and cycle rejection.
- Attachment assignment and Uncategorized counts.
- Aggregate folder counts.
- Parent-folder queries including descendants.
- Media Ajax folder filtering.
- Hierarchy reorder and recursive deletion.

The test is intentionally destructive and runs only when `MFO_ALLOW_DESTRUCTIVE_TESTS=1`. It also refuses non-local WordPress site URLs.

## Prior-Art Review

The initial design reviewed public WordPress media-folder patterns, including private hierarchical taxonomies, term ordering, media-query filters, Backbone toolbar filters, Plupload parameters, and recursive deletion.

No source code, brand asset, premium-only feature, or vendor library from another media-folder plugin is bundled in this repository. This project is independently implemented and released under the MIT License.
