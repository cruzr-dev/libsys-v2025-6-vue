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
            hiii
        </Layout>
    </AppLayout>
</template>
