<script setup lang="ts">
import { ref, computed } from "vue"
import { Check, Search } from "lucide-vue-next"
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

// selected value
const selectedAuthor = ref<{ value: string; label: string } | null>(null)

// search results
const searchResults = ref<{ value: string; label: string }[]>([])
const isLoading = ref(false)

// computed property to show loading state or results
const displayAuthors = computed(() => searchResults.value)

// search authors from API
const searchAuthors = async (query: string) => {
    if (!query.trim()) {
        searchResults.value = []
        return
    }

    isLoading.value = true
    try {
        const response = await fetch(`/api/authors/search?q=${encodeURIComponent(query)}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })

        if (response.ok) {
            const data = await response.json()
            searchResults.value = (data.authors || data || []).map((author: any) => ({
                value: author.id.toString(), // Ensure value is a string
                label: author.name,
            }))
        } else {
            console.error("Author search failed:", response.statusText)
            searchResults.value = []
        }
    } catch (error) {
        console.error("Author search error:", error)
        searchResults.value = []
    } finally {
        isLoading.value = false
    }
}

// debounce function to limit API calls
const debounce = (fn: Function, ms: number) => {
    let timeoutId: ReturnType<typeof setTimeout>
    return (...args: any[]) => {
        clearTimeout(timeoutId)
        timeoutId = setTimeout(() => fn(...args), ms)
    }
}

// debounced search
const debouncedSearch = debounce(searchAuthors, 300)

// handle input changes
const handleInput = (event: Event) => {
    const query = (event.target as HTMLInputElement).value
    debouncedSearch(query)
}
</script>

<template>
    <Combobox v-model="selectedAuthor" by="label">
        <ComboboxAnchor class="w-full border-1 rounded-lg focus-within:ring-2 focus-within:ring-[var(--ring)]">
            <div class="relative w-full max-w-sm items-center">
                <ComboboxInput
                    class="pl-10"
                :display-value="(val) => val?.label ?? ''"
                placeholder="Select author..."
                @input="handleInput"
                :disabled="isLoading"
                />
                <span class="absolute left-0 inset-y-0 flex items-center justify-center px-3">
          <Search class="size-4 text-muted-foreground" />
        </span>
                <!-- Optional loading indicator -->
                <span v-if="isLoading" class="absolute right-0 inset-y-0 flex items-center justify-center px-3">
          <svg class="animate-spin h-4 w-4 text-muted-foreground" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8h8a8 8 0 11-16 0z"></path>
          </svg>
        </span>
            </div>
        </ComboboxAnchor>

        <ComboboxList>
            <ComboboxEmpty v-if="!isLoading">No author found.</ComboboxEmpty>
            <ComboboxGroup v-if="displayAuthors.length">
                <ComboboxItem
                    v-for="author in displayAuthors"
                    :key="author.value"
                    :value="author"
                >
                    {{ author.label }}
                    <ComboboxItemIndicator>
                        <Check :class="cn('ml-auto h-4 w-4')" />
                    </ComboboxItemIndicator>
                </ComboboxItem>
            </ComboboxGroup>
        </ComboboxList>
    </Combobox>
</template>
