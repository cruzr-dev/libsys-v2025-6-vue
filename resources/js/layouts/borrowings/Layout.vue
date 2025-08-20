<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Active',
        href: '/borrowings/active',
    },
    {
        title: 'Overdue',
        href: '/borrowings/overdue',
    },
    {
        title: 'Returned',
        href: '/borrowings/returned',
    },
    {
        title: 'Lost',
        href: '/borrowings/returned',
    },
    {
        title: 'Damaged',
        href: '/borrowings/returned',
    },
    {
        title: 'Renewed',
        href: '/borrowings/returned',
    },
    {
        title: 'Inside',
        href: '/borrowings/inside',
    },
];

const rightNavItems: NavItem[] = [
    {
        title: 'Options',
        href: '/records/options',
    },
];


const page = usePage();

const currentPath = page.props.ziggy?.location ? new URL(page.props.ziggy.location).pathname : '';
</script>

<template>
    <div class="p-4">
        <div class="flex flex-col space-y-2 ">
            <!-- Header Navigation -->
            <header class="w-full flex justify-between">
                <nav class="flex flex-wrap w-fit items-center gap-1">
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="item.href"
                        variant="ghost"
                        :class="['justify-start', { 'bg-muted': currentPath.startsWith(item.href) }]"
                        as-child
                    >
                        <Link :href="item.href">
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
                <nav class="flex flex-wrap w-fit items-center gap-1">
                    <Button
                        v-for="item in rightNavItems"
                        :key="item.href"
                        variant="ghost"
                        :class="['justify-start', { 'bg-muted': currentPath.startsWith(item.href) }]"
                        as-child
                    >
                        <Link :href="item.href">
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
            </header>

            <Separator />

            <!-- Main Content Area -->
            <main class="flex justify-center">
                <section class="w-full">
                    <slot />
                </section>
            </main>
        </div>
    </div>
</template>
