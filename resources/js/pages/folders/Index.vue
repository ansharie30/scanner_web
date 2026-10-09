<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface FolderRow {
    id: number;
    name: string;
    documents_count: number;
}

defineProps<{ folders: FolderRow[] }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Folders', href: '/folders' }],
    },
});

const page = usePage();
const isSuperAdmin = computed(() => (page.props.auth as any)?.user?.role === 'super_admin');
const nameError = computed(() => (page.props.errors as any)?.name);

const form = useForm({ name: '' });

function createFolder() {
    form.post('/folders', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

function rename(folder: FolderRow) {
    const name = prompt('New folder name', folder.name);
    if (name && name.trim() && name.trim() !== folder.name) {
        router.put(`/folders/${folder.id}`, { name: name.trim() }, { preserveScroll: true });
    }
}

function remove(folder: FolderRow) {
    if (confirm(`Delete folder "${folder.name}" and all its files?`)) {
        router.delete(`/folders/${folder.id}`);
    }
}
</script>

<template>
    <Head title="Folders" />

    <div class="p-4">
            <h1 class="mb-4 text-xl font-semibold">Folders</h1>

            <form class="mb-2 flex max-w-lg gap-2" @submit.prevent="createFolder">
                <input
                    v-model="form.name"
                    type="text"
                    placeholder="New folder name (e.g. Memo)"
                    class="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                />
                <button
                    type="submit"
                    :disabled="form.processing || !form.name.trim()"
                    class="rounded-md bg-black px-4 py-2 text-sm text-white disabled:opacity-50 dark:bg-white dark:text-black"
                >
                    Add folder
                </button>
            </form>
            <p v-if="nameError" class="mb-2 text-sm text-red-600">{{ nameError }}</p>

            <p v-if="folders.length === 0" class="mt-6 text-sm text-muted-foreground">
                No folders yet. Create your first one above.
            </p>

            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div v-for="folder in folders" :key="folder.id" class="rounded-lg border p-4">
                    <Link :href="`/folders/${folder.id}`" class="block">
                        <div class="text-lg font-medium">📁 {{ folder.name }}</div>
                        <div class="text-sm text-muted-foreground">
                            {{ folder.documents_count }} file(s)
                        </div>
                    </Link>

                    <div v-if="isSuperAdmin" class="mt-3 flex gap-3 text-sm">
                        <button type="button" class="underline" @click="rename(folder)">Rename</button>
                        <button type="button" class="text-red-600 underline" @click="remove(folder)">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
    </div>
</template>