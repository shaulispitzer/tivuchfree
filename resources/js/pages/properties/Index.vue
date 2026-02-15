<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import Slider from '@vueform/slider';
import type { PropType } from 'vue';
import '@vueform/slider/themes/default.css';
import PaginationNav from '@/components/PaginationNav.vue';
import PropertyCard from '@/components/PropertyCard.vue';
import { Button } from '@/components/ui/button';
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
import { create, index } from '@/routes/properties';
// import type { Paginator } from '@/types';
import ChevronDown16 from '~icons/octicon/chevron-down-16';

type Option = {
    value: string;
    label: string;
};

const LeaseType = {
    MediumTerm: 'medium_term',
} as const;

const SortValue = {
    PriceAsc: 'price_asc',
    PriceDesc: 'price_desc',
    Newest: 'newest',
    Oldest: 'oldest',
} as const;

const sortOptions: Option[] = [
    { value: SortValue.PriceAsc, label: 'Price: lowest to highest' },
    { value: SortValue.PriceDesc, label: 'Price: highest to lowest' },
    { value: SortValue.Newest, label: 'Latest to oldest' },
    { value: SortValue.Oldest, label: 'Oldest to latest' },
];

const ALL_NEIGHBOURHOODS_VALUE = '__all_neighbourhoods__';
const ALL_FURNISHED_VALUE = '__all_furnished__';
const ALL_TYPES_VALUE = '__all_types__';

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

function initialBedroomsRange(): [number, number] {
    const min = props.filters.bedrooms_min;
    const max = props.filters.bedrooms_max;

    if (min !== null && max !== null) {
        return [
            clampBedrooms(Math.min(min, max)),
            clampBedrooms(Math.max(min, max)),
        ];
    }

    if (min !== null) {
        const exact = clampBedrooms(min);
        return [exact, exact];
    }

    return [1, 10];
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

function isSameBedroomsRange(
    first: [number, number],
    second: [number, number],
): boolean {
    return first[0] === second[0] && first[1] === second[1];
}

const props = defineProps({
    properties: {
        type: Object as PropType<Paginator<App.Data.PropertyData>>,
        required: true,
    },
    can_create: {
        type: Boolean,
        required: true,
    },
    filters: {
        type: Object as PropType<{
            neighbourhood: string | null;
            availability: 'all' | 'available' | null;
            bedrooms_min: number | null;
            bedrooms_max: number | null;
            furnished: App.Enums.PropertyFurnished | null;
            type: App.Enums.PropertyLeaseType | null;
            available_from: string | null;
            available_to: string | null;
            sort: 'price_asc' | 'price_desc' | 'newest' | 'oldest';
        }>,
        required: true,
    },
    neighbourhood_options: {
        type: Array as PropType<string[]>,
        required: true,
    },
    furnished_options: {
        type: Array as PropType<Option[]>,
        required: true,
    },
    type_options: {
        type: Array as PropType<Option[]>,
        required: true,
    },
});

const filters = ref({
    neighbourhood: props.filters.neighbourhood ?? '',
    hide_taken_properties: props.filters.availability === 'available',
    bedrooms_range: initialBedroomsRange(),
    furnished: props.filters.furnished ?? '',
    type: props.filters.type ?? '',
    available_from: props.filters.available_from?.slice(0, 10) ?? '',
    available_to: props.filters.available_to?.slice(0, 10) ?? '',
    sort: props.filters.sort ?? SortValue.Newest,
});

const bedroomsRangeDraft = ref<[number, number]>(initialBedroomsRange());

function commitBedroomsRange(value: number | number[]): void {
    const nextRange = normalizeBedroomsRange(value);

    if (isSameBedroomsRange(filters.value.bedrooms_range, nextRange)) {
        return;
    }

    filters.value.bedrooms_range = nextRange;
}

const isMediumTerm = computed(
    () => filters.value.type === LeaseType.MediumTerm,
);

const neighbourhoodSelectValue = computed({
    get: (): string => filters.value.neighbourhood || ALL_NEIGHBOURHOODS_VALUE,
    set: (value: string): void => {
        filters.value.neighbourhood =
            value === ALL_NEIGHBOURHOODS_VALUE ? '' : value;
    },
});

const furnishedSelectValue = computed({
    get: (): string => filters.value.furnished || ALL_FURNISHED_VALUE,
    set: (value: string): void => {
        filters.value.furnished = value === ALL_FURNISHED_VALUE ? '' : value;
    },
});

const typeSelectValue = computed({
    get: (): string => filters.value.type || ALL_TYPES_VALUE,
    set: (value: string): void => {
        filters.value.type = value === ALL_TYPES_VALUE ? '' : value;
    },
});

watch(
    () => ({ ...filters.value }),
    (value) => {
        const query: Record<string, string> = {};
        const [rawBedroomsMin, rawBedroomsMax] = value.bedrooms_range;
        const bedroomsMin = roundBedrooms(rawBedroomsMin);
        const bedroomsMax = roundBedrooms(rawBedroomsMax);

        if (value.neighbourhood.trim() !== '') {
            query.neighbourhood = value.neighbourhood;
        }

        if (value.hide_taken_properties) {
            query.availability = 'available';
        }

        if (!(bedroomsMin === 1 && bedroomsMax === 10)) {
            query.bedrooms_min = bedroomsMin.toString();
            query.bedrooms_max = bedroomsMax.toString();
        }

        if (value.furnished !== '') {
            query.furnished = value.furnished;
        }

        if (value.type !== '') {
            query.type = value.type;
        }

        if (
            value.type === LeaseType.MediumTerm &&
            value.available_from !== ''
        ) {
            query.available_from = value.available_from;
        }

        if (value.type === LeaseType.MediumTerm && value.available_to !== '') {
            query.available_to = value.available_to;
        }

        if (value.sort !== SortValue.Newest) {
            query.sort = value.sort;
        }

        router.get(index(), query, {
            preserveScroll: true,
            preserveState: true,
        });
    },
    { deep: true },
);

watch(
    () => filters.value.type,
    (type) => {
        if (type !== LeaseType.MediumTerm) {
            filters.value.available_from = '';
            filters.value.available_to = '';
        }
    },
);
</script>

<template>
    <Head title="Properties" />

    <div class="flex items-center justify-between">
        <h1 class="text-lg font-semibold">Properties</h1>
        <Button v-if="can_create" as-child>
            <Link :href="create()">Add property</Link>
        </Button>
    </div>
    <form class="mt-4">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-7">
            <Select v-model="neighbourhoodSelectValue" name="neighbourhood">
                <SelectTrigger
                    class="group w-full hover:bg-accent data-[state=open]:bg-accent"
                >
                    <SelectValue placeholder="All neighbourhoods" />
                    <template #trigger-icon>
                        <ChevronDown16
                            class="size-4 opacity-50 transition-transform duration-300 group-data-[state=open]:rotate-x-180"
                        />
                    </template>
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="ALL_NEIGHBOURHOODS_VALUE">
                        All neighbourhoods
                    </SelectItem>
                    <SelectItem
                        v-for="neighbourhood in neighbourhood_options"
                        :key="neighbourhood"
                        :value="neighbourhood"
                    >
                        {{ neighbourhood }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <Select v-model="furnishedSelectValue" name="furnished">
                <SelectTrigger
                    class="group w-full hover:bg-accent data-[state=open]:bg-accent"
                >
                    <SelectValue placeholder="All furnished options" />
                    <template #trigger-icon>
                        <ChevronDown16
                            class="size-4 opacity-50 transition-transform duration-300 group-data-[state=open]:rotate-x-180"
                        />
                    </template>
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="ALL_FURNISHED_VALUE">
                        All furnished options
                    </SelectItem>
                    <SelectItem
                        v-for="option in furnished_options"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <div class="w-full sm:w-auto">
                <Popover>
                    <PopoverTrigger as-child>
                        <Button
                            type="button"
                            variant="outline"
                            class="group h-9 w-full justify-between border-input bg-transparent px-3 font-normal shadow-xs hover:bg-accent data-[state=open]:bg-accent"
                        >
                            <span class="truncate">Bedrooms</span>
                            <span class="truncate text-muted-foreground">
                                {{ formatBedroomsRange(bedroomsRangeDraft) }}
                            </span>
                            <ChevronDown16
                                class="size-4 opacity-50 transition-transform duration-300 group-data-[state=open]:rotate-x-180"
                            />
                        </Button>
                    </PopoverTrigger>
                    <PopoverContent class="w-80 space-y-3 pt-12" align="end">
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
            <Select v-model="typeSelectValue" name="type">
                <SelectTrigger
                    class="group w-full hover:bg-accent data-[state=open]:bg-accent sm:col-span-2 sm:w-1/2 lg:col-span-1 lg:w-auto"
                >
                    <SelectValue placeholder="All types" />
                    <template #trigger-icon>
                        <ChevronDown16
                            class="size-4 opacity-50 transition-transform duration-300 group-data-[state=open]:rotate-x-180"
                        />
                    </template>
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="ALL_TYPES_VALUE">All types</SelectItem>
                    <SelectItem
                        v-for="option in type_options"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <input
                v-if="isMediumTerm"
                type="date"
                name="available_from"
                v-model="filters.available_from"
                class="h-9 rounded-md border border-input bg-background px-3 text-sm"
            />
            <input
                v-if="isMediumTerm"
                type="date"
                name="available_to"
                v-model="filters.available_to"
                class="h-9 rounded-md border border-input bg-background px-3 text-sm"
            />
            <Select v-model="filters.sort" name="sort">
                <SelectTrigger
                    class="group w-full hover:bg-accent data-[state=open]:bg-accent"
                >
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
        <div class="my-3">
            <label class="flex items-center gap-2 text-sm">
                <input
                    type="checkbox"
                    name="hide_taken_properties"
                    v-model="filters.hide_taken_properties"
                    class="h-4 w-4 rounded border-input"
                />
                Hide Taken Properties
            </label>
        </div>
    </form>
    <div
        v-if="properties.data.length === 0"
        class="text-sm text-muted-foreground"
    >
        No properties have been added yet.
    </div>

    <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
        <PropertyCard
            v-for="property in properties.data"
            :key="property.id"
            :property="property"
        />
    </div>

    <PaginationNav
        :links="properties.links"
        :from="properties.from"
        :to="properties.to"
        :total="properties.total"
    />
</template>
