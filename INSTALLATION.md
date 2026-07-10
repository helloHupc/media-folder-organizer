# Installation and Operations Guide

This guide covers installing, using, upgrading, troubleshooting, and removing the open-source Media Folder Organizer WordPress plugin.

Chinese guide: [INSTALLATION-ZH.md](INSTALLATION-ZH.md)

## Requirements

- WordPress 6.2 or later.
- PHP 7.4 or later.
- A user with the `upload_files` capability.
- Permission to install plugins or write to `wp-content/plugins`.
- JavaScript enabled in the administration browser.

The plugin requires no Composer packages, Node.js build, external API, cloud service, Redis instance, or custom database.

## Back Up Production Sites

Before installing or upgrading on a production site, back up:

- The WordPress database.
- The `wp-content` directory.

Media Folder Organizer does not create custom tables, but folder definitions and attachment relationships are stored in the existing WordPress taxonomy tables.

## Install a GitHub Release

1. Download a release ZIP from [GitHub Releases](https://github.com/helloHupc/media-folder-organizer/releases).
2. Sign in to WordPress as an administrator.
3. Open **Plugins > Add New Plugin > Upload Plugin**.
4. Upload the ZIP.
5. Install and activate **Media Folder Organizer**.
6. Open **Media > Library** and confirm the folder sidebar is visible.

The installed directory must be:

```text
wp-content/plugins/media-folder-organizer/
```

Avoid a double nested path such as `media-folder-organizer/media-folder-organizer`.

## Install from Source

Clone the repository into the plugins directory:

```bash
cd /path/to/wordpress/wp-content/plugins
git clone git@github.com:helloHupc/media-folder-organizer.git
```

Activate it with WP-CLI:

```bash
wp plugin activate media-folder-organizer
```

Or activate it from **Plugins > Installed Plugins**.

## Package a ZIP from Source

Run this command from the directory containing the repository:

```bash
zip -r media-folder-organizer.zip media-folder-organizer \
  -x 'media-folder-organizer/.git/*'
```

The ZIP must contain the `media-folder-organizer` directory at its top level.

## First-Run Verification

Use test media rather than important production assets:

1. Create a top-level folder.
2. Create a child folder.
3. Rename the child folder.
4. Drag a top-level folder onto another folder and reload the page.
5. Move an image into a folder.
6. Filter the folder in Media Library grid mode.
7. Filter it in list mode.
8. Filter it in the Insert Media dialog.
9. Filter it in the Featured Image dialog.
10. Upload an image into a selected folder.
11. Delete a test folder and confirm its media remains as uncategorized.

## Folder Management

Open **Media > Library**:

- Create a root folder with the plus button in the sidebar header.
- Create a subfolder with the plus button on a folder row.
- Rename or delete a folder with its row actions.
- Drag folders to reorder siblings.
- Drop a folder onto another folder to change its parent.
- Drop a folder on **Drop here to move to the top level** to make it a root folder.

Deleting a folder also removes its descendants, but never deletes media attachments or files. Affected attachments become uncategorized.

## Assign and Filter Media

In grid mode, select one or more attachments and drag them onto a folder. You can also select a folder and use **Move selected media here**.

In list mode, use row checkboxes, select the destination folder, and use the move button.

Each attachment can belong to at most one folder. Moving it replaces its previous folder relationship.

Selecting a parent folder includes media assigned to any descendant folder.

## Upload into a Folder

Select **Upload to folder** before starting an upload in:

- **Media > Add New Media File**.
- The Upload Files tab of a native WordPress media dialog.

The selected folder is sent with the WordPress upload request and assigned after WordPress creates the attachment.

## Storage and Data Behavior

The plugin registers the private taxonomy `mfo_media_folder` and uses:

- `wp_terms` for folder names.
- `wp_term_taxonomy` for hierarchy.
- `wp_term_relationships` for attachment assignments.
- `wp_termmeta` with `_mfo_order` for sibling ordering.
- `wp_options` for the plugin version.

The actual table prefix may differ from `wp_`.

The plugin does not move files, rename uploads, change attachment URLs, or rewrite existing post content.

## Permissions and REST API

Folder operations require `upload_files`. Attachment assignment also requires permission to edit the selected attachment.

Authenticated operations use the WordPress REST API under:

```text
/wp-json/mfo/v1/
```

Requests require a valid WordPress REST nonce. Security plugins and reverse proxies must allow authenticated administrators to access this path.

## Cache and Optimization Plugins

WordPress administration pages should not normally be page-cached. If an optimization plugin combines or delays admin assets, exclude:

```text
media-folder-organizer/assets/js/admin.js
media-folder-organizer/assets/css/admin.css
```

After upgrading, clear admin optimization caches and perform a hard browser refresh.

## Upgrade

### Upgrade a ZIP installation

1. Back up the database and current plugin directory.
2. Upload the new release ZIP.
3. Choose WordPress's option to replace the installed version.
4. Do not delete the old plugin first; deletion runs the uninstall routine and removes folder data.
5. Clear caches and repeat the first-run verification checks.

### Upgrade a Git checkout

From the plugin directory:

```bash
git pull --ff-only
```

Review release notes before upgrading across major versions.

## Troubleshooting

### The sidebar is missing

- Confirm the plugin is active.
- Confirm the current user has `upload_files`.
- Open the administration Media Library, not a front-end page.
- Hard-refresh the browser and clear admin optimization caches.
- Check the browser console for errors from other media plugins.

### Folder filtering shows the wrong media

- Confirm the latest plugin version is active.
- Reload the Media Library once after upgrading.
- Confirm the attachment is assigned to the expected folder.
- Temporarily disable other media-folder or taxonomy-query plugins.

### Folder drag-and-drop does not work

- Drag the folder row onto the target folder until it is highlighted.
- Use the top-level drop target to remove a parent.
- Confirm JavaScript is enabled.
- Test without browser extensions that alter pointer behavior.
- Exclude the plugin assets from admin optimization.

### REST requests return 401 or 403

- Sign out and back in to refresh the REST nonce.
- Confirm the user has `upload_files`.
- Confirm `/wp-json/` has not been disabled.
- Review WAF and security-plugin rules for `/wp-json/mfo/v1/`.

### Uploads remain uncategorized

- Select the folder before the upload begins.
- Confirm another plugin is not replacing the native WordPress uploader.
- Inspect the upload request for the `mfo_folder` parameter.

## Deactivation and Uninstall

Deactivation keeps all folder data and relationships.

Deleting the plugin from WordPress runs `uninstall.php` and removes:

- All `mfo_media_folder` terms.
- Attachment-to-folder relationships.
- `_mfo_order` metadata.
- The `mfo_version` option.

It never deletes attachment posts, physical files, or media URLs. Folder structures cannot be restored after uninstall without a database backup.

## Support

- Bugs and feature requests: [GitHub Issues](https://github.com/helloHupc/media-folder-organizer/issues)
- Contribution process: [CONTRIBUTING.md](CONTRIBUTING.md)
- Security reports: [SECURITY.md](SECURITY.md)
