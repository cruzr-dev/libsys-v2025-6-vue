<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <div :class="className" v-bind="$attrs">
        <img src="/storage/system_images/logo_50.png" alt="Placeholder Image">
    </div>
</template>
