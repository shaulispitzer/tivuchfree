<script setup lang="ts">
import {
    DateFormatter,
    getLocalTimeZone,
    today,
} from '@internationalized/date';

import { CalendarIcon } from 'lucide-vue-next';
import type { DateValue } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps<{
    modelValue: DateValue | undefined;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: DateValue];
}>();

const { locale } = useI18n();

const isHebrewLocale = computed(() => locale.value?.toLowerCase().startsWith('he'));
const resolvedLocale = computed(() => (isHebrewLocale.value ? 'he-IL' : 'en-US'));

const defaultPlaceholder = today(getLocalTimeZone()) as unknown as DateValue;

const df = computed(
    () =>
        new DateFormatter(resolvedLocale.value, {
            dateStyle: 'long',
        }),
);

const placeholderLabel = computed(() =>
    isHebrewLocale.value ? 'בחר תאריך' : 'Pick a date',
);
</script>

<template>
    <Popover v-slot="{ close }">
        <PopoverTrigger as-child>
            <Button
                variant="outline"
                :class="
                    cn(
                        'w-[240px] justify-start text-left font-normal',
                        !props.modelValue && 'text-muted-foreground',
                    )
                "
            >
                <CalendarIcon />
                {{
                    props.modelValue
                        ? df.format(props.modelValue.toDate(getLocalTimeZone()))
                        : placeholderLabel
                }}
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-auto p-0" align="start">
            <Calendar
                :model-value="props.modelValue"
                :default-placeholder="defaultPlaceholder"
                layout="month-and-year"
                :locale="resolvedLocale"
                initial-focus
                @update:model-value="
                    (val) => {
                        if (val) {
                            emit('update:modelValue', val);
                            close();
                        }
                    }
                "
            />
        </PopoverContent>
    </Popover>
</template>
