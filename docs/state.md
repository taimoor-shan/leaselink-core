# LeaseLink Agent State

## Active Mission

Audit leaselink-core plugin against REQUIREMENTS.md, fix bugs, and implement missing features.

## Architecture Decisions

- `SRP_Core::run()` is the single entry point — constructor is empty
- Cron receives workflow instances via constructor injection
- REST API delegates all business logic to workflow classes
- Notifications are decoupled — listen to `do_action` hooks emitted by workflows
- Property-Unit relationship enforced: Units cannot be published without a parent Property

## The 'Why' Log

- **Moved init from constructor to run():** Matches the WordPress plugin boot pattern (`$plugin = new Core(); $plugin->run();`) and allows future testability
- **Property-Unit enforcement:** User feedback — §4.1 says "Every Unit MUST belong to a Property, Cannot create orphan Units" — enforced via auto-revert to draft + admin notice
- **€ currency:** European standards alignment per conversation 8133bbbb

## Blocking Issues

None.

## Technical Debt

- REST API listing search filters query property/unit meta across post types — performance at scale needs meta caching/denormalization
- No rate limiting on REST endpoints or view counter
- Notifications use plain text in HTML wrapper — should migrate to proper email templates
- No unit tests yet (§12 in requirements)

## Current Context

All 4 implementation phases complete:

1. **Bug fixes:** HTML table, currency, run() refactor, Property-Unit enforcement
2. **Cron:** Listing expiry (90d) + application expiry (14d), scheduled twice daily
3. **Notifications:** 7 email hooks wired to workflow events
4. **REST API:** Public listing search with filters, authenticated application management
5. **View counter:** Increments on frontend visits and API detail requests

### Files Modified

- `class-srp-core.php` — restructured with cron/notifications/REST/view counter init
- `class-srp-listing-meta.php` — fixed HTML, fixed currency
- `class-srp-unit-meta.php` — Property-Unit enforcement
- `class-srp-activator.php` — cron scheduling on activation
- `class-srp-deactivator.php` — cron cleanup on deactivation

### Files Created

- `class-srp-cron.php` — WP cron scheduler
- `class-srp-notifications.php` — email notification handler
- `rest-api/class-srp-rest-listings.php` — public listing endpoints
- `rest-api/class-srp-rest-applications.php` — application management endpoints

## Next Steps

1. Add PHPUnit tests for workflows and REST endpoints (§12)
2. Implement search & filtering frontend (Alpine.js + HTMX per §11)
3. Add geolocation search with PostGIS or meta-based radius queries (§9)
4. Build email templates with proper branding
5. Implement monetization features (§8: subscriptions, featured listing credits)
