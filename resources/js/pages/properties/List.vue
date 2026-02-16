<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import type { PropType } from 'vue';
import PaginationNav from '@/components/PaginationNav.vue';
import PropertyFilters from '@/components/properties/PropertyFilters.vue';
import PropertyCard from '@/components/PropertyCard.vue';
import { Button } from '@/components/ui/button';
import { create, index } from '@/routes/properties';

type Option = {
    value: string;
    label: string;
};

type PropertyFilterState = {
    neighbourhood: string;
    hide_taken_properties: boolean;
    bedrooms_range: [number, number];
    furnished: string;
    type: string;
    available_from: string;
    available_to: string;
    sort: 'price_asc' | 'price_desc' | 'newest' | 'oldest';
};

const ViewMode = {
    List: 'list',
    Map: 'map',
} as const;

type ViewModeValue = (typeof ViewMode)[keyof typeof ViewMode];

const SortValue = {
    Newest: 'newest',
} as const;

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
            view: ViewModeValue;
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

const activeView = ref<ViewModeValue>(props.filters.view ?? ViewMode.List);

function initialBedroomsRange(): [number, number] {
    const min = props.filters.bedrooms_min;
    const max = props.filters.bedrooms_max;

    if (min !== null && max !== null) {
        return [Math.min(min, max), Math.max(min, max)];
    }

    if (min !== null) {
        return [min, min];
    }

    return [1, 10];
}

const filters = ref<PropertyFilterState>({
    neighbourhood: props.filters.neighbourhood ?? '',
    hide_taken_properties: props.filters.availability === 'available',
    bedrooms_range: initialBedroomsRange(),
    furnished: props.filters.furnished ?? '',
    type: props.filters.type ?? '',
    available_from: props.filters.available_from?.slice(0, 10) ?? '',
    available_to: props.filters.available_to?.slice(0, 10) ?? '',
    sort: props.filters.sort ?? SortValue.Newest,
});

function updateFilters(value: PropertyFilterState): void {
    filters.value = value;
}

function roundBedrooms(value: number): number {
    return Math.round((value + Number.EPSILON) * 2) / 2;
}

function buildQuery(value: PropertyFilterState): Record<string, string> {
    const query: Record<string, string> = {};
    const bedroomsMin = roundBedrooms(value.bedrooms_range[0]);
    const bedroomsMax = roundBedrooms(value.bedrooms_range[1]);

    if (activeView.value === ViewMode.Map) {
        query.view = ViewMode.Map;
    }

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

    if (value.type === 'medium_term' && value.available_from !== '') {
        query.available_from = value.available_from;
    }

    if (value.type === 'medium_term' && value.available_to !== '') {
        query.available_to = value.available_to;
    }

    if (value.sort !== SortValue.Newest) {
        query.sort = value.sort;
    }

    return query;
}

function syncFiltersToUrl(): void {
    router.get(index(), buildQuery(filters.value), {
        preserveScroll: true,
        preserveState: true,
    });
}

watch(
    () => ({ ...filters.value }),
    () => {
        syncFiltersToUrl();
    },
    { deep: true },
);

watch(
    () => filters.value.type,
    (type) => {
        if (type !== 'medium_term') {
            filters.value.available_from = '';
            filters.value.available_to = '';
        }
    },
);

watch(activeView, () => {
    syncFiltersToUrl();
});
</script>

<template>
    <Head title="Properties" />

    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-lg font-semibold">Properties</h1>
        <div class="flex items-center gap-2">
            <div class="inline-flex items-center rounded-md border border-input p-0.5">
                <Button
                    type="button"
                    size="sm"
                    :variant="activeView === ViewMode.List ? 'secondary' : 'ghost'"
                    @click="activeView = ViewMode.List"
                >
                    List view
                </Button>
                <Button
                    type="button"
                    size="sm"
                    :variant="activeView === ViewMode.Map ? 'secondary' : 'ghost'"
                    @click="activeView = ViewMode.Map"
                >
                    Map view
                </Button>
            </div>

            <Button v-if="can_create" as-child>
                <Link :href="create()">Add property</Link>
            </Button>
        </div>
    </div>

    <PropertyFilters
        :filters="filters"
        :neighbourhood_options="neighbourhood_options"
        :furnished_options="furnished_options"
        :type_options="type_options"
        @update:filters="updateFilters"
    />

    <div v-if="properties.data.length === 0" class="text-sm text-muted-foreground">
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
