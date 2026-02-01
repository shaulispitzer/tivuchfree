<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { SidebarProvider } from '@/components/ui/sidebar';
import type { AppShellVariant } from '@/types';

type Props = {
    variant?: AppShellVariant;
};

defineProps<Props>();

const page = usePage();
const isOpen = page.props.sidebarOpen;
const locale = computed(() => page.props.locale ?? 'en');
</script>

<template>
    <Head
        :html-attributes="{
            lang: locale,
            dir: locale === 'he' ? 'rtl' : 'ltr',
        }"
    />
    <div v-if="variant === 'header'" class="flex min-h-screen w-full flex-col">
        <slot />
    </div>
    <SidebarProvider v-else :default-open="isOpen">
        <slot />
    </SidebarProvider>
</template>
