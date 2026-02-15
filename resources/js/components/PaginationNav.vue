<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { cn } from '@/lib/utils';

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

const props = defineProps<{
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
}>();

const cleanedLinks = computed(() =>
    props.links.map((link) => ({
        ...link,
        text: link.label
            .replace(/<[^>]*>/g, '')
            .replace(/&laquo;/g, '«')
            .replace(/&raquo;/g, '»')
            .replace(/&hellip;/g, '...')
            .replace(/&amp;/g, '&')
            .trim(),
    })),
);
</script>

<template>
    <div class="mt-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-muted-foreground">
            Showing
            <span class="font-medium text-foreground">{{ from ?? 0 }}</span>
            to
            <span class="font-medium text-foreground">{{ to ?? 0 }}</span>
            of
            <span class="font-medium text-foreground">{{ total }}</span>
            results
        </p>

        <nav
            v-if="cleanedLinks.length > 3"
            class="flex flex-wrap items-center gap-2"
            aria-label="Pagination"
        >
            <template v-for="(link, index) in cleanedLinks" :key="`${index}-${link.text}`">
                <span
                    v-if="link.url === null"
                    class="inline-flex h-9 items-center justify-center rounded-md border px-3 text-sm text-muted-foreground"
                    :class="{ 'font-semibold text-foreground': link.active }"
                >
                    {{ link.text }}
                </span>

                <Link
                    v-else
                    :href="link.url"
                    preserve-scroll
                    preserve-state
                    class="inline-flex h-9 items-center justify-center rounded-md border px-3 text-sm transition-colors hover:bg-accent"
                    :class="
                        cn(
                            link.active &&
                                'border-primary bg-primary text-primary-foreground hover:bg-primary/90',
                        )
                    "
                >
                    {{ link.text }}
                </Link>
            </template>
        </nav>
    </div>
</template>
