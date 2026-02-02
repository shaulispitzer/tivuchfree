<script setup lang="ts">
import AppContent from './AppContent.vue';
import AppHeader from './AppHeader.vue';
</script>

<template>
    <AppHeader />
    <AppContent>
        <slot />
    </AppContent>
</template>
