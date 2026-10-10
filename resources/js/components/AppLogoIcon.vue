<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <img
        src="/logo/logo.png"
        alt="Document Management System logo"
        :class="className"
        v-bind="$attrs"
    />
</template>
