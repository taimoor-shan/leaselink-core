# WordPress Student Rental Housing Platform - Development Skill

## Overview

This skill provides the complete domain model, architecture patterns, and development standards for building a student rental housing marketplace on WordPress. Use this as the authoritative reference for all platform development decisions.

---

## 1. SYSTEM ARCHITECTURE

### 1.1 Core Actors & Capabilities

#### Student (`wp_user` role: `student`)

**Primary Actions:**

- Browse and search listings (public + authenticated)
- Apply for rental units
- Send/receive messages with landlords
- Save favorite listings
- Report inappropriate listings
- Manage application history

**Access Level:** Authenticated user with limited backend access

#### Landlord (`wp_user` role: `landlord`)

**Primary Actions:**

- Create and manage properties
- Add rental units to properties
- Upload verification documents (ID, proof of ownership)
- Publish listings (after verification)
- Review and respond to applications
- Message applicants
- View analytics dashboard

**Access Level:** Authenticated user with custom post management capabilities

#### Administrator (`wp_user` role: `administrator`)

**Primary Actions:**

- Verify landlord identity and documentation
- Approve/reject listings
- Suspend listings or users
- Moderate reports and disputes
- Manage subscription plans
- System configuration and settings

**Access Level:** Full WordPress admin access + custom moderation tools

---

## 2. DATA MODEL

### 2.1 Custom Post Types

#### Property (`cpt_property`)

Represents a physical building or residence.

**Fields:**

- `ID` (post_id)
- `post_author` (landlord_id reference to wp_users)
- `post_title` (property name)
- `post_content` (property description)
- `post_status` (draft, publish, suspended)

**Custom Meta Fields:**

- `_property_address` (text)
- `_property_city` (text)
- `_property_state` (text)
- `_property_country` (text - ISO code)
- `_property_postal_code` (text)
- `_property_latitude` (float)
- `_property_longitude` (float)
- `_property_type` (taxonomy: apartment, house, dormitory)
- `_verification_status` (enum: unverified, pending, verified, rejected)
- `_verification_notes` (textarea)
- `_created_at` (datetime)
- `_updated_at` (datetime)

**Relationships:**

- One Property → Many Units (parent-child relationship)
- One Landlord → Many Properties (post_author)

**Capabilities Required:**

- `edit_properties`
- `edit_published_properties`
- `delete_properties`

#### Unit (`cpt_unit`)

Represents an individual rentable space within a property.

**Fields:**

- `ID` (post_id)
- `post_parent` (property_id reference to cpt_property)
- `post_title` (unit identifier, e.g., "Unit 2B")
- `post_content` (unit description)
- `post_status` (draft, publish, booked, unavailable)

**Custom Meta Fields:**

- `_unit_property_id` (post_id reference)
- `_rent_price` (decimal - monthly rent)
- `_currency` (text - ISO currency code)
- `_deposit_amount` (decimal)
- `_available_from` (date)
- `_available_to` (date - nullable)
- `_gender_preference` (enum: male, female, any, female_only)
- `_lease_duration_min` (int - months)
- `_lease_duration_max` (int - months)
- `_max_occupancy` (int)
- `_furnished_status` (enum: furnished, unfurnished, partially_furnished)
- `_room_type` (taxonomy: private_room, shared_room, entire_unit)
- `_square_footage` (int - nullable)
- `_floor_number` (int - nullable)
- `_amenities` (taxonomy: wifi, parking, laundry, gym, etc.)
- `_utilities_included` (serialized array: electricity, water, internet, heating)
- `_pet_policy` (enum: allowed, not_allowed, negotiable)
- `_smoking_policy` (enum: allowed, not_allowed, outside_only)
- `_availability_status` (enum: available, booked, hold, unavailable)
- `_created_at` (datetime)
- `_updated_at` (datetime)

**Relationships:**

- One Unit → One Property (post_parent)
- One Unit → Many Applications (custom table)
- One Unit → One Availability Calendar (custom table)

**Capabilities Required:**

- `edit_units`
- `edit_published_units`
- `delete_units`

#### Listing (`cpt_listing`)

Public-facing representation of a Unit with additional publishing controls.

**Fields:**

- `ID` (post_id)
- `post_title` (auto-generated from unit + property)
- `post_content` (combined description)
- `post_status` (draft, pending, publish, expired, suspended)

**Custom Meta Fields:**

- `_listing_unit_id` (post_id reference to cpt_unit)
- `_listing_property_id` (post_id reference to cpt_property)
- `_publish_status` (enum: draft, pending_review, published, expired)
- `_verification_status` (enum: unverified, approved, rejected)
- `_admin_notes` (textarea - internal only)
- `_featured_flag` (boolean - for paid promotion)
- `_featured_expires_at` (datetime - nullable)
- `_view_count` (int - incremented on page view)
- `_application_count` (int - cached count)
- `_listing_expiry_date` (datetime)
- `_published_at` (datetime)
- `_suspended_at` (datetime - nullable)
- `_suspension_reason` (text - nullable)
- `_created_at` (datetime)
- `_updated_at` (datetime)

**Relationships:**

- One Listing → One Unit (required)
- One Listing → One Property (denormalized for query performance)

**Capabilities Required:**

- `edit_listings`
- `publish_listings`
- `moderate_listings` (admin only)

---

### 2.2 Custom Database Tables

#### Applications Table (`wp_rental_applications`)

```sql
CREATE TABLE wp_rental_applications (
    application_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    unit_id BIGINT UNSIGNED NOT NULL,
    listing_id BIGINT UNSIGNED NOT NULL,
    application_status VARCHAR(20) DEFAULT 'submitted',
    message TEXT,
    move_in_date DATE,
    lease_duration INT,
    student_info JSON,
    landlord_notes TEXT,
    submitted_at DATETIME NOT NULL,
    reviewed_at DATETIME,
    decision_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_student (student_id),
    INDEX idx_unit (unit_id),
    INDEX idx_status (application_status),
    INDEX idx_submitted (submitted_at),

    FOREIGN KEY (student_id) REFERENCES wp_users(ID) ON DELETE CASCADE,
    FOREIGN KEY (unit_id) REFERENCES wp_posts(ID) ON DELETE CASCADE,
    FOREIGN KEY (listing_id) REFERENCES wp_posts(ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Application Statuses:**

- `submitted` - Initial state
- `under_review` - Landlord viewing
- `accepted` - Approved by landlord
- `rejected` - Declined by landlord
- `withdrawn` - Cancelled by student
- `expired` - Auto-expired after X days

#### Availability Calendar (`wp_rental_availability`)

```sql
CREATE TABLE wp_rental_availability (
    calendar_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    unit_id BIGINT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    availability_flag TINYINT(1) DEFAULT 1,
    booking_type VARCHAR(20),
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_unit_date (unit_id, date),
    INDEX idx_date_range (unit_id, date),

    FOREIGN KEY (unit_id) REFERENCES wp_posts(ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Booking Types:**

- `available` - Open for applications
- `hold` - Temporarily reserved
- `booked` - Confirmed booking
- `maintenance` - Unavailable for maintenance
- `blocked` - Manually blocked by landlord

#### Landlord Verification (`wp_landlord_verification`)

```sql
CREATE TABLE wp_landlord_verification (
    verification_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    landlord_id BIGINT UNSIGNED NOT NULL,
    verification_type VARCHAR(50) NOT NULL,
    document_path VARCHAR(255),
    document_type VARCHAR(50),
    verification_status VARCHAR(20) DEFAULT 'pending',
    verification_level INT DEFAULT 0,
    reviewed_by BIGINT UNSIGNED,
    admin_notes TEXT,
    submitted_at DATETIME NOT NULL,
    reviewed_at DATETIME,
    expires_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_landlord (landlord_id),
    INDEX idx_status (verification_status),

    FOREIGN KEY (landlord_id) REFERENCES wp_users(ID) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES wp_users(ID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Verification Types:**

- `email` - Email verification
- `phone` - Phone number verification
- `government_id` - ID card/passport
- `proof_of_ownership` - Property deed/lease agreement
- `business_license` - For commercial landlords

**Verification Statuses:**

- `unverified` - No verification attempted
- `pending` - Documents submitted, awaiting review
- `approved` - Verified and approved
- `rejected` - Verification failed
- `expired` - Verification expired (annual renewal)
- `suspended` - Account suspended

#### Messaging System (`wp_rental_messages`)

```sql
CREATE TABLE wp_rental_messages (
    message_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    thread_id VARCHAR(64) NOT NULL,
    sender_id BIGINT UNSIGNED NOT NULL,
    recipient_id BIGINT UNSIGNED NOT NULL,
    listing_id BIGINT UNSIGNED,
    message_content TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    read_at DATETIME,
    parent_message_id BIGINT UNSIGNED,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_thread (thread_id),
    INDEX idx_recipient (recipient_id, is_read),
    INDEX idx_created (created_at),

    FOREIGN KEY (sender_id) REFERENCES wp_users(ID) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES wp_users(ID) ON DELETE CASCADE,
    FOREIGN KEY (listing_id) REFERENCES wp_posts(ID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Thread ID Format:** `{student_id}_{landlord_id}_{listing_id}`

#### Saved Listings (`wp_rental_saved_listings`)

```sql
CREATE TABLE wp_rental_saved_listings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    listing_id BIGINT UNSIGNED NOT NULL,
    saved_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,

    UNIQUE KEY unique_student_listing (student_id, listing_id),
    INDEX idx_student (student_id),

    FOREIGN KEY (student_id) REFERENCES wp_users(ID) ON DELETE CASCADE,
    FOREIGN KEY (listing_id) REFERENCES wp_posts(ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Reports & Moderation (`wp_rental_reports`)

```sql
CREATE TABLE wp_rental_reports (
    report_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reporter_id BIGINT UNSIGNED NOT NULL,
    reported_entity_type VARCHAR(20) NOT NULL,
    reported_entity_id BIGINT UNSIGNED NOT NULL,
    report_category VARCHAR(50) NOT NULL,
    report_description TEXT,
    report_status VARCHAR(20) DEFAULT 'pending',
    moderator_id BIGINT UNSIGNED,
    moderator_notes TEXT,
    action_taken VARCHAR(100),
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME,

    INDEX idx_entity (reported_entity_type, reported_entity_id),
    INDEX idx_status (report_status),

    FOREIGN KEY (reporter_id) REFERENCES wp_users(ID) ON DELETE CASCADE,
    FOREIGN KEY (moderator_id) REFERENCES wp_users(ID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Report Categories:**

- `spam` - Spam or duplicate listing
- `fraud` - Fraudulent or fake listing
- `inappropriate` - Inappropriate content
- `discrimination` - Discriminatory practices
- `safety` - Safety concerns
- `other` - Other issues

---

### 2.3 Taxonomies

#### Property Type (`property_type`)

- Apartment
- House
- Condo
- Dormitory
- Shared House
- Studio

#### Room Type (`room_type`)

- Private Room
- Shared Room
- Entire Unit
- Studio

#### Amenities (`amenity`)

- WiFi
- Parking
- Laundry (In-Unit)
- Laundry (Shared)
- Gym
- Pool
- Air Conditioning
- Heating
- Dishwasher
- Balcony
- Elevator
- Security System
- Bike Storage
- Study Room
- Kitchen Access

---

## 3. STATE MACHINES & WORKFLOWS

### 3.1 Listing Publication Workflow

```
┌─────────────┐
│    Draft     │  (Landlord creates listing)
└──────┬──────┘
       │
       ↓  (Landlord submits for review)
┌─────────────────┐
│  Pending Review  │  (Admin notification sent)
└──────┬──────────┘
       │
       ├──→ [Admin Approves] ──→ ┌───────────┐
       │                         │ Published  │
       │                         └─────┬─────┘
       │                               │
       └──→ [Admin Rejects] ───→ ┌────────┐
                                 │  Draft  │  (Back to landlord)
                                 └────────┘

Published State Transitions:
       │
       ├──→ [Unit Booked] ──→ ┌────────┐
       │                      │ Booked  │
       │                      └────────┘
       │
       ├──→ [Expiry Date] ──→ ┌─────────┐
       │                      │ Expired  │
       │                      └────────┘
       │
       ├──→ [Report Filed] ──→ ┌──────────┐
       │                       │ Reported  │
       │                       └─────┬────┘
       │                             │
       │                 ├──→ [Admin Suspends] ──→ ┌───────────┐
       │                 │                         │ Suspended  │
       │                 │                         └───────────┘
       │                 │
       │                 └──→ [Admin Dismisses] ──→ Back to Published
       │
       └──→ [Manual Archive] ──→ ┌──────────┐
                                 │ Archived  │
                                 └──────────┘
```

**Business Rules:**

- Only verified landlords can submit for review
- Listings auto-expire after 90 days (configurable)
- Suspended listings are hidden from all searches
- Landlords can resubmit rejected listings after corrections

---

### 3.2 Application Lifecycle

```
┌───────────┐
│ Submitted  │  (Student applies)
└─────┬─────┘
      │
      ↓  (Landlord views application)
┌──────────────┐
│ Under Review  │
└──────┬───────┘
       │
       ├──→ [Landlord Accepts] ──→ ┌──────────┐
       │                           │ Accepted  │ → Unit status = "Booked"
       │                           └──────────┘
       │
       ├──→ [Landlord Rejects] ──→ ┌──────────┐
       │                           │ Rejected  │
       │                           └──────────┘
       │
       ├──→ [Student Withdraws] ──→ ┌───────────┐
       │                            │ Withdrawn  │
       │                            └───────────┘
       │
       └──→ [Auto-expire after 14 days] ──→ ┌─────────┐
                                            │ Expired  │
                                            └─────────┘
```

**Business Rules:**

- Students can apply to maximum 10 active units simultaneously
- Only one application per student per unit allowed
- Accepted applications auto-book the unit
- Rejected/withdrawn applications free up the slot
- Email notifications sent on all status changes

---

### 3.3 Landlord Verification Levels

```
Level 0: Unverified
  └──→ [Email Confirmed] ──→ Level 1: Email Verified
        └──→ [Phone Confirmed] ──→ Level 2: Phone Verified
              └──→ [ID Submitted] ──→ Level 3: Document Pending
                    └──→ [Admin Approves] ──→ Level 4: Fully Verified
                          └──→ [Ownership Verified] ──→ Level 5: Premium Verified
```

**Verification Requirements by Level:**

- **Level 0:** Cannot create listings
- **Level 1:** Can create draft listings only
- **Level 2:** Can submit listings for review
- **Level 3:** Pending admin approval
- **Level 4:** Can publish listings immediately (auto-approved)
- **Level 5:** Featured listing eligibility, higher search ranking

**Annual Renewal:** Level 4+ requires reverification every 12 months

---

## 4. BUSINESS RULES & CONSTRAINTS

### 4.1 Core Constraints

**Unit-Property Relationship:**

- Every Unit MUST belong to a Property
- Cannot create orphan Units
- Deleting Property cascades to Units (soft delete recommended)

**Listing-Unit Relationship:**

- One Listing per Unit (1:1 mapping)
- Listing inherits Unit availability rules
- Cannot publish Listing without verified Landlord

**Application Rules:**

- Students can only apply to Published listings
- Cannot apply to own listings
- Duplicate applications blocked at DB level
- Application requires move-in date within unit's available window

**Booking & Availability:**

- Accepting an application automatically:
  - Changes Unit status to "Booked"
  - Rejects all other pending applications for that Unit
  - Updates availability calendar
  - Hides Listing from search results

**Verification Gates:**

- Level 0-1: No listing creation
- Level 2: Draft + submit for review
- Level 4+: Auto-publish (post-moderation)

**Suspension Rules:**

- Suspended listings hidden from all users except Admin
- Landlord notified via email with suspension reason
- Can appeal suspension through admin panel

### 4.2 Data Integrity Rules

**Prevent:**

- Circular property references
- Future-dated availability before `available_from`
- Rent price <= 0
- Applications on expired listings
- Double-booking units

**Enforce:**

- Valid email/phone before verification submission
- Min/max lease duration validation
- Geocoding validation for addresses
- Image upload limits (max 20 per listing)

---

## 5. SECURITY & PRIVACY

### 5.1 Data Sensitivity Classification

**Public Data (No Auth Required):**

- Listing title, description, rent price
- Property location (city level only)
- Amenities, room type, furnishing status
- Aggregated view counts

**Authenticated Data (Login Required):**

- Exact property address (shown only after inquiry)
- Landlord contact information (revealed only through messaging)
- Application submission forms
- Saved listings

**Sensitive Data (Role-Restricted):**

- **Landlord Only:** Application details, student contact info
- **Student Only:** Own application history, messages
- **Admin Only:** Verification documents, reports, moderation logs

**Highly Sensitive (Encrypted at Rest):**

- Government IDs
- Proof of ownership documents
- Payment information (future)
- SSN or equivalent (if collected)

### 5.2 Access Control Matrix

| Action                     | Student       | Landlord       | Admin          |
| -------------------------- | ------------- | -------------- | -------------- |
| View Published Listings    | ✓             | ✓              | ✓              |
| View Exact Address         | After inquiry | Own properties | ✓              |
| Apply to Listing           | ✓             | ✗              | ✗              |
| Create Property            | ✗             | ✓ (verified)   | ✓              |
| Manage Units               | ✗             | ✓ (own)        | ✓              |
| View Applications          | Own only      | Own listings   | ✓              |
| Accept/Reject Applications | ✗             | ✓ (own)        | ✓              |
| Access Messages            | Own threads   | Own threads    | ✓ (moderation) |
| Upload Verification Docs   | ✗             | ✓              | ✓              |
| Approve Listings           | ✗             | ✗              | ✓              |
| Suspend Users/Listings     | ✗             | ✗              | ✓              |
| View Reports               | ✗             | Own listings   | ✓              |

### 5.3 WordPress Capabilities

**Student Role:**

```php
'read'                => true,
'read_listings'       => true,
'submit_applications' => true,
'send_messages'       => true,
'save_listings'       => true,
'report_content'      => true
```

**Landlord Role:**

```php
'read'                       => true,
'read_listings'              => true,
'edit_properties'            => true,
'edit_published_properties'  => true,
'delete_properties'          => true,
'edit_units'                 => true,
'edit_published_units'       => true,
'delete_units'               => true,
'edit_listings'              => true,
'publish_listings'           => true,
'manage_applications'        => true,
'upload_verification_docs'   => true,
'view_analytics'             => true
```

**Administrator Role:**

All WordPress admin capabilities +

```php
'moderate_listings'    => true,
'verify_landlords'     => true,
'suspend_users'        => true,
'manage_reports'       => true,
'manage_subscriptions' => true,
'view_all_analytics'   => true
```

---

## 6. PERFORMANCE & SCALABILITY

### 6.1 Database Indexing Strategy

**Critical Indexes:**

```sql
-- Listings search query optimization
CREATE INDEX idx_listing_search ON wp_posts (post_type, post_status);
CREATE INDEX idx_listing_city ON wp_postmeta (meta_key, meta_value(50))
    WHERE meta_key = '_property_city';
CREATE INDEX idx_listing_price ON wp_postmeta (meta_key, meta_value)
    WHERE meta_key = '_rent_price';
CREATE INDEX idx_listing_available ON wp_postmeta (meta_key, meta_value)
    WHERE meta_key = '_available_from';

-- Geolocation queries
CREATE SPATIAL INDEX idx_property_location ON wp_postmeta (lat_lng_point);

-- Application queries
CREATE INDEX idx_app_student_status ON wp_rental_applications (student_id, application_status);
CREATE INDEX idx_app_unit_status ON wp_rental_applications (unit_id, application_status);

-- Messaging queries
CREATE INDEX idx_msg_unread ON wp_rental_messages (recipient_id, is_read, created_at);
```

### 6.2 Caching Strategy

**Object Caching (Redis/Memcached):**

- Listing search results (5 min TTL)
- User verification status (15 min TTL)
- Featured listings (1 hour TTL)
- Property metadata (until update)

**Transient Caching:**

- Homepage featured listings: 30 min
- Search facet counts: 15 min
- User application counts: 5 min

**Page Caching Exclusions:**

- User dashboard pages
- Messaging interface
- Application forms
- Admin panels

### 6.3 Query Optimization

**Avoid N+1 Queries:**

```php
// BAD: N+1 query
$listings = get_posts(['post_type' => 'cpt_listing']);
foreach ($listings as $listing) {
    $price = get_post_meta($listing->ID, '_rent_price', true); // Query per listing
}

// GOOD: Batch query
$listings = get_posts(['post_type' => 'cpt_listing']);
$listing_ids = wp_list_pluck($listings, 'ID');
$prices = get_post_meta_batch($listing_ids, '_rent_price'); // Single query
```

**Use WP_Query efficiently:**

```php
// Include necessary meta in single query
$args = [
    'post_type'                => 'cpt_listing',
    'posts_per_page'           => 20,
    'meta_query'               => [/* ... */],
    'update_post_meta_cache'   => true, // Pre-load meta
    'update_post_term_cache'   => true, // Pre-load terms
];
```

---

## 7. API ENDPOINTS & ROUTES

### 7.1 REST API Structure

**Base:** `/wp-json/rental/v1/`

#### Public Endpoints (No Auth)

```
GET    /listings
       Query params: city, min_price, max_price, gender, furnished, amenities[], page, per_page
       Response: Paginated listings with property data

GET    /listings/{id}
       Response: Full listing details (address masked until inquiry)

POST   /contact
       Body: {listing_id, name, email, phone, message}
       Response: Creates message thread, sends email to landlord
```

#### Student Endpoints (Auth Required)

```
POST   /applications
       Body: {unit_id, move_in_date, lease_duration, message, student_info}
       Response: Creates application, sends notification

GET    /applications/mine
       Response: User's application history with statuses

PATCH  /applications/{id}
       Body: {status: 'withdrawn'}
       Response: Cancels application

POST   /listings/{id}/save
       Response: Adds to saved listings

DELETE /listings/{id}/save
       Response: Removes from saved listings

GET    /saved-listings
       Response: User's saved listings

GET    /messages
       Response: Message threads

POST   /messages
       Body: {thread_id, recipient_id, message_content, listing_id?}
       Response: Sends message

POST   /reports
       Body: {entity_type, entity_id, category, description}
       Response: Creates moderation report
```

#### Landlord Endpoints (Auth Required)

```
POST   /properties
       Body: {title, address, city, coordinates, property_type, description}
       Response: Creates property

PATCH  /properties/{id}
       Body: {/* updatable fields */}

POST   /properties/{id}/units
       Body: {rent_price, furnished, amenities[], available_from, ...}
       Response: Creates unit

PATCH  /units/{id}
       Body: {/* updatable fields */}

POST   /units/{id}/publish
       Response: Creates listing, submits for review

GET    /applications
       Query params: status, property_id, unit_id
       Response: Applications for landlord's units

PATCH  /applications/{id}
       Body: {status: 'accepted' | 'rejected', notes}
       Response: Updates application, triggers booking logic

POST   /verification/upload
       Body: FormData with document file
       Response: Creates verification record

GET    /analytics/dashboard
       Response: Views, applications, booking rates
```

#### Admin Endpoints (Admin Auth)

```
GET    /admin/listings/pending
       Response: Listings awaiting review

PATCH  /admin/listings/{id}/approve
       Response: Publishes listing

PATCH  /admin/listings/{id}/reject
       Body: {reason}
       Response: Sends back to draft

PATCH  /admin/listings/{id}/suspend
       Body: {reason}
       Response: Hides listing, notifies landlord

GET    /admin/verifications/pending
       Response: Pending verification requests

PATCH  /admin/verifications/{id}
       Body: {status: 'approved' | 'rejected', notes}
       Response: Updates verification level

GET    /admin/reports
       Query params: status, category, entity_type
       Response: Moderation reports

PATCH  /admin/reports/{id}
       Body: {status, action_taken, notes}
       Response: Resolves report
```

### 7.2 Webhooks & Event System

**Events Emitted (for future extensibility):**

```php
// Listing events
do_action('rental_listing_published', $listing_id, $landlord_id);
do_action('rental_listing_viewed', $listing_id, $user_id);
do_action('rental_listing_featured', $listing_id, $expires_at);

// Application events
do_action('rental_application_submitted', $application_id, $student_id, $unit_id);
do_action('rental_application_accepted', $application_id, $landlord_id);
do_action('rental_application_rejected', $application_id, $reason);

// Verification events
do_action('rental_landlord_verified', $landlord_id, $verification_level);
do_action('rental_verification_rejected', $landlord_id, $reason);

// Booking events
do_action('rental_unit_booked', $unit_id, $application_id);
do_action('rental_unit_available', $unit_id);

// Moderation events
do_action('rental_listing_reported', $listing_id, $report_id);
do_action('rental_listing_suspended', $listing_id, $reason);
```

**Use Cases:**

- Email notifications
- SMS alerts (Twilio integration)
- Analytics tracking (Google Analytics, Mixpanel)
- Billing triggers (for subscriptions)
- Trust score calculation
- Lead tracking (for monetization)

---

## 8. MONETIZATION READINESS

### 8.1 Subscription Plans (Future)

**Database Structure:**

```sql
CREATE TABLE wp_rental_subscriptions (
    subscription_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    landlord_id BIGINT UNSIGNED NOT NULL,
    plan_type VARCHAR(20) NOT NULL,
    status VARCHAR(20) DEFAULT 'active',
    billing_interval VARCHAR(20),
    price DECIMAL(10,2),
    currency VARCHAR(3),
    started_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    auto_renew TINYINT(1) DEFAULT 1,
    stripe_subscription_id VARCHAR(100),

    FOREIGN KEY (landlord_id) REFERENCES wp_users(ID) ON DELETE CASCADE
);
```

**Plan Tiers:**

- **Basic (Free):** 3 listings, standard support
- **Professional ($49/month):** 15 listings, priority support, analytics
- **Premium ($99/month):** Unlimited listings, featured placement, API access

---

### 8.2 Featured Listings

**Meta Fields:**

- `_featured_flag` (boolean)
- `_featured_expires_at` (datetime)
- `_featured_placement` (enum: homepage, category, search_top)
- `_featured_boost_score` (int - for ranking algorithm)

**Pricing Model:**

- $10 per listing per week (homepage feature)
- $5 per listing per week (category feature)
- Automatically expires and falls back to standard ranking

### 8.3 Lead Unlock Credits

**Use Case:** Students must spend credits to unlock landlord contact info

**Database:**

```sql
CREATE TABLE wp_rental_lead_unlocks (
    unlock_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    listing_id BIGINT UNSIGNED NOT NULL,
    credits_spent INT DEFAULT 1,
    unlocked_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_student_listing (student_id, listing_id)
);

CREATE TABLE wp_rental_credit_balance (
    user_id BIGINT UNSIGNED PRIMARY KEY,
    credits INT DEFAULT 0,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES wp_users(ID) ON DELETE CASCADE
);
```

**Credit Purchase Packages:**

- 10 credits: $9.99
- 25 credits: $19.99
- 50 credits: $34.99

### 8.4 Commission Model (Alternative)

**Structure:**

- Platform takes 5% of first month's rent on successful booking
- Tracked via `rental_unit_booked` event
- Payout to landlord after 30-day hold period

**Database:**

```sql
CREATE TABLE wp_rental_transactions (
    transaction_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    landlord_id BIGINT UNSIGNED NOT NULL,
    application_id BIGINT UNSIGNED NOT NULL,
    gross_amount DECIMAL(10,2),
    commission_rate DECIMAL(5,2),
    commission_amount DECIMAL(10,2),
    net_payout DECIMAL(10,2),
    payout_status VARCHAR(20) DEFAULT 'pending',
    payout_date DATETIME,
    stripe_transfer_id VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

---

## 9. SEARCH & FILTERING

### 9.1 Search Implementation

**Primary Search Query:**

```php
$args = [
    'post_type'      => 'cpt_listing',
    'post_status'    => 'publish',
    'posts_per_page' => 24,
    'paged'          => $page,
    'meta_query'     => [
        'relation' => 'AND',

        // Price range
        [
            'key'     => '_rent_price',
            'value'   => [$min_price, $max_price],
            'type'    => 'NUMERIC',
            'compare' => 'BETWEEN'
        ],

        // Availability date
        [
            'key'     => '_available_from',
            'value'   => date('Y-m-d'),
            'type'    => 'DATE',
            'compare' => '<='
        ],

        // Availability status
        [
            'key'     => '_availability_status',
            'value'   => 'available',
            'compare' => '='
        ]
    ],

    'tax_query' => [
        'relation' => 'AND',

        // Room type
        [
            'taxonomy' => 'room_type',
            'field'    => 'slug',
            'terms'    => $room_types
        ],

        // Amenities (all required amenities must be present)
        [
            'taxonomy' => 'amenity',
            'field'    => 'slug',
            'terms'    => $amenities,
            'operator' => 'AND'
        ]
    ],

    // Sort by relevance + featured boost
    'orderby'  => [
        'meta_value_num' => 'DESC', // Featured score
        'date'           => 'DESC'
    ],
    'meta_key' => '_featured_boost_score'
];
```

### 9.2 Geolocation Search

**Radius Search (within X km of coordinates):**

```sql
SELECT p.ID, p.post_title,
    (6371 * acos(cos(radians(?)) * cos(radians(pm1.meta_value)) *
    cos(radians(pm2.meta_value) - radians(?)) +
    sin(radians(?)) * sin(radians(pm1.meta_value)))) AS distance
FROM wp_posts p
INNER JOIN wp_postmeta pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_property_latitude'
INNER JOIN wp_postmeta pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_property_longitude'
WHERE p.post_type = 'cpt_listing'
  AND p.post_status = 'publish'
HAVING distance < ?
ORDER BY distance ASC;
```

**Implementation:**

```php
function get_listings_near_location($lat, $lng, $radius_km = 10) {
    global $wpdb;

    $query = $wpdb->prepare(/* SQL above */, $lat, $lng, $lat, $radius_km);
    return $wpdb->get_results($query);
}
```

### 9.3 Search Filters UI

**Filter Categories:**

- **Location:** City dropdown, radius search, map view
- **Price:** Min/max sliders
- **Dates:** Available from, lease duration
- **Room Type:** Private, shared, entire unit
- **Furnishing:** Furnished, unfurnished, partially
- **Gender Preference:** Any, male, female, female-only
- **Amenities:** Multi-select checkboxes
- **Property Type:** Apartment, house, condo, etc.

**Faceted Search Counts:**

```php
// Example: Count listings per city
$facets = $wpdb->get_results("
    SELECT pm.meta_value AS city, COUNT(*) AS count
    FROM wp_posts p
    INNER JOIN wp_postmeta pm ON p.ID = pm.post_id
    WHERE p.post_type = 'cpt_listing'
      AND p.post_status = 'publish'
      AND pm.meta_key = '_property_city'
    GROUP BY pm.meta_value
    ORDER BY count DESC
");
```

---

## 10. EMAIL NOTIFICATIONS

### 10.1 Notification Triggers

**Student Notifications:**

- Application submitted (confirmation)
- Application status changed (accepted/rejected)
- New message from landlord
- Saved listing price dropped
- Saved listing about to expire

**Landlord Notifications:**

- New application received
- Verification approved/rejected
- Listing approved/rejected by admin
- Listing about to expire (7 days before)
- New message from student
- Monthly analytics report

**Admin Notifications:**

- New listing pending review
- New verification submission
- New report filed
- Payment received (subscriptions)

### 10.2 Email Templates

**Use WordPress `wp_mail()` with HTML templates:**

```php
// Example: Application submitted
$to = get_userdata($landlord_id)->user_email;
$subject = 'New Application for ' . get_the_title($listing_id);

$template_vars = [
    'landlord_name'   => $landlord->display_name,
    'student_name'    => $student->display_name,
    'listing_title'   => get_the_title($listing_id),
    'application_url' => admin_url('admin.php?page=rental-applications&id=' . $app_id),
    'move_in_date'    => $move_in_date
];

$html_content = render_email_template('new-application', $template_vars);

wp_mail($to, $subject, $html_content, ['Content-Type: text/html; charset=UTF-8']);
```

**Template Structure:**

- Header with logo
- Personalized greeting
- Primary CTA button
- Footer with unsubscribe link

---

## 11. FRONTEND ARCHITECTURE

### 11.1 Page Templates Required

**Public Pages:**

- `page-search-listings.php` - Main search interface
- `single-cpt_listing.php` - Listing detail page
- `page-how-it-works.php` - Onboarding guide
- `page-landlord-signup.php` - Landlord registration

**Student Dashboard:**

- `page-student-dashboard.php` - Overview
- `page-my-applications.php` - Application history
- `page-saved-listings.php` - Bookmarked listings
- `page-messages.php` - Messaging interface
- `page-profile-settings.php` - Account settings

**Landlord Dashboard:**

- `page-landlord-dashboard.php` - Overview with analytics
- `page-my-properties.php` - Property management
- `page-add-property.php` - Create property
- `page-edit-property.php` - Edit property
- `page-applications.php` - View applications
- `page-verification.php` - Upload verification docs
- `page-subscription.php` - Manage subscription

**Admin Area:**

- Custom post type screens (Properties, Units, Listings)
- `admin-page-verifications.php` - Verification queue
- `admin-page-reports.php` - Moderation dashboard
- `admin-page-analytics.php` - Platform metrics

### 11.2 JavaScript Architecture

**Use Modern Stack:**

- **Alpine.js** for lightweight reactivity (dropdowns, modals)
- **HTMX** for dynamic content loading (infinite scroll, filters)
- **Swiper.js** for image galleries
- **Mapbox/Leaflet** for map displays

**Example: Search Filters (Alpine.js)**

```html
<div x-data="searchFilters()" x-init="init()">
  <input type="range" x-model="priceRange.min" @input="applyFilters()" />
  <select x-model="city" @change="applyFilters()">
    <option value="">All Cities</option>
    <!-- ... -->
  </select>

  <div
    id="results"
    hx-get="/wp-json/rental/v1/listings"
    hx-trigger="load"
    hx-swap="innerHTML"
  >
    <!-- Results load here -->
  </div>
</div>

<script>
  function searchFilters() {
    return {
      priceRange: { min: 0, max: 5000 },
      city: "",

      applyFilters() {
        const params = new URLSearchParams({
          min_price: this.priceRange.min,
          max_price: this.priceRange.max,
          city: this.city,
        });

        htmx.ajax("GET", `/wp-json/rental/v1/listings?${params}`, {
          target: "#results",
          swap: "innerHTML",
        });
      },
    };
  }
</script>
```

---

## 12. TESTING REQUIREMENTS

### 12.1 Unit Tests (PHPUnit)

**Test Coverage:**

- Application submission logic
- Booking workflow (unit availability updates)
- Verification level calculations
- State machine transitions
- Access control (can student edit landlord's property?)

**Example Test:**

```php
class ApplicationTest extends WP_UnitTestCase {
    public function test_student_cannot_apply_to_own_listing() {
        $user_id = $this->factory->user->create(['role' => 'student']);
        $listing_id = $this->create_test_listing($user_id);

        $result = submit_application($user_id, $listing_id);

        $this->assertWPError($result);
        $this->assertEquals('cannot_apply_own_listing', $result->get_error_code());
    }

    public function test_accepted_application_books_unit() {
        $landlord_id = $this->factory->user->create(['role' => 'landlord']);
        $student_id = $this->factory->user->create(['role' => 'student']);

        $unit_id = $this->create_test_unit($landlord_id);
        $app_id = submit_application($student_id, $unit_id);

        accept_application($app_id, $landlord_id);

        $unit_status = get_post_meta($unit_id, '_availability_status', true);
        $this->assertEquals('booked', $unit_status);
    }
}
```

### 12.2 Integration Tests

**Scenarios to Test:**

- Complete landlord signup → verification → listing publication flow
- Student search → save → apply → acceptance → booking flow
- Admin moderation workflow (report → review → suspend)
- Email notifications on key events
- Payment processing (when monetization added)

---

## 13. DEPLOYMENT & DEVOPS

### 13.1 Environment Configuration

**Required Environment Variables:**

```php
// wp-config.php additions
define('RENTAL_GOOGLE_MAPS_API_KEY', 'your-key-here');
define('RENTAL_STRIPE_PUBLIC_KEY', 'pk_test_...');
define('RENTAL_STRIPE_SECRET_KEY', 'sk_test_...');
define('RENTAL_TWILIO_SID', 'AC...');
define('RENTAL_TWILIO_AUTH_TOKEN', '...');
define('RENTAL_ADMIN_EMAIL', 'admin@example.com');
define('RENTAL_MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('RENTAL_LISTING_EXPIRY_DAYS', 90);
```

### 13.2 Production Checklist

**Pre-Launch:**

- Enable Redis/Memcached object caching
- Configure CDN for static assets (images, CSS, JS)
- Set up automated database backups (daily)
- Configure SMTP for transactional emails (SendGrid/Mailgun)
- Enable SSL certificate (Let's Encrypt)
- Set up uptime monitoring (UptimeRobot, Pingdom)
- Configure rate limiting (fail2ban, Cloudflare)
- Enable WordPress security plugin (Wordfence/Sucuri)
- Test all email templates in multiple clients
- Run full accessibility audit (WCAG 2.1 AA)
- Performance test with 10k+ listings

---

## 14. FUTURE ENHANCEMENTS

### 14.1 Phase 2 Features

**Trust & Safety:**

- Landlord reputation system (reviews, ratings)
- Student verification (student ID upload)
- Escrow payment system for security deposits
- Dispute resolution center

**Advanced Search:**

- Commute time calculator (to university campus)
- Roommate matching algorithm
- Virtual tours (360° photos, video walkthroughs)
- AI-powered listing recommendations

**Monetization:**

- Background check service ($20/check)
- Premium profile placement for students
- Export lead data API (for property managers)

**Integrations:**

- Zillow/Trulia data import
- University housing office API
- Payment gateway (Stripe Connect for landlord payouts)
- CRM integration (Salesforce, HubSpot)

### 14.2 Multi-Tenant SaaS Conversion

**Database Changes:**

```sql
CREATE TABLE wp_rental_tenants (
    tenant_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_slug VARCHAR(100) UNIQUE NOT NULL,
    tenant_name VARCHAR(255) NOT NULL,
    domain VARCHAR(255) UNIQUE,
    plan_type VARCHAR(20),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Add tenant_id to all tables
ALTER TABLE wp_rental_applications ADD COLUMN tenant_id BIGINT UNSIGNED;
ALTER TABLE wp_rental_availability ADD COLUMN tenant_id BIGINT UNSIGNED;
-- ... etc
```

**Implementation:**

- Tenant context filter on all queries
- Subdomain/domain routing (tenant1.platform.com)
- Per-tenant customization (branding, colors, emails)
- Centralized billing dashboard

---

## 15. CODING STANDARDS

### 15.1 WordPress Best Practices

**Follow:**

- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- Use `wp_nonce_field()` for all forms
- Sanitize all inputs (`sanitize_text_field()`, `wp_kses_post()`)
- Escape all outputs (`esc_html()`, `esc_url()`, `esc_attr()`)
- Use prepared statements for direct DB queries (`$wpdb->prepare()`)

**Plugin Structure:**

```
student-rental-platform/
├── includes/
│   ├── class-post-types.php
│   ├── class-taxonomies.php
│   ├── class-capabilities.php
│   ├── class-rest-api.php
│   ├── class-email-notifications.php
│   └── workflows/
│       ├── class-application-workflow.php
│       ├── class-listing-workflow.php
│       └── class-verification-workflow.php
├── admin/
│   ├── views/
│   └── assets/
├── public/
│   ├── templates/
│   └── assets/
├── tests/
│   ├── unit/
│   └── integration/
└── student-rental-platform.php (main plugin file)
```

### 15.2 Code Review Checklist

**Before Merge:**

- All functions have docblocks
- No hardcoded strings (use `__()` for i18n)
- No SQL injection vulnerabilities
- No XSS vulnerabilities (all outputs escaped)
- CSRF protection via nonces
- Capabilities checked before sensitive operations
- Error logging implemented (not user-visible errors)
- Performance profiled (no N+1 queries)
- Mobile responsive (tested on real devices)
- Passes PHPCS WordPress standards
- Unit tests written for critical logic
- Database migrations included if schema changed

---

## 16. SUPPORT & MAINTENANCE

### 16.1 Logging Strategy

**Use WordPress Debug Log:**

```php
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('[Rental Platform] Application submitted: ' . $application_id);
}
```

**Custom Log Table for Critical Events:**

```sql
CREATE TABLE wp_rental_audit_log (
    log_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED,
    action VARCHAR(50) NOT NULL,
    entity_type VARCHAR(20),
    entity_id BIGINT UNSIGNED,
    metadata JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_user_action (user_id, action),
    INDEX idx_entity (entity_type, entity_id)
);
```

**Logged Actions:**

- `listing_published`, `listing_suspended`
- `application_accepted`, `application_rejected`
- `landlord_verified`, `landlord_suspended`
- `payment_received`, `payout_sent`

### 16.2 Monitoring Dashboards

**Key Metrics to Track:**

- Active listings per day
- Application conversion rate (views → applications)
- Average time to landlord response
- Verification approval rate
- User churn rate
- Revenue MRR (when monetized)

**Recommended Tools:**

- **Analytics:** Google Analytics 4 + custom events
- **Performance:** New Relic / Query Monitor
- **Errors:** Sentry / Rollbar
- **Uptime:** Pingdom / StatusCake

---

## 17. COMPLIANCE & LEGAL

### 17.1 Required Policies

**Must Have:**

- Terms of Service (governs platform use)
- Privacy Policy (GDPR compliant)
- Cookie Policy (EU Cookie Law)
- Fair Housing Policy (no discrimination)
- Refund Policy (for paid features)

**Disclaimers:**

- Platform is not party to rental agreements
- Landlords responsible for compliance with local laws
- No guarantee of listing accuracy

### 17.2 GDPR Compliance

**User Rights:**

- Right to access (export all personal data)
- Right to erasure (delete account + data)
- Right to rectification (update incorrect data)
- Right to data portability (machine-readable format)

**Implementation:**

```php
// Example: Export user data
function rental_export_user_data($user_id) {
    return [
        'applications'      => get_user_applications($user_id),
        'messages'          => get_user_messages($user_id),
        'saved_listings'    => get_user_saved_listings($user_id),
        'properties'        => get_user_properties($user_id),
        'verification_docs' => get_user_verification_docs($user_id)
    ];
}

// Example: Delete user data (GDPR erasure)
function rental_delete_user_data($user_id) {
    global $wpdb;

    // Anonymize applications instead of deleting (preserve landlord records)
    $wpdb->update('wp_rental_applications',
        ['student_id' => 0, 'student_info' => 'REDACTED'],
        ['student_id' => $user_id]
    );

    // Delete messages
    $wpdb->delete('wp_rental_messages', ['sender_id' => $user_id]);

    // Delete saved listings
    $wpdb->delete('wp_rental_saved_listings', ['student_id' => $user_id]);
}
```

---

## 18. GLOSSARY

| Term                  | Definition                                                |
| --------------------- | --------------------------------------------------------- |
| Property              | Physical building containing one or more units            |
| Unit                  | Individual rentable space (room, apartment, entire home)  |
| Listing               | Public-facing advertisement of a unit                     |
| Application           | Student's request to rent a specific unit                 |
| Verification          | Process of confirming landlord identity/ownership         |
| Featured Listing      | Paid promotion for higher visibility                      |
| Booking               | Successful rental agreement (application accepted)        |
| Thread                | Conversation between student and landlord about a listing |
| Suspension            | Temporary removal of listing/account by admin             |
| Availability Calendar | Date-based occupancy schedule for a unit                  |
| Trust Score           | Algorithmic rating of landlord reliability (future)       |
| Lead Unlock           | Student pays to access landlord contact info (future)     |

---

## 19. QUICK REFERENCE - KEY DECISIONS

**Critical Design Choices:**

- **Listing = Unit wrapper:** Allows unit reusability, separates publishing from inventory
- **Applications in custom table:** Better performance than post meta for high-volume transactional data
- **Verification levels 0-5:** Gradual trust building, clear capability gates
- **Post-moderation for Level 4+ landlords:** Reduces admin bottleneck while maintaining quality
- **One application per student per unit:** Prevents spam, simplifies landlord workflow
- **Auto-reject on acceptance:** Clean UX, prevents double-booking
- **90-day listing expiry:** Keeps listings fresh, incentivizes landlord engagement
- **City-level public location:** Privacy for landlords, exact address after inquiry only
- **Event-driven architecture:** Enables flexible monetization without core refactor
- **WordPress multisite ready:** Path to SaaS without complete rebuild

---

## FINAL NOTES

This domain model prioritizes:

- **Scalability:** Indexed queries, caching strategy, event system
- **Extensibility:** Plugin architecture, clear hook points
- **Security:** Role-based access, input sanitization, audit logs
- **Monetization readiness:** Subscription tables, credit system, featured flags
- **Maintainability:** Clear state machines, documented constraints, test coverage

**When in doubt:**

- Favor explicit state over implicit logic
- Use WordPress conventions unless performance demands otherwise
- Add database indexes before query optimization
- Test access control for every new capability
- Document all state transitions

---

**Version:** 1.0
**Last Updated:** 2026-02-16
**Maintained By:** [Your Agency Name]
**Questions?** Contact the engineering team lead.
