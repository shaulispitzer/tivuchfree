<script setup lang="ts">
import axios from 'axios';
import { Form, Head, Link, router } from '@inertiajs/vue3';
import type { PropType } from 'vue';
import InputError from '@/components/InputError.vue';
import PaginationNav from '@/components/PaginationNav.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import {
    create,
    destroy,
    edit,
    generateCsv,
    importMethod,
    index,
} from '@/routes/admin/streets';
import type { Paginator } from '@/types';
import { CircleHelp, Download } from 'lucide-vue-next';
import { useToast } from 'vue-toastification';
import ChevronDown16 from '~icons/octicon/chevron-down-16';

type Street = {
    id: number;
    neighbourhood: string;
    name: {
        en: string;
        he: string;
    };
    translatedName: string;
};

type Option = {
    value: string;
    label: string;
};

type ListFilters = {
    search: string;
    neighbourhood: number | null;
};

const props = defineProps({
    streets: {
        type: Object as PropType<Paginator<Street>>,
        required: true,
    },
    neighbourhoods: {
        type: Array as PropType<Option[]>,
        required: true,
    },
    filters: {
        type: Object as PropType<ListFilters>,
        required: true,
    },
});

const { t } = useI18n();
const toast = useToast();
const triggerClass =
    'group h-9 w-full hover:bg-accent data-[state=open]:bg-accent';
const search = ref(props.filters.search);
const neighbourhood = ref(
    props.filters.neighbourhood !== null
        ? String(props.filters.neighbourhood)
        : 'all',
);

const showGenerateSheet = ref(false);
const generatingCsv = ref(false);
const generateCsvError = ref('');
const generateSouth = ref('');
const generateWest = ref('');
const generateNorth = ref('');
const generateEast = ref('');
const generateNeighbourhoodId = ref('');

const hasActiveFilters = computed(
    () => search.value.trim() !== '' || neighbourhood.value !== 'all',
);

function applyFilters(): void {
    router.get(
        index(),
        {
            search: search.value.trim() || undefined,
            neighbourhood:
                neighbourhood.value === 'all' ? undefined : neighbourhood.value,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
}

let searchTimeout: ReturnType<typeof setTimeout> | undefined;

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 300);
});

watch(neighbourhood, () => {
    applyFilters();
});

watch(
    () => props.filters,
    (value) => {
        search.value = value.search;
        neighbourhood.value =
            value.neighbourhood !== null ? String(value.neighbourhood) : 'all';
    },
    { deep: true },
);

function streetDestroyForm(streetId: number) {
    return destroy.form(streetId, {
        query: {
            search: props.filters.search || undefined,
            neighbourhood: props.filters.neighbourhood ?? undefined,
            page:
                props.streets.current_page > 1
                    ? props.streets.current_page
                    : undefined,
        },
    });
}

function resetGenerateCsvForm(): void {
    generateSouth.value = '';
    generateWest.value = '';
    generateNorth.value = '';
    generateEast.value = '';
    generateNeighbourhoodId.value = '';
    generateCsvError.value = '';
}

async function handleGenerateCsv(): Promise<void> {
    generateCsvError.value = '';
    generatingCsv.value = true;

    try {
        const response = await axios.post(
            generateCsv.url(),
            {
                south: generateSouth.value,
                west: generateWest.value,
                north: generateNorth.value,
                east: generateEast.value,
                neighbourhood_id: generateNeighbourhoodId.value,
            },
            { responseType: 'blob' },
        );

        const disposition = response.headers['content-disposition'] as
            | string
            | undefined;
        let filename = 'streets.csv';
        const match = disposition?.match(/filename="?([^"]+)"?/);

        if (match?.[1]) {
            filename = match[1];
        }

        const url = URL.createObjectURL(
            new Blob([response.data], { type: 'text/csv' }),
        );
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        link.click();
        URL.revokeObjectURL(url);

        showGenerateSheet.value = false;
        resetGenerateCsvForm();
    } catch (error) {
        let message = 'Failed to generate CSV.';

        if (axios.isAxiosError(error) && error.response?.data instanceof Blob) {
            const text = await error.response.data.text();

            try {
                const payload = JSON.parse(text) as {
                    message?: string;
                    errors?: Record<string, string[]>;
                };

                if (payload.message) {
                    message = payload.message;
                } else if (payload.errors) {
                    message = Object.values(payload.errors).flat().join(' ');
                }
            } catch {
                message = 'Failed to generate CSV.';
            }
        }

        generateCsvError.value = message;
        toast.error(message);
    } finally {
        generatingCsv.value = false;
    }
}
</script>

<template>
    <Head title="Streets" />

    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-lg font-semibold">Streets</h1>
            <div class="flex items-center gap-2">
                <Sheet v-model:open="showGenerateSheet">
                    <SheetTrigger as-child>
                        <Button
                            type="button"
                            variant="secondary"
                            @click="resetGenerateCsvForm"
                        >
                            <Download class="size-4" />
                            Generate CSV
                        </Button>
                    </SheetTrigger>
                    <SheetContent class="overflow-y-auto sm:max-w-lg">
                        <SheetHeader>
                            <SheetTitle>Generate streets CSV</SheetTitle>
                            <SheetDescription>
                                Fetch street names from OpenStreetMap for your
                                neighbourhood area, then review and import the
                                downloaded file.
                            </SheetDescription>
                        </SheetHeader>

                        <div class="space-y-4 px-4">
                            <div class="space-y-2 rounded-lg border border-input p-4 text-sm">
                                <p class="font-medium">How to get coordinates</p>
                                <ol class="list-decimal space-y-2 pl-4 text-muted-foreground">
                                    <li>
                                        Open
                                        <a
                                            href="https://www.openstreetmap.org/export"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="text-primary underline"
                                        >
                                            OpenStreetMap export
                                        </a>
                                        and zoom until you only see the
                                        neighbourhood area.
                                    </li>
                                    <li>
                                        Copy the bounding box coordinates shown
                                        on the left in this order: bottom
                                        (south), left (west), top (north), right
                                        (east).
                                    </li>
                                    <li>
                                        Paste them below, choose the
                                        neighbourhood, then generate the CSV.
                                    </li>
                                </ol>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="generate-south">Bottom (south)</Label>
                                    <Input
                                        id="generate-south"
                                        v-model="generateSouth"
                                        type="number"
                                        step="any"
                                        placeholder="31.79"
                                        required
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="generate-west">Left (west)</Label>
                                    <Input
                                        id="generate-west"
                                        v-model="generateWest"
                                        type="number"
                                        step="any"
                                        placeholder="35.20"
                                        required
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="generate-north">Top (north)</Label>
                                    <Input
                                        id="generate-north"
                                        v-model="generateNorth"
                                        type="number"
                                        step="any"
                                        placeholder="31.81"
                                        required
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="generate-east">Right (east)</Label>
                                    <Input
                                        id="generate-east"
                                        v-model="generateEast"
                                        type="number"
                                        step="any"
                                        placeholder="35.22"
                                        required
                                    />
                                </div>
                            </div>

                            <div class="grid gap-2">
                                <Label for="generate-neighbourhood">Neighbourhood</Label>
                                <Select v-model="generateNeighbourhoodId">
                                    <SelectTrigger
                                        id="generate-neighbourhood"
                                        :class="triggerClass"
                                    >
                                        <SelectValue placeholder="Choose neighbourhood" />
                                        <template #trigger-icon>
                                            <ChevronDown16
                                                class="size-4 opacity-50 transition-transform duration-300 group-data-[state=open]:rotate-x-180"
                                            />
                                        </template>
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="option in neighbourhoods"
                                            :key="option.value"
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <InputError :message="generateCsvError" />
                        </div>

                        <SheetFooter class="px-4">
                            <Button
                                type="button"
                                :disabled="
                                    generatingCsv ||
                                    !generateSouth ||
                                    !generateWest ||
                                    !generateNorth ||
                                    !generateEast ||
                                    !generateNeighbourhoodId
                                "
                                @click="handleGenerateCsv"
                            >
                                {{
                                    generatingCsv
                                        ? 'Generating...'
                                        : 'Generate and download CSV'
                                }}
                            </Button>
                        </SheetFooter>
                    </SheetContent>
                </Sheet>
                <Form
                    v-bind="importMethod.form()"
                    class="flex items-center gap-2"
                    enctype="multipart/form-data"
                    v-slot="{ errors, processing }"
                >
                    <div class="flex items-center gap-1">
                        <input
                            type="file"
                            name="file"
                            accept=".xlsx,.csv"
                            required
                            class="text-sm file:mr-2 file:rounded-md file:border-0 file:bg-secondary file:px-4 file:py-2 file:text-sm file:font-medium file:text-secondary-foreground hover:file:bg-secondary/80"
                        />
                        <Popover>
                            <PopoverTrigger as-child>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    class="size-8 shrink-0"
                                    aria-label="Import file format help"
                                >
                                    <CircleHelp class="size-4" />
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-80 text-sm" align="end">
                                <div class="space-y-3">
                                    <p class="font-medium">Import file format</p>
                                    <p class="text-muted-foreground">
                                        Upload a CSV or XLSX file (max 10 MB)
                                        with these columns:
                                    </p>
                                    <ul
                                        class="list-disc space-y-1 pl-4 text-muted-foreground"
                                    >
                                        <li>
                                            <strong>neighbourhood</strong> —
                                            existing English name or ID
                                        </li>
                                        <li>
                                            <strong>name_en</strong> — street
                                            name in English
                                        </li>
                                        <li>
                                            <strong>name_he</strong> — street
                                            name in Hebrew
                                        </li>
                                    </ul>
                                    <pre
                                        class="overflow-x-auto rounded-md bg-muted p-2 text-xs"
                                    >neighbourhood,name_en,name_he
Bar Ilan,Eli HaCohen,עלי הכהן</pre
                                    >
                                    <p class="text-xs text-muted-foreground">
                                        Alternative headers like
                                        <strong>name (EN)</strong> and
                                        <strong>neighbourhood_id</strong> also
                                        work. Rows with missing data are
                                        skipped. Neighbourhoods must already
                                        exist.
                                    </p>
                                </div>
                            </PopoverContent>
                        </Popover>
                    </div>
                    <Button
                        type="submit"
                        variant="secondary"
                        :disabled="processing"
                    >
                        Import Streets
                    </Button>
                    <InputError :message="errors.file" />
                </Form>
                <Button as-child>
                    <Link :href="create()">Add street</Link>
                </Button>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-4">
            <div class="grid w-56 gap-2">
                <Label for="street-search">{{ t('common.search') }}</Label>
                <Input
                    id="street-search"
                    v-model="search"
                    class="max-w-md"
                    type="search"
                    :placeholder="t('common.searchHere')"
                />
            </div>
            <div class="grid w-56 gap-2">
                <Label for="street-neighbourhood">Neighbourhood</Label>
                <Select v-model="neighbourhood">
                    <SelectTrigger
                        id="street-neighbourhood"
                        :class="triggerClass"
                    >
                        <SelectValue placeholder="All neighbourhoods" />
                        <template #trigger-icon>
                            <ChevronDown16
                                class="size-4 opacity-50 transition-transform duration-300 group-data-[state=open]:rotate-x-180"
                            />
                        </template>
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All neighbourhoods</SelectItem>
                        <SelectItem
                            v-for="option in neighbourhoods"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <div
            v-if="streets.data.length === 0"
            class="rounded-lg border border-dashed border-input p-8 text-center text-sm text-muted-foreground"
        >
            {{
                hasActiveFilters
                    ? t('common.noResults')
                    : 'No streets have been added yet.'
            }}
        </div>

        <template v-else>
            <div class="overflow-x-auto rounded-lg border border-input">
                <table class="w-full text-sm">
                    <thead class="bg-muted/40 text-left">
                        <tr>
                            <th class="px-4 py-3 font-medium">Neighbourhood</th>
                            <th class="px-4 py-3 font-medium">Name (EN)</th>
                            <th class="px-4 py-3 font-medium">Name (HE)</th>
                            <th class="px-4 py-3 font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="street in streets.data"
                            :key="street.id"
                            class="border-t border-input"
                        >
                            <td class="px-4 py-3">
                                {{ street.neighbourhood }}
                            </td>
                            <td class="px-4 py-3">{{ street.name.en }}</td>
                            <td class="px-4 py-3">{{ street.name.he }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <Button
                                        variant="secondary"
                                        size="sm"
                                        as-child
                                    >
                                        <Link :href="edit(street.id)"
                                            >Edit</Link
                                        >
                                    </Button>
                                    <Form v-bind="streetDestroyForm(street.id)">
                                        <Button variant="destructive" size="sm">
                                            Delete
                                        </Button>
                                    </Form>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <PaginationNav
                :links="streets.links"
                :from="streets.from"
                :to="streets.to"
                :total="streets.total"
            />
        </template>
    </div>
</template>
