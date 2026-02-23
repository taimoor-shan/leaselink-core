/**
 * LeaseLink Frontend JS
 *
 * Alpine.js components for search, dashboard, and application forms.
 * Works with any theme — uses plugin's REST API for data.
 *
 * @package LeaseLink
 * @since   1.1.0
 */

/* global leaselinkData, Alpine */

/**
 * Search Listings — Alpine.js component
 *
 * Usage in template: x-data="leaselinkSearch()"
 */
function leaselinkSearch() {
  return {
    filters: {
      city: new URLSearchParams(window.location.search).get("city") || "",
      min_price:
        new URLSearchParams(window.location.search).get("min_price") || "",
      max_price:
        new URLSearchParams(window.location.search).get("max_price") || "",
      room_type: "",
      gender: "",
      furnished: "",
      sort: "date_desc",
    },
    listings: [],
    loading: true,
    currentPage: 1,
    totalPages: 1,
    totalResults: 0,

    init() {
      this.applyFilters();
    },

    async applyFilters() {
      this.loading = true;
      this.currentPage = 1;
      await this.fetchListings();
    },

    async fetchListings() {
      const params = new URLSearchParams();
      if (this.filters.city) params.set("city", this.filters.city);
      if (this.filters.min_price)
        params.set("min_price", this.filters.min_price);
      if (this.filters.max_price)
        params.set("max_price", this.filters.max_price);
      if (this.filters.room_type)
        params.set("room_type", this.filters.room_type);
      if (this.filters.gender) params.set("gender", this.filters.gender);
      if (this.filters.furnished)
        params.set("furnished", this.filters.furnished);
      params.set("page", this.currentPage);
      params.set("per_page", 12);

      try {
        const response = await fetch(
          `${leaselinkData.restUrl}listings?${params}`,
        );
        const data = await response.json();

        this.listings = Array.isArray(data)
          ? data.map((item) => ({
              id: item.id,
              title: item.title || "",
              price: item.rent_price || "0",
              city: item.city || "",
              room_type: item.room_type || "Unit",
              thumbnail: item.thumbnail || "",
              link: item.link || "#",
              featured: item.featured || false,
            }))
          : [];

        const total = response.headers.get("X-WP-Total");
        const pages = response.headers.get("X-WP-TotalPages");
        this.totalResults = total ? parseInt(total) : this.listings.length;
        this.totalPages = pages ? parseInt(pages) : 1;
      } catch (error) {
        console.error("LeaseLink search error:", error);
        this.listings = [];
        this.totalResults = 0;
      }

      this.loading = false;
    },

    resetFilters() {
      this.filters = {
        city: "",
        min_price: "",
        max_price: "",
        room_type: "",
        gender: "",
        furnished: "",
        sort: "date_desc",
      };
      this.applyFilters();
    },

    async nextPage() {
      if (this.currentPage < this.totalPages) {
        this.currentPage++;
        this.loading = true;
        await this.fetchListings();
        window.scrollTo({ top: 0, behavior: "smooth" });
      }
    },

    async prevPage() {
      if (this.currentPage > 1) {
        this.currentPage--;
        this.loading = true;
        await this.fetchListings();
        window.scrollTo({ top: 0, behavior: "smooth" });
      }
    },
  };
}

/**
 * Application Form — Alpine.js component
 *
 * Usage: x-data="leaselinkApplication()"
 */
function leaselinkApplication() {
  return {
    showForm: false,
    submitted: false,
    submitting: false,

    async submitApplication(event) {
      this.submitting = true;
      const form = event.target;
      const formData = new FormData(form);
      const data = Object.fromEntries(formData);

      try {
        const response = await fetch(`${leaselinkData.restUrl}applications`, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-WP-Nonce": leaselinkData.nonce,
          },
          body: JSON.stringify(data),
        });

        if (response.ok) {
          this.submitted = true;
          this.showForm = false;
        } else {
          alert("Failed to submit application. Please try again.");
        }
      } catch (error) {
        alert("Something went wrong. Please try again.");
      }
      this.submitting = false;
    },
  };
}

/**
 * Property Form — Alpine.js component for multi-step property creation
 *
 * Usage: x-data="leaselinkPropertyForm()"
 */
function leaselinkPropertyForm() {
  return {
    step: 0,
    submitting: false,
    success: false,
    property: {
      title: "",
      description: "",
      address: "",
      city: "",
      state: "",
      country: "",
      postal_code: "",
      property_type: "",
    },
    unit: {
      title: "",
      description: "",
      rent_price: "",
      deposit: "",
      room_type: "",
      furnished: "",
      available_from: "",
      gender_preference: "any",
      max_occupancy: 1,
      square_footage: "",
    },

    async submitProperty() {
      this.submitting = true;
      try {
        // Create property.
        const propRes = await fetch(`${leaselinkData.restUrl}properties`, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-WP-Nonce": leaselinkData.nonce,
          },
          body: JSON.stringify(this.property),
        });
        const propData = await propRes.json();

        if (propData.id) {
          // Create unit.
          await fetch(
            `${leaselinkData.restUrl}properties/${propData.id}/units`,
            {
              method: "POST",
              headers: {
                "Content-Type": "application/json",
                "X-WP-Nonce": leaselinkData.nonce,
              },
              body: JSON.stringify(this.unit),
            },
          );
          this.success = true;
        } else {
          alert("Failed to create property. Please try again.");
        }
      } catch (error) {
        alert("Something went wrong. Please try again.");
      }
      this.submitting = false;
    },

    resetForm() {
      this.step = 0;
      this.success = false;
      this.property = {
        title: "",
        description: "",
        address: "",
        city: "",
        state: "",
        country: "",
        postal_code: "",
        property_type: "",
      };
      this.unit = {
        title: "",
        description: "",
        rent_price: "",
        deposit: "",
        room_type: "",
        furnished: "",
        available_from: "",
        gender_preference: "any",
        max_occupancy: 1,
        square_footage: "",
      };
    },
  };
}

/**
 * Update application status — used by landlord dashboard
 *
 * @param {number} appId   Application ID.
 * @param {string} status  New status.
 */
async function leaselinkUpdateApplication(appId, status) {
  try {
    const response = await fetch(
      `${leaselinkData.restUrl}applications/${appId}`,
      {
        method: "PATCH",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": leaselinkData.nonce,
        },
        body: JSON.stringify({ status }),
      },
    );

    if (response.ok) {
      location.reload();
    } else {
      alert("Failed to update application. Please try again.");
    }
  } catch (error) {
    alert("Something went wrong. Please try again.");
  }
}

/**
 * Remove saved listing
 *
 * @param {number} listingId Listing ID.
 * @param {HTMLElement} element The element to remove on success.
 */
async function leaselinkUnsave(listingId, element) {
  if (!confirm("Remove from saved?")) return;

  try {
    await fetch(`${leaselinkData.restUrl}listings/${listingId}/save`, {
      method: "DELETE",
      headers: { "X-WP-Nonce": leaselinkData.nonce },
    });
    element.closest(".ll-saved-item").remove();
  } catch (error) {
    alert("Failed to remove. Please try again.");
  }
}

/**
 * Withdraw application
 *
 * @param {number} appId Application ID.
 */
async function leaselinkWithdraw(appId) {
  if (!confirm("Withdraw this application?")) return;

  try {
    await fetch(`${leaselinkData.restUrl}applications/${appId}`, {
      method: "PATCH",
      headers: {
        "Content-Type": "application/json",
        "X-WP-Nonce": leaselinkData.nonce,
      },
      body: JSON.stringify({ status: "withdrawn" }),
    });
    location.reload();
  } catch (error) {
    alert("Failed to withdraw. Please try again.");
  }
}
