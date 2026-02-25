<?php
/**
 * Add Property — Multi-step form
 *
 * @package LeaseLink
 */

use StudentRentalPlatform\SRP_Template_Loader;

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

get_header();
$nonce = wp_create_nonce('wp_rest');
?>

<div class="ll-dashboard">
    <?php SRP_Template_Loader::get_template('components/dashboard-nav.php', ['role' => 'landlord']); ?>

    <div class="ll-dashboard-content" x-data="propertyForm()">
        <div class="mb-6">
            <h1 class="text-2xl font-bold mb-1">Add Property</h1>
            <p class="text-gray">Fill in the details to register your property.</p>
        </div>

        <!-- Step Indicator -->
        <div class="flex items-center gap-2 mb-8">
            <template x-for="(label, index) in ['Property Details', 'Unit Details', 'Review']" :key="index">
                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium"
                        :class="step > index ? 'bg-success/10 text-success' : step === index ? 'bg-primary text-white' : 'bg-zinc-100 text-zinc-500'">
                        <span class="w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold"
                            :class="step > index ? 'bg-success text-white' : ''">
                            <span x-show="step <= index" x-text="index + 1"></span>
                            <svg x-show="step > index" class="w-3 h-3" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </span>
                        <span x-text="label" class="hidden sm:inline"></span>
                    </div>
                    <div x-show="index < 2" class="w-8 h-px bg-zinc-200"></div>
                </div>
            </template>
        </div>

        <!-- Step 1: Property Details -->
        <div x-show="step === 0" class="bg-white rounded-xl border border-zinc-100 shadow-sm p-6">
            <h2 class="text-lg font-semibold mb-5">Property Information</h2>
            <div class="space-y-4 max-w-2xl">
                <div>
                    <label class="block text-sm font-medium mb-1.5">Property Name *</label>
                    <input type="text" x-model="property.title" class="ll-input" placeholder="e.g., Sunview Apartments"
                        required>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Description</label>
                    <textarea x-model="property.description" class="ll-textarea"
                        placeholder="Describe your property..."></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Featured Image *</label>
                    <input type="file" x-ref="propertyImage" accept="image/*" class="ll-input p-2" required>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Address *</label>
                        <input type="text" x-model="property.address" class="ll-input" placeholder="Street address"
                            required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5">City *</label>
                        <input type="text" x-model="property.city" class="ll-input" placeholder="City" required>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1.5">State/Region</label>
                        <input type="text" x-model="property.state" class="ll-input" placeholder="State">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Country</label>
                        <input type="text" x-model="property.country" class="ll-input"
                            placeholder="Country code (e.g., AT)">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Postal Code</label>
                        <input type="text" x-model="property.postal_code" class="ll-input" placeholder="Postal code">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Latitude</label>
                        <input type="number" step="any" x-model="property.latitude" class="ll-input"
                            placeholder="e.g., 48.2082">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Longitude</label>
                        <input type="number" step="any" x-model="property.longitude" class="ll-input"
                            placeholder="e.g., 16.3738">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Property Type *</label>
                    <select x-model="property.property_type" class="ll-input" required>
                        <option value="">Select type...</option>
                        <option value="apartment">Apartment</option>
                        <option value="house">House</option>
                        <option value="condo">Condo</option>
                        <option value="dormitory">Dormitory</option>
                        <option value="shared_house">Shared House</option>
                        <option value="studio">Studio</option>
                    </select>
                </div>
                <div class="pt-4 flex justify-end">
                    <button @click="step = 1" class="ll-btn ll-btn-primary"
                        :disabled="!property.title || !property.address || !property.city">
                        Next: Unit Details
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Step 2: Unit Details -->
        <div x-show="step === 1" class="bg-white rounded-xl border border-zinc-100 shadow-sm p-6">
            <h2 class="text-lg font-semibold mb-5">Unit Information</h2>
            <div class="space-y-4 max-w-2xl">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Unit Name *</label>
                        <input type="text" x-model="unit.title" class="ll-input" placeholder="e.g., Unit 2B" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Room Type *</label>
                        <select x-model="unit.room_type" class="ll-input" required>
                            <option value="">Select...</option>
                            <option value="private_room">Private Room</option>
                            <option value="shared_room">Shared Room</option>
                            <option value="entire_unit">Entire Unit</option>
                            <option value="studio">Studio</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Unit Featured Image</label>
                    <input type="file" x-ref="unitImage" accept="image/*" class="ll-input p-2">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="col-span-1 md:col-span-2">
                        <label class="block text-sm font-medium mb-1.5">Monthly Rent *</label>
                        <div class="flex gap-2">
                            <input type="number" x-model="unit.rent_price" class="ll-input w-full" placeholder="500"
                                min="1" required>
                            <input type="text" x-model="unit.currency" class="ll-input w-24 text-center"
                                placeholder="USD">
                        </div>
                    </div>
                    <div class="col-span-1 md:col-span-2">
                        <label class="block text-sm font-medium mb-1.5">Deposit Amount</label>
                        <input type="number" x-model="unit.deposit" class="ll-input" placeholder="1000">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Area (m²)</label>
                        <input type="number" x-model="unit.square_footage" class="ll-input" placeholder="25">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Floor Number</label>
                        <input type="number" x-model="unit.floor_number" class="ll-input" placeholder="2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Max Occupancy</label>
                        <input type="number" x-model="unit.max_occupancy" class="ll-input" placeholder="1" min="1">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Available From *</label>
                        <input type="date" x-model="unit.available_from" class="ll-input" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Furnished *</label>
                        <select x-model="unit.furnished" class="ll-input" required>
                            <option value="">Select...</option>
                            <option value="furnished">Furnished</option>
                            <option value="unfurnished">Unfurnished</option>
                            <option value="partially_furnished">Partially Furnished</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Lease Min (Mo)</label>
                        <input type="number" x-model="unit.lease_duration_min" class="ll-input" placeholder="6">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Lease Max (Mo)</label>
                        <input type="number" x-model="unit.lease_duration_max" class="ll-input" placeholder="12">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Gender Preference</label>
                        <select x-model="unit.gender_preference" class="ll-input">
                            <option value="any">No Preference</option>
                            <option value="male">Male Only</option>
                            <option value="female">Female Only</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Pet Policy</label>
                        <select x-model="unit.pet_policy" class="ll-input">
                            <option value="not_allowed">Not Allowed</option>
                            <option value="allowed">Allowed</option>
                            <option value="negotiable">Negotiable</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Smoking Policy</label>
                        <select x-model="unit.smoking_policy" class="ll-input">
                            <option value="not_allowed">Not Allowed</option>
                            <option value="allowed">Allowed</option>
                            <option value="outside_only">Outside Only</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Utilities Included (Comma separated)</label>
                    <input type="text" x-model="unit.utilities" class="ll-input"
                        placeholder="water,electricity,internet">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Description</label>
                    <textarea x-model="unit.description" class="ll-textarea"
                        placeholder="Describe the unit..."></textarea>
                </div>
                <div class="pt-4 flex justify-between">
                    <button @click="step = 0" class="ll-btn ll-btn-secondary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back
                    </button>
                    <button @click="step = 2" class="ll-btn ll-btn-primary"
                        :disabled="!unit.title || !unit.rent_price || !unit.available_from">
                        Next: Review
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Step 3: Review & Submit -->
        <div x-show="step === 2" class="space-y-5">
            <div class="bg-white rounded-xl border border-zinc-100 shadow-sm p-6">
                <h2 class="text-lg font-semibold mb-4">Review Your Property</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-sm font-semibold text-gray mb-2 uppercase tracking-wide">Property</h3>
                        <p class="text-sm mb-1"><span class="text-gray">Name:</span> <span class="font-medium text-dark"
                                x-text="property.title"></span></p>
                        <p class="text-sm mb-1"><span class="text-gray">Address:</span> <span
                                class="font-medium text-dark" x-text="property.address + ', ' + property.city"></span>
                        </p>
                        <p class="text-sm mb-1"><span class="text-gray">Type:</span> <span
                                class="font-medium text-dark capitalize" x-text="property.property_type"></span></p>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray mb-2 uppercase tracking-wide">Unit</h3>
                        <p class="text-sm mb-1"><span class="text-gray">Name:</span> <span class="font-medium text-dark"
                                x-text="unit.title"></span></p>
                        <p class="text-sm mb-1"><span class="text-gray">Rent:</span> <span class="font-medium text-dark"
                                x-text="(unit.currency || 'USD') + ' ' + unit.rent_price + '/month'"></span></p>
                        <p class="text-sm mb-1"><span class="text-gray">Available:</span> <span
                                class="font-medium text-dark" x-text="unit.available_from"></span></p>
                        <p class="text-sm mb-1"><span class="text-gray">Furnished:</span> <span
                                class="font-medium text-dark capitalize"
                                x-text="unit.furnished?.replace('_', ' ')"></span></p>
                    </div>
                </div>
            </div>
            <div class="flex justify-between">
                <button @click="step = 1" class="ll-btn ll-btn-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back
                </button>
                <button @click="submitProperty()" class="ll-btn ll-btn-primary ll-btn-lg" :disabled="submitting">
                    <span x-show="!submitting">Submit Property</span>
                    <span x-show="submitting">Submitting...</span>
                </button>
            </div>
        </div>

        <!-- Success -->
        <div x-show="success" class="bg-white rounded-xl border border-zinc-100 shadow-sm p-10 text-center">
            <svg class="w-16 h-16 text-success mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h2 class="text-2xl font-bold mb-2">Property Created!</h2>
            <p class="text-gray mb-6">Your property and unit have been created. An admin will review your listing.</p>
            <div class="flex justify-center gap-3">
                <a href="<?php echo home_url('/my-properties/'); ?>" class="ll-btn ll-btn-primary no-underline">View My
                    Properties</a>
                <button @click="resetForm()" class="ll-btn ll-btn-secondary">Add Another</button>
            </div>
        </div>
    </div>
</div>

<script>
    function propertyForm() {
        return {
            step: 0, submitting: false, success: false,
            property: { title: '', description: '', address: '', city: '', state: '', country: '', postal_code: '', property_type: '', latitude: '', longitude: '' },
            unit: { title: '', description: '', rent_price: '', currency: 'USD', deposit: '', room_type: '', furnished: '', available_from: '', gender_preference: 'any', max_occupancy: 1, square_footage: '', floor_number: '', lease_duration_min: '', lease_duration_max: '', pet_policy: 'not_allowed', smoking_policy: 'not_allowed', utilities: '' },
            async submitProperty() {
                this.submitting = true;
                try {
                    const propData = new FormData();
                    for (const key in this.property) {
                        propData.append(key, this.property[key]);
                    }
                    if (this.$refs.propertyImage.files.length > 0) {
                        propData.append('featured_image', this.$refs.propertyImage.files[0]);
                    }

                    const propRes = await fetch('<?php echo esc_url(rest_url('rental/v1/properties')); ?>', {
                        method: 'POST',
                        headers: { 'X-WP-Nonce': '<?php echo $nonce; ?>' },
                        body: propData,
                    });

                    const propResponseJson = await propRes.json();

                    if (propResponseJson.id) {
                        const unitData = new FormData();
                        for (const key in this.unit) {
                            unitData.append(key, this.unit[key]);
                        }
                        if (this.$refs.unitImage.files.length > 0) {
                            unitData.append('featured_image', this.$refs.unitImage.files[0]);
                        }

                        await fetch(`<?php echo esc_url(rest_url('rental/v1/properties/')); ?>${propResponseJson.id}/units`, {
                            method: 'POST',
                            headers: { 'X-WP-Nonce': '<?php echo $nonce; ?>' },
                            body: unitData,
                        });
                        this.success = true;
                    } else { alert('Failed to create property. Please try again.'); }
                } catch (error) {
                    console.error(error);
                    alert('Something went wrong. Please try again.');
                }
                this.submitting = false;
            },
            resetForm() {
                this.step = 0; this.success = false;
                this.property = { title: '', description: '', address: '', city: '', state: '', country: '', postal_code: '', property_type: '', latitude: '', longitude: '' };
                this.unit = { title: '', description: '', rent_price: '', currency: 'USD', deposit: '', room_type: '', furnished: '', available_from: '', gender_preference: 'any', max_occupancy: 1, square_footage: '', floor_number: '', lease_duration_min: '', lease_duration_max: '', pet_policy: 'not_allowed', smoking_policy: 'not_allowed', utilities: '' };
                if (this.$refs.propertyImage) this.$refs.propertyImage.value = '';
                if (this.$refs.unitImage) this.$refs.unitImage.value = '';
            }
        };
    }
</script>

<?php get_footer(); ?>