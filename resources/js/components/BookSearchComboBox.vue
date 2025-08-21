<script setup lang="ts">
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { Check, Search, Book } from "lucide-vue-next"
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
import { debounce } from 'lodash-es'

// Props
const props = defineProps<{
    selectedBook?: any
}>()

// Emits
const emit = defineEmits<{
    'update:selectedBook': [book: any]
    'bookSelected': [book: any]
}>()

// Reactive state
const searchQuery = ref('')
const searchResults = ref<any[]>([])
const isLoading = ref(false)
const selectedBook = ref(props.selectedBook || null)

// Debounced search function
const debouncedSearch = debounce(async (query: string) => {
    if (!query || query.length < 2) {
        searchResults.value = []
        isLoading.value = false
        return
    }

    isLoading.value = true

    try {
        // Make request to your Laravel backend
        const response = await fetch(`/api/books/search?q=${encodeURIComponent(query)}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        })

        if (response.ok) {
            const data = await response.json()
            searchResults.value = data.books || data || []
        } else {
            console.error('Search failed:', response.statusText)
            searchResults.value = []
        }
    } catch (error) {
        console.error('Search error:', error)
        searchResults.value = []
    } finally {
        isLoading.value = false
    }
}, 300)

// Watch for search query changes
watch(searchQuery, (newQuery) => {
    debouncedSearch(newQuery)
})

// Handle book selection
const handleBookSelect = (book: any) => {
    selectedBook.value = book
    emit('update:selectedBook', book)
    emit('bookSelected', book)

    // Optional: Navigate with the selected book's accession number
    if (book?.accession_number) {
        router.get('/borrowings/create', {
            searchAcc: book.accession_number
        }, {
            preserveState: true,
            replace: true
        })
    }
}

// Display function for selected book
const displayValue = (book: any) => {
    if (!book) return ''
    return `${book.title} (${book.accession_number})`
}
</script>

<template>
    <Combobox
        v-model="selectedBook"
        by="accession_number"
        @update:model-value="handleBookSelect"
        class=""
    >
        <ComboboxAnchor class="w-full border-1 rounded-lg">
            <div class="relative w-full items-center ">
                <ComboboxInput
                    v-model="searchQuery"
                    class="pl-2"
                    :display-value="displayValue"
                    placeholder="Search by title, author, or accession number..."
                />
                <span class="absolute start-0 inset-y-0 flex items-center justify-center px-3">
                    <Search
                        v-if="!isLoading"
                        class="size-4 text-muted-foreground"
                    />
                    <div
                        v-else
                        class="size-4 animate-spin rounded-full border-2 border-muted-foreground border-t-transparent"
                    />
                </span>
            </div>
        </ComboboxAnchor>

        <ComboboxList>
            <ComboboxEmpty>
                <div class="flex flex-col items-center p-4 text-center">
                    <Book class="size-8 text-muted-foreground mb-2" />
                    <p class="text-sm text-muted-foreground">
                        {{ searchQuery.length < 2 ? 'Type at least 2 characters to search' : 'No books found' }}
                    </p>
                </div>
            </ComboboxEmpty>

            <ComboboxGroup v-if="searchResults.length > 0">
                <ComboboxItem
                    v-for="book in searchResults"
                    :key="book.accession_number || book.id"
                    :value="book"
                    class="flex flex-col items-start py-3"
                >
                    <div class="flex w-full items-center justify-between">
                        <div class="flex flex-col">
                            <span class="font-medium">{{ book.title }}</span>
                            <span class="text-xs text-muted-foreground">
                                Acc. No: {{ book.accession_number }}
                                <span v-if="book.isbn"> | ISBN: {{ book.isbn }}</span>
                            </span>
                            <span
                                v-if="book.status"
                                class="text-xs mt-1"
                                :class="{
                                    'text-green-600': book.status === 'available',
                                    'text-red-600': book.status === 'borrowed',
                                    'text-yellow-600': book.status === 'reserved'
                                }"
                            >
                                Status: {{ book.status }}
                            </span>
                        </div>

                        <ComboboxItemIndicator>
                            <Check class="ml-2 h-4 w-4" />
                        </ComboboxItemIndicator>
                    </div>
                </ComboboxItem>
            </ComboboxGroup>
        </ComboboxList>
    </Combobox>
</template>
