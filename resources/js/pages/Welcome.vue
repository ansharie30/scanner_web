<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, FolderTree, LockKeyhole, Search } from 'lucide-vue-next';
import { dashboard, login } from '@/routes';

const features = [
    {
        icon: FolderTree,
        title: 'Keep files organized',
        description: 'Group documents into clear, easy-to-browse folders.',
    },
    {
        icon: Search,
        title: 'Find what you need',
        description: 'Search your library and get back to work quickly.',
    },
    {
        icon: LockKeyhole,
        title: 'One secure workspace',
        description: 'Keep important files together in a managed workspace.',
    },
];
</script>

<template>
    <Head title="Document Management System" />

    <div class="min-h-screen bg-background text-foreground">
        <header class="border-b border-border bg-background/95">
            <nav
                class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-4 sm:px-8"
            >
                <div class="flex min-w-0 items-center gap-3">
                    <img
                        src="/logo/logo.png"
                        alt="Document Management System logo"
                        class="size-10 shrink-0 object-contain"
                    />
                    <span class="text-sm font-semibold sm:hidden">DMS</span>
                    <span
                        class="hidden truncate text-sm font-semibold sm:inline sm:text-base"
                    >
                        Document Management System
                    </span>
                </div>

                <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="rounded-md px-3 py-2 text-sm font-medium text-foreground hover:bg-accent"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="rounded-md px-3 py-2 text-sm font-medium text-foreground hover:bg-accent"
                        >
                            Log in
                        </Link>
                    </template>
                </div>
            </nav>
        </header>

        <main
            class="mx-auto flex min-h-[calc(100vh-73px)] max-w-7xl items-center px-5 py-14 sm:px-8 sm:py-20 lg:py-24"
        >
            <section class="max-w-5xl">
                <h1
                    class="max-w-3xl text-4xl leading-tight font-semibold tracking-tight sm:text-5xl"
                >
                    Document Management System
                </h1>
                <p
                    class="mt-5 max-w-2xl text-base leading-7 text-muted-foreground sm:text-lg"
                >
                    A clear, dependable place to organize, manage, and find the
                    documents your work depends on.
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="inline-flex items-center gap-2 rounded-md bg-primary px-5 py-3 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90"
                    >
                        Open workspace
                        <ArrowRight class="size-4" />
                    </Link>
                    <Link
                        v-if="!$page.props.auth.user"
                        :href="login()"
                        class="inline-flex items-center gap-2 rounded-md bg-primary px-5 py-3 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90"
                    >
                        Log in to your workspace
                        <ArrowRight class="size-4" />
                    </Link>
                </div>

                <div
                    class="mt-12 grid gap-x-8 gap-y-6 border-t border-border pt-7 sm:grid-cols-3"
                >
                    <article
                        v-for="feature in features"
                        :key="feature.title"
                        class="flex gap-3"
                    >
                        <div
                            class="flex size-9 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary"
                        >
                            <component :is="feature.icon" class="size-4" />
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold">
                                {{ feature.title }}
                            </h2>
                            <p
                                class="mt-1 text-sm leading-5 text-muted-foreground"
                            >
                                {{ feature.description }}
                            </p>
                        </div>
                    </article>
                </div>
            </section>
        </main>
    </div>
</template>
