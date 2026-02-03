<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref, useTemplateRef } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import Modal from '@/components/Modal.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const passwordInput = useTemplateRef('passwordInput');
const isModalOpen = ref(false);
</script>

<template>
    <div class="space-y-6">
        <Heading
            variant="small"
            title="Delete account"
            description="Delete your account and all of its resources"
        />
        <div
            class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10"
        >
            <div class="relative space-y-0.5 text-red-600 dark:text-red-100">
                <p class="font-medium">Warning</p>
                <p class="text-sm">
                    Please proceed with caution, this cannot be undone.
                </p>
            </div>
            <Button
                variant="destructive"
                data-test="delete-user-button"
                @click="isModalOpen = true"
            >
                Delete account
            </Button>
            <Form
                v-bind="ProfileController.destroy.form()"
                reset-on-success
                @error="() => passwordInput?.$el?.focus()"
                :options="{
                    preserveScroll: true,
                }"
                class="space-y-6"
                v-slot="{ errors, processing, reset, clearErrors }"
            >
                <Modal
                    :open="isModalOpen"
                    title="Are you sure you want to delete your account?"
                    :actions="false"
                    @close="
                        () => {
                            isModalOpen = false;
                            clearErrors();
                            reset();
                        }
                    "
                >
                    <p class="text-sm text-neutral-600 dark:text-neutral-200">
                        Once your account is deleted, all of its resources and
                        data will also be permanently deleted. Please enter
                        your password to confirm you would like to permanently
                        delete your account.
                    </p>

                    <div class="mt-6 grid gap-2">
                        <Label for="password" class="sr-only">Password</Label>
                        <Input
                            id="password"
                            type="password"
                            name="password"
                            ref="passwordInput"
                            placeholder="Password"
                        />
                        <InputError :message="errors.password" />
                    </div>

                    <div class="mt-6 flex flex-col gap-2 sm:flex-row sm:justify-end">
                        <Button
                            variant="secondary"
                            @click="
                                () => {
                                    isModalOpen = false;
                                    clearErrors();
                                    reset();
                                }
                            "
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            variant="destructive"
                            :disabled="processing"
                            data-test="confirm-delete-user-button"
                        >
                            Delete account
                        </Button>
                    </div>
                </Modal>
            </Form>
        </div>
    </div>
</template>
