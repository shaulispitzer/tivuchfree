<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import L from 'leaflet';
import type { DivIcon, LayerGroup, Map as LeafletMap } from 'leaflet';
import type { PropType } from 'vue';
import 'leaflet/dist/leaflet.css';
import PropertyFilters from '@/components/properties/PropertyFilters.vue';
import { Button } from '@/components/ui/button';
import { create, index, show } from '@/routes/properties';

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

type MapProperty = {
    id: number;
    price: number | null;
    bedrooms: number;
    lat: number | null;
    lon: number | null;
};

const ViewMode = {
    List: 'list',
    Map: 'map',
} as const;

type ViewModeValue = (typeof ViewMode)[keyof typeof ViewMode];

const SortValue = {
    Newest: 'newest',
} as const;

const DEFAULT_MAP_CENTER: [number, number] = [31.778, 35.235];
const DEFAULT_MAP_ZOOM = 12;

const props = defineProps({
    properties: {
        type: Array as PropType<MapProperty[]>,
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

const activeView = ref<ViewModeValue>(ViewMode.Map);
const mapContainer = ref<HTMLElement | null>(null);
const mapInstance = shallowRef<LeafletMap | null>(null);
const markerLayer = shallowRef<LayerGroup | null>(null);
const propertiesWithCoordinates = computed(() =>
    props.properties.filter(
        (property) => property.lat !== null && property.lon !== null,
    ),
);

let markerIcon: DivIcon | null = null;

function roundBedrooms(value: number): number {
    return Math.round((value + Number.EPSILON) * 2) / 2;
}

function buildQuery(value: typeof filters.value): Record<string, string> {
    const query: Record<string, string> = {
        view: ViewMode.Map,
    };
    const bedroomsMin = roundBedrooms(value.bedrooms_range[0]);
    const bedroomsMax = roundBedrooms(value.bedrooms_range[1]);

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

function formatMapPrice(value: number | null): string {
    if (value === null) {
        return 'Price not listed';
    }

    return `₪${value.toFixed(0)}`;
}

function formatMapBedrooms(value: number): string {
    return `${roundBedrooms(value)} rooms`;
}

function openPropertyModal(propertyId: number): void {
    router.get(
        show(propertyId),
        { view: ViewMode.Map },
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
}

function ensureMapInitialized(): void {
    if (mapInstance.value || !mapContainer.value) {
        return;
    }

    mapInstance.value = L.map(mapContainer.value, {
        scrollWheelZoom: true,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution:
            '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19,
    }).addTo(mapInstance.value);

    markerLayer.value = L.layerGroup().addTo(mapInstance.value);
    markerIcon = L.divIcon({
        className: 'property-map-marker',
        html: '<span class="property-map-marker-dot"></span>',
        iconSize: [24, 24],
        iconAnchor: [12, 24],
        tooltipAnchor: [0, -18],
    });

    mapInstance.value.setView(DEFAULT_MAP_CENTER, DEFAULT_MAP_ZOOM);
}

function renderMapMarkers(): void {
    if (
        mapInstance.value === null ||
        markerLayer.value === null ||
        markerIcon === null
    ) {
        return;
    }

    markerLayer.value.clearLayers();

    if (propertiesWithCoordinates.value.length === 0) {
        mapInstance.value.setView(DEFAULT_MAP_CENTER, DEFAULT_MAP_ZOOM);
        return;
    }

    const bounds = L.latLngBounds([]);

    for (const property of propertiesWithCoordinates.value) {
        if (property.lat === null || property.lon === null) {
            continue;
        }

        const marker = L.marker([property.lat, property.lon], {
            icon: markerIcon,
        });

        marker.bindTooltip(
            `
                <div class="property-map-tooltip-content">
                    <p>${formatMapBedrooms(property.bedrooms)}</p>
                    <p>${formatMapPrice(property.price)}</p>
                </div>
            `,
            {
                direction: 'top',
                permanent: true,
                opacity: 1,
                className: 'property-map-tooltip',
            },
        );

        marker.on('click', () => openPropertyModal(property.id));
        marker.addTo(markerLayer.value);
        bounds.extend([property.lat, property.lon]);
    }

    if (bounds.isValid()) {
        mapInstance.value.fitBounds(bounds, {
            padding: [40, 40],
            maxZoom: 15,
        });
    }
}

function refreshMap(): void {
    ensureMapInitialized();
    renderMapMarkers();
}

async function syncMapViewport(): Promise<void> {
    await nextTick();
    mapInstance.value?.invalidateSize();

    setTimeout(() => {
        mapInstance.value?.invalidateSize();
    }, 120);
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
    if (activeView.value === ViewMode.List) {
        const query = buildQuery(filters.value);
        delete query.view;

        router.get(index(), query, {
            preserveScroll: true,
            preserveState: true,
        });
    }
});

watch(
    () => props.properties,
    async () => {
        refreshMap();
        await syncMapViewport();
    },
    { deep: true },
);

onMounted(async () => {
    refreshMap();
    await syncMapViewport();
});

onBeforeUnmount(() => {
    mapInstance.value?.remove();
    mapInstance.value = null;
    markerLayer.value = null;
});
</script>

<template>
    <Head title="Properties Map" />

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

    <div class="space-y-3">
        <div v-if="properties.length === 0" class="text-sm text-muted-foreground">
            No properties have been added yet.
        </div>
        <div
            v-else-if="propertiesWithCoordinates.length === 0"
            class="text-sm text-muted-foreground"
        >
            No properties with map coordinates match these filters.
        </div>

        <div
            ref="mapContainer"
            class="h-[65vh] w-full overflow-hidden rounded-xl border border-input"
        />

        <p class="text-xs text-muted-foreground">
            Property details are shown on each pin. Click a pin to open details.
        </p>
    </div>
</template>

<style scoped>
:deep(.property-map-marker) {
    background: transparent;
    border: 0;
}

:deep(.property-map-marker-dot) {
    display: block;
    width: 24px;
    height: 24px;
    border-radius: 9999px 9999px 9999px 0;
    background: rgb(220 38 38);
    border: 2px solid white;
    transform: rotate(-45deg);
    box-shadow: 0 4px 10px rgb(15 23 42 / 0.35);
}

:deep(.property-map-tooltip) {
    border: 0;
    border-radius: 0.5rem;
    background: rgb(15 23 42 / 0.92);
    color: white;
    box-shadow: 0 8px 24px rgb(15 23 42 / 0.2);
}

:deep(.property-map-tooltip .leaflet-tooltip-content) {
    margin: 0;
}

:deep(.property-map-tooltip-content) {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
    padding: 0.5rem 0.625rem;
    font-size: 0.75rem;
    line-height: 1rem;
    white-space: nowrap;
}

:deep(.property-map-tooltip-content p) {
    margin: 0;
}
</style>
