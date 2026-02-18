<script setup lang="ts">
import { Navigation, Pagination } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/vue';

import type { PropType } from 'vue';

import { show } from '@/routes/properties';
import fallbackPropertyImage from '../../assets/default-property-image.webp';
import takenStampImage from '../../assets/taken.png';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import IconBed from '~icons/mdi/bed';
import IconCalendarRange from '~icons/mdi/calendar-range';
import IconHome from '~icons/mdi/home-outline';
import IconHomeVariant from '~icons/mdi/home-variant-outline';
import IconMapMarker from '~icons/mdi/map-marker-outline';
import IconRulerSquare from '~icons/mdi/ruler-square';
import IconSofa from '~icons/mdi/sofa';

const props = defineProps({
    property: {
        type: Object as PropType<App.Data.PropertyData>,
        required: true,
    },
});

const imageUrls = computed(() => {
    const urls = [
        props.property.main_image_url,
        ...props.property.image_urls,
    ].filter((url): url is string => typeof url === 'string' && url.length > 0);

    return Array.from(new Set(urls));
});

const addressLine = computed(() =>
    [props.property.building_number, props.property.street]
        .filter(Boolean)
        .join(' '),
);

const formatLabel = (value: string | null) =>
    value
        ? value
              .split('_')
              .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
              .join(' ')
        : '—';

const formatDate = (value: string | null) =>
    value ? value.split('T')[0] : '—';

const swiperStyles = {
    '--swiper-pagination-color': 'var(--color-primary)',
    '--swiper-pagination-bullet-inactive-color':
        'var(--color-muted-foreground)',
    '--swiper-pagination-bullet-inactive-opacity': '0.35',
    '--swiper-navigation-color': 'var(--color-primary)',
};

const showNavigation = computed(() => imageUrls.value.length > 1);

const neighbourhoodLabel = computed(() => {
    const neighbourhoods = props.property.neighbourhoods ?? [];

    if (neighbourhoods.length === 0) {
        return '—';
    }

    return neighbourhoods
        .map((neighbourhood) => formatLabel(neighbourhood))
        .join(', ');
});
</script>

<template>
    <Link
        :href="show(property.id)"
        preserve-scroll
        as="button"
        preserve-state
        class="block focus-visible:outline-none"
        :class="property.taken === true ? 'cursor-default' : 'cursor-pointer'"
        :disabled="property.taken === true"
    >
        <article
            class="relative overflow-hidden rounded-xl border border-input bg-card shadow-sm focus-visible:ring-2 focus-visible:ring-primary/40"
            :class="
                property.taken === true
                    ? 'pointer-events-none opacity-80 grayscale-75'
                    : 'transition hover:-translate-y-0.5 hover:shadow-md'
            "
        >
            <img
                v-if="property.taken === true"
                :src="takenStampImage"
                alt="Taken property"
                class="pointer-events-none absolute inset-x-0 top-5 z-20 mx-auto w-40 select-none"
                loading="lazy"
            />

            <div class="relative">
                <Swiper
                    v-if="imageUrls.length > 0"
                    :modules="[Navigation, Pagination]"
                    :navigation="showNavigation"
                    :pagination="{ clickable: true }"
                    :loop="showNavigation"
                    class="property-card-swiper"
                    :style="swiperStyles"
                >
                    <SwiperSlide
                        v-for="(url, index) in imageUrls"
                        :key="`${url}-${index}`"
                    >
                        <img
                            :src="url"
                            alt="Property image"
                            class="h-52 w-full object-cover"
                            loading="lazy"
                        />
                    </SwiperSlide>
                </Swiper>

                <div v-else class="h-52 w-full">
                    <img
                        :src="fallbackPropertyImage"
                        alt="Default property image"
                        class="h-full w-full object-cover opacity-70 contrast-75"
                        loading="lazy"
                    />
                </div>
            </div>

            <div class="space-y-4 p-4">
                <div class="flex items-start justify-between gap-4">
                    <div class="space-y-1">
                        <div
                            class="flex items-center gap-2 text-base font-semibold"
                        >
                            <IconHome class="h-4 w-4 text-primary" />

                            <span>{{ addressLine }}</span>
                        </div>
                        <div
                            class="flex items-center gap-2 text-xs text-muted-foreground"
                        >
                            <IconMapMarker class="h-4 w-4" />
                            <span>{{ neighbourhoodLabel }}</span>
                        </div>
                    </div>
                    <span class="text-sm font-medium">{{
                        property.price ? '₪' + property.price.toFixed(2) : ''
                    }}</span>
                </div>

                <div
                    v-if="property.type === 'medium_term'"
                    class="flex flex-wrap items-center gap-2 text-xs text-muted-foreground"
                >
                    <Badge class="bg-yellow-300 text-yellow-950">
                        <IconCalendarRange class="h-3.5 w-3.5" />
                        Medium term
                    </Badge>
                    <span class="flex items-center gap-1">
                        <IconCalendarRange class="h-4 w-4" />
                        {{ formatDate(property.available_from) }} -
                        {{ formatDate(property.available_to) }}
                    </span>
                </div>

                <div
                    v-else
                    class="flex items-center gap-2 text-xs text-muted-foreground"
                >
                    <IconCalendarRange class="h-4 w-4" />
                    Available from {{ formatDate(property.available_from) }}
                </div>

                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div class="flex items-center gap-2">
                        <IconBed class="h-4 w-4 text-primary" />
                        <span class="font-medium">{{ property.bedrooms }}</span>
                        <span class="text-muted-foreground">Bedrooms</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <IconRulerSquare class="h-4 w-4 text-primary" />
                        <span class="font-medium">{{
                            property.square_meter ?? '—'
                        }}</span>
                        <span class="text-muted-foreground">m2</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <IconSofa class="h-4 w-4 text-primary" />
                        <span class="text-muted-foreground">Furnished</span>
                        <span class="font-medium">{{
                            formatLabel(property.furnished)
                        }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <IconHomeVariant class="h-4 w-4 text-primary" />
                        <span class="text-muted-foreground">Condition</span>
                        <span class="font-medium">{{
                            formatLabel(property.apartment_condition)
                        }}</span>
                    </div>
                </div>
            </div>
        </article>
    </Link>
</template>

<style scoped>
.property-card-swiper :deep(.swiper-pagination) {
    bottom: 0.5rem;
}

.property-card-swiper :deep(.swiper-button-next),
.property-card-swiper :deep(.swiper-button-prev) {
    width: 2rem;
    height: 2rem;
    border-radius: 999px;
    background: rgb(15 23 42 / 0.45);
    backdrop-filter: blur(6px);
}

.property-card-swiper :deep(.swiper-button-next::after),
.property-card-swiper :deep(.swiper-button-prev::after) {
    font-size: 0.9rem;
    font-weight: 700;
}
</style>
