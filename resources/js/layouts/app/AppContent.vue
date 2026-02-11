<script setup lang="ts">
import { computed } from 'vue';
// import { SidebarInset } from '@/components/ui/sidebar';

type Props = {
    variant?: 'header' | 'sidebar';
    class?: string;
};

const props = defineProps<Props>();
const className = computed(() => props.class);
</script>

<template>
    <!-- <SidebarInset v-if="props.variant === 'sidebar'" :class="className">
        <slot />
    </SidebarInset> -->
    <main
        class="mx-auto flex h-full w-full max-w-400 flex-1 flex-col gap-4 rounded-xl px-4"
        :class="className"
    >
        <slot />
    </main>
</template>
