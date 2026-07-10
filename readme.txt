=== Media Folder Organizer ===
Contributors: hupc
Tags: media, folders, media library, image organizer, attachment
Requires at least: 6.2
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.3
License: MIT
License URI: https://opensource.org/license/mit

Organize WordPress media attachments in unlimited virtual folders without changing file paths or URLs.

== Description ==

Media Folder Organizer adds a hierarchical virtual folder system to the WordPress Media Library.

Key features:

* Create unlimited folders and nested subfolders.
* Rename, delete, reorder, and reparent folders with a custom interface.
* Drag one or more media items into a folder.
* Filter the Media Library in grid and list modes.
* Filter media in the Insert Media and Featured Image dialogs.
* Select a destination folder before uploading.
* Keep uploaded files, attachment URLs, and existing post content unchanged.
* Store data with native WordPress taxonomy and term metadata APIs.

Folders are virtual. The plugin does not move or rename files in `wp-content/uploads` and does not require an external service or custom database table.

The source code is maintained openly at:

https://github.com/helloHupc/media-folder-organizer

== Installation ==

1. Download a release ZIP from the GitHub repository, or create a ZIP whose top-level directory is `media-folder-organizer`.
2. In WordPress, open Plugins > Add New Plugin > Upload Plugin.
3. Upload the ZIP, install it, and activate Media Folder Organizer.
4. Open Media > Library and use the folder sidebar.

Detailed guides are available in `INSTALLATION.md` and `INSTALLATION-ZH.md` in the project repository.

== Frequently Asked Questions ==

= Are physical media files moved? =

No. Folders are virtual taxonomy terms. Existing paths and attachment URLs remain unchanged.

= Can an attachment belong to multiple folders? =

No. Each attachment belongs to at most one Media Folder Organizer folder. Moving it replaces the previous assignment.

= Does selecting a parent folder include its subfolders? =

Yes. Folder filters include attachments assigned to descendant folders.

= What happens when a folder is deleted? =

The folder and its descendants are removed. Their media attachments remain in WordPress and become uncategorized.

= Does the plugin create custom database tables? =

No. It uses a private hierarchical taxonomy, attachment relationships, and term metadata in the existing WordPress database.

= Where can I report a bug or request a feature? =

Use GitHub Issues:

https://github.com/helloHupc/media-folder-organizer/issues

== Changelog ==

= 1.0.3 =

* Added accessible custom dialogs for creating, renaming, and deleting folders.
* Fixed folder drag-and-drop so top-level folders can become subfolders.

= 1.0.2 =

* Fixed the All media counter.
* Fixed media-modal filtering by reading the folder from the original Ajax request.

= 1.0.1 =

* Fixed folder-filter layout in narrow media dialogs such as the Featured Image picker.
* Added a unique element ID for the folder filter.

= 1.0.0 =

* Initial open-source release.
