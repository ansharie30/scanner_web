<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface UserRow {
    id: number;
    name: string;
    email: string;
    role: string;
}

defineProps<{ users: UserRow[] }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Users', href: '/admin/users' }],
    },
});

const page = usePage();
const currentUserId = computed(() => (page.props.auth as any)?.user?.id);

function remove(user: UserRow) {
    if (confirm(`Delete ${user.name}?`)) {
        router.delete(`/admin/users/${user.id}`);
    }
}
</script>

<template>
    <Head title="Users" />

    <div class="p-4">
            <div class="mb-4 flex items-center justify-between">
                <h1 class="text-xl font-semibold">Users</h1>
                <Link
                    href="/admin/users/create"
                    class="rounded-md bg-black px-4 py-2 text-sm text-white dark:bg-white dark:text-black"
                >
                    Add user
                </Link>
            </div>

            <div class="overflow-x-auto rounded-lg border">
                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-muted/50">
                        <tr>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Role</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in users" :key="user.id" class="border-b last:border-0">
                            <td class="px-4 py-3">{{ user.name }}</td>
                            <td class="px-4 py-3">{{ user.email }}</td>
                            <td class="px-4 py-3">
                                {{ user.role === 'super_admin' ? 'Super Admin' : 'User' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="`/admin/users/${user.id}/edit`" class="mr-3 underline">
                                    Edit
                                </Link>
                                <button
                                    v-if="user.id !== currentUserId"
                                    type="button"
                                    class="text-red-600 underline"
                                    @click="remove(user)"
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