<script setup lang="ts">
/* -------------------- Imports -------------------- */
import { Head, Link, usePage } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

import LoggerPatronSearch from '@/components/LoggerPatronSearch.vue';

import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { AlertCircle, CircleCheckBig, X } from 'lucide-vue-next';

/* -------------------- Props -------------------- */
defineProps<{
    patron: object;
    purposes: object;
    search_button: boolean;
    is_logout: boolean;
}>();

/* -------------------- State & Lifecycle -------------------- */
const page = usePage();
const showAlert = ref(true);

onMounted(() => {
    if (page.props.flash.error) {
        setTimeout(() => {
            showAlert.value = false;
        }, 5000);
    }
});

/* -------------------- Types -------------------- */
interface Flash {
    success?: string | null;
    error?: string | null;
}

declare module '@inertiajs/core' {
    interface PageProps {
        flash: Flash;
    }
}
</script>

<template>
    <Head title="Patron Logger" />

    <div class="flex min-h-screen flex-col items-center text-[#1b1b18] lg:justify-center dark:bg-[#0a0a0a]">
        <!-- Hidden redirect link -->
        <Link :href="route('home')" class="fixed top-0 left-0 bg-red-500 opacity-0"> hi </Link>

        <!-- Error Alert -->
        <Alert v-if="page.props.flash.error && showAlert" class="absolute top-5 right-5 w-fit pr-8" variant="destructive">
            <AlertCircle class="h-4 w-4" />
            <button @click="showAlert = false" class="absolute top-2 right-2 rounded-full p-1 transition-colors hover:bg-red-100">
                <X class="h-4 w-4" />
            </button>
            <AlertTitle>Error</AlertTitle>
            <AlertDescription>
                {{ page.props.flash.error }}
            </AlertDescription>
        </Alert>

        <!-- Success Alert -->
        <Alert v-if="page.props.flash.success && showAlert" class="fixed top-5 right-5 z-30 w-fit max-w-md border-2 border-green-500 pr-8">
            <CircleCheckBig />
            <button @click="showAlert = false" class="absolute top-2 right-2 rounded-full p-1 transition-colors hover:bg-red-100">
                <X class="h-4 w-4" />
            </button>
            <AlertTitle>Success</AlertTitle>
            <AlertDescription>
                {{ page.props.flash.success }}
            </AlertDescription>
        </Alert>

        <!-- Main Content -->
        <div class="grid w-full opacity-100 transition-opacity duration-750 starting:opacity-0">
            <div class="flex min-w-full flex-col items-center p-8">
                <LoggerPatronSearch :purposes="purposes" :patron="patron" :search_button="search_button" :is_logout="is_logout" />
            </div>
        </div>
    </div>
</template>
