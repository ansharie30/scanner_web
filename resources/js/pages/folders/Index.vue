<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    FileText,
    Folder,
    FolderPlus,
    MoreHorizontal,
    Pencil,
    Search,
    Trash2,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    destroy as destroyFolderRoute,
    show as showFolderRoute,
    store as storeFolderRoute,
    update as updateFolderRoute,
} from '@/actions/App/Http/Controllers/FolderController';

interface FolderRow {
    id: number;
    name: string;
    documents_count: number;
    children_count: number;
}

const props = defineProps<{
    folders: FolderRow[];
    totalFolders: number;
    totalDocuments: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Folders', href: '/folders' }],
    },
});

const page = usePage();
const isSuperAdmin = computed(
    () => (page.props.auth as any)?.user?.role === 'super_admin',
);
const search = ref('');
const visibleFolders = computed(() => {
    const query = search.value.trim().toLowerCase();

    return query
        ? props.folders.filter((folder) =>
              folder.name.toLowerCase().includes(query),
          )
        : props.folders;
});
/* ---------- Create ---------- */
const createOpen = ref(false);
const createForm = useForm({ name: '' });

function createFolder() {
    createForm.post(storeFolderRoute.url(), {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            createOpen.value = false;
        },
    });
}

function onCreateOpenChange(open: boolean) {
    createOpen.value = open;
    if (!open) {
        createForm.reset();
        createForm.clearErrors();
    }
}

/* ---------- Rename ---------- */
const renameTarget = ref<FolderRow | null>(null);
const renameForm = useForm({ name: '' });

function openRename(folder: FolderRow) {
    renameTarget.value = folder;
    renameForm.name = folder.name;
    renameForm.clearErrors();
}

function closeRename() {
    renameTarget.value = null;
    renameForm.reset();
    renameForm.clearErrors();
}

function renameFolder() {
    if (!renameTarget.value) return;
    renameForm.put(updateFolderRoute.url(renameTarget.value.id), {
        preserveScroll: true,
        onSuccess: closeRename,
    });
}

/* ---------- Delete ---------- */
const deleteTarget = ref<FolderRow | null>(null);
const deleting = ref(false);

function deleteFolder() {
    if (!deleteTarget.value) return;
    deleting.value = true;
    router.delete(destroyFolderRoute.url(deleteTarget.value.id), {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            deleteTarget.value = null;
        },
    });
}
</script>

<template>
    <Head title="Folders" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <!-- Header -->
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="space-y-1">
                <p class="text-sm font-medium text-muted-foreground">
                    Document library
                </p>
                <h1 class="text-2xl font-semibold tracking-tight">Folders</h1>
                <p class="text-sm text-muted-foreground">
                    Browse and organize your documents.
                </p>
            </div>

            <!-- Add folder dialog -->
            <Dialog :open="createOpen" @update:open="onCreateOpenChange">
                <DialogTrigger as-child>
                    <Button class="w-full sm:w-auto">
                        <FolderPlus class="size-4" />
                        Add folder
                    </Button>
                </DialogTrigger>

                <DialogContent
                    class="max-h-[90dvh] max-h-[90vh] overflow-y-auto sm:max-w-md"
                >
                    <form class="grid gap-4" @submit.prevent="createFolder">
                        <DialogHeader>
                            <DialogTitle>New folder</DialogTitle>
                            <DialogDescription>
                                Give your folder a name. You can rename it
                                later.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="folder-name">Name</Label>
                            <Input
                                id="folder-name"
                                v-model="createForm.name"
                                placeholder="e.g. Memo"
                                autocomplete="off"
                                autofocus
                                :aria-invalid="!!createForm.errors.name"
                            />
                            <p
                                v-if="createForm.errors.name"
                                class="text-sm text-destructive"
                            >
                                {{ createForm.errors.name }}
                            </p>
                        </div>

                        <DialogFooter class="gap-2 sm:gap-0">
                            <DialogClose as-child>
                                <Button
                                    type="button"
                                    variant="outline"
                                    class="w-full sm:w-auto"
                                    >Cancel</Button
                                >
                            </DialogClose>
                            <Button
                                type="submit"
                                class="w-full sm:w-auto"
                                :disabled="
                                    createForm.processing ||
                                    !createForm.name.trim()
                                "
                            >
                                Create folder
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>

        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div
                class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-muted-foreground"
            >
                <span
                    >{{ props.totalFolders }}
                    {{ props.totalFolders === 1 ? 'folder' : 'folders' }}</span
                >
                <span
                    >{{ props.totalDocuments }}
                    {{
                        props.totalDocuments === 1 ? 'document' : 'documents'
                    }}</span
                >
            </div>
            <div class="relative w-full sm:max-w-xs">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    class="pr-9 pl-9"
                    placeholder="Search folders"
                    aria-label="Search folders"
                />
                <Button
                    v-if="search"
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    class="absolute top-1/2 right-1 -translate-y-1/2"
                    aria-label="Clear folder search"
                    @click="search = ''"
                >
                    <X class="size-4" />
                </Button>
            </div>
        </div>

        <!-- Empty state -->
        <div
            v-if="props.folders.length === 0"
            class="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed px-4 py-12 text-center sm:px-6 sm:py-16"
        >
            <div
                class="flex size-12 items-center justify-center rounded-full bg-muted"
            >
                <Folder class="size-6 text-muted-foreground" />
            </div>
            <div class="space-y-1">
                <h2 class="font-medium">No folders yet</h2>
                <p class="text-sm text-muted-foreground">
                    Create your first folder to start organizing files.
                </p>
            </div>
            <Button variant="outline" size="sm" @click="createOpen = true">
                <FolderPlus class="size-4" />
                Add folder
            </Button>
        </div>

        <div
            v-else-if="visibleFolders.length === 0"
            class="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed px-4 py-10 text-center sm:px-6 sm:py-14"
        >
            <div
                class="flex size-12 items-center justify-center rounded-full bg-muted"
            >
                <Search class="size-5 text-muted-foreground" />
            </div>
            <div class="space-y-1">
                <h2 class="font-medium">No matching folders</h2>
                <p class="text-sm text-muted-foreground">
                    Try a different folder name.
                </p>
            </div>
            <Button variant="outline" size="sm" @click="search = ''"
                >Clear search</Button
            >
        </div>

        <!-- Folder grid -->
        <div
            v-else
            class="grid gap-3 sm:grid-cols-2 sm:gap-4 xl:grid-cols-3 2xl:grid-cols-4"
        >
            <Card
                v-for="folder in visibleFolders"
                :key="folder.id"
                class="group relative transition-colors hover:bg-muted/40"
            >
                <Link
                    :href="showFolderRoute.url(folder.id)"
                    class="block rounded-xl focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    <CardContent
                        class="flex items-center gap-3 p-4 sm:gap-4 sm:p-5"
                    >
                        <div
                            class="flex size-11 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                        >
                            <Folder class="size-5" />
                        </div>
                        <div class="min-w-0 flex-1 pr-8">
                            <div class="truncate leading-tight font-medium">
                                {{ folder.name }}
                            </div>
                            <div
                                class="mt-1 flex items-center gap-1.5 text-sm text-muted-foreground"
                            >
                                <FileText class="size-3.5" />
                                {{ folder.documents_count }}
                                {{
                                    folder.documents_count === 1
                                        ? 'file'
                                        : 'files'
                                }}
                            </div>
                            <div
                                v-if="folder.children_count > 0"
                                class="mt-1 text-xs text-muted-foreground"
                            >
                                {{ folder.children_count }}
                                {{
                                    folder.children_count === 1
                                        ? 'subfolder'
                                        : 'subfolders'
                                }}
                            </div>
                        </div>
                    </CardContent>
                </Link>

                <!-- Actions (super admin only) -->
                <DropdownMenu v-if="isSuperAdmin" :modal="false">
                    <DropdownMenuTrigger as-child>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="absolute top-3 right-3 size-8"
                            aria-label="Folder actions"
                        >
                            <MoreHorizontal class="size-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-40">
                        <DropdownMenuItem @select="openRename(folder)">
                            <Pencil class="size-4" />
                            Rename
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            class="text-destructive focus:text-destructive"
                            @select="deleteTarget = folder"
                        >
                            <Trash2 class="size-4" />
                            Delete
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </Card>
        </div>
    </div>

    <!-- Rename dialog -->
    <Dialog
        :open="!!renameTarget"
        @update:open="(open: boolean) => !open && closeRename()"
    >
        <DialogContent
            class="max-h-[90dvh] max-h-[90vh] overflow-y-auto sm:max-w-md"
        >
            <form class="grid gap-4" @submit.prevent="renameFolder">
                <DialogHeader>
                    <DialogTitle>Rename folder</DialogTitle>
                    <DialogDescription
                        >Enter a new name for this folder.</DialogDescription
                    >
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="rename-name">Name</Label>
                    <Input
                        id="rename-name"
                        v-model="renameForm.name"
                        autocomplete="off"
                        :aria-invalid="!!renameForm.errors.name"
                    />
                    <p
                        v-if="renameForm.errors.name"
                        class="text-sm text-destructive"
                    >
                        {{ renameForm.errors.name }}
                    </p>
                </div>

                <DialogFooter class="gap-2 sm:gap-0">
                    <Button
                        type="button"
                        variant="outline"
                        class="w-full sm:w-auto"
                        @click="closeRename"
                        >Cancel</Button
                    >
                    <Button
                        type="submit"
                        class="w-full sm:w-auto"
                        :disabled="
                            renameForm.processing ||
                            !renameForm.name.trim() ||
                            renameForm.name.trim() === renameTarget?.name
                        "
                    >
                        Save changes
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <!-- Delete confirmation -->
    <Dialog
        :open="!!deleteTarget"
        @update:open="(open: boolean) => !open && (deleteTarget = null)"
    >
        <DialogContent class="max-h-[90dvh] max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>Delete "{{ deleteTarget?.name }}"?</DialogTitle>
                <DialogDescription>
                    This will permanently delete the folder and all
                    {{ deleteTarget?.documents_count ?? 0 }} file(s) inside it.
                    This action cannot be undone.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <DialogClose as-child>
                    <Button
                        type="button"
                        variant="outline"
                        class="w-full sm:w-auto"
                        >Cancel</Button
                    >
                </DialogClose>
                <Button
                    type="button"
                    class="w-full bg-destructive text-white hover:bg-destructive/90 sm:w-auto"
                    :disabled="deleting"
                    @click.prevent="deleteFolder"
                >
                    Delete folder
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
