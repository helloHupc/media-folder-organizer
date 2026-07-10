# Reference Plugin Analysis

This implementation was designed after reading the official WordPress.org pages and official distributed source packages for:

- FileBird 6.5.5
- Folders 3.0.4
- The Folders WordPress.org SVN trunk

## FileBird Patterns Reviewed

- Custom folder and attachment-relation tables.
- Recursive tree construction from parent IDs and explicit order values.
- REST folder creation, rename, deletion, assignment, and drag-order endpoints.
- `ajax_query_attachments_args` and attachment query clause filtering.
- `add_attachment` assignment from an upload request parameter.
- Media Library and media modal integration.

## Folders Patterns Reviewed

- Private hierarchical taxonomies for virtual folders.
- Term metadata for custom sibling order.
- `ajax_query_attachments_args` and `pre_get_posts` handling for assigned and unassigned media.
- Backbone media toolbar filters.
- Plupload multipart parameters for upload-to-folder behavior.
- Recursive deletion and WordPress term APIs.

## Decisions In This Plugin

- Use a private hierarchical WordPress taxonomy instead of custom database tables.
- Store only sibling order in term metadata.
- Keep one folder assignment per attachment.
- Include child folders when filtering a parent.
- Use authenticated custom REST routes for folder operations.
- Keep all folders virtual so file paths and URLs are unchanged.

No source code, brand asset, premium-only feature, or vendor library from either reference plugin is bundled in this plugin.
