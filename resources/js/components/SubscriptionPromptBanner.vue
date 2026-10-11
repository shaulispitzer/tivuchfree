<script setup lang="ts">
import { X } from 'lucide-vue-next';
import { subscribe } from '@/routes';
import {
    SUBSCRIBE_BANNER_LONG_MS,
    SUBSCRIBE_BANNER_SHORT_MS,
    useSubscribeDiscovery,
} from '@/composables/useSubscribeDiscovery';

const { t } = useI18n();
const {
    bannerIsVisible,
    markBannerReady,
    dismissBanner,
    isSubscribeBannerHidden,
} = useSubscribeDiscovery();

onMounted(() => {
    markBannerReady(!isSubscribeBannerHidden());
});

function handleClose(): void {
    dismissBanner(SUBSCRIBE_BANNER_SHORT_MS);
}

function handleDontShowAgain(): void {
    dismissBanner(SUBSCRIBE_BANNER_LONG_MS);
}

function handleSubscribeClick(): void {
    dismissBanner(SUBSCRIBE_BANNER_SHORT_MS);
}
</script>

<template>
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0 -translate-y-1"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-1"
    >
        <div
            v-if="bannerIsVisible"
            class="relative mt-3 overflow-hidden rounded-lg border border-primary/15 bg-[linear-gradient(135deg,hsl(173_95%_26%/0.08),hsl(173_40%_96%)_55%,hsl(0_0%_100%))] px-4 py-3.5 sm:px-5"
            role="region"
            :aria-label="t('subscription.bannerTitle')"
        >
            <div
                class="pointer-events-none absolute inset-y-0 start-0 w-1 bg-primary"
                aria-hidden="true"
            />

            <div
                class="flex flex-col gap-3 ps-2 sm:flex-row sm:items-center sm:justify-between sm:gap-4"
            >
                <div class="min-w-0 space-y-1">
                    <p class="text-sm font-semibold text-foreground">
                        {{ t('subscription.bannerTitle') }}
                    </p>
                    <p class="text-sm leading-relaxed text-muted-foreground">
                        {{ t('subscription.bannerDescription') }}
                    </p>
                </div>

                <div
                    class="flex flex-wrap items-center gap-2 sm:shrink-0 sm:justify-end"
                >
                    <Button as-child size="sm" class="font-semibold">
                        <Link
                            :href="subscribe().url"
                            @click="handleSubscribeClick"
                        >
                            {{ t('subscription.subscribe') }}
                        </Link>
                    </Button>

                    <button
                        type="button"
                        class="inline-flex h-8 items-center rounded-md px-2.5 text-xs font-medium text-muted-foreground transition hover:bg-muted hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        @click="handleDontShowAgain"
                    >
                        {{ t('subscription.dontShowAgain') }}
                    </button>

                    <button
                        type="button"
                        class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition hover:bg-muted hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        :aria-label="t('common.close')"
                        @click="handleClose"
                    >
                        <X class="size-4" />
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>
