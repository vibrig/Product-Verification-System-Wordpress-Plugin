<div align="center">

# 🛡️ Product Verification System

**Give every product and document a place to be verified.**

A WordPress plugin by **[Vibrig](https://vibrig.com/)** for checking unique codes, displaying item details, and organizing products and services by project and client.

![WordPress plugin](https://img.shields.io/badge/WordPress-Plugin-21759B?logo=wordpress&logoColor=white)
![Version 1.9.2](https://img.shields.io/badge/version-1.9.2-2563EB)
![License GPL v2 or later](https://img.shields.io/badge/license-GPL--2.0%2B-16A34A)

[Download](https://github.com/vibrig/Product-Verification-System-Wordpress-Plugin/releases/latest) · [Quick start](#quick-start) · [Features](#features) · [Changelog](CHANGELOG.md) · [Report an issue](https://github.com/vibrig/Product-Verification-System-Wordpress-Plugin/issues)

</div>

---

## A simple code. A clear result.

Let visitors enter a code on your website and check it against your published records. When a match is found, show the details you choose: the product name, project, client, validity status, image, certificate, or invoice.

Manage records in WordPress, place one shortcode on a page, and give customers a straightforward way to look up the information behind their code.

> **How it works:** Create a record → add verification codes → publish your verification page → visitors enter a code and see the result.

## Features

| | What you can do |
| :--- | :--- |
| 🔎 **Unique-code verification** | Assign multiple codes to an item, enter them manually or generate random codes, and let visitors verify without signing in. |
| 📦 **Products & services** | Manage product and service records, with descriptions, batch or series numbers, manufacturing dates, and expiry dates. |
| 🗂️ **Projects & clients** | Group items under projects and automatically inherit the project's client, or select a client manually when no project client is assigned. |
| 🎨 **Customizable results** | Set genuine and invalid-code messages, edit the form heading and button text, and reorder, rename, or hide supported result fields. |
| ➕ **Custom fields** | Add your own text fields for details such as distributor names or other item-specific information. |
| 📎 **Images & documents** | Attach an item image, certificate, and bill or invoice, and choose whether they appear in verification results. |
| 📅 **Validity status** | Display Active, Expired, or N/A based on the record's expiry date. |
| ✉️ **Email alerts** | Optionally send invalid-code attempts to your chosen alert email address. |
| 📊 **Verification history** | Optionally record genuine and invalid attempts, review history in WordPress, export CSV files, or clear logs. |
| 📱 **Responsive form** | Display a styled verification form with AJAX results, keyboard focus styles, and live status announcements. |

## Quick start

### 1. Install the plugin

Download the plugin ZIP from the [latest GitHub release](https://github.com/vibrig/Product-Verification-System-Wordpress-Plugin/releases/latest).

The release ZIP contains the plugin files **directly at the archive root**. To install it manually:

1. Create a folder named `product-verification-system` inside `wp-content/plugins/`.
2. Extract the ZIP contents into that folder. The main file should be at:
   ```text
   wp-content/plugins/product-verification-system/product-code-verification-system.php
   ```
3. In WordPress, open **Plugins → Installed Plugins** and activate **Product Verification System**.

### 2. Add your first record

Open **Product Verification → Add New Product**.

- Enter the item title and choose **Product** or **Service**.
- Add the details you want customers to see.
- Optionally select a project and client, and attach an image, certificate, or invoice.
- Enter verification codes, or use **Generate Random Code**.
- Click **Publish**.

Use a different code for each item you want to identify uniquely.

### 3. Publish a verification page

Create a WordPress page, add a **Shortcode** block, and paste:

```text
[product_verify]
```

Publish the page. Visitors can now enter a code and receive a result without reloading the page.

The shortcode also works in posts and text widgets that process shortcodes.

### 4. Make it yours

Go to **Product Verification → Settings → General Settings** to customize:

- **Messages & alerts:** genuine and invalid messages, alert recipient, and email notifications.
- **Verification form:** heading, description, and button text.
- **Verification history:** logging, optional IP recording, and retention period.
- **Plugin updates:** release checks, a private-repository access token, and Update now when a newer stable version is available.

Use **Settings → Field Settings** for field order, labels, visibility, and custom fields. Each section saves independently.

An in-plugin guide is available under **Product Verification → How to Use**.

## Organize work with projects and clients

Keep related products and services together:

```text
Client
└── Project
    ├── Product
    ├── Product
    └── Service
```

Publish clients and projects to make them available in the item selectors. When a project has a client, its items use that client automatically. Updating the project's client updates the association used by its items.

If no project client is assigned, you can choose a client on the item itself. Verification results expose only the project and client names; client contact details and notes stay in the administration area.

## What visitors see

| Code entered | Result |
| :--- | :--- |
| Matches a published record | Your genuine message and the visible, populated fields for that record. |
| Does not match a published record | Your invalid-code message. |
| Matches a record with an expiry date | A successful match, with Active or Expired shown if the validity field is visible. |

Code matching is **case-sensitive**. A matching code can be verified more than once; verification does not consume or deactivate it.

Verification confirms that a code exists in your published records. The accuracy of the associated product or document details depends on the records you maintain.

## History & notifications

Verification history is **off by default**, and IP recording is also **off by default**. When history is enabled, the plugin records the attempted code, time, and genuine or invalid result.

- Choose a retention period from **1 to 365 days**; the default is **30 days**.
- History is limited to **5,000 entries**.
- Review, export, or clear history under **Product Verification → Verification Logs**.
- Invalid-code email alerts are **on by default** and use your configured alert address, falling back to the WordPress admin email.

Invalid-code alert emails include the attempted code, time, and visitor IP address, independently of the optional IP setting for stored verification logs.

## Plugin updates

Version 1.9.2 checks stable releases from **vibrig/Product-Verification-System-Wordpress-Plugin** on GitHub. New versions appear in WordPress's update count and Installed Plugins update row, with an administrator notice and an **Update now** control in **Settings → General Settings → Plugin Updates**.

- Use **Check for updates** to refresh immediately. Successful checks are cached for six hours; failed checks are cached for ten minutes. Scheduled checks run through WordPress's normal plugin-update checks.
- For the private repository, save a fine-grained GitHub token with **Contents: read** access to this repository. Public releases can be checked without a token.
- The token is stored in the WordPress options database without autoloading, is not displayed after saving, and is sent only to the fixed GitHub API endpoints. Leave the password field empty to keep it or use **Remove saved token** to remove it.
- **Update now** uses WordPress's normal plugin updater, including permission, nonce, filesystem, and installation checks. Automatic installation remains controlled by WordPress's own auto-update setting.
- Each stable release must have a three-part version tag (for example `v1.9.3`) and a matching uploaded asset such as `Product-Verification-System-1.9.3.zip`. GitHub's generated source archives are not used.

Install 1.9.2 manually once to enable the updater on sites running an older version. A newer version must be published with its matching release ZIP before it can be detected.

## Frequently asked questions

<details>
<summary><strong>Do visitors need an account?</strong></summary>

No. The verification form accepts requests from both signed-in visitors and guests.

</details>

<details>
<summary><strong>Can one record have several verification codes?</strong></summary>

Yes. Each record can contain multiple codes. Any matching code returns that record's configured details.

</details>

<details>
<summary><strong>Can I hide certificates, invoices, or other fields?</strong></summary>

Yes. Use the Visible checkboxes in Settings to control what is returned in verification results. You can also reorder fields and customize supported labels.

Field visibility controls the verification output. It does not add access restrictions to files in the WordPress Media Library.

</details>

<details>
<summary><strong>Does an expired item become an invalid code?</strong></summary>

No. A code match still succeeds. Expiry is shown separately through the validity status field.

</details>

<details>
<summary><strong>Where can I find release details?</strong></summary>

See [CHANGELOG.md](CHANGELOG.md) for the documented version history and [GitHub Releases](https://github.com/vibrig/Product-Verification-System-Wordpress-Plugin/releases) for downloadable packages.

</details>

## Support & contributions

Found a bug or have an idea? [Open a GitHub issue](https://github.com/vibrig/Product-Verification-System-Wordpress-Plugin/issues) with the steps to reproduce it, the expected result, and your WordPress, PHP, and plugin versions.

For changes to the administration scripts, the existing relationship checks can be run with Node.js:

```sh
node tests/admin-relationships.test.js
php tests/updates.test.php
```

## License & ownership

Owned by **[Vibrig](https://vibrig.com/)**. Licensed under **[GNU GPL v2 or later](https://www.gnu.org/licenses/old-licenses/gpl-2.0.html)**.

