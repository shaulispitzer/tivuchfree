<script setup lang="ts">
import type { PropType } from 'vue';
import {
    store,
    verifyOtp,
} from '@/actions/App/Http/Controllers/PropertySubscriptionController';
import PropertyFilters from '@/components/properties/PropertyFilters.vue';
import type { PropertyFilterState } from '@/components/properties/PropertyFilters.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/properties';

type Option = { value: string; label: string };

const defaultFilters: PropertyFilterState = {
    neighbourhood: '',
    hide_taken_properties: false,
    bedrooms_range: [1, 10],
    furnished: '',
    type: '',
    available_from: '',
    available_to: '',
    sort: 'newest',
};

const props = defineProps({
    user: {
        type: Object as PropType<{ email: string } | null>,
        default: null,
    },
    subscription_otp_pending: {
        type: Object as PropType<{ email: string } | null>,
        default: null,
    },
    neighbourhood_options: {
        type: Array as PropType<string[]>,
        required: true,
    },
    furnished_options: {
        type: Array as PropType<Option[]>,
        required: true,
    },
    type_options: {
        type: Array as PropType<Option[]>,
        required: true,
    },
});

const { t } = useI18n();

const filters = ref<PropertyFilterState>({ ...defaultFilters });
const email = ref(props.user?.email ?? '');

const form = useForm<{
    filters: PropertyFilterState;
    email: string;
}>({
    filters: filters.value,
    email: props.user?.email ?? email.value,
});

const otpForm = useForm<{ email: string; otp: string }>({
    email: props.subscription_otp_pending?.email ?? '',
    otp: '',
});

const subscribeProcessing = ref(false);
const otpProcessing = ref(false);

watch(
    () => props.subscription_otp_pending,
    (val) => {
        if (val) {
            otpForm.email = val.email;
            otpForm.otp = '';
        }
    },
    { immediate: true },
);

function updateFilters(value: PropertyFilterState): void {
    filters.value = value;
}

function submitSubscribe(): void {
    form.filters = filters.value;
    form.email = props.user?.email ?? email.value;
    subscribeProcessing.value = true;
    form.post(store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            // If guest, we stay on this page with OTP step; if logged in, redirect to properties
        },
        onFinish: () => (subscribeProcessing.value = false),
    });
}

function submitOtp(): void {
    otpForm.email =
        (otpForm.email || props.subscription_otp_pending?.email) ?? '';
    otpProcessing.value = true;
    otpForm.post(verifyOtp.url(), {
        preserveScroll: true,
        onSuccess: () => {
            // Redirect to properties is done by backend
        },
        onFinish: () => (otpProcessing.value = false),
    });
}
</script>

<template>
    <Head :title="t('subscription.subscribeToUpdates')" />
    <div class="mx-auto px-4 py-12">
        <h1 class="mb-2 text-2xl font-bold">
            {{
                subscription_otp_pending
                    ? t('subscription.enterCode')
                    : t('subscription.subscribeToUpdates')
            }}
        </h1>

        <div v-if="subscription_otp_pending" class="space-y-6">
            <p class="text-muted-foreground">
                {{ t('subscription.otpSent') }}
            </p>
            <form @submit.prevent="submitOtp" class="space-y-6">
                <div class="space-y-2">
                    <Label for="otp" class="text-base">{{
                        t('subscription.enterCode')
                    }}</Label>
                    <Input
                        id="otp"
                        v-model="otpForm.otp"
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        maxlength="6"
                        placeholder="000000"
                        class="h-12 font-mono text-lg tracking-widest"
                        :class="otpForm.errors.otp ? 'border-destructive' : ''"
                    />
                    <p
                        v-if="otpForm.errors.otp"
                        class="text-sm text-destructive"
                    >
                        {{ otpForm.errors.otp }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <Button as-child variant="outline">
                        <Link :href="index()">{{ t('common.back') }}</Link>
                    </Button>
                    <Button type="submit" :disabled="otpProcessing">
                        {{
                            otpProcessing
                                ? t('common.sending')
                                : t('subscription.verify')
                        }}
                    </Button>
                </div>
            </form>
        </div>

        <div v-else class="space-y-6">
            <p class="text-muted-foreground">
                {{ t('subscription.chooseFilters') }}
            </p>
            <form @submit.prevent="submitSubscribe" class="space-y-6">
                <PropertyFilters
                    :filters="filters"
                    :neighbourhood_options="neighbourhood_options"
                    :furnished_options="furnished_options"
                    :type_options="type_options"
                    :show_sort="false"
                    :show_hide_taken="false"
                    :subscription_mode="true"
                    @update:filters="updateFilters"
                />

                <div v-if="!user" class="space-y-2">
                    <Label
                        for="subscribe-email"
                        class="text-base"
                        requiredStar
                        >{{ t('common.email') }}</Label
                    >
                    <Input
                        id="subscribe-email"
                        v-model="email"
                        type="email"
                        required
                        class="h-12 w-64 text-base"
                        :class="form.errors.email ? 'border-destructive' : ''"
                    />
                    <p
                        v-if="form.errors.email"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.email }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <Button as-child variant="outline">
                        <Link :href="index()">{{ t('common.back') }}</Link>
                    </Button>
                    <Button
                        type="submit"
                        :disabled="subscribeProcessing"
                        class="min-w-32"
                    >
                        {{
                            subscribeProcessing
                                ? t('common.sending')
                                : t('subscription.subscribeToUpdates')
                        }}
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
