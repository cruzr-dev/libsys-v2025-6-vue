<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpenCheck, LayoutGrid, Library, Users, ChartColumnIncreasing, ChartPie  } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { FileClock } from 'lucide-vue-next';
import { computed } from 'vue';

// Get current page URL from Inertia
const page = usePage();
const currentUrl = computed(() => page.url);

// Helper function to check if a route is active
const isRouteActive = (url: string): boolean => {
    if (url === '/dashboard') {
        return currentUrl.value === '/dashboard' || currentUrl.value === '/';
    }
    return currentUrl.value.startsWith(url);
};

// Helper function to check if any sub-item is active
const hasActiveSubItem = (items: { url: string }[] = []): boolean => {
    return items.some(item => currentUrl.value.startsWith(item.url));
};

const mainNavItems = computed((): NavItem[] => [
    {
        title: 'Dashboard',
        url: '/dashboard',
        icon: LayoutGrid,
        isActive: isRouteActive('/dashboard'),
    },
    {
        title: 'Patrons',
        url: '/users',
        icon: Users,
        isActive: isRouteActive('/users'),
    },
    {
        title: 'Collection',
        url: '/records',
        icon: Library,
        isActive: isRouteActive('/records'),
    },
    {
        title: 'Borrowings',
        url: '/borrowings',
        icon: BookOpenCheck,
        isActive: isRouteActive('/borrowings'),
    },
    {
        title: 'Library Visits',
        url: '/logger',
        icon: FileClock,
        isActive: isRouteActive('/logger'),
    },
    {
        title: "Reports",
        url: "reports",
        icon: ChartColumnIncreasing ,
        isActive: hasActiveSubItem([
            { url: "/reports/users/student/barcodes" },
            { url: "/explorer" },
            { url: "/quantum" }
        ]),
        items: [
            {
                title: "Users",
                url: "/reports/users/student/barcodes",
                isActive: isRouteActive('users'),
            },
            {
                title: "Collection",
                url: "/reports/collection/book/qrcodes",
                isActive: isRouteActive('collection'),
            },
            {
                title: "Quantum",
                url: "/quantum",
                isActive: isRouteActive('/quantum'),
            },
        ],
    },
    {
        title: "Statistical data",
        url: "#",
        icon: ChartPie ,
        isActive: hasActiveSubItem([
            { url: "#" },
            { url: "#" },
            { url: "#" }
        ]),
        items: [
            {
                title: "Genesis",
                url: "/sample",
                isActive: isRouteActive('/sample'),
            },
            {
                title: "Explorer",
                url: "/explorer",
                isActive: isRouteActive('/explorer'),
            },
            {
                title: "Quantum",
                url: "/quantum",
                isActive: isRouteActive('/quantum'),
            },
        ],
    },
]);

const footerNavItems: NavItem[] = [
    // Your footer items here
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton class="bg-background hover:bg-muted text-accent-foreground" size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
