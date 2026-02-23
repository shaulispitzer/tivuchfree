<script setup lang="ts">
import { Trash2 } from 'lucide-vue-next';
import type { PropType } from 'vue';
import { destroy } from '@/routes/admin/property-stats';
const { t } = useI18n();

type PropertyStatRow = {
    id: number;
    property_id: number;
    type: string | null;
    neighbourhoods: string[] | null;
    address: string | null;
    how_got_taken: string | null;
    price_advertised: string | number | null;
    price_taken_at: string | number | null;
    date_taken: string | null;
    date_advertised: string | null;
    created_at: string | null;
};

defineProps({
    stats: {
        type: Array as PropType<PropertyStatRow[]>,
        required: true,
    },
});

const deletingId = ref<number | null>(null);

function handleDelete(statId: number) {
    deletingId.value = statId;

    router.delete(destroy(statId).url, {
        preserveScroll: true,
        onFinish: () => (deletingId.value = null),
    });
}
</script>

<template>
    <Head :title="t('common.priceStats')" />

    <div class="space-y-6">
        <div>
            <h1 class="text-xl font-semibold tracking-tight">
                {{ t('common.priceStats') }}
            </h1>
        </div>

        <div
            v-if="stats.length === 0"
            class="rounded-lg border border-dashed border-input p-8 text-center text-sm text-muted-foreground"
        >
            {{ t('common.noResults') }}
        </div>

        <div
            v-else
            class="overflow-hidden rounded-lg border border-input shadow-sm"
        >
            <table class="w-full text-sm">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 text-start font-medium">
                            Property ID
                        </th>
                        <th class="px-4 py-3 text-start font-medium">
                            {{ t('common.type') }}
                        </th>
                        <th class="px-4 py-3 text-start font-medium">
                            {{ t('common.address') }}
                        </th>
                        <th class="px-4 py-3 text-start font-medium">
                            {{ t('common.neighbourhoods') }}
                        </th>
                        <th class="px-4 py-3 text-start font-medium">
                            {{ t('common.source') }}
                        </th>
                        <th class="px-4 py-3 text-start font-medium">
                            {{ t('common.advertised') }}
                        </th>
                        <th class="px-4 py-3 text-start font-medium">
                            {{ t('common.takenAt') }}
                        </th>
                        <th class="px-4 py-3 text-start font-medium">
                            {{ t('common.dateTaken') }}
                        </th>
                        <th class="px-4 py-3 text-start font-medium">
                            {{ t('common.dateAdvertised') }}
                        </th>
                        <th class="px-4 py-3 text-start font-medium">
                            {{ t('common.actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-input">
                    <tr
                        v-for="stat in stats"
                        :key="stat.id"
                        class="bg-background transition-colors hover:bg-muted/30"
                    >
                        <td class="px-4 py-3 font-medium">
                            {{ stat.property_id }}
                        </td>
                        <td class="px-4 py-3">
                            {{ stat.type ?? '-' }}
                        </td>
                        <td class="px-4 py-3">
                            {{ stat.address ?? '-' }}
                        </td>
                        <td class="px-4 py-3">
                            {{
                                stat.neighbourhoods?.length
                                    ? stat.neighbourhoods.join(', ')
                                    : '-'
                            }}
                        </td>
                        <td class="px-4 py-3">
                            {{ stat.how_got_taken ?? '-' }}
                        </td>
                        <td class="px-4 py-3">
                            {{
                                stat.price_advertised !== null
                                    ? `₪${Number(stat.price_advertised).toFixed(2)}`
                                    : '-'
                            }}
                        </td>
                        <td class="px-4 py-3">
                            {{
                                stat.price_taken_at !== null
                                    ? `₪${Number(stat.price_taken_at).toFixed(2)}`
                                    : '-'
                            }}
                        </td>
                        <td class="px-4 py-3">
                            {{
                                stat.date_taken
                                    ? new Date(
                                          stat.date_taken,
                                      ).toLocaleDateString()
                                    : '-'
                            }}
                        </td>
                        <td class="px-4 py-3">
                            {{
                                stat.date_advertised
                                    ? new Date(
                                          stat.date_advertised,
                                      ).toLocaleDateString()
                                    : '-'
                            }}
                        </td>
                        <td class="px-4 py-3">
                            <ConfirmableButton
                                variant="destructive"
                                size="sm"
                                :processing="deletingId === stat.id"
                                @confirm="handleDelete(stat.id)"
                                message="Are you sure you want to delete this property stat?"
                            >
                                <Trash2 class="size-4" />
                            </ConfirmableButton>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
