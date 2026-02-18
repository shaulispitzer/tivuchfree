<script setup lang="ts">
import { Modal as InertiaModal } from '../../../../vendor/emargareten/inertia-modal';
import AppContent from './AppContent.vue';
import AppFooter from './AppFooter.vue';
import AppHeader from './AppHeader.vue';
</script>

<template>
    <div class="flex min-h-screen flex-col">
        <!-- make the header sticky -->
        <AppHeader class="sticky top-0 z-50" />

        <AppContent>
            <slot />
        </AppContent>

        <AppFooter />
        <InertiaModal />
    </div>
</template>
