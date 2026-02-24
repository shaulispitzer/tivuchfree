<script setup lang="ts">
import { store } from '@/actions/App/Http/Controllers/ContactController';

const { t } = useI18n();
const page = usePage();
const user = computed(() => page.props.user ?? null);

const form = useForm({
    subject: '',
    email: user.value?.email ?? '',
    is_about_dira: false,
    property_id: '',
    message: '',
});

const isAboutDira = ref(false);

function submit(): void {
    form.post(store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            isAboutDira.value = false;
        },
    });
}
</script>

<template>
    <Head title="Contact Us" />
    <div class="mx-auto max-w-2xl px-4 py-12">
        <h1 class="mb-2 text-2xl font-bold">{{ t('common.contactUs') }}</h1>
        <p class="mb-8 text-muted-foreground">
            Have a question or need help? Fill out the form below and we'll get
            back to you.
        </p>

        <FormKit
            type="form"
            :actions="false"
            class="space-y-5"
            form-class="space-y-5"
            @submit="submit"
        >
            <FormKit
                v-model="form.subject"
                type="text"
                name="subject"
                :label="t('contact.subject')"
                validation="required"
                :errors="form.errors.subject ? [form.errors.subject] : []"
            />

            <FormKit
                v-model="form.email"
                type="email"
                name="email"
                :label="t('contact.email')"
                validation="required|email"
                :errors="form.errors.email ? [form.errors.email] : []"
            />

            <FormKit
                v-model="isAboutDira"
                type="checkbox"
                name="is_about_dira"
                :label="t('contact.isAboutDira')"
                @input="
                    (value: boolean | undefined) => {
                        if (value === undefined) return;
                        form.is_about_dira = value;
                        if (!value) {
                            form.property_id = '';
                        }
                    }
                "
            />

            <FormKit
                v-if="isAboutDira"
                v-model="form.property_id"
                type="text"
                name="property_id"
                :label="t('contact.propertyId')"
                validation="required"
                :errors="
                    form.errors.property_id ? [form.errors.property_id] : []
                "
            />

            <FormKit
                v-model="form.message"
                type="textarea"
                name="message"
                :label="t('contact.message')"
                validation="required"
                :errors="form.errors.message ? [form.errors.message] : []"
            />

            <Button type="submit" :disabled="form.processing" class="w-full">
                {{ form.processing ? t('common.sending') : t('contact.send') }}
            </Button>
        </FormKit>
    </div>
</template>
