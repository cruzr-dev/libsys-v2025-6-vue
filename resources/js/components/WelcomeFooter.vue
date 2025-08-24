<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import AppLogoIcon from '@/components/AppLogoIcon.vue'
import { Users } from 'lucide-vue-next'

const appName = 'LibSys'
</script>

<template>
    <footer class="border-t border-border bg-background text-sm text-muted-foreground">
        <div class="container mx-auto grid grid-cols-1 gap-8 px-8 py-12 md:grid-cols-3 lg:px-16">

            <!-- Brand / Logo -->
            <div>
                <div class="flex items-center space-x-2 text-lg font-medium text-foreground">
                    <AppLogoIcon class="size-7 fill-current" />
                    <span>{{ appName }}</span>
                </div>
                <p class="mt-2 text-sm leading-relaxed text-muted-foreground">
                    A modern library system powered by Vue & Laravel.
                </p>
            </div>

            <!-- Navigation -->
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-foreground">Quick Links</h4>
                    <ul class="space-y-2">
                        <li>
                            <Link :href="route('home')" class="hover:text-foreground transition-colors">Dashboard</Link>
                        </li>
                        <li>
                            <Link :href="route('home')" class="hover:text-foreground transition-colors">Collections</Link>
                        </li>
                        <li>
                            <Link :href="route('home')" class="hover:text-foreground transition-colors">About</Link>
                        </li>
                    </ul>
                </div>
                <div>
                    <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-foreground">Support</h4>
                    <ul class="space-y-2">
                        <li>
                            <Link :href="route('home')" class="hover:text-foreground transition-colors">Contact</Link>
                        </li>
                        <li>
                            <Link :href="route('home')" class="hover:text-foreground transition-colors">FAQ</Link>
                        </li>
                        <li>
                            <Link :href="route('home')" class="hover:text-foreground transition-colors">Terms</Link>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Social / Actions -->
            <div class="flex flex-col items-start gap-4 md:items-end">
                <div class="flex space-x-4">
                    <Link :href="route('home')">
                        <Button variant="outline" size="icon" class="rounded-full">
                            <Users class="h-4 w-4" />
                        </Button>
                    </Link>
                    <Link :href="route('home')">
                        <Button variant="outline" size="icon" class="rounded-full">
                            <Users class="h-4 w-4" />
                        </Button>
                    </Link>
                    <Link :href="route('home')">
                        <Button variant="outline" size="icon" class="rounded-full">
                            <Users class="h-4 w-4" />
                        </Button>
                    </Link>
                </div>
                <p class="text-xs text-muted-foreground">
                    © {{ new Date().getFullYear() }} {{ appName }}. All rights reserved.
                </p>
            </div>
        </div>
    </footer>
</template>
