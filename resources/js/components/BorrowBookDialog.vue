<script setup lang="ts">
import { Button } from "@/components/ui/button"
import {
    Dialog,
    DialogContent,
    DialogTrigger,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog"
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    user: Object,
    book_accession: String,
});

const isLoading = ref(false);

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
    <Dialog>
        <DialogTrigger as-child>
            <Button class="w-full">
                Borrow Book
            </Button>
        </DialogTrigger>
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>Confirm Book Borrowing</DialogTitle>
            </DialogHeader>
            <div class="space-y-4">
                <div>
                    <h3 class="text-lg font-semibold">Borrower Details</h3>
                    <p class="text-sm text-gray-600">{{ user.first_name }}</p>
                    <p class="text-sm text-gray-600">{{ user.email }}</p>
                </div>

                <div>
                    <h3 class="text-lg font-semibold">Book Details</h3>
                    <p class="text-sm text-gray-600">
                        <strong>Accession Number:</strong> {{ book_accession }}
                    </p>
                </div>

                <Button
                    @click="borrowBook(user.value || user.id, book_accession)"
                    v-if="user && book_accession"
                    :disabled="isLoading"
                    class="w-full"
                >
                    {{ isLoading ? 'Processing...' : 'Confirm Borrow' }}
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
