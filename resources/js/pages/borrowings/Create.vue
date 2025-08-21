<script setup lang="ts">
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import BookScannerDialog from '@/components/BookScannerDialog.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import BookSearchComboBox from '@/components/BookSearchComboBox.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Borrowings',
        href: '/borrowings',
    },
    {
        title: 'Borrow',
        href: '/borrowings/create',
    },
];

// Get the props
const props = defineProps({
    searchAcResult: {
        type: [Object, null],
        default: null,
    },
});

// Reactive state for selected book
const selectedBook = ref(props.searchAcResult);

// Handle book selection
const handleBookSelected = (book: any) => {
    selectedBook.value = book;
    console.log('Selected book:', book);

    // You can perform additional actions here when a book is selected
    // such as updating a form, fetching borrower info, etc.
};

// Log the search result for debugging
console.log('Search result from props:', props.searchAcResult);
</script>

<template>
    <Head title="Borrow/Return Books" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="flex gap-4">
                <Card class="w-lg flex-1">
                    <CardHeader>
                        <CardTitle>Search Book</CardTitle>
                        <CardDescription>Find the book to borrow by searching title, author, or accession number.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex flex-col gap-4 w-full">
                            <BookScannerDialog />
                            <BookSearchComboBox
                                v-model:selectedBook="selectedBook"
                                @book-selected="handleBookSelected"
                            />
                        </div>
                    </CardContent>
                </Card>

                <!-- Display selected book information -->
                <Card v-if="selectedBook" class="w-lg flex-1">
                    <CardHeader>
                        <CardTitle>Selected Book</CardTitle>
                        <CardDescription>Book details for borrowing.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <div>
                                <strong>Title:</strong> {{ selectedBook.title }}
                            </div>
                            <div>
                                <strong>Author:</strong> {{ selectedBook.author || 'Unknown' }}
                            </div>
                            <div>
                                <strong>Accession Number:</strong> {{ selectedBook.accession_number }}
                            </div>
                            <div v-if="selectedBook.isbn">
                                <strong>ISBN:</strong> {{ selectedBook.isbn }}
                            </div>
                            <div v-if="selectedBook.status">
                                <strong>Status:</strong>
                                <span
                                    :class="{
                                        'text-green-600': selectedBook.status === 'available',
                                        'text-red-600': selectedBook.status === 'borrowed',
                                        'text-yellow-600': selectedBook.status === 'reserved'
                                    }"
                                >
                                    {{ selectedBook.status }}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
