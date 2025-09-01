<script setup lang="ts">
import { ref, watch } from 'vue'
import { X, Book } from "lucide-vue-next"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { debounce } from 'lodash-es'
import LoggerSearchDialog from '@/components/LoggerSearchDialog.vue'

// Reactive state
const searchQuery = ref('')
const searchResults = ref<any[]>([])
const isLoading = ref(false)
const selectedUser = ref<any | null>(null)
const dialogOpen = ref(false)

// For auto-search: 000088888 OR 0000-88888 (must be exactly 9 digits)
const isLibraryIdQuery = (query: string) => {
    const trimmed = query.trim()
    return /^\d{9}$/.test(trimmed) || /^\d{4}-\d{5}$/.test(trimmed)
}

// For ENTER-based search: any numeric format
const isNumericQuery = (query: string) => {
    const trimmed = query.trim()
    return /^\d+$/.test(trimmed) || /^\d{4}-\d{5}$/.test(trimmed)
}

// Debounced search function for name search
const debouncedSearch = debounce(async (query: string) => {
    // Reset selected user and dialog
    selectedUser.value = null
    dialogOpen.value = false

    // Only allow search when query starts with `--` for name search
    if (!query.startsWith('--')) {
        searchResults.value = []
        isLoading.value = false
        return
    }

    // Remove the `--` prefix for the actual search
    const actualQuery = query.slice(2).trim()

    if (!actualQuery || actualQuery.length < 2) {
        searchResults.value = []
        isLoading.value = false
        return
    }

    isLoading.value = true

    try {
        const params = new URLSearchParams({ q: actualQuery })

        const response = await fetch(`/api/logger/patron/search-by-name?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        })

        if (response.ok) {
            const data = await response.json()
            searchResults.value = data.users || data || []
        } else {
            console.error('user search failed:', response.statusText)
            searchResults.value = []
        }
    } catch (error) {
        console.error('user search error:', error)
        searchResults.value = []
    } finally {
        isLoading.value = false
    }
}, 300)

// Search by library number
const searchByLibraryNumber = async (query: string) => {
    isLoading.value = true
    searchResults.value = []

    try {
        const params = new URLSearchParams({ library_id: query })

        const response = await fetch(`/api/logger/patron/search-by-id?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        })

        if (response.ok) {
            const data = await response.json()
            if (data.user) {
                selectedUser.value = data.user
                dialogOpen.value = true
            } else {
                selectedUser.value = null
                dialogOpen.value = false
            }
        } else {
            console.error('library number search failed:', response.statusText)
            selectedUser.value = null
        }
    } catch (error) {
        console.error('library number search error:', error)
        selectedUser.value = null
    } finally {
        isLoading.value = false
    }
}

// Debounced library ID search for automatic triggering
const debouncedLibrarySearch = debounce(async (query: string) => {
    if (isLibraryIdQuery(query)) {
        // Keep the full format but remove dash if present for API call
        const formattedQuery = query.includes('-') ? query.replace('-', '') : query
        await searchByLibraryNumber(formattedQuery)
    }
}, 500) // Slightly longer delay to avoid too many API calls while typing

// Watch for search query changes
watch(searchQuery, (newQuery) => {
    if (newQuery.startsWith('--')) {
        // Name-based search
        debouncedSearch(newQuery)
    } else if (isLibraryIdQuery(newQuery)) {
        // Auto-trigger library ID search
        searchResults.value = [] // Clear any previous name search results
        debouncedLibrarySearch(newQuery)
    } else {
        // Clear results for invalid queries
        searchResults.value = []
        selectedUser.value = null
        dialogOpen.value = false
    }
})

// Handle enter key for any numeric queries (keeping existing functionality)
const handleKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Enter' && isNumericQuery(searchQuery.value)) {
        // Cancel any pending debounced search and search immediately
        debouncedLibrarySearch.cancel()
        const formattedQuery = searchQuery.value.includes('-') ? searchQuery.value.replace('-', '') : searchQuery.value
        searchByLibraryNumber(formattedQuery)
    }
}

// Handle user selection from name search results
const handleUserSelect = (user: any) => {
    selectedUser.value = user
    dialogOpen.value = true
}

// Clear search
const clearSearch = () => {
    searchQuery.value = ''
    searchResults.value = []
    selectedUser.value = null
    dialogOpen.value = false
    // Cancel any pending searches
    debouncedSearch.cancel()
    debouncedLibrarySearch.cancel()
}

// Handle dialog close
const handleDialogClose = () => {
    clearSearch()
}
</script>

<template>
    <div class="grid space-y-4">
        <!-- Search Input with Results -->
        <div class="relative">
            <div class="relative">
                <Input
                    v-model="searchQuery"
                    class="pr-10"
                    placeholder="Library ID: 000088888 or 0000-88888"
                    @keydown="handleKeydown"
                />

                <!-- Clear button -->
                <Button
                    v-if="searchQuery"
                    variant="ghost"
                    class="absolute top-0 right-0 h-full px-2"
                    @click="clearSearch"
                >
                    <X class="h-4 w-4" />
                </Button>

                <!-- Loading spinner -->
                <div
                    v-if="isLoading"
                    class="absolute top-0 right-8 h-full px-2 flex items-center justify-center pointer-events-none"
                >
                    <div class="h-4 w-4 animate-spin rounded-full border-2 border-muted-foreground border-t-transparent" />
                </div>
            </div>

            <!-- Search Results Dropdown -->
            <div
                v-if="searchQuery && !selectedUser"
                class="absolute top-full left-0 right-0 z-50 mt-1 bg-popover border rounded-md shadow-lg max-h-96 overflow-y-auto"
            >
                <!-- Empty state -->
                <div
                    v-if="!searchResults.length && !isLoading"
                    class="flex flex-col items-center p-4 text-center"
                >
                    <Book class="size-8 text-muted-foreground mb-2" />
                    <p class="text-sm text-muted-foreground">
                        No results found
                    </p>
                </div>

                <!-- Search Results -->
                <div v-if="searchResults.length > 0" class="p-1">
                    <div
                        v-for="user in searchResults"
                        :key="user.id"
                        class="flex flex-col items-start hover:bg-accent rounded-sm cursor-pointer"
                    >
                        <LoggerSearchDialog
                            :user="user"
                            @trigger="handleUserSelect(user)"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Auto-opened dialog for numeric search -->
        <LoggerSearchDialog
            v-if="selectedUser"
            :user="selectedUser"
            v-model:open="dialogOpen"
            @close="handleDialogClose"
        />
    </div>
</template>
