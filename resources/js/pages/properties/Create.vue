<script setup lang="ts">
import axios from 'axios';
import { Check, ChevronsUpDown } from 'lucide-vue-next';
import type { PropType } from 'vue';

import PropertyImageUploader from '@/components/PropertyImageUploader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { index, store } from '@/routes/properties';

type StreetOption = {
    id: number;
    name: string;
};

const props = defineProps({
    options: {
        type: Object as PropType<App.Data.PropertyFormOptionsData>,
        required: true,
    },
});

const LeaseType = {
    MediumTerm: 'medium_term',
    LongTerm: 'long_term',
} as const satisfies Record<string, App.Enums.PropertyLeaseType>;

type PropertyCreateFormData = Omit<
    App.Data.Forms.PropertyFormData,
    'street' | 'floor' | 'bedrooms' | 'building_number'
> & {
    building_number: number | undefined;
    street: number | undefined;
    floor: number | undefined;
    bedrooms: number | undefined;
};

function getLocalDateString(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// Convert YYYY-MM-DD to YYYY-MM-DDTHH:MM:SSZ
function toISODateTime(dateString: string | null): string | null {
    if (!dateString) return null;
    return `${dateString}T00:00:00Z`;
}

const form = useForm<PropertyCreateFormData>({
    contact_name: '',
    contact_phone: '',
    price: null,
    square_meter: null,
    bathrooms: null,
    access: null,
    kitchen_dining_room: null,
    porch_garden: null,
    succah_porch: false,
    air_conditioning: null,
    apartment_condition: null,
    additional_info_en: '',
    additional_info_he: '',
    has_dud_shemesh: false,
    has_machsan: false,
    has_parking_spot: false,
    neighbourhoods: [],
    building_number: undefined,
    street: undefined,
    floor: undefined,
    type: LeaseType.LongTerm,
    available_from: getLocalDateString(new Date()) + 'T00:00:00Z',
    available_to: null,
    bedrooms: undefined,
    furnished: 'no',
    temp_upload_id: null,
    image_media_ids: [],
    main_image_media_id: null,
});
const uploadingImages = ref(false);
const streetComboboxOpen = ref(false);
const streetSearch = ref('');
const streetsLoading = ref(false);
const streetOptions = ref<StreetOption[]>([]);
let streetsFetchTimeout: ReturnType<typeof setTimeout> | undefined;
let streetsRequestId = 0;

const isMediumTerm = computed(() => form.type === 'medium_term');
const neighbourhoodClientError = computed(() => {
    if (form.neighbourhoods.length === 0) {
        return 'Please select at least one neighbourhood.';
    }

    if (form.neighbourhoods.length > 3) {
        return 'You can select up to 3 neighbourhoods. Please remove at least one neighbourhood.';
    }

    if (new Set(form.neighbourhoods).size !== form.neighbourhoods.length) {
        return 'Each neighbourhood can only be selected once.';
    }

    return null;
});
const neighbourhoodLimitError = computed(() => {
    if (form.neighbourhoods.length > 3) {
        return 'You can select up to 3 neighbourhoods. Please remove at least one neighbourhood.';
    }

    return null;
});
const filteredStreetOptions = computed(() => {
    const query = streetSearch.value.trim().toLowerCase();
    const streets = streetOptions.value;

    if (query === '') {
        return streets;
    }

    return streets.filter((street) =>
        street.name.toLowerCase().includes(query),
    );
});
const selectedStreetName = computed(() => {
    const selectedStreet = streetOptions.value.find(
        (street) => street.id === form.street,
    );

    return selectedStreet?.name ?? '';
});

// Computed properties to handle date conversion
const availableFrom = computed<string>({
    get: () =>
        form.available_from?.slice(0, 10) ?? getLocalDateString(new Date()),
    set: (value) => {
        form.available_from = value
            ? `${value}T00:00:00Z`
            : getLocalDateString(new Date()) + 'T00:00:00Z';
    },
});

const availableTo = computed<string>({
    get: () => form.available_to?.slice(0, 10) ?? '',
    set: (value) => {
        form.available_to = value.length ? toISODateTime(value) : null;
    },
});

const contactNameInput = computed<string | undefined>({
    get: () => form.contact_name ?? undefined,
    set: (value) => {
        form.contact_name = value ?? null;
    },
});

const squareMeterInput = computed<number | undefined>({
    get: () => form.square_meter ?? undefined,
    set: (value) => {
        form.square_meter = value ?? null;
    },
});

const bathroomsInput = computed<number | undefined>({
    get: () => form.bathrooms ?? undefined,
    set: (value) => {
        form.bathrooms = value ?? null;
    },
});

const additionalInfoEnInput = computed<string | undefined>({
    get: () => form.additional_info_en ?? undefined,
    set: (value) => {
        form.additional_info_en = value ?? null;
    },
});

const additionalInfoHeInput = computed<string | undefined>({
    get: () => form.additional_info_he ?? undefined,
    set: (value) => {
        form.additional_info_he = value ?? null;
    },
});

async function loadStreets(): Promise<void> {
    if (form.neighbourhoods.length === 0) {
        streetOptions.value = [];
        streetsLoading.value = false;
        return;
    }

    const requestId = ++streetsRequestId;
    streetsLoading.value = true;

    try {
        const response = await axios.get<{ streets: StreetOption[] }>(
            '/properties/streets',
            {
                params: {
                    neighbourhoods: form.neighbourhoods,
                },
            },
        );

        if (requestId !== streetsRequestId) {
            return;
        }

        streetOptions.value = response.data.streets;
    } finally {
        if (requestId === streetsRequestId) {
            streetsLoading.value = false;
        }
    }
}

watch(
    () => form.type,
    (type) => {
        if (type !== 'medium_term') {
            form.available_to = null;
        }
    },
);

watch(
    () => form.neighbourhoods,
    () => {
        if (form.errors.neighbourhoods) {
            form.clearErrors('neighbourhoods');
        }

        form.street = undefined;
        streetSearch.value = '';

        if (form.errors.street) {
            form.clearErrors('street');
        }

        if (streetsFetchTimeout) {
            clearTimeout(streetsFetchTimeout);
        }

        streetsFetchTimeout = setTimeout(() => {
            void loadStreets();
        }, 200);
    },
    { deep: true },
);

onBeforeUnmount(() => {
    if (streetsFetchTimeout) {
        clearTimeout(streetsFetchTimeout);
    }
});

function selectStreet(streetId: number): void {
    form.street = streetId;
    form.clearErrors('street');
    streetComboboxOpen.value = false;
    streetSearch.value = '';
}

function submit(): void {
    if (uploadingImages.value) {
        return;
    }

    if (neighbourhoodClientError.value) {
        form.setError('neighbourhoods', neighbourhoodClientError.value);
        return;
    }

    form.clearErrors('neighbourhoods');
    form.post(store.url());
}
</script>

<template>
    <Head title="Create property" />

    <div class="flex items-center justify-between">
        <h1 class="text-lg font-semibold">Create property</h1>
        <Button as-child>
            <Link :href="index()">Back to list</Link>
        </Button>
    </div>

    <FormKit
        type="form"
        :actions="false"
        class="mx-auto mt-6 w-full max-w-6xl space-y-6 px-4 sm:px-6"
        form-class="space-y-6"
        @submit="submit"
    >
        <Card>
            <h2 class="text-sm font-semibold text-foreground/80">Contact</h2>
            <div class="grid gap-4 md:grid-cols-2">
                <div class="grid gap-2">
                    <FormKit
                        v-model="contactNameInput"
                        type="text"
                        name="contact_name"
                        label="Contact Name"
                        placeholder="Optional"
                    />
                    <div
                        v-if="form.errors.contact_name"
                        class="text-sm text-red-600"
                    >
                        {{ form.errors.contact_name }}
                    </div>
                </div>

                <div class="grid gap-2">
                    <FormKit
                        v-model="form.contact_phone"
                        type="text"
                        name="contact_phone"
                        label="Contact Phone"
                        validation="required"
                        label-class="required-asterisk"
                    />
                    <div
                        v-if="form.errors.contact_phone"
                        class="text-sm text-red-600"
                    >
                        {{ form.errors.contact_phone }}
                    </div>
                </div>
            </div>
        </Card>

        <Card>
            <h2 class="text-sm font-semibold text-foreground/80">Location</h2>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <div class="grid gap-3 md:col-span-2">
                    <Label class="required-asterisk">Neighbourhoods</Label>
                    <Select v-model="form.neighbourhoods" multiple>
                        <SelectTrigger class="w-full border-0 shadow-sm">
                            <SelectValue
                                placeholder="Select 1 to 3 neighbourhoods"
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="option in props.options.neighbourhoods"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <div
                        v-if="neighbourhoodLimitError"
                        class="text-sm font-medium text-red-600"
                    >
                        {{ neighbourhoodLimitError }}
                    </div>
                    <div
                        v-else-if="form.errors.neighbourhoods"
                        class="text-sm text-red-600"
                    >
                        {{ form.errors.neighbourhoods }}
                    </div>
                    <div
                        v-if="form.errors['neighbourhoods.0']"
                        class="text-sm text-red-600"
                    >
                        {{ form.errors['neighbourhoods.0'] }}
                    </div>
                </div>

                <div class="grid gap-2 md:col-span-2 xl:col-span-1">
                    <Label class="required-asterisk">Street</Label>
                    <Popover v-model:open="streetComboboxOpen">
                        <PopoverTrigger as-child>
                            <Button
                                variant="outline"
                                role="combobox"
                                :aria-expanded="streetComboboxOpen"
                                class="w-full justify-between border-0 font-normal shadow-sm"
                            >
                                <span class="truncate">
                                    {{
                                        selectedStreetName ||
                                        (form.neighbourhoods.length > 0
                                            ? 'Select street'
                                            : 'Select neighbourhood first')
                                    }}
                                </span>
                                <ChevronsUpDown
                                    class="ml-2 size-4 shrink-0 opacity-50"
                                />
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent
                            class="w-(--reka-popover-trigger-width) p-2"
                        >
                            <Input
                                v-model="streetSearch"
                                class="mb-2 h-9"
                                placeholder="Search street..."
                                :disabled="form.neighbourhoods.length === 0"
                            />
                            <div class="max-h-60 overflow-y-auto">
                                <p
                                    v-if="form.neighbourhoods.length === 0"
                                    class="py-6 text-center text-sm text-muted-foreground"
                                >
                                    Select at least one neighbourhood first.
                                </p>
                                <p
                                    v-else-if="streetsLoading"
                                    class="py-6 text-center text-sm text-muted-foreground"
                                >
                                    Loading streets...
                                </p>
                                <p
                                    v-else-if="
                                        filteredStreetOptions.length === 0
                                    "
                                    class="py-6 text-center text-sm text-muted-foreground"
                                >
                                    No street found.
                                </p>
                                <div v-else class="space-y-1">
                                    <button
                                        v-for="streetOption in filteredStreetOptions"
                                        :key="streetOption.id"
                                        type="button"
                                        class="flex w-full items-center rounded-sm px-2 py-1.5 text-sm hover:bg-accent hover:text-accent-foreground"
                                        @click="selectStreet(streetOption.id)"
                                    >
                                        <span>{{ streetOption.name }}</span>
                                        <Check
                                            :class="
                                                cn(
                                                    'ml-auto size-4',
                                                    form.street ===
                                                        streetOption.id
                                                        ? 'opacity-100'
                                                        : 'opacity-0',
                                                )
                                            "
                                        />
                                    </button>
                                </div>
                            </div>
                        </PopoverContent>
                    </Popover>
                    <div v-if="form.errors.street" class="text-sm text-red-600">
                        {{ form.errors.street }}
                    </div>
                </div>

                <div class="grid gap-2">
                    <FormKit
                        v-model="form.building_number"
                        type="number"
                        name="building_number"
                        label="Building number"
                        step="1"
                        number
                        validation="number|min:1"
                        label-class="required-asterisk"
                    />
                    <div
                        v-if="form.errors.building_number"
                        class="text-sm text-red-600"
                    >
                        {{ form.errors.building_number }}
                    </div>
                </div>

                <div class="grid gap-2">
                    <FormKit
                        v-model="form.floor"
                        type="number"
                        name="floor"
                        label="Floor"
                        step="0.5"
                        number
                        validation="number"
                        label-class="required-asterisk"
                    />
                    <div v-if="form.errors.floor" class="text-sm text-red-600">
                        {{ form.errors.floor }}
                    </div>
                </div>
            </div>
        </Card>

        <Card>
            <h2 class="text-sm font-semibold text-foreground/80">
                Property details
            </h2>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="grid gap-2">
                    <FormKit
                        v-model="form.type"
                        type="select"
                        name="type"
                        label="Type"
                        placeholder="Select type"
                        :options="props.options.lease_types"
                        validation="required"
                        label-class="required-asterisk"
                    />
                    <div v-if="form.errors.type" class="text-sm text-red-600">
                        {{ form.errors.type }}
                    </div>
                </div>

                <div class="grid gap-2">
                    <FormKit
                        v-model="availableFrom"
                        type="date"
                        name="available_from"
                        label="Available from"
                        validation="required"
                        label-class="required-asterisk"
                    />
                    <div
                        v-if="form.errors.available_from"
                        class="text-sm text-red-600"
                    >
                        {{ form.errors.available_from }}
                    </div>
                </div>

                <div v-if="isMediumTerm" class="grid gap-2">
                    <FormKit
                        v-model="availableTo"
                        type="date"
                        name="available_to"
                        label="Available to"
                        validation="required"
                        label-class="required-asterisk"
                    />
                    <div
                        v-if="form.errors.available_to"
                        class="text-sm text-red-600"
                    >
                        {{ form.errors.available_to }}
                    </div>
                </div>

                <div class="grid gap-2">
                    <FormKit
                        v-model="form.bedrooms"
                        type="number"
                        name="bedrooms"
                        label="Bedrooms"
                        step="0.5"
                        number
                        validation="required|min:1|max:10"
                        label-class="required-asterisk"
                    />
                    <div
                        v-if="form.errors.bedrooms"
                        class="text-sm text-red-600"
                    >
                        {{ form.errors.bedrooms }}
                    </div>
                </div>

                <div class="grid gap-2">
                    <FormKit
                        v-model="squareMeterInput"
                        type="number"
                        name="square_meter"
                        label="Square meter"
                        step="1"
                        number
                        validation="number|min:0"
                    />
                    <div
                        v-if="form.errors.square_meter"
                        class="text-sm text-red-600"
                    >
                        {{ form.errors.square_meter }}
                    </div>
                </div>

                <div class="grid gap-2">
                    <FormKit
                        v-model="bathroomsInput"
                        type="number"
                        name="bathrooms"
                        label="Bathrooms"
                        step="1"
                        number
                        validation="number|min:0"
                    />
                    <div
                        v-if="form.errors.bathrooms"
                        class="text-sm text-red-600"
                    >
                        {{ form.errors.bathrooms }}
                    </div>
                </div>

                <div class="grid gap-2">
                    <FormKit
                        v-model="form.furnished"
                        type="select"
                        name="furnished"
                        label="Furnished"
                        placeholder="Select furnished status"
                        :options="props.options.furnished"
                        validation="required"
                        label-class="required-asterisk"
                    />
                    <div
                        v-if="form.errors.furnished"
                        class="text-sm text-red-600"
                    >
                        {{ form.errors.furnished }}
                    </div>
                </div>

                <div class="grid gap-2">
                    <FormKit
                        v-model="form.access"
                        type="select"
                        name="access"
                        label="Access"
                        placeholder="Select access"
                        :options="props.options.access"
                    />
                    <div v-if="form.errors.access" class="text-sm text-red-600">
                        {{ form.errors.access }}
                    </div>
                </div>

                <div class="grid gap-2">
                    <FormKit
                        v-model="form.kitchen_dining_room"
                        type="select"
                        name="kitchen_dining_room"
                        label="Separate kitchen dining room"
                        placeholder="Select option"
                        :options="props.options.kitchen_dining_room"
                    />
                    <div
                        v-if="form.errors.kitchen_dining_room"
                        class="text-sm text-red-600"
                    >
                        {{ form.errors.kitchen_dining_room }}
                    </div>
                </div>

                <div class="grid gap-2">
                    <FormKit
                        v-model="form.porch_garden"
                        type="select"
                        name="porch_garden"
                        label="Porch/Garden"
                        placeholder="Select option"
                        :options="props.options.porch_garden"
                    />
                    <div
                        v-if="form.errors.porch_garden"
                        class="text-sm text-red-600"
                    >
                        {{ form.errors.porch_garden }}
                    </div>
                </div>

                <div class="grid gap-2">
                    <FormKit
                        v-model="form.air_conditioning"
                        type="select"
                        name="air_conditioning"
                        label="Air conditioning"
                        placeholder="Select option"
                        :options="props.options.air_conditioning"
                    />
                    <div
                        v-if="form.errors.air_conditioning"
                        class="text-sm text-red-600"
                    >
                        {{ form.errors.air_conditioning }}
                    </div>
                </div>

                <div class="grid gap-2">
                    <FormKit
                        v-model="form.apartment_condition"
                        type="select"
                        name="apartment_condition"
                        label="Apartment condition"
                        placeholder="Select option"
                        :options="props.options.apartment_condition"
                    />
                    <div
                        v-if="form.errors.apartment_condition"
                        class="text-sm text-red-600"
                    >
                        {{ form.errors.apartment_condition }}
                    </div>
                </div>
            </div>
        </Card>

        <Card>
            <h2 class="text-sm font-semibold text-foreground/80">Notes</h2>
            <div class="grid gap-4 lg:grid-cols-2">
                <div class="grid gap-2">
                    <FormKit
                        v-model="additionalInfoEnInput"
                        type="textarea"
                        name="additional_info_en"
                        label="Additional info (English)"
                        rows="4"
                    />
                    <div
                        v-if="form.errors.additional_info_en"
                        class="text-sm text-red-600"
                    >
                        {{ form.errors.additional_info_en }}
                    </div>
                </div>

                <div class="grid gap-2">
                    <FormKit
                        v-model="additionalInfoHeInput"
                        type="textarea"
                        name="additional_info_he"
                        label="Additional info (Hebrew)"
                        rows="4"
                    />
                    <div
                        v-if="form.errors.additional_info_he"
                        class="text-sm text-red-600"
                    >
                        {{ form.errors.additional_info_he }}
                    </div>
                </div>
            </div>
        </Card>

        <Card>
            <h2 class="text-sm font-semibold text-foreground/80">Amenities</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="grid gap-2">
                    <FormKit
                        v-model="form.succah_porch"
                        type="checkbox"
                        name="succah_porch"
                        label="Succah porch"
                    />
                    <div
                        v-if="form.errors.succah_porch"
                        class="text-sm text-red-600"
                    >
                        {{ form.errors.succah_porch }}
                    </div>
                </div>

                <div class="grid gap-2">
                    <FormKit
                        v-model="form.has_dud_shemesh"
                        type="checkbox"
                        name="has_dud_shemesh"
                        label="Dud shemesh"
                    />
                    <div
                        v-if="form.errors.has_dud_shemesh"
                        class="text-sm text-red-600"
                    >
                        {{ form.errors.has_dud_shemesh }}
                    </div>
                </div>

                <div class="grid gap-2">
                    <FormKit
                        v-model="form.has_machsan"
                        type="checkbox"
                        name="has_machsan"
                        label="Machsan"
                    />
                    <div
                        v-if="form.errors.has_machsan"
                        class="text-sm text-red-600"
                    >
                        {{ form.errors.has_machsan }}
                    </div>
                </div>

                <div class="grid gap-2">
                    <FormKit
                        v-model="form.has_parking_spot"
                        type="checkbox"
                        name="has_parking_spot"
                        label="Parking spot"
                    />
                    <div
                        v-if="form.errors.has_parking_spot"
                        class="text-sm text-red-600"
                    >
                        {{ form.errors.has_parking_spot }}
                    </div>
                </div>
            </div>
        </Card>

        <Card>
            <PropertyImageUploader
                v-model:tempUploadId="form.temp_upload_id"
                v-model:mediaIds="form.image_media_ids"
                v-model:mainMediaId="form.main_image_media_id"
                v-model:uploading="uploadingImages"
                :error="
                    form.errors.main_image_media_id ||
                    form.errors.image_media_ids ||
                    form.errors.temp_upload_id
                "
            />
        </Card>

        <div class="flex items-center gap-4">
            <Button
                type="submit"
                :disabled="form.processing || uploadingImages"
            >
                Create
            </Button>
        </div>
    </FormKit>
</template>
