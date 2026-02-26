<script setup lang="ts">
import {
    AirVent,
    Armchair,
    Bath,
    BedDouble,
    CalendarClock,
    CalendarDays,
    ChefHat,
    Eye,
    Home,
    KeyRound,
    Layers,
    ParkingSquare,
    Ruler,
    SunMedium,
    Trees,
    Warehouse,
} from 'lucide-vue-next';
import { EffectFade, Navigation, Thumbs } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/vue';
import type { Component } from 'vue';
import { computed, ref } from 'vue';
import { useModal } from '../../../../vendor/emargareten/inertia-modal';
import SmartHomeBoiler from '~icons/gg/smart-home-boiler';
import BedroomChildOutlineRounded from '~icons/material-symbols/bedroom-child-outline-rounded';
import 'swiper/css';
import 'swiper/css/effect-fade';
import 'swiper/css/navigation';
import 'swiper/css/thumbs';

const props = defineProps<{
    property: Omit<App.Data.PropertyData, 'user' | 'user_id'>;
}>();

const { close: closeModal } = useModal();
const { t } = useI18n();
const open = ref(true);
const notSpecifiedLabel = 'not specified';

const imageUrls = computed(() => {
    const urls = [
        props.property.main_image_url,
        ...props.property.image_urls,
    ].filter((url): url is string => typeof url === 'string' && url.length > 0);

    return Array.from(new Set(urls));
});

const thumbsSwiper = ref();

const fullAddress = computed(() => {
    return [props.property.building_number, props.property.street]
        .filter(Boolean)
        .join(' ');
});

const neighbourhoodsLabel = computed(() => {
    if (props.property.neighbourhoods.length === 0) {
        return null;
    }

    return props.property.neighbourhoods.map(formatLabel).join(', ');
});

const isMediumTerm = computed(() => props.property.type === 'medium_term');

function close(): void {
    open.value = false;
    closeModal();
}

function setThumbsSwiper(swiper: unknown): void {
    thumbsSwiper.value = swiper;
}

function formatLabel(value: string | null): string {
    if (!value) {
        return notSpecifiedLabel;
    }

    return value
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}

function formatDate(value: string | null): string | null {
    return value ? value.split('T')[0] : null;
}

function formatBoolean(value: boolean): string {
    return value ? t('common.yes') : t('common.no');
}

function isMissing(value: unknown): boolean {
    if (value === null || value === undefined) {
        return true;
    }

    if (typeof value === 'string') {
        return value.trim().length === 0 || value === notSpecifiedLabel;
    }

    return false;
}

type PropertyDetail = {
    label: string;
    value: string | number | null;
    icon: Component;
};

const propertyDetails = computed<PropertyDetail[]>(() => {
    const details: PropertyDetail[] = [
        {
            label: t('common.bedrooms'),
            value: props.property.bedrooms,
            icon: BedroomChildOutlineRounded,
        },
        {
            label: t('common.bathrooms'),
            value: props.property.bathrooms,
            icon: Bath,
        },
        {
            label: t('common.squareMeter'),
            value: props.property.square_meter,
            icon: Ruler,
        },
        {
            label: t('common.floor'),
            value: props.property.floor,
            icon: Layers,
        },
        {
            label: t('common.furnished'),
            value: props.property.furnished
                ? t('propertyFurnished.' + props.property.furnished)
                : t('common.notSpecified'),
            icon: Armchair,
        },
        {
            label: t('common.access'),
            value: t(props.property.access ?? 'not specified'),
            icon: KeyRound,
        },
        {
            label: t('common.condition'),
            value: t(props.property.apartment_condition ?? 'not specified'),
            icon: Home,
        },
        {
            label: t('common.kitchenDining'),
            value: t(props.property.kitchen_dining_room ?? 'not specified'),
            icon: ChefHat,
        },
        {
            label: t('common.porchGarden'),
            value: t(props.property.porch_garden ?? 'not specified'),
            icon: Trees,
        },
        {
            label: t('common.airConditioning'),
            value: t(props.property.air_conditioning ?? 'not specified'),
            icon: AirVent,
        },
        {
            label: t('common.succahPorch'),
            value: formatBoolean(props.property.succah_porch),
            icon: SunMedium,
        },
        {
            label: t('common.dudShemesh'),
            value: formatBoolean(props.property.has_dud_shemesh),
            icon: SmartHomeBoiler,
        },
        {
            label: t('common.machsan'),
            value: formatBoolean(props.property.has_machsan),
            icon: Warehouse,
        },
        {
            label: t('common.parkingSpot'),
            value: formatBoolean(props.property.has_parking_spot),
            icon: ParkingSquare,
        },
        {
            label: t('common.views'),
            value: props.property.views,
            icon: Eye,
        },
    ];

    return details;
});
</script>

<template>
    <Modal
        :open="open"
        :title="
            t('common.property', 1) +
            ' #' +
            (property.type === 'long_term' ? '1' : '6') +
            property.id
        "
        :as-page="true"
        @close="close"
        width="max-w-4xl"
        :confirmable="false"
        :cancel-label="t('common.close')"
        margin="mt-0"
    >
        <div class="max-h-[80vh] space-y-6 overflow-y-auto p-1 sm:p-2">
            <div class="space-y-3">
                <div
                    v-if="imageUrls.length > 0"
                    class="relative overflow-hidden rounded-xl border bg-muted/20"
                >
                    <Swiper
                        :modules="[Navigation, Thumbs, EffectFade]"
                        :navigation="imageUrls.length > 1"
                        :thumbs="{ swiper: thumbsSwiper }"
                        :loop="imageUrls.length > 1"
                        effect="fade"
                        :fade-effect="{ crossFade: true }"
                        :speed="350"
                        class="property-show-swiper"
                    >
                        <SwiperSlide
                            v-for="(url, index) in imageUrls"
                            :key="`${url}-${index}`"
                        >
                            <img
                                :src="url"
                                :alt="t('common.mainPropertyImageAlt')"
                                class="h-56 w-full object-cover sm:h-72 md:h-80"
                            />
                        </SwiperSlide>
                    </Swiper>
                </div>
                <div
                    v-if="imageUrls.length > 1"
                    class="overflow-hidden rounded-lg"
                >
                    <Swiper
                        :modules="[Thumbs]"
                        :space-between="8"
                        :slides-per-view="4"
                        :watch-slides-progress="true"
                        :slide-to-clicked-slide="true"
                        :breakpoints="{
                            640: { slidesPerView: 5 },
                            768: { slidesPerView: 6 },
                        }"
                        class="property-show-thumbs-swiper"
                        @swiper="setThumbsSwiper"
                    >
                        <SwiperSlide
                            v-for="(url, index) in imageUrls"
                            :key="`thumb-${url}-${index}`"
                        >
                            <img
                                :src="url"
                                :alt="t('common.propertyThumbnailAlt')"
                                class="h-16 w-full rounded-md border border-border/60 object-cover transition sm:h-20"
                            />
                        </SwiperSlide>
                    </Swiper>
                </div>
                <div
                    v-if="imageUrls.length === 0"
                    class="flex h-24 w-full items-center justify-center rounded-lg border border-dashed text-xs text-muted-foreground sm:h-28"
                >
                    {{ t('common.noImagesAvailable') }}
                </div>
            </div>

            <div class="space-y-1">
                <h1 class="text-2xl font-semibold">{{ fullAddress }}</h1>
                <p
                    class="text-sm text-muted-foreground"
                    :class="{ italic: isMissing(neighbourhoodsLabel) }"
                >
                    {{ neighbourhoodsLabel ?? t('common.notSpecified') }}
                </p>
                <p
                    class="text-xl font-semibold text-primary"
                    :class="{
                        'text-muted-foreground italic': isMissing(
                            property.price,
                        ),
                    }"
                >
                    {{
                        property.price !== null
                            ? `₪${property.price.toFixed(2)}`
                            : t('common.notSpecified')
                    }}
                </p>
                <div
                    class="flex flex-wrap items-center gap-3 text-xs text-muted-foreground"
                >
                    <span class="inline-flex items-center gap-1.5">
                        <CalendarDays class="h-3.5 w-3.5 text-primary" />
                        <span>{{ t('common.availableFrom') }}</span>
                        <span
                            class="font-medium"
                            :class="{
                                italic: isMissing(
                                    formatDate(property.available_from),
                                ),
                            }"
                        >
                            {{
                                formatDate(property.available_from) ??
                                t('common.notSpecified')
                            }}
                        </span>
                    </span>
                    <span
                        v-if="isMediumTerm"
                        class="inline-flex items-center gap-1.5"
                    >
                        <CalendarClock class="h-3.5 w-3.5 text-primary" />
                        <span>{{ t('common.availableTo') }}</span>
                        <span
                            class="font-medium"
                            :class="{
                                italic: isMissing(
                                    formatDate(property.available_to),
                                ),
                            }"
                        >
                            {{
                                formatDate(property.available_to) ??
                                t('common.notSpecified')
                            }}
                        </span>
                    </span>
                </div>
                <div v-if="isMediumTerm">
                    <Badge
                        class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-800"
                    >
                        <CalendarClock class="h-3.5 w-3.5" />
                        {{ t('common.mediumTermRental') }}
                    </Badge>
                </div>
            </div>

            <div
                v-if="
                    property.contact_name ||
                    property.contact_phone ||
                    property.contact_phone_2
                "
                class="space-y-2 rounded-md bg-muted/20 p-4"
            >
                <p class="text-xs text-muted-foreground">
                    {{ t('common.contactSection') }}
                </p>
                <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm">
                    <span v-if="property.contact_name" class="font-medium">
                        {{ property.contact_name }}
                    </span>
                    <span
                        v-if="property.contact_phone"
                        class="text-muted-foreground"
                    >
                        {{ property.contact_phone }}
                    </span>
                    <span
                        v-if="property.contact_phone_2"
                        class="text-muted-foreground"
                    >
                        {{ property.contact_phone_2 }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-x-6 gap-y-4 md:grid-cols-3">
                <div
                    v-for="detail in propertyDetails"
                    :key="detail.label"
                    class="flex items-start gap-2.5"
                >
                    <component
                        :is="detail.icon"
                        class="mt-0.5 h-4 w-4 shrink-0 text-primary"
                    />
                    <div class="min-w-0">
                        <p class="text-xs text-muted-foreground">
                            {{ detail.label }}
                        </p>
                        <p
                            class="font-medium"
                            :class="{
                                'text-muted-foreground italic': isMissing(
                                    detail.value,
                                ),
                            }"
                        >
                            {{
                                isMissing(detail.value)
                                    ? t('common.notSpecified')
                                    : detail.value
                            }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="space-y-2 rounded-md bg-muted/20 p-4">
                <p class="text-xs text-muted-foreground">
                    {{ t('common.additionalInfo') }}
                </p>
                <p
                    class="text-sm leading-relaxed"
                    :class="{
                        'text-muted-foreground italic': isMissing(
                            property.additional_info,
                        ),
                    }"
                >
                    {{ property.additional_info ?? t('common.notSpecified') }}
                </p>
            </div>
        </div>
    </Modal>
</template>

<style scoped>
.property-show-swiper :deep(.swiper-button-next),
.property-show-swiper :deep(.swiper-button-prev) {
    width: 1.5rem;
    height: 1.5rem;
    padding: 0;
    border-radius: 999px;
    background: rgb(15 23 42 / 0.45);
    color: white;
    backdrop-filter: blur(6px);
}

.property-show-swiper :deep(.swiper-button-next svg),
.property-show-swiper :deep(.swiper-button-prev svg) {
    width: 0.7rem;
    height: 0.7rem;
    filter: drop-shadow(0 0 0.5px currentColor);
}

.property-show-thumbs-swiper :deep(.swiper-slide) {
    cursor: pointer;
}

.property-show-thumbs-swiper :deep(.swiper-slide-thumb-active img) {
    border-color: var(--color-primary);
    box-shadow: 0 0 0 2px
        color-mix(in srgb, var(--color-primary) 35%, transparent);
}
</style>
