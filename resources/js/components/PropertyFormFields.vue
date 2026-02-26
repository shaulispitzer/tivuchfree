<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const { t } = useI18n();

type Option = {
    value: string;
    label: string;
};

type PropertyFormOptions = {
    neighbourhoods: Option[];
    lease_types: Option[];
    furnished: Option[];
    access: Option[];
    kitchen_dining_room: Option[];
    porch_garden: Option[];
    air_conditioning: Option[];
    apartment_condition: Option[];
};

type Property = {
    neighbourhoods: string[];
    price: number | null;
    street: string;
    building_number: string | null;
    floor: number;
    type: string;
    available_from: string;
    available_to: string | null;
    bedrooms: number;
    square_meter: number | null;
    furnished: string;
    taken: boolean;
    bathrooms: number | null;
    access: string | null;
    kitchen_dining_room: string | null;
    porch_garden: string | null;
    succah_porch: boolean;
    air_conditioning: string | null;
    apartment_condition: string | null;
    additional_info: string | null;
    has_dud_shemesh: boolean;
    has_machsan: boolean;
    has_parking_spot: boolean;
};

type Props = {
    property?: Property;
    options: PropertyFormOptions;
    errors: Record<string, string>;
    requireMainImage?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    property: undefined,
    requireMainImage: false,
});

const toDateValue = (value?: string | null) =>
    value ? value.split('T')[0] : '';
</script>

<template>
    <div class="grid gap-6">
        <div class="grid gap-2">
            <Label for="neighbourhoods">Neighbourhoods</Label>
            <select
                id="neighbourhoods"
                name="neighbourhoods[]"
                multiple
                class="min-h-32 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
            >
                <option
                    v-for="option in props.options.neighbourhoods"
                    :key="option.value"
                    :value="option.value"
                    :selected="
                        props.property?.neighbourhoods?.includes(
                            option.value,
                        ) ?? false
                    "
                >
                    {{ option.label }}
                </option>
            </select>
            <p class="text-xs text-muted-foreground">
                Select between 1 and 3 neighbourhoods.
            </p>
            <InputError :message="props.errors.neighbourhoods" />
        </div>

        <div class="grid gap-2">
            <Label for="price">{{ t('common.price') }}</Label>
            <Input
                id="price"
                name="price"
                type="number"
                step="0.01"
                min="0"
                :default-value="props.property?.price ?? ''"
            />
            <InputError :message="props.errors.price" />
        </div>

        <div class="grid gap-2">
            <Label for="street">Street</Label>
            <Input
                id="street"
                name="street"
                required
                :default-value="props.property?.street ?? ''"
            />
            <InputError :message="props.errors.street" />
        </div>

        <div class="grid gap-2">
            <Label for="building_number">Building number</Label>
            <Input
                id="building_number"
                name="building_number"
                :default-value="props.property?.building_number ?? ''"
            />
            <InputError :message="props.errors.building_number" />
        </div>

        <div class="grid gap-2">
            <Label for="floor">Floor</Label>
            <Input
                id="floor"
                name="floor"
                type="number"
                step="0.1"
                required
                :default-value="props.property?.floor ?? ''"
            />
            <InputError :message="props.errors.floor" />
        </div>

        <div class="grid gap-2">
            <Label for="type">Type</Label>
            <select
                id="type"
                name="type"
                required
                class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                :value="props.property?.type ?? ''"
            >
                <option value="" disabled>Select type</option>
                <option
                    v-for="option in props.options.lease_types"
                    :key="option.value"
                    :value="option.value"
                >
                    {{ option.label }}
                </option>
            </select>
            <InputError :message="props.errors.type" />
        </div>

        <div class="grid gap-2">
            <Label for="available_from">Available from</Label>
            <Input
                id="available_from"
                name="available_from"
                type="date"
                required
                :default-value="toDateValue(props.property?.available_from)"
            />
            <InputError :message="props.errors.available_from" />
        </div>

        <div class="grid gap-2">
            <Label for="available_to"
                >Available to (required for medium term)</Label
            >
            <Input
                id="available_to"
                name="available_to"
                type="date"
                :default-value="toDateValue(props.property?.available_to)"
            />
            <InputError :message="props.errors.available_to" />
        </div>

        <div class="grid gap-2">
            <Label for="bedrooms">Bedrooms</Label>
            <Input
                id="bedrooms"
                name="bedrooms"
                type="number"
                step="0.1"
                min="0"
                required
                :default-value="props.property?.bedrooms ?? ''"
            />
            <InputError :message="props.errors.bedrooms" />
        </div>

        <div class="grid gap-2">
            <Label for="square_meter">Square meter</Label>
            <Input
                id="square_meter"
                name="square_meter"
                type="number"
                min="0"
                :default-value="props.property?.square_meter ?? ''"
            />
            <InputError :message="props.errors.square_meter" />
        </div>

        <div class="grid gap-2">
            <Label for="furnished">Furnished</Label>
            <select
                id="furnished"
                name="furnished"
                required
                class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                :value="props.property?.furnished ?? ''"
            >
                <option value="" disabled>Select furnished status</option>
                <option
                    v-for="option in props.options.furnished"
                    :key="option.value"
                    :value="option.value"
                >
                    {{ option.label }}
                </option>
            </select>
            <InputError :message="props.errors.furnished" />
        </div>

        <div class="grid gap-2">
            <Label for="bathrooms">Bathrooms</Label>
            <Input
                id="bathrooms"
                name="bathrooms"
                type="number"
                min="0"
                :default-value="props.property?.bathrooms ?? ''"
            />
            <InputError :message="props.errors.bathrooms" />
        </div>

        <div class="grid gap-2">
            <Label for="access">Access</Label>
            <select
                id="access"
                name="access"
                class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                :value="props.property?.access ?? ''"
            >
                <option value="">Select access</option>
                <option
                    v-for="option in props.options.access"
                    :key="option.value"
                    :value="option.value"
                >
                    {{ option.label }}
                </option>
            </select>
            <InputError :message="props.errors.access" />
        </div>

        <div class="grid gap-2">
            <Label for="kitchen_dining_room"
                >Separate kitchen dining room</Label
            >
            <select
                id="kitchen_dining_room"
                name="kitchen_dining_room"
                class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                :value="props.property?.kitchen_dining_room ?? ''"
            >
                <option value="">Select option</option>
                <option
                    v-for="option in props.options.kitchen_dining_room"
                    :key="option.value"
                    :value="option.value"
                >
                    {{ option.label }}
                </option>
            </select>
            <InputError :message="props.errors.kitchen_dining_room" />
        </div>

        <div class="grid gap-2">
            <Label for="porch_garden">Porch/Garden</Label>
            <select
                id="porch_garden"
                name="porch_garden"
                class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                :value="props.property?.porch_garden ?? ''"
            >
                <option value="">Select option</option>
                <option
                    v-for="option in props.options.porch_garden"
                    :key="option.value"
                    :value="option.value"
                >
                    {{ option.label }}
                </option>
            </select>
            <InputError :message="props.errors.porch_garden" />
        </div>

        <div class="grid gap-2">
            <Label for="air_conditioning">Air conditioning</Label>
            <select
                id="air_conditioning"
                name="air_conditioning"
                class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                :value="props.property?.air_conditioning ?? ''"
            >
                <option value="">Select option</option>
                <option
                    v-for="option in props.options.air_conditioning"
                    :key="option.value"
                    :value="option.value"
                >
                    {{ option.label }}
                </option>
            </select>
            <InputError :message="props.errors.air_conditioning" />
        </div>

        <div class="grid gap-2">
            <Label for="apartment_condition">Apartment condition</Label>
            <select
                id="apartment_condition"
                name="apartment_condition"
                class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                :value="props.property?.apartment_condition ?? ''"
            >
                <option value="">Select option</option>
                <option
                    v-for="option in props.options.apartment_condition"
                    :key="option.value"
                    :value="option.value"
                >
                    {{ option.label }}
                </option>
            </select>
            <InputError :message="props.errors.apartment_condition" />
        </div>

        <div class="grid gap-2">
            <Label for="additional_info">Additional info</Label>
            <textarea
                id="additional_info"
                name="additional_info"
                class="min-h-[120px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                :value="props.property?.additional_info ?? ''"
            ></textarea>
            <InputError :message="props.errors.additional_info" />
        </div>

        <div class="grid gap-3">
            <Label>Booleans</Label>

            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="taken" value="0" />
                <input
                    id="taken"
                    type="checkbox"
                    name="taken"
                    value="1"
                    class="h-4 w-4 rounded border-input"
                    :checked="props.property?.taken ?? false"
                />
                Taken
            </label>
            <InputError :message="props.errors.taken" />

            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="succah_porch" value="0" />
                <input
                    id="succah_porch"
                    type="checkbox"
                    name="succah_porch"
                    value="1"
                    class="h-4 w-4 rounded border-input"
                    :checked="props.property?.succah_porch ?? false"
                />
                Succah porch
            </label>
            <InputError :message="props.errors.succah_porch" />
        </div>

        <div class="grid gap-3">
            <Label>Amenities</Label>

            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="has_dud_shemesh" value="0" />
                <input
                    id="has_dud_shemesh"
                    type="checkbox"
                    name="has_dud_shemesh"
                    value="1"
                    class="h-4 w-4 rounded border-input"
                    :checked="props.property?.has_dud_shemesh ?? false"
                />
                Dud shemesh
            </label>
            <InputError :message="props.errors.has_dud_shemesh" />

            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="has_machsan" value="0" />
                <input
                    id="has_machsan"
                    type="checkbox"
                    name="has_machsan"
                    value="1"
                    class="h-4 w-4 rounded border-input"
                    :checked="props.property?.has_machsan ?? false"
                />
                Machsan
            </label>
            <InputError :message="props.errors.has_machsan" />

            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="has_parking_spot" value="0" />
                <input
                    id="has_parking_spot"
                    type="checkbox"
                    name="has_parking_spot"
                    value="1"
                    class="h-4 w-4 rounded border-input"
                    :checked="props.property?.has_parking_spot ?? false"
                />
                Parking spot
            </label>
            <InputError :message="props.errors.has_parking_spot" />
        </div>

        <div class="grid gap-2">
            <Label for="main_image">Main image</Label>
            <input
                id="main_image"
                name="main_image"
                type="file"
                accept="image/*"
                :required="props.requireMainImage"
            />
            <InputError :message="props.errors.main_image" />
        </div>

        <div class="grid gap-2">
            <Label for="images">Additional images (up to 4)</Label>
            <input
                id="images"
                name="images[]"
                type="file"
                accept="image/*"
                multiple
            />
            <InputError :message="props.errors.images" />
        </div>
    </div>
</template>
