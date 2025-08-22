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
        <DialogContent class="sm:max-w-[500px]">
            <DialogHeader>
                <DialogTitle>Confirm Book Borrowing</DialogTitle>
            </DialogHeader>

            <div class="space-y-6 py-4">
                <!-- Borrower Section with Profile Image -->
                <div class="border rounded-lg p-4">
                    <h3 class="text-lg font-semibold mb-3">Borrower Details</h3>
                    <div class="flex items-center gap-4">
                        <!-- Profile Image -->
                        <div class="flex-shrink-0">
                            <img
                                v-if="user?.profile_image"
                                :src="user.profile_image"
                                :alt="`${user?.first_name}'s profile`"
                                class="w-16 h-16 rounded-full object-cover border"
                            />
                            <!-- Fallback Avatar -->
                            <div
                                v-else
                                class="w-16 h-16 rounded-full border flex items-center justify-center"
                            >
                            <span class="font-semibold text-lg">
                                {{ user?.first_name?.charAt(0)?.toUpperCase() }}
                            </span>
                            </div>
                        </div>

                        <!-- User Info -->
                        <div class="flex-1">
                            <p class="font-medium text-base">{{ user?.first_name }}</p>
                            <p class="text-sm text-gray-600">{{ user?.library_id }}</p>
                        </div>
                    </div>
                </div>

                <!-- Book Details Section -->
                <div class="border rounded-lg p-4">
                    <h3 class="text-lg font-semibold mb-3">Book Details</h3>
                    <p class="text-sm">
                        <strong>Accession Number:</strong> {{ book_accession }}
                    </p>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3">
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
