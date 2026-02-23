<?php
/**
 * Shortcode Template: Add Property (Landlord)
 * @package LeaseLink
 * @since   1.1.0
 */
use StudentRentalPlatform\SRP_Template_Loader;
?>
<div class="ll-dashboard">
    <?php SRP_Template_Loader::get_template('components/dashboard-nav.php', ['role' => 'landlord']); ?>
    <div class="ll-dashboard-content" x-data="leaselinkPropertyForm()">
        <h1 style="font-size:1.5rem;font-weight:700;margin-bottom:0.25rem;">Add Property</h1>
        <p style="color:var(--ll-gray);margin-bottom:2rem;">Fill in the details to register your property.</p>

        <!-- Step Indicator -->
        <div style="display:flex;gap:0.5rem;margin-bottom:2rem;">
            <template x-for="(label, index) in ['Property', 'Unit', 'Review']" :key="index">
                <div style="display:flex;align-items:center;gap:0.5rem;">
                    <span style="padding:0.375rem 0.75rem;border-radius:9999px;font-size:0.8125rem;font-weight:500;"
                        :style="step > index ? 'background:rgba(16,185,129,0.1);color:var(--ll-success);' : step === index ? 'background:var(--ll-primary);color:#fff;' : 'background:var(--ll-surface);color:var(--ll-gray);'"
                        x-text="label"></span>
                    <span x-show="index < 2" style="width:1.5rem;height:1px;background:var(--ll-border);"></span>
                </div>
            </template>
        </div>

        <!-- Step 1: Property -->
        <div x-show="step === 0 && !success" class="ll-card" style="padding:1.5rem;">
            <h2 style="font-size:1.125rem;font-weight:600;margin-bottom:1.25rem;">Property Information</h2>
            <div style="display:flex;flex-direction:column;gap:0.75rem;max-width:36rem;">
                <div>
                    <label style="display:block;font-size:0.875rem;font-weight:500;margin-bottom:0.25rem;">Property Name
                        *</label>
                    <input type="text" x-model="property.title" class="ll-input" placeholder="e.g., Sunview Apartments">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
                    <div><label style="display:block;font-size:0.875rem;font-weight:500;margin-bottom:0.25rem;">Address
                            *</label><input type="text" x-model="property.address" class="ll-input"
                            placeholder="Street"></div>
                    <div><label style="display:block;font-size:0.875rem;font-weight:500;margin-bottom:0.25rem;">City
                            *</label><input type="text" x-model="property.city" class="ll-input" placeholder="City">
                    </div>
                </div>
                <div>
                    <label style="display:block;font-size:0.875rem;font-weight:500;margin-bottom:0.25rem;">Property Type
                        *</label>
                    <select x-model="property.property_type" class="ll-input">
                        <option value="">Select...</option>
                        <option value="apartment">Apartment</option>
                        <option value="house">House</option>
                        <option value="condo">Condo</option>
                        <option value="dormitory">Dormitory</option>
                        <option value="shared_house">Shared House</option>
                        <option value="studio">Studio</option>
                    </select>
                </div>
                <div style="padding-top:0.75rem;text-align:right;">
                    <button @click="step = 1" class="ll-btn ll-btn-primary"
                        :disabled="!property.title || !property.address || !property.city">Next →</button>
                </div>
            </div>
        </div>

        <!-- Step 2: Unit -->
        <div x-show="step === 1 && !success" class="ll-card" style="padding:1.5rem;">
            <h2 style="font-size:1.125rem;font-weight:600;margin-bottom:1.25rem;">Unit Information</h2>
            <div style="display:flex;flex-direction:column;gap:0.75rem;max-width:36rem;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
                    <div><label style="display:block;font-size:0.875rem;font-weight:500;margin-bottom:0.25rem;">Unit
                            Name *</label><input type="text" x-model="unit.title" class="ll-input"
                            placeholder="e.g., Unit 2B"></div>
                    <div>
                        <label style="display:block;font-size:0.875rem;font-weight:500;margin-bottom:0.25rem;">Room Type
                            *</label>
                        <select x-model="unit.room_type" class="ll-input">
                            <option value="">Select...</option>
                            <option value="private_room">Private Room</option>
                            <option value="shared_room">Shared Room</option>
                            <option value="entire_unit">Entire Unit</option>
                            <option value="studio">Studio</option>
                        </select>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:0.75rem;">
                    <div><label style="display:block;font-size:0.875rem;font-weight:500;margin-bottom:0.25rem;">Rent (€)
                            *</label><input type="number" x-model="unit.rent_price" class="ll-input" placeholder="500"
                            min="1"></div>
                    <div><label style="display:block;font-size:0.875rem;font-weight:500;margin-bottom:0.25rem;">Deposit
                            (€)</label><input type="number" x-model="unit.deposit" class="ll-input" placeholder="1000">
                    </div>
                    <div><label style="display:block;font-size:0.875rem;font-weight:500;margin-bottom:0.25rem;">Area
                            (m²)</label><input type="number" x-model="unit.square_footage" class="ll-input"
                            placeholder="25"></div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
                    <div><label
                            style="display:block;font-size:0.875rem;font-weight:500;margin-bottom:0.25rem;">Available
                            From *</label><input type="date" x-model="unit.available_from" class="ll-input"></div>
                    <div>
                        <label style="display:block;font-size:0.875rem;font-weight:500;margin-bottom:0.25rem;">Furnished
                            *</label>
                        <select x-model="unit.furnished" class="ll-input">
                            <option value="">Select...</option>
                            <option value="furnished">Furnished</option>
                            <option value="unfurnished">Unfurnished</option>
                            <option value="partially_furnished">Partially</option>
                        </select>
                    </div>
                </div>
                <div style="padding-top:0.75rem;display:flex;justify-content:space-between;">
                    <button @click="step = 0" class="ll-btn ll-btn-secondary">← Back</button>
                    <button @click="step = 2" class="ll-btn ll-btn-primary"
                        :disabled="!unit.title || !unit.rent_price || !unit.available_from">Next →</button>
                </div>
            </div>
        </div>

        <!-- Step 3: Review -->
        <div x-show="step === 2 && !success">
            <div class="ll-card" style="padding:1.5rem;margin-bottom:1rem;">
                <h2 style="font-size:1.125rem;font-weight:600;margin-bottom:1rem;">Review Your Property</h2>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;">
                    <div>
                        <p
                            style="font-size:0.75rem;font-weight:600;color:var(--ll-gray);text-transform:uppercase;margin-bottom:0.5rem;">
                            Property</p>
                        <p style="font-size:0.875rem;margin:0 0 0.25rem;"><span
                                style="color:var(--ll-gray);">Name:</span> <strong x-text="property.title"></strong></p>
                        <p style="font-size:0.875rem;margin:0 0 0.25rem;"><span
                                style="color:var(--ll-gray);">Location:</span> <span
                                x-text="property.address + ', ' + property.city"></span></p>
                    </div>
                    <div>
                        <p
                            style="font-size:0.75rem;font-weight:600;color:var(--ll-gray);text-transform:uppercase;margin-bottom:0.5rem;">
                            Unit</p>
                        <p style="font-size:0.875rem;margin:0 0 0.25rem;"><span
                                style="color:var(--ll-gray);">Name:</span> <strong x-text="unit.title"></strong></p>
                        <p style="font-size:0.875rem;margin:0 0 0.25rem;"><span
                                style="color:var(--ll-gray);">Rent:</span> <span
                                x-text="'€' + unit.rent_price + '/month'"></span></p>
                    </div>
                </div>
            </div>
            <div style="display:flex;justify-content:space-between;">
                <button @click="step = 1" class="ll-btn ll-btn-secondary">← Back</button>
                <button @click="submitProperty()" class="ll-btn ll-btn-primary ll-btn-lg" :disabled="submitting">
                    <span x-show="!submitting">Submit Property</span><span x-show="submitting">Submitting...</span>
                </button>
            </div>
        </div>

        <!-- Success -->
        <div x-show="success" class="ll-card" style="padding:3rem;text-align:center;">
            <p style="font-size:1.25rem;font-weight:700;color:var(--ll-success);margin-bottom:0.5rem;">Property Created!
            </p>
            <p style="color:var(--ll-gray);margin-bottom:1.5rem;">Your property and unit have been created.</p>
            <div style="display:flex;gap:0.75rem;justify-content:center;">
                <a href="<?php echo home_url('/my-properties/'); ?>" class="ll-btn ll-btn-primary"
                    style="text-decoration:none;">My Properties</a>
                <button @click="resetForm()" class="ll-btn ll-btn-secondary">Add Another</button>
            </div>
        </div>
    </div>
</div>