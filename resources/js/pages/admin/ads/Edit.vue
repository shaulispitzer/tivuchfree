<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import type { PropType } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { destroy, index, update } from '@/routes/admin/ads';

type Advertisement = {
    id: number;
    position: number;
    url: string;
    is_active: boolean;
    image_url: string;
};

const props = defineProps({
    advertisement: {
        type: Object as PropType<Advertisement>,
        required: true,
    },
    max_count: {
        type: Number,
        required: true,
    },
    taken_positions: {
        type: Array as PropType<number[]>,
        required: true,
    },
});

const { t } = useI18n();

const position = ref(String(props.advertisement.position));

const availablePositions = computed(() =>
    [1, 2, 3, 4].filter(
        (availablePosition) =>
            availablePosition === props.advertisement.position ||
            !props.taken_positions.includes(availablePosition),
    ),
);
</script>

<template>
    <Head :title="t('common.editAd')" />

    <div class="flex items-center justify-between">
        <h1 class="text-lg font-semibold">{{ t('common.editAd') }}</h1>
        <Button as-child variant="secondary">
            <Link :href="index()">{{ t('common.back') }}</Link>
        </Button>
    </div>

    <Form
        v-bind="update.form(advertisement.id)"
        class="mt-6 max-w-xl"
        enctype="multipart/form-data"
        v-slot="{ errors, processing }"
    >
        <div class="space-y-6">
            <div class="grid gap-2">
                <Label>{{ t('common.currentImage') }}</Label>
                <img
                    :src="advertisement.image_url"
                    :alt="t('common.adImageAlt')"
                    class="h-40 w-60 rounded-lg border border-input object-cover"
                />
            </div>

            <div class="grid gap-2">
                <Label for="image">{{ t('common.replaceAdImage') }}</Label>
                <input
                    id="image"
                    type="file"
                    name="image"
                    accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
                    class="text-sm file:mr-2 file:rounded-md file:border-0 file:bg-secondary file:px-4 file:py-2 file:text-sm file:font-medium file:text-secondary-foreground hover:file:bg-secondary/80"
                />
                <p class="text-xs text-muted-foreground">
                    {{ t('common.adImageHints') }}
                </p>
                <InputError :message="errors.image" />
            </div>

            <div class="grid gap-2">
                <Label for="url">{{ t('common.url') }}</Label>
                <Input
                    id="url"
                    name="url"
                    type="url"
                    required
                    :default-value="advertisement.url"
                />
                <InputError :message="errors.url" />
            </div>

            <div class="grid gap-2">
                <Label for="position">{{ t('common.position') }}</Label>
                <Select v-model="position">
                    <SelectTrigger id="position" class="w-full">
                        <SelectValue :placeholder="t('common.position')" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="availablePosition in availablePositions"
                            :key="availablePosition"
                            :value="String(availablePosition)"
                        >
                            {{ availablePosition }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <input type="hidden" name="position" :value="position" />
                <p class="text-xs text-muted-foreground">
                    {{ t('common.adPositionHint') }}
                </p>
                <InputError :message="errors.position" />
            </div>

            <div class="flex items-center gap-2">
                <input
                    id="is_active"
                    type="checkbox"
                    name="is_active"
                    value="1"
                    :checked="advertisement.is_active"
                    class="size-4 rounded border border-input"
                />
                <Label for="is_active">{{ t('common.active') }}</Label>
                <InputError :message="errors.is_active" />
            </div>

            <div class="flex items-center gap-4">
                <Button type="submit" :disabled="processing">
                    {{ t('common.save') }}
                </Button>
                <ConfirmableButton
                    @confirm="
                        router.delete(destroy(advertisement.id).url, {
                            preserveScroll: true,
                        })
                    "
                    confirmLabel="Delete"
                    message="Are you sure you want to delete this ad?"
                    variant="destructive"
                >
                    {{ t('common.delete') }}
                </ConfirmableButton>
            </div>
        </div>
    </Form>
</template>
