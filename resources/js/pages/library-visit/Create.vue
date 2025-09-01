<script setup lang="ts">
/* -------------------- Imports -------------------- */
import { Head, Link } from '@inertiajs/vue3';
import LoggerSearchComboBox from '@/components/LoggerSearchComboBox.vue';
</script>

<template>
    <Head title="Patron Logger" />

    <div
        class="flex min-h-screen flex-col items-center text-[#1b1b18] lg:justify-center dark:bg-[#0a0a0a]"
    >
        <!-- Hidden redirect link -->
        <Link
            :href="route('home')"
            class="fixed top-0 left-0 bg-red-500 opacity-0"
        >
            hi
        </Link>

        <!-- Main Content -->
        <div
            class="grid w-full opacity-100 transition-opacity duration-750 starting:opacity-0"
        >
            <div class="px-4">
                <div
                    class="relative flex h-[360px] max-w-5xl mx-auto flex-col items-center justify-center rounded-2xl bg-[url(/storage/system_images/eagle.jpg)] bg-cover bg-center shadow-lg"
                >
                    <!-- Heading + Sub-heading -->
                    <div
                        class="absolute top-12 left-1/2 -translate-x-1/2 text-center text-primary-foreground dark:text-muted-foreground"
                    >
                        <h1 class="text-4xl font-bold drop-shadow">ULRC Tagum-Mabini</h1>
                        <p class="mt-2 text-lg font-medium">Patron Logger System</p>
                    </div>

                    <!-- Search Box -->
                    <div
                        class="w-full max-w-xl p-2 mt-28 rounded-xl bg-background/95 shadow-md backdrop-blur-sm"
                    >
                        <LoggerSearchComboBox class="w-full rounded-lg" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
