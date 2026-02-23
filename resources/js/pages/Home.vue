<script setup lang="ts">
import heroImage from '@assets/jerusalem-night-view.jpg';
import { Head, Link } from '@inertiajs/vue3';

const { t } = useI18n();
const shortTermModalOpen = ref(false);
const targetSavings = 8_787_876_786;
const displayedSavings = ref(0);

const testimonials = [
    {
        name: 'Chaya R.',
        role: 'Property Owner',
        quote: 'I listed in minutes and got serious inquiries fast, without paying agent fees.',
    },
    {
        name: 'Dovid L.',
        role: 'Renter',
        quote: 'Clean listings, direct communication, and no middleman costs. Exactly what I needed.',
    },
    {
        name: 'Malky S.',
        role: 'Landlord',
        quote: 'This platform saved me both money and time. The process feels simple and fair.',
    },
];

const formattedSavings = computed(() =>
    new Intl.NumberFormat('en-US').format(displayedSavings.value),
);

function openShortTermModal(): void {
    shortTermModalOpen.value = true;
}

function closeShortTermModal(): void {
    shortTermModalOpen.value = false;
}

onMounted(() => {
    const durationInMs = 2200;
    const animationStart = performance.now();

    const animateCounter = (timestamp: number): void => {
        const elapsed = timestamp - animationStart;
        const progress = Math.min(elapsed / durationInMs, 1);

        displayedSavings.value = Math.floor(targetSavings * progress);

        if (progress < 1) {
            requestAnimationFrame(animateCounter);
            return;
        }

        displayedSavings.value = targetSavings;
    };

    requestAnimationFrame(animateCounter);
});
</script>

<template>
    <div class="overflow-hidden">
        <Head :title="t('common.home')" />

        <section class="relative isolate min-h-[78vh] overflow-hidden">
            <img
                :src="heroImage"
                alt="Jerusalem at night"
                class="absolute inset-0 h-full w-full object-cover"
            />
            <div class="absolute inset-0 bg-slate-950/55" />

            <div
                class="relative z-10 mx-auto flex min-h-[78vh] max-w-7xl flex-col items-center justify-center gap-7 px-6 py-12 text-center text-white"
            >
                <p class="text-sm text-white" dir="rtl">
                    לע&quot;נ הצדיק ר&apos; ישעיה בן ר&apos; משה זי&quot;ע
                </p>
                <h1
                    class="text-3xl font-bold tracking-tight text-balance sm:text-5xl"
                >
                    {{ t('common.freeToListFreeToFind') }}
                </h1>

                <p class="text-lg font-medium text-white/90">
                    {{ t('common.saveMoneyConnectDirectlyMoveSmarter') }}
                </p>

                <div
                    class="grid w-full max-w-4xl grid-cols-1 place-items-center gap-5 pt-2 sm:grid-cols-3"
                >
                    <Link
                        as="button"
                        type="button"
                        href="/properties?type=long_term"
                        class="flex h-54 w-54 cursor-pointer items-center justify-center rounded-full border border-white/35 bg-primary p-5 text-center text-lg font-semibold text-white transition hover:scale-[1.03] hover:bg-primary/90 hover:shadow-xl"
                    >
                        {{ t('common.longTermListings') }}
                    </Link>

                    <Link
                        as="button"
                        type="button"
                        href="/properties?type=medium_term"
                        class="flex h-54 w-54 cursor-pointer items-center justify-center rounded-full border border-white/35 bg-primary p-5 text-center text-lg font-semibold text-white transition hover:scale-[1.03] hover:bg-primary/90 hover:shadow-xl"
                    >
                        {{ t('common.mediumTermListings') }}
                    </Link>

                    <button
                        type="button"
                        class="flex h-54 w-54 cursor-pointer items-center justify-center rounded-full border border-white/35 bg-primary p-5 text-center text-lg font-semibold text-white transition hover:scale-[1.03] hover:bg-primary/90 hover:shadow-xl"
                        @click="openShortTermModal"
                    >
                        {{ t('common.shortTermListings') }}
                    </button>
                </div>
            </div>
        </section>

        <section class="bg-background py-14">
            <div class="mx-auto max-w-7xl px-6">
                <div
                    class="rounded-2xl border border-border bg-card p-7 text-center shadow-sm"
                >
                    <p
                        class="text-sm font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        {{ t('common.moneySavedByOurCommunity') }}
                    </p>
                    <p class="mt-3 text-3xl font-bold text-primary sm:text-5xl">
                        ₪{{ formattedSavings }}
                    </p>
                </div>
            </div>
        </section>

        <section class="bg-muted/40 py-14">
            <div class="mx-auto max-w-7xl px-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-2xl font-bold sm:text-3xl">
                        {{ t('common.testimonials') }}
                    </h2>
                    <button
                        type="button"
                        class="rounded-full border border-primary/40 bg-background px-5 py-2.5 text-sm font-semibold text-primary transition hover:bg-primary/10"
                    >
                        {{ t('common.leaveAReview') }}
                    </button>
                </div>

                <div class="mt-7 grid gap-5 md:grid-cols-3">
                    <article
                        v-for="testimonial in testimonials"
                        :key="testimonial.name"
                        class="rounded-2xl border border-border bg-card p-6 shadow-sm"
                    >
                        <p class="text-base leading-relaxed text-foreground">
                            &quot;{{ testimonial.quote }}&quot;
                        </p>
                        <p class="mt-5 text-sm font-semibold text-primary">
                            {{ testimonial.name }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ testimonial.role }}
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <Modal
            :open="shortTermModalOpen"
            :title="t('common.shortTermProperties')"
            :actions="false"
            :as-page="false"
            @close="closeShortTermModal"
        >
            <div class="space-y-5">
                <p class="text-sm leading-relaxed text-muted-foreground">
                    {{ t('common.pleaseNoteTivuchFreeDoesNotHave') }}
                </p>

                <a
                    href="https://www.heimishe.apartments"
                    class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground transition hover:opacity-90"
                >
                    {{ t('common.goToWwwHeimisheApartments') }}
                </a>
            </div>
        </Modal>
    </div>
</template>
