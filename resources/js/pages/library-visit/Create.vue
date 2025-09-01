<script setup lang="ts">
/* -------------------- Imports -------------------- */
import { Head, Link, usePage } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { debounce } from 'lodash-es';

import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { AlertCircle, X, UserRound } from 'lucide-vue-next';
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

interface SearchResult {
    user: User | null;
    users?: User[];
    message: string;
    success: boolean;
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
const foundUsers = ref<User[]>([]);
const isDialogOpen = ref(false);
const searchMessage = ref('');

/* -------------------- Utility Functions -------------------- */
const isExactNineDigits = (query: string): boolean => {
    return /^\d{9}$/.test(query);
};

const isValidDashFormat = (query: string): boolean => {
    return /^\d{4}-\d{5}$/.test(query);
};

const isInstantSearchFormat = (query: string): boolean => {
    return isExactNineDigits(query) || isValidDashFormat(query);
};

const isSecretPassFormat = (query: string): boolean => {
    return query.startsWith('--');
};

const normalizeDashFormat = (query: string): string => {
    return query.replace('-', '');
};

const buildSearchParams = (query: string, isSecretPass: boolean = false): URLSearchParams => {
    if (isSecretPass) {
        return new URLSearchParams({
            query: query.replace('--', '').trim()
        });
    }
    const normalizedQuery = isValidDashFormat(query) ? normalizeDashFormat(query) : query;
    return new URLSearchParams({
        library_id: normalizedQuery.trim()
    });
};

/* -------------------- API Functions -------------------- */
const searchUser = async (query: string, isSecretPass: boolean = false): Promise<SearchResult> => {
    if (!query || query.length < 2) {
        return {
            user: null,
            users: [],
            message: 'Please enter at least 2 characters',
            success: false
        };
    }

    const params = buildSearchParams(query, isSecretPass);
    const endpoint = isSecretPass ? '/api/logger/patron/search-by-name' : '/api/logger/patron/search';

    try {
        const response = await fetch(`${endpoint}?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        });

        const data = await response.json();

        if (response.ok) {
            if (isSecretPass) {
                return {
                    user: null,
                    users: data.users || [],
                    message: data.users?.length ? 'Users found' : 'No users found',
                    success: true
                };
            }
            return {
                user: data.user || null,
                users: [],
                message: data.message || 'User found successfully',
                success: true
            };
        } else if (response.status === 404) {
            return {
                user: null,
                users: [],
                message: 'No user found with the provided query',
                success: false
            };
        } else if (response.status === 422) {
            return {
                user: null,
                users: [],
                message: 'Invalid input provided',
                success: false
            };
        } else {
            return {
                user: null,
                users: [],
                message: 'An error occurred while searching',
                success: false
            };
        }
    } catch (error) {
        console.error('User search error:', error);
        return {
            user: null,
            users: [],
            message: 'Network error occurred while searching',
            success: false
        };
    }
};

/* -------------------- Search Logic -------------------- */
const performSearch = async (query: string, showLoadingState = true): Promise<void> => {
    if (showLoadingState) {
        isLoading.value = true;
    }

    try {
        const isSecretPass = isSecretPassFormat(query);
        const result = await searchUser(query, isSecretPass);

        if (isSecretPass) {
            foundUsers.value = result.users || [];
            searchMessage.value = result.message;
            searchQuery.value = ''; // Clear input after search
        } else if (result.success && result.user) {
            foundUser.value = result.user;
            isDialogOpen.value = true;
            foundUsers.value = [];
            searchMessage.value = '';
            searchQuery.value = '';
        } else {
            foundUser.value = null;
            foundUsers.value = [];
            searchMessage.value = result.message;
        }
    } finally {
        if (showLoadingState) {
            isLoading.value = false;
        }
    }
};

const debouncedSearch = debounce(async (query: string) => {
    if (!isInstantSearchFormat(query) && !isSecretPassFormat(query)) {
        foundUser.value = null;
        foundUsers.value = [];
        isLoading.value = false;
        searchMessage.value = '';
        return;
    }

    await performSearch(query, true);
}, 300);

const performManualSearch = async (query: string): Promise<void> => {
    if (!query || query.length < 2) {
        foundUser.value = null;
        foundUsers.value = [];
        searchMessage.value = 'Please enter at least 2 characters';
        return;
    }

    await performSearch(query, true);
};

/* -------------------- Event Handlers -------------------- */
const handleSearchInput = (event: Event): void => {
    const target = event.target as HTMLInputElement;
    const query = target.value;

    searchQuery.value = query;
    searchMessage.value = '';

    if (isInstantSearchFormat(query) || isSecretPassFormat(query)) {
        debouncedSearch(query);
    } else {
        foundUser.value = null;
        foundUsers.value = [];
        isLoading.value = false;
    }
};

const handleKeyDown = (event: KeyboardEvent): void => {
    if (event.key === 'Enter') {
        const target = event.target as HTMLInputElement;
        const query = target.value.trim();

        if (query && !isInstantSearchFormat(query) && !isSecretPassFormat(query)) {
            performManualSearch(query);
        }
    }
};

const closeDialog = (): void => {
    isDialogOpen.value = false;
};

const selectUser = (user: User): void => {
    foundUser.value = user;
    isDialogOpen.value = true;
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

        <!-- Flash Alert -->
        <Alert
            v-if="showAlert && page.props.flash.error"
            class="fixed top-5 right-5 z-30 w-fit max-w-md pr-8"
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

        <!-- Main Content -->
        <div class="grid w-full opacity-100 transition-opacity duration-750 starting:opacity-0">
            <div class="flex min-w-full flex-col items-center p-8">
                <div class="relative w-full max-w-80 items-center">
                    <Input
                        id="search"
                        type="text"
                        placeholder="Enter Library ID or -- for name search"
                        class="p-4 pl-10 md:text-lg pr-10"
                        v-model="searchQuery"
                        @input="handleSearchInput"
                        @keydown="handleKeyDown"
                        :disabled="isLoading"
                        autocomplete="off"
                    />
                    <span class="absolute start-0 inset-y-0 flex items-center justify-center px-2">
                        <UserRound class="size-6 text-muted-foreground" />
                    </span>
                    <!-- Clear button -->
                    <Button
                        v-if="searchQuery"
                        variant="ghost"
                        class="absolute right-0 top-1/2 transform -translate-y-1/2 px-2"
                        @click="searchQuery = ''; foundUser = null; foundUsers = []; searchMessage = '';"
                    >
                        <X class="size-5 text-muted-foreground" />
                    </Button>
                    <!-- Loading indicator -->
                    <div
                        v-if="isLoading"
                        class="absolute right-8 top-1/2 transform -translate-y-1/2"
                    >
                        <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-gray-900"></div>
                    </div>
                </div>

                <!-- Search instruction -->
                <p class="mt-4 text-sm text-gray-500 text-center max-w-sm">
                    Enter a 9-digit library id (000000000), dash format (0000-00000), or start with -- for name search
                </p>

                <!-- Search Results -->
                <div v-if="foundUsers.length || searchMessage" class="mt-4 w-full max-w-80">
                    <div v-if="foundUsers.length" class="space-y-2">
                        <Button
                            v-for="user in foundUsers"
                            :key="user.library_id"
                            variant="ghost"
                            class="w-full text-left justify-start p-4 hover:bg-gray-100"
                            @click="selectUser(user)"
                        >
                            <div class="flex items-center space-x-2">
                                <UserRound class="h-5 w-5 text-gray-500" />
                                <span>{{ user.first_name }} {{ user.last_name || '' }}</span>
                                <span class="text-sm text-gray-500">({{ user.library_id }})</span>
                            </div>
                        </Button>
                    </div>
                    <p v-else class="text-sm text-gray-500 text-center">{{ searchMessage }}</p>
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

                    <div class="text-sm text-gray-600 space-y-1">
                        <p><strong>Library ID:</strong> {{ foundUser.library_id }}</p>
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
