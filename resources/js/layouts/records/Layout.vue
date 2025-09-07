<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';

const sidebarNavItems: NavItem[] = [
    { title: 'Collection', href: '/records/all' },
    { title: 'Book', href: '/records/books' },
    { title: 'Multimedia', href: '/records/multimedia' },
    { title: 'Periodicals', href: '/records/periodicals' },
    { title: 'Theses', href: '/records/theses' },
];

const rightNavItems: NavItem[] = [{ title: 'Options', href: '/records/options' }];

const page = usePage();
// if (page.props.auth.permissions.can_view_any_users) {
//     rightNavItems.unshift({ title: 'Import Books', href: '/records/import' });
// }

const currentPath = page.props.ziggy?.location ? new URL(page.props.ziggy.location).pathname : '';
</script>

<template>
    <div class="p-2">
        <header class="flex flex-wrap items-center justify-between gap-2">
            <nav class="flex flex-wrap gap-1">
                <Button v-for="item in sidebarNavItems" :key="item.href" variant="ghost" size="sm" :class="{ 'bg-muted': currentPath.startsWith(item.href) }" as-child>
                    <Link :href="item.href">{{ item.title }}</Link>
                </Button>
            </nav>
            <nav class="flex flex-wrap gap-1">
                <Button v-for="item in rightNavItems" :key="item.href" variant="ghost" size="sm" :class="{ 'bg-muted': currentPath.startsWith(item.href) }" as-child>
                    <Link :href="item.href">{{ item.title }}</Link>
                </Button>
            </nav>
        </header>
        <Separator class="my-1" />
        <main class="min-h-0"><slot /></main>
    </div>
</template>
