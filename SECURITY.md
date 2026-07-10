# Security Policy

## Supported Versions

Security fixes are provided for the latest released version of Media Folder Organizer.

| Version | Supported |
| --- | --- |
| 1.0.x | Yes |
| Earlier versions | No |

## Reporting a Vulnerability

Please do not publish a suspected vulnerability in a public GitHub issue before it has been reviewed.

Report security concerns privately to:

```text
985708024@qq.com
```

Include:

- The affected version.
- WordPress and PHP versions.
- Reproduction steps or a proof of concept.
- The expected security impact.
- Any suggested mitigation.

You should receive an acknowledgement within seven days. Confirmed issues will be investigated, fixed, and disclosed responsibly based on severity.

## Security Model

The plugin relies on WordPress authentication and capabilities:

- Folder management requires `upload_files`.
- Attachment changes require permission to edit the attachment.
- REST mutations require a valid WordPress REST nonce.
- All external input must be sanitized and all rendered output escaped.

The plugin does not provide anonymous write endpoints and does not transmit media or folder data to an external service.
