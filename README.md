# Media Folder Organizer

Media Folder Organizer is an open-source WordPress plugin for organizing Media Library attachments in unlimited virtual folders and subfolders.

It integrates with the native Media Library, media picker, Featured Image dialog, and WordPress uploader. Folder operations never move physical files or change attachment URLs.

> 中文安装与使用说明：[INSTALLATION-ZH.md](INSTALLATION-ZH.md)

## Features

- Unlimited folders and nested subfolders.
- Custom dialogs for creating, renaming, and deleting folders.
- Drag-and-drop folder ordering and hierarchy changes.
- Multi-select media assignment by dragging or using the move button.
- Folder filters in Media Library grid and list modes.
- Folder filters in Insert Media and Featured Image dialogs.
- Upload directly into a selected folder.
- Parent-folder filters include all descendant folders.
- Native WordPress storage with no custom tables or external services.
- Safe virtual organization: media paths, URLs, and post content remain unchanged.

## Requirements

- WordPress 6.2 or later.
- PHP 7.4 or later.
- A WordPress user with the `upload_files` capability.
- JavaScript enabled in the administration browser.

No Composer, Node.js build, external API, or separate database is required to install the plugin.

## Installation

### Install a release ZIP

1. Download a ZIP from [GitHub Releases](https://github.com/helloHupc/media-folder-organizer/releases).
2. In WordPress, open **Plugins > Add New Plugin > Upload Plugin**.
3. Upload the ZIP and activate **Media Folder Organizer**.
4. Open **Media > Library**.

### Install from source

Clone the repository into the WordPress plugins directory:

```bash
cd wp-content/plugins
git clone git@github.com:helloHupc/media-folder-organizer.git
```

Then activate the plugin in WordPress or with WP-CLI:

```bash
wp plugin activate media-folder-organizer
```

See [INSTALLATION.md](INSTALLATION.md) for deployment, upgrade, troubleshooting, and uninstall details.

## Usage

### Manage folders

Open **Media > Library** and use the folder sidebar:

- Use the header plus button to create a top-level folder.
- Use the plus button on a folder row to create a subfolder.
- Use the edit and delete actions to manage a folder.
- Drag a folder onto another folder to make it a child.
- Drag a folder to the top-level drop area to remove its parent.

### Organize media

- Select one or more attachments and drag them onto a folder.
- Alternatively, select a destination folder and choose **Move selected media here**.
- Choose **Uncategorized** to find attachments without a folder.
- Choose **All media** to remove the folder filter.

### Use folders in editor dialogs

The folder selector is available in WordPress media dialogs, including:

- Insert Media.
- Image and Gallery blocks.
- Featured Image selection.
- Other editor features that use the native WordPress media modal.

## Data Model

The plugin registers the private hierarchical taxonomy:

```text
mfo_media_folder
```

WordPress stores folder data in its standard tables:

- Terms and names in `wp_terms`.
- Hierarchy in `wp_term_taxonomy`.
- Attachment assignments in `wp_term_relationships`.
- Sibling order in `wp_termmeta` under `_mfo_order`.

Each attachment can have at most one folder assignment. The plugin creates no custom database table.

For implementation details, see [REFERENCE-ANALYSIS.md](REFERENCE-ANALYSIS.md).

## Development

The plugin is intentionally build-free. Edit the PHP, JavaScript, and CSS files directly.

Run basic checks before submitting a change:

```bash
node --check assets/js/admin.js
find . -name '*.php' -print0 | xargs -0 -n1 php -l
```

An integration smoke test is provided at `tests/wordpress-smoke.php`. It is destructive and refuses to run against a non-local WordPress installation:

```bash
MFO_ALLOW_DESTRUCTIVE_TESTS=1 \
MFO_WP_ROOT=/path/to/local/wordpress \
php tests/wordpress-smoke.php
```

## Contributing

Issues and pull requests are welcome. Read [CONTRIBUTING.md](CONTRIBUTING.md) before submitting changes.

- Bug reports: [GitHub Issues](https://github.com/helloHupc/media-folder-organizer/issues)
- Security reports: see [SECURITY.md](SECURITY.md)
- Release history: [CHANGELOG.md](CHANGELOG.md)

## Acknowledgements

学AI，上L站

 [linux.do](https://linux.do) 



## License

Media Folder Organizer is released under the [MIT License](LICENSE).

Copyright © 2026 hupc.
