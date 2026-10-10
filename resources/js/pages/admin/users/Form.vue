<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { store as storeUser, update as updateUser } from '@/routes/admin/users';

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
        form.put(updateUser.url(props.user.id));
    } else {
        form.post(storeUser.url());
    }
}
</script>

<template>
    <Head :title="isEdit ? 'Edit user' : 'Add user'" />

    <main class="flex w-full justify-center p-4 md:p-6">
        <Card class="w-full max-w-2xl self-start overflow-hidden">
            <CardHeader class="border-b p-4 sm:p-6">
                <CardTitle class="text-xl">{{ isEdit ? 'Edit user' : 'Add user' }}</CardTitle>
                <CardDescription>
                    {{ isEdit ? 'Update this account’s details and access.' : 'Create an account and assign its access level.' }}
                </CardDescription>
            </CardHeader>
            <CardContent class="p-4 sm:p-6">
                <form class="grid gap-5" @submit.prevent="submit">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="user-name">Name</Label>
                            <Input id="user-name" v-model="form.name" autocomplete="name" :aria-invalid="!!form.errors.name" />
                            <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="user-email">Email</Label>
                            <Input id="user-email" v-model="form.email" type="email" autocomplete="email" :aria-invalid="!!form.errors.email" />
                            <p v-if="form.errors.email" class="text-sm text-destructive">{{ form.errors.email }}</p>
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="user-role">Role</Label>
                        <select
                            id="user-role"
                            v-model="form.role"
                            :disabled="isSelf"
                            class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs focus-visible:ring-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <option value="user">User</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                        <p v-if="isSelf" class="text-xs text-muted-foreground">
                            You cannot change your own role.
                        </p>
                        <p v-if="form.errors.role" class="text-sm text-destructive">{{ form.errors.role }}</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="user-password">
                                Password <span v-if="isEdit" class="text-xs text-muted-foreground">(leave blank to keep current)</span>
                            </Label>
                            <Input
                                id="user-password"
                                v-model="form.password"
                                type="password"
                                autocomplete="new-password"
                                :aria-invalid="!!form.errors.password"
                            />
                            <p v-if="form.errors.password" class="text-sm text-destructive">{{ form.errors.password }}</p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="user-password-confirmation">Confirm password</Label>
                            <Input
                                id="user-password-confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                autocomplete="new-password"
                            />
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-2 border-t pt-4 sm:flex-row sm:justify-end">
                        <Button variant="outline" class="w-full sm:w-auto" as-child>
                            <Link href="/admin/users">Cancel</Link>
                        </Button>
                        <Button type="submit" class="w-full sm:w-auto" :disabled="form.processing">
                            {{ form.processing ? 'Saving…' : isEdit ? 'Save changes' : 'Create user' }}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </main>
</template>