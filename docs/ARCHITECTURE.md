# System Architecture - Student Rental Platform

## High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                         Frontend Layer                       │
│  ┌─────────────┐  ┌──────────────┐  ┌──────────────────┐   │
│  │   Search    │  │   Student    │  │    Landlord      │   │
│  │  Interface  │  │  Dashboard   │  │   Dashboard      │   │
│  └─────────────┘  └──────────────┘  └──────────────────┘   │
│         │                 │                    │             │
│         └─────────────────┴────────────────────┘             │
│                           ↓                                  │
├─────────────────────────────────────────────────────────────┤
│                      REST API Layer                          │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────┐  │
│  │  Listings    │  │ Applications │  │   Messaging      │  │
│  │  Endpoint    │  │   Endpoint   │  │    Endpoint      │  │
│  └──────────────┘  └──────────────┘  └──────────────────┘  │
│         │                 │                    │             │
├─────────────────────────────────────────────────────────────┤
│                   Business Logic Layer                       │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────┐  │
│  │   Listing    │  │ Application  │  │  Verification    │  │
│  │  Workflow    │  │   Workflow   │  │    Workflow      │  │
│  └──────────────┘  └──────────────┘  └──────────────────┘  │
│         │                 │                    │             │
├─────────────────────────────────────────────────────────────┤
│                      Data Layer                              │
│  ┌──────────────────────────────────────────────────────┐   │
│  │              WordPress Database (MySQL)              │   │
│  │  ┌────────────┐  ┌─────────────┐  ┌──────────────┐  │   │
│  │  │ wp_posts   │  │ wp_postmeta │  │ Custom Tables│  │   │
│  │  │ (CPTs)     │  │ (Metadata)  │  │ (Transactional)│  │   │
│  │  └────────────┘  └─────────────┘  └──────────────┘  │   │
│  └──────────────────────────────────────────────────────┘   │
│                           ↓                                  │
│  ┌──────────────────────────────────────────────────────┐   │
│  │            Redis/Memcached (Object Cache)            │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

## Component Architecture

### Core Components

1. **Post Type Manager**
   - Registers: Property, Unit, Listing CPTs
   - Manages meta boxes and custom fields
   - Location: `includes/post-types/`

2. **Workflow Engine**
   - Handles state transitions
   - Validates business rules
   - Triggers events and notifications
   - Location: `includes/workflows/`

3. **REST API Controller**
   - Exposes endpoints
   - Handles authentication
   - Validates requests
   - Location: `includes/rest-api/`

4. **Notification System**
   - Email templates
   - Trigger management
   - Multi-channel support (email, SMS future)
   - Location: `includes/notifications/`

5. **Verification Manager**
   - Document upload
   - Level calculation
   - Admin review queue
   - Location: `includes/verification/`

### Data Flow Examples

#### Listing Publication Flow

```
Landlord Action → REST API → Workflow Validation
→ State Transition → Database Update → Cache Invalidation
→ Event Emission → Email Notification → Admin Queue
```

#### Application Submission Flow

```
Student Form → AJAX Request → REST API → Capability Check
→ Availability Validation → Database Insert → Calendar Update
→ Email to Landlord → Dashboard Update
```

## Design Patterns

### 1. State Machine Pattern

Used for: Listings, Applications, Verifications

```php
class SRP_Listing_Workflow {
    private $allowed_transitions = [
        'draft'          => ['pending_review'],
        'pending_review' => ['published', 'draft'],
        'published'      => ['booked', 'expired', 'reported', 'suspended'],
        'booked'         => ['archived'],
        'expired'        => ['archived', 'draft'],
    ];

    public function transition( $listing_id, $new_status ) {
        $current_status = get_post_status( $listing_id );

        if ( ! $this->is_valid_transition( $current_status, $new_status ) ) {
            return new WP_Error( 'invalid_transition' );
        }

        // Execute transition
        wp_update_post( [ 'ID' => $listing_id, 'post_status' => $new_status ] );

        // Trigger hooks
        do_action( "srp_listing_status_{$new_status}", $listing_id );
    }
}
```

### 2. Repository Pattern

Used for: Complex queries, data access abstraction

```php
class SRP_Application_Repository {
    public function find_by_student( $student_id, $status = null ) {
        global $wpdb;

        $where = $wpdb->prepare( 'student_id = %d', $student_id );

        if ( $status ) {
            $where .= $wpdb->prepare( ' AND application_status = %s', $status );
        }

        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}rental_applications WHERE {$where}"
        );
    }
}
```

### 3. Event-Driven Pattern

Used for: Notifications, analytics, extensibility

```php
// Emit events
do_action( 'srp_application_submitted', $application_id, $student_id, $unit_id );

// Listen to events
add_action( 'srp_application_submitted', 'srp_send_landlord_notification', 10, 3 );
add_action( 'srp_application_submitted', 'srp_track_analytics', 10, 3 );
add_action( 'srp_application_submitted', 'srp_update_lead_score', 10, 3 );
```

### 4. Factory Pattern

Used for: Object creation, dependency injection

```php
class SRP_Factory {
    public static function create_workflow( $type ) {
        switch ( $type ) {
            case 'listing':
                return new SRP_Listing_Workflow();
            case 'application':
                return new SRP_Application_Workflow();
            case 'verification':
                return new SRP_Verification_Workflow();
        }
    }
}
```

## Security Architecture

### Defense in Depth

1. **Input Layer**
   - Sanitize all $\_POST, $\_GET, $\_REQUEST
   - Validate data types and formats
   - Reject malformed requests

2. **Authentication Layer**
   - WordPress user authentication
   - JWT tokens for API (optional)
   - Session management

3. **Authorization Layer**
   - Capability checks
   - Resource ownership validation
   - Role-based access control

4. **Data Layer**
   - Prepared statements
   - Escaped outputs
   - Input validation

5. **Output Layer**
   - Context-aware escaping
   - Content Security Policy headers
   - XSS prevention

### Permission Matrix

| Resource             | Student         | Landlord    | Admin |
| -------------------- | --------------- | ----------- | ----- |
| View Listing         | Own + Published | All         | All   |
| Edit Listing         | ✗               | Own         | All   |
| Delete Listing       | ✗               | Own         | All   |
| Submit Application   | ✓               | ✗           | ✗     |
| Accept Application   | ✗               | Own Listing | All   |
| View Verification    | ✗               | Own         | All   |
| Approve Verification | ✗               | ✗           | ✓     |

## Performance Architecture

### Caching Strategy

**Level 1: Object Cache (Redis)**

- User sessions
- Query results
- Computed values
- TTL: 5-60 minutes

**Level 2: Transients**

- Expensive aggregations
- API responses
- Search facets
- TTL: 15 minutes - 1 hour

**Level 3: Page Cache**

- Static pages only
- Exclude: Dashboards, forms, checkout
- CDN: CloudFlare / Varnish

### Query Optimization

**Indexes Required:**

```sql
-- Listings search
CREATE INDEX idx_listing_search
ON wp_posts (post_type, post_status);

-- Price range
CREATE INDEX idx_price
ON wp_postmeta (meta_key, meta_value(10));

-- Geolocation
CREATE INDEX idx_coordinates
ON wp_postmeta (meta_key, meta_value(20));

-- Applications
CREATE INDEX idx_app_student_status
ON wp_rental_applications (student_id, application_status);
```

**Query Budget:**

- Homepage: 30 queries max
- Search page: 40 queries max
- Listing detail: 25 queries max
- Dashboard: 50 queries max

## Scalability Considerations

### Horizontal Scaling

- Stateless application design
- Shared session storage (Redis)
- CDN for static assets
- Database read replicas

### Vertical Scaling

- PHP 8.1+ (JIT compiler)
- MySQL 8.0+ (improved query optimizer)
- Redis for object cache
- OpCode caching (OPcache)

### Database Sharding (Future)

- Shard by geographic region
- Shard by user ID range
- Cross-shard queries via API layer

## Monitoring & Observability

### Metrics to Track

- Application response time
- Database query time
- Cache hit/miss ratio
- API error rates
- Background job queue depth

### Logging Strategy

- **Error logs**: PHP errors, exceptions
- **Audit logs**: State transitions, sensitive actions
- **Access logs**: API requests, authentication attempts
- **Performance logs**: Slow queries, memory usage

### Alerting Thresholds

- Response time > 2s
- Error rate > 1%
- Database connections > 80%
- Disk space < 20%
- Memory usage > 85%

## Deployment Architecture

### Environments

**Development**

- Local WordPress (Local WP / Docker)
- Development database
- Debug mode enabled
- Sample data loaded

**Staging**

- Production-like environment
- Anonymized production data
- External services (test keys)
- QA testing ground

**Production**

- Load balanced web servers
- Read replicas for database
- CDN for assets
- Full monitoring stack

### CI/CD Pipeline

```
Git Push → GitHub Actions
  ↓
Run Tests (PHPUnit, PHPCS)
  ↓
Build Assets (npm run build)
  ↓
Deploy to Staging
  ↓
Automated Tests (Selenium)
  ↓
Manual Approval
  ↓
Deploy to Production
  ↓
Health Check
  ↓
Rollback if Failed
```

## Technology Decisions

### Why WordPress?

- ✅ Mature CMS with strong ecosystem
- ✅ Built-in user management
- ✅ Rich plugin architecture
- ✅ SEO-friendly out of the box
- ✅ Large developer community

### Why Custom Tables for Applications?

- ✅ Better performance (no meta queries)
- ✅ Transactional integrity
- ✅ Easier complex queries
- ✅ Clearer data model

### Why Alpine.js over React?

- ✅ Smaller bundle size
- ✅ No build step required
- ✅ WordPress-friendly
- ✅ Progressive enhancement

### Why Redis over Memcached?

- ✅ Data persistence
- ✅ More data structures
- ✅ Better WordPress integration
- ✅ Session storage capability

---

**Document Version**: 1.0  
**Last Updated**: 2026-02-16  
**Review Cycle**: Quarterly
