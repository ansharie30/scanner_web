<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    FileText,
    Folder,
    FolderOpen,
    HardDrive,
    ScanLine,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { dashboard } from '@/routes';
import { index as foldersIndex, show as showFolder } from '@/routes/folders';

interface DashboardStats {
    total_documents: number;
    total_folders: number;
    storage_bytes: number;
    scanner_uploads: number;
}

interface RecentDocument {
    id: number;
    folder_id: number;
    folder_name: string;
    title: string;
    original_name: string;
    size: number;
    source: string;
    created_at: string;
}

interface DashboardFolder {
    id: number;
    name: string;
    documents_count: number;
}

const props = defineProps<{
    stats: DashboardStats;
    recentDocuments: RecentDocument[];
    topFolders: DashboardFolder[];
}>();

const summaryCards = computed(() => [
    {
        label: 'Documents',
        value: props.stats.total_documents.toLocaleString(),
        detail: 'in the library',
        icon: FileText,
    },
    {
        label: 'Folders',
        value: props.stats.total_folders.toLocaleString(),
        detail: 'across the workspace',
        icon: Folder,
    },
    {
        label: 'Storage used',
        value: formatFileSize(props.stats.storage_bytes),
        detail: 'total document size',
        icon: HardDrive,
    },
    {
        label: 'Scanner uploads',
        value: props.stats.scanner_uploads.toLocaleString(),
        detail: 'captured from scanner',
        icon: ScanLine,
    },
]);

function formatFileSize(bytes: number): string {
    if (bytes === 0) {
        return '0 B';
    }

    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    const unitIndex = Math.min(
        Math.floor(Math.log(bytes) / Math.log(1024)),
        units.length - 1,
    );
    const value = bytes / 1024 ** unitIndex;

    return `${value.toFixed(unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
}

function formatDate(value: string): string {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
    }).format(new Date(value));
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-muted-foreground">
                    Document library
                </p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight">
                    Dashboard
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    A current view of your documents, folders, and storage.
                </p>
            </div>
            <Link
                :href="foldersIndex.url()"
                class="inline-flex items-center gap-2 rounded-md border border-border bg-background px-3 py-2 text-sm font-medium transition-colors hover:bg-accent"
            >
                <FolderOpen class="size-4" />
                Browse folders
            </Link>
        </header>

        <section
            aria-label="Library summary"
            class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4"
        >
            <article
                v-for="card in summaryCards"
                :key="card.label"
                class="min-w-0 rounded-md border border-border bg-card p-4"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm text-muted-foreground">
                            {{ card.label }}
                        </p>
                        <p
                            class="mt-2 truncate text-2xl font-semibold tabular-nums"
                        >
                            {{ card.value }}
                        </p>
                    </div>
                    <component
                        :is="card.icon"
                        class="size-5 shrink-0 text-primary"
                    />
                </div>
                <p class="mt-2 text-xs text-muted-foreground">
                    {{ card.detail }}
                </p>
            </article>
        </section>

        <div
            class="grid min-w-0 gap-4 xl:grid-cols-[minmax(0,1.6fr)_minmax(18rem,0.9fr)]"
        >
            <section class="min-w-0 rounded-md border border-border bg-card">
                <div
                    class="flex items-center justify-between gap-4 border-b border-border px-4 py-4 sm:px-5"
                >
                    <div>
                        <h2 class="font-semibold">Recent documents</h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            The latest files added to the library.
                        </p>
                    </div>
                    <Link
                        :href="foldersIndex.url()"
                        class="inline-flex shrink-0 items-center gap-1 text-sm font-medium text-primary hover:underline"
                    >
                        All folders
                        <ArrowUpRight class="size-4" />
                    </Link>
                </div>

                <div
                    v-if="props.recentDocuments.length"
                    class="divide-y divide-border"
                >
                    <Link
                        v-for="document in props.recentDocuments"
                        :key="document.id"
                        :href="showFolder.url(document.folder_id)"
                        class="flex min-w-0 items-center gap-3 px-4 py-3 transition-colors hover:bg-muted/40 sm:px-5"
                    >
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary"
                        >
                            <FileText class="size-4" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium">{{
                                document.title
                            }}</span>
                            <span
                                class="mt-1 block truncate text-xs text-muted-foreground"
                            >
                                {{ document.folder_name }} ·
                                {{
                                    document.source === 'scanner'
                                        ? 'Scanner'
                                        : 'Manual upload'
                                }}
                            </span>
                        </span>
                        <span
                            class="hidden shrink-0 text-right text-xs text-muted-foreground sm:block"
                        >
                            <span class="block">{{
                                formatDate(document.created_at)
                            }}</span>
                            <span class="mt-1 block">{{
                                formatFileSize(document.size)
                            }}</span>
                        </span>
                    </Link>
                </div>
                <div v-else class="px-5 py-12 text-center">
                    <FileText class="mx-auto size-8 text-muted-foreground/60" />
                    <h3 class="mt-3 text-sm font-medium">No documents yet</h3>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Upload a file from a folder to see it here.
                    </p>
                    <Link
                        :href="foldersIndex.url()"
                        class="mt-4 inline-flex text-sm font-medium text-primary hover:underline"
                    >
                        Browse folders
                    </Link>
                </div>
            </section>

            <section class="min-w-0 rounded-md border border-border bg-card">
                <div class="border-b border-border px-4 py-4 sm:px-5">
                    <h2 class="font-semibold">Folders</h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Sorted by document count.
                    </p>
                </div>
                <div
                    v-if="props.topFolders.length"
                    class="divide-y divide-border px-4 sm:px-5"
                >
                    <Link
                        v-for="folder in props.topFolders"
                        :key="folder.id"
                        :href="showFolder.url(folder.id)"
                        class="flex items-center gap-3 py-3 transition-colors hover:text-primary"
                    >
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-md bg-muted text-muted-foreground"
                        >
                            <Folder class="size-4" />
                        </span>
                        <span
                            class="min-w-0 flex-1 truncate text-sm font-medium"
                            >{{ folder.name }}</span
                        >
                        <span
                            class="shrink-0 text-xs text-muted-foreground tabular-nums"
                        >
                            {{ folder.documents_count }}
                            {{
                                folder.documents_count === 1 ? 'file' : 'files'
                            }}
                        </span>
                    </Link>
                </div>
                <div v-else class="px-5 py-12 text-center">
                    <Folder class="mx-auto size-8 text-muted-foreground/60" />
                    <h3 class="mt-3 text-sm font-medium">No folders yet</h3>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Your folders will appear here.
                    </p>
                </div>
                <div class="border-t border-border px-4 py-3 sm:px-5">
                    <Link
                        :href="foldersIndex.url()"
                        class="inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline"
                    >
                        Manage folders
                        <ArrowUpRight class="size-4" />
                    </Link>
                </div>
            </section>
        </div>
    </div>
</template>
