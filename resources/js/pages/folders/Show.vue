<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ChevronRight,
    Download,
    Eye,
    FileImage,
    FileSpreadsheet,
    FileText,
    FileType,
    Files,
    Folder,
    FolderOpen,
    FolderPlus,
    HardDrive,
    ScanLine,
    Search,
    Presentation,
    Trash2,
    Upload,
    X,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

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
    destroy as destroyFolder,
    storeSubfolder as storeSubfolderRoute,
} from '@/actions/App/Http/Controllers/FolderController';
import DatePicker from '@/components/ui/date-picker/DatePicker.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    destroy as destroyDocument,
    download as downloadDocument,
    preview as previewDocument,
    store as storeDocument,
} from '@/actions/App/Http/Controllers/DocumentController';
import { index as foldersIndex, show as showFolder } from '@/routes/folders';

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

interface PaginatedDocuments {
    data: DocRow[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

interface SubfolderRow {
    id: number;
    name: string;
    documents_count: number;
    size: number;
    source: string;
    created_by_name: string | null;
    created_at: string;
}

const documentTypeIcons = {
    pdf: { icon: FileText, color: 'text-red-600' },
    image: { icon: FileImage, color: 'text-violet-600' },
    word: { icon: FileType, color: 'text-blue-600' },
    spreadsheet: { icon: FileSpreadsheet, color: 'text-emerald-600' },
    presentation: { icon: Presentation, color: 'text-orange-600' },
    other: { icon: FileText, color: 'text-muted-foreground' },
};

function documentType(filename: string) {
    const extension = filename.split('.').pop()?.toLowerCase() ?? '';

    if (extension === 'pdf') return documentTypeIcons.pdf;
    if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'].includes(extension)) {
        return documentTypeIcons.image;
    }
    if (['doc', 'docx', 'odt', 'rtf'].includes(extension)) {
        return documentTypeIcons.word;
    }
    if (['xls', 'xlsx', 'csv', 'ods'].includes(extension)) {
        return documentTypeIcons.spreadsheet;
    }
    if (['ppt', 'pptx', 'odp'].includes(extension)) {
        return documentTypeIcons.presentation;
    }

    return documentTypeIcons.other;
}

const props = defineProps<{
    folder: {
        id: number;
        name: string;
        parent_id: number | null;
        parent: { id: number; name: string } | null;
    };
    subfolders: SubfolderRow[];
    documents: PaginatedDocuments;
    search: string;
    dateFrom: string;
    dateTo: string;
    perPage: number;
    totalDocuments: number;
    totalSize: number;
    scannerUploads: number;
}>();

defineOptions({
    layout: (pageProps: {
        folder: {
            id: number;
            name: string;
            parent_id: number | null;
            parent: { id: number; name: string } | null;
        };
    }) => ({
        breadcrumbs: [
            { title: 'Folders', href: foldersIndex.url() },
            ...(pageProps.folder.parent
                ? [
                      {
                          title: pageProps.folder.parent.name,
                          href: showFolder.url(pageProps.folder.parent.id),
                      },
                  ]
                : []),
            {
                title: pageProps.folder.name,
                href: showFolder.url(pageProps.folder.id),
            },
        ],
    }),
});

const page = usePage();
const currentUser = computed(() => (page.props.auth as any)?.user);
const isSuperAdmin = computed(() => currentUser.value?.role === 'super_admin');

const fileInput = ref<HTMLInputElement | null>(null);
const uploadOpen = ref(false);
const subfolderOpen = ref(false);
const subfolderDeleteTarget = ref<SubfolderRow | null>(null);
const deletingSubfolder = ref(false);
const deleteTarget = ref<DocRow | null>(null);
const deletingDocument = ref(false);
const searchInput = ref(props.search);
const dateFromInput = ref(props.dateFrom);
const dateToInput = ref(props.dateTo);
const perPageInput = ref(String(props.perPage));

const form = useForm({
    title: '',
    file: null as File | null,
});
const subfolderForm = useForm({ name: '' });

watch(
    () => [props.search, props.dateFrom, props.dateTo, props.perPage] as const,
    ([search, dateFrom, dateTo, perPage]) => {
        searchInput.value = search;
        dateFromInput.value = dateFrom;
        dateToInput.value = dateTo;
        perPageInput.value = String(perPage);
    },
);

function searchDocuments() {
    const query: Record<string, string | number> = {
        per_page: Number(perPageInput.value),
    };
    const search = searchInput.value.trim();

    if (search) {
        query.search = search;
    }

    if (dateFromInput.value) {
        query.date_from = dateFromInput.value;
    }

    if (dateToInput.value) {
        query.date_to = dateToInput.value;
    }

    router.get(showFolder.url(props.folder.id), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function clearSearch() {
    searchInput.value = '';
    searchDocuments();
}

function clearFilters() {
    searchInput.value = '';
    dateFromInput.value = '';
    dateToInput.value = '';
    perPageInput.value = '10';
    searchDocuments();
}

function pageUrl(page: number): string {
    const query: Record<string, string | number> = {
        per_page: props.perPage,
        page,
    };

    if (props.search) {
        query.search = props.search;
    }

    if (props.dateFrom) {
        query.date_from = props.dateFrom;
    }

    if (props.dateTo) {
        query.date_to = props.dateTo;
    }

    return showFolder.url(props.folder.id, {
        query,
    });
}

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

function onSubfolderDialogOpenChange(open: boolean) {
    subfolderOpen.value = open;

    if (!open) {
        subfolderForm.reset();
        subfolderForm.clearErrors();
    }
}

function createSubfolder() {
    subfolderForm.post(storeSubfolderRoute.url(props.folder.id), {
        preserveScroll: true,
        onSuccess: () => onSubfolderDialogOpenChange(false),
    });
}

function deleteSelectedSubfolder() {
    if (!subfolderDeleteTarget.value) {
        return;
    }

    deletingSubfolder.value = true;
    router.delete(destroyFolder.url(subfolderDeleteTarget.value.id), {
        preserveScroll: true,
        onFinish: () => {
            deletingSubfolder.value = false;
            subfolderDeleteTarget.value = null;
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
            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
                <Button variant="outline" class="w-full sm:w-auto" as-child>
                    <Link :href="foldersIndex.url()">
                        <ArrowLeft class="size-4" />
                        All folders
                    </Link>
                </Button>
                <div
                    class="grid w-full gap-2 sm:w-auto"
                    :class="
                        folder.parent_id === null
                            ? 'grid-cols-2'
                            : 'grid-cols-1'
                    "
                >
                    <Button
                        v-if="folder.parent_id === null"
                        variant="outline"
                        class="w-full sm:w-auto"
                        @click="subfolderOpen = true"
                    >
                        <FolderPlus class="size-4" />
                        New subfolder
                    </Button>
                    <Button class="w-full sm:w-auto" @click="uploadOpen = true">
                        <Upload class="size-4" />
                        Upload file
                    </Button>
                </div>
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
                            {{ totalDocuments }}
                        </p>
                        <p
                            class="text-[10px] leading-tight text-muted-foreground sm:text-xs"
                        >
                            {{ totalDocuments === 1 ? 'File' : 'Files' }}
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
            <CardHeader class="flex flex-col gap-4 border-b px-4 py-4 sm:px-5">
                <div class="space-y-1">
                    <CardTitle class="text-base">Contents</CardTitle>
                    <CardDescription
                        >Documents and subfolders in
                        {{ folder.name }}.</CardDescription
                    >
                </div>
                <form
                    v-if="totalDocuments > 0"
                    class="grid w-full gap-3 sm:grid-cols-2 xl:grid-cols-[minmax(12rem,1fr)_10rem_10rem_9rem_auto_auto] xl:items-end"
                    @submit.prevent="searchDocuments"
                >
                    <div
                        class="grid min-w-0 gap-1.5 sm:col-span-2 xl:col-span-1"
                    >
                        <Label for="document-search">Search</Label>
                        <div class="relative">
                            <Search
                                class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                id="document-search"
                                v-model="searchInput"
                                class="pr-9 pl-9"
                                placeholder="Title, filename, uploader, source"
                                maxlength="255"
                            />
                            <Button
                                v-if="searchInput"
                                type="button"
                                variant="ghost"
                                size="icon-sm"
                                class="absolute top-1/2 right-1 -translate-y-1/2"
                                aria-label="Clear document search"
                                @click="clearSearch"
                            >
                                <X class="size-4" />
                            </Button>
                        </div>
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="date-from">Date from</Label>
                        <DatePicker
                            id="date-from"
                            v-model="dateFromInput"
                            label="Date from"
                            :max-date="dateToInput || undefined"
                        />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="date-to">Date to</Label>
                        <DatePicker
                            id="date-to"
                            v-model="dateToInput"
                            label="Date to"
                            :min-date="dateFromInput || undefined"
                        />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="documents-per-page">Rows per page</Label>
                        <Select v-model="perPageInput">
                            <SelectTrigger
                                id="documents-per-page"
                                class="w-full"
                            >
                                <SelectValue
                                    :placeholder="`${perPage} per page`"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="10">10 per page</SelectItem>
                                <SelectItem value="20">20 per page</SelectItem>
                                <SelectItem value="50">50 per page</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <Button type="submit" class="w-full xl:w-auto">
                        Apply filters
                    </Button>
                    <Button
                        v-if="search || dateFrom || dateTo || perPage !== 10"
                        type="button"
                        variant="outline"
                        class="w-full xl:w-auto"
                        @click="clearFilters"
                    >
                        Clear
                    </Button>
                </form>
            </CardHeader>
            <CardContent
                v-if="totalDocuments === 0 && subfolders.length === 0"
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
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[58rem] text-left text-sm">
                        <thead
                            class="bg-muted/40 text-xs text-muted-foreground"
                        >
                            <tr>
                                <th class="px-5 py-3 font-medium">Name</th>
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
                                v-for="subfolder in subfolders"
                                :key="`folder-${subfolder.id}`"
                                class="border-t transition-colors hover:bg-muted/30"
                            >
                                <td class="px-5 py-3.5">
                                    <Link
                                        :href="showFolder.url(subfolder.id)"
                                        class="flex min-w-0 items-center gap-3 font-medium hover:text-primary"
                                    >
                                        <span
                                            class="flex size-9 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary"
                                        >
                                            <Folder class="size-4" />
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block truncate">{{
                                                subfolder.name
                                            }}</span>
                                            <span
                                                class="mt-1 block text-xs font-normal text-muted-foreground"
                                            >
                                                Subfolder ·
                                                {{ subfolder.documents_count }}
                                                {{
                                                    subfolder.documents_count ===
                                                    1
                                                        ? 'file'
                                                        : 'files'
                                                }}
                                            </span>
                                        </span>
                                        <ChevronRight
                                            class="ml-auto size-4 shrink-0 text-muted-foreground"
                                        />
                                    </Link>
                                </td>
                                <td
                                    class="px-5 py-3.5 whitespace-nowrap text-muted-foreground tabular-nums"
                                >
                                    {{ formatSize(subfolder.size) }}
                                </td>
                                <td class="px-5 py-3.5 text-muted-foreground">
                                    {{
                                        subfolder.source === 'scanner'
                                            ? 'Scanner'
                                            : 'Manual'
                                    }}
                                </td>
                                <td class="px-5 py-3.5 text-muted-foreground">
                                    {{ subfolder.created_by_name ?? '' }}
                                </td>
                                <td
                                    class="px-5 py-3.5 whitespace-nowrap text-muted-foreground"
                                >
                                    {{ subfolder.created_at }}
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon-sm"
                                            as-child
                                        >
                                            <Link
                                                :href="
                                                    showFolder.url(subfolder.id)
                                                "
                                                :aria-label="`Open ${subfolder.name}`"
                                                title="Open subfolder"
                                            >
                                                <FolderOpen class="size-4" />
                                            </Link>
                                        </Button>
                                        <Button
                                            v-if="isSuperAdmin"
                                            type="button"
                                            variant="ghost"
                                            size="icon-sm"
                                            class="text-destructive hover:text-destructive"
                                            :aria-label="`Delete ${subfolder.name}`"
                                            title="Delete subfolder"
                                            @click="
                                                subfolderDeleteTarget =
                                                    subfolder
                                            "
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr
                                v-for="doc in documents.data"
                                :key="`document-${doc.id}`"
                                class="border-t transition-colors hover:bg-muted/30"
                            >
                                <td class="px-5 py-3.5">
                                    <div
                                        class="flex min-w-0 items-center gap-3"
                                    >
                                        <span
                                            class="flex size-9 shrink-0 items-center justify-center rounded-md border bg-background"
                                        >
                                            <component
                                                :is="
                                                    documentType(
                                                        doc.original_name,
                                                    ).icon
                                                "
                                                class="size-4"
                                                :class="
                                                    documentType(
                                                        doc.original_name,
                                                    ).color
                                                "
                                            />
                                        </span>
                                        <span class="min-w-0">
                                            <span
                                                class="block truncate font-medium"
                                                >{{ doc.title }}</span
                                            >
                                            <span
                                                class="mt-1 block truncate text-xs text-muted-foreground"
                                                >{{ doc.original_name }}</span
                                            >
                                        </span>
                                    </div>
                                </td>
                                <td
                                    class="px-5 py-3.5 whitespace-nowrap text-muted-foreground tabular-nums"
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
                                    {{ doc.uploader_name ?? '' }}
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
                            <tr v-if="documents.total === 0">
                                <td colspan="6" class="px-5 py-12 text-center">
                                    <div
                                        class="flex flex-col items-center gap-3"
                                    >
                                        <Search
                                            class="size-7 text-muted-foreground"
                                        />
                                        <div class="space-y-1">
                                            <p class="font-medium">
                                                No matching documents
                                            </p>
                                            <p
                                                class="text-sm text-muted-foreground"
                                            >
                                                Try another title, filename,
                                                uploader, or source.
                                            </p>
                                        </div>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            @click="clearFilters"
                                        >
                                            Clear filters
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <nav
                    v-if="documents.total > 0"
                    aria-label="Document pages"
                    class="flex flex-wrap items-center justify-between gap-3 border-t px-4 py-3 sm:px-5"
                >
                    <p class="text-sm text-muted-foreground">
                        Showing {{ documents.from }}–{{ documents.to }} of
                        {{ documents.total }} files
                    </p>
                    <div class="flex items-center gap-2">
                        <Link
                            v-if="documents.prev_page_url"
                            :href="pageUrl(documents.current_page - 1)"
                            preserve-scroll
                            preserve-state
                            class="rounded-md border border-border px-3 py-1.5 text-sm font-medium hover:bg-accent"
                        >
                            Previous
                        </Link>
                        <span
                            v-else
                            aria-disabled="true"
                            class="rounded-md border border-border px-3 py-1.5 text-sm text-muted-foreground opacity-50"
                        >
                            Previous
                        </span>
                        <span
                            class="px-1 text-sm text-muted-foreground tabular-nums"
                        >
                            {{ documents.current_page }} /
                            {{ documents.last_page }}
                        </span>
                        <Link
                            v-if="documents.next_page_url"
                            :href="pageUrl(documents.current_page + 1)"
                            preserve-scroll
                            preserve-state
                            class="rounded-md border border-border px-3 py-1.5 text-sm font-medium hover:bg-accent"
                        >
                            Next
                        </Link>
                        <span
                            v-else
                            aria-disabled="true"
                            class="rounded-md border border-border px-3 py-1.5 text-sm text-muted-foreground opacity-50"
                        >
                            Next
                        </span>
                    </div>
                </nav>
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

    <Dialog :open="subfolderOpen" @update:open="onSubfolderDialogOpenChange">
        <DialogContent
            class="max-h-[90dvh] max-h-[90vh] overflow-y-auto sm:max-w-md"
        >
            <form class="grid gap-4" @submit.prevent="createSubfolder">
                <DialogHeader>
                    <DialogTitle>New subfolder</DialogTitle>
                    <DialogDescription>
                        Create a folder inside {{ folder.name }}.
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-2">
                    <Label for="subfolder-name">Name</Label>
                    <Input
                        id="subfolder-name"
                        v-model="subfolderForm.name"
                        autocomplete="off"
                        autofocus
                        :aria-invalid="!!subfolderForm.errors.name"
                    />
                    <p
                        v-if="subfolderForm.errors.name"
                        class="text-sm text-destructive"
                    >
                        {{ subfolderForm.errors.name }}
                    </p>
                </div>
                <DialogFooter class="gap-2 sm:gap-0">
                    <DialogClose as-child>
                        <Button
                            type="button"
                            variant="outline"
                            class="w-full sm:w-auto"
                        >
                            Cancel
                        </Button>
                    </DialogClose>
                    <Button
                        type="submit"
                        class="w-full sm:w-auto"
                        :disabled="
                            subfolderForm.processing ||
                            !subfolderForm.name.trim()
                        "
                    >
                        {{
                            subfolderForm.processing
                                ? 'Creating…'
                                : 'Create subfolder'
                        }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <Dialog
        :open="!!subfolderDeleteTarget"
        @update:open="
            (open: boolean) => !open && (subfolderDeleteTarget = null)
        "
    >
        <DialogContent class="max-h-[90dvh] max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle
                    >Delete "{{ subfolderDeleteTarget?.name }}"?</DialogTitle
                >
                <DialogDescription>
                    This permanently deletes the subfolder and its
                    {{ subfolderDeleteTarget?.documents_count ?? 0 }} files.
                    This action cannot be undone.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <DialogClose as-child>
                    <Button
                        type="button"
                        variant="outline"
                        class="w-full sm:w-auto"
                        :disabled="deletingSubfolder"
                    >
                        Cancel
                    </Button>
                </DialogClose>
                <Button
                    type="button"
                    class="w-full bg-destructive text-white hover:bg-destructive/90 sm:w-auto"
                    :disabled="deletingSubfolder"
                    @click.prevent="deleteSelectedSubfolder"
                >
                    {{ deletingSubfolder ? 'Deleting…' : 'Delete subfolder' }}
                </Button>
            </DialogFooter>
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
