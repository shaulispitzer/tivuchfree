import { ref } from 'vue';

const BANNER_STORAGE_KEY = 'tivuchfree:subscribe-banner-hidden-until';
const COACHMARK_STORAGE_KEY = 'tivuchfree:subscribe-coachmark-hidden-until';

/** Keep in sync with App\Support\TimedDismissal::TWO_WEEKS_MS */
export const SUBSCRIBE_BANNER_SHORT_MS = 14 * 24 * 60 * 60 * 1000;

/** Keep in sync with App\Support\TimedDismissal::TWO_YEARS_MS */
export const SUBSCRIBE_BANNER_LONG_MS = 2 * 365 * 24 * 60 * 60 * 1000;

export const SUBSCRIBE_COACHMARK_DISMISS_MS = SUBSCRIBE_BANNER_LONG_MS;

/** Delay after mount when the banner is already hidden (lets the T&C modal settle). */
export const SUBSCRIBE_COACHMARK_DELAY_MS = 6500;

/** Delay after the user dismisses the banner in this session. */
export const SUBSCRIBE_COACHMARK_AFTER_BANNER_MS = 1400;

const bannerIsVisible = ref(false);
const bannerReady = ref(false);
const bannerDismissedThisSession = ref(false);

function readHiddenUntil(key: string): number | null {
    try {
        const stored = window.localStorage.getItem(key);

        if (!stored) {
            return null;
        }

        const until = Number.parseInt(stored, 10);

        return Number.isNaN(until) ? null : until;
    } catch {
        return null;
    }
}

function isHidden(key: string): boolean {
    const until = readHiddenUntil(key);

    if (until === null) {
        return false;
    }

    return Date.now() < until;
}

function hideFor(key: string, durationMs: number): void {
    try {
        window.localStorage.setItem(
            key,
            (Date.now() + durationMs).toString(),
        );
    } catch {
        // Intentionally no-op if storage is unavailable.
    }
}

export function isSubscribeBannerHidden(): boolean {
    return isHidden(BANNER_STORAGE_KEY);
}

export function isSubscribeCoachmarkHidden(): boolean {
    return isHidden(COACHMARK_STORAGE_KEY);
}

export function hideSubscribeBannerFor(durationMs: number): void {
    hideFor(BANNER_STORAGE_KEY, durationMs);
}

export function hideSubscribeCoachmark(): void {
    hideFor(COACHMARK_STORAGE_KEY, SUBSCRIBE_COACHMARK_DISMISS_MS);
}

/**
 * Shared discovery state for the listing subscribe banner and side-button coachmark.
 */
export function useSubscribeDiscovery() {
    function markBannerReady(visible: boolean): void {
        bannerIsVisible.value = visible;
        bannerReady.value = true;
    }

    function dismissBanner(durationMs: number): void {
        hideSubscribeBannerFor(durationMs);
        bannerIsVisible.value = false;
        bannerDismissedThisSession.value = true;
    }

    return {
        bannerIsVisible,
        bannerReady,
        bannerDismissedThisSession,
        markBannerReady,
        dismissBanner,
        isSubscribeBannerHidden,
        isSubscribeCoachmarkHidden,
        hideSubscribeCoachmark,
    };
}
