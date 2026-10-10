<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const props = defineProps<{
    name?: string;
    compact?: boolean;
}>();

const defaultName = usePage().props.name;
</script>

<template>
    <AppLogoIcon
        class="shrink-0 object-contain"
        :class="props.compact ? 'size-7' : 'size-8'"
    />
    <div class="ml-2 grid min-w-0 flex-1 text-left text-sm">
        <span class="truncate leading-tight font-semibold">{{
            props.name ?? defaultName
        }}</span>
    </div>
</template>
