# Notifix

Notifix is a WordPress plugin for real-time social proof and event notifications.

It captures events from supported plugins such as WooCommerce, Fluent Forms, LMS plugins, and membership plugins, normalizes them into a shared event contract, stores them in WordPress, and delivers them to the frontend through managed realtime providers.

## Current Features

- Universal adapter-based event pipeline
- Normalized event storage in a custom database table
- Admin-configurable message templates
- Username-aware notifications with `Someone` fallback
- Toast UI customization for color, position, spacing, and timing
- Managed realtime provider support
- Pusher transport driver
- Ably transport driver
- Integration-aware admin UI with disabled state for unavailable plugins

## Architecture

Core event flow:

```text
Plugin Hook
-> Adapter
-> EventManager
-> EventNormalizer
-> EventRepository
-> TransportManager
-> Managed Realtime Provider
-> Frontend Client
-> Notification Queue
```

## Plugin Structure

```text
notifix/
├── notifix.php
├── includes/
│   ├── Abstracts/
│   ├── Adapters/
│   ├── Admin/
│   ├── Contracts/
│   ├── Core/
│   ├── Support/
│   ├── Transport/
├── assets/
│   ├── css/
│   ├── js/
├── PLAN.md
└── README.md
```

## Realtime Providers

Notifix is built around managed realtime delivery.

### Pusher

Configure:

- `Transport Driver`: `Pusher`
- `App ID`
- `Key`
- `Secret`
- `Cluster`

### Ably

Configure:

- `Transport Driver`: `Ably`
- `Publish API Key`
- `Frontend Client Key`

For production, the frontend should use a restricted client key or token-auth flow instead of a full publish-capable key.

## Admin Page

Settings page:

```text
/wp-admin/admin.php?page=notifix
```

Key areas:

- General
- Pusher
- Ably
- Display Rules
- Identity
- Appearance
- Fake Events
- Integrations
- Event Types
- Templates

## Supported Integrations

Current adapter coverage in code includes:

- WooCommerce
- SureCart
- FluentCart
- Easy Digital Downloads
- Fluent Forms
- WPForms
- Gravity Forms
- Ninja Forms
- Formidable Forms
- LearnDash
- Tutor LMS
- LearnPress
- LifterLMS
- MemberPress
- Paid Memberships Pro
- Restrict Content
- Paid Member Subscriptions

Availability in admin is determined from the active WordPress environment.

## Local Development

1. Place the plugin in `wp-content/plugins/notifix`
2. Activate the plugin in WordPress
3. Open `Notifix` in the WordPress admin
4. Configure a managed realtime provider
5. Trigger a supported plugin event and verify delivery

## Notes

- The old custom websocket path still exists in code for compatibility, but the admin UI is intentionally focused on managed providers.
- The plugin is structured around reusable abstract classes and shared services so new integrations can be added without changing the core pipeline.

## Repository

```text
https://github.com/dhrupo/notifix.git
```
