<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import Layout from '@/layouts/records/Layout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ChevronLeftIcon, ChevronRightIcon, DoubleArrowLeftIcon, DoubleArrowRightIcon } from '@radix-icons/vue';
import type { ColumnDef, ColumnFiltersState, SortingState } from '@tanstack/vue-table';
import { FlexRender, getCoreRowModel, getFilteredRowModel, getPaginationRowModel, getSortedRowModel, useVueTable } from '@tanstack/vue-table';
import { ArrowUpDown, ChevronDown, Eye, Loader2, Plus, Search, X } from 'lucide-vue-next';
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

// Record type definitions
const recordTypes = [
    { value: 'book', label: 'Book', color: 'bg-blue-100 text-blue-800' },
    { value: 'digitalResource', label: 'Multimedia', color: 'bg-green-100 text-green-800' },
    { value: 'periodical', label: 'Periodical/Magazine', color: 'bg-purple-100 text-purple-800' },
    { value: 'thesis', label: 'Thesis/Dissertation', color: 'bg-orange-100 text-orange-800' },
];

const statusTypes = [
    { key: 'available', label: 'Available', color: 'bg-green-100 text-green-800' },
    { key: 'borrowed', label: 'Borrowed', color: 'bg-yellow-100 text-yellow-800' },
    { key: 'damaged', label: 'Damaged', color: 'bg-red-100 text-red-800' },
    { key: 'missing', label: 'Missing', color: 'bg-red-200 text-red-900' },
    { key: 'discarded', label: 'Discarded', color: 'bg-gray-200 text-gray-800' },
];

const getRecordTypeInfo = (type: string) => {
    return recordTypes.find((rt) => rt.value === type) || { value: type, label: type, color: 'bg-gray-100 text-gray-800' };
};

const getStatusInfo = (status: string) => {
    return statusTypes.find((s) => s.key === status) || { key: status, label: status, color: 'bg-gray-100 text-gray-800' };
};

// Reactive state
const data = ref<any[]>([]);
const isLoading = ref(false);
const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);
const error = ref<string | null>(null);

const sorting = ref<SortingState>([]);
const columnFilters = ref<ColumnFiltersState>([]);

// Modal dialog state
const isDialogOpen = ref(false);
const selectedRecord = ref<any | null>(null);

// Search functionality state
const filterInput = ref<string>('');
const searchInputRef = ref(null);

// Pagination state
const pageSizes = [5, 10, 20, 30, 40, 50];
const pagination = ref({
    pageIndex: 0,
    pageSize: 10,
});

import type { VisibilityState } from '@tanstack/vue-table';
import { DropdownMenuCheckboxItem, DropdownMenuContent, DropdownMenuRoot, DropdownMenuTrigger } from 'radix-vue';

const columnVisibility = ref<VisibilityState>({
    date_received: false, // hidden by default
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
const handleShow = (record: any) => {
    selectedRecord.value = record;
    isDialogOpen.value = true;
};

const handleEdit = (id: string | number, recordType: string) => {
    // Route to appropriate edit page based on record type
    const routes = {
        book: 'records.books.edit',
        digitalResource: 'records.digital-resources.edit',
        periodical: 'records.periodicals.edit',
        thesis: 'records.theses.edit',
    };

    const routeName = routes[recordType as keyof typeof routes] || 'records.books.edit';
    router.get(route(routeName, id));
};

// Sorting helper
function cycleSort(column: any) {
    const currentSort = column.getIsSorted();
    if (currentSort === false) column.toggleSorting(false);
    else if (currentSort === 'asc') column.toggleSorting(true);
    else column.clearSorting();
}

// Columns definition
const columns: ColumnDef<any>[] = [
    {
        accessorKey: 'accession_number',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => ['Acc. No.', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => h('div', row.getValue('accession_number')),
        enableHiding: false,
    },
    {
        accessorKey: 'title',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => ['Title', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => h('div', { class: 'truncate max-w-sm' }, row.getValue('title')),
        enableHiding: false,
    },
    {
        accessorKey: 'status',
        header: 'Status',
        cell: ({ row }) => {
            const status = row.getValue('status') as string;
            const statusInfo = getStatusInfo(status);
            return h(
                Badge,
                {
                    class: `${statusInfo.color} border-0 font-medium text-xs px-2 py-1`,
                },
                () => statusInfo.label,
            );
        },
        enableHiding: false,
    },
    {
        accessorKey: 'record_type',
        header: 'Type',
        cell: ({ row }) => {
            const recordType = row.getValue('record_type') as string;
            const typeInfo = getRecordTypeInfo(recordType);
            return h(
                Badge,
                {
                    class: `${typeInfo.color} border-0 font-medium text-xs px-2 py-1`,
                },
                () => typeInfo.label,
            );
        },
        enableHiding: false,
    },
    {
        accessorKey: 'date_received',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => ['Date Received', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => {
            const value = row.getValue('date_received') as string | null;
            return h('div', value ? new Date(value).toLocaleDateString() : 'N/A');
        },
        enableHiding: true, // allow toggling
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

        // Make API request
        const response = await fetch(`/api/records?${params.toString()}`, {
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
    onColumnVisibilityChange: (updaterOrValue) => {
        columnVisibility.value = typeof updaterOrValue === 'function' ? updaterOrValue(columnVisibility.value) : updaterOrValue;
    },
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
    { title: 'All Records', href: '/records/all' },
];

// Helper function to get record type specific data
const getRecordSpecificData = (record: any) => {
    if (!record || !record.record_type) return {};

    const typeKey = record.record_type;
    const typeData = record[typeKey];

    if (!typeData) return {};

    switch (typeKey) {
        case 'book':
            return {
                isbn: typeData.isbn,
                publisher: typeData.publisher,
                publication_year: typeData.publication_year,
                category: typeData.category?.name,
                location: typeData.location,
                cover_image: typeData.cover_image,
                authors: typeData.authors?.map((a: any) => a.name).join(', '),
            };
        case 'digitalResource':
            return {
                url: typeData.url,
                file_format: typeData.file_format,
                file_size: typeData.file_size,
                access_type: typeData.access_type,
            };
        case 'periodical':
            return {
                issn: typeData.issn,
                volume: typeData.volume,
                issue: typeData.issue,
                publication_date: typeData.publication_date,
                frequency: typeData.frequency,
            };
        case 'thesis':
            return {
                degree_program: typeData.degree_program,
                advisor: typeData.advisor,
                year_submitted: typeData.year_submitted,
                department: typeData.department,
            };
        default:
            return {};
    }
};
</script>

<template>
    <Head title="All Records" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <Layout>
            <div class="w-full">
                <!-- Error message -->
                <div v-if="error" class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-red-700">
                    <p>{{ error }}</p>
                    <Button variant="outline" size="sm" @click="fetchData" class="mt-2"> Retry </Button>
                </div>

                <!-- Filters -->
                <div class="flex items-center justify-between gap-4 py-4">
                    <!-- Search -->
                    <div class="relative">
                        <Input ref="searchInputRef" class="w-[380px] pr-8" placeholder="Search by acc no. or title..." v-model="filterInput" />
                        <Button v-if="filterInput" variant="ghost" class="absolute top-0 right-0 h-full px-2" @click="clearFilter">
                            <X class="h-4 w-4" />
                        </Button>
                        <div v-else class="pointer-events-none absolute top-0 right-0 flex h-full items-center justify-center px-2">
                            <Search class="h-4 w-4 text-foreground" />
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <Button variant="secondary"> <Plus class="h-4 w-4" /> Import Collection </Button>

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
                                    {{ column.id }}
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

                <!-- Record Details Modal -->
                <Dialog v-model:open="isDialogOpen">
                    <DialogContent class="max-h-[95dvh] overflow-x-auto rounded-lg bg-background p-0 shadow-xl sm:max-w-4xl">
                        <!-- Header -->
                        <DialogHeader class="border-b px-4 pt-4 pb-4">
                            <DialogTitle class="flex items-center gap-3 text-xl font-semibold text-foreground">
                                Record Details
                                <Badge v-if="selectedRecord?.record_type" :class="getRecordTypeInfo(selectedRecord.record_type).color + ' border-0'">
                                    {{ getRecordTypeInfo(selectedRecord.record_type).label }}
                                </Badge>
                            </DialogTitle>
                        </DialogHeader>

                        <!-- Main Content -->
                        <div class="grid grid-cols-1 gap-4 overflow-y-auto p-4 py-0 md:grid-cols-3">
                            <!-- Cover/Image and QR Code -->
                            <div class="flex flex-col items-center gap-4 rounded-lg border bg-card p-4">
                                <!-- Cover Image (mainly for books) -->
                                <template v-if="selectedRecord?.record_type === 'book'">
                                    <img
                                        v-if="getRecordSpecificData(selectedRecord).cover_image"
                                        :src="'/storage/resized_book_covers/' + getRecordSpecificData(selectedRecord).cover_image"
                                        alt="Book Cover"
                                        class="h-[225px] w-[150px] border-4 border-background object-cover shadow-lg"
                                    />
                                    <div
                                        v-else
                                        class="flex h-[225px] w-[150px] items-center justify-center border-4 border-background bg-muted text-muted-foreground shadow-lg"
                                    >
                                        <span class="text-sm font-medium">No Cover</span>
                                    </div>
                                </template>
                                <!-- Generic placeholder for other types -->
                                <div
                                    v-else
                                    class="flex h-[225px] w-[150px] items-center justify-center border-4 border-background bg-muted text-muted-foreground shadow-lg"
                                >
                                    <span class="text-sm font-medium">{{ getRecordTypeInfo(selectedRecord?.record_type || '').label }}</span>
                                </div>

                                <!-- QR Code -->
                                <div class="flex w-full flex-col items-center gap-2">
                                    <div v-if="selectedRecord && selectedRecord.book && selectedRecord.book.qrcode_path" class="w-full">
                                        <img
                                            :src="'/storage/' + selectedRecord.book.qrcode_path"
                                            alt="QR Code"
                                            class="mx-auto h-24 w-auto rounded border shadow-sm"
                                        />
                                        <span class="mt-2 block text-center text-xs text-muted-foreground">
                                            QR Code: {{ selectedRecord.accession_number }}
                                        </span>
                                    </div>
                                    <div
                                        v-else
                                        class="flex h-16 w-full items-center justify-center rounded border bg-muted text-muted-foreground shadow-sm"
                                    >
                                        <span class="text-xs font-medium">No QR Code</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Record Details -->
                            <div class="space-y-4 md:col-span-2">
                                <div v-if="selectedRecord" class="space-y-4">
                                    <!-- General Information -->
                                    <div class="rounded-lg border bg-card p-5">
                                        <h3 class="mb-4 text-lg font-semibold text-foreground">General Information</h3>
                                        <div class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                                            <p><strong class="text-foreground">Accession No.:</strong> {{ selectedRecord.accession_number }}</p>
                                            <p><strong class="text-foreground">Title:</strong> {{ selectedRecord.title }}</p>
                                            <p><strong class="text-foreground">Subject:</strong> {{ selectedRecord.subject || 'Not provided' }}</p>
                                            <p>
                                                <strong class="text-foreground">Status:</strong>
                                                <span
                                                    :class="{
                                                        'text-green-600': selectedRecord.status === 'available',
                                                        'text-yellow-600': selectedRecord.status === 'borrowed',
                                                        'text-red-600': ['damaged', 'missing', 'discarded'].includes(selectedRecord.status),
                                                    }"
                                                >
                                                    {{ selectedRecord.status || 'Not provided' }}
                                                </span>
                                            </p>
                                            <p>
                                                <strong class="text-foreground">Date Received:</strong>
                                                {{
                                                    selectedRecord.date_received
                                                        ? new Date(selectedRecord.date_received).toLocaleDateString()
                                                        : 'Not provided'
                                                }}
                                            </p>
                                            <p>
                                                <strong class="text-foreground">Added:</strong>
                                                {{
                                                    selectedRecord.created_at
                                                        ? new Date(selectedRecord.created_at).toLocaleDateString()
                                                        : 'Not provided'
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="p-4 text-center text-muted-foreground">
                                    <p class="text-sm">No record selected.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <DialogFooter class="border-t bg-background p-6">
                            <div class="flex w-full justify-between">
                                <Button variant="outline" @click="isDialogOpen = false" class="px-6">Close</Button>
                                <Button @click="handleEdit(selectedRecord?.id, selectedRecord?.record_type)" class="px-6">
                                    Edit {{ getRecordTypeInfo(selectedRecord?.record_type || '').label }} Details
                                </Button>
                            </div>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </Layout>
    </AppLayout>
</template>
