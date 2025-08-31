<script setup lang="ts">
import { ref, watch } from 'vue'
import { Check, Search, X, Book } from "lucide-vue-next"
import {
    Combobox,
    ComboboxAnchor,
    ComboboxEmpty,
    ComboboxGroup,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxList
} from "@/components/ui/combobox"
import { Button } from "@/components/ui/button"
import { debounce } from 'lodash-es'

// Reactive state
const searchQuery = ref('')
const searchResults = ref<any[]>([])
const isLoading = ref(false)
const selectedRecord = ref(null)

// Debounced search function
const debouncedSearch = debounce(async (query: string) => {
    if (!query || query.length < 2) {
        searchResults.value = []
        isLoading.value = false
        return
    }

    isLoading.value = true

    try {
        const response = await fetch(`/api/welcome/records/search?q=${encodeURIComponent(query)}`, {
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

// Handle record selection
const handleRecordSelect = (record: any) => {
    selectedRecord.value = record
}

// Clear search filter
const clearFilter = () => {
    searchQuery.value = ''
    searchResults.value = []
    selectedRecord.value = null
}

// Display function for selected record
const displayValue = (record: any) => {
    if (!record) return ''
    const title = record.title || 'Untitled'
    const accessionNumber = record.accession_number || ''
    return accessionNumber ? `${title} (${accessionNumber})` : title
}

// Get resource type display name
const getResourceType = (record: any) => {
    if (record.book) return 'Book'
    if (record.digital_resource) return 'Multimedia'
    if (record.periodical) return 'Periodical'
    if (record.thesis) return 'Thesis'
    return 'Collection'
}
</script>

<template>
    <div class="grid space-y-4">
        <Combobox
            v-model="selectedRecord"
            by="id"
            @update:model-value="handleRecordSelect"
        >
            <ComboboxAnchor class="w-full border-1 rounded-lg focus-within:ring-2 focus-within:ring-[var(--ring)]">
                <div class="relative w-full">
                    <ComboboxInput
                        v-model="searchQuery"
                        class="w-full pl-3 pr-10 py-2 dark:text-muted-foreground"
                        :display-value="displayValue"
                        placeholder="Search by title, or accession number..."
                    />

                    <!-- Clear button always visible -->
                    <Button
                        variant="ghost"
                        class="absolute top-0 right-0 h-full px-2"
                        @click="clearFilter"
                    >
                        <X class="h-4 w-4" />
                    </Button>

                    <!-- Loading spinner -->
                    <div
                        v-if="isLoading"
                        class="absolute top-0 right-0 h-full px-2 flex items-center justify-center pointer-events-none"
                    >
                        <div class="h-4 w-4 animate-spin rounded-full border-2 border-muted-foreground border-t-transparent" />
                    </div>
                </div>
            </ComboboxAnchor>

            <ComboboxList>
                <ComboboxEmpty>
                    <div class="flex flex-col items-center p-4 text-center">
                        <Book class="size-8 text-muted-foreground mb-2" />
                        <p class="text-sm text-muted-foreground">
                            {{ searchQuery.length < 2 ? 'Type at least 2 characters to search' : 'No records found' }}
                        </p>
                    </div>
                </ComboboxEmpty>

                <ComboboxGroup v-if="searchResults.length > 0">
                    <ComboboxItem
                        v-for="record in searchResults"
                        :key="record.id"
                        :value="record"
                        class="flex flex-col items-start py-3"
                    >
                        <div class="flex w-full items-center justify-between">
                            <div class="flex flex-col flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-medium truncate">
                                        {{ record.title || 'Untitled' }}
                                    </span>
                                    <span class="flex-shrink-0 inline-flex items-center px-2 py-1 text-xs font-medium bg-primary/10 text-primary rounded-md">
                                        {{ getResourceType(record) }}
                                    </span>
                                </div>

                                <div class="flex flex-col text-sm text-muted-foreground space-y-0.5">
                                    <span v-if="record.accession_number">
                                        Accession: {{ record.accession_number }}
                                    </span>
                                </div>
                            </div>

                            <ComboboxItemIndicator>
                                <Check class="ml-2 h-4 w-4 flex-shrink-0" />
                            </ComboboxItemIndicator>
                        </div>
                    </ComboboxItem>
                </ComboboxGroup>
            </ComboboxList>
        </Combobox>
    </div>
</template>
