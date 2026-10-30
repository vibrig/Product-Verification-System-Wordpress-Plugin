# Project instructions

## Releases
- Always include a changelog in every GitHub release.
- Update CHANGELOG.md with the release version, date, and an accurate summary of the changes. Use that version's entry in the GitHub release notes.
- Match the release tag and ZIP version to the plugin header and PCVS_VERSION constant.
- Name each release and ZIP using Version - Name, for example: 1.9.1 - Product Verification System (with .zip for the archive).
- ZIP the files and subfolders inside the project directly at the archive root, without an enclosing plugin directory. Exclude Git metadata, agent instructions, credentials, and generated release archives.
- Run the existing relevant checks and inspect the ZIP contents before publishing.
