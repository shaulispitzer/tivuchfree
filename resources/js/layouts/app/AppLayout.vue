<script setup lang="ts">
import { Modal as InertiaModal } from '../../../../vendor/emargareten/inertia-modal';
import AppContent from './AppContent.vue';
import AppFooter from './AppFooter.vue';
import AppHeader from './AppHeader.vue';

const DONATION_MODAL_STORAGE_KEY = 'tivuchfree:donation-modal-response-at';
const DONATION_MODAL_DELAY_MS = 30000;
const DONATION_MODAL_COOLDOWN_MS = 30 * 24 * 60 * 60 * 1000;
const DONATION_PAGE_URL = 'https://thechesedfund.com/tivuchfree/tivuch-free';

const showDonationModal = ref(false);
let donationModalTimerId: number | undefined;

function isDonationModalInCooldown(): boolean {
    try {
        const storedTimestamp = window.localStorage.getItem(
            DONATION_MODAL_STORAGE_KEY,
        );

        if (!storedTimestamp) {
            return false;
        }

        const lastResponseAt = Number.parseInt(storedTimestamp, 10);
        if (Number.isNaN(lastResponseAt)) {
            return false;
        }

        return Date.now() - lastResponseAt < DONATION_MODAL_COOLDOWN_MS;
    } catch {
        return false;
    }
}

function rememberDonationModalResponse(): void {
    try {
        window.localStorage.setItem(
            DONATION_MODAL_STORAGE_KEY,
            Date.now().toString(),
        );
    } catch {
        // Intentionally no-op if storage is unavailable.
    }
}

function handleMaybeLaterClick(): void {
    rememberDonationModalResponse();
    showDonationModal.value = false;
}

function handleDonateNowClick(): void {
    rememberDonationModalResponse();
    showDonationModal.value = false;

    window.open(DONATION_PAGE_URL, '_blank', 'noopener,noreferrer');
}

onMounted(() => {
    if (isDonationModalInCooldown()) {
        return;
    }

    donationModalTimerId = window.setTimeout(() => {
        showDonationModal.value = true;
    }, DONATION_MODAL_DELAY_MS);
});

onUnmounted(() => {
    if (typeof donationModalTimerId === 'number') {
        window.clearTimeout(donationModalTimerId);
    }
});
</script>

<template>
    <div class="flex min-h-screen flex-col">
        <!-- make the header sticky -->
        <AppHeader class="sticky top-0 z-50" />

        <AppContent>
            <slot />
        </AppContent>

        <AppFooter />
        <InertiaModal />

        <Modal
            :open="showDonationModal"
            title="A small ask from us"
            :actions="false"
            :closable="false"
            :close-on-backdrop="false"
            width="max-w-2xl"
        >
            <div class="space-y-5">
                <p class="text-base leading-relaxed text-foreground">
                    We are so excited you are using this site! TOGETHER we will
                    IY&quot;H help everyone find their next dira &quot;Tivuch
                    Free&quot;.
                </p>

                <p class="text-base leading-relaxed text-foreground">
                    We&apos;d love it if you could help take part in the
                    expensive running costs.
                </p>

                <p
                    class="text-base leading-relaxed font-semibold text-foreground"
                >
                    Can we take you to our donate page?
                </p>

                <div class="flex flex-wrap gap-3 pt-2">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-md bg-primary px-7 py-3 text-base font-bold text-primary-foreground shadow-sm transition hover:opacity-90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                        @click="handleDonateNowClick"
                    >
                        Yes
                    </button>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-md border border-border bg-transparent px-5 py-3 text-sm font-medium text-muted-foreground transition hover:bg-muted"
                        @click="handleMaybeLaterClick"
                    >
                        Maybe Later
                    </button>
                </div>
            </div>
        </Modal>
    </div>
</template>
