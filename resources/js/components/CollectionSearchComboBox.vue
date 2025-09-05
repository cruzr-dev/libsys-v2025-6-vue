<script setup lang="ts">
import { ref, watch } from 'vue'
import { X, Book } from "lucide-vue-next"
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { debounce } from 'lodash-es'
import WelcomeSearchDialog from '@/components/WelcomeSearchDialog.vue';

// Reactive state
const searchQuery = ref('')
const searchResults = ref<any[]>([])
const isLoading = ref(false)
const selectedFilter = ref('all') // Default to "all records"

// Filter options
const filterOptions = [
    { value: 'all', label: 'Collections' },
    { value: 'book', label: 'Books' },
    { value: 'digital_resource', label: 'Multimedia Collection' },
    { value: 'periodical', label: 'Periodicals/Magazines' },
    { value: 'thesis', label: 'Thesis/Dissertations' }
]

// Debounced search function
const debouncedSearch = debounce(async (query: string) => {
    if (!query || query.length < 2) {
        searchResults.value = []
        isLoading.value = false
        return
    }

    isLoading.value = true

    try {
        // Build query parameters
        const params = new URLSearchParams({
            q: query
        })

        // Add filter parameter if not "all"
        if (selectedFilter.value !== 'all') {
            params.append('type', selectedFilter.value)
        }

        const response = await fetch(`/api/welcome/records/search?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        })

        if (response.ok) {
            const data = await response.json()
            searchResults.value = data.records || data || []
        } else {
            console.error('Record search failed:', response.statusText)
            searchResults.value = []
        }
    } catch (error) {
        console.error('Record search error:', error)
        searchResults.value = []
    } finally {
        isLoading.value = false
    }
}, 300)

// Watch for search query changes
watch(searchQuery, (newQuery) => {
    debouncedSearch(newQuery)
})

// Watch for filter changes and re-trigger search if there's a query
watch(selectedFilter, () => {
    if (searchQuery.value && searchQuery.value.length >= 2) {
        debouncedSearch(searchQuery.value)
    }
})

// Clear search - only clear search query and results, preserve filter
const clearSearch = () => {
    searchQuery.value = ''
    searchResults.value = []
    // Remove this line to preserve the filter: selectedFilter.value = 'all'
}

</script>

<template>
    <div class="grid space-y-4">
        <div class="flex gap-1">
            <!-- Filter Select Box -->
            <Select v-model="selectedFilter">
                <SelectTrigger class="w-48">
                    <SelectValue placeholder="Filter by type" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="option in filterOptions"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <!-- Search Input with Results -->
            <div class="relative flex-1">
                <div class="relative">
                    <Input
                        v-model="searchQuery"
                        class="pr-10"
                        placeholder="Search by title, or accession number..."
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
                    class="absolute top-full left-0 right-0 z-50 mt-1 bg-muted border rounded-md shadow-lg max-h-96 overflow-y-auto"
                >
                    <!-- Empty state -->
                    <div
                        v-if="!searchResults.length && !isLoading"
                        class="flex flex-col items-center p-4 text-center"
                    >
                        <Book class="size-8 text-muted-foreground mb-2" />
                        <p class="text-sm text-muted-foreground">
                            {{ searchQuery.length < 2 ? 'Type at least 2 characters to search' : 'No records found' }}
                        </p>
                    </div>

                    <!-- Search Results -->
                    <div v-if="searchResults.length > 0" class="p-1">
                        <div
                            v-for="record in searchResults"
                            :key="record.id"
                            class="flex my-1 bg-white flex-col items-start hover:bg-accent rounded-sm cursor-pointer"
                        >
                            <div class="flex w-full items-center justify-between">
                                <WelcomeSearchDialog :record="record"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
