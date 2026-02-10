<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Navigation, Pagination } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/vue';
import { computed } from 'vue';
import type { PropType } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { edit } from '@/routes/properties';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import IconBed from '~icons/mdi/bed';
import IconCalendarRange from '~icons/mdi/calendar-range';
import IconHome from '~icons/mdi/home-outline';
import IconHomeVariant from '~icons/mdi/home-variant-outline';
import IconImageOff from '~icons/mdi/image-off-outline';
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
</script>

<template>
    <article
        class="overflow-hidden rounded-xl border border-input bg-card shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
    >
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

            <div
                v-else
                class="flex h-52 w-full items-center justify-center bg-muted text-sm text-muted-foreground"
            >
                <IconImageOff class="h-5 w-5" />
                <span class="ml-2">No images available</span>
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
                        <span>{{ formatLabel(property.neighbourhood) }}</span>
                    </div>
                </div>
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
