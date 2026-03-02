# LeaseLink Agent State

## Active Mission

Verify landlord capabilities, set up PHPUnit test suite with WP_UnitTestCase, and style TailPress theme override components.

## Architecture Decisions

- `SRP_Core::run()` is the single entry point — constructor is empty
- Cron receives workflow instances via constructor injection
- REST API delegates all business logic to workflow classes
- Notifications are decoupled — listen to `do_action` hooks emitted by workflows
- Property-Unit relationship enforced: Units cannot be published without a parent Property
- **Template Loader pattern**: Plugin ships default templates under `templates/`; themes can override by placing files in `leaselink/` directory (mirrors WooCommerce's `woocommerce/` convention)
- **SRP_Frontend class**: All frontend concerns (shortcodes, asset enqueuing, body classes, dashboard protection, login redirects) moved from theme `functions.php` into plugin
- **REST API Form Data Submission:** Add Property frontend now submits `FormData` allowing media uploads to WP core via `media_handle_sideload`.
- **PHPUnit with WP_UnitTestCase** (new): Full WordPress integration tests using PHPUnit 9.6 + yoast/phpunit-polyfills. Tests run against a separate `local_tests` database via Local WP's MySQL socket.
- **Theme override scope** (new): Only small, reusable components (badge, stat-card, empty-state, dashboard-nav) are overridden in the theme — full page templates are NOT duplicated.

## The 'Why' Log

- **Moved init from constructor to run():** Matches the WordPress plugin boot pattern (`$plugin = new Core(); $plugin->run();`) and allows future testability
- **Property-Unit enforcement:** User feedback — §4.1 says "Every Unit MUST belong to a Property, Cannot create orphan Units" — enforced via auto-revert to draft + admin notice
- **€ currency:** European standards alignment per conversation 8133bbbb
- **Plugin-theme decoupling:** Theme `functions.php` had 115+ lines of plugin-specific logic. Moved all this logic into `SRP_Frontend` so the plugin works with any theme.
- **REST API Property Upload:** Switched frontend form submission to `FormData` to support image uploads for the `featured_image` of Properties and Units.
- **PHPUnit not Brain\Monkey:** User explicitly requested full WP test suite (`WP_UnitTestCase`) rather than mock-based unit tests, since Local WP provides a MySQL database.
- **No full-page theme overrides:** Duplicating 200-400 line dashboard templates just to swap CSS classes is wasteful. Only small, reusable components were overridden in TailPress. Plugin's `leaselink.css` handles the rest.

## Blocking Issues

- Theme `functions.php` contains git merge conflict markers that need to be resolved (carried over issue).

## Technical Debt

- REST API listing search filters query property/unit meta across post types — performance at scale needs meta caching/denormalization
- No rate limiting on REST endpoints or view counter
- Notifications use plain text in HTML wrapper — should migrate to proper email templates
- `leaselink.css` is a baseline — needs design polish and dark mode support

## Current Context

### Session: Caps Verification + PHPUnit + Theme Overrides (2026-03-02)

**1. Landlord Capability Verification — ✅ All present**

Verified via direct MySQL query (socket connection to Local WP): 26 landlord capabilities and 10 student capabilities all present and correct. No reset needed. Created `tests/verify-caps.php` diagnostic script and `mu-plugins/leaselink-verify-caps.php` admin notice variant.

**2. TailPress Theme Overrides — ✅ 4 components created**

Created `tailpress/leaselink/components/` with 4 Tailwind-styled overrides:

- `badge.php` — ring utilities + semantic color tokens (indigo/emerald/amber/red)
- `stat-card.php` — hover gradient accent, refined spacing
- `empty-state.php` — larger icon container, focus-visible button styles
- `dashboard-nav.php` — gradient avatar, active ring state

Full page overrides (dashboard, add-property) intentionally skipped — not worth duplicating 200-400 line templates.

**3. PHPUnit Test Suite — ✅ 63 tests, 136 assertions, 0 failures**

Files created:

- `composer.json` — PHPUnit 9.6 + yoast/phpunit-polyfills
- `phpunit.xml.dist` — PHPUnit 9.x config
- `tests/bootstrap.php` — WP test suite loading
- `bin/install-wp-tests.sh` — WP test suite installer (Local WP MySQL socket)
- `tests/unit/ListingWorkflowTest.php` — 24 tests: transition map, convenience methods, verification level guard
- `tests/unit/ApplicationWorkflowTest.php` — 15 tests: submit/accept/reject/withdraw, required field validation
- `tests/unit/RolesTest.php` — 14 tests: install/remove roles, capability verification, idempotency
- `tests/unit/TemplateLoaderTest.php` — 10 tests: locate, fallback, get_template output, filter application

### Previous Session: Property Creation Flow Fix

- Added `manage_properties` to landlord role
- Built REST API for properties/units with FormData + media_handle_sideload
- Aligned frontend form to CPT meta fields

## Next Steps

1. **Implement search & filtering frontend** (Alpine.js + HTMX per §11) — wire up `[srp_search]` shortcode
2. **Review Frontend Styling & Interactivity:** Update the `add-property.php` form to have better user feedback during file uploads; integrate Leaflet.js for address → lat/lng
3. Resolve git merge conflict markers in theme `functions.php`
4. Add more tests: REST API endpoints, notification hooks, cron job execution
5. Dark mode support for `leaselink.css`
