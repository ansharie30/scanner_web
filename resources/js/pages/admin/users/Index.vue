<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import {
    Pencil,
    Search,
    ShieldCheck,
    Trash2,
    UserPlus,
    Users,
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
    destroy as destroyUser,
    store as storeUser,
    update as updateUser,
} from '@/routes/admin/users';

interface UserRow {
    id: number;
    name: string;
    email: string;
    role: string;
}

const props = defineProps<{ users: UserRow[] }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Users', href: '/admin/users' }],
    },
});

const page = usePage();
const currentUserId = computed(() => (page.props.auth as any)?.user?.id);
const search = ref('');
const dialogOpen = ref(false);
const editingUser = ref<UserRow | null>(null);
const deleteTarget = ref<UserRow | null>(null);
const deletingUser = ref(false);

const filteredUsers = computed(() => {
    const query = search.value.trim().toLowerCase();

    if (!query) {
        return props.users;
    }

    return props.users.filter((user) =>
        [user.name, user.email, user.role].some((value) =>
            value.toLowerCase().includes(query),
        ),
    );
});

const administratorCount = computed(
    () => props.users.filter((user) => user.role === 'super_admin').length,
);

const form = useForm({
    name: '',
    email: '',
    role: 'user',
    password: '',
    password_confirmation: '',
});

const isEditingSelf = computed(
    () => editingUser.value?.id === currentUserId.value,
);

function openCreateDialog() {
    editingUser.value = null;
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
}

function openEditDialog(user: UserRow) {
    editingUser.value = user;
    form.name = user.name;
    form.email = user.email;
    form.role = user.role;
    form.password = '';
    form.password_confirmation = '';
    form.clearErrors();
    dialogOpen.value = true;
}

function onDialogOpenChange(open: boolean) {
    dialogOpen.value = open;

    if (!open) {
        editingUser.value = null;
        form.reset();
        form.clearErrors();
    }
}

function saveUser() {
    const onSuccess = () => onDialogOpenChange(false);

    if (editingUser.value) {
        form.put(updateUser.url(editingUser.value.id), {
            preserveScroll: true,
            onSuccess,
        });
        return;
    }

    form.post(storeUser.url(), {
        preserveScroll: true,
        onSuccess,
    });
}

function remove(user: UserRow) {
    deleteTarget.value = user;
}

function deleteSelectedUser() {
    if (!deleteTarget.value) {
        return;
    }

    deletingUser.value = true;
    router.delete(destroyUser.url(deleteTarget.value.id), {
        preserveScroll: true,
        onFinish: () => {
            deletingUser.value = false;
            deleteTarget.value = null;
        },
    });
}
</script>

<template>
    <Head title="Users" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div class="space-y-1">
                <p class="text-sm font-medium text-muted-foreground">
                    Administration
                </p>
                <h1 class="text-2xl font-semibold tracking-tight">Users</h1>
                <p class="text-sm text-muted-foreground">
                    Manage accounts and access levels.
                </p>
            </div>
            <Button class="w-full sm:w-auto" @click="openCreateDialog">
                <UserPlus class="size-4" />
                Add user
            </Button>
        </header>

        <div
            class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-muted-foreground"
        >
            <span class="inline-flex items-center gap-2">
                <Users class="size-4" />
                {{ props.users.length }}
                {{ props.users.length === 1 ? 'account' : 'accounts' }}
            </span>
            <span class="inline-flex items-center gap-2">
                <ShieldCheck class="size-4" />
                {{ administratorCount }}
                {{
                    administratorCount === 1
                        ? 'administrator'
                        : 'administrators'
                }}
            </span>
        </div>

        <Card class="overflow-hidden">
            <CardHeader
                class="flex flex-col gap-4 border-b px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5"
            >
                <div class="space-y-1">
                    <CardTitle class="text-base">Account directory</CardTitle>
                    <CardDescription
                        >View and manage registered users.</CardDescription
                    >
                </div>
                <div class="relative w-full sm:max-w-xs">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="search"
                        class="pl-9"
                        placeholder="Search users"
                        aria-label="Search users"
                    />
                </div>
            </CardHeader>

            <CardContent class="p-0">
                <div class="divide-y lg:hidden">
                    <article
                        v-for="user in filteredUsers"
                        :key="user.id"
                        class="p-4"
                    >
                        <div
                            class="flex min-w-0 items-start justify-between gap-3"
                        >
                            <div class="min-w-0 flex-1">
                                <p class="font-medium break-words">
                                    {{ user.name }}
                                </p>
                                <p
                                    class="mt-0.5 text-sm break-all text-muted-foreground"
                                >
                                    {{ user.email }}
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-1">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon-sm"
                                    :aria-label="`Edit ${user.name}`"
                                    title="Edit user"
                                    @click="openEditDialog(user)"
                                >
                                    <Pencil class="size-4" />
                                </Button>
                                <Button
                                    v-if="user.id !== currentUserId"
                                    type="button"
                                    variant="ghost"
                                    size="icon-sm"
                                    class="text-destructive hover:text-destructive"
                                    :aria-label="`Delete ${user.name}`"
                                    title="Delete user"
                                    @click="remove(user)"
                                >
                                    <Trash2 class="size-4" />
                                </Button>
                            </div>
                        </div>
                        <div class="mt-3">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-md border px-2 py-1 text-xs font-medium"
                            >
                                <ShieldCheck
                                    v-if="user.role === 'super_admin'"
                                    class="size-3.5"
                                />
                                {{
                                    user.role === 'super_admin'
                                        ? 'Super Admin'
                                        : 'User'
                                }}
                            </span>
                        </div>
                    </article>
                    <div
                        v-if="filteredUsers.length === 0"
                        class="px-4 py-10 text-center"
                    >
                        <p class="font-medium">No users found</p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{
                                search
                                    ? 'Try another search.'
                                    : 'Add a user to get started.'
                            }}
                        </p>
                    </div>
                </div>
                <div class="hidden overflow-x-auto lg:block">
                    <table class="w-full min-w-[42rem] text-left text-sm">
                        <thead
                            class="bg-muted/40 text-xs text-muted-foreground"
                        >
                            <tr>
                                <th class="px-5 py-3 font-medium">Name</th>
                                <th class="px-5 py-3 font-medium">Email</th>
                                <th class="px-5 py-3 font-medium">Role</th>
                                <th class="px-5 py-3 text-right font-medium">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="user in filteredUsers"
                                :key="user.id"
                                class="border-t transition-colors hover:bg-muted/30"
                            >
                                <td class="px-5 py-3.5 font-medium">
                                    {{ user.name }}
                                </td>
                                <td class="px-5 py-3.5 text-muted-foreground">
                                    {{ user.email }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-md border px-2 py-1 text-xs font-medium"
                                    >
                                        <ShieldCheck
                                            v-if="user.role === 'super_admin'"
                                            class="size-3.5"
                                        />
                                        {{
                                            user.role === 'super_admin'
                                                ? 'Super Admin'
                                                : 'User'
                                        }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon-sm"
                                            :aria-label="`Edit ${user.name}`"
                                            title="Edit user"
                                            @click="openEditDialog(user)"
                                        >
                                            <Pencil class="size-4" />
                                        </Button>
                                        <Button
                                            v-if="user.id !== currentUserId"
                                            type="button"
                                            variant="ghost"
                                            size="icon-sm"
                                            class="text-destructive hover:text-destructive"
                                            :aria-label="`Delete ${user.name}`"
                                            title="Delete user"
                                            @click="remove(user)"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredUsers.length === 0">
                                <td colspan="4" class="px-5 py-12 text-center">
                                    <p class="font-medium">No users found</p>
                                    <p
                                        class="mt-1 text-sm text-muted-foreground"
                                    >
                                        {{
                                            search
                                                ? 'Try another search.'
                                                : 'Add a user to get started.'
                                        }}
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <Dialog :open="dialogOpen" @update:open="onDialogOpenChange">
            <DialogContent
                class="max-h-[90dvh] max-h-[90vh] overflow-y-auto sm:max-w-lg"
            >
                <form class="grid gap-5" @submit.prevent="saveUser">
                    <DialogHeader>
                        <DialogTitle>{{
                            editingUser ? 'Edit user' : 'Add user'
                        }}</DialogTitle>
                        <DialogDescription>
                            {{
                                editingUser
                                    ? 'Update this account’s details and access.'
                                    : 'Create an account and assign its access level.'
                            }}
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="user-name">Name</Label>
                            <Input
                                id="user-name"
                                v-model="form.name"
                                autocomplete="name"
                                :aria-invalid="!!form.errors.name"
                            />
                            <p
                                v-if="form.errors.name"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.name }}
                            </p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="user-email">Email</Label>
                            <Input
                                id="user-email"
                                v-model="form.email"
                                type="email"
                                autocomplete="email"
                                :aria-invalid="!!form.errors.email"
                            />
                            <p
                                v-if="form.errors.email"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.email }}
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="user-role">Role</Label>
                        <select
                            id="user-role"
                            v-model="form.role"
                            :disabled="isEditingSelf"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <option value="user">User</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                        <p
                            v-if="isEditingSelf"
                            class="text-xs text-muted-foreground"
                        >
                            You cannot change your own role.
                        </p>
                        <p
                            v-if="form.errors.role"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.role }}
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="user-password">
                                Password {{ editingUser ? '(optional)' : '' }}
                            </Label>
                            <Input
                                id="user-password"
                                v-model="form.password"
                                type="password"
                                autocomplete="new-password"
                                :aria-invalid="!!form.errors.password"
                            />
                            <p
                                v-if="form.errors.password"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.password }}
                            </p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="user-password-confirmation"
                                >Confirm password</Label
                            >
                            <Input
                                id="user-password-confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                autocomplete="new-password"
                            />
                        </div>
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
                            :disabled="form.processing"
                        >
                            {{
                                form.processing
                                    ? 'Saving…'
                                    : editingUser
                                      ? 'Save changes'
                                      : 'Create user'
                            }}
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
                    <DialogTitle
                        >Delete "{{ deleteTarget?.name }}"?</DialogTitle
                    >
                    <DialogDescription>
                        This will permanently delete the user account for
                        {{ deleteTarget?.email }}. This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button
                            type="button"
                            variant="outline"
                            class="w-full sm:w-auto"
                            :disabled="deletingUser"
                        >
                            Cancel
                        </Button>
                    </DialogClose>
                    <Button
                        type="button"
                        class="w-full bg-destructive text-white hover:bg-destructive/90 sm:w-auto"
                        :disabled="deletingUser"
                        @click.prevent="deleteSelectedUser"
                    >
                        {{ deletingUser ? 'Deleting…' : 'Delete user' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
