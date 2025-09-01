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

// Debounced search function
const debouncedSearch = debounce(async (query: string) => {
    // Only allow search when query starts with `--`
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

// Watch for search query changes
watch(searchQuery, (newQuery) => {
    debouncedSearch(newQuery)
})

// Clear search
const clearSearch = () => {
    searchQuery.value = ''
    searchResults.value = []
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
                    placeholder="Enter Library ID, e.g. 000088888 or 0000-88888"
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
                v-if="searchQuery"
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
                        <div class="flex w-full items-center justify-between">
                            <LoggerSearchDialog :user="user"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
