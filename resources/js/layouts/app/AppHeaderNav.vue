<script setup lang="ts">
import { ChevronDown } from 'lucide-vue-next';

const { t } = useI18n();

type NavItem =
    | {
          type: 'link';
          action: string;
          label: string;
      }
    | {
          type: 'dropdown';
          label: string;
          items: Array<{
              action: string;
              label: string;
          }>;
      };

const props = defineProps<{
    navClass: string;
    buttonClass: string;
    dropdownAlign: 'start' | 'center' | 'end';
    dropdownContentClass: string;
    onNavClick: (label: string) => void;
}>();

const navItems = computed<NavItem[]>(() => [
    { type: 'link', action: 'home', label: t('common.home') },
    {
        type: 'dropdown',
        label: t('common.listings'),
        items: [
            { action: 'listings-long-term', label: t('common.longTermRental') },
            {
                action: 'listings-medium-term',
                label: t('common.mediumTermRental'),
            },
            {
                action: 'listings-short-term',
                label: t('common.shortTermRental'),
            },
        ],
    },
    { type: 'link', action: 'about-us', label: t('common.aboutUs') },
    { type: 'link', action: 'contact-us', label: t('common.contactUs') },
]);
</script>

<template>
    <nav :class="props.navClass">
        <template v-for="item in navItems" :key="item.label">
            <DropdownMenu v-if="item.type === 'dropdown'">
                <DropdownMenuTrigger :as-child="true">
                    <Button variant="ghost" :class="props.buttonClass">
                        {{ item.label }}
                        <ChevronDown class="ml-1 h-4 w-4" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    :align="props.dropdownAlign"
                    :class="props.dropdownContentClass"
                >
                    <DropdownMenuItem
                        v-for="entry in item.items"
                        :key="entry.action"
                        @click="props.onNavClick(entry.action)"
                    >
                        {{ entry.label }}
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
            <Button
                v-else
                variant="ghost"
                :class="props.buttonClass"
                @click="props.onNavClick(item.action)"
            >
                {{ item.label }}
            </Button>
        </template>
    </nav>
</template>
