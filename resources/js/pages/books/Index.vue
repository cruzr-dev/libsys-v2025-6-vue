<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import Layout from '@/layouts/records/Layout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronLeftIcon, ChevronRightIcon, DoubleArrowLeftIcon, DoubleArrowRightIcon } from '@radix-icons/vue';
import type { ColumnDef, ColumnFiltersState, SortingState, VisibilityState } from '@tanstack/vue-table';
import { FlexRender, getCoreRowModel, getFilteredRowModel, getPaginationRowModel, getSortedRowModel, useVueTable } from '@tanstack/vue-table';
import { ArrowUpDown, ChevronDown, Eye, Loader2, Plus, Search, X } from 'lucide-vue-next';
import { DropdownMenuCheckboxItem, DropdownMenuContent, DropdownMenuRoot, DropdownMenuTrigger } from 'radix-vue';
import { h, nextTick, onMounted, ref, watch } from 'vue';

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

// Reactive state
const data = ref<any[]>([]);
const isLoading = ref(false);
const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);
const error = ref<string | null>(null);

const sorting = ref<SortingState>([]);
const columnFilters = ref<ColumnFiltersState>([]);
const columnVisibility = ref<VisibilityState>({
    editors_list: false,
    publication_year: false,
    call_number: false,
    isbn: false,
    volume: false,
    edition: false,
    publisher: false,
    ddc_classification: true,
    physical_location: true,
});

// Modal dialog state
const isDialogOpen = ref(false);
const selectedBook = ref<any | null>(null);

// Search functionality state
const filterInput = ref<string>('');
const searchInputRef = ref(null);

// Pagination state
const pageSizes = [5, 10, 20, 30, 40, 50];
const pagination = ref({
    pageIndex: 0,
    pageSize: 10,
});

// Scroll position tracking
const scrollPosition = ref(0);

const saveScrollPosition = () => {
    scrollPosition.value = window.pageYOffset || document.documentElement.scrollTop;
};

const restoreScrollPosition = () => {
    nextTick(() => {
        window.scrollTo({
            top: scrollPosition.value,
            behavior: 'instant',
        });
    });
};

// Modal handlers
const handleShow = (book: any) => {
    selectedBook.value = book;
    isDialogOpen.value = true;
};

const handleEdit = (id: string | number) => {
    router.get(route('books.edit', id));
};

// Sorting helper
function cycleSort(column: any) {
    const currentSort = column.getIsSorted();
    if (currentSort === false) column.toggleSorting(false);
    else if (currentSort === 'asc') column.toggleSorting(true);
    else column.clearSorting();
}

// Columns definition with visibility support
const columns: ColumnDef<any>[] = [
    {
        accessorKey: 'accession_number',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => ['Acc. #', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => h('div', row.getValue('accession_number')),
        enableHiding: false, // Always show accession number
    },
    {
        accessorKey: 'title',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => ['Title', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => h('div', { class: 'truncate max-w-80' }, row.getValue('title')),
        enableHiding: false, // Always show title
    },
    {
        id: 'volume',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => ['Volume', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => {
            const volume = row.original.book?.volume;
            return h('div', volume || 'N/A');
        },
        enableHiding: true,
    },
    {
        id: 'edition',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => ['Edition', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => {
            const edition = row.original.book?.edition;
            return h('div', edition || 'N/A');
        },
        enableHiding: true,
    },
    {
        id: 'publisher',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => ['Publisher', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => {
            const publisher = row.original.book?.publisher;
            return h('div', publisher || 'N/A');
        },
        enableHiding: true,
    },
    {
        id: 'isbn',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => ['ISBN', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => {
            const isbn = row.original.book?.isbn;
            return h('div', isbn || 'N/A');
        },
        enableHiding: true,
    },
    {
        id: 'call_number',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => ['Call No.', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => {
            const callNumber = row.original.book?.call_number;
            return h('div', callNumber || 'N/A');
        },
        enableHiding: true,
    },
    {
        id: 'ddc_classification',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => ['DDC', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => {
            const ddc = row.original.ddc_classification;
            return h('div', ddc || 'N/A');
        },
        enableHiding: true,
    },
    {
        id: 'physical_location',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => ['Location', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => {
            const location = row.original.physical_location;
            return h('div', location || 'N/A');
        },
        enableHiding: true,
    },
    {
        accessorKey: 'authors_list',
        header: () => h('div', 'Authors'),
        cell: ({ row }) => {
            const authorsList = row.getValue('authors_list');
            return h('div', { class: 'truncate max-w-xs' }, authorsList || 'No authors');
        },
        enableHiding: true,
    },
    {
        accessorKey: 'editors_list',
        header: () => h('div', 'Editors'),
        cell: ({ row }) => {
            const editorsList = row.getValue('editors_list');
            return h('div', { class: 'truncate max-w-xs' }, editorsList || 'No editors');
        },
        enableHiding: true,
    },
    {
        id: 'publication_year', // use id instead of accessorKey since it's nested
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => ['Year', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => {
            const year = row.original.book?.publication_year;
            return h('div', year || 'N/A');
        },
        enableHiding: true,
    },
    {
        id: 'action',
        header: 'Action',
        enableHiding: false,
        cell: ({ row }) =>
            h(
                Button,
                {
                    variant: 'outline',
                    size: 'sm',
                    onClick: () => handleShow(row.original),
                    class: 'flex items-center gap-2',
                },
                () => [h(Eye, { class: 'h-4 w-4 text-muted-foreground' }), 'Show'],
            ),
    },
];

// Apply search filter
const applyFilter = () => {
    const newFilters = columnFilters.value.filter((f) => f.id !== 'search');
    if (filterInput.value.trim()) {
        newFilters.push({ id: 'search', value: filterInput.value.trim() });
    }
    table.setColumnFilters(newFilters);
};

// Clear search filter
const clearFilter = () => {
    filterInput.value = '';
    const newFilters = columnFilters.value.filter((f) => f.id !== 'search');
    table.setColumnFilters(newFilters);
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

// Fetch data with all parameters
const fetchData = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        // Build query parameters
        const params = new URLSearchParams();

        // Pagination
        params.append('page', (pagination.value.pageIndex + 1).toString());
        params.append('per_page', pagination.value.pageSize.toString());

        // Sorting
        if (sorting.value.length > 0) {
            params.append('sort_field', sorting.value[0].id);
            params.append('sort_direction', sorting.value[0].desc ? 'desc' : 'asc');
        }

        // Filters
        columnFilters.value.forEach((filter) => {
            if (Array.isArray(filter.value) && filter.value.length > 0) {
                params.append(filter.id, filter.value.join(','));
            } else if (filter.value !== '' && filter.value !== null && filter.value !== undefined) {
                params.append(filter.id, filter.value.toString());
            }
        });

        // Column visibility
        Object.entries(columnVisibility.value).forEach(([key, value]) => {
            if (value === true) {
                params.append(`show_${key}`, '1');
            } else {
                params.append(`hide_${key}`, '1');
            }
        });

        // Make API request
        const response = await fetch(`/api/books?${params.toString()}`, {
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result: ApiResponse = await response.json();

        // Update reactive data
        data.value = result.data || [];
        currentPage.value = result.current_page || 1;
        lastPage.value = result.last_page || 1;
        total.value = result.total || 0;

        // Update pagination state to match API response
        pagination.value.pageIndex = (result.current_page || 1) - 1;
        pagination.value.pageSize = result.per_page || 10;
    } catch (err) {
        console.error('API fetch error:', err);
        error.value = err instanceof Error ? err.message : 'An error occurred while fetching data';
        data.value = [];
    } finally {
        isLoading.value = false;
    }
};

// Debounced fetch for immediate UI feedback
const debouncedFetch = debounce(fetchData, 300);

// Enhanced event handlers
function handlePaginationChange(updater: any) {
    saveScrollPosition();
    pagination.value = typeof updater === 'function' ? updater(pagination.value) : updater;
    fetchData().then(() => {
        restoreScrollPosition();
    });
}

function handleSortingChange(updaterOrValue: any) {
    sorting.value = typeof updaterOrValue === 'function' ? updaterOrValue(sorting.value) : updaterOrValue;
    pagination.value.pageIndex = 0;
    fetchData();
}

function handleFilterChange(updaterOrValue: any) {
    columnFilters.value = typeof updaterOrValue === 'function' ? updaterOrValue(columnFilters.value) : updaterOrValue;
    pagination.value.pageIndex = 0;
    debouncedFetch();
}

function handleColumnVisibilityChange(updaterOrValue: any) {
    columnVisibility.value = typeof updaterOrValue === 'function' ? updaterOrValue(columnVisibility.value) : updaterOrValue;
    fetchData();
}

// Pagination navigation functions with scroll preservation
const goToFirstPage = () => {
    if (table.getCanPreviousPage() && !isLoading.value) {
        saveScrollPosition();
        table.setPageIndex(0);
        fetchData().then(() => {
            restoreScrollPosition();
        });
    }
};

const goToPreviousPage = () => {
    if (table.getCanPreviousPage() && !isLoading.value) {
        saveScrollPosition();
        table.previousPage();
        fetchData().then(() => {
            restoreScrollPosition();
        });
    }
};

const goToNextPage = () => {
    if (table.getCanNextPage() && !isLoading.value) {
        saveScrollPosition();
        table.nextPage();
        fetchData().then(() => {
            restoreScrollPosition();
        });
    }
};

const goToLastPage = () => {
    if (table.getCanNextPage() && !isLoading.value) {
        saveScrollPosition();
        table.setPageIndex(table.getPageCount() - 1);
        fetchData().then(() => {
            restoreScrollPosition();
        });
    }
};

const handlePageSizeChange = (value: string) => {
    if (!isLoading.value) {
        saveScrollPosition();
        table.setPageSize(Number(value));
        fetchData().then(() => {
            restoreScrollPosition();
        });
    }
};

// Initialize from URL
const initializeFromURL = () => {
    const urlParams = new URLSearchParams(window.location.search);

    // Initialize pagination
    const page = parseInt(urlParams.get('page') || '1');
    const perPageParam = parseInt(urlParams.get('per_page') || '10');
    pagination.value.pageIndex = page - 1;
    pagination.value.pageSize = perPageParam;

    // Initialize sorting
    const sortField = urlParams.get('sort_field');
    const sortDirection = urlParams.get('sort_direction');
    if (sortField) {
        sorting.value = [{ id: sortField, desc: sortDirection === 'desc' }];
    }

    // Initialize search filter
    const searchParam = urlParams.get('search');
    if (searchParam) {
        filterInput.value = searchParam;
        columnFilters.value = [{ id: 'search', value: searchParam }];
    }

    // Initialize column visibility
    urlParams.forEach((value, key) => {
        if (key.startsWith('show_')) {
            const columnKey = key.replace('show_', '');
            columnVisibility.value[columnKey] = value === '1';
        } else if (key.startsWith('hide_')) {
            const columnKey = key.replace('hide_', '');
            columnVisibility.value[columnKey] = value !== '1';
        }
    });
};

// Table instance
const table = useVueTable({
    get data() {
        return data.value;
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    get pageCount() {
        return lastPage.value;
    },
    manualPagination: true,
    manualSorting: true,
    manualFiltering: true,
    onPaginationChange: handlePaginationChange,
    onSortingChange: handleSortingChange,
    onColumnFiltersChange: handleFilterChange,
    onColumnVisibilityChange: handleColumnVisibilityChange,
    state: {
        get pagination() {
            return pagination.value;
        },
        get sorting() {
            return sorting.value;
        },
        get columnFilters() {
            return columnFilters.value;
        },
        get columnVisibility() {
            return columnVisibility.value;
        },
    },
});

// Lifecycle
onMounted(() => {
    initializeFromURL();
    fetchData();
});

// Watch for external changes
watch(
    () => window.location.search,
    () => {
        initializeFromURL();
        fetchData();
    },
);

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Records', href: '/records' },
    { title: 'Books', href: '/records/books' },
];

console.log(data);
</script>

<template>
    <Head title="Books" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <Layout>
            <div class="w-full">
                <!-- Error message -->
                <div v-if="error" class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-red-700">
                    <p>{{ error }}</p>
                    <Button variant="outline" size="sm" @click="fetchData" class="mt-2"> Retry </Button>
                </div>

                <!-- Search and Controls -->
                <div class="flex items-center justify-between gap-2 py-4">
                    <div class="relative">
                        <Input
                            ref="searchInputRef"
                            class="w-[380px] pr-8"
                            placeholder="Search by acc no., title, author, or editor..."
                            v-model="filterInput"
                        />
                        <Button v-if="filterInput" variant="ghost" class="absolute top-0 right-0 h-full px-2" @click="clearFilter">
                            <X class="h-4 w-4" />
                        </Button>
                        <div v-else class="pointer-events-none absolute top-0 right-0 flex h-full items-center justify-center px-2">
                            <Search class="h-4 w-4 text-foreground" />
                        </div>
                    </div>

                    <!-- Column Visibility Dropdown -->
                    <div class="flex gap-2">
                        <Link href="/records/books/create">
                            <Button variant="secondary"> <Plus class="h-4 w-4" /> Add Book </Button>
                        </Link>
                        <DropdownMenuRoot>
                            <DropdownMenuTrigger as-child>
                                <Button variant="outline" class="ml-auto" :disabled="isLoading">
                                    Columns
                                    <ChevronDown class="ml-2 h-4 w-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="z-50 min-w-[220px] rounded-md border border-gray-200 bg-white p-1 shadow-lg">
                                <DropdownMenuCheckboxItem
                                    v-for="column in table.getAllColumns().filter((col) => col.getCanHide())"
                                    :key="column.id"
                                    :checked="column.getIsVisible()"
                                    @update:checked="(value) => column.toggleVisibility(!!value)"
                                    class="relative flex cursor-pointer items-center rounded-sm py-1.5 pr-2 pl-8 text-sm outline-none select-none hover:bg-gray-100"
                                >
                                    <span class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center">
                                        <svg v-if="column.getIsVisible()" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </span>
                                    {{ column.id.replace('_', ' ').replace(/\b\w/g, (l) => l.toUpperCase()) }}
                                </DropdownMenuCheckboxItem>
                            </DropdownMenuContent>
                        </DropdownMenuRoot>
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
                                    <div class="flex items-center justify-center">
                                        <Loader2 class="mr-2 h-4 w-4 animate-spin" />
                                        Loading...
                                    </div>
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
                        <Select :model-value="pagination.pageSize.toString()" @update:model-value="handlePageSizeChange" :disabled="isLoading">
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
                            <Button variant="outline" class="h-8 w-8 p-0" :disabled="!table.getCanNextPage() || isLoading" @click="goToNextPage">
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

                <!-- Book Details Modal -->
                <Dialog v-model:open="isDialogOpen">
                    <DialogContent class="max-h-[95dvh] overflow-x-auto rounded-lg bg-background p-0 shadow-xl sm:max-w-4xl">
                        <!-- Header -->
                        <DialogHeader class="border-b px-4 pt-4 pb-4">
                            <DialogTitle class="text-xl font-semibold text-foreground">Book Details</DialogTitle>
                        </DialogHeader>

                        <!-- Main Content -->
                        <div class="grid grid-cols-1 gap-4 overflow-y-auto p-4 py-0 md:grid-cols-3">
                            <!-- Book Cover Card -->
                            <!-- Book Cover Card -->
                            <div class="flex flex-col items-center justify-center gap-4 rounded-lg border bg-card p-4">
                                <!-- Book Cover Image with QR Code -->
                                <div class="relative w-full">
                                    <img
                                        v-if="selectedBook.book?.cover_image"
                                        :src="'/storage/resized_book_covers/' + selectedBook.book.cover_image"
                                        alt="Book Cover"
                                        class="w-full rounded-lg border-4 border-background object-cover shadow-lg"
                                    />
                                    <div
                                        v-else
                                        class="flex h-64 w-full items-center justify-center rounded-lg border-4 border-background bg-muted text-muted-foreground shadow-lg"
                                    >
                                        <span class="text-sm font-medium">No Cover</span>
                                    </div>

                                    <!-- QR Code in Top Right Corner -->
                                    <div class="absolute top-2 right-2">
                                        <div v-if="selectedBook.book?.qrcode_file" class="flex flex-col items-center">
                                            <img
                                                :src="'/storage/qrcodes/' + selectedBook.book.qrcode_file"
                                                alt="Book QR Code"
                                                class="h-16 w-16 rounded border bg-white shadow-md"
                                            />
                                        </div>
                                        <div
                                            v-else
                                            class="flex h-16 w-16 items-center justify-center rounded border bg-muted text-muted-foreground shadow-md"
                                        >
                                            <span class="text-[10px] font-medium">No QR</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Details Section -->
                            <div class="space-y-4 md:col-span-2">
                                <div v-if="selectedBook" class="space-y-4">
                                    <!-- Book Information -->
                                    <div class="rounded-lg border bg-card p-5">
                                        <h3 class="mb-4 text-lg font-semibold text-foreground">Book Information</h3>
                                        <div class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                                            <p><strong class="text-foreground">Accession No.:</strong> {{ selectedBook.accession_number }}</p>
                                            <p><strong class="text-foreground">Title:</strong> {{ selectedBook.title }}</p>
                                            <p>
                                                <strong class="text-foreground">Authors:</strong>
                                                <span v-if="selectedBook.book?.authors && selectedBook.book.authors.length > 0">
                                                    <span v-for="(author, index) in selectedBook.book.authors" :key="author.id">
                                                        {{ author.name }}<span v-if="index < selectedBook.book.authors.length - 1">, </span>
                                                    </span>
                                                </span>
                                                <span v-else class="text-muted-foreground">No authors listed</span>
                                            </p>
                                            <p>
                                                <strong class="text-foreground">Editors:</strong>
                                                <span v-if="selectedBook.book?.editors && selectedBook.book.editors.length > 0">
                                                    <span v-for="(editor, index) in selectedBook.book.editors" :key="editor.id">
                                                        {{ editor.name }}<span v-if="index < selectedBook.book.editors.length - 1">, </span>
                                                    </span>
                                                </span>
                                                <span v-else class="text-muted-foreground">No editors listed</span>
                                            </p>
                                            <p><strong class="text-foreground">ISBN:</strong> {{ selectedBook.book?.isbn || 'Not provided' }}</p>
                                            <p>
                                                <strong class="text-foreground">Publisher:</strong>
                                                {{ selectedBook.book?.publisher || 'Not provided' }}
                                            </p>
                                            <p>
                                                <strong class="text-foreground">Publication Year:</strong>
                                                {{ selectedBook.book?.publication_year || 'Not provided' }}
                                            </p>
                                            <p>
                                                <strong class="text-foreground">Category:</strong>
                                                {{ selectedBook.book?.category?.name || 'Not provided' }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Additional Information -->
                                    <div class="rounded-lg border bg-card p-5">
                                        <h3 class="mb-4 text-lg font-semibold text-foreground">Additional Information</h3>
                                        <div class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                                            <p>
                                                <strong class="text-foreground">Location:</strong> {{ selectedBook.book?.location || 'Not provided' }}
                                            </p>
                                            <p><strong class="text-foreground">Subject:</strong> {{ selectedBook.subject || 'Not provided' }}</p>
                                            <p>
                                                <strong class="text-foreground">Status:</strong>
                                                <span
                                                    :class="{
                                                        'text-green-600': selectedBook.status === 'available',
                                                        'text-yellow-600': selectedBook.status === 'borrowed',
                                                        'text-red-600': ['damaged', 'missing', 'discarded'].includes(selectedBook.status),
                                                    }"
                                                >
                                                    {{ selectedBook.status || 'Not provided' }}
                                                </span>
                                            </p>
                                            <p>
                                                <strong class="text-foreground">Date Received:</strong>
                                                {{
                                                    selectedBook.date_received
                                                        ? new Date(selectedBook.date_received).toLocaleDateString()
                                                        : 'Not provided'
                                                }}
                                            </p>
                                            <p>
                                                <strong class="text-foreground">Added:</strong>
                                                {{
                                                    selectedBook.created_at ? new Date(selectedBook.created_at).toLocaleDateString() : 'Not provided'
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="p-4 text-center text-muted-foreground">
                                    <p class="text-sm">No book selected.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <DialogFooter class="border-t bg-background p-6">
                            <div class="flex w-full justify-between">
                                <Button variant="outline" @click="isDialogOpen = false" class="px-6">Close</Button>
                                <Button @click="handleEdit(selectedBook?.id)" class="px-6">Edit Book Details</Button>
                            </div>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </Layout>
    </AppLayout>
</template>
