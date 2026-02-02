<script setup lang="ts">
import AppShellLayout from '@/layouts/app/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShellLayout :breadcrumbs="breadcrumbs">
        <slot />
    </AppShellLayout>
</template>
