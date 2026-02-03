<template>
    <Modal :open="open" title="Modal Title" @confirm="confirm" :as-page="true">
        <p>Modal Content for the sample</p>
    </Modal>
</template>

<script setup lang="ts">
import { useModal } from '../../../vendor/emargareten/inertia-modal';

const { close: closePage } = useModal();
const open = ref(true);

function confirm() {
    console.log('confirm');
    closePage();
}
</script>
