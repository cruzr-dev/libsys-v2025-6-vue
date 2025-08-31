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

/* -------------------- Types -------------------- */
interface User {
    first_name: string;
    last_name?: string;
    library_id: string;
    email?: string;
}

interface Flash {
    success?: string | null;
    error?: string | null;
}

declare module '@inertiajs/core' {
    interface PageProps {
        flash: Flash;
    }
}

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
const foundUser = ref<User | null>(null);
const isDialogOpen = ref(false);

/* -------------------- Utility Functions -------------------- */
const isExactNineDigits = (query: string): boolean => {
    return /^\d{9}$/.test(query);
};

const buildSearchParams = (query: string): URLSearchParams => {
    return new URLSearchParams({
        library_id: query.trim()
    });
};

/* -------------------- API Functions -------------------- */
const searchUser = async (query: string): Promise<User | null> => {
    if (!query || query.length < 2) {
        return null;
    }

    const params = buildSearchParams(query);

    try {
        const response = await fetch(`/api/logger/patron/search?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        });

        if (response.ok) {
            const data = await response.json();
            return data.user || null;
        } else {
            console.error('User search failed:', response.statusText);
            return null;
        }
    } catch (error) {
        console.error('User search error:', error);
        return null;
    }
};

/* -------------------- Search Logic -------------------- */
const performSearch = async (query: string, showLoadingState = true): Promise<void> => {
    if (showLoadingState) {
        isLoading.value = true;
    }

    try {
        const user = await searchUser(query);

        if (user) {
            foundUser.value = user;
            isDialogOpen.value = true;
        } else {
            foundUser.value = null;
        }
    } finally {
        if (showLoadingState) {
            isLoading.value = false;
        }
    }
};

// Debounced search for 9-digit numbers only
const debouncedSearch = debounce(async (query: string) => {
    if (!isExactNineDigits(query)) {
        foundUser.value = null;
        isLoading.value = false;
        return;
    }

    await performSearch(query, true);
}, 300);

// Manual search for non-9-digit inputs (triggered by Enter key)
const performManualSearch = async (query: string): Promise<void> => {
    if (!query || query.length < 2) {
        foundUser.value = null;
        return;
    }

    await performSearch(query, true);
};

/* -------------------- Event Handlers -------------------- */
const handleSearchInput = (event: Event): void => {
    const target = event.target as HTMLInputElement;
    const query = target.value;

    searchQuery.value = query;

    if (isExactNineDigits(query)) {
        // Auto-search for 9-digit numbers
        debouncedSearch(query);
    } else {
        // Clear results for non-9-digit inputs
        foundUser.value = null;
        isLoading.value = false;
    }
};

const handleKeyDown = (event: KeyboardEvent): void => {
    if (event.key === 'Enter') {
        const target = event.target as HTMLInputElement;
        const query = target.value.trim();

        // Only perform manual search for non-9-digit inputs
        // 9-digit inputs are handled automatically by debounced search
        if (query && !isExactNineDigits(query)) {
            performManualSearch(query);
        }
    }
};

const closeDialog = (): void => {
    isDialogOpen.value = false;
};

const dismissAlert = (): void => {
    showAlert.value = false;
};

/* -------------------- Lifecycle -------------------- */
onMounted(() => {
    if (page.props.flash.error) {
        setTimeout(() => {
            showAlert.value = false;
        }, 5000);
    }
});
</script>

<template>
    <Head title="Patron Logger" />

    <div class="flex min-h-screen flex-col items-center text-[#1b1b18] lg:justify-center dark:bg-[#0a0a0a]">
        <!-- Hidden redirect link -->
        <Link :href="route('home')" class="fixed top-0 left-0 bg-red-500 opacity-0">
            hi
        </Link>

        <!-- Error Alert -->
        <Alert
            v-if="page.props.flash.error && showAlert"
            class="absolute top-5 right-5 w-fit pr-8"
            variant="destructive"
        >
            <AlertCircle class="h-4 w-4" />
            <button
                @click="dismissAlert"
                class="absolute top-2 right-2 rounded-full p-1 transition-colors hover:bg-red-100"
                aria-label="Close alert"
            >
                <X class="h-4 w-4" />
            </button>
            <AlertTitle>Error</AlertTitle>
            <AlertDescription>
                {{ page.props.flash.error }}
            </AlertDescription>
        </Alert>

        <!-- Success Alert -->
        <Alert
            v-if="page.props.flash.success && showAlert"
            class="fixed top-5 right-5 z-30 w-fit max-w-md border-2 border-green-500 pr-8"
        >
            <CircleCheckBig />
            <button
                @click="dismissAlert"
                class="absolute top-2 right-2 rounded-full p-1 transition-colors hover:bg-green-100"
                aria-label="Close alert"
            >
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
                        @keydown="handleKeyDown"
                        :disabled="isLoading"
                        autocomplete="off"
                    />
                    <span class="absolute start-0 inset-y-0 flex items-center justify-center px-2">
                        <UserRound class="size-6 text-muted-foreground" />
                    </span>
                    <!-- Loading indicator -->
                    <div
                        v-if="isLoading"
                        class="absolute right-3 top-1/2 transform -translate-y-1/2"
                    >
                        <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-gray-900"></div>
                    </div>
                </div>

                <!-- Search instruction -->
                <p class="mt-4 text-sm text-gray-500 text-center max-w-sm">
                    Enter a 9-digit card number for instant search, or press Enter for other formats
                </p>
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

                    <div class="text-sm text-gray-600 space-y-1">
                        <p><strong>Card Number:</strong> {{ foundUser.library_id }}</p>
                        <p v-if="foundUser.email"><strong>Email:</strong> {{ foundUser.email }}</p>
                    </div>
                </div>
            </div>

            <DialogFooter class="p-6 pt-2">
                <Button @click="closeDialog" variant="outline">
                    Close
                </Button>
                <Button @click="closeDialog">
                    Continue
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
