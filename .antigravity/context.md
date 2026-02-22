# Antigravity AI - Development Context

## 🎯 Project Mission

Build a secure, scalable WordPress plugin for student rental housing marketplace with landlord verification, application management, and future monetization capabilities.

## 📚 Required Reading Order

1. **FIRST**: Read `docs/REQUIREMENTS.md` completely - contains ALL business logic
2. **SECOND**: Review `docs/ARCHITECTURE.md` for technical patterns
3. **THIRD**: Check `docs/API_SPEC.md` for endpoint specifications

## 🔧 Development Mode: WordPress Plugin

### Critical WordPress Rules

```php
// ALWAYS follow this pattern for security
function srp_example_function() {
    // 1. Check capabilities
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'Access denied', 'student-rental-platform' ) );
    }

    // 2. Verify nonce
    if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'srp_action' ) ) {
        wp_die( __( 'Security check failed', 'student-rental-platform' ) );
    }

    // 3. Sanitize inputs
    $data = sanitize_text_field( $_POST['data'] );

    // 4. Process
    $result = process_data( $data );

    // 5. Escape outputs
    echo '<div>' . esc_html( $result ) . '</div>';
}
```

### Naming Conventions

- **Functions**: `srp_function_name()` (lowercase, underscores)
- **Classes**: `SRP_Class_Name` (PascalCase, prefix)
- **Hooks**: `srp_hook_name` (lowercase, underscores)
- **Options**: `srp_option_name` (lowercase, underscores)
- **Post Meta**: `_srp_meta_key` (leading underscore for private)
- **Custom Tables**: `wp_rental_table_name` (wp* prefix + rental* namespace)

### File Organization

```
includes/
├── class-{name}.php          # One class per file
├── post-types/
│   └── class-property.php    # Custom Post Type
├── workflows/
│   └── class-application-workflow.php
└── rest-api/
    └── class-listings-endpoint.php
```

### State Machine Implementation Priority

Refer to REQUIREMENTS.md Section 3 - These are CRITICAL:

1. **Listing Lifecycle**: Draft → Pending → Published → Booked/Expired/Suspended
2. **Application Lifecycle**: Submitted → Under Review → Accepted/Rejected
3. **Verification Lifecycle**: Unverified → Pending → Approved/Rejected

**NEVER create invalid state transitions!**

### Database Operations

```php
// Custom table queries - ALWAYS use prepare()
global $wpdb;
$results = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}rental_applications
     WHERE student_id = %d AND application_status = %s",
    $student_id,
    'submitted'
) );

// Post meta - use WordPress functions
$price = get_post_meta( $listing_id, '_rent_price', true );
update_post_meta( $listing_id, '_rent_price', floatval( $price ) );
```

### REST API Pattern

```php
// Register endpoint
add_action( 'rest_api_init', function() {
    register_rest_route( 'rental/v1', '/listings', [
        'methods'             => 'GET',
        'callback'            => 'srp_get_listings',
        'permission_callback' => '__return_true', // Public endpoint
        'args'                => [
            'city' => [
                'required'          => false,
                'validate_callback' => function( $param ) {
                    return is_string( $param );
                },
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ],
    ] );
} );

// Callback function
function srp_get_listings( WP_REST_Request $request ) {
    $city = $request->get_param( 'city' );

    // Query logic
    $listings = get_listings_by_city( $city );

    return new WP_REST_Response( $listings, 200 );
}
```

### Security Checklist (Auto-verify before suggesting code)

- [ ] Capability check: `current_user_can()`
- [ ] Nonce verification: `wp_verify_nonce()`
- [ ] Input sanitization: `sanitize_*()` functions
- [ ] Output escaping: `esc_html()`, `esc_url()`, `esc_attr()`
- [ ] SQL preparation: `$wpdb->prepare()`
- [ ] File validation: Type, size, content checks

### Performance Patterns

```php
// GOOD: Cache expensive queries
$listings = get_transient( 'srp_featured_listings' );
if ( false === $listings ) {
    $listings = get_featured_listings(); // Expensive query
    set_transient( 'srp_featured_listings', $listings, HOUR_IN_SECONDS );
}

// BAD: N+1 query problem
foreach ( $listings as $listing ) {
    $price = get_post_meta( $listing->ID, '_rent_price', true ); // Query per iteration
}

// GOOD: Batch query
update_post_meta_cache( wp_list_pluck( $listings, 'ID' ) ); // Single query
foreach ( $listings as $listing ) {
    $price = get_post_meta( $listing->ID, '_rent_price', true ); // From cache
}
```

### Testing Requirements

Every feature needs a test:

```php
// tests/unit/test-application-workflow.php
class Application_Workflow_Test extends WP_UnitTestCase {
    public function test_student_cannot_apply_to_booked_unit() {
        $unit_id = $this->create_booked_unit();
        $student_id = $this->factory->user->create( [ 'role' => 'student' ] );

        $result = srp_submit_application( $student_id, $unit_id );

        $this->assertWPError( $result );
        $this->assertEquals( 'unit_unavailable', $result->get_error_code() );
    }
}
```

## 🚨 Common Mistakes to PREVENT

### 1. Direct Superglobal Access

```php
// ❌ WRONG
$email = $_POST['email'];

// ✅ CORRECT
$email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
```

### 2. Missing Capability Checks

```php
// ❌ WRONG
function srp_delete_listing( $listing_id ) {
    wp_delete_post( $listing_id );
}

// ✅ CORRECT
function srp_delete_listing( $listing_id ) {
    if ( ! current_user_can( 'delete_listings' ) ) {
        return new WP_Error( 'unauthorized', __( 'Access denied', 'student-rental-platform' ) );
    }

    wp_delete_post( $listing_id );
}
```

### 3. Unescaped Output

```php
// ❌ WRONG
echo '<h1>' . $title . '</h1>';

// ✅ CORRECT
echo '<h1>' . esc_html( $title ) . '</h1>';
```

### 4. SQL Injection Vulnerability

```php
// ❌ WRONG
$wpdb->query( "DELETE FROM wp_rental_applications WHERE id = $id" );

// ✅ CORRECT
$wpdb->query( $wpdb->prepare(
    "DELETE FROM {$wpdb->prefix}rental_applications WHERE id = %d",
    $id
) );
```

## 📖 Business Logic Reference

### Application Submission Rules (REQUIREMENTS.md Section 4.1)

1. Student can only apply to **Published** listings
2. Cannot apply to own listings
3. Maximum 10 active applications per student
4. Unit must be **available** status
5. Move-in date must be within unit's available window

### Booking Logic (REQUIREMENTS.md Section 4.1)

When landlord accepts application:

1. Update application status to 'accepted'
2. Update unit status to 'booked'
3. Update availability calendar
4. Reject all other pending applications for that unit
5. Hide listing from search results
6. Send email notifications

### Verification Requirements (REQUIREMENTS.md Section 3.3)

- Level 0-1: Cannot create listings
- Level 2: Can submit for admin review
- Level 4+: Can auto-publish (post-moderation)
- Annual reverification required for Level 4+

## 🎨 Frontend Patterns

### Alpine.js for Reactivity

```html
<div x-data="{ open: false }">
  <button @click="open = !open">Toggle</button>
  <div x-show="open">Content</div>
</div>
```

### HTMX for Dynamic Content

```html
<button
  hx-post="/wp-json/rental/v1/applications"
  hx-target="#result"
  hx-swap="innerHTML"
>
  Apply Now
</button>
<div id="result"></div>
```

### Tailwind CSS (Utility-First)

```html
<div class="flex items-center justify-between p-4 bg-white rounded-lg shadow">
  <h2 class="text-xl font-bold text-gray-900">Listing Title</h2>
  <span class="text-2xl font-semibold text-blue-600">$800/mo</span>
</div>
```

## 🔍 Code Review Checklist

Before suggesting any code, verify:

1. [ ] Follows WordPress coding standards (tabs, spacing, naming)
2. [ ] Has capability checks for protected operations
3. [ ] Uses nonces for form submissions
4. [ ] Sanitizes all inputs
5. [ ] Escapes all outputs
6. [ ] Uses $wpdb->prepare() for custom queries
7. [ ] Respects state machine rules from REQUIREMENTS.md
8. [ ] Has PHPDoc comments
9. [ ] Includes error handling
10. [ ] Has corresponding unit test

## 💡 When Asking for Code

### Good Prompt Structure:

```
"Following REQUIREMENTS.md Section 3.2 (Application Lifecycle),
create the function to accept an application. Include:
- Capability check (landlord must own the unit)
- State validation (application must be 'under_review')
- Auto-reject other pending applications
- Update unit availability
- Send email notifications
- Include unit test"
```

### Bad Prompt (too vague):

```
"Create application function"
```

## 🚀 Development Workflow

1. **Read REQUIREMENTS.md section** for feature specification
2. **Design database schema** (if needed)
3. **Write unit test first** (TDD approach)
4. **Implement functionality** following WordPress standards
5. **Manual test** on local WordPress install
6. **Security review** against checklist
7. **Performance check** (Query Monitor plugin)
8. **Commit** with descriptive message

## 📞 When You Need Help

If unclear about:

- **Business logic** → Reference specific REQUIREMENTS.md section number
- **WordPress way** → Check WordPress Developer Handbook
- **State transitions** → Refer to REQUIREMENTS.md Section 3 (State Machines)
- **Security pattern** → Use examples in this file
- **Performance** → Check REQUIREMENTS.md Section 6

## ⚡ Quick Reference

### Sanitization Functions

- `sanitize_text_field()` - General text
- `sanitize_email()` - Email addresses
- `sanitize_url()` - URLs
- `sanitize_key()` - Keys/slugs
- `wp_kses_post()` - HTML content
- `absint()` - Positive integers
- `floatval()` - Numbers with decimals

### Escaping Functions

- `esc_html()` - Text content
- `esc_attr()` - HTML attributes
- `esc_url()` - URLs
- `esc_js()` - JavaScript
- `esc_textarea()` - Textarea content

### Capability Checks

- `current_user_can( 'manage_options' )` - Admin
- `current_user_can( 'edit_listings' )` - Custom capability
- `current_user_can( 'edit_post', $post_id )` - Specific post

### Database Access

- `$wpdb->get_results()` - Multiple rows
- `$wpdb->get_row()` - Single row
- `$wpdb->get_var()` - Single value
- `$wpdb->insert()` - Insert row
- `$wpdb->update()` - Update row
- `$wpdb->delete()` - Delete row

---

## 🗂️ Git-Backed State Management (Blackboard Pattern)

The project uses a **Git-Backed Blackboard** (`docs/state.md`) as the single source of truth for agent state. This file is versioned, peer-reviewed, and synchronized across the team.

### Rules — Non-Negotiable

1. **After completing any task**, update `docs/state.md` before considering the work done.
2. The update must include:
   - **Current Context:** What was just changed.
   - **Technical Debt:** Any TODO items or hacks introduced.
   - **Next Steps:** Precise, actionable instructions for the next agent session.
3. Once updated, stage **both** the code changes AND `docs/state.md`.
4. Commit with the prefix: `agent(state): [Brief Description]`.
5. **Never update the blackboard directly on `main`** — always use feature branches.
6. When starting a new session, read `docs/state.md` first to understand the current project state.

### Context Injection (Session Start)

At the beginning of every session, run:

```bash
git log -p docs/state.md
```

This provides the full evolution history of the project's state — not just where it is now, but how it got there.

### Strategic Branching

- **Feature branches**: Maintain a local `docs/state.md` unique to that feature.
- **On merge**: `state.md` updates merge with the code. This allows any agent or human to pull `main` and immediately understand the project's global status.
- **Conflicts**: Git handles state conflicts the same way it handles code conflicts.

### Handoff Protocol

Use the `/handoff` workflow (`.agent/workflows/handoff.md`) at the end of each session. It:

1. Summarizes all work done
2. Updates `docs/state.md`
3. Commits and pushes the branch
4. Generates a **Resume String** for the next session

---

**Remember**: Security and WordPress standards are non-negotiable. When in doubt, choose the WordPress way.
