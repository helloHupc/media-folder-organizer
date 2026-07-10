# Contributing

Thank you for considering a contribution to Media Folder Organizer.

## Before You Start

- Search existing issues and pull requests before opening a duplicate.
- Use an issue to discuss large features, data-model changes, or breaking behavior first.
- Keep changes focused. Unrelated refactors should be submitted separately.

## Local Setup

1. Fork and clone the repository.
2. Place the repository at `wp-content/plugins/media-folder-organizer` in a local WordPress installation.
3. Activate the plugin.
4. Use a test account with `upload_files`.
5. Test with several images and at least three folder levels.

The project has no build step and no runtime dependency outside WordPress.

## Coding Guidelines

- Follow WordPress PHP and JavaScript conventions used in the existing codebase.
- Sanitize external input and escape rendered output.
- Check capabilities before changing folders or attachments.
- Use WordPress APIs instead of direct database writes unless there is a documented performance reason.
- Preserve media paths and URLs; folders must remain virtual.
- Keep the plugin compatible with the minimum supported WordPress and PHP versions.
- Add or update documentation when behavior changes.

## Validation

Run syntax checks:

```bash
node --check assets/js/admin.js
find . -name '*.php' -print0 | xargs -0 -n1 php -l
```

For changes involving storage, hierarchy, filtering, or assignment, run the local WordPress smoke test:

```bash
MFO_ALLOW_DESTRUCTIVE_TESTS=1 \
MFO_WP_ROOT=/path/to/local/wordpress \
php tests/wordpress-smoke.php
```

The smoke test deletes test folder terms and attachments. Never point it at a production site.

UI changes should be verified in:

- Media Library grid mode.
- Media Library list mode.
- Insert Media dialog.
- Featured Image dialog.
- Narrow and mobile-width administration layouts.

## Pull Requests

A good pull request includes:

- A clear explanation of the problem and solution.
- The user-visible impact.
- Screenshots for interface changes.
- The checks and WordPress/PHP versions used.
- Updated changelog and documentation when appropriate.

By contributing, you agree that your contribution is licensed under the project's MIT License.
