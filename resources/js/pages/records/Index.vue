<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import Layout from '@/layouts/records/Layout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ChevronLeftIcon, ChevronRightIcon, DoubleArrowLeftIcon, DoubleArrowRightIcon } from '@radix-icons/vue';
import { ArrowUpDown, Search, X, Loader2, Eye } from 'lucide-vue-next';
import { h, ref, onMounted, watch, nextTick } from 'vue';
import {
    FlexRender,
    getCoreRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    getFilteredRowModel,
    useVueTable,
} from '@tanstack/vue-table';

// Utility function for debouncing
function debounce(func: Function, wait: number) {
    let timeout: ReturnType<typeof setTimeout>;
    return function executedFunction(...args: any[]) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// API Response type
interface ApiResponse {
    data: any[];
    current_page: number;
    per_page: number;
    last_page: number;
    total: number;
}

// reactive state
const data = ref<any[]>([]);
const isLoading = ref(false);
const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);
const error = ref<string | null>(null);

const sorting = ref([]);
const columnFilters = ref([]);

// --- Modal dialog state ---
const isDialogOpen = ref(false);
const selectedBook = ref<any | null>(null);

// --- Search functionality state ---
const filterInput = ref<string>('');
const searchInputRef = ref(null);

// --- Pagination state ---
const pageSizes = [5, 10, 20, 30, 40, 50];
const pagination = ref({
    pageIndex: 0,
    pageSize: 10,
});

// Scroll position tracking
let scrollPosition = 0;

const saveScrollPosition = () => {
    scrollPosition = window.scrollY;
};

const restoreScrollPosition = () => {
    nextTick(() => {
        window.scrollTo(0, scrollPosition);
    });
};

// Modal handlers
const handleShow = (book: any) => {
    selectedBook.value = book;
    isDialogOpen.value = true;
};

const handleEdit = (id: string | number) => {
    router.get(route('records.books.edit', id));
};

console.log(data)

// Columns with authors column added
const columns = [
    {
        accessorKey: 'accession_number',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => [
                'Acc. No.', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })
            ]),
        cell: ({ row }) => h('div', row.getValue('accession_number')),
    },
    {
        accessorKey: 'title',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => [
                'Title', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })
            ]),
        cell: ({ row }) => h('div', { class: 'truncate max-w-sm' }, row.getValue('title')),
    },
    {
        accessorKey: 'authors_list',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => [
                'Authors', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })
            ]),
        cell: ({ row }) => {
            const authorsList = row.getValue('authors_list');
            return h('div', { class: 'truncate max-w-xs' }, authorsList || 'No authors');
        },
    },
    {
        id: 'action',
        header: 'Action',
        enableHiding: false,
        cell: ({ row }) =>
            h(Button,
                {
                    variant: 'outline',
                    size: 'sm',
                    onClick: () => handleShow(row.original),
                    class: 'flex items-center gap-2'
                },
                () => [
                    h(Eye, { class: 'h-4 w-4 text-muted-foreground' }),
                    'Show'
                ]
            )
    }
];

// sort helper
function cycleSort(column) {
    const currentSort = column.getIsSorted();
    if (currentSort === false) column.toggleSorting(false);
    else if (currentSort === 'asc') column.toggleSorting(true);
    else column.clearSorting();
}

// Apply search filter
const applyFilter = () => {
    const newFilters = columnFilters.value.filter((f) => f.id !== 'search');
    if (filterInput.value.trim()) {
        newFilters.push({ id: 'search', value: filterInput.value.trim() });
    }
    columnFilters.value = newFilters;
    // Reset to first page when searching
    currentPage.value = 1;
    pagination.value.pageIndex = 0;
    fetchData();
};

// Clear search filter
const clearFilter = () => {
    filterInput.value = '';
    const newFilters = columnFilters.value.filter((f) => f.id !== 'search');
    columnFilters.value = newFilters;
    currentPage.value = 1;
    pagination.value.pageIndex = 0;
    fetchData();
};

// Debounced search
const debouncedApplyFilter = debounce(() => {
    applyFilter();
}, 300);

// Watch input and trigger search
watch(filterInput, (newValue, oldValue) => {
    if (newValue !== oldValue) {
        const hadFocus = document.activeElement === searchInputRef.value;
        debouncedApplyFilter();
        if (hadFocus) {
            nextTick(() => {
                searchInputRef.value?.focus();
            });
        }
    }
});

// fetch data with search support and sorting
const fetchData = async () => {
    isLoading.value = true;
    error.value = null;
    try {
        const searchFilter = columnFilters.value.find(f => f.id === 'search');
        const searchQuery = searchFilter ? searchFilter.value : '';

        // Get current sorting state
        const sortState = sorting.value[0];
        let sortField = '';
        let sortDirection = 'asc';

        if (sortState) {
            sortField = sortState.id;
            sortDirection = sortState.desc ? 'desc' : 'asc';
        }

        let url = `/api/records?page=${currentPage.value}&per_page=${pagination.value.pageSize}`;
        if (searchQuery) {
            url += `&search=${encodeURIComponent(searchQuery)}`;
        }
        if (sortField) {
            url += `&sort_field=${sortField}&sort_direction=${sortDirection}`;
        }

        const response = await fetch(url);
        const result: ApiResponse = await response.json();
        data.value = result.data;
        currentPage.value = result.current_page;
        lastPage.value = result.last_page;
        total.value = result.total;

        // Update pagination state to match server response
        pagination.value.pageIndex = result.current_page - 1;
    } catch (err) {
        error.value = 'Failed to load data.';
    } finally {
        isLoading.value = false;
    }
};

// Watch for sorting changes
watch(sorting, () => {
    currentPage.value = 1;
    pagination.value.pageIndex = 0;
    fetchData();
}, { deep: true });

// Pagination handlers
function handlePaginationChange(updater) {
    saveScrollPosition();
    pagination.value = typeof updater === 'function' ? updater(pagination.value) : updater;
    currentPage.value = pagination.value.pageIndex + 1;
    fetchData().then(() => restoreScrollPosition());
}

// Pagination navigation functions
const goToFirstPage = () => {
    if (table.getCanPreviousPage() && !isLoading.value) {
        saveScrollPosition();
        table.setPageIndex(0);
        currentPage.value = 1;
        pagination.value.pageIndex = 0;
        fetchData().then(() => restoreScrollPosition());
    }
};

const goToPreviousPage = () => {
    if (table.getCanPreviousPage() && !isLoading.value) {
        saveScrollPosition();
        table.previousPage();
        currentPage.value = pagination.value.pageIndex + 1;
        fetchData().then(() => restoreScrollPosition());
    }
};

const goToNextPage = () => {
    if (table.getCanNextPage() && !isLoading.value) {
        saveScrollPosition();
        table.nextPage();
        currentPage.value = pagination.value.pageIndex + 1;
        fetchData().then(() => restoreScrollPosition());
    }
};

const goToLastPage = () => {
    if (table.getCanNextPage() && !isLoading.value) {
        saveScrollPosition();
        table.setPageIndex(lastPage.value - 1);
        currentPage.value = lastPage.value;
        pagination.value.pageIndex = lastPage.value - 1;
        fetchData().then(() => restoreScrollPosition());
    }
};

const handlePageSizeChange = (value: string) => {
    if (!isLoading.value) {
        saveScrollPosition();
        table.setPageSize(Number(value));
        pagination.value.pageSize = Number(value);
        // Reset to first page when changing page size
        currentPage.value = 1;
        pagination.value.pageIndex = 0;
        fetchData().then(() => restoreScrollPosition());
    }
};

// Initialize from URL (search param)
const initializeFromURL = () => {
    const urlParams = new URLSearchParams(window.location.search);
    const searchParam = urlParams.get('search');
    if (searchParam) {
        filterInput.value = searchParam;
        columnFilters.value = [{ id: 'search', value: searchParam }];
    }
};

// table instance
const table = useVueTable({
    get data() { return data.value; },
    columns,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    get pageCount() {
        return lastPage.value;
    },
    manualPagination: true,
    manualSorting: true, // Enable manual sorting for server-side sorting
    onPaginationChange: handlePaginationChange,
    onSortingChange: (updater) => {
        sorting.value = typeof updater === 'function' ? updater(sorting.value) : updater;
    },
    state: {
        get pagination() {
            return pagination.value;
        },
        get sorting() {
            return sorting.value;
        },
        columnFilters: columnFilters.value,
    },
});

// lifecycle
onMounted(() => {
    initializeFromURL();
    fetchData();
});

// breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Records', href: '/records' },
    { title: 'Books', href: '/records/books' },
];
</script>

<template>
    <Head title="Books" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <Layout>
            <div class="w-full">
                <!-- Search -->
                <div class="flex items-center gap-2 py-4">
                    <div class="relative">
                        <Input
                            ref="searchInputRef"
                            class="w-[380px] pr-8"
                            placeholder="Search by acc no., title, or author..."
                            v-model="filterInput"
                        />
                        <Button
                            v-if="filterInput"
                            variant="ghost"
                            class="absolute top-0 right-0 h-full px-2"
                            @click="clearFilter"
                        >
                            <X class="h-4 w-4" />
                        </Button>
                        <div
                            v-else
                            class="absolute top-0 right-0 h-full px-2 flex items-center justify-center pointer-events-none"
                        >
                            <Search class="h-4 w-4 text-foreground" />
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="rounded-md border">
                    <Table class="w-full">
                        <TableHeader>
                            <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                                <TableHead v-for="header in headerGroup.headers" :key="header.id">
                                    <FlexRender v-if="!header.isPlaceholder" :render="header.column.columnDef.header" :props="header.getContext()" />
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <template v-if="table.getRowModel().rows?.length && !isLoading">
                                <TableRow v-for="row in table.getRowModel().rows" :key="row.id">
                                    <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                                        <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                                    </TableCell>
                                </TableRow>
                            </template>
                            <TableRow v-else-if="isLoading">
                                <TableCell :colspan="columns.length" class="h-24 text-center">
                                    <Loader2 class="h-4 w-4 animate-spin mr-2 inline" /> Loading...
                                </TableCell>
                            </TableRow>
                            <TableRow v-else>
                                <TableCell :colspan="columns.length" class="h-24 text-center">No results found.</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Pagination Controls -->
                <div class="flex items-center justify-end space-x-2 py-4">
                    <div class="flex-1 text-sm text-muted-foreground">
                        Showing page {{ currentPage }} of {{ lastPage }} in {{ total }} {{ total === 1 || total === 0 ? 'item' : 'items' }}.
                    </div>
                    <div class="flex items-center space-x-2">
                        <p class="text-sm font-medium">Rows per page</p>
                        <Select
                            :model-value="pagination.pageSize.toString()"
                            @update:model-value="handlePageSizeChange"
                            :disabled="isLoading"
                        >
                            <SelectTrigger class="h-8 w-[80px]">
                                <SelectValue :placeholder="pagination.pageSize.toString()" />
                            </SelectTrigger>
                            <SelectContent side="top">
                                <SelectItem v-for="pageSize in pageSizes" :key="pageSize" :value="pageSize.toString()">
                                    {{ pageSize }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="space-x-2">
                        <div class="flex items-center space-x-2">
                            <Button
                                variant="outline"
                                class="hidden h-8 w-8 p-0 lg:flex"
                                :disabled="!table.getCanPreviousPage() || isLoading"
                                @click="goToFirstPage"
                            >
                                <DoubleArrowLeftIcon class="h-4 w-4" />
                            </Button>
                            <Button
                                variant="outline"
                                class="h-8 w-8 p-0"
                                :disabled="!table.getCanPreviousPage() || isLoading"
                                @click="goToPreviousPage"
                            >
                                <ChevronLeftIcon class="h-4 w-4" />
                            </Button>
                            <Button
                                variant="outline"
                                class="h-8 w-8 p-0"
                                :disabled="!table.getCanNextPage() || isLoading"
                                @click="goToNextPage"
                            >
                                <ChevronRightIcon class="h-4 w-4" />
                            </Button>
                            <Button
                                variant="outline"
                                class="hidden h-8 w-8 p-0 lg:flex"
                                :disabled="!table.getCanNextPage() || isLoading"
                                @click="goToLastPage"
                            >
                                <DoubleArrowRightIcon class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Book Details Modal with Authors -->
                <Dialog v-model:open="isDialogOpen">
                    <DialogContent class="sm:max-w-xl grid-rows-[auto_minmax(0,1fr)_auto] p-0 max-h-[90dvh]">
                        <DialogHeader class="p-6 pb-0">
                            <DialogTitle>Book Details</DialogTitle>
                            <DialogDescription>
                                Viewing book information.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 py-4 px-6 overflow-y-auto">
                            <!-- Book Cover and Barcode -->
                            <div class="flex flex-col items-center md:items-start gap-4">
                                <!-- Book Cover Image -->
                                <img
                                    v-if="selectedBook?.cover_image"
                                    :src="'/storage/book_covers/' + selectedBook.cover_image"
                                    alt="Book Cover"
                                    class="h-40 w-32 object-cover border shadow-md rounded"
                                />
                                <div
                                    v-else
                                    class="h-40 w-32 flex items-center justify-center bg-muted text-muted-foreground border shadow-md rounded"
                                >
                                    <span class="text-sm text-center">No Cover</span>
                                </div>

                                <!-- Barcode -->
                                <div v-if="selectedBook?.barcode_path" class="flex flex-col items-center">
                                    <img
                                        :src="'/storage/' + selectedBook.barcode_path"
                                        alt="Book Barcode"
                                        class="h-16 w-auto border shadow-md"
                                    />
                                    <span class="text-xs text-muted-foreground mt-2">
                                        Barcode: {{ selectedBook.accession_number }}
                                    </span>
                                </div>
                                <div
                                    v-else
                                    class="h-16 w-32 flex items-center justify-center bg-muted text-muted-foreground border shadow-md"
                                >
                                    <span class="text-xs">No Barcode</span>
                                </div>
                            </div>

                            <!-- Book Details -->
                            <div class="md:col-span-2">
                                <div v-if="selectedBook" class="grid gap-2 text-sm">
                                    <p><strong>Accession No.:</strong> {{ selectedBook.accession_number }}</p>
                                    <p><strong>Title:</strong> {{ selectedBook.title }}</p>

                                    <!-- Authors Section -->
                                    <div>
                                        <strong>Authors:</strong>
                                        <div v-if="selectedBook.book?.authors && selectedBook.book.authors.length > 0" class="mt-1">
                                            <span v-for="(author, index) in selectedBook.book.authors" :key="author.id">
                                                {{ author.name }}<span v-if="index < selectedBook.book.authors.length - 1">, </span>
                                            </span>
                                        </div>
                                        <span v-else class="text-muted-foreground">No authors listed</span>
                                    </div>

                                    <p><strong>ISBN:</strong> {{ selectedBook.book?.isbn || 'N/A' }}</p>
                                    <p><strong>Publisher:</strong> {{ selectedBook.book?.publisher || 'N/A' }}</p>
                                    <p><strong>Publication Year:</strong> {{ selectedBook.book?.publication_year || 'N/A' }}</p>
                                    <p><strong>Category:</strong> {{ selectedBook.book?.category?.name || 'N/A' }}</p>
                                    <p><strong>Location:</strong> {{ selectedBook.book?.location || 'N/A' }}</p>
                                    <p><strong>Subject:</strong> {{ selectedBook.subject || 'N/A' }}</p>
                                    <p><strong>Status:</strong>
                                        <span :class="{
                                            'text-green-600': selectedBook.status === 'available',
                                            'text-yellow-600': selectedBook.status === 'borrowed',
                                            'text-red-600': ['damaged', 'missing', 'discarded'].includes(selectedBook.status)
                                        }">
                                            {{ selectedBook.status || 'N/A' }}
                                        </span>
                                    </p>
                                    <p><strong>Date Received:</strong> {{ selectedBook.date_received ? new Date(selectedBook.date_received).toLocaleDateString() : 'N/A' }}</p>
                                    <p><strong>Added:</strong> {{ selectedBook.created_at ? new Date(selectedBook.created_at).toLocaleDateString() : 'N/A' }}</p>
                                </div>
                                <div v-else class="text-muted-foreground">
                                    <p>No book selected.</p>
                                </div>
                            </div>
                        </div>

                        <DialogFooter class="p-6 pt-0">
                            <div class="flex justify-between w-full">
                                <Button variant="outline" @click="isDialogOpen = false">Close</Button>
                                <Button @click="handleEdit(selectedBook?.id)">Edit Book Details</Button>
                            </div>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </Layout>
    </AppLayout>
</template>
