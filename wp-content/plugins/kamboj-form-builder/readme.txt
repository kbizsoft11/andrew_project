=== Kamboj Form Builder – Drag & Drop Contact Forms ===
Contributors: mohitkamboj55
Tags: contact form, form builder, contact forms, drag and drop, custom forms
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.7.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Build contact forms with a drag & drop builder, AJAX submit, reCAPTCHA, webhooks, CSV export, and a built-in submissions inbox.

== Description ==

**Kamboj Form Builder** is a lightweight **WordPress contact form plugin** built for site owners who want powerful **custom forms** without slowing down their website. Build **contact forms**, **feedback forms**, **quote request forms**, and **registration forms** using a visual **drag & drop form builder**, then publish them anywhere with a shortcode.

Every form submits through **AJAX**, so visitors never leave the page. Submissions can be stored in a built-in inbox, sent by email, exported to CSV, or pushed to external apps through **webhooks**.

= Why site owners choose Kamboj Form Builder =

Unlike many traditional WordPress form plugins, Kamboj Form Builder focuses on speed, simplicity, and essential features without unnecessary bloat.

✔ Lightweight  
✔ Drag & Drop Builder  
✔ AJAX Forms  
✔ Built-in Form Entries  
✔ CSV Export  
✔ Google reCAPTCHA  
✔ Webhooks  
✔ Conditional Logic  
✔ Developer Friendly  

= Key Features =

= Drag & Drop Form Builder =

Create professional forms visually without writing code. Add text, email, phone, number, textarea, dropdown, radio, checkbox, file upload, GDPR consent, and hidden fields from a simple field palette. Drag fields to reorder them, set field width (full, half, or one third), mark fields as required, and preview your form before publishing. The tabbed editor keeps fields, email settings, preview, and validation organized in one place.

= AJAX Forms =

Forms submit in the background without reloading the page. Visitors see instant success or field-level error messages, which creates a smoother experience on contact pages, landing pages, and lead capture forms. AJAX submission also works well with caching plugins and modern WordPress themes.

= Form Entries =

Every submission can be saved to a built-in **form entries** inbox inside WordPress. Review new and read messages, open submission details, and keep a record of leads and customer inquiries without relying on email alone. Form entries are stored in your WordPress database and stay under your control.

= Email Notifications =

Send email alerts to one or multiple recipients when someone submits a form. Customize the subject and body with merge tags such as `{{name}}`, `{{email}}`, and `{{all_fields}}`. Optionally send a confirmation email back to the person who filled out the form. File uploads can be included as a download link or attached to the notification email.

= Conditional Logic =

Show or hide fields based on answers in other fields. For example, display an extra text box only when a visitor selects a specific option from a dropdown. Conditional logic helps you build smarter **quote request forms**, support forms, and multi-step style experiences without adding complexity for the visitor.

= Google reCAPTCHA =

Protect your forms from spam bots with **Google reCAPTCHA v2** (the familiar “I’m not a robot” checkbox) or **Google reCAPTCHA v3** (invisible, score-based verification). Add your site key and secret key once in plugin settings, then enable reCAPTCHA per form alongside honeypot and rate limiting.

= Webhooks =

Send submission data to Zapier, Slack, Make, or any custom API endpoint. When a form is submitted, the plugin can POST the entry data to your webhook URL so you can automate CRM updates, team notifications, and custom workflows.

= CSV Export =

Export **form entries** to CSV for reporting, backup, or import into spreadsheets and CRM tools. This is useful for agencies, sales teams, and site owners who need an offline copy of submissions.

= Perfect for =

* Business **contact forms** and lead capture pages
* Customer **feedback forms** and satisfaction surveys
* **Quote request forms** for services and products
* Simple **registration forms** and event sign-ups
* Support tickets and help desk intake forms

= Easy to use =

1. Create a form in the visual builder with live preview.
2. Copy the shortcode — for example `[kmfb_form id="123"]`.
3. Paste it into any page, post, or widget area.
4. Submissions arrive in your inbox and by email automatically.

No theme edits or PHP required. Works with any WordPress theme.

= Built for site owners and developers =

Customize validation messages globally or per form. Set redirect URLs after submit, connect webhooks for automation, and control file upload types and sizes. Kamboj Form Builder is designed to stay fast, secure, and easy to maintain on WordPress.org hosting standards.

== Installation ==

1. Upload the `kamboj-form-builder` folder to the `/wp-content/plugins/` directory, or install through the WordPress Plugins screen.
2. Activate the plugin through the **Plugins** screen.
3. Go to **Kamboj Form Builder → Add New** to create your first form.
4. Copy the shortcode and paste it into any page or post.

== Frequently Asked Questions ==

= How do I display a form? =

Use the shortcode shown in the form list or editor, for example: `[kmfb_form id="123"]`

= Can I send notifications to multiple email addresses? =

Yes. In the form editor, open the **Email** tab and enter multiple addresses separated by commas.

= Can I send a confirmation email to the visitor? =

Yes. In the **Email** tab, enable **Send confirmation email to submitter** and customize the subject and body. The plugin sends it to the email address submitted in the form.

= Does this plugin store submissions? =

Yes, if **Store submissions in inbox** is enabled for the form (enabled by default). Submissions are stored in your WordPress database.

= What data is collected? =

The plugin stores whatever fields you add to your form (such as name and email), plus the visitor IP address and user agent for spam protection when submissions are stored.

= Does the plugin call external services? =

Only if you configure a webhook URL on a form. Email is sent using your WordPress site’s built-in `wp_mail()` function.

== Changelog ==

= 1.7.1 =
* Fix reCAPTCHA v2 checkbox appearing below the submit button

= 1.7.0 =
* Inline layout mode for newsletter / subscriber signup forms (email + button in one row)
* Per-field "Show label" toggle with accessible aria-label when hidden
* "Add Newsletter Form" starter template on the forms list
* International phone field with country flag and dial code selector
* Phone fields are optional by default (enable Required only if needed)

= 1.6.0 =
* Google reCAPTCHA v2 (checkbox) and v3 (invisible) support
* Global API keys in plugin settings; enable per form in Form behavior

= 1.5.0 =
* Tabbed form editor: Form Fields, Email, Preview, and Validation
* Per-form validation message overrides
* Fixed drag-and-drop field reordering in the builder

= 1.4.1 =
* Plugin Check: prefixed template and uninstall variables

= 1.4.0 =
* Rebranded to Kamboj Form Builder for WordPress.org.

= 1.3.3 =
* Field width option: full, half (2 per row), or one third (3 per row)
* Responsive layout stacks fields on mobile

= 1.3.2 =
* Sanitize form builder JSON payload on save

= 1.3.1 =
* Plugin Check compatibility fixes

= 1.3.0 =
* WordPress.org directory compatibility
* Privacy policy and personal data export/erase support
* Uninstall cleanup option
* Improved admin asset loading

= 1.2.8 =
* Multiple notification recipients
* Confirmation email to submitter with on/off toggle
* Removed top validation error summary box

= 1.2.7 =
* UI fixes for delete button overflow and required toggle

= 1.2.5 =
* Duplicate and delete forms from the list
* Clickable form names

= 1.2.0 =
* Initial public release as Kamboj Form Builder

== Upgrade Notice ==

= 1.5.0 =
Redesigned tabbed form editor with working drag-and-drop reorder and per-form validation messages.

= 1.4.1 =

= 1.3.3 =
Adds per-field width controls for multi-column form layouts.

= 1.3.2 =
Sanitizes form builder save payload for Plugin Check compliance.

= 1.3.1 =
Plugin Check and WordPress 7.0 compatibility fixes.

= 1.3.0 =
Recommended update for WordPress.org compatibility and privacy tools support.

== Privacy Policy ==

This plugin may store form submission data in your WordPress database, including personal information entered by visitors (such as name and email), IP address, and browser user agent. Email notifications are sent through your server using WordPress `wp_mail()`. If you configure a webhook URL, submission data is sent to that external URL. Use WordPress **Settings → Privacy** to review the suggested privacy policy text added by this plugin.
