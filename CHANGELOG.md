# Changelog

All notable changes to Media Folder Organizer are documented here.

The project follows semantic versioning where practical.

## [1.0.3] - 2026-07-10

### Added

- Accessible custom dialogs for creating, renaming, and deleting folders.
- Responsive dialog styles, validation feedback, keyboard handling, and destructive-action styling.

### Fixed

- Folder and attachment droppable handlers no longer overwrite each other.
- Top-level folders can be dropped onto another folder to become subfolders.

## [1.0.2] - 2026-07-10

### Fixed

- The All media counter now uses an attachment query that matches Media Library visibility.
- Media-modal folder filtering now reads `mfo_folder` from the original Ajax request before WordPress discards custom query fields.

## [1.0.1] - 2026-07-10

### Fixed

- Folder filters remain visible in narrow media dialogs such as Featured Image selection.
- The folder filter uses a unique DOM ID and an accessible label.

## [1.0.0] - 2026-07-10

### Added

- Initial open-source release.
- Hierarchical virtual media folders.
- Media Library, editor-modal, Featured Image, and upload integration.
- REST-based folder management and attachment assignment.

[1.0.3]: https://github.com/helloHupc/media-folder-organizer/releases/tag/v1.0.3
[1.0.2]: https://github.com/helloHupc/media-folder-organizer/releases/tag/v1.0.2
[1.0.1]: https://github.com/helloHupc/media-folder-organizer/releases/tag/v1.0.1
[1.0.0]: https://github.com/helloHupc/media-folder-organizer/releases/tag/v1.0.0
