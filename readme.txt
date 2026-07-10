=== Media Folder Organizer ===
Contributors: media-folder-organizer
Tags: media, folders, media library, image organizer, attachment
Requires at least: 6.2
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Organize media attachments in unlimited virtual folders without changing file paths or URLs.

== Description ==

Media Folder Organizer adds a hierarchical virtual folder tree to the WordPress Media Library.

Features:

* Unlimited folders and subfolders.
* Rename and delete folders.
* Drag folders to reorder them or change their parent.
* Drag media items into folders.
* Filter the Media Library in grid and list modes.
* Filter media while inserting images into posts or choosing featured images.
* Choose a destination folder before uploading.
* Keep original upload paths and attachment URLs unchanged.

The plugin stores folders as a private hierarchical WordPress taxonomy and stores ordering in term metadata. It does not require an external database or service.

== Installation ==

1. Upload the `media-folder-organizer` directory to `/wp-content/plugins/`, or install the ZIP from Plugins > Add New > Upload Plugin.
2. Activate Media Folder Organizer.
3. Open Media > Library.
4. Create folders from the folder sidebar.

See `INSTALLATION-ZH.md` for the complete Chinese installation and integration guide, including prerequisites and how to meet them.

See `INSTALLATION.md` for the English installation, usage, troubleshooting, backup, and removal guide.

== Frequently Asked Questions ==

= Are physical files moved? =

No. Folders are virtual. Existing file paths and URLs do not change.

= What happens when a folder is deleted? =

Its subfolders are deleted and media previously assigned to those folders becomes uncategorized. Media files are not deleted.

= Does this need a third-party database? =

No. It uses the existing WordPress taxonomy and term metadata tables.

== Changelog ==

= 1.0.3 =

* Replace browser prompts and confirms with accessible custom dialogs for creating, renaming, and deleting folders.
* Improve folder drag-and-drop so top-level folders can be dropped onto another folder to become subfolders.

= 1.0.2 =

* Fix the All media counter on installations where the attachment status count API returns no usable total.
* Read the media folder from the original Ajax request so selecting a folder actually filters attachments.

= 1.0.1 =

* Fix the media folder filter layout in narrower media modals such as the featured image picker.
* Give the folder filter a unique element ID to avoid conflicts with WordPress core filters.

= 1.0.0 =

* Initial release.
