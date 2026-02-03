<script setup lang="ts">
import { Modal as InertiaModal } from '../../../../vendor/emargareten/inertia-modal';
import AppContent from './AppContent.vue';
import AppHeader from './AppHeader.vue';
</script>

<template>
    <AppHeader />
    <AppContent>
        <slot />
    </AppContent>
    <InertiaModal />
</template>
