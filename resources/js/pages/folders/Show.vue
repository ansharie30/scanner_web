<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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
            { title: pageProps.folder.name, href: `/folders/${pageProps.folder.id}` },
        ],
    }),
});

const page = usePage();
const currentUser = computed(() => (page.props.auth as any)?.user);
const isSuperAdmin = computed(() => currentUser.value?.role === 'super_admin');

const fileInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    title: '',
    file: null as File | null,
});

function onFileChange(e: Event) {
    form.file = (e.target as HTMLInputElement).files?.[0] ?? null;
}

function upload() {
    form.post(`/folders/${props.folder.id}/documents`, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            if (fileInput.value) fileInput.value.value = '';
        },
    });
}

function canDelete(doc: DocRow) {
    return isSuperAdmin.value || doc.uploaded_by === currentUser.value?.id;
}

function remove(doc: DocRow) {
    if (confirm(`Delete "${doc.title}"?`)) {
        router.delete(`/documents/${doc.id}`, { preserveScroll: true });
    }
}

function formatSize(bytes: number) {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
}

const inputClass = 'w-full rounded-md border bg-transparent px-3 py-2 text-sm';
</script>

<template>
    <Head :title="folder.name" />

    <div class="p-4">
            <div class="mb-4 flex items-center justify-between">
                <h1 class="text-xl font-semibold">📁 {{ folder.name }}</h1>
                <Link href="/folders" class="text-sm underline">Back to folders</Link>
            </div>

            <form class="mb-6 max-w-lg space-y-3 rounded-lg border p-4" @submit.prevent="upload">
                <h2 class="font-medium">Upload a file</h2>

                <div>
                    <label class="mb-1 block text-sm">Title (optional)</label>
                    <input v-model="form.title" type="text" :class="inputClass" />
                    <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm">File (PDF, JPG, PNG, max 20 MB)</label>
                    <input
                        ref="fileInput"
                        type="file"
                        accept=".pdf,.jpg,.jpeg,.png"
                        class="block w-full text-sm"
                        @change="onFileChange"
                    />
                    <p v-if="form.errors.file" class="mt-1 text-sm text-red-600">{{ form.errors.file }}</p>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing || !form.file"
                    class="rounded-md bg-black px-4 py-2 text-sm text-white disabled:opacity-50 dark:bg-white dark:text-black"
                >
                    {{ form.processing ? 'Uploading...' : 'Upload' }}
                </button>
            </form>

            <p v-if="documents.length === 0" class="text-sm text-muted-foreground">
                No files in this folder yet.
            </p>

            <div v-else class="overflow-x-auto rounded-lg border">
                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="px-4 py-3">Title</th>
                            <th class="px-4 py-3">Size</th>
                            <th class="px-4 py-3">Source</th>
                            <th class="px-4 py-3">Uploaded by</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="doc in documents" :key="doc.id" class="border-b last:border-0">
                            <td class="px-4 py-3">{{ doc.title }}</td>
                            <td class="px-4 py-3">{{ formatSize(doc.size) }}</td>
                            <td class="px-4 py-3">{{ doc.source === 'scanner' ? 'Scanner' : 'Manual' }}</td>
                            <td class="px-4 py-3">{{ doc.uploader_name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ doc.created_at }}</td>
                            <td class="px-4 py-3 text-right">
                                <a
                                    :href="`/documents/${doc.id}/preview`"
                                    target="_blank"
                                    class="mr-3 underline"
                                >
                                    View
                                </a>
                                <a :href="`/documents/${doc.id}/download`" class="mr-3 underline">
                                    Download
                                </a>
                                <button
                                    v-if="canDelete(doc)"
                                    type="button"
                                    class="text-red-600 underline"
                                    @click="remove(doc)"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
    </div>
</template>