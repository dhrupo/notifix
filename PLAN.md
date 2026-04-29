# Real-Time WordPress Notification Engine Plan

## Goal

Build a universal event-driven notification plugin for WordPress that captures events from the WordPress plugins that matter most for revenue and lead generation, normalizes them into a single schema, stores them for replay/debugging, and delivers them to site visitors in real time through a WebSocket-based transport layer.

## Non-Negotiables

- No polling.
- No hardcoded business logic inside plugin integrations.
- WordPress is the event source and control plane, not the realtime server.
- Every integration must emit the same normalized event shape.
- Realtime delivery must be transport-driven so the backend can be replaced without rewriting adapters or UI.
- The plugin must remain lightweight in memory usage, database writes, frontend payload size, and runtime overhead.

## Core Flow

```text
Plugin Hook
-> Adapter
-> EventManager
-> EventNormalizer
-> EventRepository
-> TransportManager
-> WebSocket Relay
-> Frontend Client
-> Notification Queue
```

## Proposed Plugin Structure

```bash
wp-rt-notify/
├── rt-notify.php
├── includes/
│   ├── Core/
│   │   ├── EventManager.php
│   │   ├── EventNormalizer.php
│   │   ├── EventRepository.php
│   │   ├── NotificationPolicy.php
│   │   ├── TransportManager.php
│   ├── Contracts/
│   │   ├── AdapterInterface.php
│   │   ├── TransportDriverInterface.php
│   ├── Adapters/
│   │   ├── Commerce/
│   │   │   ├── WooCommerce.php
│   │   │   ├── SureCart.php
│   │   │   ├── FluentCart.php
│   │   │   ├── EasyDigitalDownloads.php
│   │   ├── Forms/
│   │   │   ├── FluentForms.php
│   │   │   ├── WPForms.php
│   │   │   ├── GravityForms.php
│   │   │   ├── NinjaForms.php
│   │   │   ├── FormidableForms.php
│   │   ├── LMS/
│   │   │   ├── LearnDash.php
│   │   │   ├── TutorLMS.php
│   │   │   ├── LearnPress.php
│   │   │   ├── LifterLMS.php
│   │   ├── Membership/
│   │   │   ├── MemberPress.php
│   │   │   ├── PaidMembershipsPro.php
│   │   │   ├── RestrictContent.php
│   │   │   ├── PaidMemberSubscriptions.php
│   ├── Transport/
│   │   ├── WebSocketDriver.php
│   │   ├── NullDriver.php
│   ├── Admin/
│   │   ├── Settings.php
│   │   ├── DebugScreen.php
│   ├── API/
│   │   ├── RestController.php
├── assets/
│   ├── js/client.js
│   ├── css/style.css
└── PLAN.md
```

## Integration Coverage Strategy

The product should target plugin families, not isolated plugins:

- Commerce: order activity, checkout completions, subscriptions, digital product sales.
- LMS: enrollments, completions, course starts, bundle access.
- Forms: lead capture, registrations, quote requests, newsletter intent.
- Memberships: membership purchases, renewals, upgrades, access grants.

The adapter system should boot only for active plugins and should register adapters lazily to avoid unnecessary overhead.

## Priority Plugin Matrix

These are the integrations that matter most for the first serious version of the product. The priority is based on current adoption signals from official plugin pages and vendor sites checked on April 28, 2026.

### Commerce

- WooCommerce: must support. WordPress.org lists 7+ million active installs.
- SureCart: must support. WordPress.org lists 90,000+ active installs.
- FluentCart: must support. Newer but strategically important because it is growing in the modern WordPress commerce segment.
- Easy Digital Downloads: should support. Important for software, digital goods, and creator stores.

### LMS

- LearnDash: must support. Commercial-only but still one of the most important WordPress LMS products.
- Tutor LMS: must support. WordPress.org lists 100,000+ active installs.
- LearnPress: must support. WordPress.org lists 80,000+ active installs.
- LifterLMS: should support. WordPress.org lists 10,000+ active installs and it has meaningful course and membership overlap.

### Forms

- WPForms: must support. WordPress.org lists 6+ million active installs.
- Fluent Forms: must support. WordPress.org lists 700,000+ active installs.
- Gravity Forms: must support. Commercial-only but widely used on serious business sites.
- Ninja Forms: should support. WordPress.org lists 600,000+ active installs.
- Formidable Forms: should support. WordPress.org lists 300,000+ active installs.

### Memberships

- MemberPress: must support. Commercial-only but strategically important for monetized sites and course businesses.
- Paid Memberships Pro: must support. Important open-source membership platform with broad LMS overlap.
- Restrict Content: should support. Relevant for content monetization and access gating.
- Paid Member Subscriptions: should support. WordPress.org lists 10,000+ active installs.

### Optional but High-Value Expansion

- Booking: Amelia, Bookly.
- Events and tickets: The Events Calendar, Event Tickets.
- Email/CRM-triggered proof sources: FluentCRM, MailPoet.

These should not change the core architecture. They should just add new adapters and new event templates.

## Event Contract

Every adapter must emit this shape:

```json
{
  "type": "purchase|order_paid|subscription_started|subscription_renewed|form_submit|lead_captured|course_enrolled|course_completed|membership_started|membership_renewed",
  "source": "woocommerce|surecart|fluentcart|edd|fluentforms|wpforms|gravityforms|ninjaforms|formidable|learndash|tutorlms|learnpress|lifterlms|memberpress|pmpro|custom",
  "title": "Someone purchased Product X",
  "message": "Someone purchased Product X",
  "meta": {},
  "actor": {
    "label": "Someone",
    "username": "",
    "location": ""
  },
  "object": {
    "id": 0,
    "type": "product",
    "label": "Product X"
  },
  "visibility": "public",
  "dedupe_key": "",
  "created_at": "YYYY-MM-DD HH:MM:SS"
}
```

## Message Rendering Rules

Notification text must be template-driven and identity-aware.

Rules:

- If a usable user name is available, render notifications like `John purchased Product X 2 minutes ago`.
- If no usable user name is available, render notifications like `Someone purchased Product X 2 minutes ago`.
- Relative time should be rendered at display time, not hardcoded at storage time.
- Actor identity must support masking rules and privacy settings.
- Templates must be editable by the admin per event type.

Recommended template tokens:

- `{actor_name}`
- `{object_name}`
- `{event_time_ago}`
- `{actor_location}`
- `{source}`
- `{event_type}`

Recommended default examples:

- `"{actor_name} purchased {object_name} {event_time_ago}"`
- `"{actor_name} enrolled in {object_name} {event_time_ago}"`
- `"{actor_name} submitted {object_name} {event_time_ago}"`

## Database Plan

Use one custom table:

### `wp_rt_events`

- `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `type` VARCHAR(50) NOT NULL
- `source` VARCHAR(50) NOT NULL
- `title` TEXT NOT NULL
- `message` TEXT NOT NULL
- `meta` LONGTEXT NULL
- `actor` LONGTEXT NULL
- `object` LONGTEXT NULL
- `visibility` VARCHAR(20) NOT NULL DEFAULT 'public'
- `dedupe_key` VARCHAR(191) NULL
- `dispatch_status` VARCHAR(20) NOT NULL DEFAULT 'pending'
- `created_at` DATETIME NOT NULL

Indexes:

- `KEY type_created_at (type, created_at)`
- `KEY source_created_at (source, created_at)`
- `KEY dispatch_status_created_at (dispatch_status, created_at)`
- `KEY dedupe_key (dedupe_key)`

## Core Responsibilities

### `EventManager`

- Receive raw event payloads.
- Validate minimum required fields.
- Pass payloads to the normalizer.
- Store normalized events.
- Dispatch stored events to the configured transport.

### `EventNormalizer`

- Convert adapter-specific payloads into the canonical schema.
- Fill defaults for missing optional fields.
- Sanitize text and serialize nested payloads safely.
- Generate `dedupe_key` when missing.

### `EventRepository`

- Create the event table on activation.
- Insert normalized events.
- Read recent events for debug and replay.
- Update dispatch status after transport attempts.

### `NotificationPolicy`

- Decide whether an event is displayable.
- Prevent noisy repeats.
- Enforce event-type enable/disable rules.
- Support page targeting and cooldown rules.
- Enforce actor fallback rules such as `Someone` when identity is unavailable or hidden.

### `TransportManager`

- Resolve the active transport driver.
- Pass accepted events to the driver.
- Handle dispatch failure without losing the stored event.

## Transport Plan

Use a transport driver abstraction:

- `WebSocketDriver`: primary production transport.
- `NullDriver`: safe fallback when realtime is not configured.

The plugin should not host sockets directly inside WordPress. The plugin should send accepted events to an external relay or a dedicated realtime service, which then broadcasts to subscribed browser clients.

Expected driver behavior:

1. Receive normalized event.
2. Convert it to transport payload.
3. Sign or authenticate the request.
4. Send it to the relay endpoint.
5. Return success or failure to the repository layer.

## Frontend Plan

The frontend client should:

- Connect to the realtime relay through WebSocket.
- Subscribe only after page-level display rules pass.
- Maintain a FIFO queue.
- Show one notification at a time.
- Auto-hide after a configurable duration.
- Deduplicate repeated messages in a short time window.
- Pause or suppress notifications when the tab is hidden.
- Render identity-aware templates using normalized data and relative time helpers.
- Read UI settings that are configured in the admin without requiring hardcoded styles.

UI behavior:

- Toast-style popup.
- Subtle entrance and exit animation.
- Small visual footprint.
- No notification flood.

Supported positions:

- `top-left`
- `top-right`
- `bottom-left`
- `bottom-right`
- `top-center`
- `bottom-center`

Customizable style controls:

- Background color
- Text color
- Accent or border color
- Border radius
- Shadow intensity
- Width and spacing
- Animation speed
- Notification duration

The frontend should apply these settings with lightweight CSS variables so the UI remains configurable without shipping multiple style bundles.

## Adapter Plan

Each adapter must implement a shared interface and only do three things:

1. Listen to plugin hooks.
2. Extract the minimum useful context.
3. Emit a normalized event through the core API.

### Adapter families

- Commerce adapters
- LMS adapters
- Form adapters
- Membership adapters

### Core support target

- Commerce
- WooCommerce
- SureCart
- FluentCart
- Easy Digital Downloads

- LMS
- LearnDash
- Tutor LMS
- LearnPress
- LifterLMS

- Forms
- WPForms
- Fluent Forms
- Gravity Forms
- Ninja Forms
- Formidable Forms

- Memberships
- MemberPress
- Paid Memberships Pro
- Restrict Content
- Paid Member Subscriptions

### Canonical event families

Commerce event types:

- `purchase`
- `order_paid`
- `subscription_started`
- `subscription_renewed`

LMS event types:

- `course_enrolled`
- `course_started`
- `course_completed`

Form event types:

- `form_submit`
- `lead_captured`
- `registration_submitted`

Membership event types:

- `membership_started`
- `membership_renewed`
- `membership_upgraded`

### Adapter implementation rules

- Each adapter must first detect whether the target plugin is active.
- Each adapter must use the target plugin's stable public hooks where possible.
- Each adapter must map plugin-specific statuses into canonical event types.
- Each adapter must expose enough metadata for templating without leaking sensitive data.
- Each adapter should pass actor identity when available, but must not assume identity is public-safe.
- Each adapter must fail silently if the source plugin is absent or its event payload is incomplete.

### Why this support set matters

- Commerce plugins generate the clearest conversion proof.
- LMS plugins generate both trust and progress proof.
- Form plugins generate lead and signup proof on non-commerce sites.
- Membership plugins cover paywalled and subscription-heavy businesses that do not use a traditional cart.

## Public Integration API

Expose a single integration point for third parties:

```php
do_action('rt_notify_event', $event);
```

Internal helper:

```php
rt_notify_emit($event);
```

This keeps external integrations simple and keeps normalization centralized.

## Admin Plan

Keep the admin surface small:

- Enable or disable the plugin.
- Choose transport driver.
- Configure relay credentials or endpoint.
- Enable or disable integrations by plugin family and by plugin.
- Enable or disable event types.
- Configure message templates.
- Configure cooldown and timing rules.
- Enable or disable fake events.

Admin should still have enough control to tune behavior without code changes.

Required control areas:

- General
- Enable or disable the engine
- Enable debug mode
- Set retention rules for stored events

- Realtime
- Relay endpoint
- Auth token or signing secret
- Connection timeout and retry settings

- Integrations
- Per-plugin enable or disable
- Per-event-type enable or disable
- Source-specific template overrides

- Display rules
- Page targeting
- Delay before first notification
- Cooldown between notifications
- Maximum notifications per session
- Repeat suppression window
- Device targeting

- Identity and privacy
- Show username when available
- Fallback to `Someone`
- Optional location display
- Identity masking mode

- UI customization
- Toast background color
- Toast text color
- Position selector
- Duration
- Animation
- Max width
- Spacing

- Fake events
- Enable or disable synthetic events
- Template and source labeling for synthetic events
- Frequency rules for synthetic events

Add one debug screen with:

- Recent normalized events.
- Event source.
- Event type.
- Dispatch status.
- Last transport error if available.

## Fake Event Plan

Fake events must not bypass the system. They should be generated as synthetic events and passed through the same storage, policy, and rendering pipeline.

Rules:

- Mark fake events with `source = synthetic`.
- Never mix fake and real data in the adapter layer.
- Allow fake events only when no eligible real events exist or when explicitly enabled by settings.

## Privacy Rules

- Do not expose raw customer names by default.
- Support masked labels such as `Someone`, `A student`, or `Someone from Dhaka`.
- If admin enables identity display and a safe username is available, use it in the rendered message.
- If no safe username is available, always fall back to `Someone`.
- Keep full payload details out of public output unless explicitly allowed.
- Sanitize all event text before storage and before broadcast.

## Performance Rules

- Keep the frontend script small and framework-free unless a larger dependency is clearly justified.
- Load assets only on frontend requests where notifications can actually appear.
- Register adapters conditionally and avoid booting unused integrations.
- Avoid repeated database queries in request-time hooks.
- Keep event inserts minimal and index only what is needed for retrieval and status updates.
- Use CSS variables for admin-driven UI customization instead of generating large inline style blocks repeatedly.
- Avoid expensive realtime dispatch retries during page requests; hand off retry work to scheduled background processing when needed.
- Cache resolved plugin-activation checks and settings reads within the request lifecycle.
- Do not ship analytics-heavy code in the initial core if it threatens runtime cost.

## Build Order

1. Bootstrap the plugin entry file and autoloading strategy.
2. Create activation logic and `wp_rt_events` table.
3. Implement contracts for adapters and transport drivers.
4. Build `EventNormalizer`.
5. Build `EventRepository`.
6. Build `TransportManager` and `NullDriver`.
7. Build `EventManager` and the public emit API.
8. Add `WebSocketDriver` with relay configuration support.
9. Build adapter discovery and plugin-activation detection.
10. Build commerce adapters for WooCommerce, SureCart, FluentCart, and Easy Digital Downloads.
11. Build LMS adapters for LearnDash, Tutor LMS, LearnPress, and LifterLMS.
12. Build form adapters for WPForms, Fluent Forms, Gravity Forms, Ninja Forms, and Formidable Forms.
13. Build membership adapters for MemberPress, Paid Memberships Pro, Restrict Content, and Paid Member Subscriptions.
14. Build `NotificationPolicy`.
15. Build admin-configurable message templating with actor fallback and relative-time rendering.
16. Build the frontend socket client and queue renderer.
17. Add lightweight CSS-variable-based toast styling with configurable positions and colors.
18. Build admin settings for transport, integration toggles, display rules, UI customization, identity handling, and event toggles.
19. Build debug screen for event inspection and dispatch status.
20. Add fake event generation using the same normalized pipeline.
21. Test realtime delivery, duplicate suppression, adapter isolation, admin template rendering, identity fallback behavior, and rule-based display behavior across supported plugin families.

## Acceptance Criteria

- A supported plugin event is captured without hardcoded frontend logic.
- The event is normalized into a single schema.
- The event is stored in `wp_rt_events`.
- The event is dispatched through the configured realtime driver.
- The browser receives the event without polling.
- The UI shows the notification naturally and without flooding the user.
- If username data exists and is allowed, the UI can render messages like `X purchased Y 2 minutes ago`.
- If username data is missing or hidden, the UI renders `Someone` as fallback.
- Admin can change notification colors and position without code changes.
- Failed dispatches do not lose the event record.
- New adapters can be added without changing the transport or frontend code.
- The plugin can selectively enable only the adapters needed on a given site.
- The plugin remains lightweight by loading only the integrations, assets, and logic needed for the active site.

## Technical Notes

- WebSocket should be the primary transport, but the transport layer must remain replaceable.
- Server-Sent Events can be added later as another transport driver if needed.
- Analytics, A/B testing, and advanced segmentation should stay out of the initial build until the realtime event pipeline is stable.
