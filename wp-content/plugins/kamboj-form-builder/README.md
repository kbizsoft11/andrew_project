# Kamboj Form Builder

A modern WordPress contact form plugin — a better alternative to Contact Form 7 for teams that want visual editing, built-in submission storage, and stronger spam protection without extra add-ons.

## Why this over Contact Form 7?

| Feature | Contact Form 7 | Kamboj Form Builder |
|---------|----------------|---------------------|
| Form building | Write shortcode tags by hand | Visual form builder in admin |
| Submission storage | Needs Flamingo plugin | Built-in inbox |
| CSV export | Via add-ons | One-click export |
| Spam protection | Basic / plugins | Honeypot + rate limit built-in |
| AJAX submit | Yes | Yes, with inline errors |
| Webhooks | Via plugins | Built-in per form |
| Conditional fields | Limited / plugins | Built-in show/hide rules |
| GDPR consent field | Manual | Dedicated consent field type |
| File uploads | Yes | Yes, with type/size controls |

## Features

- Visual form builder (no `[text* your-name]` markup)
- Shortcode: `[kmfb_form id="123"]`
- Submission inbox with detail view
- Email notifications with merge tags (`{{name}}`, `{{all_fields}}`, etc.)
- Webhook URL per form (Slack, Zapier, custom API)
- Conditional field logic
- Honeypot + per-IP rate limiting
- AJAX form submit with validation messages
- File upload support
- Global settings for email sender and file rules

## Installation

1. Copy `kamboj-form-builder` into `wp-content/plugins/`
2. Activate **Kamboj Form Builder**
3. Go to **Kamboj Form Builder → Add New**
4. Build your form and click **Save Form**
5. Place `[kmfb_form id="X"]` on any page

## Project structure

```
kamboj-form-builder/
├── kamboj-form-builder.php
├── includes/
├── admin/
├── public/
└── templates/
```

## Requirements

- WordPress 6.0+
- PHP 7.4+

## License

GPL-2.0-or-later
