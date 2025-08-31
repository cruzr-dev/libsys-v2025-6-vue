<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogTrigger } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';

const getCoverUrl = (path: string) => {
    return path ? `/storage/uploads/book-covers/${path}` : `/storage/placeholder_images/sample1.png`;
};

// Dummy record data for now
const record = {
    title: "Introduction to Algorithms",
    accession_number: "A123456",
    status: "Available",
    copy_count: 3,
    subject_headings: "Algorithms, Data Structures, Computer Science",
    book: {
        authors: "Thomas H. Cormen, Charles E. Leiserson, Ronald L. Rivest, Clifford Stein",
        editors: "John Doe",
        isbn: "9780262033848",
        publication_year: "2009",
        call_number: "QA76.6 .I585",
        publisher: "MIT Press",
        status: "Available",
        volume: "3rd Edition",
        publication_place: "Cambridge, MA",
        ddc_class_id: "005.1",
        physical_location_id: "Shelf 3A",
        cover_image: null, // keep null to test placeholder
        table_of_contents: `1. Foundations
2. Sorting and Order Statistics
3. Data Structures
4. Advanced Design and Analysis Techniques
5. Advanced Data Structures
6. Graph Algorithms
7. Selected Topics`
    }
};
</script>

<template>
    <Dialog class="">
        <DialogTrigger as-child>
            <Card class="overflow-hidden p-0 hover:shadow-md transition">
                <div class="flex">
                    <!-- Main content area -->
                    <div class="flex-1 p-6">
                        <CardHeader class="p-0 pb-4">
                            <CardTitle class="line-clamp-2 text-xl font-semibold">
                                {{ record?.title }}
                            </CardTitle>
                            <div class="mt-2 text-sm text-muted-foreground">
                                {{ record?.book.authors }}
                            </div>
                        </CardHeader>

                        <CardContent class="p-0">
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div class="space-y-1">
                                    <span class="text-muted-foreground">Accession Number</span>
                                    <div class="font-medium">{{ record?.accession_number }}</div>
                                </div>
                                <div class="space-y-1">
                                    <span class="text-muted-foreground">Year</span>
                                    <div class="font-medium">{{ record?.book.publication_year }}</div>
                                </div>
                            </div>

                            <div class="mt-4 border-t pt-4">
                                <div class="grid grid-cols-2 items-center gap-4 text-sm">
                                    <div class="flex justify-start">
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="{
                                                'bg-green-100 text-green-800': record?.status === 'Available',
                                                'bg-red-100 text-red-800': record?.status === 'Checked Out',
                                                'bg-yellow-100 text-yellow-800': record?.status === 'Reserved',
                                                'bg-gray-100 text-gray-800':
                                                    !record?.status || !['Available', 'Checked Out', 'Reserved'].includes(record?.status),
                                            }"
                                        >
                                            {{ record?.status }}
                                        </span>
                                    </div>
                                    <div v-if="record?.copy_count > 1" class="flex gap-2 items-center">
                                        <Badge v-if="record?.copy_count > 1">{{ record?.copy_count }} copies</Badge>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </div>

                    <!-- Book cover area -->
                    <div class="flex w-32 h-40 items-center justify-center bg-gray-200 dark:bg-muted rounded-lg shadow-sm">
                        <svg class="w-12 h-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a4 4 0 014-4h10a4 4 0 014 4v10a4 4 0 01-4 4H7a4 4 0 01-4-4V7z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11h.01M12 16l-3-3 2-2 4 4 3-3" />
                        </svg>
                    </div>

                </div>
            </Card>
        </DialogTrigger>
        <DialogContent class="grid gap-6 h-full max-h-9/10 sm:grid-cols-2 sm:max-w-6xl justify-between">
            <div class="flex items-center justify-center">
                <img
                    class="max-w-xs rounded-lg shadow-md "
                    loading="lazy"
                    :src="getCoverUrl(record?.book.cover_image)"
                    alt="Book Cover"
                />
            </div>

            <div class="space-y-6 overflow-y-auto">
                <div>
                    <h2 class="text-2xl font-bold">{{ record?.title }}</h2>
                    <div class="flex my-4 gap-2">
                        <p class="text-muted-foreground">{{ record?.accession_number }}</p>
                        <Badge v-if="record?.copy_count > 1">{{ record?.copy_count }} copies</Badge>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-1">
                        <span class="font-semibold">Authors:</span>
                        <p>{{ record?.book.authors }}</p>
                    </div>
                    <div class="space-y-1">
                        <span class="font-semibold">Editors:</span>
                        <p>{{ record?.book.editors }}</p>
                    </div>
                    <div class="space-y-1">
                        <span class="font-semibold">ISBN:</span>
                        <p>{{ record?.book.isbn }}</p>
                    </div>
                    <div class="space-y-1">
                        <span class="font-semibold">Publication Year:</span>
                        <p>{{ record?.book.publication_year }}</p>
                    </div>
                    <div class="space-y-1">
                        <span class="font-semibold">Call Number:</span>
                        <p>{{ record?.book.call_number }}</p>
                    </div>
                    <div class="space-y-1">
                        <span class="font-semibold">Publisher:</span>
                        <p>{{ record?.book.publisher }}</p>
                    </div>
                    <div class="space-y-1">
                        <span class="font-semibold">Status:</span>
                        <p>{{ record?.book.status }}</p>
                    </div>
                    <div class="space-y-1">
                        <span class="font-semibold">Volume:</span>
                        <p>{{ record?.book.volume }}</p>
                    </div>
                    <div class="space-y-1">
                        <span class="font-semibold">Publication Place:</span>
                        <p>{{ record?.book.publication_place }}</p>
                    </div>
                    <div class="space-y-1">
                        <span class="font-semibold">DDC Class ID:</span>
                        <p>{{ record?.book.ddc_class_id }}</p>
                    </div>
                    <div class="space-y-1">
                        <span class="font-semibold">Physical Location ID:</span>
                        <p>{{ record?.book.physical_location_id }}</p>
                    </div>
                </div>

                <div class="space-y-1">
                    <span class="font-semibold">Subject Headings:</span>
                    <p>{{ record?.subject_headings }}</p>
                </div>

                <div class="space-y-1">
                    <span class="font-semibold">Table of Contents:</span>
                    <p class="whitespace-pre-wrap">{{ record?.book.table_of_contents }}</p>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
