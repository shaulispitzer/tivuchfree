<script setup lang="ts">
import type { PropType } from 'vue';

type Ad = {
    id: number;
    url: string;
    image_url: string;
    position: number;
};

defineProps({
    ad: {
        type: Object as PropType<Ad>,
        required: true,
    },
});

const { t } = useI18n();
</script>

<template>
    <a
        :href="ad.url"
        target="_blank"
        rel="noopener noreferrer"
        class="block h-full cursor-pointer focus-visible:outline-none"
    >
        <article
            class="relative h-full min-h-80 overflow-hidden rounded-xl border border-input bg-muted shadow-sm transition hover:-translate-y-0.5 hover:scale-101 hover:shadow-lg focus-visible:ring-2 focus-visible:ring-primary/40"
        >
            <img
                :src="ad.image_url"
                :alt="t('common.adImageAlt')"
                class="absolute inset-0 size-full object-contain object-center"
                loading="lazy"
            />
            <span
                class="absolute start-2 top-2 z-10 rounded bg-background/90 px-2 py-0.5 text-xs font-medium text-muted-foreground"
            >
                {{ t('common.adLabel') }}
            </span>
        </article>
    </a>
</template>
