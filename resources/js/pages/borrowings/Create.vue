<script setup lang="ts">
import BookScannerDialog from '@/components/BookScannerDialog.vue';
import BookSearchComboBox from '@/components/BookSearchComboBox.vue';
import BorrowBookDialog from '@/components/BorrowBookDialog.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import UserSearchComboBox from '@/components/UserSearchComboBox.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { MapPinPlusInside } from 'lucide-vue-next';
import { ref } from 'vue';

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
        router.post(
            '/borrowings/borrow/inside',
            {
                accession_number: selectedBook.value.accession_number,
            },
            {
                onSuccess: () => {
                    handleBorrowSuccess();
                },
            },
        );
    }
};
</script>

<template>
    <Head title="Borrow/Return Books" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div :class="['flex gap-4', searchAcResult ? '' : 'justify-center']">
                <Card
                    :class="[
                        searchAcResult ? 'w-full flex-1 lg:w-2/3' : 'w-full lg:w-1/2',
                        'rounded-[var(--radius)] bg-[var(--card)] shadow-sm transition-all duration-300 hover:shadow-xl',
                    ]"
                >
                    <CardHeader class="p-6">
                        <div class="flex items-center gap-3">
                            <svg
                                class="h-6 w-6 text-[var(--primary)]"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"
                                ></path>
                            </svg>
                            <CardTitle class="text-2xl font-semibold text-[var(--card-foreground)]">Search Book</CardTitle>
                        </div>
                        <CardDescription class="mt-2 text-[var(--muted-foreground)]"
                            >Find the book to borrow by searching title, author, or accession number.</CardDescription
                        >
                    </CardHeader>
                    <CardContent class="p-6">
                        <div class="flex w-full flex-col gap-6">
                            <BookScannerDialog class="w-full" />
                            <BookSearchComboBox
                                v-model:selectedBook="selectedBook"
                                @book-selected="handleBookSelected"
                                class="w-full rounded-[var(--radius)] transition-all duration-200 focus-within:ring-2 focus-within:ring-[var(--ring)]"
                            />
                        </div>
                    </CardContent>
                </Card>

                <!-- Display selected book information -->
                <Card
                    v-if="selectedBook"
                    :class="[
                        'w-full flex-1 lg:w-2/3',
                        'rounded-[var(--radius)] bg-[var(--card)] p-0 gap-0 shadow-sm transition-all duration-300 hover:shadow-xl',
                    ]"
                >
                    <CardHeader class="p-6">
                        <div class="flex items-center gap-3">
                            <svg
                                class="h-6 w-6 text-[var(--primary)]"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"
                                ></path>
                            </svg>
                            <CardTitle class="text-2xl font-semibold text-[var(--card-foreground)]"> Selected Book </CardTitle>
                        </div>
                        <CardDescription class="mt-2 text-[var(--muted-foreground)]"> Book details for borrowing. </CardDescription>
                    </CardHeader>
                    <CardContent class="p-6 pt-0">
                        <div class="space-y-8">
                            <div class="flex flex-col gap-6 md:flex-row">
                                <!-- Cover Image -->
                                <div class="flex-shrink-0">
                                    <img
                                        v-if="selectedBook.cover_image"
                                        :src="selectedBook.cover_image"
                                        alt="Book cover"
                                        class="h-48 w-32 rounded-[var(--radius)] border border-[var(--border)] object-cover transition-transform duration-300 hover:scale-105"
                                        loading="lazy"
                                    />
                                    <div
                                        v-else
                                        class="flex h-48 w-32 items-center justify-center rounded-[var(--radius)] border border-[var(--border)] bg-[var(--muted)] text-sm text-[var(--muted-foreground)]"
                                    >
                                        No Cover Image
                                    </div>
                                </div>
                                <!-- Book Details -->
                                <div class="flex-1 space-y-3">
                                    <div><strong class="text-[var(--card-foreground)]">Title:</strong> {{ selectedBook.title }}</div>
                                    <div>
                                        <strong class="text-[var(--card-foreground)]">Author:</strong>
                                        {{ selectedBook.author || 'Unknown' }}
                                    </div>
                                    <div>
                                        <strong class="text-[var(--card-foreground)]">Accession Number:</strong>
                                        {{ selectedBook.accession_number }}
                                    </div>
                                    <div v-if="selectedBook.isbn">
                                        <strong class="text-[var(--card-foreground)]">ISBN:</strong> {{ selectedBook.isbn }}
                                    </div>
                                    <div v-if="selectedBook.status">
                                        <strong class="text-[var(--card-foreground)]">Status:</strong>
                                        <span
                                            :class="{
                                                'text-[var(--chart-1)]': selectedBook.status === 'available',
                                                'text-[var(--destructive)]': selectedBook.status === 'borrowed',
                                                'text-[var(--chart-3)]': selectedBook.status === 'reserved',
                                            }"
                                        >
                                            {{ selectedBook.status }}
                                        </span>
                                    </div>
                                    <Button
                                        class="flex-1 bg-[var(--secondary)] text-[var(--secondary-foreground)] transition-colors duration-200 hover:bg-[var(--secondary)]/90"
                                        @click="handleBorrowInside"
                                        variant="secondary"
                                        v-if="selectedBook.status == 'available'"
                                    >
                                        <MapPinPlusInside class="mr-2 h-4 w-4" />
                                        Borrow (Inside)
                                    </Button>
                                </div>
                            </div>
                            <!-- User Search -->
                            <div v-if="selectedBook.status === 'available'" class="space-y-4">
                                <UserSearchComboBox
                                    v-model:selectedUser="selectedUser"
                                    @user-selected="handleUserSelected"
                                    class="w-full rounded-[var(--radius)] transition-all duration-200"
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
