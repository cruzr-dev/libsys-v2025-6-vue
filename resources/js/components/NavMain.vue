<script setup lang="ts">
import type { LucideIcon } from "lucide-vue-next"
import { ChevronRight } from "lucide-vue-next"
import { Link, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from "@/components/ui/collapsible"
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from "@/components/ui/sidebar"
import { User } from '@/types';

const props = defineProps<{
    items: {
        title: string
        url: string
        icon?: LucideIcon
        isActive?: boolean
        items?: {
            title: string
            url: string
            isActive?: boolean
        }[]
    }[]
}>()

const page = usePage();
const user = page.props.auth.user as User;

// Track which collapsible is currently open
const openCollapsible = ref<string | null>(null);

// Initialize with the active item on mount
const initializeOpenCollapsible = () => {
    for (const item of props.items) {
        if (item.isActive && item.items?.length) {
            openCollapsible.value = item.title;
            break;
        }
    }
};

// Initialize on component mount
initializeOpenCollapsible();

// Watch for changes in props to reinitialize if needed
watch(() => props.items, initializeOpenCollapsible, { deep: true });

const toggleCollapsible = (itemTitle: string) => {
    if (openCollapsible.value === itemTitle) {
        openCollapsible.value = null; // Close if already open
    } else {
        openCollapsible.value = itemTitle; // Open this one and close others
    }
};

const isCollapsibleOpen = (itemTitle: string) => {
    return openCollapsible.value === itemTitle;
};
</script>

<template>
    <SidebarGroup>
        <SidebarGroupLabel> {{ user.user_type.name }} Menu</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <!-- If item has sub-items, render collapsible -->
                <Collapsible
                    v-if="item.items?.length"
                    as-child
                    :open="isCollapsibleOpen(item.title)"
                    @update:open="() => toggleCollapsible(item.title)"
                    class="group/collapsible"
                >
                    <SidebarMenuItem>
                        <CollapsibleTrigger as-child>
                            <SidebarMenuButton
                                :tooltip="item.title"
                                :class="{
                                    'bg-background text-foreground': item.isActive,
                                    'hover:bg-accent': !item.isActive
                                }"
                            >
                                <component :is="item.icon" v-if="item.icon" />
                                <span>{{ item.title }}</span>
                                <ChevronRight
                                    class="ml-auto transition-transform duration-200"
                                    :class="{
                                        'rotate-90': isCollapsibleOpen(item.title)
                                    }"
                                />
                            </SidebarMenuButton>
                        </CollapsibleTrigger>
                        <CollapsibleContent>
                            <SidebarMenuSub>
                                <SidebarMenuSubItem v-for="subItem in item.items" :key="subItem.title">
                                    <SidebarMenuSubButton
                                        as-child
                                        :class="{
                                            'bg-background text-foreground': subItem.isActive,
                                            'hover:bg-accent': !subItem.isActive
                                        }"
                                    >
                                        <Link :href="subItem.url">
                                            <span>{{ subItem.title }}</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                            </SidebarMenuSub>
                        </CollapsibleContent>
                    </SidebarMenuItem>
                </Collapsible>
                <!-- If no sub-items, render direct link -->
                <SidebarMenuButton
                    v-else
                    as-child
                    :tooltip="item.title"
                    :class="{
                        'bg-background text-foreground': item.isActive,
                        'hover:bg-accent': !item.isActive
                    }"
                >
                    <Link :href="item.url">
                        <component :is="item.icon" v-if="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
