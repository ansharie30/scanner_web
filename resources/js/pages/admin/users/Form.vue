<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface UserData {
    id: number;
    name: string;
    email: string;
    role: string;
}

const props = defineProps<{ user: UserData | null }>();

defineOptions({
    layout: (pageProps: { user: UserData | null }) => ({
        breadcrumbs: [
            { title: 'Users', href: '/admin/users' },
            { title: pageProps.user ? 'Edit user' : 'Add user', href: '#' },
        ],
    }),
});

const page = usePage();
const currentUserId = computed(() => (page.props.auth as any)?.user?.id);
const isEdit = computed(() => props.user !== null);
const isSelf = computed(() => props.user?.id === currentUserId.value);

const form = useForm({
    name: props.user?.name ?? '',
    email: props.user?.email ?? '',
    role: props.user?.role ?? 'user',
    password: '',
    password_confirmation: '',
});

function submit() {
    if (props.user) {
        form.put(`/admin/users/${props.user.id}`);
    } else {
        form.post('/admin/users');
    }
}

const inputClass = 'w-full rounded-md border bg-transparent px-3 py-2 text-sm';
</script>

<template>
    <Head :title="isEdit ? 'Edit user' : 'Add user'" />

    <div class="max-w-lg p-4">
            <h1 class="mb-4 text-xl font-semibold">{{ isEdit ? 'Edit user' : 'Add user' }}</h1>

            <form class="space-y-4" @submit.prevent="submit">
                <div>
                    <label class="mb-1 block text-sm">Name</label>
                    <input v-model="form.name" type="text" :class="inputClass" />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm">Email</label>
                    <input v-model="form.email" type="email" :class="inputClass" />
                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm">Role</label>
                    <select v-model="form.role" :disabled="isSelf" :class="inputClass">
                        <option value="user">User</option>
                        <option value="super_admin">Super Admin</option>
                    </select>
                    <p v-if="isSelf" class="mt-1 text-xs text-muted-foreground">
                        You cannot change your own role.
                    </p>
                    <p v-if="form.errors.role" class="mt-1 text-sm text-red-600">{{ form.errors.role }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm">
                        Password <span v-if="isEdit" class="text-xs">(leave blank to keep current)</span>
                    </label>
                    <input v-model="form.password" type="password" :class="inputClass" />
                    <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm">Confirm password</label>
                    <input v-model="form.password_confirmation" type="password" :class="inputClass" />
                </div>

                <div class="flex gap-3">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-md bg-black px-4 py-2 text-sm text-white disabled:opacity-50 dark:bg-white dark:text-black"
                    >
                        {{ isEdit ? 'Save changes' : 'Create user' }}
                    </button>
                    <Link href="/admin/users" class="px-4 py-2 text-sm underline">Cancel</Link>
                </div>
            </form>
    </div>
</template>