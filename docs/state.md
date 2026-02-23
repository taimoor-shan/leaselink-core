# LeaseLink Agent State

## Active Mission

Decouple leaselink-core plugin from the TailPress theme using a WooCommerce-style template override system, making the plugin theme-independent.

## Architecture Decisions

- `SRP_Core::run()` is the single entry point — constructor is empty
- Cron receives workflow instances via constructor injection
- REST API delegates all business logic to workflow classes
- Notifications are decoupled — listen to `do_action` hooks emitted by workflows
- Property-Unit relationship enforced: Units cannot be published without a parent Property
- **Template Loader pattern** (new): Plugin ships default templates under `templates/`; themes can override by placing files in `leaselink/` directory (mirrors WooCommerce's `woocommerce/` convention)
- **SRP_Frontend class** (new): All frontend concerns (shortcodes, asset enqueuing, body classes, dashboard protection, login redirects) moved from theme `functions.php` into plugin

## The 'Why' Log

- **Moved init from constructor to run():** Matches the WordPress plugin boot pattern (`$plugin = new Core(); $plugin->run();`) and allows future testability
- **Property-Unit enforcement:** User feedback — §4.1 says "Every Unit MUST belong to a Property, Cannot create orphan Units" — enforced via auto-revert to draft + admin notice
- **€ currency:** European standards alignment per conversation 8133bbbb
- **Plugin-theme decoupling:** Theme `functions.php` had 115+ lines of plugin-specific logic (HTMX/Leaflet/Fancybox enqueuing, role-based body classes, dashboard protection, login redirects). This tightly coupled the plugin to TailPress. Moved all this logic into `SRP_Frontend` so the plugin works with any theme. Theme retains only a thin `leaselink_get_dashboard_url()` helper that delegates to the plugin.
- **Template override system:** Instead of hard-coding templates in the theme, plugin ships functional default templates. Themes can override any template by copying it to `mytheme/leaselink/`. This follows the well-established WooCommerce pattern and lets plugin users use any theme.

## Blocking Issues

- Theme `functions.php` contains git merge conflict markers (`<<<<<<< Updated upstream` / `>>>>>>> Stashed changes`) that was already present before this session. The decoupling changes clean up this section, but the conflict markers need to be resolved in the commit.

## Technical Debt

- REST API listing search filters query property/unit meta across post types — performance at scale needs meta caching/denormalization
- No rate limiting on REST endpoints or view counter
- Notifications use plain text in HTML wrapper — should migrate to proper email templates
- No unit tests yet (§12 in requirements)
- Theme override templates in `tailpress/leaselink/` currently mirror plugin defaults — need TailPress-styled versions with Tailwind classes
- `leaselink.css` is a baseline — needs design polish and dark mode support

## Current Context

Implemented plugin-theme decoupling (WooCommerce-style template override system):

### Plugin Changes (`leaselink-core`, branch `front-end`)

**Modified:**

- `includes/class-srp-core.php` — added `$template_loader` and `$frontend` properties, requires and instantiates both in `run()`

**Created:**

- `includes/class-srp-template-loader.php` — `SRP_Template_Loader` class; intercepts WP `template_include` for CPTs and page templates, searches theme `leaselink/` dir then falls back to plugin `templates/`
- `includes/class-srp-frontend.php` — `SRP_Frontend` class; registers shortcodes (`[srp_search]`, `[srp_my_applications]`), enqueues CSS/JS/CDN assets, adds role-based body classes, protects dashboard pages, handles login redirects
- `assets/css/leaselink.css` — baseline plugin stylesheet (variables, card components, dashboard layout, badges, forms)
- `assets/js/leaselink-frontend.js` — frontend JS (Fancybox init, Leaflet maps, search form HTMX behavior)
- `templates/single-cpt_listing.php` — default single listing template
- `templates/dashboard/` — 7 dashboard templates (landlord-dashboard, student-dashboard, my-properties, add-property, my-applications, landlord-applications, saved-listings)
- `templates/shortcodes/search.php` — search shortcode template
- `templates/components/` — 5 reusable partials (badge, dashboard-nav, empty-state, listing-card, stat-card)

### Theme Changes (`tailpress`, branch `master`)

**Modified:**

- `functions.php` — removed 115 lines of plugin-coupled code (HTMX/Leaflet/Fancybox enqueuing, body classes, dashboard protection, login redirect). Replaced with thin `leaselink_get_dashboard_url()` helper that delegates to plugin.

**Created:**

- `leaselink/` — theme template overrides directory with 14 files mirroring plugin defaults (ready for TailPress-specific styling)

## Next Steps

1. **Style theme overrides:** Update `tailpress/leaselink/` templates with TailPress/Tailwind classes for a polished look — start with `single-cpt_listing.php` and dashboard templates
2. Add PHPUnit tests for workflows, REST endpoints, and template loader (§12)
3. Implement search & filtering frontend (Alpine.js + HTMX per §11) — wire up `[srp_search]` shortcode
4. Add geolocation search with PostGIS or meta-based radius queries (§9)
5. Build email templates with proper branding
6. Implement monetization features (§8: subscriptions, featured listing credits)
