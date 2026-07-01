<script setup lang="ts">
import ChevronDown16 from '~icons/octicon/chevron-down-16';

const { t } = useI18n();

type Option = {
    value: string;
    label: string;
};

const props = withDefaults(
    defineProps<{
        modelValue: string[];
        options: Option[];
        triggerClass?: string;
    }>(),
    { triggerClass: '' },
);

const emit = defineEmits<{
    'update:modelValue': [value: string[]];
}>();

const showAll = ref((props.modelValue?.length ?? 0) === 0);
const selectedNeighbourhoods = ref<string[]>([...(props.modelValue ?? [])]);

watch(
    () => props.modelValue,
    (value) => {
        if (!value || value.length === 0) {
            showAll.value = true;
            selectedNeighbourhoods.value = [];
        } else {
            showAll.value = false;
            selectedNeighbourhoods.value = [...value];
        }
    },
);

function toggleShowAll(checked: boolean): void {
    showAll.value = checked;
    if (checked) {
        selectedNeighbourhoods.value = [];
        emit('update:modelValue', []);
    }
}

function toggleOption(option: string): void {
    if (showAll.value) {
        showAll.value = false;
        selectedNeighbourhoods.value = [option];
    } else {
        const next = new Set(selectedNeighbourhoods.value);
        if (next.has(option)) {
            next.delete(option);
        } else {
            next.add(option);
        }
        selectedNeighbourhoods.value = [...next];
    }
    emit('update:modelValue', [...selectedNeighbourhoods.value]);
}

function isChecked(option: string): boolean {
    if (showAll.value) return false;
    return selectedNeighbourhoods.value.includes(option);
}

function labelFor(option: Option): string {
    return option.label;
}

function displayText(): string {
    if (showAll.value || selectedNeighbourhoods.value.length === 0) {
        return t('neighbourhoods.allNeighbourhoods');
    }
    if (selectedNeighbourhoods.value.length === 1) {
        const selected = props.options.find(
            (option) => option.value === selectedNeighbourhoods.value[0],
        );

        return selected?.label ?? selectedNeighbourhoods.value[0];
    }
    return t('propertyFilters.neighbourhoodsSelected', {
        count: selectedNeighbourhoods.value.length,
    });
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
                <div class="flex items-center gap-2 text-sm font-medium">
                    <Switch
                        :model-value="showAll"
                        @update:model-value="toggleShowAll"
                    />
                    <span>{{ t('neighbourhoods.allNeighbourhoods') }}</span>
                </div>
                <p class="-mt-1 text-xs text-muted-foreground">
                    {{ t('neighbourhoods.orSelectFromList') }}
                </p>
                <div class="flex max-h-48 flex-col gap-2 overflow-y-auto">
                    <label
                        v-for="option in options"
                        :key="option.value"
                        class="flex cursor-pointer items-center gap-2 text-sm"
                    >
                        <Checkbox
                            :model-value="isChecked(option.value)"
                            @update:model-value="() => toggleOption(option.value)"
                        />
                        {{ labelFor(option) }}
                    </label>
                </div>
            </div>
        </PopoverContent>
    </Popover>
</template>
