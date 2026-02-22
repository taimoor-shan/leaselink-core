# REST API Specification

## Base URL

```
https://yourdomain.com/wp-json/rental/v1/
```

## Authentication

- Public endpoints: No auth required
- Protected endpoints: WordPress authentication (cookies or JWT)

## Response Format

```json
{
  "success": true,
  "data": {
    /* response data */
  },
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 150,
    "total_pages": 8
  }
}
```

## Error Format

```json
{
  "code": "invalid_input",
  "message": "The city parameter is required",
  "data": {
    "status": 400
  }
}
```

## Endpoints

### GET /listings

Search for rental listings

**Query Parameters:**

- `city` (string): Filter by city
- `min_price` (number): Minimum monthly rent
- `max_price` (number): Maximum monthly rent
- `gender` (string): Gender preference (male, female, any)
- `furnished` (string): Furnished status
- `amenities[]` (array): Required amenities
- `page` (number): Page number (default: 1)
- `per_page` (number): Results per page (default: 20, max: 100)

**Response:**

```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "title": "Cozy Studio Near Campus",
      "rent_price": 800,
      "currency": "USD",
      "city": "Boston",
      "available_from": "2026-03-01",
      "furnished": true,
      "thumbnail": "https://...",
      "property": {
        "id": 45,
        "title": "University Apartments"
      }
    }
  ],
  "meta": {
    /* pagination */
  }
}
```

### POST /applications

Submit rental application (Auth required)

**Request Body:**

```json
{
  "unit_id": 123,
  "move_in_date": "2026-03-01",
  "lease_duration": 12,
  "message": "I'm a second-year student...",
  "student_info": {
    "university": "MIT",
    "year": 2,
    "major": "Computer Science"
  }
}
```

**Response:**

```json
{
  "success": true,
  "data": {
    "application_id": 456,
    "status": "submitted",
    "submitted_at": "2026-02-16T10:30:00Z"
  }
}
```

[Continue with all endpoints...]

---

**For complete API specification, see REQUIREMENTS.md Section 7**
