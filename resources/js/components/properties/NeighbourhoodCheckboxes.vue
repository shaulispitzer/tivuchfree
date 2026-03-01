<script setup lang="ts">
import ChevronDown16 from '~icons/octicon/chevron-down-16';

const { t } = useI18n();

const props = withDefaults(
    defineProps<{
        modelValue: string[];
        options: string[];
        triggerClass?: string;
    }>(),
    { triggerClass: '' },
);

const emit = defineEmits<{
    'update:modelValue': [value: string[]];
}>();

/** When modelValue is [] we treat as "no filter" / all selected for display. */
const selectedSet = computed(() => {
    const value = props.modelValue ?? [];
    if (value.length === 0 && props.options.length > 0) {
        return new Set(props.options);
    }
    return new Set(value);
});

const isAllSelected = computed(
    () =>
        props.options.length > 0 &&
        props.options.every((opt) => selectedSet.value.has(opt)),
);

const isNoneSelected = computed(
    () => props.options.length === 0 || (props.modelValue ?? []).length === 0,
);

/** "Select all" uses only checked/unchecked so it matches other checkboxes visually. */
const selectAllState = computed<boolean>(() => isAllSelected.value);

/** When Select all is checked, emit [] so no neighbourhood filter is applied. */
function toggleSelectAll(checked: boolean | 'indeterminate'): void {
    if (checked === true) {
        emit('update:modelValue', []);
    } else {
        emit('update:modelValue', []);
    }
}

function setOption(option: string, checked: boolean): void {
    const next = new Set(selectedSet.value);
    if (checked) {
        next.add(option);
    } else {
        next.delete(option);
    }
    emit('update:modelValue', [...next]);
}

function labelFor(neighbourhood: string): string {
    return t(`neighbourhoods.${neighbourhood.replaceAll(' ', '')}`);
}

function displayText(): string {
    const arr = props.modelValue ?? [];
    if (arr.length === 0) {
        return props.options.length > 0
            ? t('neighbourhoods.allNeighbourhoodsSelected')
            : t('neighbourhoods.allNeighbourhoods');
    }
    if (isAllSelected.value)
        return t('neighbourhoods.allNeighbourhoodsSelected');
    if (arr.length === 1) {
        return t(`neighbourhoods.${arr[0].replaceAll(' ', '')}`);
    }
    return t('propertyFilters.neighbourhoodsSelected', { count: arr.length });
}
</script>

<template>
    <Popover>
        <PopoverTrigger as-child>
            <Button
                type="button"
                variant="outline"
                :class="[
                    'group w-full justify-between font-normal',
                    triggerClass,
                ]"
            >
                <span class="min-w-0 truncate">{{ displayText() }}</span>
                <ChevronDown16
                    class="size-4 shrink-0 opacity-50 transition-transform duration-300 group-data-[state=open]:rotate-x-180"
                />
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-72 p-0" align="start">
            <div class="flex flex-col gap-3 p-3">
                <label
                    class="flex cursor-pointer items-center gap-2 text-sm font-medium"
                >
                    <Checkbox
                        :model-value="selectAllState"
                        @update:model-value="toggleSelectAll"
                    />
                    {{ t('neighbourhoods.selectAll') }}
                </label>
                <div class="flex max-h-48 flex-col gap-2 overflow-y-auto">
                    <label
                        v-for="option in options"
                        :key="option"
                        class="flex cursor-pointer items-center gap-2 text-sm"
                    >
                        <Checkbox
                            :model-value="selectedSet.has(option)"
                            @update:model-value="
                                (v) => setOption(option, v === true)
                            "
                        />
                        {{ labelFor(option) }}
                    </label>
                </div>
            </div>
        </PopoverContent>
    </Popover>
</template>
