<script setup lang="ts">
import { ref, watch } from 'vue'
import { Check, Search, BookOpen } from "lucide-vue-next"
import { cn } from "@/utils"
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
    selectedAuthor?: any
}>()

// Emits
const emit = defineEmits<{
    'update:selectedAuthor': [author: any]
    'authorSelected': [author: any]
}>()

// Reactive state
const searchQuery = ref('')
const searchResults = ref<any[]>([])
const isLoading = ref(false)
const selectedAuthor = ref(props.selectedAuthor || null)

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
        const response = await fetch(`/api/authors/search?q=${encodeURIComponent(query)}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        })

        if (response.ok) {
            const data = await response.json()
            searchResults.value = data.authors || data || []
        } else {
            console.error('Author search failed:', response.statusText)
            searchResults.value = []
        }
    } catch (error) {
        console.error('Author search error:', error)
        searchResults.value = []
    } finally {
        isLoading.value = false
    }
}, 300)

// Watch for search query changes
watch(searchQuery, (newQuery) => {
    debouncedSearch(newQuery)
})

// Handle author selection
const handleAuthorSelect = (author: any) => {
    selectedAuthor.value = author
    emit('update:selectedAuthor', author)
    emit('authorSelected', author)
}

// Display function for selected author
const displayValue = (author: any) => {
    if (!author) return ''
    return `${author.name} (${author.author_number})`
}
</script>

<template>
    <Combobox
        v-model="selectedAuthor"
        by="author_number"
        @update:model-value="handleAuthorSelect"
    >
        <ComboboxAnchor class="border w-full">
            <div class="relative w-full max-w-sm items-center">
                <ComboboxInput
                    v-model="searchQuery"
                    class="pl-2"
                    :display-value="displayValue"
                    placeholder="Search author by name or number..."
                />
                <span class="absolute start-0 inset-y-0 flex items-center justify-center px-3">
                    <Search
                        v-if="!isLoading"
                        class="size-4 text-muted-foreground"
                    />
                    <span
                        v-else
                        class="size-4 animate-spin rounded-full border-2 border-muted-foreground border-t-transparent"
                    />
                </span>
            </div>
        </ComboboxAnchor>

        <ComboboxList>
            <ComboboxEmpty>
                <div class="flex flex-col items-center p-4 text-center">
                    <BookOpen class="size-8 text-muted-foreground mb-2" />
                    <p class="text-sm text-muted-foreground">
                        {{ searchQuery.length < 2 ? 'Type at least 2 characters to search' : 'No authors found' }}
                    </p>
                </div>
            </ComboboxEmpty>

            <ComboboxGroup v-if="searchResults.length > 0">
                <ComboboxItem
                    v-for="author in searchResults"
                    :key="author.author_number"
                    :value="author"
                    class="flex items-center justify-between py-2"
                >
                    <span>
                      {{ author.name }}
                      <span v-if="author.author_number">({{ author.author_number }})</span>
                    </span>
                    <ComboboxItemIndicator>
                        <Check :class="cn('ml-auto h-4 w-4')" />
                    </ComboboxItemIndicator>
                </ComboboxItem>
            </ComboboxGroup>
        </ComboboxList>
    </Combobox>
</template>
