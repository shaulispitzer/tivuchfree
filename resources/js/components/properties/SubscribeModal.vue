<script setup lang="ts">
import type { PropType } from 'vue';
import {
    store,
    verifyOtp,
} from '@/actions/App/Http/Controllers/PropertySubscriptionController';
import PropertyFilters from '@/components/properties/PropertyFilters.vue';
import type { PropertyFilterState } from '@/components/properties/PropertyFilters.vue';

type Option = { value: string; label: string };

const props = defineProps({
    open: { type: Boolean, required: true },
    userEmail: { type: String, default: null },
    neighbourhoodOptions: { type: Array as PropType<string[]>, required: true },
    furnishedOptions: { type: Array as PropType<Option[]>, required: true },
    typeOptions: { type: Array as PropType<Option[]>, required: true },
    initialFilters: {
        type: Object as PropType<PropertyFilterState>,
        default: () => ({
            neighbourhood: '',
            hide_taken_properties: false,
            bedrooms_range: [1, 10] as [number, number],
            furnished: '',
            type: '',
            available_from: '',
            available_to: '',
            sort: 'newest' as const,
        }),
    },
    otpPending: {
        type: Object as PropType<{ email: string }>,
        default: null,
    },
});

const emit = defineEmits<{ close: [] }>();

const { t } = useI18n();

const filters = ref<PropertyFilterState>({ ...props.initialFilters });
const email = ref(props.userEmail ?? '');
const otp = ref('');

const form = useForm<{
    filters: PropertyFilterState;
    email: string;
}>({
    filters: filters.value,
    email: props.userEmail ?? email.value,
});

const otpForm = useForm<{ email: string; otp: string }>({
    email: props.otpPending?.email ?? '',
    otp: '',
});

const subscribeProcessing = ref(false);
const otpProcessing = ref(false);

watch(
    () => props.otpPending,
    (val) => {
        if (val) {
            otpForm.email = val.email;
            otpForm.otp = '';
        }
    },
    { immediate: true },
);

watch(
    () => props.open,
    (val) => {
        if (val) {
            filters.value = { ...props.initialFilters };
            email.value = props.userEmail ?? '';
        }
    },
);

function updateFilters(value: PropertyFilterState): void {
    filters.value = value;
}

function submitSubscribe(): void {
    form.filters = filters.value;
    form.email = props.userEmail ?? email.value;
    subscribeProcessing.value = true;
    form.post(store.url(), {
        preserveScroll: true,
        onSuccess: () => emit('close'),
        onFinish: () => (subscribeProcessing.value = false),
    });
}

function submitOtp(): void {
    otpForm.email = (otpForm.email || props.otpPending?.email) ?? '';
    otpProcessing.value = true;
    otpForm.post(verifyOtp.url(), {
        preserveScroll: true,
        onSuccess: () => emit('close'),
        onFinish: () => (otpProcessing.value = false),
    });
}
</script>

<template>
    <Modal
        :open="open"
        :title="
            otpPending
                ? t('subscription.enterCode')
                : t('subscription.subscribeToUpdates')
        "
        :actions="false"
        :width="'max-w-2xl'"
        @close="emit('close')"
    >
        <div v-if="otpPending" class="space-y-4">
            <p class="text-sm text-muted-foreground">
                {{ t('subscription.otpSent') }}
            </p>
            <form @submit.prevent="submitOtp" class="space-y-4">
                <div class="space-y-2">
                    <Label for="otp">{{ t('subscription.enterCode') }}</Label>
                    <Input
                        id="otp"
                        v-model="otpForm.otp"
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        maxlength="6"
                        placeholder="000000"
                        class="font-mono text-lg tracking-widest"
                        :class="otpForm.errors.otp ? 'border-destructive' : ''"
                    />
                    <p
                        v-if="otpForm.errors.otp"
                        class="text-sm text-destructive"
                    >
                        {{ otpForm.errors.otp }}
                    </p>
                </div>
                <Button type="submit" :disabled="otpProcessing" class="w-full">
                    {{
                        otpProcessing
                            ? t('common.sending')
                            : t('subscription.verify')
                    }}
                </Button>
            </form>
        </div>

        <div v-else class="space-y-4">
            <p class="text-sm text-muted-foreground">
                {{ t('subscription.chooseFilters') }}
            </p>
            <form @submit.prevent="submitSubscribe" class="space-y-4">
                <PropertyFilters
                    :filters="filters"
                    :neighbourhood_options="neighbourhoodOptions"
                    :furnished_options="furnishedOptions"
                    :type_options="typeOptions"
                    @update:filters="updateFilters"
                />
                <div v-if="!userEmail" class="space-y-2">
                    <Label for="subscribe-email">{{ t('common.email') }}</Label>
                    <Input
                        id="subscribe-email"
                        v-model="email"
                        type="email"
                        required
                        :class="form.errors.email ? 'border-destructive' : ''"
                    />
                    <p
                        v-if="form.errors.email"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.email }}
                    </p>
                </div>
                <Button
                    type="submit"
                    :disabled="subscribeProcessing"
                    class="w-full"
                >
                    {{
                        subscribeProcessing
                            ? t('common.sending')
                            : t('subscription.subscribeToUpdates')
                    }}
                </Button>
            </form>
        </div>
    </Modal>
</template>
