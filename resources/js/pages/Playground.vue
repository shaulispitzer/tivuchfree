<template>
    <h1>Playground</h1>
    <Button @click="open = true">Click me</Button>
    <Button @click="showModal">show Modal</Button>
    <FormKit type="text" name="name" validation="required">
        <template #label>
            <span
                >{{ t('auth.login') }}<span class="text-red-500"> *</span></span
            >
        </template>
    </FormKit>
    <IconAccessibility class="h-4"/>
    <Modal
        :open="open"
        title="Modal Title"
        @confirm="confirm"
        @close="cancel"
        :as-page="false"
    >
        <p>Modal Content</p>
    </Modal>
</template>

<script setup lang="ts">
import IconAccessibility from '~icons/carbon/accessibility';
const { t } = useI18n();

const open = ref(false);

function confirm() {
    console.log('confirm');
    open.value = false;
}

function cancel() {
    console.log('cancel');
    open.value = false;
}
function showModal() {
    router.get('sample-modale-out');
}
</script>
