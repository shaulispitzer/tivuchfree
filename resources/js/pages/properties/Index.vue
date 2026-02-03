<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import type { PropType } from 'vue';
import { Button } from '@/components/ui/button';
import { create, edit } from '@/routes/properties';

defineProps({
    properties: {
        type: Array as PropType<Array<App.Data.PropertyData>>,
        required: true,
    },
    can_create: {
        type: Boolean,
        required: true,
    },
});

const formatLabel = (value: string | null) =>
    value
        ? value
              .split('_')
              .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
              .join(' ')
        : '—';

const formatDate = (value: string | null) =>
    value ? value.split('T')[0] : '—';
</script>

<template>
    <Head title="Properties" />

    <div class="flex items-center justify-between">
        <h1 class="text-lg font-semibold">Properties</h1>
        <Button v-if="can_create" as-child>
            <Link :href="create()">Add property</Link>
        </Button>
    </div>

    <div
        v-if="properties.length === 0"
        class="mt-6 text-sm text-muted-foreground"
    >
        No properties have been added yet.
    </div>

    <div class="mt-6 grid gap-6">
        <div
            v-for="property in properties"
            :key="property.id"
            class="grid gap-4 rounded-lg border border-input p-4 shadow-xs"
        >
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <p class="text-sm text-muted-foreground">Listing ID</p>
                    <p class="text-base font-semibold">
                        {{ property.listing_id }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        Posted by {{ property.user.name }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        v-if="property.can_edit"
                        variant="secondary"
                        as-child
                    >
                        <Link :href="edit(property.id)">Edit</Link>
                    </Button>
                </div>
            </div>

            <img
                v-if="property.main_image_url"
                :src="property.main_image_url"
                alt="Property image"
                class="h-48 w-full rounded-md object-cover"
            />

            <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2 text-sm">
                    <div>
                        <span class="font-medium">Price:</span>
                        {{ property.price ?? '—' }}
                    </div>
                    <div>
                        <span class="font-medium">Neighbourhood ID:</span>
                        {{ property.neighbourhood_id ?? '—' }}
                    </div>
                    <div>
                        <span class="font-medium">Street:</span>
                        {{ property.street }}
                    </div>
                    <div>
                        <span class="font-medium">Building number:</span>
                        {{ property.building_number ?? '—' }}
                    </div>
                    <div>
                        <span class="font-medium">Floor:</span>
                        {{ property.floor }}
                    </div>
                    <div>
                        <span class="font-medium">Type:</span>
                        {{ formatLabel(property.type) }}
                    </div>
                    <div>
                        <span class="font-medium">Available from:</span>
                        {{ formatDate(property.available_from) }}
                    </div>
                    <div>
                        <span class="font-medium">Available to:</span>
                        {{ formatDate(property.available_to) }}
                    </div>
                    <div>
                        <span class="font-medium">Bedrooms:</span>
                        {{ property.bedrooms }}
                    </div>
                    <div>
                        <span class="font-medium">Square meter:</span>
                        {{ property.square_meter ?? '—' }}
                    </div>
                </div>

                <div class="space-y-2 text-sm">
                    <div>
                        <span class="font-medium">Furnished:</span>
                        {{ formatLabel(property.furnished) }}
                    </div>
                    <div>
                        <span class="font-medium">Bathrooms:</span>
                        {{ property.bathrooms ?? '—' }}
                    </div>
                    <div>
                        <span class="font-medium">Access:</span>
                        {{ formatLabel(property.access) }}
                    </div>
                    <div>
                        <span class="font-medium">Kitchen dining room:</span>
                        {{ formatLabel(property.kitchen_dining_room) }}
                    </div>
                    <div>
                        <span class="font-medium">Porch/Garden:</span>
                        {{ formatLabel(property.porch_garden) }}
                    </div>
                    <div>
                        <span class="font-medium">Succah porch:</span>
                        {{ property.succah_porch ? 'Yes' : 'No' }}
                    </div>
                    <div>
                        <span class="font-medium">Air conditioning:</span>
                        {{ formatLabel(property.air_conditioning) }}
                    </div>
                    <div>
                        <span class="font-medium">Apartment condition:</span>
                        {{ formatLabel(property.apartment_condition) }}
                    </div>
                    <div>
                        <span class="font-medium">Views:</span>
                        {{ property.views }}
                    </div>
                    <div>
                        <span class="font-medium">Taken:</span>
                        {{ property.taken ? 'Yes' : 'No' }}
                    </div>
                </div>
            </div>

            <div class="text-sm">
                <span class="font-medium">Additional info:</span>
                <span class="text-muted-foreground">
                    {{ property.additional_info || '—' }}
                </span>
            </div>

            <div class="text-sm">
                <span class="font-medium">Amenities:</span>
                <span class="text-muted-foreground">
                    <span v-if="property.has_dud_shemesh">Dud shemesh</span>
                    <span
                        v-if="
                            property.has_dud_shemesh &&
                            (property.has_machsan || property.has_parking_spot)
                        "
                        >,
                    </span>
                    <span v-if="property.has_machsan">Machsan</span>
                    <span
                        v-if="property.has_machsan && property.has_parking_spot"
                        >,
                    </span>
                    <span v-if="property.has_parking_spot">Parking spot</span>
                    <span
                        v-if="
                            !property.has_dud_shemesh &&
                            !property.has_machsan &&
                            !property.has_parking_spot
                        "
                        >—</span
                    >
                </span>
            </div>
        </div>
    </div>
</template>
