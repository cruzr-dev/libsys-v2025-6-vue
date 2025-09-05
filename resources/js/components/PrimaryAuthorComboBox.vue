<script setup lang="ts">
import { ref, watch, computed } from 'vue';
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
    selectedAuthor?: any;
    cutterNumber?: string | null; // New prop for Cutter number
}>();

// Emits
const emit = defineEmits<{
    'update:selectedAuthor': [author: any];
    'authorSelected': [author: any];
}>();

// Reactive state
const searchQuery = ref('');
const searchResults = ref<any[]>([]);
const isLoading = ref(false);
const selectedAuthor = ref(props.selectedAuthor || null);

// Debounced search function
const debouncedSearch = debounce(async (query: string) => {
    if (!query || query.length < 2) {
        searchResults.value = [];
        isLoading.value = false;
        return;
    }

    isLoading.value = true;

    try {
        // Make request to your Laravel backend, including cutter_number if provided
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
            // If cutterNumber matches an author, auto-select it
            if (props.cutterNumber) {
                const matchingAuthor = searchResults.value.find(
                    author => author.author_number?.toLowerCase() === props.cutterNumber?.toLowerCase()
                );
                if (matchingAuthor && !selectedAuthor.value) {
                    selectedAuthor.value = matchingAuthor;
                    emit('update:selectedAuthor', matchingAuthor);
                    emit('authorSelected', matchingAuthor);
                    searchQuery.value = matchingAuthor.name;
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
    debouncedSearch(newQuery);
});

// Watch for cutterNumber changes
watch(
    () => props.cutterNumber,
    (newCutterNumber) => {
        if (newCutterNumber) {
            searchQuery.value = newCutterNumber; // Pre-populate search with Cutter number
            debouncedSearch(newCutterNumber);
        }
    },
    { immediate: true }
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
            author_number: props.cutterNumber || null, // Use cutterNumber if available
            isNew: true, // Flag to indicate this is a new author
        });
    }
    return results;
});

// Handle author selection
const handleAuthorSelect = (author: any) => {
    selectedAuthor.value = author;
    emit('update:selectedAuthor', author);
    emit('authorSelected', author);
    // Update searchQuery to reflect the selected author's name
    searchQuery.value = author.name;
};

// Display function for selected author
const displayValue = (author: any) => {
    if (!author) return '';
    return `${author.name}`;
};
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
                        <span v-if="author.author_number && !author.isNew">({{ author.author_number }})</span>
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
