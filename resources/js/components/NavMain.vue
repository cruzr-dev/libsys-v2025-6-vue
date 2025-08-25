<script setup lang="ts">
import type { LucideIcon } from "lucide-vue-next"
import { ChevronRight } from "lucide-vue-next"
import { Link, usePage } from '@inertiajs/vue3';
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

defineProps<{
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
                    :default-open="item.isActive"
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
                                <ChevronRight class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
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
