<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Books',
        href: '/records/books',
    },
    {
        title: 'Multimedia Collections',
        href: '/records/multimedia',
    },
    {
        title: 'Magazines/Periodicals',
        href: '/records/periodicals',
    },
    {
        title: 'Theses/Dissertations',
        href: '/records/theses',
    },
];

const rightNavItems: NavItem[] = [
    {
        title: 'Options',
        href: '/records/options',
    },
];


const page = usePage();
if (page.props.auth.permissions.can_view_any_users)
{
    // rightNavItems.unshift( {
    //     title: 'Import Books',
    //     href: '/records/import',
    // })
}

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
