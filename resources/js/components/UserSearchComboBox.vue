<script setup lang="ts">
import { ref, watch } from 'vue'
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
    ComboboxList
} from "@/components/ui/combobox"
import { debounce } from 'lodash-es'

// Props
const props = defineProps<{
    selectedUser?: any
}>()

// Emits
const emit = defineEmits<{
    'update:selectedUser': [user: any]
    'userSelected': [user: any]
}>()

// Reactive state
const searchQuery = ref('')
const searchResults = ref<any[]>([])
const isLoading = ref(false)
const selectedUser = ref(props.selectedUser || null)

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
        const response = await fetch(`/api/borrowings/users/search?q=${encodeURIComponent(query)}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        })

        if (response.ok) {
            const data = await response.json()
            searchResults.value = data.users || data || []
        } else {
            console.error('User search failed:', response.statusText)
            searchResults.value = []
        }
    } catch (error) {
        console.error('User search error:', error)
        searchResults.value = []
    } finally {
        isLoading.value = false
    }
}, 300)

// Watch for search query changes
watch(searchQuery, (newQuery) => {
    debouncedSearch(newQuery)
})

// Handle user selection
const handleUserSelect = (user: any) => {
    selectedUser.value = user
    emit('update:selectedUser', user)
    emit('userSelected', user)
}

// Display function for selected user
const displayValue = (user: any) => {
    if (!user) return ''
    const fullName = [user.first_name, user.middle_initial, user.last_name]
        .filter(Boolean)
        .join(' ')
    return `${fullName} (${user.library_id})`
}
</script>

<template>
    <div class="space-y-2">
        <label class="text-lg font-medium text-[var(--card-foreground)]">
            Select Borrower to Borrow (Take Home)
        </label>
        <Combobox
            v-model="selectedUser"
            by="id"
            @update:model-value="handleUserSelect"
        >
            <ComboboxAnchor class="w-full border-1 rounded-lg focus-within:ring-2 focus-within:ring-[var(--ring)]">
                <div class="relative w-full items-center">
                    <ComboboxInput
                        v-model="searchQuery"
                        class="pl-2"
                        :display-value="displayValue"
                        placeholder="Search by name or library ID..."
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
                        <User class="size-8 text-muted-foreground mb-2" />
                        <p class="text-sm text-muted-foreground">
                            {{ searchQuery.length < 2 ? 'Type at least 2 characters to search' : 'No users found' }}
                        </p>
                    </div>
                </ComboboxEmpty>

                <ComboboxGroup v-if="searchResults.length > 0">
                    <ComboboxItem
                        v-for="user in searchResults"
                        :key="user.id"
                        :value="user"
                        class="flex flex-col items-start py-3"
                    >
                        <div class="flex w-full items-center justify-between">
                            <div class="flex flex-col">
                                <span class="font-medium">
                                    {{ user.first_name }} {{ user.middle_initial ? user.middle_initial + '.' : '' }} {{ user.last_name }}
                                </span>
                                <div class="flex flex-col text-sm text-muted-foreground">
                                    <span v-if="user.library_id">
                                        Library ID: {{ user.library_id }}
                                    </span>
                                </div>
                            </div>

                            <ComboboxItemIndicator>
                                <Check class="ml-2 h-4 w-4" />
                            </ComboboxItemIndicator>
                        </div>
                    </ComboboxItem>
                </ComboboxGroup>
            </ComboboxList>
        </Combobox>
    </div>
</template>
