<script setup lang="ts">
import { ref, watch, nextTick } from 'vue';
import { BookOpen } from 'lucide-vue-next';
import { Input } from '@/components/ui/input';

// Props
const props = defineProps<{
    selectedAuthor?: string;
    cutterNumber?: string | null;
}>();

// Emits
const emit = defineEmits<{
    'update:selectedAuthor': [authorName: string];
    'authorSelected': [author: any];
}>();

// Reactive state
const isLoading = ref(false);
const hasAutoSelected = ref(false);

// Auto-select author based on cutter number
const autoSelectFromCutter = async (cutterNumber: string) => {
    if (!cutterNumber || hasAutoSelected.value) return;

    isLoading.value = true;

    try {
        const url = new URL('/api/authors/search', window.location.origin);
        url.searchParams.append('cutter_number', cutterNumber);

        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (response.ok) {
            const data = await response.json();
            const authors = data.authors || data || [];

            // Auto-select if we have a cutter number match
            const matchingAuthor = authors.find(
                author => author.author_number?.toLowerCase() === cutterNumber.toLowerCase()
            );

            if (matchingAuthor) {
                await nextTick();
                emit('update:selectedAuthor', matchingAuthor.name);
                emit('authorSelected', matchingAuthor);
                hasAutoSelected.value = true;
            }
        } else {
            console.error('Author search failed:', response.statusText);
        }
    } catch (error) {
        console.error('Author search error:', error);
    } finally {
        isLoading.value = false;
    }
};

// Watch for cutterNumber changes
watch(
    () => props.cutterNumber,
    (newCutterNumber, oldCutterNumber) => {
        if (newCutterNumber && newCutterNumber !== oldCutterNumber) {
            hasAutoSelected.value = false;
            autoSelectFromCutter(newCutterNumber);
        } else if (!newCutterNumber) {
            hasAutoSelected.value = false;
            emit('update:selectedAuthor', '');
        }
    },
    { immediate: true }
);
</script>

<template>
    <div class="relative">
        <Input
            :value="selectedAuthor || ''"
            :disabled="true"
            :placeholder="isLoading ? 'Loading...' : 'Auto-selected from Cutter number'"
            class="bg-gray-50 text-gray-500 cursor-not-allowed"
            readonly
        />

        <!-- Loading indicator -->
        <div v-if="isLoading" class="absolute right-3 top-1/2 transform -translate-y-1/2">
            <div class="size-4 animate-spin rounded-full border-2 border-muted-foreground border-t-transparent"></div>
        </div>

        <!-- Icon when not loading and no author selected -->
        <div v-else-if="!selectedAuthor" class="absolute right-3 top-1/2 transform -translate-y-1/2">
            <BookOpen class="size-4 text-muted-foreground" />
        </div>
    </div>
</template>
