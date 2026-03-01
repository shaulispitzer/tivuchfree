<script setup lang="ts">
import { parseDate } from '@internationalized/date';
import axios from 'axios';
import { Check, ChevronsUpDown } from 'lucide-vue-next';
import type { DateValue } from 'reka-ui';
import type { PropType } from 'vue';
import { useI18n } from 'vue-i18n';

import DatePicker from '@/components/DatePicker.vue';
import PropertyImageUploader from '@/components/PropertyImageUploader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import MenuSelect from '@/components/ui/menu-select/MenuSelect.vue';
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
const { t } = useI18n();
const page = usePage();
const currentLocale = computed(() =>
    page.props.locale === 'he' ? 'he' : 'en',
);

const LeaseType = {
    MediumTerm: 'medium_term',
    LongTerm: 'long_term',
} as const satisfies Record<string, App.Enums.PropertyLeaseType>;

type PropertyCreateFormData = Omit<
    App.Data.Forms.PropertyFormData,
    | 'street'
    | 'floor'
    | 'bedrooms'
    | 'building_number'
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
    access: App.Enums.PropertyAccess | null;
    kitchen_dining_room: App.Enums.PropertyKitchenDiningRoom | null;
    porch_garden: App.Enums.PropertyPorchGarden | null;
    air_conditioning: App.Enums.PropertyAirConditioning | null;
    apartment_condition: App.Enums.PropertyApartmentCondition | null;
    furnished: App.Enums.PropertyFurnished | null;
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
    contact_phone_2: null,
    email: null,
    price: null,
    square_meter: null,
    bathrooms: null,
    access: null,
    kitchen_dining_room: null,
    porch_garden: null,
    succah_porch: false,
    air_conditioning: null,
    apartment_condition: null,
    additional_info: '',
    additional_info_en: null,
    additional_info_he: null,
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
    furnished: null,
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

const emailInput = computed<string | undefined>({
    get: () => form.email ?? undefined,
    set: (value) => {
        form.email = value ?? null;
    },
});

const contactPhone2Input = computed<string | undefined>({
    get: () => form.contact_phone_2 ?? undefined,
    set: (value) => {
        form.contact_phone_2 = value ?? null;
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

const additionalInfoInput = computed<string | undefined>({
    get: () => form.additional_info ?? undefined,
    set: (value) => {
        form.additional_info = value ?? null;
    },
});

const accessSelect = computed<string>({
    get: () => form.access ?? '',
    set: (value) => {
        form.access = (value || null) as App.Enums.PropertyAccess | null;
    },
});

const kitchenDiningRoomSelect = computed<string>({
    get: () => form.kitchen_dining_room ?? '',
    set: (value) => {
        form.kitchen_dining_room = (value ||
            null) as App.Enums.PropertyKitchenDiningRoom | null;
    },
});

const porchGardenSelect = computed<string>({
    get: () => form.porch_garden ?? '',
    set: (value) => {
        form.porch_garden = (value ||
            null) as App.Enums.PropertyPorchGarden | null;
    },
});

const airConditioningSelect = computed<string>({
    get: () => form.air_conditioning ?? '',
    set: (value) => {
        form.air_conditioning = (value ||
            null) as App.Enums.PropertyAirConditioning | null;
    },
});

const apartmentConditionSelect = computed<string>({
    get: () => form.apartment_condition ?? '',
    set: (value) => {
        form.apartment_condition = (value ||
            null) as App.Enums.PropertyApartmentCondition | null;
    },
});

const furnishedSelect = computed<string>({
    get: () => form.furnished ?? '',
    set: (value) => {
        form.furnished = (value || null) as App.Enums.PropertyFurnished | null;
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
    <Head :title="t('common.createProperty')" />

    <div class="flex items-center justify-between">
        <h1 class="text-lg font-semibold">{{ t('common.createProperty') }}</h1>
        <Button as-child>
            <Link :href="index()">{{ t('common.backToList') }}</Link>
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
                        validation="required"
                        label-class="required-asterisk"
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
                            phone_uk_us: t('common.invalidPhone'),
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
                </div>
            </div>
        </Card>

        <Card>
            <h2 class="text-sm font-semibold text-foreground/80">
                {{ t('common.location') }}
            </h2>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <div class="grid gap-3 md:col-span-2">
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

                <div class="grid gap-2 md:col-span-2 xl:col-span-1">
                    <Label :requiredStar="true">
                        {{ t('common.street') }}
                    </Label>
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

                <div class="grid gap-2">
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

                <div class="grid gap-2">
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
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="grid gap-2">
                    <Label requiredStar>
                        {{ t('common.type') }}
                    </Label>
                    <MenuSelect
                        v-model="form.type"
                        :options="translatedLeaseTypes"
                        :placeholder="t('common.selectType')"
                        trigger-class="w-full border-0 shadow-sm justify-between"
                    />
                    <div v-if="form.errors.type" class="text-sm text-red-600">
                        {{ form.errors.type }}
                    </div>
                </div>

                <div class="grid gap-2">
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

                <div v-if="isMediumTerm" class="grid gap-2">
                    <Label :requiredStar="true">
                        {{ t('common.availableTo') }}
                    </Label>
                    <DatePicker v-model="availableToDate" name="available_to" />
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

                <div class="grid gap-2">
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

                <div class="grid gap-2">
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
                    <div v-if="form.errors.access" class="text-sm text-red-600">
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
        </Card>
        <Card>
            <h2 class="text-sm font-semibold text-foreground/80">
                {{ t('common.amenities') }}
            </h2>
            <div class="grid gap-3 sm:grid-cols-2">
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
                        Please enter text in {{ currentLocale }}. We will handle
                        the translation automatically.
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
                {{ t('common.create') }}
            </Button>
        </div>
    </FormKit>
</template>
