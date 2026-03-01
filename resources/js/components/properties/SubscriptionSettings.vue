<script setup lang="ts">
import { parseDate } from '@internationalized/date';
import Slider from '@vueform/slider';
import '@vueform/slider/themes/default.css';
import type { DateValue } from 'reka-ui';
import DatePicker from '@/components/DatePicker.vue';
import type { PropertyFilterState } from '@/components/properties/PropertyFilters.vue';
import ChevronDown16 from '~icons/octicon/chevron-down-16';

type Option = { value: string; label: string };

const LeaseType = { MediumTerm: 'medium_term' } as const;
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

function isSameFilterState(
    first: PropertyFilterState,
    second: PropertyFilterState,
): boolean {
    const sameNeighbourhoods =
        (first.neighbourhoods ?? []).length === (second.neighbourhoods ?? []).length &&
        (first.neighbourhoods ?? []).every((n) =>
            (second.neighbourhoods ?? []).includes(n),
        );
    return (
        sameNeighbourhoods &&
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

const { t } = useI18n();

const props = defineProps<{
    filters: PropertyFilterState;
    neighbourhood_options: string[];
    furnished_options: Option[];
    type_options: Option[];
}>();

const emit = defineEmits<{
    'update:filters': [value: PropertyFilterState];
}>();

const localFilters = ref<PropertyFilterState>(cloneFilters(props.filters));
const bedroomsRangeDraft = ref<[number, number]>([
    ...localFilters.value.bedrooms_range,
]);

const isMediumTerm = computed(
    () => localFilters.value.type === LeaseType.MediumTerm,
);

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

const neighbourhoodsModel = computed({
    get: () => localFilters.value.neighbourhoods ?? [],
    set: (value: string[]) => {
        localFilters.value = { ...localFilters.value, neighbourhoods: value };
    },
});

const neighbourhoodsDisplay = computed(() =>
    formatNeighbourhoodsDisplay(localFilters.value.neighbourhoods ?? []),
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

const subscriptionTriggerClass =
    'group h-11! w-full items-center justify-between border-input bg-transparent px-4 text-base shadow-xs hover:bg-accent data-[state=open]:bg-accent';

watch(
    () => props.filters,
    (value) => {
        if (isSameFilterState(value, localFilters.value)) return;
        localFilters.value = cloneFilters(value);
        bedroomsRangeDraft.value = [...value.bedrooms_range];
    },
    { deep: true },
);

watch(
    () => localFilters.value.bedrooms_range,
    (value) => {
        if (!isSameBedroomsRange(value, bedroomsRangeDraft.value)) {
            bedroomsRangeDraft.value = [...value];
        }
    },
    { deep: true },
);

watch(
    () => localFilters.value,
    (value) => emit('update:filters', cloneFilters(value)),
    { deep: true },
);

defineExpose({
    getFilters: (): PropertyFilterState => cloneFilters(localFilters.value),
});
</script>

<template>
    <form class="mt-4">
        <div class="grid min-w-0 gap-5 sm:grid-cols-2 lg:grid-cols-6">
            <div class="col-span-full flex w-full min-w-0 flex-wrap items-center gap-4">
                <div class="min-w-72 shrink-0 overflow-hidden">
                    <NeighbourhoodCheckboxes
                        v-model="neighbourhoodsModel"
                        :options="neighbourhood_options"
                        :trigger-class="subscriptionTriggerClass"
                    />
                </div>
                <div class="w-72 shrink-0 overflow-hidden sm:w-56">
                    <Select v-model="furnishedSelectValue" name="furnished">
                        <SelectTrigger :class="subscriptionTriggerClass">
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
                <div class="w-72 shrink-0 sm:w-56">
                    <Popover>
                        <PopoverTrigger as-child>
                            <Button
                                type="button"
                                variant="outline"
                                :class="subscriptionTriggerClass"
                                class="font-normal"
                            >
                                <span class="truncate">{{
                                    t('propertyFilters.bedrooms')
                                }}</span>
                                <span class="truncate text-muted-foreground">
                                    {{ formatBedroomsRange(bedroomsRangeDraft) }}
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
                <div class="w-72 shrink-0 sm:w-44">
                    <Select v-model="typeSelectValue" name="type">
                        <SelectTrigger :class="subscriptionTriggerClass">
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
                trigger-class="rounded-md border border-input bg-background h-11! px-4 text-base"
            />
            <DatePicker
                v-if="isMediumTerm"
                name="available_to"
                v-model="availableToDate"
                clearable
                trigger-class="rounded-md border border-input bg-background h-11! px-4 text-base"
            />
        </div>
    </form>
</template>
