<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import BookScannerDialog from '@/components/BookScannerDialog.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import BookSearchComboBox from '@/components/BookSearchComboBox.vue';
import UserSearchComboBox from '@/components/UserSearchComboBox.vue';
import BorrowBookDialog from '@/components/BorrowBookDialog.vue';
import { Button } from '@/components/ui/button';
import { MapPinPlusInside } from "lucide-vue-next"

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

// Reactive state for selected book and user
const selectedBook = ref(props.searchAcResult);
const selectedUser = ref(null);

// Handle book selection
const handleBookSelected = (book: any) => {
    selectedBook.value = book;
    console.log('Selected book:', book);

    // Reset selected user when book changes
    selectedUser.value = null;
};

// Handle user selection
const handleUserSelected = (user: any) => {
    selectedUser.value = user;
};

// Handle borrow success - clear both book and user
const handleBorrowSuccess = () => {
    selectedBook.value = null;
    selectedUser.value = null;
};

// Handle Borrow (Inside) button click
const handleBorrowInside = () => {
    if (selectedBook.value) {
        router.post('/borrowings/borrow/inside', {
            accession_number: selectedBook.value.accession_number
        }, {
            onSuccess: () => {
                handleBorrowSuccess()
            }
        });
    }
};

// Log the search result for debugging
console.log('Search result from props:', props.searchAcResult);
</script>

<template>
    <Head title="Borrow/Return Books" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div :class="[
                'flex gap-4',
                searchAcResult ? '' : 'justify-center'
            ]">
                <Card :class="[
                    searchAcResult ? 'w-lg flex-1' : 'w-lg'
                ]">
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
                        <div class="space-y-4">
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
                                <Button
                                    class="flex-1"
                                    @click="handleBorrowInside"
                                    variant="secondary"
                                    v-if="selectedBook.status == 'available'"
                                >
                                    <MapPinPlusInside class="w-4 h-4 mr-2" />
                                    Borrow (Inside)
                                </Button>
                            </div>

                            <!-- User Search appears when book is available -->
                            <div v-if="selectedBook.status === 'available'">
                                <UserSearchComboBox
                                    v-model:selectedUser="selectedUser"
                                    @user-selected="handleUserSelected"
                                />
                                <div v-if="selectedUser" class="mt-4">
                                    <BorrowBookDialog
                                        :user="selectedUser"
                                        :book_accession="selectedBook.accession_number"
                                        @borrow-success="handleBorrowSuccess"
                                    />
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
