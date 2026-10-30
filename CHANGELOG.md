# Changelog

## 1.9.2 - 2026-10-31

- Split Settings into General Settings and Field Settings, with independent saves that preserve the other section's configuration.
- Added GitHub release checks, installed-version and last-check information, and Check for updates / Update now controls under General Settings.
- Integrated newer releases with WordPress update counts, the Plugins update row, release details, and administrator update notices.
- Added optional GitHub token configuration for private releases. Saved tokens remain hidden and are not forwarded to redirected asset-storage hosts.
- Added ZIP validation and update-folder handling for flat release archives, preserving the existing installed plugin directory.
- Added PHP regression coverage for update selection, caching, settings isolation, download handling, and archive preparation.

## 1.9.1 - 2026-10-31

First packaged GitHub release of Product Verification System. Earlier versions are not documented in this repository.

### Included features
- Product, service, and document verification using unique codes and the `[product_verify]` shortcode.
- Projects and clients, with automatic client selection from the selected project.
- Configurable verification messages, form text, and visible result fields.
- Product images, certificates, invoices, and manufacturing and expiry details.
- Optional invalid-code email alerts and verification history with CSV export, retention settings, and optional IP logging.
- WordPress administration screens and an in-plugin usage guide.
