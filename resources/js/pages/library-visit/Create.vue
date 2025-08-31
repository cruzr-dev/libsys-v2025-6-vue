<script setup lang="ts">
/* -------------------- Imports -------------------- */
import { Head, Link, usePage } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { debounce } from 'lodash-es';

import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { AlertCircle, CircleCheckBig, X, UserRound } from 'lucide-vue-next';
import { Input } from '@/components/ui/input';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';

/* -------------------- Props -------------------- */
defineProps<{
    patron: object;
    purposes: object;
    search_button: boolean;
    is_logout: boolean;
}>();

/* -------------------- State & Lifecycle -------------------- */
const page = usePage();
const showAlert = ref(true);
const searchQuery = ref('');
const isLoading = ref(false);
const foundUser = ref(null);
const isDialogOpen = ref(false);

/* -------------------- Search Logic -------------------- */
const debouncedSearch = debounce(async (query: string) => {
    if (!query || query.length < 2) {
        foundUser.value = null;
        isLoading.value = false;
        return;
    }

    isLoading.value = true;

    try {
        // Build query parameters
        const params = new URLSearchParams({
            card_number: query // Changed from 'q' to 'card_number' for exact match
        });

        const response = await fetch(`/api/logger/patron/search?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        });

        if (response.ok) {
            const data = await response.json();

            if (data.user) {
                foundUser.value = data.user;
                isDialogOpen.value = true; // Open dialog when user is found
            } else {
                foundUser.value = null;
                // Optionally show a "no user found" message
            }
        } else {
            console.error('Record search failed:', response.statusText);
            foundUser.value = null;
        }
    } catch (error) {
        console.error('Record search error:', error);
        foundUser.value = null;
    } finally {
        isLoading.value = false;
    }
}, 300);

// Handle search input changes
const handleSearchInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    searchQuery.value = target.value;
    debouncedSearch(target.value);
};

onMounted(() => {
    if (page.props.flash.error) {
        setTimeout(() => {
            showAlert.value = false;
        }, 5000);
    }
});

/* -------------------- Types -------------------- */
interface Flash {
    success?: string | null;
    error?: string | null;
}

declare module '@inertiajs/core' {
    interface PageProps {
        flash: Flash;
    }
}
</script>

<template>
    <Head title="Patron Logger" />

    <div class="flex min-h-screen flex-col items-center text-[#1b1b18] lg:justify-center dark:bg-[#0a0a0a]">
        <!-- Hidden redirect link -->
        <Link :href="route('home')" class="fixed top-0 left-0 bg-red-500 opacity-0"> hi </Link>

        <!-- Error Alert -->
        <Alert v-if="page.props.flash.error && showAlert" class="absolute top-5 right-5 w-fit pr-8" variant="destructive">
            <AlertCircle class="h-4 w-4" />
            <button @click="showAlert = false" class="absolute top-2 right-2 rounded-full p-1 transition-colors hover:bg-red-100">
                <X class="h-4 w-4" />
            </button>
            <AlertTitle>Error</AlertTitle>
            <AlertDescription>
                {{ page.props.flash.error }}
            </AlertDescription>
        </Alert>

        <!-- Success Alert -->
        <Alert v-if="page.props.flash.success && showAlert" class="fixed top-5 right-5 z-30 w-fit max-w-md border-2 border-green-500 pr-8">
            <CircleCheckBig />
            <button @click="showAlert = false" class="absolute top-2 right-2 rounded-full p-1 transition-colors hover:bg-red-100">
                <X class="h-4 w-4" />
            </button>
            <AlertTitle>Success</AlertTitle>
            <AlertDescription>
                {{ page.props.flash.success }}
            </AlertDescription>
        </Alert>

        <!-- Main Content -->
        <div class="grid w-full opacity-100 transition-opacity duration-750 starting:opacity-0">
            <div class="flex min-w-full flex-col items-center p-8">
                <div class="relative w-full max-w-sm items-center">
                    <Input
                        id="search"
                        type="text"
                        placeholder="Enter card number..."
                        class="p-6 pl-10 md:text-xl"
                        v-model="searchQuery"
                        @input="handleSearchInput"
                        :disabled="isLoading"
                    />
                    <span class="absolute start-0 inset-y-0 flex items-center justify-center px-2">
                        <UserRound class="size-6 text-muted-foreground" />
                    </span>
                    <!-- Loading indicator -->
                    <div v-if="isLoading" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                        <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-gray-900"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Found Dialog -->
    <Dialog v-model:open="isDialogOpen">
        <DialogContent class="sm:max-w-xl grid-rows-[auto_minmax(0,1fr)_auto] p-0 max-h-[90dvh]">
            <DialogHeader class="p-6 pb-2">
                <DialogTitle class="text-xl font-semibold">User Found</DialogTitle>
                <DialogDescription>
                    User details retrieved successfully
                </DialogDescription>
            </DialogHeader>

            <div class="p-6 pt-2" v-if="foundUser">
                <div class="space-y-4">
                    <div class="flex items-center space-x-2">
                        <UserRound class="h-5 w-5 text-gray-500" />
                        <span class="text-lg font-medium">{{ foundUser.first_name }}</span>
                        <span class="text-lg font-medium">{{ foundUser.last_name || '' }}</span>
                    </div>

                    <div class="text-sm text-gray-600">
                        <p><strong>Card Number:</strong> {{ foundUser.card_number }}</p>
                        <p v-if="foundUser.email"><strong>Email:</strong> {{ foundUser.email }}</p>
                    </div>
                </div>
            </div>

            <DialogFooter class="p-6 pt-2">
                <Button @click="isDialogOpen = false" variant="outline">
                    Close
                </Button>
                <Button @click="isDialogOpen = false">
                    Continue
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
