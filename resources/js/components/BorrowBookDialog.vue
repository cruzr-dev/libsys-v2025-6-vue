<script setup lang="ts">
import { Button } from "@/components/ui/button"
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog"
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    user: Object,
    book_accession: String,
});

// Define emits
const emit = defineEmits(['borrow-success']);

const isLoading = ref(false);
const isOpen = ref(false);

// Watch for user selection and auto-open dialog
watch(() => props.user, (newUser) => {
    if (newUser && props.book_accession) {
        isOpen.value = true;
    }
}, { immediate: true });

const borrowBook = (user_id, book_accession) => {
    if (isLoading.value) return;

    isLoading.value = true;

    const data = {
        user_id: user_id,
        book_accession: book_accession
    };

    router.post(route('borrowings.borrow'), data, {
        onSuccess: (page) => {
            // Handle success - maybe show a toast notification
            console.log('Book borrowed successfully');
            isLoading.value = false;
            isOpen.value = false; // Close dialog on success

            // Emit success event to parent
            emit('borrow-success');
        },
        onError: (errors) => {
            // Handle validation errors
            console.error('Borrowing failed:', errors);
            isLoading.value = false;
        },
        onFinish: () => {
            isLoading.value = false;
        }
    });
};
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>Confirm Book Borrowing</DialogTitle>
            </DialogHeader>
            <div class="space-y-4">
                <div>
                    <h3 class="text-lg font-semibold">Borrower Details</h3>
                    <p class="text-sm text-gray-600">{{ user?.first_name }}</p>
                    <p class="text-sm text-gray-600">{{ user?.library_id }}</p>
                </div>

                <div>
                    <h3 class="text-lg font-semibold">Book Details</h3>
                    <p class="text-sm text-gray-600">
                        <strong>Accession Number:</strong> {{ book_accession }}
                    </p>
                </div>

                <div class="flex gap-2">
                    <Button
                        variant="outline"
                        @click="isOpen = false"
                        :disabled="isLoading"
                        class="flex-1"
                    >
                        Cancel
                    </Button>
                    <Button
                        @click="borrowBook(user?.value || user?.id, book_accession)"
                        v-if="user && book_accession"
                        :disabled="isLoading"
                        class="flex-1"
                    >
                        {{ isLoading ? 'Processing...' : 'Confirm Borrow' }}
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
