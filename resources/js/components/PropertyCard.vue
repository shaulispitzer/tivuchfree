<script setup lang="ts">
import { Navigation, Pagination } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/vue';

import type { PropType } from 'vue';
import { useI18n } from 'vue-i18n';

import { show } from '@/routes/properties';
import fallbackPropertyImage from '../../assets/DeafultPropertyImage.webp';
import takenStampImage from '../../assets/taken.webp';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import MaterialSymbolsStairs2Rounded from '~icons/material-symbols/stairs-2-rounded';
import IconBed from '~icons/mdi/bed';
import IconCalendarRange from '~icons/mdi/calendar-range';
import IconCurrencyUsd from '~icons/mdi/currency-usd';
import IconHome from '~icons/mdi/home-outline';
import IconMapMarker from '~icons/mdi/map-marker-outline';
import IconRulerSquare from '~icons/mdi/ruler-square';
const { t } = useI18n();
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

const dateLocale = (): string | undefined =>
    typeof document !== 'undefined' &&
    document.documentElement?.lang?.startsWith('he')
        ? 'he'
        : typeof navigator !== 'undefined' &&
            navigator.language?.startsWith('he')
          ? 'he'
          : undefined;

const formatDate = (value: string | null): string => {
    if (!value) return '—';
    const date = new Date(value);
    return Number.isNaN(date.getTime())
        ? '—'
        : date.toLocaleDateString(dateLocale(), {
              day: 'numeric',
              month: 'short',
              year: 'numeric',
          });
};

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
        .map((name) => {
            const key = name
                .split(/\s+/)
                .map(
                    (word: string) =>
                        word.charAt(0).toUpperCase() +
                        word.slice(1).toLowerCase(),
                )
                .join('');
            const translated = t(`neighbourhood.${key}`);
            return translated !== `neighbourhood.${key}` ? translated : name;
        })
        .join(', ');
});
</script>

<template>
    <Link
        :href="show(property.id)"
        preserve-scroll
        preserve-state
        class="block focus-visible:outline-none"
        :class="
            property.taken === true
                ? 'pointer-events-none cursor-default'
                : 'cursor-pointer'
        "
        :tabindex="property.taken === true ? -1 : undefined"
        :aria-disabled="property.taken === true || undefined"
    >
        <!-- make the taken image not grayscale -->
        <article
            class="relative overflow-hidden rounded-xl border border-input bg-card shadow-sm focus-visible:ring-2 focus-visible:ring-primary/40"
            :class="
                !property.taken &&
                'transition hover:-translate-y-0.5 hover:scale-101 hover:shadow-lg'
            "
        >
            <img
                v-if="property.taken === true"
                :src="takenStampImage"
                :alt="t('common.takenPropertyStampAlt')"
                class="pointer-events-none absolute inset-x-0 top-7 z-20 mx-auto w-40 select-none"
                loading="lazy"
            />

            <div
                class="relative"
                :class="property.taken ? 'opacity-80 grayscale' : ''"
            >
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
                            :alt="t('common.mainPropertyImageAlt')"
                            class="h-52 w-full object-cover"
                            loading="lazy"
                        />
                    </SwiperSlide>
                </Swiper>

                <div v-else class="h-52 w-full">
                    <img
                        :src="fallbackPropertyImage"
                        :alt="t('common.defaultPropertyImageAlt')"
                        class="h-full w-full opacity-70 contrast-75"
                        loading="lazy"
                    />
                </div>
                <!-- make the badge centered -->
                <div class="absolute -bottom-2 z-10 flex w-full justify-center">
                    <Badge
                        v-if="property.type === 'medium_term'"
                        class="bg-yellow-300 text-yellow-950"
                    >
                        {{ t('common.mediumTermRental') }}
                    </Badge>
                </div>
            </div>

            <div
                class="space-y-4 p-4"
                :class="property.taken ? 'opacity-80 grayscale' : ''"
            >
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
                    class="flex items-center gap-2 text-xs text-muted-foreground"
                >
                    <IconCalendarRange class="h-4 w-4" />
                    {{ formatDate(property.available_from) }} -
                    {{ formatDate(property.available_to) }}
                </div>

                <div
                    v-else
                    class="flex items-center gap-2 text-xs text-muted-foreground"
                >
                    <IconCalendarRange class="h-4 w-4" />
                    {{ t('common.availableFrom') }}:
                    <span class="font-medium">{{
                        formatDate(property.available_from)
                    }}</span>
                </div>

                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div class="flex items-center gap-2">
                        <IconCurrencyUsd class="h-4 w-4 text-primary" />
                        <span class="text-muted-foreground"
                            >{{ t('common.price') }}:</span
                        >
                        <span class="font-medium" v-if="property.price">{{
                            '₪' + property.price.toFixed(2)
                        }}</span>
                        <span class="font-medium italic" v-else>{{
                            t('common.notSpecified')
                        }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <IconBed class="h-4 w-4 text-primary" />
                        <span class="text-muted-foreground"
                            >{{ t('common.bedrooms') }}:</span
                        >
                        <span class="font-medium">{{ property.bedrooms }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <IconRulerSquare class="h-4 w-4 text-primary" />
                        <span class="text-muted-foreground"
                            >{{ t('common.m2') }}:</span
                        >
                        <span class="font-medium">{{
                            property.square_meter ?? '—'
                        }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <MaterialSymbolsStairs2Rounded
                            class="h-4 w-4 text-primary"
                        />
                        <span class="text-muted-foreground"
                            >{{ t('common.floor') }}:</span
                        >
                        <span class="font-medium">{{
                            property.floor ?? '—'
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
    width: 1.5rem;
    height: 1.5rem;
    padding: 0;
    border-radius: 999px;
    background: rgb(15 23 42 / 0.45);
    backdrop-filter: blur(6px);
    color: white;
}

.property-card-swiper :deep(.swiper-button-next svg),
.property-card-swiper :deep(.swiper-button-prev svg) {
    width: 0.7rem;
    height: 0.7rem;
    filter: drop-shadow(0 0 0.5px currentColor);
}
</style>
