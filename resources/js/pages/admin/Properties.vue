<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CircleCheck, Pencil, RefreshCw, Trash2 } from 'lucide-vue-next';
import type { PropType } from 'vue';

import { destroy, edit } from '@/routes/admin/properties';
import { markAsTaken, repost } from '@/routes/my-properties';
import PepiconsPopDotsY from '~icons/pepicons-pop/dots-y';

type PropertyRow = {
    id: number;
    street: string;
    building_number: number;
    floor: number;
    bedrooms: number;
    type: string;
    taken: boolean;
    reported_taken_at: string | null;
    user: {
        name: string;
        email: string;
    };
};

const props = defineProps({
    properties: {
        type: Array as PropType<PropertyRow[]>,
        required: true,
    },
});

const { t } = useI18n();

const deletingId = ref<number | null>(null);
const deleteModalPropertyId = ref<number | null>(null);
const markAsTakenModalPropertyId = ref<number | null>(null);
const feedbackSource = ref<string | null>(null);
const feedbackPrice = ref('');
const markingAsTaken = ref(false);
const markAsTakenThenDelete = ref(false);

const feedbackOptions = [
    { value: 'tivuchfree', label: 'Tivuch Free' },
    { value: 'other_non_paid', label: 'Other (non-paid)' },
    { value: 'agent', label: 'Tivuch (agent)' },
    { value: 'other', label: 'Other' },
];

function openMarkAsTakenModal(propertyId: number) {
    markAsTakenThenDelete.value = false;
    markAsTakenModalPropertyId.value = propertyId;
    feedbackSource.value = null;
    feedbackPrice.value = '';
}

function openDeleteModal(property: PropertyRow) {
    if (!property.taken) {
        markAsTakenThenDelete.value = true;
        markAsTakenModalPropertyId.value = property.id;
        feedbackSource.value = null;
        feedbackPrice.value = '';
    } else {
        deleteModalPropertyId.value = property.id;
    }
}

function handleMarkAsTaken() {
    if (!markAsTakenModalPropertyId.value) return;

    const priceString =
        typeof feedbackPrice.value === 'string'
            ? feedbackPrice.value
            : String(feedbackPrice.value ?? '');
    const priceTrimmed = priceString.trim();
    const parsedPrice =
        priceTrimmed !== '' && Number.isFinite(Number(priceTrimmed))
            ? Number(priceTrimmed)
            : null;

    const propertyId = markAsTakenModalPropertyId.value;
    const thenDelete = markAsTakenThenDelete.value;

    markingAsTaken.value = true;
    router.patch(
        markAsTaken(propertyId).url,
        {
            how_got_taken: feedbackSource.value,
            price_taken_at: parsedPrice,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                markAsTakenModalPropertyId.value = null;
                markAsTakenThenDelete.value = false;
                if (thenDelete) {
                    deletingId.value = propertyId;
                    router.delete(destroy(propertyId).url, {
                        preserveScroll: true,
                        onSuccess: () => (deleteModalPropertyId.value = null),
                        onFinish: () => (deletingId.value = null),
                    });
                }
            },
            onFinish: () => {
                markingAsTaken.value = false;
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

function handleConfirmDelete() {
    if (!deleteModalPropertyId.value) return;

    const propertyId = deleteModalPropertyId.value;

    deletingId.value = propertyId;
    router.delete(destroy(propertyId).url, {
        preserveScroll: true,
        onSuccess: () => (deleteModalPropertyId.value = null),
        onFinish: () => (deletingId.value = null),
    });
}
</script>

<template>
    <Head title="Admin - Properties" />

    <div class="space-y-6">
        <div>
            <h1 class="text-xl font-semibold tracking-tight">Properties</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Manage all properties. Only admins can access this page.
            </p>
        </div>

        <div
            v-if="properties.length === 0"
            class="rounded-lg border border-dashed border-input p-8 text-center text-sm text-muted-foreground"
        >
            No properties have been listed yet.
        </div>

        <div
            v-else
            class="overflow-hidden rounded-lg border border-input shadow-sm"
        >
            <table class="w-full text-sm">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Street</th>
                        <th class="px-4 py-3 text-left font-medium">
                            Building
                        </th>
                        <th class="px-4 py-3 text-left font-medium">Floor</th>
                        <th class="px-4 py-3 text-left font-medium">
                            Bedrooms
                        </th>
                        <th class="px-4 py-3 text-left font-medium">Type</th>
                        <th class="px-4 py-3 text-left font-medium">Status</th>
                        <th class="px-4 py-3 text-left font-medium">Owner</th>
                        <th class="px-4 py-3 text-right font-medium">
                            Actions
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
                            {{ property.building_number }}
                        </td>
                        <td class="px-4 py-3">{{ property.floor }}</td>
                        <td class="px-4 py-3">{{ property.bedrooms }}</td>
                        <td class="px-4 py-3">{{ property.type }}</td>
                        <td class="px-4 py-3">
                            <div
                                class="flex w-fit flex-wrap items-center gap-1.5"
                            >
                                <span
                                    class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-xs font-medium"
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
                                <span
                                    v-if="
                                        property.reported_taken_at &&
                                        !property.taken
                                    "
                                    class="inline-flex shrink-0 items-center rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-700"
                                >
                                    {{ t('common.reportedTaken') }}
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-medium">{{
                                property.user?.name
                            }}</span>
                            <span
                                v-if="property.user?.email"
                                class="block text-xs text-muted-foreground"
                            >
                                {{ property.user.email }}
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
                                            class="cursor-pointer text-red-500"
                                            @click="openDeleteModal(property)"
                                        >
                                            <Trash2 class="mr-2 size-4" />
                                            {{ t('common.delete') }}
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
        @close="
            markAsTakenModalPropertyId = null;
            markAsTakenThenDelete = false;
        "
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
                <Label for="admin-feedback-price">{{
                    t('common.finalPrice')
                }}</Label>
                <Input
                    id="admin-feedback-price"
                    v-model="feedbackPrice"
                    type="number"
                    :placeholder="t('common.optional')"
                />
            </div>
            <p
                v-if="markAsTakenThenDelete"
                class="text-sm text-muted-foreground"
            >
                {{ t('common.markAsTakenBeforeDelete') }}
            </p>
        </div>
    </Modal>

    <Modal
        :open="deleteModalPropertyId !== null"
        :title="t('common.actionDangerous')"
        :confirm-label="t('common.delete')"
        :processing="
            deleteModalPropertyId !== null &&
            deletingId === deleteModalPropertyId
        "
        @close="deleteModalPropertyId = null"
        @confirm="handleConfirmDelete"
    >
        <p class="text-sm text-muted-foreground">
            {{ t('common.actionDangerousConfirm') }}
        </p>
    </Modal>
</template>
