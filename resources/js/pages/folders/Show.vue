<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Download,
    Eye,
    FileText,
    Files,
    Folder,
    HardDrive,
    ScanLine,
    Trash2,
    Upload,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    destroy as destroyDocument,
    download as downloadDocument,
    preview as previewDocument,
    store as storeDocument,
} from '@/actions/App/Http/Controllers/DocumentController';
import { index as foldersIndex } from '@/routes/folders';

interface DocRow {
    id: number;
    title: string;
    original_name: string;
    size: number;
    source: string;
    uploaded_by: number | null;
    uploader_name: string | null;
    created_at: string;
}

const props = defineProps<{
    folder: { id: number; name: string };
    documents: DocRow[];
}>();

defineOptions({
    layout: (pageProps: { folder: { id: number; name: string } }) => ({
        breadcrumbs: [
            { title: 'Folders', href: '/folders' },
            {
                title: pageProps.folder.name,
                href: `/folders/${pageProps.folder.id}`,
            },
        ],
    }),
});

const page = usePage();
const currentUser = computed(() => (page.props.auth as any)?.user);
const isSuperAdmin = computed(() => currentUser.value?.role === 'super_admin');

const fileInput = ref<HTMLInputElement | null>(null);
const uploadOpen = ref(false);
const deleteTarget = ref<DocRow | null>(null);
const deletingDocument = ref(false);

const form = useForm({
    title: '',
    file: null as File | null,
});

const totalSize = computed(() =>
    props.documents.reduce((total, document) => total + document.size, 0),
);
const scannerUploads = computed(
    () =>
        props.documents.filter((document) => document.source === 'scanner')
            .length,
);

function onUploadOpenChange(open: boolean) {
    uploadOpen.value = open;

    if (!open) {
        form.reset();
        form.clearErrors();
        if (fileInput.value) {
            fileInput.value.value = '';
        }
    }
}

function onFileChange(e: Event) {
    form.file = (e.target as HTMLInputElement).files?.[0] ?? null;
}

function upload() {
    form.post(storeDocument.url(props.folder.id), {
        preserveScroll: true,
        onSuccess: () => {
            onUploadOpenChange(false);
        },
    });
}

function canDelete(doc: DocRow) {
    return isSuperAdmin.value || doc.uploaded_by === currentUser.value?.id;
}

function remove(doc: DocRow) {
    deleteTarget.value = doc;
}

function deleteSelectedDocument() {
    if (!deleteTarget.value) {
        return;
    }

    deletingDocument.value = true;
    router.delete(destroyDocument.url(deleteTarget.value.id), {
        preserveScroll: true,
        onFinish: () => {
            deletingDocument.value = false;
            deleteTarget.value = null;
        },
    });
}

function formatSize(bytes: number) {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
}
</script>

<template>
    <Head :title="folder.name" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex min-w-0 items-center gap-3">
                <div
                    class="flex size-11 shrink-0 items-center justify-center rounded-lg border bg-muted/40"
                >
                    <Folder class="size-5 text-muted-foreground" />
                </div>
                <div class="min-w-0 space-y-1">
                    <p class="text-sm text-muted-foreground">Folder</p>
                    <h1 class="truncate text-2xl font-semibold tracking-tight">
                        {{ folder.name }}
                    </h1>
                </div>
            </div>
            <div class="grid w-full grid-cols-2 gap-2 sm:flex sm:w-auto">
                <Button variant="outline" class="w-full sm:w-auto" as-child>
                    <Link :href="foldersIndex.url()">
                        <ArrowLeft class="size-4" />
                        All folders
                    </Link>
                </Button>
                <Button class="w-full sm:w-auto" @click="uploadOpen = true">
                    <Upload class="size-4" />
                    Upload file
                </Button>
            </div>
        </header>

        <section class="grid grid-cols-3 gap-2 sm:gap-3">
            <Card>
                <CardContent
                    class="flex h-full min-w-0 flex-col items-start gap-2 p-3 sm:flex-row sm:items-center sm:gap-3 sm:p-4"
                >
                    <div
                        class="flex size-8 shrink-0 items-center justify-center rounded-md bg-muted/60 sm:size-9"
                    >
                        <Files class="size-4 text-muted-foreground" />
                    </div>
                    <div class="min-w-0">
                        <p
                            class="truncate text-lg font-semibold tabular-nums sm:text-xl"
                        >
                            {{ documents.length }}
                        </p>
                        <p
                            class="text-[10px] leading-tight text-muted-foreground sm:text-xs"
                        >
                            {{ documents.length === 1 ? 'File' : 'Files' }}
                        </p>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent
                    class="flex h-full min-w-0 flex-col items-start gap-2 p-3 sm:flex-row sm:items-center sm:gap-3 sm:p-4"
                >
                    <div
                        class="flex size-8 shrink-0 items-center justify-center rounded-md bg-muted/60 sm:size-9"
                    >
                        <HardDrive class="size-4 text-muted-foreground" />
                    </div>
                    <div class="min-w-0">
                        <p
                            class="truncate text-lg font-semibold tabular-nums sm:text-xl"
                        >
                            {{ formatSize(totalSize) }}
                        </p>
                        <p
                            class="text-[10px] leading-tight text-muted-foreground sm:text-xs"
                        >
                            Total size
                        </p>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent
                    class="flex h-full min-w-0 flex-col items-start gap-2 p-3 sm:flex-row sm:items-center sm:gap-3 sm:p-4"
                >
                    <div
                        class="flex size-8 shrink-0 items-center justify-center rounded-md bg-muted/60 sm:size-9"
                    >
                        <ScanLine class="size-4 text-muted-foreground" />
                    </div>
                    <div class="min-w-0">
                        <p
                            class="truncate text-lg font-semibold tabular-nums sm:text-xl"
                        >
                            {{ scannerUploads }}
                        </p>
                        <p
                            class="text-[10px] leading-tight break-words text-muted-foreground sm:text-xs"
                        >
                            Scanner uploads
                        </p>
                    </div>
                </CardContent>
            </Card>
        </section>

        <Card class="overflow-hidden">
            <CardHeader class="border-b px-4 py-4 sm:px-5">
                <CardTitle class="text-base">Documents</CardTitle>
                <CardDescription
                    >Files stored in {{ folder.name }}.</CardDescription
                >
            </CardHeader>
            <CardContent
                v-if="documents.length === 0"
                class="flex flex-col items-center gap-3 px-6 py-14 text-center"
            >
                <div
                    class="flex size-11 items-center justify-center rounded-lg border bg-muted/40"
                >
                    <FileText class="size-5 text-muted-foreground" />
                </div>
                <div class="space-y-1">
                    <p class="font-medium">No files in this folder</p>
                    <p class="text-sm text-muted-foreground">
                        Upload a PDF or image to get started.
                    </p>
                </div>
                <Button variant="outline" size="sm" @click="uploadOpen = true">
                    <Upload class="size-4" />
                    Upload file
                </Button>
            </CardContent>
            <CardContent v-else class="p-0">
                <div class="divide-y xl:hidden">
                    <article
                        v-for="doc in documents"
                        :key="doc.id"
                        class="space-y-3 p-4"
                    >
                        <div class="flex min-w-0 items-start gap-3">
                            <div
                                class="flex size-9 shrink-0 items-center justify-center rounded-md border bg-background"
                            >
                                <FileText
                                    class="size-4 text-muted-foreground"
                                />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium break-words">
                                    {{ doc.title }}
                                </p>
                                <p
                                    class="mt-0.5 text-xs break-all text-muted-foreground"
                                >
                                    {{ doc.original_name }}
                                </p>
                            </div>
                        </div>
                        <dl class="grid grid-cols-2 gap-x-3 gap-y-2 text-xs">
                            <div class="min-w-0">
                                <dt class="text-muted-foreground">Size</dt>
                                <dd class="mt-0.5 tabular-nums">
                                    {{ formatSize(doc.size) }}
                                </dd>
                            </div>
                            <div class="min-w-0">
                                <dt class="text-muted-foreground">Source</dt>
                                <dd class="mt-0.5">
                                    {{
                                        doc.source === 'scanner'
                                            ? 'Scanner'
                                            : 'Manual'
                                    }}
                                </dd>
                            </div>
                            <div class="min-w-0">
                                <dt class="text-muted-foreground">
                                    Uploaded by
                                </dt>
                                <dd class="mt-0.5 truncate">
                                    {{ doc.uploader_name ?? '-' }}
                                </dd>
                            </div>
                            <div class="min-w-0">
                                <dt class="text-muted-foreground">
                                    Date added
                                </dt>
                                <dd class="mt-0.5 break-words">
                                    {{ doc.created_at }}
                                </dd>
                            </div>
                        </dl>
                        <div class="flex justify-end gap-1">
                            <a
                                :href="previewDocument.url(doc.id)"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex size-9 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                                :aria-label="`Preview ${doc.title}`"
                                title="Preview"
                            >
                                <Eye class="size-4" />
                            </a>
                            <a
                                :href="downloadDocument.url(doc.id)"
                                class="inline-flex size-9 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                                :aria-label="`Download ${doc.title}`"
                                title="Download"
                            >
                                <Download class="size-4" />
                            </a>
                            <Button
                                v-if="canDelete(doc)"
                                type="button"
                                variant="ghost"
                                size="icon"
                                class="text-destructive hover:text-destructive"
                                :aria-label="`Delete ${doc.title}`"
                                title="Delete file"
                                @click="remove(doc)"
                            >
                                <Trash2 class="size-4" />
                            </Button>
                        </div>
                    </article>
                </div>
                <div class="hidden overflow-x-auto xl:block">
                    <table class="w-full min-w-[58rem] text-left text-sm">
                        <thead
                            class="bg-muted/40 text-xs text-muted-foreground"
                        >
                            <tr>
                                <th class="px-5 py-3 font-medium">Document</th>
                                <th class="px-5 py-3 font-medium">Size</th>
                                <th class="px-5 py-3 font-medium">Source</th>
                                <th class="px-5 py-3 font-medium">
                                    Uploaded by
                                </th>
                                <th class="px-5 py-3 font-medium">
                                    Date added
                                </th>
                                <th class="px-5 py-3 text-right font-medium">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="doc in documents"
                                :key="doc.id"
                                class="border-t transition-colors hover:bg-muted/30"
                            >
                                <td class="px-5 py-3.5">
                                    <div
                                        class="flex min-w-0 items-center gap-3"
                                    >
                                        <div
                                            class="flex size-9 shrink-0 items-center justify-center rounded-md border bg-background"
                                        >
                                            <FileText
                                                class="size-4 text-muted-foreground"
                                            />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate font-medium">
                                                {{ doc.title }}
                                            </p>
                                            <p
                                                class="truncate text-xs text-muted-foreground"
                                            >
                                                {{ doc.original_name }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td
                                    class="px-5 py-3.5 text-muted-foreground tabular-nums"
                                >
                                    {{ formatSize(doc.size) }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <span
                                        class="inline-flex rounded-md border px-2 py-1 text-xs font-medium"
                                    >
                                        {{
                                            doc.source === 'scanner'
                                                ? 'Scanner'
                                                : 'Manual'
                                        }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-muted-foreground">
                                    {{ doc.uploader_name ?? '-' }}
                                </td>
                                <td
                                    class="px-5 py-3.5 whitespace-nowrap text-muted-foreground"
                                >
                                    {{ doc.created_at }}
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <a
                                            :href="previewDocument.url(doc.id)"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                                            :aria-label="`Preview ${doc.title}`"
                                            title="Preview"
                                        >
                                            <Eye class="size-4" />
                                        </a>
                                        <a
                                            :href="downloadDocument.url(doc.id)"
                                            class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                                            :aria-label="`Download ${doc.title}`"
                                            title="Download"
                                        >
                                            <Download class="size-4" />
                                        </a>
                                        <Button
                                            v-if="canDelete(doc)"
                                            type="button"
                                            variant="ghost"
                                            size="icon-sm"
                                            class="text-destructive hover:text-destructive"
                                            :aria-label="`Delete ${doc.title}`"
                                            title="Delete file"
                                            @click="remove(doc)"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>

    <Dialog :open="uploadOpen" @update:open="onUploadOpenChange">
        <DialogContent
            class="max-h-[90dvh] max-h-[90vh] overflow-y-auto sm:max-w-lg"
        >
            <form class="grid gap-5" @submit.prevent="upload">
                <DialogHeader>
                    <DialogTitle>Upload a document</DialogTitle>
                    <DialogDescription>
                        Add a PDF or image to {{ folder.name }}. Maximum file
                        size is 20 MB.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="document-title"
                        >Title
                        <span class="text-muted-foreground"
                            >(optional)</span
                        ></Label
                    >
                    <Input
                        id="document-title"
                        v-model="form.title"
                        placeholder="Use the file name if left blank"
                        :aria-invalid="!!form.errors.title"
                    />
                    <p
                        v-if="form.errors.title"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.title }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label for="document-file">File</Label>
                    <input
                        id="document-file"
                        ref="fileInput"
                        type="file"
                        accept=".pdf,.jpg,.jpeg,.png"
                        class="h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs file:mr-3 file:h-7 file:border-0 file:bg-transparent file:font-medium file:text-foreground"
                        :aria-invalid="!!form.errors.file"
                        @change="onFileChange"
                    />
                    <p v-if="form.file" class="text-xs text-muted-foreground">
                        Selected: {{ form.file.name }}
                    </p>
                    <p v-if="form.errors.file" class="text-sm text-destructive">
                        {{ form.errors.file }}
                    </p>
                </div>

                <DialogFooter>
                    <DialogClose as-child>
                        <Button
                            type="button"
                            variant="outline"
                            :disabled="form.processing"
                            >Cancel</Button
                        >
                    </DialogClose>
                    <Button
                        type="submit"
                        :disabled="form.processing || !form.file"
                    >
                        {{ form.processing ? 'Uploading…' : 'Upload file' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <Dialog
        :open="!!deleteTarget"
        @update:open="(open: boolean) => !open && (deleteTarget = null)"
    >
        <DialogContent class="max-h-[90dvh] max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>Delete "{{ deleteTarget?.title }}"?</DialogTitle>
                <DialogDescription>
                    This will permanently delete this file from
                    {{ folder.name }}. This action cannot be undone.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <DialogClose as-child>
                    <Button
                        type="button"
                        variant="outline"
                        class="w-full sm:w-auto"
                        :disabled="deletingDocument"
                    >
                        Cancel
                    </Button>
                </DialogClose>
                <Button
                    type="button"
                    class="w-full bg-destructive text-white hover:bg-destructive/90 sm:w-auto"
                    :disabled="deletingDocument"
                    @click.prevent="deleteSelectedDocument"
                >
                    {{ deletingDocument ? 'Deleting…' : 'Delete file' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
