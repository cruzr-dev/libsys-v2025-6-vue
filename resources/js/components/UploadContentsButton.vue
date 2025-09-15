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
    if (!rawText || typeof rawText !== 'string') {
        return '';
    }

    // Split text into lines for line-by-line processing
    const lines = rawText.split(/\r?\n/);

    const cleanedLines = lines.map(line => {
        // Remove dotted leaders and trailing page numbers
        // This regex matches patterns like "Chapter 1 .................. 5" or "Introduction ........ 1"
        let cleaned = line.replace(/\.{2,}\s*\d+\s*$/, '');

        // Also handle cases with spaces and dots mixed: "Chapter 1 . . . . . . 5"
        cleaned = cleaned.replace(/[\s\.]{3,}\d+\s*$/, '');

        // Remove trailing spaces but preserve the line structure
        cleaned = cleaned.replace(/\s+$/, '');

        return cleaned;
    });

    // Join lines back together, preserving line breaks
    // Filter out completely empty lines that might have been created during cleaning
    const result = cleanedLines
        .filter(line => line.trim().length > 0 || cleanedLines.indexOf(line) === 0)
        .join('\n');

    return result.trim();
};
</script>

