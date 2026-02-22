<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CircleCheck, Pencil, RefreshCw, Trash2 } from 'lucide-vue-next';
import type { PropType } from 'vue';
import { useI18n } from 'vue-i18n';
import ConfirmableButton from '@/components/ConfirmableButton.vue';
import Modal from '@/components/Modal.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { destroy, markAsTaken, repost } from '@/routes/my-properties';
import { edit } from '@/routes/properties';
import PepiconsPopDotsY from '~icons/pepicons-pop/dots-y';

defineProps({
    properties: {
        type: Array as PropType<App.Data.PropertyData[]>,
        required: true,
    },
});
const { t } = useI18n();

const deletingId = ref<number | null>(null);
const markAsTakenModalPropertyId = ref<number | null>(null);
const feedbackSource = ref<string | null>(null);
const feedbackPrice = ref('');
const markingAsTaken = ref(false);

const feedbackOptions = [
    { value: 'tivuchfree', label: 'Tivuch Free' },
    { value: 'other_non_paid', label: 'Other (non-paid)' },
    { value: 'agent', label: 'Tivuch (agent)' },
    { value: 'other', label: 'Other' },
];

function openMarkAsTakenModal(propertyId: number) {
    markAsTakenModalPropertyId.value = propertyId;
    feedbackSource.value = null;
    feedbackPrice.value = '';
}

function handleMarkAsTaken() {
    if (!markAsTakenModalPropertyId.value) return;

    markingAsTaken.value = true;
    router.patch(
        markAsTaken(markAsTakenModalPropertyId.value).url,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                markingAsTaken.value = false;
                markAsTakenModalPropertyId.value = null;
            },
        },
    );
}

function handleRepost(propertyId: number) {
    router.patch(
        repost(propertyId).url,
        {},
        {
            preserveScroll: true,
        },
    );
}

function handleDelete(propertyId: number) {
    deletingId.value = propertyId;
    router.delete(destroy(propertyId).url, {
        preserveScroll: true,
        onFinish: () => (deletingId.value = null),
    });
}
</script>

<template>
    <Head :title="t('common.myProperties')" />

    <div class="space-y-6">
        <div>
            <h1 class="text-xl font-semibold tracking-tight">
                {{ t('common.myProperties') }}
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
                {{ t('common.myPropertiesDescription') }}
            </p>
        </div>

        <div
            v-if="properties.length === 0"
            class="rounded-lg border border-dashed border-input p-8 text-center text-sm text-muted-foreground"
        >
            {{ t('common.noPropertiesPosted') }}
        </div>

        <div
            v-else
            class="overflow-hidden rounded-lg border border-input shadow-sm"
        >
            <table class="w-full text-sm">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 text-start font-medium">
                            {{ t('common.street') }}
                        </th>
                        <th class="px-4 py-3 text-start font-medium">
                            {{ t('common.buildingNumber') }}
                        </th>
                        <th class="px-4 py-3 text-start font-medium">
                            {{ t('common.floor') }}
                        </th>
                        <th class="px-4 py-3 text-start font-medium">
                            {{ t('common.bedrooms') }}
                        </th>
                        <th class="px-4 py-3 text-start font-medium">
                            {{ t('common.type') }}
                        </th>
                        <th class="px-4 py-3 text-start font-medium">
                            {{ t('common.status') }}
                        </th>
                        <th class="px-4 py-3 text-end font-medium">
                            {{ t('common.actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-input">
                    <tr
                        v-for="property in properties"
                        :key="property.id"
                        class="bg-background transition-colors hover:bg-muted/30"
                    >
                        <td class="px-4 py-3">{{ property.street }}</td>
                        <td class="px-4 py-3">
                            {{ property.building_number ?? '-' }}
                        </td>
                        <td class="px-4 py-3">{{ property.floor }}</td>
                        <td class="px-4 py-3">{{ property.bedrooms }}</td>
                        <td class="px-4 py-3">
                            {{ t(`propertyLeaseType.${property.type ?? '-'}`) }}
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="
                                    property.taken
                                        ? 'bg-red-100 text-red-700'
                                        : 'bg-green-100 text-green-700'
                                "
                            >
                                {{
                                    property.taken
                                        ? t('common.taken')
                                        : t('common.available')
                                }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            class="size-8"
                                        >
                                            <PepiconsPopDotsY class="size-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem as-child>
                                            <Link
                                                class="cursor-pointer"
                                                :href="edit(property.id)"
                                            >
                                                <Pencil class="mr-2 size-4" />
                                                {{ t('common.edit') }}
                                            </Link>
                                        </DropdownMenuItem>

                                        <DropdownMenuSeparator />

                                        <DropdownMenuItem
                                            v-if="property.taken"
                                            class="cursor-pointer"
                                            @click="handleRepost(property.id)"
                                        >
                                            <RefreshCw class="mr-2 size-4" />
                                            {{ t('common.repost') }}
                                        </DropdownMenuItem>

                                        <DropdownMenuItem
                                            v-else
                                            class="cursor-pointer"
                                            @click="
                                                openMarkAsTakenModal(
                                                    property.id,
                                                )
                                            "
                                        >
                                            <CircleCheck class="mr-2 size-4" />
                                            {{ t('common.markAsTaken') }}
                                        </DropdownMenuItem>

                                        <DropdownMenuSeparator />

                                        <TooltipProvider v-if="!property.taken">
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <div>
                                                        <DropdownMenuItem
                                                            variant="destructive"
                                                            disabled
                                                            class="text-red-400"
                                                        >
                                                            <Trash2
                                                                class="mr-2 size-4"
                                                            />
                                                            {{
                                                                t(
                                                                    'common.delete',
                                                                )
                                                            }}
                                                        </DropdownMenuItem>
                                                    </div>
                                                </TooltipTrigger>
                                                <TooltipContent side="left">
                                                    {{
                                                        t(
                                                            'common.markAsTakenBeforeDelete',
                                                        )
                                                    }}
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>

                                        <DropdownMenuItem
                                            v-else
                                            variant="destructive"
                                            as-child
                                        >
                                            <ConfirmableButton
                                                variant="ghost"
                                                size="sm"
                                                class="w-full justify-start px-2 py-1.5 text-red-500"
                                                :processing="
                                                    deletingId === property.id
                                                "
                                                @confirm="
                                                    handleDelete(property.id)
                                                "
                                            >
                                                <Trash2 class="mr-2 size-4" />
                                                {{ t('common.delete') }}
                                            </ConfirmableButton>
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <Modal
        :open="markAsTakenModalPropertyId !== null"
        :title="t('common.howWasListingTaken')"
        :confirm-label="t('common.markAsTaken')"
        :processing="markingAsTaken"
        @close="markAsTakenModalPropertyId = null"
        @confirm="handleMarkAsTaken"
    >
        <div class="space-y-4">
            <div class="space-y-2">
                <Label>{{ t('common.howWasListingTakenSource') }}</Label>
                <div class="flex flex-wrap gap-2">
                    <Button
                        v-for="option in feedbackOptions"
                        :key="option.value"
                        size="sm"
                        :variant="
                            feedbackSource === option.value
                                ? 'default'
                                : 'outline'
                        "
                        @click="
                            feedbackSource =
                                feedbackSource === option.value
                                    ? null
                                    : option.value
                        "
                    >
                        {{ option.label }}
                    </Button>
                </div>
            </div>

            <div class="space-y-2">
                <Label for="feedback-price">{{ t('common.finalPrice') }}</Label>
                <Input
                    id="feedback-price"
                    v-model="feedbackPrice"
                    type="number"
                    :placeholder="t('common.optional')"
                />
            </div>
        </div>
    </Modal>
</template>
