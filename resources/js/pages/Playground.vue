<template>
    <h1>Playground</h1>
    <Button @click="open = true">Click me</Button>
    <Button @click="showModal">show Modal</Button>
    <!-- <FormKit type="text" name="name" validation="required"> </FormKit> -->
    <DatePicker v-model="date" />
    <!-- show the date from the date picker, or if it is null, show "No date selected" -->
    <!-- why is it always no date selected? -->
    <p>Date: {{ date?.toDate(getLocalTimeZone())?.toLocaleDateString() }}</p>
    <IconAccessibility class="h-4" />
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
import { getLocalTimeZone } from '@internationalized/date';
import type { DateValue } from 'reka-ui';
import { sampleModaleOut } from '@/routes';
import IconAccessibility from '~icons/lucide/accessibility';

const open = ref(false);
const date = shallowRef<DateValue | undefined>(undefined);

function confirm() {
    console.log('confirm');
    open.value = false;
}

function cancel() {
    console.log('cancel');
    open.value = false;
}

function showModal() {
    router.get(sampleModaleOut());
}
</script>
