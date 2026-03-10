<script setup lang="ts">
import {
    AirVent,
    Armchair,
    Bath,
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
import PepiconsPopExpand from '~icons/pepicons-pop/expand?raw';
import FluentArrowMinimize20Filled from '~icons/fluent/arrow-minimize-20-filled?raw';
import { EffectFade, Navigation, Thumbs } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/vue';
import type { Component } from 'vue';

import { useToast } from 'vue-toastification';
import { useModal } from '../../../../vendor/emargareten/inertia-modal';
import SmartHomeBoiler from '~icons/gg/smart-home-boiler';
import BedroomChildOutlineRounded from '~icons/material-symbols/bedroom-child-outline-rounded';
import 'swiper/css';
import 'swiper/css/effect-fade';
import 'swiper/css/navigation';
import 'swiper/css/thumbs';
import { router } from '@inertiajs/vue3';
import { reportTaken } from '@/routes/properties';
const props = defineProps<{
    property: Omit<App.Data.PropertyData, 'user' | 'user_id'>;
}>();

const { close: closeModal } = useModal();
const { t } = useI18n();
const page = usePage();
const toast = useToast();
const open = ref(true);
const notSpecifiedLabel = 'not specified';
const reportingTaken = ref(false);

function submitReportTaken(): void {
    const route = reportTaken({ property: props.property.id });
    reportingTaken.value = true;
    router.post(
        route.url,
        {},
        {
            preserveScroll: true,
            onFinish: () => (reportingTaken.value = false),
        },
    );
}

const imageUrls = computed(() => {
    const urls = [
        props.property.main_image_url,
        ...props.property.image_urls,
    ].filter((url): url is string => typeof url === 'string' && url.length > 0);

    return Array.from(new Set(urls));
});

const thumbsSwiper = ref();
const mainSwiperInstance = ref();
const lightboxOpen = ref(false);
const lightboxStartIndex = ref(0);
const lightboxCurrentIndex = ref(0);
const lightboxThumbsSwiper = ref();

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

function setMainSwiper(swiper: unknown): void {
    mainSwiperInstance.value = swiper;
}

function setLightboxThumbsSwiper(swiper: unknown): void {
    lightboxThumbsSwiper.value = swiper;
}

function openLightbox(): void {
    lightboxStartIndex.value =
        (mainSwiperInstance.value as any)?.realIndex ?? 0;
    lightboxCurrentIndex.value = lightboxStartIndex.value;
    lightboxOpen.value = true;
}

function closeLightbox(): void {
    lightboxOpen.value = false;
    lightboxThumbsSwiper.value = undefined;
}

function onLightboxSlideChange(swiper: any): void {
    lightboxCurrentIndex.value = swiper.realIndex;
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

function formatEnum(
    value: string | null | undefined,
    namespace:
        | 'propertyAccess'
        | 'propertyAirConditioning'
        | 'propertyApartmentCondition'
        | 'propertyFurnished'
        | 'propertyKitchenDiningRoom'
        | 'propertyPorchGarden',
): string {
    if (value == null || value === '') {
        return t('common.notSpecified');
    }
    return t(`${namespace}.${value}`);
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

onMounted(() => document.addEventListener('keydown', handleLightboxKey));
onUnmounted(() => document.removeEventListener('keydown', handleLightboxKey));

function handleLightboxKey(e: KeyboardEvent): void {
    if (e.key === 'Escape' && lightboxOpen.value) {
        closeLightbox();
    }
}

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
            value: formatEnum(props.property.furnished, 'propertyFurnished'),
            icon: Armchair,
        },
        {
            label: t('common.access'),
            value: formatEnum(props.property.access, 'propertyAccess'),
            icon: KeyRound,
        },
        {
            label: t('common.condition'),
            value: formatEnum(
                props.property.apartment_condition,
                'propertyApartmentCondition',
            ),
            icon: Home,
        },
        {
            label: t('common.kitchenDining'),
            value: formatEnum(
                props.property.kitchen_dining_room,
                'propertyKitchenDiningRoom',
            ),
            icon: ChefHat,
        },
        {
            label: t('common.porchGarden'),
            value: formatEnum(
                props.property.porch_garden,
                'propertyPorchGarden',
            ),
            icon: Trees,
        },
        {
            label: t('common.airConditioning'),
            value: formatEnum(
                props.property.air_conditioning,
                'propertyAirConditioning',
            ),
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
                        @swiper="setMainSwiper"
                    >
                        <SwiperSlide
                            v-for="(url, index) in imageUrls"
                            :key="`${url}-${index}`"
                        >
                            <img
                                :src="url"
                                :alt="t('common.mainPropertyImageAlt')"
                                class="h-56 w-full object-contain sm:h-72 md:h-80"
                            />
                        </SwiperSlide>
                    </Swiper>
                    <button
                        type="button"
                        class="absolute top-2.5 right-2.5 z-20 flex size-8 cursor-pointer items-center justify-center rounded-full bg-black/45 text-white backdrop-blur-md transition-all hover:scale-105 hover:bg-black/65 active:scale-95 [&>span>svg]:block [&>span>svg]:size-4 [&>span>svg]:fill-white"
                        @click="openLightbox"
                    >
                        <span v-html="PepiconsPopExpand" />
                    </button>
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

            <div class="flex justify-end border-t pt-4">
                <Button
                    class="inline-flex items-center gap-1.5 rounded-md border border-gray-200 bg-white p-1 text-xs font-medium text-gray-700 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="reportingTaken"
                    @click="submitReportTaken"
                >
                    {{ t('common.reportTaken') }}</Button
                >
            </div>
        </div>

        <Teleport to="body">
            <Transition name="lightbox">
                <div
                    v-if="lightboxOpen"
                    class="fixed inset-0 z-9999 flex items-center justify-center bg-black sm:bg-transparent sm:p-6 sm:backdrop-blur-sm"
                    @click.self="closeLightbox"
                >
                    <div
                        class="flex h-full w-full flex-col overflow-hidden bg-black sm:h-[90dvh] sm:max-h-[90dvh] sm:max-w-5xl sm:rounded-2xl sm:shadow-2xl"
                    >
                        <div
                            class="lightbox-header flex shrink-0 items-center justify-between px-3 py-2.5 sm:px-4 sm:py-3"
                        >
                            <span
                                class="text-sm font-medium text-white/60 tabular-nums"
                            >
                                {{ lightboxCurrentIndex + 1 }} /
                                {{ imageUrls.length }}
                            </span>
                            <button
                                type="button"
                                class="flex size-9 cursor-pointer items-center justify-center rounded-full bg-white/10 text-white transition-all hover:scale-105 hover:bg-white/20 active:scale-95 [&>span>svg]:block [&>span>svg]:size-5 [&>span>svg]:fill-white"
                                @click="closeLightbox"
                            >
                                <span v-html="FluentArrowMinimize20Filled" />
                            </button>
                        </div>

                        <div class="min-h-0 flex-1">
                            <Swiper
                                :modules="[Navigation, Thumbs]"
                                :navigation="{
                                    enabled: imageUrls.length > 1,
                                }"
                                :thumbs="{ swiper: lightboxThumbsSwiper }"
                                :loop="imageUrls.length > 1"
                                :initial-slide="lightboxStartIndex"
                                :speed="300"
                                class="lightbox-swiper h-full"
                                @slide-change="onLightboxSlideChange"
                            >
                                <SwiperSlide
                                    v-for="(url, index) in imageUrls"
                                    :key="`lb-${url}-${index}`"
                                >
                                    <div
                                        class="flex h-full w-full items-center justify-center px-3 py-2 sm:px-12"
                                    >
                                        <img
                                            :src="url"
                                            :alt="
                                                t('common.mainPropertyImageAlt')
                                            "
                                            class="max-h-full max-w-full object-contain sm:shadow-2xl"
                                        />
                                    </div>
                                </SwiperSlide>
                            </Swiper>
                        </div>

                        <div
                            v-if="imageUrls.length > 1"
                            class="lightbox-thumbs-strip shrink-0 px-3 pt-2 pb-3 sm:px-4 sm:pt-3 sm:pb-4"
                        >
                            <Swiper
                                :modules="[Thumbs]"
                                :space-between="5"
                                :slides-per-view="5"
                                :watch-slides-progress="true"
                                :slide-to-clicked-slide="true"
                                :centered-slides="true"
                                :breakpoints="{
                                    640: { slidesPerView: 7, spaceBetween: 6 },
                                    768: { slidesPerView: 9, spaceBetween: 6 },
                                }"
                                class="lightbox-thumbs-swiper"
                                @swiper="setLightboxThumbsSwiper"
                            >
                                <SwiperSlide
                                    v-for="(url, index) in imageUrls"
                                    :key="`lb-thumb-${url}-${index}`"
                                >
                                    <img
                                        :src="url"
                                        :alt="t('common.propertyThumbnailAlt')"
                                        class="h-10 w-full rounded border-2 border-transparent object-cover transition-all sm:h-14 sm:rounded-md"
                                    />
                                </SwiperSlide>
                            </Swiper>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
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

/* Landscape mobile: give all vertical space to the image */
@media (orientation: landscape) and (max-width: 767px) {
    .lightbox-thumbs-strip {
        display: none;
    }

    .lightbox-header {
        padding-top: 0.25rem;
        padding-bottom: 0.25rem;
    }
}

/* Lightbox transition */
.lightbox-enter-active {
    transition:
        opacity 0.28s ease,
        transform 0.28s ease;
}

.lightbox-leave-active {
    transition:
        opacity 0.2s ease,
        transform 0.2s ease;
}

.lightbox-enter-from,
.lightbox-leave-to {
    opacity: 0;
    transform: scale(0.97);
}

/* Lightbox main swiper */
.lightbox-swiper :deep(.swiper-button-next),
.lightbox-swiper :deep(.swiper-button-prev) {
    display: none;
}

@media (min-width: 640px) {
    .lightbox-swiper :deep(.swiper-button-next),
    .lightbox-swiper :deep(.swiper-button-prev) {
        display: flex;
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 999px;
        background: rgb(255 255 255 / 0.12);
        color: white;
        backdrop-filter: blur(8px);
        transition:
            background 0.2s,
            transform 0.15s;
    }

    .lightbox-swiper :deep(.swiper-button-next:hover),
    .lightbox-swiper :deep(.swiper-button-prev:hover) {
        background: rgb(255 255 255 / 0.22);
        transform: scale(1.08);
    }

    .lightbox-swiper :deep(.swiper-button-next svg),
    .lightbox-swiper :deep(.swiper-button-prev svg) {
        width: 1rem;
        height: 1rem;
        filter: drop-shadow(0 0 1px currentColor);
    }
}

.lightbox-swiper :deep(.swiper-slide) {
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Lightbox thumbnails */
.lightbox-thumbs-swiper :deep(.swiper-slide) {
    cursor: pointer;
    opacity: 0.45;
    transition:
        opacity 0.2s,
        transform 0.2s;
}

.lightbox-thumbs-swiper :deep(.swiper-slide:hover) {
    opacity: 0.75;
}

.lightbox-thumbs-swiper :deep(.swiper-slide-thumb-active) {
    opacity: 1;
}

.lightbox-thumbs-swiper :deep(.swiper-slide-thumb-active img) {
    border-color: white;
    box-shadow: 0 0 0 1px rgb(255 255 255 / 0.4);
}
</style>
