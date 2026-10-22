<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import type { PropType } from 'vue';
import { Button } from '@/components/ui/button';
import { create, destroy, edit } from '@/routes/admin/ads';

type AdvertisementRow = {
    id: number;
    position: number;
    url: string;
    is_active: boolean;
    image_url: string;
};

defineProps({
    advertisements: {
        type: Array as PropType<AdvertisementRow[]>,
        required: true,
    },
    can_create: {
        type: Boolean,
        required: true,
    },
    max_count: {
        type: Number,
        required: true,
    },
});

const { t } = useI18n();
</script>

<template>
    <Head :title="`${t('common.admin')} - ${t('common.ads')}`" />

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-lg font-semibold">{{ t('common.ads') }}</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                {{ t('common.adsAdminDescription', { max: max_count }) }}
            </p>
        </div>
        <Button v-if="can_create" as-child>
            <Link :href="create()">{{ t('common.addAd') }}</Link>
        </Button>
    </div>

    <div
        v-if="advertisements.length === 0"
        class="mt-6 text-sm text-muted-foreground"
    >
        {{ t('common.noAdsYet') }}
    </div>

    <div v-else class="mt-6 overflow-x-auto rounded-lg border border-input">
        <table class="w-full text-sm">
            <thead class="bg-muted/40 text-left">
                <tr>
                    <th class="px-4 py-3 font-medium">
                        {{ t('common.preview') }}
                    </th>
                    <th class="px-4 py-3 font-medium">
                        {{ t('common.position') }}
                    </th>
                    <th class="px-4 py-3 font-medium">{{ t('common.url') }}</th>
                    <th class="px-4 py-3 font-medium">
                        {{ t('common.status') }}
                    </th>
                    <th class="px-4 py-3 font-medium">
                        {{ t('common.actions') }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="advertisement in advertisements"
                    :key="advertisement.id"
                    class="border-t border-input"
                >
                    <td class="px-4 py-3">
                        <img
                            :src="advertisement.image_url"
                            :alt="t('common.adImageAlt')"
                            class="h-16 w-24 rounded object-cover"
                        />
                    </td>
                    <td class="px-4 py-3">{{ advertisement.position }}</td>
                    <td class="max-w-xs truncate px-4 py-3">
                        <a
                            :href="advertisement.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-primary underline-offset-2 hover:underline"
                        >
                            {{ advertisement.url }}
                        </a>
                    </td>
                    <td class="px-4 py-3">
                        {{
                            advertisement.is_active
                                ? t('common.active')
                                : t('common.inactive')
                        }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <Button as-child variant="secondary" size="sm">
                                <Link :href="edit(advertisement.id)">
                                    {{ t('common.edit') }}
                                </Link>
                            </Button>

                            <ConfirmableButton
                                @confirm="
                                    router.delete(
                                        destroy(advertisement.id).url,
                                        {
                                            preserveScroll: true,
                                        },
                                    )
                                "
                                confirmLabel="Delete"
                                message="Are you sure you want to delete this ad?"
                                variant="destructive"
                                size="sm"
                            >
                                {{ t('common.delete') }}
                            </ConfirmableButton>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
