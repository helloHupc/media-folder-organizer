# Media Folder Organizer

Chinese installation and integration guide: `INSTALLATION-ZH.md`

## 1. Requirements

- WordPress 6.2 or newer.
- PHP 7.4 or newer.
- A user account with the WordPress `upload_files` capability.
- JavaScript enabled in the browser.
- Permission to install plugins on the WordPress site.

No Node.js build step, Composer dependency, external API, external database, Redis, or cloud storage connection is required. The plugin uses the site's existing WordPress database.

## 2. Install From The Included Directory

1. Copy the complete `media-folder-organizer` directory to:

   ```text
   wp-content/plugins/media-folder-organizer
   ```

2. Sign in to WordPress as an administrator.
3. Open **Plugins > Installed Plugins**.
4. Activate **Media Folder Organizer**.
5. Open **Media > Library**.

## 3. Install From A ZIP

Create a ZIP whose top-level directory is `media-folder-organizer`, then:

1. Open **Plugins > Add New Plugin > Upload Plugin**.
2. Select `media-folder-organizer.zip`.
3. Choose **Install Now**.
4. Choose **Activate Plugin**.
5. Open **Media > Library**.

The ZIP can be created from a shell with:

```bash
zip -r media-folder-organizer.zip media-folder-organizer
```

## 4. Database Storage

The plugin does not create custom tables or connect to a third-party database.

It registers the private hierarchical taxonomy:

```text
mfo_media_folder
```

WordPress stores the data in its existing tables:

- `wp_terms`: folder names and slugs.
- `wp_term_taxonomy`: parent-child folder hierarchy.
- `wp_term_relationships`: attachment-to-folder assignments.
- `wp_termmeta`: sibling ordering under the `_mfo_order` key.
- `wp_options`: plugin version only.

The actual table prefix can differ from `wp_`.

Before production installation, use the hosting control panel or database tooling to back up the WordPress database. No schema migration or manual SQL is required.

## 5. Create And Manage Folders

1. Open **Media > Library**.
2. Use the plus icon in the folder sidebar to create a top-level folder.
3. Use the plus icon on a folder row to create a subfolder.
4. Use the edit icon to rename a folder.
5. Drag a folder row to reorder it.
6. Drag a folder onto another folder to make it a child.
7. Drag a folder to the **Drop here to move to the top level** target to remove its parent.
8. Use the trash icon to delete a folder and its descendants.

Deleting a folder never deletes media files. Attachments that belonged to deleted folders become uncategorized.

## 6. Assign Existing Media

Grid mode:

1. Select one or more media items.
2. Drag a selected media item onto a folder, or select a folder and choose **Move selected media here**.

List mode:

1. Select media using the row checkboxes.
2. Select a folder in the sidebar.
3. Choose **Move selected media here**.

An attachment belongs to one Media Folder Organizer folder at a time. Moving it replaces its previous assignment.

## 7. Filter Media

### Media Library

- Select **All media**, **Uncategorized**, or any folder in the sidebar.
- In list mode, the standard filter bar also contains a folder selector.
- Selecting a parent folder includes media assigned to its descendants.

### Post And Page Editor

1. Edit a post or page.
2. Insert an Image, Gallery, Cover, Media & Text, or other block that opens the WordPress media modal.
3. Use the media folder selector in the modal toolbar.

The same selector is available when choosing or replacing a featured image because both workflows use the WordPress media modal.

## 8. Upload Into A Folder

1. Open **Media > Add New Media File**, or open the **Upload files** tab in a media modal.
2. Choose a value in **Upload to folder**.
3. Upload the files.

The folder selection is sent with the WordPress Plupload request and assigned after WordPress creates the attachment.

## 9. File And URL Behavior

Folders are virtual. The plugin does not:

- Move files inside `wp-content/uploads`.
- Rename uploaded files.
- Change attachment URLs.
- Rewrite existing post content.

Disabling the plugin leaves media files and URLs untouched.

## 10. Permissions

Folder management requires `upload_files`. Attachment assignment also requires permission to edit each attachment.

Administrators, editors, and authors normally have `upload_files`, but role customization plugins can change this. If the folder interface is missing, verify the affected role capabilities.

## 11. Cache And Security Plugins

No special cache integration is required. If an optimization plugin combines or delays WordPress admin scripts, exclude:

```text
media-folder-organizer/assets/js/admin.js
media-folder-organizer/assets/css/admin.css
```

REST requests use the logged-in WordPress REST nonce and require `upload_files`. Confirm that security plugins do not block authenticated requests under:

```text
/wp-json/mfo/v1/
```

## 12. Troubleshooting

### The folder sidebar is not visible

- Confirm the plugin is active.
- Confirm the user has `upload_files`.
- Open **Media > Library**, not a front-end media page.
- Clear browser and admin optimization caches.
- Check the browser console for JavaScript errors from another admin plugin.

### Folder filtering returns no media

- Refresh the page once after activation.
- Confirm the attachment was assigned to the expected folder.
- Test with other media-library plugins disabled to identify taxonomy-query conflicts.

### Uploads are uncategorized

- Select the folder before adding files.
- Confirm the upload request contains `mfo_folder`.
- Temporarily disable admin script delay or minification.
- Confirm another plugin is not replacing the WordPress uploader.

### REST requests return 401 or 403

- Sign out and back in to refresh nonces.
- Confirm the user has `upload_files`.
- Confirm `/wp-json/` is available to authenticated administrators.
- Review security plugin rules for `/wp-json/mfo/v1/`.

## 13. Deactivation And Uninstall

Deactivation keeps folder data so it is available after reactivation.

Deleting the plugin from **Plugins > Installed Plugins** runs `uninstall.php` and deletes:

- All `mfo_media_folder` terms.
- Their attachment relationships.
- Their `_mfo_order` metadata.
- The `mfo_version` option.

Media attachments and physical files are never deleted by uninstall.

Back up the WordPress database before uninstalling if folder assignments may be needed later.

## 14. Production Verification Checklist

After installation, verify:

1. Create at least three nesting levels.
2. Rename a folder.
3. Reorder siblings and reload the page.
4. Move a folder under another folder and reload.
5. Assign an existing image by dragging it onto a folder.
6. Filter that folder in grid and list modes.
7. Filter the same folder in an Insert Media modal.
8. Filter the same folder in the Featured Image modal.
9. Upload a new image into a selected folder.
10. Delete a test folder and confirm its images remain in the Media Library as uncategorized.
