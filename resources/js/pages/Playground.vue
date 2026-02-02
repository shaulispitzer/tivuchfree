<template>
    <h1>Playground</h1>
    <Button>Click me</Button>
    <FormKit type="text" name="name" :label="t('auth.login')" />
</template>

<script setup lang="ts">
const { t } = useI18n();
</script>
