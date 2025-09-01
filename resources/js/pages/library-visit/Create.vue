<script setup lang="ts">
/* -------------------- Imports -------------------- */
import { Head, Link } from '@inertiajs/vue3';
import CollectionSearchComboBox from '@/components/CollectionSearchComboBox.vue';
import LoggerSearchComboBox from '@/components/LoggerSearchComboBox.vue';

</script>

<template>
    <Head title="Patron Logger" />

    <div class="flex min-h-screen flex-col items-center text-[#1b1b18] lg:justify-center dark:bg-[#0a0a0a]">
        <!-- Hidden redirect link -->
        <Link :href="route('home')" class="fixed top-0 left-0 bg-red-500 opacity-0">
            hi
        </Link>

        <!-- Main Content -->
        <div class="grid w-full opacity-100 transition-opacity duration-750 starting:opacity-0">
            <div class="px-4">
                <div class="relative flex h-[360px] min-w-full items-center justify-center rounded-2xl bg-[url(/storage/system_images/eagle.jpg)] bg-cover">
                    <!-- Heading + Sub-heading -->
                    <div class="absolute top-10 left-1/2 transform -translate-x-1/2 text-center text-primary-foreground dark:text-muted-foreground">
                        <h1 class="text-4xl font-bold">ULRC Tagum-Mabini</h1>
                        <p class="text-lg">Your gateway to knowledge and discovery</p>
                    </div>

                    <!-- Search Box -->
                    <div class="w-full max-w-xl p-1 rounded-xl bg-background">
                        <LoggerSearchComboBox
                            class="w-full rounded-lg"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
