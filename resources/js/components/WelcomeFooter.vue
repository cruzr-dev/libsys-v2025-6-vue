<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import AppLogoIcon from '@/components/AppLogoIcon.vue'
import { Mail } from 'lucide-vue-next'

// Define props correctly in <script setup>
const props = defineProps<{
    appName: string
}>()

// Extract appName
const appName = props.appName

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
                    <a
                        href="https://www.facebook.com/USePTMLRC"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <Button variant="outline" size="icon" class="rounded-full">
                            <svg role="img" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <title>Facebook</title>
                                <path d="M9.101 23.691v-7.98H6.627v-3.667h2.474v-1.58c0-4.085 1.848-5.978 5.858-5.978.401 0 .955.042 1.468.103a8.68 8.68 0 0 1 1.141.195v3.325a8.623 8.623 0 0 0-.653-.036 26.805 26.805 0 0 0-.733-.009c-.707 0-1.259.096-1.675.309a1.686 1.686 0 0 0-.679.622c-.258.42-.374.995-.374 1.752v1.297h3.919l-.386 2.103-.287 1.564h-3.246v8.245C19.396 23.238 24 18.179 24 12.044c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.628 3.874 10.35 9.101 11.647Z"/>
                            </svg>
                        </Button>
                    </a>

                    <a href="mailto:library_tagum-mabini@usep.edu.ph">
                        <Button variant="outline" size="icon" class="rounded-full">
                            <Mail class="h-4 w-4" />
                        </Button>
                    </a>

                </div>
                <p class="text-xs text-muted-foreground">
                    © {{ new Date().getFullYear() }} {{ appName }}. All rights reserved.
                </p>
            </div>
        </div>
    </footer>
</template>
