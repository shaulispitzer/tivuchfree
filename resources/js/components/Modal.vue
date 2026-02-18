<template>
    <TransitionRoot appear :show="asPage ? showPage : open" as="template">
        <Dialog as="div" class="relative z-999999" @close="handleDialogClose">
            <TransitionChild
                @after-leave="
                    asPage
                        ? redirect({
                              preserveState: true,
                              preserveScroll: true,
                          })
                        : null
                "
                as="template"
                enter="duration-300 ease-out"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="duration-200 ease-in"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div class="bg-brand-600/25 fixed inset-0 backdrop-blur-sm" />
            </TransitionChild>

            <div
                :dir="locale == 'he' ? 'rtl' : 'ltr'"
                :class="[outerPadding, outerHeight]"
                class="fixed inset-0 overflow-y-auto"
            >
                <div
                    class="flex min-h-full items-start justify-center text-center"
                    :class="[semiouterPadding]"
                >
                    <TransitionChild
                        as="template"
                        enter="duration-300 ease-out"
                        enter-from="opacity-0 scale-75 -translate-y-10"
                        enter-to="opacity-100 scale-100"
                        leave="duration-200 ease-in"
                        leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-75 -translate-y-10"
                    >
                        <DialogPanel
                            :class="[
                                width,
                                padding,
                                rounded,
                                height,
                                margin,
                                position,
                            ]"
                            class="shadow-soft-lg w-full overflow-hidden border border-gray-200 bg-white text-start align-middle transition-all"
                        >
                            <button
                                v-if="closable"
                                class="absolute end-2 top-2 z-50 float-end h-8 w-8 cursor-pointer rounded-full p-1.5 text-primary"
                                @click="closeModal"
                            >
                                x
                            </button>
                            <div
                                v-if="title"
                                class="space-s-3 mb-4 flex items-center pt-2"
                            >
                                <div v-if="icon">
                                    <Icon
                                        :name="icon"
                                        class="h-5 w-5 text-primary"
                                    />
                                </div>
                                <DialogTitle
                                    as="h3"
                                    class="text-brand-900 flex-1 text-lg leading-6 font-semibold"
                                >
                                    {{ title }}
                                </DialogTitle>
                                <slot name="title" />
                            </div>
                            <div :class="{ 'mt-2': !!title }">
                                <slot />
                            </div>

                            <div
                                v-if="actions"
                                class="space-s-3 -m-2 mt-6 flex justify-end"
                            >
                                <Button
                                    v-if="cancelable"
                                    color="secondary"
                                    size="lg"
                                    @click="closeModal"
                                >
                                    {{ cancelLabel || t('common.cancel') }}
                                </Button>
                                <Button
                                    :processing="processing"
                                    v-if="confirmable"
                                    color="brand"
                                    size="lg"
                                    @click="emit('confirm')"
                                >
                                    {{ confirmLabel || t('common.confirm') }}
                                </Button>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script setup lang="ts">
import {
    TransitionRoot,
    TransitionChild,
    Dialog,
    DialogPanel,
    DialogTitle,
} from '@headlessui/vue';
import { useModal } from '../../../vendor/emargareten/inertia-modal';
import Icon from '~icons/lucide/home';

const { t, locale } = useI18n();

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    asPage: {
        type: Boolean,
        default: false,
    },
    title: String,
    icon: {
        type: String,
        default: null,
    },
    width: {
        type: String,
        default: 'max-w-md',
    },
    padding: {
        type: String,
        default: 'p-6',
    },
    actions: {
        type: Boolean,
        default: true,
    },
    cancelable: {
        type: Boolean,
        default: true,
    },
    cancelLabel: {
        type: String,
    },
    confirmable: {
        type: Boolean,
        default: true,
    },
    confirmLabel: {
        type: String,
    },
    style: {
        type: String,
    },
    outerPadding: {
        type: String,
        default: 'p6',
    },
    rounded: {
        type: String,
        default: 'rounded-2xl',
    },
    outerHeight: {
        type: String,
        default: '',
    },
    semiouterPadding: {
        type: String,
        default: 'py-[10vh]',
    },
    height: {
        type: String,
        default: '',
    },
    margin: {
        type: String,
        default: 'mt-[10%]',
    },
    position: {
        type: String,
        default: 'relative',
    },
    closable: {
        type: Boolean,
        default: true,
    },
    closeOnBackdrop: {
        type: Boolean,
        default: true,
    },
    processing: {
        type: Boolean,
        default: false,
    },
});

const { show: showPage, close: closePage, redirect } = useModal();

const emit = defineEmits(['close', 'confirm']);

function closeModal() {
    if (props.asPage) {
        closePage();
        // temp fix to prevent page freeze
        // return redirect()
    }
    emit('close');
}

function handleDialogClose() {
    if (!props.closeOnBackdrop) {
        return;
    }

    closeModal();
}
</script>
