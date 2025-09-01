<script setup lang="ts">
/* -------------------- Imports -------------------- */
import { Head, Link } from '@inertiajs/vue3';

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
            hi
        </div>
    </div>
</template>
