<template>
    <div class="flex justify-end">
        <Button
            type="button"
            variant="outline"
            size="sm"
            @click="triggerFileInput"
            :disabled="isProcessing"
            class="flex items-center gap-2"
        >
            <Upload class="h-4 w-4" />
            <span v-if="!isProcessing">Upload Contents</span>
            <span v-else class="flex items-center gap-2">
                <LoaderCircle class="h-4 w-4 animate-spin" />
                Processing...
            </span>
        </Button>

        <!-- Hidden file input -->
        <input
            ref="fileInput"
            type="file"
            accept=".docx"
            @change="handleFileSelection"
            class="hidden"
        />
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Upload, LoaderCircle } from 'lucide-vue-next';
import mammoth from 'mammoth';

// Define emits
const emit = defineEmits<{
    'contents-uploaded': [contents: string];
    'upload-error': [error: string];
}>();

// Reactive state
const fileInput = ref<HTMLInputElement | null>(null);
const isProcessing = ref(false);

// Trigger the hidden file input
const triggerFileInput = () => {
    if (fileInput.value && !isProcessing.value) {
        fileInput.value.click();
    }
};

// Handle file selection and processing
const handleFileSelection = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (!file) return;

    // Validate file type
    if (!file.name.toLowerCase().endsWith('.docx')) {
        emit('upload-error', 'Please select a valid .docx file.');
        return;
    }

    // Validate file size (optional - limit to 10MB)
    const maxSize = 10 * 1024 * 1024; // 10MB
    if (file.size > maxSize) {
        emit('upload-error', 'File size must be less than 10MB.');
        return;
    }

    isProcessing.value = true;

    try {
        // Convert file to ArrayBuffer for mammoth
        const arrayBuffer = await file.arrayBuffer();

        // Extract raw text using mammoth
        const result = await mammoth.extractRawText({ arrayBuffer });

        if (result.messages && result.messages.length > 0) {
            console.warn('Mammoth parsing warnings:', result.messages);
        }

        // Clean the extracted text
        const cleanedText = cleanTableOfContents(result.value);

        // Emit the cleaned text to parent
        emit('contents-uploaded', cleanedText);

    } catch (error) {
        console.error('Error processing .docx file:', error);
        emit('upload-error', 'Failed to process the document. Please ensure it\'s a valid .docx file.');
    } finally {
        isProcessing.value = false;

        // Clear the input so the same file can be selected again
        if (target) {
            target.value = '';
        }
    }
};

// Clean the table of contents text
const cleanTableOfContents = (rawText: string): string => {
    if (!rawText) return '';

    // Normalize line endings & strip BOM
    let text = rawText.replace(/\r\n?/g, '\n').replace(/^\uFEFF/, '');

    const originalLines = text.split('\n');
    const cleaned: string[] = [];
    let prev = '';

    for (let line of originalLines) {
        // Normalize tabs -> spaces, trim right only
        line = line.replace(/\t+/g, ' ').replace(/\s+$/g, '');

        const trimmed = line.trim();

        // Collapse multiple blank lines into one
        if (!trimmed) {
            if (cleaned.length && cleaned[cleaned.length - 1] !== '') {
                cleaned.push('');
            }
            prev = '';
            continue;
        }

        // Skip repeated TOC heading (keep first only)
        if (/^table of contents$/i.test(trimmed) &&
            cleaned.some(l => /^table of contents$/i.test(l))) {
            continue;
        }

        // Skip common running headers/footers
        if (/^copyright\s+/i.test(trimmed)) continue;

        // Remove standalone page numbers like "12" or "Page 5"
        if (/^(page\s+)?\d+$/i.test(trimmed)) continue;

        // Merge hyphenated line breaks: word- \n continuation
        if (cleaned.length && /-$/.test(prev) && /^[a-z]/.test(trimmed)) {
            const last = cleaned.pop()!;
            line = last.slice(0, -1) + trimmed;
        }

        // Remove dotted leaders + trailing page number (arabic or roman)
        // e.g., "Chapter 1 ........ 5" or "Preface .... xii"
        line = line.replace(
            /(?:\.{2,}|[\s\.]{3,})\s*\b(\d+|[ivxlcdm]{1,7})\b\s*$/i,
            ''
        ).replace(/\s+$/, '');

        // Avoid consecutive duplicates
        if (line === prev) continue;

        cleaned.push(line);
        prev = line;
    }

    // Trim leading/trailing blank lines
    while (cleaned[0] === '') cleaned.shift();
    while (cleaned[cleaned.length - 1] === '') cleaned.pop();

    return cleaned.join('\n');
};
</script>

