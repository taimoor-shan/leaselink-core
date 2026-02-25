<?php
/**
 * Search Listings — public listing search with filters
 *
 * @package LeaseLink
 */

get_header();

$init_city = isset($_GET['city']) ? sanitize_text_field($_GET['city']) : '';
$init_min_price = isset($_GET['min_price']) ? absint($_GET['min_price']) : '';
$init_max_price = isset($_GET['max_price']) ? absint($_GET['max_price']) : '';
?>

<section class="py-10 bg-surface min-h-screen" x-data="searchListings()" x-init="init()">
    <div class="container mx-auto px-4">
        <div class="mb-8">
            <h1 class="text-3xl font-bold mb-2">Browse Listings</h1>
            <p class="text-gray">Find your perfect student rental from verified landlords.</p>
        </div>

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Filter Sidebar -->
            <aside class="w-full lg:w-72 shrink-0">
                <div class="bg-white rounded-xl border border-zinc-100 p-5 shadow-sm sticky top-20">
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-base font-semibold mb-0">Filters</h3>
                        <button @click="resetFilters()" class="text-xs text-primary hover:underline">Reset</button>
                    </div>
                    <div class="mb-5">
                        <label class="block text-sm font-medium text-dark mb-1.5">City</label>
                        <input type="text" x-model="filters.city" @input.debounce.500ms="applyFilters()"
                            placeholder="Search city..." class="ll-input">
                    </div>
                    <div class="mb-5">
                        <label class="block text-sm font-medium text-dark mb-1.5">Price Range (€/month)</label>
                        <div class="flex gap-2">
                            <input type="number" x-model="filters.min_price" @input.debounce.500ms="applyFilters()"
                                placeholder="Min" class="ll-input">
                            <input type="number" x-model="filters.max_price" @input.debounce.500ms="applyFilters()"
                                placeholder="Max" class="ll-input">
                        </div>
                    </div>
                    <div class="mb-5">
                        <label class="block text-sm font-medium text-dark mb-1.5">Room Type</label>
                        <select x-model="filters.room_type" @change="applyFilters()" class="ll-input">
                            <option value="">All Types</option>
                            <option value="private_room">Private Room</option>
                            <option value="shared_room">Shared Room</option>
                            <option value="entire_unit">Entire Unit</option>
                            <option value="studio">Studio</option>
                        </select>
                    </div>
                    <div class="mb-5">
                        <label class="block text-sm font-medium text-dark mb-1.5">Gender Preference</label>
                        <select x-model="filters.gender" @change="applyFilters()" class="ll-input">
                            <option value="">Any</option>
                            <option value="any">No Preference</option>
                            <option value="male">Male Only</option>
                            <option value="female">Female Only</option>
                        </select>
                    </div>
                    <div class="mb-5">
                        <label class="block text-sm font-medium text-dark mb-1.5">Furnishing</label>
                        <select x-model="filters.furnished" @change="applyFilters()" class="ll-input">
                            <option value="">Any</option>
                            <option value="furnished">Furnished</option>
                            <option value="unfurnished">Unfurnished</option>
                            <option value="partially_furnished">Partially Furnished</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-dark mb-1.5">Sort By</label>
                        <select x-model="filters.sort" @change="applyFilters()" class="ll-input">
                            <option value="date_desc">Newest First</option>
                            <option value="date_asc">Oldest First</option>
                            <option value="price_asc">Price: Low to High</option>
                            <option value="price_desc">Price: High to Low</option>
                        </select>
                    </div>
                </div>
            </aside>

            <!-- Results -->
            <div class="flex-1">
                <div class="flex items-center justify-between mb-5">
                    <p class="text-sm text-gray mb-0">
                        <span x-show="!loading" x-text="totalResults + ' listings found'"></span>
                        <span x-show="loading">Searching...</span>
                    </p>
                </div>

                <!-- Loading -->
                <div x-show="loading" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                    <template x-for="i in 6" :key="i">
                        <div class="bg-white rounded-xl border border-zinc-100 overflow-hidden shadow-sm animate-pulse">
                            <div class="aspect-[4/3] bg-zinc-100"></div>
                            <div class="p-4 space-y-3">
                                <div class="h-4 bg-zinc-100 rounded w-3/4"></div>
                                <div class="h-3 bg-zinc-50 rounded w-1/2"></div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Results Grid -->
                <div x-show="!loading && listings.length > 0"
                    class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5" id="listing-results">
                    <template x-for="listing in listings" :key="listing.id">
                        <a :href="listing.link"
                            class="group block bg-white rounded-xl border border-zinc-100 overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300 no-underline">
                            <div class="relative aspect-[4/3] overflow-hidden bg-zinc-100">
                                <img :src="listing.thumbnail || ''" :alt="listing.title"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                    loading="lazy" x-show="listing.thumbnail">
                                <div x-show="!listing.thumbnail"
                                    class="w-full h-full flex items-center justify-center bg-zinc-50">
                                    <svg class="w-12 h-12 text-zinc-300" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                    </svg>
                                </div>
                                <span x-show="listing.featured"
                                    class="absolute top-3 left-3 inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-warning text-white text-xs font-semibold">Featured</span>
                                <span
                                    class="absolute top-3 right-3 inline-flex items-center px-2.5 py-1 rounded-full bg-dark/60 backdrop-blur-sm text-white text-xs font-medium"
                                    x-text="listing.room_type"></span>
                            </div>
                            <div class="p-4">
                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <h3 class="text-base font-semibold text-dark leading-snug line-clamp-1 mb-0"
                                        x-text="listing.title"></h3>
                                    <span class="text-lg font-bold text-primary whitespace-nowrap"
                                        x-text="'€' + listing.price"></span>
                                </div>
                                <p class="text-sm text-gray flex items-center gap-1 mb-3" x-show="listing.city">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span x-text="listing.city"></span>
                                </p>
                            </div>
                        </a>
                    </template>
                </div>

                <!-- Empty -->
                <div x-show="!loading && listings.length === 0" class="bg-white rounded-xl border border-zinc-100">
                    <?php \StudentRentalPlatform\SRP_Template_Loader::get_template('components/empty-state.php', [
                        'title' => 'No listings found',
                        'description' => 'Try adjusting your search filters or broadening your search area.',
                        'icon' => 'search',
                    ]); ?>
                </div>

                <!-- Pagination -->
                <div x-show="!loading && totalPages > 1" class="mt-8 flex justify-center gap-2">
                    <button @click="prevPage()" :disabled="currentPage <= 1" class="ll-btn ll-btn-secondary ll-btn-sm"
                        :class="{'opacity-50 cursor-not-allowed': currentPage <= 1}">Previous</button>
                    <span class="inline-flex items-center px-3 text-sm text-gray"
                        x-text="'Page ' + currentPage + ' of ' + totalPages"></span>
                    <button @click="nextPage()" :disabled="currentPage >= totalPages"
                        class="ll-btn ll-btn-secondary ll-btn-sm"
                        :class="{'opacity-50 cursor-not-allowed': currentPage >= totalPages}">Next</button>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    function searchListings() {
        return {
            filters: {
                city: '<?php echo esc_js($init_city); ?>', min_price: '<?php echo esc_js($init_min_price); ?>',
                max_price: '<?php echo esc_js($init_max_price); ?>', room_type: '', gender: '', furnished: '', sort: 'date_desc',
            },
            listings: [], loading: true, currentPage: 1, totalPages: 1, totalResults: 0,
            init() { this.applyFilters(); },
            async applyFilters() { this.loading = true; this.currentPage = 1; await this.fetchListings(); },
            async fetchListings() {
                const params = new URLSearchParams();
                if (this.filters.city) params.set('city', this.filters.city);
                if (this.filters.min_price) params.set('min_price', this.filters.min_price);
                if (this.filters.max_price) params.set('max_price', this.filters.max_price);
                if (this.filters.room_type) params.set('room_type', this.filters.room_type);
                if (this.filters.gender) params.set('gender', this.filters.gender);
                if (this.filters.furnished) params.set('furnished', this.filters.furnished);
                params.set('page', this.currentPage);
                params.set('per_page', 12);
                try {
                    const response = await fetch(`<?php echo esc_url(rest_url('rental/v1/listings')); ?>?${params}`);
                    const data = await response.json();
                    this.listings = Array.isArray(data) ? data.map(item => ({
                        id: item.id, title: item.title || '', price: item.rent_price || '0',
                        city: item.city || '', room_type: item.room_type || 'Unit',
                        thumbnail: item.thumbnail || '', link: item.link || '#', featured: item.featured || false,
                    })) : [];
                    this.totalResults = parseInt(response.headers.get('X-WP-Total') || this.listings.length);
                    this.totalPages = parseInt(response.headers.get('X-WP-TotalPages') || 1);
                } catch (error) { console.error('Search error:', error); this.listings = []; this.totalResults = 0; }
                this.loading = false;
            },
            resetFilters() { this.filters = { city: '', min_price: '', max_price: '', room_type: '', gender: '', furnished: '', sort: 'date_desc' }; this.applyFilters(); },
            async nextPage() { if (this.currentPage < this.totalPages) { this.currentPage++; this.loading = true; await this.fetchListings(); window.scrollTo({ top: 0, behavior: 'smooth' }); } },
            async prevPage() { if (this.currentPage > 1) { this.currentPage--; this.loading = true; await this.fetchListings(); window.scrollTo({ top: 0, behavior: 'smooth' }); } }
        };
    }
</script>

<?php get_footer(); ?>