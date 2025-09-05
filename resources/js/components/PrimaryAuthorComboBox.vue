<script setup lang="ts">
import { ref, watch, computed, nextTick } from 'vue';
import { Check, Search, BookOpen, Plus } from 'lucide-vue-next';
import { cn } from '@/utils';
import {
    Combobox,
    ComboboxAnchor,
    ComboboxEmpty,
    ComboboxGroup,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxList,
} from '@/components/ui/combobox';
import { debounce } from 'lodash-es';

// Props
const props = defineProps<{
    selectedAuthor?: string; // Changed to string to match form field
    cutterNumber?: string | null;
}>();

// Emits
const emit = defineEmits<{
    'update:selectedAuthor': [authorName: string];
    'authorSelected': [author: any];
}>();

// Reactive state
const searchQuery = ref('');
const searchResults = ref<any[]>([]);
const isLoading = ref(false);
const selectedAuthorObject = ref<any>(null);
const hasAutoSelected = ref(false);

// Debounced search function
const debouncedSearch = debounce(async (query: string) => {
    if (!query || query.length < 2) {
        searchResults.value = [];
        isLoading.value = false;
        return;
    }

    isLoading.value = true;

    try {
        const url = new URL('/api/authors/search', window.location.origin);
        url.searchParams.append('q', query);
        if (props.cutterNumber) {
            url.searchParams.append('cutter_number', props.cutterNumber);
        }

        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (response.ok) {
            const data = await response.json();
            searchResults.value = data.authors || data || [];

            // Auto-select if we have a cutter number match and haven't auto-selected yet
            if (props.cutterNumber && !hasAutoSelected.value) {
                const matchingAuthor = searchResults.value.find(
                    author => author.author_number?.toLowerCase() === props.cutterNumber?.toLowerCase()
                );
                if (matchingAuthor) {
                    await nextTick(); // Wait for DOM update
                    handleAuthorSelect(matchingAuthor);
                    hasAutoSelected.value = true;
                }
            }
        } else {
            console.error('Author search failed:', response.statusText);
            searchResults.value = [];
        }
    } catch (error) {
        console.error('Author search error:', error);
        searchResults.value = [];
    } finally {
        isLoading.value = false;
    }
}, 300);

// Watch for search query changes
watch(searchQuery, (newQuery) => {
    if (newQuery && newQuery !== selectedAuthorObject.value?.name) {
        debouncedSearch(newQuery);
    }
});

// Watch for cutterNumber changes - this triggers the auto-selection
watch(
    () => props.cutterNumber,
    (newCutterNumber, oldCutterNumber) => {
        if (newCutterNumber && newCutterNumber !== oldCutterNumber) {
            hasAutoSelected.value = false; // Reset auto-selection flag
            searchQuery.value = newCutterNumber;
            debouncedSearch(newCutterNumber);
        } else if (!newCutterNumber) {
            // Clear selection when cutter number is removed
            searchQuery.value = '';
            selectedAuthorObject.value = null;
            searchResults.value = [];
            hasAutoSelected.value = false;
            emit('update:selectedAuthor', '');
        }
    },
    { immediate: true }
);

// Watch for external selectedAuthor changes
watch(
    () => props.selectedAuthor,
    (newSelectedAuthor) => {
        if (newSelectedAuthor && newSelectedAuthor !== selectedAuthorObject.value?.name) {
            // External change - update our internal state
            searchQuery.value = newSelectedAuthor;
        } else if (!newSelectedAuthor && selectedAuthorObject.value) {
            // Clear our internal state
            selectedAuthorObject.value = null;
            searchQuery.value = '';
        }
    }
);

// Computed property to include "Create New Author" option
const displayResults = computed(() => {
    const results = [...searchResults.value];
    if (
        searchQuery.value.length >= 2 &&
        !searchResults.value.some(author => author.name.toLowerCase() === searchQuery.value.toLowerCase())
    ) {
        results.push({
            name: searchQuery.value,
            author_number: props.cutterNumber || null,
            isNew: true,
        });
    }
    return results;
});

// Handle author selection
const handleAuthorSelect = (author: any) => {
    selectedAuthorObject.value = author;
    searchQuery.value = author.name;

    // Emit both events for backward compatibility
    emit('update:selectedAuthor', author.name);
    emit('authorSelected', author);
};

// Display function for selected author
const displayValue = (author: any) => {
    if (!author) return searchQuery.value;
    return author.name;
};
</script>

<template>
    <Combobox
        v-model="selectedAuthorObject"
        by="author_number"
        @update:model-value="handleAuthorSelect"
    >
        <ComboboxAnchor class="border w-full">
            <div class="relative w-full max-w-sm items-center">
                <ComboboxInput
                    v-model="searchQuery"
                    class="pl-2"
                    :display-value="displayValue"
                    placeholder="Search or type a new author name..."
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
                        {{ searchQuery.length < 2 ? 'Type at least 2 characters to search or create' : 'No authors found' }}
                    </p>
                </div>
            </ComboboxEmpty>

            <ComboboxGroup v-if="displayResults.length > 0">
                <ComboboxItem
                    v-for="author in displayResults"
                    :key="author.author_number || `new-${author.name}`"
                    :value="author"
                    class="flex items-center justify-between py-2"
                >
                    <span class="flex items-center">
                        <Plus v-if="author.isNew" class="size-4 mr-2 text-primary" />
                        {{ author.name }}
                        <span v-if="author.author_number && !author.isNew" class="text-muted-foreground ml-1">({{ author.author_number }})</span>
                        <span v-if="author.isNew" class="text-primary ml-2">(Create New)</span>
                    </span>
                    <ComboboxItemIndicator>
                        <Check :class="cn('ml-auto h-4 w-4')" />
                    </ComboboxItemIndicator>
                </ComboboxItem>
            </ComboboxGroup>
        </ComboboxList>
    </Combobox>
</template>
