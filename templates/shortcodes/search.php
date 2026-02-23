<?php
/**
 * Shortcode Template: Search Listings
 *
 * Rendered by [leaselink_search] shortcode.
 * Theme can override at: yourtheme/leaselink/shortcodes/search.php
 *
 * @package LeaseLink
 * @since   1.1.0
 */

use StudentRentalPlatform\SRP_Template_Loader;
?>

<div class="ll-search-layout" x-data="leaselinkSearch()">
    <!-- Sidebar Filters -->
    <div class="ll-search-sidebar">
        <div class="ll-card" style="padding:1.25rem;">
            <h3 style="font-size:1rem;font-weight:600;margin-bottom:1rem;color:var(--ll-dark);">Filters</h3>

            <div style="display:flex;flex-direction:column;gap:0.75rem;">
                <div>
                    <label
                        style="display:block;font-size:0.8125rem;font-weight:500;margin-bottom:0.25rem;color:var(--ll-gray);">City</label>
                    <input type="text" x-model="filters.city" @change="applyFilters()" class="ll-input"
                        placeholder="Enter city...">
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;">
                    <div>
                        <label
                            style="display:block;font-size:0.8125rem;font-weight:500;margin-bottom:0.25rem;color:var(--ll-gray);">Min
                            Price</label>
                        <input type="number" x-model="filters.min_price" @change="applyFilters()" class="ll-input"
                            placeholder="€ Min">
                    </div>
                    <div>
                        <label
                            style="display:block;font-size:0.8125rem;font-weight:500;margin-bottom:0.25rem;color:var(--ll-gray);">Max
                            Price</label>
                        <input type="number" x-model="filters.max_price" @change="applyFilters()" class="ll-input"
                            placeholder="€ Max">
                    </div>
                </div>

                <div>
                    <label
                        style="display:block;font-size:0.8125rem;font-weight:500;margin-bottom:0.25rem;color:var(--ll-gray);">Room
                        Type</label>
                    <select x-model="filters.room_type" @change="applyFilters()" class="ll-input">
                        <option value="">All Types</option>
                        <option value="private_room">Private Room</option>
                        <option value="shared_room">Shared Room</option>
                        <option value="entire_unit">Entire Unit</option>
                        <option value="studio">Studio</option>
                    </select>
                </div>

                <div>
                    <label
                        style="display:block;font-size:0.8125rem;font-weight:500;margin-bottom:0.25rem;color:var(--ll-gray);">Furnished</label>
                    <select x-model="filters.furnished" @change="applyFilters()" class="ll-input">
                        <option value="">Any</option>
                        <option value="furnished">Furnished</option>
                        <option value="unfurnished">Unfurnished</option>
                        <option value="partially_furnished">Partially</option>
                    </select>
                </div>

                <button @click="resetFilters()" class="ll-btn ll-btn-ghost ll-btn-sm" style="width:100%;">Reset
                    Filters</button>
            </div>
        </div>
    </div>

    <!-- Results -->
    <div class="ll-search-results">
        <div style="margin-bottom:1rem;display:flex;justify-content:space-between;align-items:center;">
            <p style="font-size:0.875rem;color:var(--ll-gray);margin:0;">
                <span x-text="totalResults + ' listings found'"></span>
            </p>
        </div>

        <!-- Loading -->
        <div x-show="loading" class="ll-search-grid">
            <template x-for="i in 6" :key="i">
                <div class="ll-skeleton" style="height:280px;"></div>
            </template>
        </div>

        <!-- Results Grid -->
        <div x-show="!loading && listings.length > 0" class="ll-search-grid">
            <template x-for="listing in listings" :key="listing.id">
                <a :href="listing.link" class="ll-listing-card" style="text-decoration:none;color:inherit;">
                    <div class="ll-listing-card-image">
                        <img :src="listing.thumbnail || ''" :alt="listing.title" loading="lazy"
                            style="width:100%;height:100%;object-fit:cover;">
                        <span x-show="listing.featured" class="ll-badge ll-badge-warning"
                            style="position:absolute;top:0.75rem;left:0.75rem;">Featured</span>
                    </div>
                    <div class="ll-listing-card-body">
                        <div
                            style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:0.25rem;">
                            <p class="ll-listing-card-title" x-text="listing.title"></p>
                            <span class="ll-listing-card-price" x-text="'€' + listing.price"></span>
                        </div>
                        <p class="ll-listing-card-location" x-text="listing.city"></p>
                        <div class="ll-listing-card-meta">
                            <span x-text="listing.room_type"></span>
                        </div>
                    </div>
                </a>
            </template>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && listings.length === 0">
            <?php SRP_Template_Loader::get_template('components/empty-state.php', [
                'title' => 'No listings found',
                'description' => 'Try adjusting your filters to find more results.',
                'icon' => 'inbox',
            ]); ?>
        </div>

        <!-- Pagination -->
        <div x-show="totalPages > 1" style="display:flex;justify-content:center;gap:0.5rem;margin-top:2rem;">
            <button @click="prevPage()" :disabled="currentPage <= 1" class="ll-btn ll-btn-secondary ll-btn-sm">←
                Previous</button>
            <span style="display:flex;align-items:center;font-size:0.875rem;color:var(--ll-gray);padding:0 0.75rem;"
                x-text="'Page ' + currentPage + ' of ' + totalPages"></span>
            <button @click="nextPage()" :disabled="currentPage >= totalPages"
                class="ll-btn ll-btn-secondary ll-btn-sm">Next →</button>
        </div>
    </div>
</div>