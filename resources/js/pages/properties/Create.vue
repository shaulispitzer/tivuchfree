<script setup lang="ts">
import type { PropType } from 'vue';

import PropertyImageUploader from '@/components/PropertyImageUploader.vue';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index, store } from '@/routes/properties';

const props = defineProps({
    options: {
        type: Object as PropType<App.Data.PropertyFormOptionsData>,
        required: true,
    },
});

const LeaseType = {
    MediumTerm: 'medium_term',
    LongTerm: 'long_term',
} as const satisfies Record<string, App.Enums.PropertyLeaseType>;

type PropertyCreateFormData = Omit<
    App.Data.Forms.PropertyFormData,
    'floor' | 'bedrooms'
> & {
    floor: number | undefined;
    bedrooms: number | undefined;
};

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

const form = useForm<PropertyCreateFormData>({
    neighbourhoods: [],
    street: '',
    floor: undefined,
    type: LeaseType.LongTerm,
    available_from: getLocalDateString(new Date()) + 'T00:00:00Z',
    available_to: null,
    bedrooms: undefined,
    furnished: 'no',
    temp_upload_id: null,
    image_media_ids: [],
    main_image_media_id: null,
});
const uploadingImages = ref(false);

const isMediumTerm = computed(() => form.type === 'medium_term');
const neighbourhoodClientError = computed(() => {
    if (form.neighbourhoods.length === 0) {
        return 'Please select at least one neighbourhood.';
    }

    if (form.neighbourhoods.length > 3) {
        return 'You can select up to 3 neighbourhoods.';
    }

    if (new Set(form.neighbourhoods).size !== form.neighbourhoods.length) {
        return 'Each neighbourhood can only be selected once.';
    }

    return null;
});

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

watch(
    () => form.neighbourhoods,
    () => {
        if (form.errors.neighbourhoods) {
            form.clearErrors('neighbourhoods');
        }
    },
    { deep: true },
);

function submit(): void {
    if (uploadingImages.value) {
        return;
    }

    if (neighbourhoodClientError.value) {
        form.setError('neighbourhoods', neighbourhoodClientError.value);
        return;
    }

    form.clearErrors('neighbourhoods');
    form.post(store.url());
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

    <FormKit
        type="form"
        :actions="false"
        class="mx-3 mt-6 space-y-6 px-6"
        form-class="space-y-6"
        @submit="submit"
    >
        <div class="grid gap-3">
            <label class="required-asterisk">Neighbourhoods</label>
            <Select v-model="form.neighbourhoods" multiple>
                <SelectTrigger class="w-full">
                    <SelectValue placeholder="Select 1 to 3 neighbourhoods" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="option in props.options.neighbourhoods"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <p class="text-xs text-muted-foreground">
                Select between 1 and 3 neighbourhoods.
            </p>

            <div v-if="form.errors.neighbourhoods" class="text-sm text-red-600">
                {{ form.errors.neighbourhoods }}
            </div>
            <div
                v-if="form.errors['neighbourhoods.0']"
                class="text-sm text-red-600"
            >
                {{ form.errors['neighbourhoods.0'] }}
            </div>
        </div>

        <div class="grid gap-2">
            <FormKit
                v-model="form.street"
                type="text"
                name="street"
                label="Street"
                validation="required"
                label-class="required-asterisk"
            />
            <div v-if="form.errors.street" class="text-sm text-red-600">
                {{ form.errors.street }}
            </div>
        </div>

        <div class="grid gap-2">
            <FormKit
                v-model="form.floor"
                type="number"
                name="floor"
                label="Floor"
                step="0.1"
                number
                validation="number|min:0|required"
                label-class="required-asterisk"
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
                :options="props.options.lease_types"
                validation="required"
                label-class="required-asterisk"
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
                label-class="required-asterisk"
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
                label-class="required-asterisk"
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
                step="0.5"
                number
                validation="required|min:1|max:10"
                label-class="required-asterisk"
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
                :options="props.options.furnished"
                validation="required"
                label-class="required-asterisk"
            />
            <div v-if="form.errors.furnished" class="text-sm text-red-600">
                {{ form.errors.furnished }}
            </div>
        </div>

        <PropertyImageUploader
            v-model:tempUploadId="form.temp_upload_id"
            v-model:mediaIds="form.image_media_ids"
            v-model:mainMediaId="form.main_image_media_id"
            v-model:uploading="uploadingImages"
            :error="
                form.errors.main_image_media_id ||
                form.errors.image_media_ids ||
                form.errors.temp_upload_id
            "
        />

        <div class="flex items-center gap-4">
            <Button
                type="submit"
                :disabled="form.processing || uploadingImages"
            >
                Create
            </Button>
        </div>
    </FormKit>
</template>
