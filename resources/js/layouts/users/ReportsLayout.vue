<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';

const sidebarNavItems: NavItem[] = [
    { title: 'Barcodes', href: '/reports/users/barcodes' },
    { title: 'Item', href: '/#' },
    { title: 'Item', href: '/#' },
    { title: 'Item', href: '/#' },
];

const page = usePage();

const currentPath = page.props.ziggy?.location ? new URL(page.props.ziggy.location).pathname : '';
</script>

<template>
    <div class="p-4 pt-2">
        <header class="flex flex-wrap items-center justify-between gap-2">
            <nav class="flex flex-wrap gap-1">
                <Button v-for="item in sidebarNavItems" :key="item.href" variant="ghost" size="sm" :class="{ 'bg-primary text-background': currentPath.startsWith(item.href) }" as-child>
                    <Link :href="item.href">{{ item.title }}</Link>
                </Button>
            </nav>
            <nav class="flex flex-wrap gap-1">
                <Button v-for="item in rightNavItems" :key="item.href" variant="ghost" size="sm" :class="{ 'bg-primary text-background': currentPath.startsWith(item.href) }" as-child>
                    <Link :href="item.href">{{ item.title }}</Link>
                </Button>
            </nav>
        </header>
        <main class="min-h-0"><slot /></main>
    </div>
</template>
