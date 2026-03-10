<script setup lang="ts">
import { parseDate } from '@internationalized/date';
import axios from 'axios';
import {
    Check,
    ChevronsUpDown,
    CircleCheck,
    Clock,
    ImagePlus,
    Star,
    Trash2,
    User,
} from 'lucide-vue-next';
import type { DateValue } from 'reka-ui';
import type { PropType } from 'vue';

import { useToast } from 'vue-toastification';

import { router } from '@inertiajs/vue3';
import { cn } from '@/lib/utils';
import { index as adminPropertiesIndex } from '@/routes/admin/properties';
import { destroy, markAsTaken } from '@/routes/my-properties';
import { index, update } from '@/routes/properties';

const { t } = useI18n();
const page = usePage();
const currentLocale = computed(() =>
    page.props.locale === 'he' ? 'he' : 'en',
);
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
    adminEdit: {
        type: Boolean,
        default: false,
    },
});
const toast = useToast();

const LeaseType = {
    MediumTerm: 'medium_term',
    LongTerm: 'long_term',
} as const satisfies Record<string, App.Enums.PropertyLeaseType>;

type PropertyEditFormData = Omit<
    App.Data.Forms.PropertyFormData,
    | 'street'
    | 'floor'
    | 'bedrooms'
    | 'building_number'
    | 'contact_name'
    | 'access'
    | 'kitchen_dining_room'
    | 'porch_garden'
    | 'air_conditioning'
    | 'apartment_condition'
    | 'furnished'
> & {
    building_number: number | undefined;
    street: number | undefined;
    floor: number | undefined;
    bedrooms: number | undefined;
    contact_name: string;
    access: App.Enums.PropertyAccess | null;
    kitchen_dining_room: App.Enums.PropertyKitchenDiningRoom | null;
    porch_garden: App.Enums.PropertyPorchGarden | null;
    air_conditioning: App.Enums.PropertyAirConditioning | null;
    apartment_condition: App.Enums.PropertyApartmentCondition | null;
    furnished: App.Enums.PropertyFurnished | null;
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
    contact_name: props.property.contact_name ?? '',
    contact_phone: props.property.contact_phone ?? '',
    contact_phone_2: props.property.contact_phone_2 ?? null,
    email: null,
    price: props.property.price,
    square_meter: props.property.square_meter,
    bathrooms: props.property.bathrooms,
    access: props.property.access ?? null,
    kitchen_dining_room: props.property.kitchen_dining_room ?? null,
    porch_garden: props.property.porch_garden ?? null,
    succah_porch: props.property.succah_porch,
    air_conditioning: props.property.air_conditioning ?? null,
    apartment_condition: props.property.apartment_condition ?? null,
    additional_info:
        getAdditionalInfo(
            (page.props.locale as string) === 'he' ? 'he' : 'en',
        ) ?? null,
    additional_info_en: null,
    additional_info_he: null,
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
    furnished: props.property.furnished ?? null,
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

const translatedLeaseTypes = computed(() =>
    props.options.lease_types.map((option) => ({
        value: option.value,
        label: t(`propertyLeaseType.${option.value}`),
    })),
);

const translatedNeighbourhoods = computed(() =>
    props.options.neighbourhoods.map((option) => ({
        value: option.value,
        label: t(`neighbourhood.${option.value.replaceAll(' ', '')}`),
    })),
);

const translatedFurnished = computed(() =>
    props.options.furnished.map((option) => ({
        value: option.value,
        label: t(`propertyFurnished.${option.value}`),
    })),
);

const translatedAccess = computed(() =>
    props.options.access.map((option) => ({
        value: option.value,
        label: t(`propertyAccess.${option.value}`),
    })),
);

const translatedKitchenDiningRoom = computed(() =>
    props.options.kitchen_dining_room.map((option) => ({
        value: option.value,
        label: t(`propertyKitchenDiningRoom.${option.value}`),
    })),
);

const translatedPorchGarden = computed(() =>
    props.options.porch_garden.map((option) => ({
        value: option.value,
        label: t(`propertyPorchGarden.${option.value}`),
    })),
);

const translatedAirConditioning = computed(() =>
    props.options.air_conditioning.map((option) => ({
        value: option.value,
        label: t(`propertyAirConditioning.${option.value}`),
    })),
);

const translatedApartmentCondition = computed(() =>
    props.options.apartment_condition.map((option) => ({
        value: option.value,
        label: t(`propertyApartmentCondition.${option.value}`),
    })),
);

const neighbourhoodClientError = computed(() => {
    if (form.neighbourhoods.length === 0) {
        return t('common.selectAtLeastOneNeighbourhood');
    }

    if (form.neighbourhoods.length > 3) {
        return t('common.upToThreeNeighbourhoods');
    }

    if (new Set(form.neighbourhoods).size !== form.neighbourhoods.length) {
        return t('common.neighbourhoodUnique');
    }

    return null;
});

const neighbourhoodLimitError = computed(() => {
    if (form.neighbourhoods.length > 3) {
        return t('common.upToThreeNeighbourhoods');
    }

    return null;
});

const furnishedSelect = computed({
    get: () => form.furnished ?? '',
    set: (value) => {
        form.furnished = (value || null) as App.Enums.PropertyFurnished | null;
    },
});

const accessSelect = computed({
    get: () => form.access ?? '',
    set: (value) => {
        form.access = (value || null) as App.Enums.PropertyAccess | null;
    },
});

const kitchenDiningRoomSelect = computed({
    get: () => form.kitchen_dining_room ?? '',
    set: (value) => {
        form.kitchen_dining_room = (value ||
            null) as App.Enums.PropertyKitchenDiningRoom | null;
    },
});

const porchGardenSelect = computed({
    get: () => form.porch_garden ?? '',
    set: (value) => {
        form.porch_garden = (value ||
            null) as App.Enums.PropertyPorchGarden | null;
    },
});

const airConditioningSelect = computed({
    get: () => form.air_conditioning ?? '',
    set: (value) => {
        form.air_conditioning = (value ||
            null) as App.Enums.PropertyAirConditioning | null;
    },
});

const apartmentConditionSelect = computed({
    get: () => form.apartment_condition ?? '',
    set: (value) => {
        form.apartment_condition = (value ||
            null) as App.Enums.PropertyApartmentCondition | null;
    },
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

const contactPhone2Input = computed({
    get: () => form.contact_phone_2 ?? '',
    set: (value: string) => {
        form.contact_phone_2 = value.trim() || null;
    },
});

const emailInput = computed<string | undefined>({
    get: () => form.email ?? undefined,
    set: (value: string | undefined) => {
        form.email = value?.trim() || null;
    },
});

function toDateValue(value: string): DateValue | undefined {
    const trimmed = value?.trim();
    if (!trimmed) return undefined;

    const normalized = trimmed.includes('T') ? trimmed.slice(0, 10) : trimmed;
    try {
        return parseDate(normalized);
    } catch {
        return undefined;
    }
}

const availableFromDate = computed<DateValue | undefined>({
    get: () => toDateValue(availableFrom.value),
    set: (value) => {
        if (value) {
            availableFrom.value = value.toString();
        }
    },
});

const availableToDate = computed<DateValue | undefined>({
    get: () => toDateValue(availableTo.value),
    set: (value) => {
        availableTo.value = value ? value.toString() : '';
    },
});

const contactNameInput = computed<string | undefined>({
    get: () => form.contact_name ?? undefined,
    set: (value) => {
        form.contact_name = value ?? '';
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

const priceInput = computed<number | undefined>({
    get: () => form.price ?? undefined,
    set: (value) => {
        form.price = value ?? null;
    },
});

const additionalInfoInput = computed<string | undefined>({
    get: () => form.additional_info ?? undefined,
    set: (value) => {
        form.additional_info = value ?? null;
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
        toast.success(t('common.imageUploadedSuccess'));
    } catch (error) {
        if (axios.isAxiosError(error)) {
            imageUploadError.value =
                error.response?.data?.errors?.image?.[0] ??
                error.response?.data?.message ??
                t('common.uploadFailedTryAgain');
        } else {
            imageUploadError.value = t('common.uploadFailedTryAgain');
        }
    } finally {
        uploadingImages.value = false;
    }
}

async function deleteImage(image: EditableImage): Promise<void> {
    const accepted = window.confirm(
        image.is_main
            ? t('common.imageDeleteConfirmMain')
            : t('common.imageDeleteConfirm'),
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
                ? t('common.mainImageDeletedSuccess')
                : t('common.imageDeletedSuccess'),
        );
    } catch (error) {
        if (axios.isAxiosError(error)) {
            imageUploadError.value =
                error.response?.data?.message ??
                t('common.deleteFailedTryAgain');
        } else {
            imageUploadError.value = t('common.deleteFailedTryAgain');
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

const reportedTakenDaysRemaining = computed(() => {
    if (!props.property.reported_taken_at) return null;
    const reportedAt = new Date(
        props.property.reported_taken_at as unknown as string,
    );
    const expiresAt = new Date(reportedAt.getTime() + 3 * 24 * 60 * 60 * 1000);
    const diff = Math.ceil(
        (expiresAt.getTime() - Date.now()) / (1000 * 60 * 60 * 24),
    );
    return Math.max(0, diff);
});

const reportedTakenDate = computed(() => {
    if (!props.property.reported_taken_at) return null;
    return new Date(
        props.property.reported_taken_at as unknown as string,
    ).toLocaleDateString();
});

const cancellingReport = ref(false);

function cancelReport(): void {
    cancellingReport.value = true;
    router.patch(
        `/my-properties/${props.property.id}/cancel-report-taken`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                cancellingReport.value = false;
            },
        },
    );
}

const markAsTakenModalOpen = ref(false);
const deleteModalOpen = ref(false);
const feedbackSource = ref<string | null>(null);
const feedbackPrice = ref('');
const markingAsTaken = ref(false);
const deletingId = ref<number | null>(null);
const markAsTakenThenDelete = ref(false);

const feedbackOptions = [
    { value: 'tivuchfree', label: 'Tivuch Free' },
    { value: 'other_non_paid', label: 'Other (non-paid)' },
    { value: 'agent', label: 'Tivuch (agent)' },
    { value: 'other', label: 'Other' },
];

function openMarkAsTakenModal(): void {
    markAsTakenThenDelete.value = false;
    markAsTakenModalOpen.value = true;
    feedbackSource.value = null;
    feedbackPrice.value = '';
}

function openDeleteModal(): void {
    if (!props.lifecycle.taken) {
        markAsTakenThenDelete.value = true;
        markAsTakenModalOpen.value = true;
        feedbackSource.value = null;
        feedbackPrice.value = '';
    } else {
        deleteModalOpen.value = true;
    }
}

function handleMarkAsTaken(): void {
    const priceString =
        typeof feedbackPrice.value === 'string'
            ? feedbackPrice.value
            : String(feedbackPrice.value ?? '');
    const priceTrimmed = priceString.trim();
    const parsedPrice =
        priceTrimmed !== '' && Number.isFinite(Number(priceTrimmed))
            ? Number(priceTrimmed)
            : null;

    markingAsTaken.value = true;
    const propertyId = props.property.id;
    const thenDelete = markAsTakenThenDelete.value;

    router.patch(
        markAsTaken(propertyId).url,
        {
            how_got_taken: feedbackSource.value,
            price_taken_at: parsedPrice,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                markAsTakenModalOpen.value = false;
                markAsTakenThenDelete.value = false;
                if (thenDelete) {
                    deletingId.value = propertyId;
                    router.delete(destroy(propertyId).url, {
                        preserveScroll: true,
                        onSuccess: () => (deleteModalOpen.value = false),
                        onFinish: () => (deletingId.value = null),
                    });
                }
            },
            onFinish: () => {
                markingAsTaken.value = false;
            },
        },
    );
}

function handleConfirmDelete(): void {
    const propertyId = props.property.id;
    deletingId.value = propertyId;
    router.delete(destroy(propertyId).url, {
        preserveScroll: true,
        onSuccess: () => (deleteModalOpen.value = false),
        onFinish: () => (deletingId.value = null),
    });
}
</script>

<template>
    <Head :title="t('common.editProperty')" />

    <form
        class="mx-auto w-full max-w-5xl space-y-6 px-4 sm:px-6"
        @submit.prevent="submit"
    >
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-semibold">
                {{ t('common.editProperty') }}{{ adminEdit ? ' (Admin)' : '' }}
            </h1>
            <div class="flex flex-wrap items-center gap-2">
                <Button
                    v-if="lifecycle.next_action === 'deletion'"
                    size="sm"
                    :variant="lifecycle.taken ? 'destructive' : 'outline'"
                    :disabled="markingAsTaken || deletingId !== null"
                    type="button"
                    @click="openDeleteModal"
                >
                    <Trash2 class="mr-2 size-4" />
                    {{ t('common.delete') }}
                </Button>
                <Button as-child>
                    <Link
                        :href="adminEdit ? adminPropertiesIndex() : index()"
                        >{{ t('common.backToList') }}</Link
                    >
                </Button>
            </div>
        </div>

        <div
            v-if="adminEdit && property.user"
            class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700"
        >
            <div class="flex items-center gap-2">
                <User class="size-4 shrink-0" />
                <span class="font-medium">{{ t('common.postedBy') }}:</span>
                <span
                    >{{ property.user.name }} (ID: {{ property.user.id }})</span
                >
                <a
                    v-if="property.user.email"
                    :href="`mailto:${property.user.email}`"
                    class="text-primary underline hover:no-underline"
                >
                    {{ property.user.email }}
                </a>
            </div>
        </div>

        <div
            class="rounded-lg border px-4 py-3 text-sm"
            :class="
                lifecycle.taken
                    ? 'border-red-200 bg-red-50 text-red-800'
                    : lifecycle.days_remaining <= 3
                      ? 'border-amber-200 bg-amber-50 text-amber-800'
                      : 'border-blue-200 bg-blue-50 text-blue-800'
            "
        >
            <div class="flex w-full items-start gap-3">
                <Clock class="mt-0.5 size-4 shrink-0" />
                <div class="min-w-0 flex-1 space-y-1">
                    <p>
                        <span class="font-medium"
                            >{{ t('common.posted') }}:</span
                        >
                        {{ new Date(lifecycle.posted_at).toLocaleDateString() }}
                    </p>
                    <p v-if="lifecycle.next_action === 'marked_as_taken'">
                        <span class="font-medium">{{ t('common.next') }}:</span>
                        {{
                            t('common.markedAsTakenInDays', {
                                days: lifecycle.days_remaining,
                                multipledays:
                                    lifecycle.days_remaining &&
                                    lifecycle.days_remaining > 1
                                        ? t('common.days')
                                        : t('common.day'),
                            })
                        }}
                    </p>
                    <p v-else>
                        <span class="font-medium">{{ t('common.next') }}:</span>
                        {{
                            t('common.markedAsTakenAndDeletedInDays', {
                                days: lifecycle.days_remaining,
                                multipledays:
                                    lifecycle.days_remaining &&
                                    lifecycle.days_remaining > 1
                                        ? t('common.days')
                                        : t('common.day'),
                            })
                        }}
                    </p>
                </div>
                <Button
                    v-if="lifecycle.next_action === 'marked_as_taken'"
                    size="sm"
                    variant="secondary"
                    class="shrink-0"
                    type="button"
                    :disabled="markingAsTaken"
                    @click="openMarkAsTakenModal"
                >
                    <CircleCheck class="mr-2 size-4" />
                    {{ t('common.markAsTakenNow') }}
                </Button>
            </div>
        </div>

        <div
            v-if="property.reported_taken_at && !lifecycle.taken"
            class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600"
        >
            <div class="flex items-start justify-between gap-3">
                <div class="space-y-1">
                    <p class="font-semibold">
                        {{ t('common.reportedTakenWarningTitle') }}
                    </p>
                    <p>
                        {{
                            t('common.reportedTakenWarningBody', {
                                date: reportedTakenDate,
                                days: reportedTakenDaysRemaining,
                                multipledays:
                                    reportedTakenDaysRemaining &&
                                    reportedTakenDaysRemaining > 1
                                        ? t('common.days')
                                        : t('common.day'),
                            })
                        }}
                    </p>
                </div>
                <Button
                    size="sm"
                    variant="outline"
                    class="shrink-0 border-red-300 text-red-700 hover:bg-red-100"
                    type="button"
                    :disabled="cancellingReport"
                    @click="cancelReport"
                >
                    {{
                        cancellingReport
                            ? t('common.sending')
                            : t('common.cancelReport')
                    }}
                </Button>
            </div>
        </div>
        <Card>
            <h2 class="text-sm font-semibold text-foreground/80">
                {{ t('common.contactSection') }}
            </h2>
            <div class="grid gap-4 md:grid-cols-2">
                <div class="grid gap-2">
                    <FormKit
                        v-model="contactNameInput"
                        type="text"
                        name="contact_name"
                        :label="t('common.contactName')"
                        :placeholder="t('common.optional')"
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
                        type="tel"
                        name="contact_phone"
                        :label="t('common.contactPhone')"
                        validation="required|phone_uk_us_il"
                        label-class="required-asterisk"
                        :validation-messages="{
                            phone_uk_us_il: t('common.invalidPhone'),
                        }"
                    />
                    <div
                        v-if="form.errors.contact_phone"
                        class="text-sm text-red-600"
                    >
                        {{ form.errors.contact_phone }}
                    </div>
                </div>

                <div class="grid gap-2">
                    <FormKit
                        v-model="contactPhone2Input"
                        type="tel"
                        name="contact_phone_2"
                        :label="t('common.contactPhone2')"
                        validation="phone_uk_us_il"
                        :validation-messages="{
                            phone_uk_us_il: t('common.invalidPhone'),
                        }"
                    />
                    <div
                        v-if="form.errors.contact_phone_2"
                        class="text-sm text-red-600"
                    >
                        {{ form.errors.contact_phone_2 }}
                    </div>
                </div>
                <div class="grid gap-2">
                    <FormKit
                        v-model="emailInput"
                        type="email"
                        name="email"
                        :label="t('common.email')"
                        validation="email"
                    />
                    <div v-if="form.errors.email" class="text-sm text-red-600">
                        {{ form.errors.email }}
                    </div>
                </div>
            </div>
        </Card>

        <Card>
            <h2 class="text-sm font-semibold text-foreground/80">
                {{ t('common.location') }}
            </h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="grid gap-2 sm:col-span-2">
                    <Label :requiredStar="true">
                        {{ t('common.neighbourhood', 2) }}
                    </Label>
                    <Select v-model="form.neighbourhoods" multiple>
                        <SelectTrigger class="w-full border-0 shadow-sm">
                            <SelectValue
                                :placeholder="
                                    t('common.selectOneToThreeNeighbourhoods')
                                "
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="option in translatedNeighbourhoods"
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

                <div class="grid gap-2 sm:col-span-2">
                    <Label :requiredStar="true">
                        {{ t('common.street') }}
                    </Label>
                    <Popover v-model:open="streetComboboxOpen">
                        <PopoverTrigger as-child>
                            <Button
                                variant="outline"
                                role="combobox"
                                :aria-expanded="streetComboboxOpen"
                                class="w-full min-w-0 justify-between border-0 font-normal shadow-sm"
                            >
                                <span class="truncate">
                                    {{
                                        selectedStreetName ||
                                        (form.neighbourhoods.length > 0
                                            ? t('common.selectStreet')
                                            : t(
                                                  'common.selectNeighbourhoodFirst',
                                              ))
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
                                :placeholder="t('common.searchStreet')"
                                :disabled="form.neighbourhoods.length === 0"
                            />
                            <div class="max-h-60 overflow-y-auto">
                                <p
                                    v-if="form.neighbourhoods.length === 0"
                                    class="py-6 text-center text-sm text-muted-foreground"
                                >
                                    {{
                                        t(
                                            'common.selectAtLeastOneNeighbourhoodFirst',
                                        )
                                    }}
                                </p>
                                <p
                                    v-else-if="streetsLoading"
                                    class="py-6 text-center text-sm text-muted-foreground"
                                >
                                    {{ t('common.loadingStreets') }}
                                </p>
                                <p
                                    v-else-if="
                                        filteredStreetOptions.length === 0
                                    "
                                    class="py-6 text-center text-sm text-muted-foreground"
                                >
                                    {{ t('common.noStreetFound') }}
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

                <div class="grid w-full max-w-full min-w-0 gap-2 md:max-w-32">
                    <FormKit
                        v-model="form.building_number"
                        type="number"
                        name="building_number"
                        :label="t('common.buildingNumber')"
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

                <div class="grid w-full max-w-full min-w-0 gap-2 md:max-w-32">
                    <FormKit
                        v-model="form.floor"
                        type="number"
                        name="floor"
                        :label="t('common.floor')"
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
                {{ t('common.propertyDetails') }}
            </h2>
            <div class="space-y-6">
                <div class="flex flex-wrap items-start justify-start gap-5">
                    <div
                        class="grid w-32 max-w-full min-w-32 shrink-0 gap-2 sm:w-38"
                    >
                        <Label requiredStar>
                            {{ t('common.type') }}
                        </Label>
                        <MenuSelect
                            v-model="form.type"
                            :options="translatedLeaseTypes"
                            :placeholder="t('common.selectType')"
                            trigger-class="w-full border-0 shadow-sm justify-between"
                        />
                        <div
                            v-if="form.errors.type"
                            class="text-sm text-red-600"
                        >
                            {{ form.errors.type }}
                        </div>
                    </div>

                    <div class="flex flex-wrap items-start gap-5">
                        <div
                            class="grid w-44 max-w-full min-w-44 shrink-0 gap-2 sm:w-44"
                        >
                            <Label requiredStar>
                                {{ t('common.availableFrom') }}
                            </Label>
                            <DatePicker
                                v-model="availableFromDate"
                                name="available_from"
                            />
                            <div
                                v-if="form.errors.available_from"
                                class="text-sm text-red-600"
                            >
                                {{ form.errors.available_from }}
                            </div>
                        </div>

                        <div
                            v-if="isMediumTerm"
                            class="grid w-44 max-w-full min-w-44 shrink-0 gap-2 sm:w-44"
                        >
                            <Label :requiredStar="true">
                                {{ t('common.availableTo') }}
                            </Label>
                            <DatePicker
                                v-model="availableToDate"
                                name="available_to"
                            />
                            <div
                                v-if="form.errors.available_to"
                                class="text-sm text-red-600"
                            >
                                {{ form.errors.available_to }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-start justify-start gap-5">
                    <div class="grid w-32 max-w-full min-w-32 gap-2 sm:w-32">
                        <FormKit
                            v-model="form.bedrooms"
                            type="number"
                            name="bedrooms"
                            :label="t('common.bedrooms')"
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

                    <div class="grid w-32 max-w-full min-w-32 gap-2 sm:w-32">
                        <FormKit
                            v-model="squareMeterInput"
                            type="number"
                            name="square_meter"
                            :label="t('common.squareMeter')"
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

                    <div class="grid w-32 max-w-full min-w-32 gap-2 sm:w-32">
                        <FormKit
                            v-model="bathroomsInput"
                            type="number"
                            name="bathrooms"
                            :label="t('common.bathrooms')"
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

                    <div class="grid w-36 max-w-full min-w-36 gap-2 sm:w-36">
                        <FormKit
                            v-model="priceInput"
                            type="number"
                            name="price"
                            :label="t('common.pricePM')"
                            step="1"
                            number
                            validation="number|min:0"
                        />
                        <div
                            v-if="form.errors.price"
                            class="text-sm text-red-600"
                        >
                            {{ form.errors.price }}
                        </div>
                    </div>
                </div>

                <div
                    class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                >
                    <div class="grid gap-2">
                        <Label :requiredStar="true">
                            {{ t('common.furnished') }}
                        </Label>
                        <MenuSelect
                            v-model="furnishedSelect"
                            :options="translatedFurnished"
                            :placeholder="t('common.selectFurnishedStatus')"
                            trigger-class="w-full border-0 shadow-sm justify-between"
                        />
                        <div
                            v-if="form.errors.furnished"
                            class="text-sm text-red-600"
                        >
                            {{ form.errors.furnished }}
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label :requiredStar="true">
                            {{ t('common.access') }}
                        </Label>
                        <MenuSelect
                            v-model="accessSelect"
                            :options="translatedAccess"
                            :placeholder="t('common.selectAccess')"
                            trigger-class="w-full border-0 shadow-sm justify-between"
                        />
                        <div
                            v-if="form.errors.access"
                            class="text-sm text-red-600"
                        >
                            {{ form.errors.access }}
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label :requiredStar="true">
                            {{ t('common.separateKitchenDiningRoom') }}
                        </Label>
                        <MenuSelect
                            v-model="kitchenDiningRoomSelect"
                            :options="translatedKitchenDiningRoom"
                            :placeholder="t('common.selectOption')"
                            trigger-class="w-full border-0 shadow-sm justify-between"
                        />
                        <div
                            v-if="form.errors.kitchen_dining_room"
                            class="text-sm text-red-600"
                        >
                            {{ form.errors.kitchen_dining_room }}
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label :requiredStar="true">
                            {{ t('common.porchGarden') }}
                        </Label>
                        <MenuSelect
                            v-model="porchGardenSelect"
                            :options="translatedPorchGarden"
                            :placeholder="t('common.selectOption')"
                            trigger-class="w-full border-0 shadow-sm justify-between"
                        />
                        <div
                            v-if="form.errors.porch_garden"
                            class="text-sm text-red-600"
                        >
                            {{ form.errors.porch_garden }}
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label :requiredStar="true">
                            {{ t('common.airConditioning') }}
                        </Label>
                        <MenuSelect
                            v-model="airConditioningSelect"
                            :options="translatedAirConditioning"
                            :placeholder="t('common.selectOption')"
                            trigger-class="w-full border-0 shadow-sm justify-between"
                        />
                        <div
                            v-if="form.errors.air_conditioning"
                            class="text-sm text-red-600"
                        >
                            {{ form.errors.air_conditioning }}
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label :requiredStar="true">
                            {{ t('common.apartmentCondition') }}
                        </Label>
                        <MenuSelect
                            v-model="apartmentConditionSelect"
                            :options="translatedApartmentCondition"
                            :placeholder="t('common.selectOption')"
                            trigger-class="w-full border-0 shadow-sm justify-between"
                        />
                        <div
                            v-if="form.errors.apartment_condition"
                            class="text-sm text-red-600"
                        >
                            {{ form.errors.apartment_condition }}
                        </div>
                    </div>
                </div>
            </div>
        </Card>

        <Card>
            <h2 class="text-sm font-semibold text-foreground/80">
                {{ t('common.amenities') }}
            </h2>
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                <div class="grid gap-2">
                    <FormKit
                        v-model="form.succah_porch"
                        type="checkbox"
                        name="succah_porch"
                        :label="t('common.succahPorch')"
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
                        :label="t('common.dudShemesh')"
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
                        :label="t('common.machsan')"
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
                        :label="t('common.parkingSpot')"
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
            <h2 class="text-sm font-semibold text-foreground/80">
                {{ t('common.notes') }}
            </h2>
            <div class="grid gap-4">
                <div class="grid gap-2">
                    <p class="text-sm text-muted-foreground">
                        {{
                            t('common.pleaseEnterTextInEtc', {
                                locale:
                                    currentLocale === 'he'
                                        ? 'עברית'
                                        : 'English',
                            })
                        }}
                    </p>
                    <FormKit
                        v-model="additionalInfoInput"
                        type="textarea"
                        name="additional_info"
                        :label="t('common.additionalInfo')"
                        rows="4"
                    />
                    <div
                        v-if="form.errors.additional_info"
                        class="text-sm text-red-600"
                    >
                        {{ form.errors.additional_info }}
                    </div>
                </div>
            </div>
        </Card>

        <Card class="space-y-4">
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <h2 class="text-sm font-semibold text-foreground/80">
                        {{ t('common.image') }}
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        {{ t('common.imagesEditHint') }}
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
                    <span v-if="uploadingImages">{{
                        t('common.uploading')
                    }}</span>
                    <span v-else-if="!propertyImages.length">{{
                        t('common.uploadImage')
                    }}</span>
                    <span v-else-if="canAddMoreImages">{{
                        t('common.addAnotherImage')
                    }}</span>
                    <span v-else>{{ t('common.maximumImagesReached') }}</span>
                </span>
                <span class="text-xs text-muted-foreground">{{
                    t('common.browse')
                }}</span>
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
                            {{ t('common.mainImageLabel') }}
                        </span>

                        <button
                            type="button"
                            class="absolute top-2 right-2 inline-flex h-8 w-8 items-center justify-center rounded-md bg-black/60 text-white opacity-100 backdrop-blur-sm transition hover:bg-black/70 sm:opacity-0 sm:group-hover:opacity-100"
                            :disabled="imageDeleteInProgressId === image.id"
                            @click="deleteImage(image)"
                            :aria-label="t('common.deleteImageAria')"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>
            <p v-else class="text-sm text-muted-foreground">
                {{ t('common.noImagesUploadedYet') }}
            </p>
        </Card>

        <div class="flex items-center gap-4">
            <Button
                type="submit"
                :disabled="form.processing || uploadingImages"
            >
                {{ t('common.update') }}
            </Button>
        </div>
    </form>

    <Modal
        :open="markAsTakenModalOpen"
        :title="t('common.howWasListingTaken')"
        :confirm-label="t('common.markAsTaken')"
        :processing="markingAsTaken"
        @close="
            markAsTakenModalOpen = false;
            markAsTakenThenDelete = false;
        "
        @confirm="handleMarkAsTaken"
    >
        <div class="space-y-4">
            <div class="space-y-2">
                <Label>{{ t('common.howWasListingTakenSource') }}</Label>
                <div class="flex flex-wrap gap-2">
                    <Button
                        v-for="option in feedbackOptions"
                        :key="option.value"
                        size="sm"
                        :variant="
                            feedbackSource === option.value
                                ? 'default'
                                : 'outline'
                        "
                        @click="
                            feedbackSource =
                                feedbackSource === option.value
                                    ? null
                                    : option.value
                        "
                    >
                        {{ option.label }}
                    </Button>
                </div>
            </div>

            <div class="space-y-2">
                <Label for="edit-feedback-price">{{
                    t('common.finalPrice')
                }}</Label>
                <Input
                    id="edit-feedback-price"
                    v-model="feedbackPrice"
                    type="number"
                    :placeholder="t('common.optional')"
                />
            </div>
            <p
                v-if="markAsTakenThenDelete"
                class="text-sm text-muted-foreground"
            >
                {{ t('common.markAsTakenBeforeDelete') }}
            </p>
        </div>
    </Modal>

    <Modal
        :open="deleteModalOpen"
        :title="t('common.actionDangerous')"
        :confirm-label="t('common.delete')"
        :processing="deletingId !== null"
        @close="deleteModalOpen = false"
        @confirm="handleConfirmDelete"
    >
        <p class="text-sm text-muted-foreground">
            {{ t('common.actionDangerousConfirm') }}
        </p>
    </Modal>
</template>
