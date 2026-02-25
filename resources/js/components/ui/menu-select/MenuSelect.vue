<script setup lang="ts">
import type { HTMLAttributes } from "vue"

import { useVModel } from "@vueuse/core"
import { ChevronDown } from "lucide-vue-next"
import { cn } from "@/lib/utils"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuRadioGroup,
  DropdownMenuRadioItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"

const dropdownOpen = ref(false)

type Option = {
  value: string
  label: string
  disabled?: boolean
}

defineOptions({
  inheritAttrs: false,
})

const props = withDefaults(
  defineProps<{
    modelValue?: string
    options: Option[]
    placeholder?: string
    disabled?: boolean
    size?: "sm" | "default"
    triggerClass?: HTMLAttributes["class"]
    contentClass?: HTMLAttributes["class"]
    itemClass?: HTMLAttributes["class"]
    align?: "start" | "center" | "end"
  }>(),
  {
    placeholder: "Select…",
    disabled: false,
    size: "default",
    align: "center",
  },
)

const emit = defineEmits<{
  "update:modelValue": [value: string]
}>()

const modelValue = useVModel(props, "modelValue", emit, {
  passive: true,
  defaultValue: "",
})

const selectedLabel = computed(() => {
  return props.options.find((o) => o.value === modelValue.value)?.label ?? props.placeholder
})

watch(dropdownOpen, (open) => {
  if (open) {
    nextTick(() => {
      const content = document.querySelector(
        '[data-slot="dropdown-menu-content"][data-state="open"]',
      )
      const checked = content?.querySelector('[data-state="checked"]')
      checked?.scrollIntoView({ block: 'center', behavior: 'instant' })
    })
  }
})
</script>

<template>
  <DropdownMenu v-model:open="dropdownOpen" v-bind="$attrs">
    <DropdownMenuTrigger as-child>
      <button
        type="button"
        :disabled="disabled"
        :data-size="size"
        :class="
          cn(
            'group border-input cursor-pointer data-placeholder:text-muted-foreground outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:ring-offset-background rounded-md aria-invalid:ring-destructive/20 aria-invalid:border-destructive inline-flex w-fit items-center justify-between gap-2 border bg-transparent text-sm whitespace-nowrap shadow-xs transition-[color,box-shadow] disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 hover:bg-accent data-[state=open]:bg-accent data-[size=default]:h-9 data-[size=default]:px-3 data-[size=default]:py-2 data-[size=sm]:h-8 data-[size=sm]:px-2 data-[size=sm]:py-1.5 data-[size=sm]:text-xs',
            props.triggerClass,
          )
        "
        :data-placeholder="!modelValue ? '' : undefined"
      >
        <span class="min-w-0 truncate pointer-events-none">
          <slot name="value" :label="selectedLabel" :value="modelValue">
            {{ selectedLabel }}
          </slot>
        </span>
        <ChevronDown
          :class="
            cn(
              'size-4 shrink-0 opacity-50 transition-transform duration-500',
              dropdownOpen ? '-scale-y-100' : 'scale-y-100',

            )
          "
          aria-hidden="true"
        />
      </button>
    </DropdownMenuTrigger>

    <DropdownMenuContent
      :align="align"
      :class="
        cn(
          'min-w-32 w-(--reka-dropdown-menu-trigger-width)',
          props.contentClass,
        )
      "
    >
      <DropdownMenuRadioGroup v-model="modelValue">
        <DropdownMenuRadioItem
          v-for="option in options"
          :key="option.value"
          :value="option.value"
          :disabled="option.disabled"
          :class="
            cn(
              'pl-2 pr-2 data-[state=checked]:bg-accent data-[state=checked]:text-accent-foreground [&>span:first-child]:hidden',
              props.itemClass,
            )
          "
        >
          <template #indicator-icon />
          <slot name="item" :option="option">
            {{ option.label }}
          </slot>
        </DropdownMenuRadioItem>
      </DropdownMenuRadioGroup>
    </DropdownMenuContent>
  </DropdownMenu>
</template>

