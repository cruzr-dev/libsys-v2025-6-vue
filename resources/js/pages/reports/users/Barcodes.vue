<script setup lang="ts">
import Layout from '@/layouts/users/ReportsLayout.vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users Reports', href: '/users' },
];

</script>

<template>
    <Head title="Create Library Staff" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <Layout>
            <div class="flex h-full flex-1 flex-col gap-6 p-6 bg-white rounded-xl shadow-sm overflow-x-auto relative">
            hiii from usrs heheh
            </div>
        </Layout>
    </AppLayout>
</template>
