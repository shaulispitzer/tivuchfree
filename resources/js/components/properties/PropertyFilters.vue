<script setup lang="ts">
import { parseDate } from '@internationalized/date';
import Slider from '@vueform/slider';
import '@vueform/slider/themes/default.css';
import type { DateValue } from 'reka-ui';
import DatePicker from '@/components/DatePicker.vue';

import ChevronDown16 from '~icons/octicon/chevron-down-16';

type Option = {
    value: string;
    label: string;
};

export type PropertyFilterState = {
    neighbourhoods: string[];
    hide_taken_properties: boolean;
    bedrooms_range: [number, number];
    furnished: string;
    type: string;
    available_from: string;
    available_to: string;
    sort: 'price_asc' | 'price_desc' | 'newest' | 'oldest';
};
const { t } = useI18n();
const LeaseType = {
    MediumTerm: 'medium_term',
} as const;

const SortValue = {
    PriceAsc: 'price_asc',
    PriceDesc: 'price_desc',
    Newest: 'newest',
    Oldest: 'oldest',
} as const;

const sortOptions = computed<Option[]>(() => [
    {
        value: SortValue.PriceAsc,
        label: t('propertyFilters.priceLowestToHighest'),
    },
    {
        value: SortValue.PriceDesc,
        label: t('propertyFilters.priceHighestToLowest'),
    },
    { value: SortValue.Newest, label: t('propertyFilters.latestToOldest') },
    { value: SortValue.Oldest, label: t('propertyFilters.oldestToLatest') },
]);

const ALL_FURNISHED_VALUE = '__all_furnished__';
const ALL_TYPES_VALUE = '__all_types__';

function normalizeNeighbourhoods(value: PropertyFilterState): string[] {
    if (Array.isArray(value.neighbourhoods)) return [...value.neighbourhoods];
    const legacy = (value as { neighbourhood?: string }).neighbourhood;
    if (typeof legacy === 'string' && legacy.trim() !== '') return [legacy];
    return [];
}

function cloneFilters(value: PropertyFilterState): PropertyFilterState {
    return {
        ...value,
        neighbourhoods: normalizeNeighbourhoods(value),
        bedrooms_range: [...value.bedrooms_range],
    };
}

function isSameBedroomsRange(
    first: [number, number],
    second: [number, number],
): boolean {
    return first[0] === second[0] && first[1] === second[1];
}

function isSameNeighbourhoods(a: string[], b: string[]): boolean {
    if (a.length !== b.length) return false;
    const set = new Set(b);
    return a.every((n) => set.has(n));
}

function isSameFilterState(
    first: PropertyFilterState,
    second: PropertyFilterState,
): boolean {
    return (
        isSameNeighbourhoods(
            first.neighbourhoods ?? [],
            second.neighbourhoods ?? [],
        ) &&
        first.hide_taken_properties === second.hide_taken_properties &&
        isSameBedroomsRange(first.bedrooms_range, second.bedrooms_range) &&
        first.furnished === second.furnished &&
        first.type === second.type &&
        first.available_from === second.available_from &&
        first.available_to === second.available_to &&
        first.sort === second.sort
    );
}

function clampBedrooms(value: number): number {
    return Math.min(10, Math.max(1, value));
}

function roundBedrooms(value: number): number {
    return clampBedrooms(Math.round((value + Number.EPSILON) * 2) / 2);
}

function formatBedrooms(value: number): string {
    const rounded = roundBedrooms(value);
    return Number.isInteger(rounded) ? rounded.toString() : rounded.toFixed(1);
}

function formatBedroomsRange(value: [number, number]): string {
    return `${formatBedrooms(value[0])} - ${formatBedrooms(value[1])}`;
}

function normalizeBedroomsRange(value: number | number[]): [number, number] {
    if (Array.isArray(value)) {
        const min = value[0] ?? 1;
        const max = value[1] ?? min;

        return [clampBedrooms(min), clampBedrooms(max)];
    }

    const exact = clampBedrooms(value);
    return [exact, exact];
}

const props = withDefaults(
    defineProps<{
        filters: PropertyFilterState;
        neighbourhood_options: string[];
        furnished_options: Option[];
        type_options: Option[];
        show_sort?: boolean;
        show_hide_taken?: boolean;
        subscription_mode?: boolean;
        subscription_neighbourhoods?: string[];
    }>(),
    {
        show_sort: true,
        show_hide_taken: true,
        subscription_mode: false,
        subscription_neighbourhoods: () => [],
    },
);

const emit = defineEmits<{
    'update:filters': [value: PropertyFilterState];
    'update:subscription_neighbourhoods': [value: string[]];
}>();

const localFilters = ref<PropertyFilterState>(cloneFilters(props.filters));

const bedroomsRangeDraft = ref<[number, number]>([
    ...localFilters.value.bedrooms_range,
]);

const isMediumTerm = computed(
    () => localFilters.value.type === LeaseType.MediumTerm,
);

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
    get: () => toDateValue(localFilters.value.available_from),
    set: (value) => {
        localFilters.value.available_from = value ? value.toString() : '';
    },
});

const availableToDate = computed<DateValue | undefined>({
    get: () => toDateValue(localFilters.value.available_to),
    set: (value) => {
        localFilters.value.available_to = value ? value.toString() : '';
    },
});

function formatNeighbourhoodsDisplay(arr: string[]): string {
    if (arr.length === 0) return t('neighbourhoods.allNeighbourhoods');
    if (arr.length === 1) {
        return t(`neighbourhoods.${arr[0].replaceAll(' ', '')}`);
    }
    return t('propertyFilters.neighbourhoodsSelected', { count: arr.length });
}

const subscriptionNeighbourhoods = computed(() =>
    Array.isArray(props.subscription_neighbourhoods)
        ? props.subscription_neighbourhoods
        : normalizeNeighbourhoods(props.filters),
);

const subscriptionNeighbourhoodsModel = computed({
    get: () => subscriptionNeighbourhoods.value,
    set: (value: string[]) => {
        emit('update:subscription_neighbourhoods', value);
        emit('update:filters', {
            ...cloneFilters(localFilters.value),
            neighbourhoods: value,
        });
    },
});

const neighbourhoodsModel = computed({
    get: () =>
        props.subscription_mode
            ? subscriptionNeighbourhoodsModel.value
            : localFilters.value.neighbourhoods,
    set: (value: string[]) => {
        if (props.subscription_mode) {
            subscriptionNeighbourhoodsModel.value = value;
        } else {
            localFilters.value = {
                ...localFilters.value,
                neighbourhoods: value,
            };
        }
    },
});

const neighbourhoodsDisplay = computed(() =>
    formatNeighbourhoodsDisplay(
        props.subscription_mode
            ? subscriptionNeighbourhoods.value
            : (localFilters.value.neighbourhoods ?? []),
    ),
);

const furnishedSelectValue = computed({
    get: (): string => localFilters.value.furnished || ALL_FURNISHED_VALUE,
    set: (value: string): void => {
        localFilters.value.furnished =
            value === ALL_FURNISHED_VALUE ? '' : value;
    },
});

const typeSelectValue = computed({
    get: (): string => localFilters.value.type || ALL_TYPES_VALUE,
    set: (value: string): void => {
        localFilters.value.type = value === ALL_TYPES_VALUE ? '' : value;
    },
});

function commitBedroomsRange(value: number | number[]): void {
    const nextRange = normalizeBedroomsRange(value);

    if (isSameBedroomsRange(localFilters.value.bedrooms_range, nextRange)) {
        return;
    }

    localFilters.value.bedrooms_range = nextRange;
}

watch(
    () => props.filters,
    (value) => {
        if (props.subscription_mode) {
            return;
        }
        if (isSameFilterState(value, localFilters.value)) {
            return;
        }

        localFilters.value = cloneFilters(value);
        bedroomsRangeDraft.value = [...value.bedrooms_range];
    },
    { deep: true },
);

watch(
    () => localFilters.value.bedrooms_range,
    (value) => {
        if (isSameBedroomsRange(value, bedroomsRangeDraft.value)) {
            return;
        }

        bedroomsRangeDraft.value = [...value];
    },
    { deep: true },
);

watch(
    () => localFilters.value,
    (value) => {
        if (props.subscription_mode) {
            emit('update:filters', {
                ...cloneFilters(value),
                neighbourhoods: subscriptionNeighbourhoods.value,
            });
        } else {
            emit('update:filters', cloneFilters(value));
        }
    },
    { deep: true },
);

const defaultFilterState = (): PropertyFilterState => ({
    neighbourhoods: [],
    hide_taken_properties: false,
    bedrooms_range: [1, 10],
    furnished: '',
    type: '',
    available_from: '',
    available_to: '',
    sort: SortValue.Newest,
});

function clearFilters(): void {
    if (props.subscription_mode) return;
    const next = defaultFilterState();
    localFilters.value = cloneFilters(next);
    bedroomsRangeDraft.value = [...next.bedrooms_range];
}

defineExpose({
    getFilters: (): PropertyFilterState =>
        props.subscription_mode
            ? {
                  ...cloneFilters(localFilters.value),
                  neighbourhoods: subscriptionNeighbourhoods.value,
              }
            : cloneFilters(localFilters.value),
    clearFilters,
});

const triggerClass = computed(() =>
    props.subscription_mode
        ? 'group !h-11 w-full items-center justify-between border-input bg-transparent px-4 text-base shadow-xs hover:bg-accent data-[state=open]:bg-accent'
        : 'group h-9 w-full hover:bg-accent data-[state=open]:bg-accent',
);
const gridClass = computed(() =>
    props.subscription_mode
        ? 'grid min-w-0 gap-5 sm:grid-cols-2 lg:grid-cols-6'
        : 'grid min-w-0 gap-3 sm:grid-cols-2 lg:grid-cols-7',
);
</script>

<template>
    <form class="mt-4">
        <div :class="gridClass">
            <div
                :class="
                    subscription_mode
                        ? 'col-span-full flex w-full min-w-0 flex-wrap items-center gap-4'
                        : 'contents'
                "
            >
                <div
                    :class="
                        subscription_mode
                            ? 'min-w-72 shrink-0 overflow-hidden'
                            : 'min-w-0 overflow-hidden'
                    "
                >
                    <Select
                        v-model="neighbourhoodsModel"
                        name="neighbourhoods"
                        multiple
                    >
                        <SelectTrigger :class="triggerClass">
                            <span class="min-w-0 truncate">{{
                                neighbourhoodsDisplay
                            }}</span>
                            <template #trigger-icon>
                                <ChevronDown16
                                    class="size-4 shrink-0 opacity-50 transition-transform duration-300 group-data-[state=open]:rotate-x-180"
                                />
                            </template>
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="neighbourhood in neighbourhood_options"
                                :key="neighbourhood"
                                :value="neighbourhood"
                            >
                                {{
                                    t(
                                        `neighbourhoods.${neighbourhood.replaceAll(' ', '')}`,
                                    )
                                }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div
                    :class="
                        subscription_mode
                            ? 'w-72 shrink-0 overflow-hidden sm:w-56'
                            : 'min-w-0'
                    "
                >
                    <Select v-model="furnishedSelectValue" name="furnished">
                        <SelectTrigger :class="triggerClass">
                            <SelectValue
                                :placeholder="
                                    t('propertyFilters.allFurnishedOptions')
                                "
                            />
                            <template #trigger-icon>
                                <ChevronDown16
                                    class="size-4 opacity-50 transition-transform duration-300 group-data-[state=open]:rotate-x-180"
                                />
                            </template>
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="ALL_FURNISHED_VALUE">
                                {{ t('propertyFilters.allFurnishedOptions') }}
                            </SelectItem>
                            <SelectItem
                                v-for="option in furnished_options"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ t(`propertyFurnished.${option.value}`) }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div
                    :class="
                        subscription_mode
                            ? 'w-72 shrink-0 sm:w-56'
                            : 'w-full min-w-0 sm:w-auto'
                    "
                >
                    <Popover>
                        <PopoverTrigger as-child>
                            <Button
                                type="button"
                                variant="outline"
                                :class="triggerClass"
                                class="font-normal"
                            >
                                <span class="truncate">{{
                                    t('propertyFilters.bedrooms')
                                }}</span>
                                <span class="truncate text-muted-foreground">
                                    {{
                                        formatBedroomsRange(bedroomsRangeDraft)
                                    }}
                                </span>
                                <ChevronDown16
                                    class="size-4 opacity-50 transition-transform duration-300 group-data-[state=open]:rotate-x-180"
                                />
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent
                            class="w-80 space-y-3 pt-12"
                            align="end"
                        >
                            <Slider
                                v-model="bedroomsRangeDraft"
                                @set="commitBedroomsRange"
                                class="slider-red"
                                :min="1"
                                :max="10"
                                :step="0.5"
                                :tooltips="true"
                                :lazy="false"
                                :format="formatBedrooms"
                            />
                        </PopoverContent>
                    </Popover>
                </div>
                <div
                    :class="
                        subscription_mode ? 'w-72 shrink-0 sm:w-44' : 'min-w-0'
                    "
                >
                    <Select v-model="typeSelectValue" name="type">
                        <SelectTrigger
                            :class="[
                                triggerClass,
                                subscription_mode
                                    ? ''
                                    : 'sm:col-span-2 sm:w-1/2 lg:col-span-1 lg:w-auto',
                            ]"
                        >
                            <SelectValue
                                :placeholder="t('propertyFilters.allTypes')"
                            />
                            <template #trigger-icon>
                                <ChevronDown16
                                    class="size-4 opacity-50 transition-transform duration-300 group-data-[state=open]:rotate-x-180"
                                />
                            </template>
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="ALL_TYPES_VALUE">
                                {{ t('propertyFilters.allTypes') }}
                            </SelectItem>
                            <SelectItem
                                v-for="option in type_options"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ t(`propertyLeaseType.${option.value}`) }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <DatePicker
                v-if="isMediumTerm"
                name="available_from"
                v-model="availableFromDate"
                clearable
                :trigger-class="[
                    'rounded-md border border-input bg-background',
                    subscription_mode
                        ? 'h-11! px-4 text-base'
                        : 'h-9 px-3 text-sm',
                ]"
            />
            <DatePicker
                v-if="isMediumTerm"
                name="available_to"
                v-model="availableToDate"
                clearable
                :trigger-class="[
                    'rounded-md border border-input bg-background',
                    subscription_mode
                        ? 'h-11! px-4 text-base'
                        : 'h-9 px-3 text-sm',
                ]"
            />
            <Select v-if="show_sort" v-model="localFilters.sort" name="sort">
                <SelectTrigger :class="triggerClass">
                    <SelectValue />
                    <template #trigger-icon>
                        <ChevronDown16
                            class="size-4 opacity-50 transition-transform duration-300 group-data-[state=open]:rotate-x-180"
                        />
                    </template>
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="option in sortOptions"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>
        <div
            v-if="!subscription_mode"
            class="mt-3 flex flex-wrap items-center gap-3"
        >
            <Button
                type="button"
                variant="ghost"
                size="sm"
                class="text-muted-foreground hover:text-foreground"
                @click="clearFilters"
            >
                {{ t('propertyFilters.clearFilters') }}
            </Button>
        </div>
        <div v-if="show_hide_taken" class="my-3">
            <label
                :class="[
                    'flex items-center gap-2',
                    subscription_mode ? 'text-base' : 'text-sm',
                ]"
            >
                <input
                    type="checkbox"
                    name="hide_taken_properties"
                    v-model="localFilters.hide_taken_properties"
                    :class="subscription_mode ? 'h-5 w-5' : 'h-4 w-4'"
                    class="rounded border-input"
                />
                {{ t('propertyFilters.hideTakenProperties') }}
            </label>
        </div>
    </form>
</template>
