<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { Check, ChevronsUpDown, Clock, ImagePlus, Star, Trash2 } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import type { PropType } from 'vue';
import { useToast } from 'vue-toastification';

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
import { index, update } from '@/routes/properties';

type StreetOption = {
    id: number;
    name: string;
};

type EditableImage = {
    id: number;
    name: string;
    url: string;
    is_main: boolean;
};

type LifecycleInfo = {
    posted_at: string;
    taken: boolean;
    next_action: 'deletion' | 'marked_as_taken';
    next_action_date: string;
    days_remaining: number;
};

const props = defineProps({
    property: {
        type: Object as PropType<App.Data.PropertyData>,
        required: true,
    },
    options: {
        type: Object as PropType<App.Data.PropertyFormOptionsData>,
        required: true,
    },
    lifecycle: {
        type: Object as PropType<LifecycleInfo>,
        required: true,
    },
});
const toast = useToast();

const LeaseType = {
    MediumTerm: 'medium_term',
    LongTerm: 'long_term',
} as const satisfies Record<string, App.Enums.PropertyLeaseType>;

type PropertyEditFormData = Omit<
    App.Data.Forms.PropertyFormData,
    'street' | 'floor' | 'bedrooms' | 'building_number'
> & {
    building_number: number | undefined;
    street: number | undefined;
    floor: number | undefined;
    bedrooms: number | undefined;
};

function toISODateTime(dateString: string | null): string | null {
    if (!dateString) {
        return null;
    }

    return `${dateString}T00:00:00Z`;
}

function getAdditionalInfo(locale: 'en' | 'he'): string {
    const value = props.property.additional_info;
    if (!value) {
        return '';
    }

    try {
        const parsed = JSON.parse(value) as { en?: string; he?: string };
        const localized = parsed[locale];
        if (typeof localized === 'string') {
            return localized;
        }
    } catch {
        return value;
    }

    return value;
}

const form = useForm<PropertyEditFormData>({
    contact_name: props.property.contact_name,
    contact_phone: props.property.contact_phone ?? '',
    price: props.property.price,
    square_meter: props.property.square_meter,
    bathrooms: props.property.bathrooms,
    access: props.property.access,
    kitchen_dining_room: props.property.kitchen_dining_room,
    porch_garden: props.property.porch_garden,
    succah_porch: props.property.succah_porch,
    air_conditioning: props.property.air_conditioning,
    apartment_condition: props.property.apartment_condition,
    additional_info_en: getAdditionalInfo('en'),
    additional_info_he: getAdditionalInfo('he'),
    has_dud_shemesh: props.property.has_dud_shemesh,
    has_machsan: props.property.has_machsan,
    has_parking_spot: props.property.has_parking_spot,
    neighbourhoods: [...(props.property.neighbourhoods ?? [])],
    building_number: props.property.building_number ?? undefined,
    street: undefined,
    floor: props.property.floor ?? undefined,
    type: props.property.type,
    available_from: props.property.available_from,
    available_to: props.property.available_to,
    bedrooms: props.property.bedrooms ?? undefined,
    furnished: props.property.furnished,
    temp_upload_id: null,
    image_media_ids: [],
    main_image_media_id: null,
});
const uploadingImages = ref(false);
const imageDeleteInProgressId = ref<number | null>(null);
const imageUploadError = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);
const propertyImages = ref<EditableImage[]>([]);
const streetComboboxOpen = ref(false);
const streetSearch = ref('');
const streetsLoading = ref(false);
const streetOptions = ref<StreetOption[]>([]);
let streetsFetchTimeout: ReturnType<typeof setTimeout> | undefined;
let streetsRequestId = 0;
const canAddMoreImages = computed(() => propertyImages.value.length < 6);

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

    if (selectedStreet) {
        return selectedStreet.name;
    }

    return props.property.street;
});

const availableFrom = computed<string>({
    get: () =>
        form.available_from?.slice(0, 10) ??
        props.property.available_from.slice(0, 10),
    set: (value) => {
        form.available_from = value ? `${value}T00:00:00Z` : '';
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

        if (!form.street) {
            const matchedStreet = response.data.streets.find(
                (street) =>
                    street.name.trim().toLowerCase() ===
                    props.property.street.trim().toLowerCase(),
            );

            if (matchedStreet) {
                form.street = matchedStreet.id;
            }
        }
    } finally {
        if (requestId === streetsRequestId) {
            streetsLoading.value = false;
        }
    }
}

async function loadPropertyImages(): Promise<void> {
    const response = await axios.get<{
        images: EditableImage[];
    }>(`/properties/${props.property.id}/images`);

    propertyImages.value = response.data.images;
}

function openImagePicker(): void {
    imageUploadError.value = null;
    fileInput.value?.click();
}

async function onImageInputChange(event: Event): Promise<void> {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    input.value = '';

    if (!file || !canAddMoreImages.value) {
        return;
    }

    imageUploadError.value = null;
    uploadingImages.value = true;

    try {
        const formData = new FormData();
        formData.append('image', file);

        const response = await axios.post<{ images: EditableImage[] }>(
            `/properties/${props.property.id}/images`,
            formData,
            {
                headers: {
                    'Content-Type': 'multipart/form-data',
                    Accept: 'application/json',
                },
            },
        );

        propertyImages.value = response.data.images;
        toast.success('Image uploaded successfully.');
    } catch (error) {
        if (axios.isAxiosError(error)) {
            imageUploadError.value =
                error.response?.data?.errors?.image?.[0] ??
                error.response?.data?.message ??
                'Upload failed. Please try again.';
        } else {
            imageUploadError.value = 'Upload failed. Please try again.';
        }
    } finally {
        uploadingImages.value = false;
    }
}

async function deleteImage(image: EditableImage): Promise<void> {
    const accepted = window.confirm(
        image.is_main
            ? 'Delete the main image now? This action is immediate and cannot be undone.'
            : 'Delete this image now? This action is immediate and cannot be undone.',
    );

    if (!accepted) {
        return;
    }

    imageDeleteInProgressId.value = image.id;
    imageUploadError.value = null;

    try {
        const response = await axios.delete<{ images: EditableImage[] }>(
            `/properties/${props.property.id}/images/${image.id}`,
        );

        propertyImages.value = response.data.images;
        toast.success(
            image.is_main
                ? 'Main image deleted and updated successfully.'
                : 'Image deleted successfully.',
        );
    } catch (error) {
        if (axios.isAxiosError(error)) {
            imageUploadError.value =
                error.response?.data?.message ?? 'Delete failed. Please try again.';
        } else {
            imageUploadError.value = 'Delete failed. Please try again.';
        }
    } finally {
        imageDeleteInProgressId.value = null;
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

onMounted(() => {
    void loadPropertyImages();
    void loadStreets();
});

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
    form.put(update.url(props.property.id));
}
</script>

<template>
    <Head title="Edit property" />

    <div class="flex items-center justify-between">
        <h1 class="text-lg font-semibold">Edit property</h1>
        <Button as-child>
            <Link :href="index()">Back to list</Link>
        </Button>
    </div>

    <div
        class="mt-4 rounded-lg border px-4 py-3 text-sm"
        :class="
            lifecycle.taken
                ? 'border-red-200 bg-red-50 text-red-800'
                : lifecycle.days_remaining <= 3
                  ? 'border-amber-200 bg-amber-50 text-amber-800'
                  : 'border-blue-200 bg-blue-50 text-blue-800'
        "
    >
        <div class="flex items-start gap-3">
            <Clock class="mt-0.5 size-4 shrink-0" />
            <div class="space-y-1">
                <p>
                    <span class="font-medium">Posted:</span>
                    {{ new Date(lifecycle.posted_at).toLocaleDateString() }}
                </p>
                <p v-if="lifecycle.next_action === 'marked_as_taken'">
                    <span class="font-medium">Next:</span>
                    This listing will be automatically marked as taken in
                    <span class="font-semibold">{{ lifecycle.days_remaining }}</span>
                    {{ lifecycle.days_remaining === 1 ? 'day' : 'days' }}.
                </p>
                <p v-else>
                    <span class="font-medium">Next:</span>
                    This listing is marked as taken and will be deleted in
                    <span class="font-semibold">{{ lifecycle.days_remaining }}</span>
                    {{ lifecycle.days_remaining === 1 ? 'day' : 'days' }}.
                </p>
            </div>
        </div>
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

        <Card class="space-y-4">
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <h2 class="text-sm font-semibold text-foreground/80">
                        Images
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Deleting and uploading happens immediately without
                        clicking Update.
                    </p>
                </div>
                <div class="text-sm text-muted-foreground tabular-nums">
                    {{ propertyImages.length }}/6
                </div>
            </div>

            <input
                ref="fileInput"
                type="file"
                accept="image/*"
                class="hidden"
                @change="onImageInputChange"
            />

            <button
                type="button"
                class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="uploadingImages || !canAddMoreImages"
                @click="openImagePicker"
            >
                <span class="flex items-center gap-2">
                    <ImagePlus class="h-4 w-4" />
                    <span v-if="uploadingImages">Uploading...</span>
                    <span v-else-if="!propertyImages.length">Upload image</span>
                    <span v-else-if="canAddMoreImages">Upload another image</span>
                    <span v-else>Maximum images reached</span>
                </span>
                <span class="text-xs text-muted-foreground">Browse</span>
            </button>

            <p v-if="imageUploadError" class="text-sm text-red-600">
                {{ imageUploadError }}
            </p>

            <div v-if="propertyImages.length" class="grid gap-3">
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <div
                        v-for="image in propertyImages"
                        :key="image.id"
                        class="group relative overflow-hidden rounded-md border border-input bg-muted"
                    >
                        <img
                            :src="image.url"
                            :alt="image.name"
                            class="h-28 w-full object-cover"
                        />

                        <span
                            v-if="image.is_main"
                            class="absolute top-2 left-2 inline-flex items-center gap-1 rounded-full bg-black/60 px-2 py-1 text-xs font-medium text-white backdrop-blur-sm"
                        >
                            <Star class="h-3.5 w-3.5 fill-current" />
                            Main
                        </span>

                        <button
                            type="button"
                            class="absolute top-2 right-2 inline-flex h-8 w-8 items-center justify-center rounded-md bg-black/60 text-white opacity-100 backdrop-blur-sm transition hover:bg-black/70 sm:opacity-0 sm:group-hover:opacity-100"
                            :disabled="imageDeleteInProgressId === image.id"
                            @click="deleteImage(image)"
                            aria-label="Delete image"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>
            <p v-else class="text-sm text-muted-foreground">
                No images uploaded yet.
            </p>
        </Card>

        <div class="flex items-center gap-4">
            <Button
                type="submit"
                :disabled="form.processing || uploadingImages"
            >
                Update
            </Button>
        </div>
    </FormKit>
</template>
