<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import type { PropType } from 'vue';

import { index, store } from '@/routes/admin/ads';

const props = defineProps({
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

const availablePositions = computed(() =>
    [1, 2, 3, 4].filter(
        (availablePosition) =>
            !props.taken_positions.includes(availablePosition),
    ),
);

const position = ref(String(availablePositions.value[0] ?? ''));
</script>

<template>
    <Head :title="t('common.createAd')" />

    <div class="flex items-center justify-between">
        <h1 class="text-lg font-semibold">{{ t('common.createAd') }}</h1>
        <Button as-child variant="secondary">
            <Link :href="index()">{{ t('common.back') }}</Link>
        </Button>
    </div>

    <Form
        v-bind="store.form()"
        class="mt-6 max-w-xl"
        enctype="multipart/form-data"
        v-slot="{ errors, processing }"
    >
        <div class="space-y-6">
            <div class="grid gap-2">
                <Label for="image" required-star>{{
                    t('common.adImage')
                }}</Label>
                <input
                    id="image"
                    type="file"
                    name="image"
                    accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
                    required
                    class="text-sm file:mr-2 file:rounded-md file:border-0 file:bg-secondary file:px-4 file:py-2 file:text-sm file:font-medium file:text-secondary-foreground hover:file:bg-secondary/80"
                />
                <p class="text-xs text-muted-foreground">
                    {{ t('common.adImageHints') }}
                </p>
                <InputError :message="errors.image" />
            </div>

            <div class="grid gap-2">
                <Label for="url" required-star>{{ t('common.url') }}</Label>
                <Input
                    id="url"
                    name="url"
                    type="url"
                    required
                    placeholder="https://example.com"
                />
                <InputError :message="errors.url" />
            </div>

            <div class="grid gap-2">
                <Label for="position" required-star>{{
                    t('common.position')
                }}</Label>
                <Select v-model="position" required>
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
                    class="size-4 rounded border border-input"
                />
                <Label for="is_active">{{ t('common.active') }}</Label>
                <InputError :message="errors.is_active" />
            </div>

            <div class="flex items-center gap-4">
                <Button type="submit" :disabled="processing">
                    {{ t('common.save') }}
                </Button>
            </div>
        </div>
    </Form>
</template>
