<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import type { BreadcrumbItemType } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { AlertCircle, CircleCheckBig, X } from 'lucide-vue-next';
import { onMounted, ref, watch } from 'vue';

// TypeScript Interfaces
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

// Component Props
withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

// Alert State
const page = usePage();
const showAlert = ref(false);
const progressWidth = ref(100);

// Alert Progress Bar Logic
const startProgressBar = () => {
    showAlert.value = true;
    progressWidth.value = 100;

    const startTime = Date.now();
    const duration = 5000; // 5 seconds

    const updateProgress = () => {
        const elapsed = Date.now() - startTime;
        const remaining = Math.max(0, duration - elapsed);
        progressWidth.value = (remaining / duration) * 100;

        if (remaining > 0) {
            requestAnimationFrame(updateProgress);
        } else {
            showAlert.value = false;
        }
    };

    requestAnimationFrame(updateProgress);
};

// Watch Flash Messages
watch(
    () => [page.props.flash.error, page.props.flash.success],
    ([newError, newSuccess]) => {
        if (newError || newSuccess) {
            startProgressBar();
        }
    },
    { immediate: true },
);

// Backup Flash Check on Mount
onMounted(() => {
    if (page.props.flash.error || page.props.flash.success) {
        startProgressBar();
    }
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <!-- Error Alert -->
            <Alert
                class="fixed top-5 left-1/2 z-50 w-fit max-w-md -translate-x-1/2 transform overflow-hidden pr-8"
                variant="destructive"
                v-if="page.props.flash.error && showAlert"
            >
                <AlertCircle class="h-4 w-4" />
                <button @click="showAlert = false" class="absolute top-2 right-2 rounded-full p-1 transition-colors hover:bg-red-100">
                    <X class="h-4 w-4" />
                </button>
                <AlertTitle>Error</AlertTitle>
                <AlertDescription>
                    {{ page.props.flash.error }}
                </AlertDescription>
                <div class="absolute bottom-0 left-0 h-1 w-full bg-red-200">
                    <div class="h-full bg-red-600 transition-all duration-100 ease-linear" :style="{ width: progressWidth + '%' }"></div>
                </div>
            </Alert>

            <!-- Success Alert -->
            <Alert
                class="fixed top-5 left-1/2 z-50 w-fit max-w-md -translate-x-1/2 transform overflow-hidden border-2 border-green-500 pr-8"
                v-if="page.props.flash.success && showAlert"
            >
                <CircleCheckBig />
                <button @click="showAlert = false" class="absolute top-2 right-2 rounded-full p-1 transition-colors hover:bg-red-100">
                    <X class="h-4 w-4" />
                </button>
                <AlertTitle>Success</AlertTitle>
                <AlertDescription>
                    {{ page.props.flash.success }}
                </AlertDescription>
                <div class="absolute bottom-0 left-0 h-1 w-full bg-green-200">
                    <div class="h-full bg-green-600 transition-all duration-100 ease-linear" :style="{ width: progressWidth + '%' }"></div>
                </div>
            </Alert>

            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
    </AppShell>
</template>
