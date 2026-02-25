<script setup lang="ts">
import {
    DateFormatter,
    getLocalTimeZone,
    today,
} from '@internationalized/date';

import { CalendarIcon } from 'lucide-vue-next';
import type { DateValue } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    modelValue: DateValue | undefined;
    name?: string;
    placeholder?: string;
    disabled?: boolean;
    triggerClass?: HTMLAttributes['class'];
    clearable?: boolean;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: DateValue | undefined];
}>();

const { locale } = useI18n();

const isHebrewLocale = computed(() =>
    locale.value?.toLowerCase().startsWith('he'),
);
const resolvedLocale = computed(() =>
    isHebrewLocale.value ? 'he-IL' : 'en-US',
);

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

const resolvedPlaceholderLabel = computed(
    () => props.placeholder ?? placeholderLabel.value,
);

const clearLabel = computed(() => (isHebrewLocale.value ? 'נקה' : 'Clear'));
</script>

<template>
    <div class="w-full min-w-0">
        <Popover v-slot="{ close }">
            <PopoverTrigger as-child>
                <Button
                    variant="outline"
                    size="default"
                    :disabled="props.disabled"
                    :class="
                        cn(
                            'w-full min-w-0 justify-start px-3 py-1 text-left text-base font-normal shadow-xs md:text-sm',
                            !props.modelValue && 'text-muted-foreground',
                            props.triggerClass,
                        )
                    "
                >
                    <CalendarIcon />
                    {{
                        props.modelValue
                            ? df.format(
                                  props.modelValue.toDate(getLocalTimeZone()),
                              )
                            : resolvedPlaceholderLabel
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
                <div v-if="props.clearable" class="border-t p-2">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="w-full justify-start"
                        @click="
                            () => {
                                emit('update:modelValue', undefined);
                                close();
                            }
                        "
                    >
                        {{ clearLabel }}
                    </Button>
                </div>
            </PopoverContent>
        </Popover>

        <input
            v-if="props.name"
            type="hidden"
            :name="props.name"
            :value="props.modelValue ? props.modelValue.toString() : ''"
        />
    </div>
</template>
