# Project Blackboard — LeaseLink Core

> **Last Updated:** 2026-02-23  
> **Branch:** `main`  
> **Session Agent:** Antigravity

---

## Active Mission

Build a secure, scalable WordPress plugin for student rental housing marketplace with landlord verification, application management, and future monetization capabilities.

**Current Phase:** Core plugin architecture — CPTs, meta fields, REST API endpoints, and state machine workflows.

---

## Architecture Decisions

| Decision                | Choice                  | Rationale                                                       |
| ----------------------- | ----------------------- | --------------------------------------------------------------- |
| Frontend reactivity     | Alpine.js               | Lightweight, no build step, fits WordPress templating model     |
| Dynamic content loading | HTMX                    | Server-rendered partials avoid SPA complexity                   |
| CSS framework           | Tailwind CSS (via Vite) | Utility-first, rapid prototyping, consistent design tokens      |
| Caching layer           | Redis + Transients      | Object cache for queries, transients for expensive computations |
| State machines          | Custom PHP (post meta)  | WordPress-native, no external dependencies, simple transitions  |
| Currency/units          | EUR / m²                | European market — standardized across all templates and filters |
| Lightbox gallery        | Fancybox v5 (CDN)       | Rich thumbnails, toolbar controls, minimal JS overhead          |

---

## The 'Why' Log

- **`srp_` prefix everywhere**: Namespace isolation to prevent collisions with other plugins and themes.
- **Custom tables for applications**: `wp_rental_applications` — post meta would be too slow for the query patterns needed (filtering by student, status, date ranges).
- **Verification levels (0–4)**: Tiered trust system — levels 0–1 can't list, level 2 needs admin review, level 4+ auto-publishes. This prevents spam while rewarding trusted landlords.
- **Vite with stable filenames**: Production builds use `[name].js` / `[name].css` (no hashing) so WordPress `wp_enqueue_script` references remain static.

---

## Blocking Issues

_None currently._

---

## Technical Debt

- [ ] Unit test coverage is incomplete — test scaffolding exists but most workflows lack tests.
- [ ] No CI/CD pipeline configured yet.
- [ ] Redis caching strategy is planned but not yet implemented.
- [ ] Contact Form 7 styling is patched inline — should be extracted to a dedicated stylesheet.

---

## Current Context

**Last session work:**

- Initial `state.md` created as part of Git-Blackboard workflow integration.
- Git repository initialized for the `leaselink-core` plugin.
- Agent rules and `/handoff` workflow configured.

---

## Next Steps

1. Continue implementing CPT meta fields per `REQUIREMENTS.md` audit results.
2. Implement application submission workflow with state machine transitions.
3. Add PHPUnit tests for listing lifecycle (Draft → Pending → Published → Booked/Expired).
4. Set up `.gitignore` and make the first meaningful feature branch.
