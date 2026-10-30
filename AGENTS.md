# Project instructions

## Releases
- Always include a changelog in every GitHub release.
- Update CHANGELOG.md with the release version, date, and an accurate summary of the changes. Use that version's entry in the GitHub release notes.
- Match the release tag and ZIP version to the plugin header and PCVS_VERSION constant.
- Attach an installable plugin ZIP with a single top-level plugin directory. Exclude Git metadata, credentials, and generated release archives.
- Run the existing relevant checks and inspect the ZIP contents before publishing.
