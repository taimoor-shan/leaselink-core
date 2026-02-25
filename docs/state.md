# LeaseLink Agent State

## Active Mission

Fix Landlord property creation issues and ensure the frontend property creation form precisely matches the backend CPT meta fields.

## Architecture Decisions

- `SRP_Core::run()` is the single entry point — constructor is empty
- Cron receives workflow instances via constructor injection
- REST API delegates all business logic to workflow classes
- Notifications are decoupled — listen to `do_action` hooks emitted by workflows
- Property-Unit relationship enforced: Units cannot be published without a parent Property
- **Template Loader pattern** (new): Plugin ships default templates under `templates/`; themes can override by placing files in `leaselink/` directory (mirrors WooCommerce's `woocommerce/` convention)
- **SRP_Frontend class** (new): All frontend concerns (shortcodes, asset enqueuing, body classes, dashboard protection, login redirects) moved from theme `functions.php` into plugin
- **REST API Form Data Submission:** Add Property frontend now submits `FormData` allowing media uploads to WP core via `media_handle_sideload`.

## The 'Why' Log

- **Moved init from constructor to run():** Matches the WordPress plugin boot pattern (`$plugin = new Core(); $plugin->run();`) and allows future testability
- **Property-Unit enforcement:** User feedback — §4.1 says "Every Unit MUST belong to a Property, Cannot create orphan Units" — enforced via auto-revert to draft + admin notice
- **€ currency:** European standards alignment per conversation 8133bbbb
- **Plugin-theme decoupling:** Theme `functions.php` had 115+ lines of plugin-specific logic (HTMX/Leaflet/Fancybox enqueuing, role-based body classes, dashboard protection, login redirects). This tightly coupled the plugin to TailPress. Moved all this logic into `SRP_Frontend` so the plugin works with any theme. Theme retains only a thin `leaselink_get_dashboard_url()` helper that delegates to the plugin.
- **REST API Property Upload:** Switched frontend Vue/AlpineJS form submission from `application/json` to `FormData` to support image uploads for the `featured_image` of Properties and Units. Created a `handle_featured_image` helper leveraging `media_handle_sideload` to save attachments directly on creation.

## Blocking Issues

- Theme `functions.php` contains git merge conflict markers (`<<<<<<< Updated upstream` / `>>>>>>> Stashed changes`) that was already present before this session. The decoupling changes clean up this section, but the conflict markers need to be resolved in the commit. (Carried over issue).
- Running WP-CLI from `app/public/wp-content` inside the machine hit a DB connection error (likely due to missing config environment variables or WP setup in that specific terminal state). Bypassed by triggering capability resets dynamically or verifying visually.

## Technical Debt

- `class-srp-roles.php` role resetting had to be triggered to apply the `manage_properties` capability fix for the Landlord role since `add_role` caches in the DB permanently.
- REST API listing search filters query property/unit meta across post types — performance at scale needs meta caching/denormalization
- No rate limiting on REST endpoints or view counter
- Notifications use plain text in HTML wrapper — should migrate to proper email templates
- No unit tests yet (§12 in requirements)
- Theme override templates in `tailpress/leaselink/` currently mirror plugin defaults — need TailPress-styled versions with Tailwind classes
- `leaselink.css` is a baseline — needs design polish and dark mode support

## Current Context

Fixed Landlord Property Creation flow and aligned frontend dashboard form to backend CPT meta fields:

### Plugin Changes (`leaselink-core`, branch `cleaning-theme`)

**Modified:**

- `includes/class-srp-roles.php` — Added `manage_properties` capability to the 'landlord' role so landlords can pass the REST API permission check for property endpoints.
- `includes/class-srp-core.php` — Wired up new `SRP_REST_Properties` controller.
- `includes/rest-api/class-srp-rest-properties.php` — Built comprehensive REST API handling POST `/rental/v1/properties`, POST `/rental/v1/properties/{id}/units`, and GET `/rental/v1/properties`. Handles `FormData` for creating properties and units, specifically calling `media_handle_sideload` for `featured_image` field. Aligned all saving functionality with required CPT meta fields (`latitude`, `longitude`, `floor_number`, `utilities_included`, etc.).
- `templates/dashboard/add-property.php` — Updated frontend UI to include all missing inputs (Featured Image, utilities, floor number, latitude/longitude). Swapped submission logic from standard fetch JSON to `FormData` so file uploads work correctly.

## Next Steps

1. **Verify Landlord Role Capabilities:** Ensure the WP database has been updated with the modified `landlord` capabilities (e.g., deactivate/reactivate plugin or run reset script).
2. **Review Frontend Styling & Interactivity:** Update the `add-property.php` form to have better user feedback during file uploads and perhaps integrate Leaflet.js to auto-fill latitude/longitude when selecting an address.
3. **Style theme overrides:** Update `tailpress/leaselink/` templates with TailPress/Tailwind classes for a polished look — start with `single-cpt_listing.php` and dashboard templates
4. Add PHPUnit tests for workflows, REST endpoints, and template loader (§12)
5. Implement search & filtering frontend (Alpine.js + HTMX per §11) — wire up `[srp_search]` shortcode
