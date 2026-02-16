<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
// import { SidebarInset } from '@/components/ui/sidebar';

type Props = {
    variant?: 'header' | 'sidebar';
    class?: string;
};

const props = defineProps<Props>();

const page = usePage();
const isHomePage = computed(() => page.component === 'Home');
const className = computed(() => props.class);
const contentClasses = computed(() =>
    isHomePage.value
        ? 'mx-auto flex h-full w-full max-w-none flex-1 flex-col gap-0 rounded-none px-0'
        : 'mx-auto flex h-full w-full max-w-400 flex-1 flex-col gap-4 rounded-xl px-4',
);
</script>

<template>
    <!-- <SidebarInset v-if="props.variant === 'sidebar'" :class="className">
        <slot />
    </SidebarInset> -->
    <main :class="[contentClasses, className]">
        <slot />
    </main>
</template>
