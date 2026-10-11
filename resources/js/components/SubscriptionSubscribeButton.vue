<script setup lang="ts">
import { subscribe } from '@/routes';
import {
    SUBSCRIBE_COACHMARK_AFTER_BANNER_MS,
    SUBSCRIBE_COACHMARK_DELAY_MS,
    useSubscribeDiscovery,
} from '@/composables/useSubscribeDiscovery';

const { t } = useI18n();
const {
    bannerIsVisible,
    bannerReady,
    bannerDismissedThisSession,
    isSubscribeCoachmarkHidden,
    hideSubscribeCoachmark,
} = useSubscribeDiscovery();

const showCoachmark = ref(false);
let coachmarkTimerId: number | undefined;

function clearCoachmarkTimer(): void {
    if (typeof coachmarkTimerId === 'number') {
        window.clearTimeout(coachmarkTimerId);
        coachmarkTimerId = undefined;
    }
}

function dismissCoachmark(): void {
    clearCoachmarkTimer();
    showCoachmark.value = false;
    hideSubscribeCoachmark();
}

function handleSubscribeClick(): void {
    if (showCoachmark.value) {
        dismissCoachmark();
    }
}

function scheduleCoachmark(delayMs: number): void {
    clearCoachmarkTimer();

    if (isSubscribeCoachmarkHidden() || bannerIsVisible.value) {
        return;
    }

    coachmarkTimerId = window.setTimeout(() => {
        if (isSubscribeCoachmarkHidden() || bannerIsVisible.value) {
            return;
        }

        showCoachmark.value = true;
    }, delayMs);
}

watch(
    [bannerReady, bannerIsVisible, bannerDismissedThisSession],
    ([ready, bannerVisible, dismissedThisSession]) => {
        if (!ready || isSubscribeCoachmarkHidden()) {
            clearCoachmarkTimer();
            showCoachmark.value = false;

            return;
        }

        if (bannerVisible) {
            clearCoachmarkTimer();
            showCoachmark.value = false;

            return;
        }

        scheduleCoachmark(
            dismissedThisSession
                ? SUBSCRIBE_COACHMARK_AFTER_BANNER_MS
                : SUBSCRIBE_COACHMARK_DELAY_MS,
        );
    },
    { immediate: true },
);

onUnmounted(() => {
    clearCoachmarkTimer();
});
</script>

<template>
    <Teleport to="body">
        <Button
            as-child
            class="subscribe-side-button fixed top-1/2 left-9 z-50 max-w-[calc(100dvh-2rem)] origin-top-left -translate-y-1/2 rotate-90 shadow-md"
            :class="{
                'subscribe-side-button--spotlight': showCoachmark,
            }"
        >
            <Link :href="subscribe().url" @click="handleSubscribeClick">{{
                t('subscription.subscribeToUpdates')
            }}</Link>
        </Button>

        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 translate-x-1"
            enter-to-class="opacity-100 translate-x-0"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 translate-x-0"
            leave-to-class="opacity-0 translate-x-1"
        >
            <div
                v-if="showCoachmark"
                class="subscribe-coachmark fixed top-1/2 left-[4.75rem] z-50 w-[min(16.5rem,calc(100vw-5.5rem))] -translate-y-1/2 rounded-lg border border-[hsl(48_85%_42%/0.45)] bg-[hsl(48_90%_95%/0.97)] p-3.5 shadow-[0_10px_28px_rgb(15_23_42/0.18)] backdrop-blur-sm"
                role="status"
            >
                <div
                    class="absolute top-1/2 -left-1.5 size-3 -translate-y-1/2 rotate-45 border-b border-l border-[hsl(48_85%_42%/0.45)] bg-[hsl(48_90%_95%)]"
                    aria-hidden="true"
                />

                <p class="text-sm leading-snug font-semibold text-foreground">
                    {{ t('subscription.coachmarkTitle') }}
                </p>
                <p class="mt-1 text-xs leading-relaxed text-foreground/70">
                    {{ t('subscription.coachmarkDescription') }}
                </p>

                <div class="mt-3 flex items-center justify-end">
                    <button
                        type="button"
                        class="inline-flex h-7 items-center rounded-md bg-[hsl(48_98%_52%/0.2)] px-2.5 text-xs font-semibold text-[hsl(42_80%_28%)] transition hover:bg-[hsl(48_98%_52%/0.3)] focus-visible:ring-2 focus-visible:ring-[hsl(48_98%_52%)] focus-visible:outline-none"
                        @click="dismissCoachmark"
                    >
                        {{ t('subscription.gotIt') }}
                    </button>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
