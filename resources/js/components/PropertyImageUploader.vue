<script setup lang="ts">
import axios from 'axios';
import { ImagePlus, Star, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { destroy, store } from '@/routes/property-image-uploads';

type ImageItem = {
    key: string;
    id: number | null;
    url: string;
    name: string;
    uploading: boolean;
};

const props = withDefaults(
    defineProps<{
        tempUploadId: number | null;
        mediaIds: number[] | null;
        mainMediaId: number | null;
        uploading?: boolean;
        max?: number;
        error?: string;
    }>(),
    {
        max: 6,
        error: undefined,
    },
);

const emit = defineEmits<{
    (e: 'update:tempUploadId', value: number | null): void;
    (e: 'update:mediaIds', value: number[]): void;
    (e: 'update:mainMediaId', value: number | null): void;
    (e: 'update:uploading', value: boolean): void;
}>();

const fileInput = ref<HTMLInputElement | null>(null);
const images = ref<ImageItem[]>([]);
const uploadError = ref<string | null>(null);

const canAddMore = computed(() => images.value.length < props.max);
const isUploading = computed(() => images.value.some((i) => i.uploading));

watch(
    isUploading,
    (value) => {
        emit('update:uploading', value);
    },
    { immediate: true },
);

function createKey(): string {
    const maybeCrypto = globalThis.crypto as Crypto | undefined;
    if (maybeCrypto?.randomUUID) {
        return maybeCrypto.randomUUID();
    }

    return `${Date.now()}-${Math.random().toString(16).slice(2)}`;
}

function syncToParent(): void {
    const ids = images.value.map((i) => i.id).filter((id): id is number => !!id);

    emit('update:mediaIds', ids);

    if (props.mainMediaId && ids.includes(props.mainMediaId)) {
        return;
    }

    emit('update:mainMediaId', ids[0] ?? null);
}

function openPicker(): void {
    uploadError.value = null;
    fileInput.value?.click();
}

function setMain(id: number): void {
    emit('update:mainMediaId', id);

    const idx = images.value.findIndex((i) => i.id === id);
    if (idx > 0) {
        const [item] = images.value.splice(idx, 1);
        images.value.unshift(item);
    }

    syncToParent();
}

async function removeImage(item: ImageItem): Promise<void> {
    uploadError.value = null;

    if (!props.tempUploadId || !item.id) {
        images.value = images.value.filter((i) => i.key !== item.key);
        syncToParent();
        return;
    }

    await axios.delete(
        destroy.url({
            tempUpload: props.tempUploadId,
            media: item.id,
        }),
    );

    images.value = images.value.filter((i) => i.key !== item.key);
    if (images.value.length === 0) {
        emit('update:tempUploadId', null);
    }
    syncToParent();
}

async function onFileChange(event: Event): Promise<void> {
    uploadError.value = null;
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    input.value = '';

    if (!file) return;
    if (!canAddMore.value) return;

    const sizeMb = file.size / 1024 / 1024;
    if (sizeMb > 10) {
        uploadError.value = `This image is ${sizeMb.toFixed(
            1,
        )}MB. Max allowed is 10MB.`;
        return;
    }

    const localUrl = URL.createObjectURL(file);

    const item: ImageItem = {
        key: createKey(),
        id: null,
        url: localUrl,
        name: file.name,
        uploading: true,
    };

    images.value.push(item);

    try {
        const form = new FormData();
        form.append('image', file);
        if (props.tempUploadId) {
            form.append('temp_upload_id', String(props.tempUploadId));
        }

        const response = await axios.post(store.url(), form, {
            headers: {
                'Content-Type': 'multipart/form-data',
                Accept: 'application/json',
            },
        });

        const { temp_upload_id, media } = response.data as {
            temp_upload_id: number;
            media: { id: number; url: string; name: string };
        };

        emit('update:tempUploadId', temp_upload_id);

        const idx = images.value.findIndex((i) => i.key === item.key);
        if (idx !== -1) {
            images.value[idx] = {
                ...images.value[idx],
                id: media.id,
                url: media.url,
                name: media.name,
                uploading: false,
            };
        }

        if (!props.mainMediaId) {
            setMain(media.id);
        } else {
            syncToParent();
        }
    } catch (error) {
        images.value = images.value.filter((i) => i.key !== item.key);

        if (axios.isAxiosError(error)) {
            if (error.response?.status === 419) {
                uploadError.value =
                    'Your session expired. Please refresh the page and try again.';
                syncToParent();
                return;
            }

            const validationMessage =
                error.response?.data?.errors?.image?.[0] ??
                error.response?.data?.message ??
                null;

            if (typeof validationMessage === 'string' && validationMessage) {
                if (validationMessage === 'The image failed to upload.') {
                    uploadError.value =
                        'Upload failed before reaching Laravel. This usually means your PHP `upload_max_filesize` or `post_max_size` is too low for this file. Try a smaller image, or increase those limits and refresh.';
                } else {
                    uploadError.value = validationMessage;
                }
                syncToParent();
                return;
            }
        }

        uploadError.value = 'Upload failed. Please try again.';
        syncToParent();
    } finally {
        URL.revokeObjectURL(localUrl);
    }
}
</script>

<template>
    <div class="grid gap-2">
        <div class="flex items-start justify-between gap-4">
            <div class="grid gap-1">
                <p class="text-sm font-medium">Images</p>
                <p class="text-sm text-muted-foreground">
                    Add up to {{ max }} images. Pick a main image (shown first).
                </p>
            </div>

            <div
                v-if="images.length"
                class="text-sm text-muted-foreground tabular-nums"
            >
                {{ images.length }}/{{ max }}
            </div>
        </div>

        <input
            ref="fileInput"
            type="file"
            accept="image/*"
            class="hidden"
            @change="onFileChange"
        />

        <button
            type="button"
            class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="!canAddMore || isUploading"
            @click="openPicker"
        >
            <span class="flex items-center gap-2">
                <ImagePlus class="h-4 w-4" />
                <span v-if="!images.length">Select an image</span>
                <span v-else-if="canAddMore">Add another image</span>
                <span v-else>Maximum images reached</span>
            </span>

            <span class="text-xs text-muted-foreground">
                {{ isUploading ? 'Uploading…' : 'Browse' }}
            </span>
        </button>

        <InputError :message="props.error" />
        <InputError :message="uploadError ?? undefined" />

        <div v-if="images.length" class="grid gap-3">
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                <div
                    v-for="item in images"
                    :key="item.key"
                    class="group relative overflow-hidden rounded-md border border-input bg-muted"
                >
                    <img
                        :src="item.url"
                        :alt="item.name"
                        class="h-28 w-full object-cover"
                    />

                    <div
                        v-if="item.uploading"
                        class="absolute inset-0 grid place-items-center bg-black/40 text-xs font-medium text-white"
                    >
                        Uploading…
                    </div>

                    <div class="absolute left-2 top-2 flex items-center gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 rounded-full bg-black/60 px-2 py-1 text-xs font-medium text-white backdrop-blur-sm transition hover:bg-black/70"
                            :disabled="!item.id || item.uploading"
                            @click="item.id ? setMain(item.id) : null"
                        >
                            <Star
                                class="h-3.5 w-3.5"
                                :class="
                                    item.id && item.id === mainMediaId
                                        ? 'fill-current'
                                        : ''
                                "
                            />
                            <span>
                                {{
                                    item.id && item.id === mainMediaId
                                        ? 'Main'
                                        : 'Set main'
                                }}
                            </span>
                        </button>
                    </div>

                    <button
                        type="button"
                        class="absolute right-2 top-2 inline-flex h-8 w-8 items-center justify-center rounded-md bg-black/60 text-white opacity-100 backdrop-blur-sm transition hover:bg-black/70 sm:opacity-0 sm:group-hover:opacity-100"
                        :disabled="item.uploading"
                        @click="removeImage(item)"
                        aria-label="Remove image"
                    >
                        <Trash2 class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <p class="text-xs text-muted-foreground">
                Tip: if you change the main image, it will move to the first
                position.
            </p>
        </div>
    </div>
</template>
