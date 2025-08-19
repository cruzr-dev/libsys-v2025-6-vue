<script setup lang="ts">
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { AlertCircle, CircleCheckBig, X } from 'lucide-vue-next';
import { usePage } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

interface Flash {
    success?: string | null;
    error?: string | null;
}

declare module '@inertiajs/core' {
    interface PageProps {
        flash: Flash;
    }
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

// for alert
const page = usePage()
const showAlert = ref(false)
const progressWidth = ref(100)

const startProgressBar = () => {
    showAlert.value = true
    progressWidth.value = 100

    const startTime = Date.now()
    const duration = 5000 // 5 seconds

    const updateProgress = () => {
        const elapsed = Date.now() - startTime
        const remaining = Math.max(0, duration - elapsed)
        progressWidth.value = (remaining / duration) * 100

        if (remaining > 0) {
            requestAnimationFrame(updateProgress)
        } else {
            showAlert.value = false
        }
    }

    requestAnimationFrame(updateProgress)
}

// Watch for changes in flash messages
watch(
    () => [page.props.flash.error, page.props.flash.success],
    ([newError, newSuccess]) => {
        if (newError || newSuccess) {
            startProgressBar()
        }
    },
    { immediate: true } // Run immediately on component creation
)

// Also run on mount as backup
onMounted(() => {
    if (page.props.flash.error || page.props.flash.success) {
        startProgressBar()
    }
})
</script>

<template>
    <Alert
        class="fixed top-5 left-1/2 transform -translate-x-1/2 w-fit max-w-md pr-8 z-50 overflow-hidden"
        variant="destructive"
        v-if="page.props.flash.error && showAlert"
    >
        <AlertCircle class="w-4 h-4" />
        <button
            @click="showAlert = false"
            class="absolute top-2 right-2 p-1 hover:bg-red-100 rounded-full transition-colors"
        >
            <X class="w-4 h-4" />
        </button>
        <AlertTitle>Error</AlertTitle>
        <AlertDescription>
            {{ page.props.flash.error }}
        </AlertDescription>
        <!-- Progress bar for error alert -->
        <div class="absolute bottom-0 left-0 w-full h-1 bg-red-200">
            <div
                class="h-full bg-red-600 transition-all duration-100 ease-linear"
                :style="{ width: progressWidth + '%' }"
            ></div>
        </div>
    </Alert>

    <Alert
        class="fixed top-5 left-1/2 transform -translate-x-1/2 w-fit max-w-md pr-8 z-50 border-2 border-green-500 overflow-hidden"
        v-if="page.props.flash.success && showAlert"
    >
        <CircleCheckBig />
        <button
            @click="showAlert = false"
            class="absolute top-2 right-2 p-1 hover:bg-red-100 rounded-full transition-colors"
        >
            <X class="w-4 h-4" />
        </button>
        <AlertTitle>Success</AlertTitle>
        <AlertDescription>
            {{ page.props.flash.success }}
        </AlertDescription>
        <!-- Progress bar for success alert -->
        <div class="absolute bottom-0 left-0 w-full h-1 bg-green-200">
            <div
                class="h-full bg-green-600 transition-all duration-100 ease-linear"
                :style="{ width: progressWidth + '%' }"
            ></div>
        </div>
    </Alert>

    <AppLayout :breadcrumbs="breadcrumbs">
        <slot />
    </AppLayout>
</template>
