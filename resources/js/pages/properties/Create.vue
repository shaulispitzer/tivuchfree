<script setup lang="ts">
import type { PropType } from 'vue';

import { Button } from '@/components/ui/button';
import { index, store } from '@/routes/properties';

defineProps({
    options: {
        type: Object as PropType<App.Data.PropertyFormOptionsData>,
        required: true,
    },
});

const LeaseType = {
    MediumTerm: 'medium_term',
    LongTerm: 'long_term',
} as const satisfies Record<string, App.Enums.PropertyLeaseType>;

function getLocalDateString(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// Convert YYYY-MM-DD to YYYY-MM-DDTHH:MM:SSZ
function toISODateTime(dateString: string | null): string | null {
    if (!dateString) return null;
    return `${dateString}T00:00:00Z`;
}

const form = useForm<App.Data.Forms.PropertyFormData>({
    street: '',
    floor: '',
    type: LeaseType.LongTerm,
    available_from: getLocalDateString(new Date()) + 'T00:00:00Z',
    available_to: getLocalDateString(new Date()) + 'T00:00:00Z',
    bedrooms: 0,
    furnished: 'no',
});

const isMediumTerm = computed(() => form.type === 'medium_term');

// Computed properties to handle date conversion
const availableFrom = computed<string>({
    get: () =>
        form.available_from?.slice(0, 10) ?? getLocalDateString(new Date()),
    set: (value) => {
        form.available_from = value
            ? `${value}T00:00:00Z`
            : getLocalDateString(new Date()) + 'T00:00:00Z';
    },
});

const availableTo = computed<string>({
    get: () => form.available_to?.slice(0, 10) ?? '',
    set: (value) => {
        form.available_to = value.length ? toISODateTime(value) : null;
    },
});

watch(
    () => form.type,
    (type) => {
        if (type !== 'medium_term') {
            form.available_to = null;
        }
    },
);

function submit(): void {
    console.log(form.data());
    form.post(store.url(), {
        onSuccess: () => {
            router.visit(index());
        },
    });
}
</script>

<template>
    <Head title="Create property" />

    <div class="flex items-center justify-between">
        <h1 class="text-lg font-semibold">Create property</h1>
        <Button as-child variant="secondary">
            <Link :href="index()">Back to list</Link>
        </Button>
    </div>

    <FormKit type="form" class="mt-6 space-y-6" @submit="submit">
        <div class="grid gap-2">
            <FormKit
                v-model="form.street"
                type="text"
                name="street"
                label="Street"
                validation="required"
            />
            <div v-if="form.errors.street" class="text-sm text-red-600">
                {{ form.errors.street }}
            </div>
        </div>

        <div class="grid gap-2">
            <FormKit
                v-model="form.floor"
                type="text"
                name="floor"
                label="Floor"
                validation="required"
            />
            <div v-if="form.errors.floor" class="text-sm text-red-600">
                {{ form.errors.floor }}
            </div>
        </div>

        <div class="grid gap-2">
            <FormKit
                v-model="form.type"
                type="select"
                name="type"
                label="Type"
                placeholder="Select type"
                :options="options.lease_types"
                validation="required"
            />
            <div v-if="form.errors.type" class="text-sm text-red-600">
                {{ form.errors.type }}
            </div>
        </div>

        <div class="grid gap-2">
            <FormKit
                v-model="availableFrom"
                type="date"
                name="available_from"
                label="Available from"
                validation="required"
            />
            <div v-if="form.errors.available_from" class="text-sm text-red-600">
                {{ form.errors.available_from }}
            </div>
        </div>

        <div v-if="isMediumTerm" class="grid gap-2">
            <FormKit
                v-model="availableTo"
                type="date"
                name="available_to"
                label="Available to"
                validation="required"
            />
            <div v-if="form.errors.available_to" class="text-sm text-red-600">
                {{ form.errors.available_to }}
            </div>
        </div>

        <div class="grid gap-2">
            <FormKit
                v-model="form.bedrooms"
                type="number"
                name="bedrooms"
                label="Bedrooms"
                number
                validation="required|min:0"
            />
            <div v-if="form.errors.bedrooms" class="text-sm text-red-600">
                {{ form.errors.bedrooms }}
            </div>
        </div>

        <div class="grid gap-2">
            <FormKit
                v-model="form.furnished"
                type="select"
                name="furnished"
                label="Furnished"
                placeholder="Select furnished status"
                :options="options.furnished"
                validation="required"
            />
            <div v-if="form.errors.furnished" class="text-sm text-red-600">
                {{ form.errors.furnished }}
            </div>
        </div>
    </FormKit>
</template>
