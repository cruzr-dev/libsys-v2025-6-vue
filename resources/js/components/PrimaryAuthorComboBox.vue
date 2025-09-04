<script setup lang="ts">
import { ref, computed, watch } from "vue"
import { Check, Search, User } from "lucide-vue-next"
import { cn } from "@/utils"
import {
    Combobox,
    ComboboxAnchor,
    ComboboxEmpty,
    ComboboxGroup,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxList,
} from "@/components/ui/combobox"
import { debounce } from 'lodash-es'

// Props
const props = defineProps<{
    selectedAuthor?: { value: string; label: string } | null
}>()

// Emits
const emit = defineEmits<{
    'update:selectedAuthor': [author: any]
    'authorSelected': [author: any]
}>()

// Reactive state
const searchQuery = ref('')
const searchResults = ref<{ value: string; label: string }[]>([])
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
        const response = await fetch(`/api/authors/search?q=${encodeURIComponent(query)}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        })

        if (response.ok) {
            const data = await response.json()
            searchResults.value = (data.authors || data || []).map((author: any) => ({
                value: author.id.toString(),
                label: author.name,
            }))
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

// Computed property for display value
const displayValue = computed(() => (author: any) => {
    return author?.label ?? ''
})

// Computed property for displayed authors
const displayAuthors = computed(() => searchResults.value)
</script>

<template>
    <div class="grid space-y-2">
        <Combobox
            v-model="selectedAuthor"
            by="value"
            @update:model-value="handleAuthorSelect"
        >
            <ComboboxAnchor class="w-full max-w-sm border rounded-lg focus-within:ring-2 focus-within:ring-[var(--ring)]">
                <div class="relative w-full items-center">
                    <ComboboxInput
                        v-model="searchQuery"
                        :display-value="displayValue"
                        placeholder="Search authors by name..."
                        class="pl-2 pr-10"
                        :disabled="isLoading"
                    />
                    <span class="absolute left-0 inset-y-0 flex items-center justify-center px-3">
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
                        <User class="size-6 text-muted-foreground mb-2" />
                        <p class="text-sm text-muted-foreground">
                            {{ searchQuery.length < 2 ? 'Type at least 2 characters to search' : 'No authors found' }}
                        </p>
                    </div>
                </ComboboxEmpty>

                <ComboboxGroup v-if="displayAuthors.length">
                    <ComboboxItem
                        v-for="author in displayAuthors"
                        :key="author.value"
                        :value="author"
                        class="flex items-center py-2"
                    >
                        <span class="font-medium">{{ author.label }}</span>
                        <ComboboxItemIndicator>
                            <Check :class="cn('ml-auto h-4 w-4')" />
                        </ComboboxItemIndicator>
                    </ComboboxItem>
                </ComboboxGroup>
            </ComboboxList>
        </Combobox>
    </div>
</template>
