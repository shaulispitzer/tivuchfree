<script setup lang="ts">
import { ChevronDown } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

type NavItem =
    | {
          type: 'link';
          label: string;
      }
    | {
          type: 'dropdown';
          label: string;
          items: string[];
      };

const props = defineProps<{
    navClass: string;
    buttonClass: string;
    dropdownAlign: 'start' | 'center' | 'end';
    dropdownContentClass: string;
    onNavClick: (label: string) => void;
}>();

const navItems: NavItem[] = [
    { type: 'link', label: t('common.home') },
    {
        type: 'dropdown',
        label: 'Listings',
        items: ['Long Term Rental', 'Medium Term Rental', 'Short Term Rental'],
    },
    { type: 'link', label: t('common.aboutUs') },
    { type: 'link', label: t('common.contactUs') },
];
</script>

<template>
    <nav :class="props.navClass">
        <template v-for="item in navItems" :key="item.label">
            <DropdownMenu v-if="item.type === 'dropdown'">
                <DropdownMenuTrigger :as-child="true">
                    <Button
                        variant="ghost"
                        :class="props.buttonClass"
                        @click="props.onNavClick(item.label)"
                    >
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
                        :key="entry"
                        @click="props.onNavClick(entry)"
                    >
                        {{ entry }}
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
            <Button
                v-else
                variant="ghost"
                :class="props.buttonClass"
                @click="props.onNavClick(item.label)"
            >
                {{ item.label }}
            </Button>
        </template>
    </nav>
</template>
