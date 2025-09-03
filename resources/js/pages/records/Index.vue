<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import Layout from '@/layouts/records/Layout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ChevronLeftIcon, ChevronRightIcon, DoubleArrowLeftIcon, DoubleArrowRightIcon } from '@radix-icons/vue';
import { ArrowUpDown, Search, X, Loader2, Eye, Filter } from 'lucide-vue-next';
import { h, ref, onMounted, watch, nextTick } from 'vue';
import type { ColumnDef, SortingState, ColumnFiltersState } from '@tanstack/vue-table';
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

// Record type definitions
const recordTypes = [
    { value: 'book', label: 'Book', color: 'bg-blue-100 text-blue-800' },
    { value: 'digitalResource', label: 'Multimedia', color: 'bg-green-100 text-green-800' },
    { value: 'periodical', label: 'Periodical/Magazine', color: 'bg-purple-100 text-purple-800' },
    { value: 'thesis', label: 'Thesis/Dissertation', color: 'bg-orange-100 text-orange-800' },
];

const getRecordTypeInfo = (type: string) => {
    return recordTypes.find(rt => rt.value === type) || { value: type, label: type, color: 'bg-gray-100 text-gray-800' };
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

// Record type filter state
const recordTypeFilter = ref<string>('');

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
            behavior: 'instant'
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
        thesis: 'records.theses.edit'
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
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => [
                'Acc. No.', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })
            ]),
        cell: ({ row }) => h('div', row.getValue('accession_number')),
        enableHiding: false,
    },
    {
        accessorKey: 'title',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => [
                'Title', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })
            ]),
        cell: ({ row }) => h('div', { class: 'truncate max-w-sm' }, row.getValue('title')),
        enableHiding: false,
    },
    {
        accessorKey: 'record_type',
        header: 'Type',
        cell: ({ row }) => {
            const recordType = row.getValue('record_type') as string;
            const typeInfo = getRecordTypeInfo(recordType);
            return h(Badge, {
                class: `${typeInfo.color} border-0 font-medium text-xs px-2 py-1`
            }, () => typeInfo.label);
        },
        enableHiding: false,
        filterFn: (row, id, value) => {
            return value === '' || row.getValue(id) === value;
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

// Handle record type filter
const handleRecordTypeFilter = (value: string) => {
    recordTypeFilter.value = value;
    const newFilters = columnFilters.value.filter((f) => f.id !== 'record_type');
    if (value && value !== '') {
        newFilters.push({ id: 'record_type', value });
    }
    table.setColumnFilters(newFilters);
};

// Clear record type filter
const clearRecordTypeFilter = () => {
    recordTypeFilter.value = '';
    const newFilters = columnFilters.value.filter((f) => f.id !== 'record_type');
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
        columnFilters.value.forEach(filter => {
            if (Array.isArray(filter.value) && filter.value.length > 0) {
                params.append(filter.id, filter.value.join(','));
            } else if (filter.value !== '' && filter.value !== null && filter.value !== undefined) {
                params.append(filter.id, filter.value.toString());
            }
        });

        // Make API request
        const response = await fetch(`/api/records?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
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

    // Initialize record type filter
    const recordTypeParam = urlParams.get('record_type');
    if (recordTypeParam) {
        recordTypeFilter.value = recordTypeParam;
        const existingFilters = columnFilters.value.filter((f) => f.id !== 'record_type');
        columnFilters.value = [...existingFilters, { id: 'record_type', value: recordTypeParam }];
    }
};

// Table instance
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
    manualSorting: true,
    manualFiltering: true,
    onPaginationChange: handlePaginationChange,
    onSortingChange: handleSortingChange,
    onColumnFiltersChange: handleFilterChange,
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
    },
});

// Lifecycle
onMounted(() => {
    initializeFromURL();
    fetchData();
});

// Watch for external changes
watch(() => window.location.search, () => {
    initializeFromURL();
    fetchData();
});

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
                authors: typeData.authors?.map((a: any) => a.name).join(', ')
            };
        case 'digitalResource':
            return {
                url: typeData.url,
                file_format: typeData.file_format,
                file_size: typeData.file_size,
                access_type: typeData.access_type
            };
        case 'periodical':
            return {
                issn: typeData.issn,
                volume: typeData.volume,
                issue: typeData.issue,
                publication_date: typeData.publication_date,
                frequency: typeData.frequency
            };
        case 'thesis':
            return {
                degree_program: typeData.degree_program,
                advisor: typeData.advisor,
                year_submitted: typeData.year_submitted,
                department: typeData.department
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
                <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
                    <p>{{ error }}</p>
                    <Button variant="outline" size="sm" @click="fetchData" class="mt-2">
                        Retry
                    </Button>
                </div>

                <!-- Filters -->
                <div class="flex items-center justify-between gap-4 py-4">
                    <!-- Search -->
                    <div class="relative">
                        <Input
                            ref="searchInputRef"
                            class="w-[380px] pr-8"
                            placeholder="Search by acc no. or title..."
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

                    <!-- Record Type Filter -->
                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-2">
                            <Filter class="h-4 w-4 text-muted-foreground" />
                            <span class="text-sm font-medium">Type:</span>
                        </div>
                        <Select
                            :model-value="recordTypeFilter"
                            @update:model-value="handleRecordTypeFilter"
                        >
                            <SelectTrigger class="w-[180px]">
                                <SelectValue placeholder="All Types" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">All Types</SelectItem>
                                <SelectItem
                                    v-for="type in recordTypes"
                                    :key="type.value"
                                    :value="type.value"
                                >
                                    {{ type.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <Button
                            v-if="recordTypeFilter"
                            variant="ghost"
                            size="sm"
                            @click="clearRecordTypeFilter"
                            class="px-2"
                        >
                            <X class="h-4 w-4" />
                        </Button>
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
                                    <div class="flex justify-center items-center">
                                        <Loader2 class="h-4 w-4 animate-spin mr-2" />
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

                <!-- Record Details Modal -->
                <Dialog v-model:open="isDialogOpen">
                    <DialogContent class="sm:max-w-4xl grid-rows-[auto_minmax(0,1fr)_auto] p-0 max-h-[90dvh]">
                        <DialogHeader class="p-6 pb-0">
                            <DialogTitle class="flex items-center gap-3">
                                Record Details
                                <Badge
                                    v-if="selectedRecord?.record_type"
                                    :class="getRecordTypeInfo(selectedRecord.record_type).color + ' border-0'"
                                >
                                    {{ getRecordTypeInfo(selectedRecord.record_type).label }}
                                </Badge>
                            </DialogTitle>
                            <DialogDescription>
                                Viewing {{ selectedRecord?.record_type || 'record' }} information.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 py-4 px-6 overflow-y-auto">
                            <!-- Cover/Image and Barcode -->
                            <div class="flex flex-col items-center lg:items-start gap-4">
                                <!-- Cover Image (mainly for books) -->
                                <template v-if="selectedRecord?.record_type === 'book'">
                                    <img
                                        v-if="getRecordSpecificData(selectedRecord).cover_image"
                                        :src="'/storage/book_covers/' + getRecordSpecificData(selectedRecord).cover_image"
                                        alt="Book Cover"
                                        class="h-40 w-32 object-cover border shadow-md rounded"
                                    />
                                    <div
                                        v-else
                                        class="h-40 w-32 flex items-center justify-center bg-muted text-muted-foreground border shadow-md rounded"
                                    >
                                        <span class="text-sm text-center">No Cover</span>
                                    </div>
                                </template>

                                <!-- Generic placeholder for other types -->
                                <div
                                    v-else
                                    class="h-40 w-32 flex items-center justify-center bg-muted text-muted-foreground border shadow-md rounded"
                                >
                                    <span class="text-sm text-center">{{ getRecordTypeInfo(selectedRecord?.record_type || '').label }}</span>
                                </div>

                                <!-- Barcode -->
                                <div v-if="selectedRecord?.barcode_path" class="flex flex-col items-center">
                                    <img
                                        :src="'/storage/' + selectedRecord.barcode_path"
                                        alt="Barcode"
                                        class="h-16 w-auto border shadow-md"
                                    />
                                    <span class="text-xs text-muted-foreground mt-2">
                                        Barcode: {{ selectedRecord.accession_number }}
                                    </span>
                                </div>
                                <div
                                    v-else
                                    class="h-16 w-32 flex items-center justify-center bg-muted text-muted-foreground border shadow-md"
                                >
                                    <span class="text-xs">No Barcode</span>
                                </div>
                            </div>

                            <!-- Record Details -->
                            <div class="lg:col-span-3">
                                <div v-if="selectedRecord" class="grid gap-4">
                                    <!-- Common Fields -->
                                    <div class="grid gap-2 text-sm">
                                        <h3 class="font-semibold text-base mb-2">General Information</h3>
                                        <p><strong>Accession No.:</strong> {{ selectedRecord.accession_number }}</p>
                                        <p><strong>Title:</strong> {{ selectedRecord.title }}</p>
                                        <p><strong>Subject:</strong> {{ selectedRecord.subject || 'N/A' }}</p>
                                        <p><strong>Status:</strong>
                                            <span :class="{
                                                'text-green-600': selectedRecord.status === 'available',
                                                'text-yellow-600': selectedRecord.status === 'borrowed',
                                                'text-red-600': ['damaged', 'missing', 'discarded'].includes(selectedRecord.status)
                                            }">
                                                {{ selectedRecord.status || 'N/A' }}
                                            </span>
                                        </p>
                                        <p><strong>Date Received:</strong> {{ selectedRecord.date_received ? new Date(selectedRecord.date_received).toLocaleDateString() : 'N/A' }}</p>
                                        <p><strong>Added:</strong> {{ selectedRecord.created_at ? new Date(selectedRecord.created_at).toLocaleDateString() : 'N/A' }}</p>
                                    </div>

                                    <!-- Type-specific Fields -->
                                    <div class="grid gap-2 text-sm" v-if="selectedRecord.record_type">
                                        <h3 class="font-semibold text-base mb-2">{{ getRecordTypeInfo(selectedRecord.record_type).label }} Details</h3>

                                        <!-- Book specific fields -->
                                        <template v-if="selectedRecord.record_type === 'book'">
                                            <p><strong>ISBN:</strong> {{ getRecordSpecificData(selectedRecord).isbn || 'N/A' }}</p>
                                            <p><strong>Authors:</strong> {{ getRecordSpecificData(selectedRecord).authors || 'N/A' }}</p>
                                            <p><strong>Publisher:</strong> {{ getRecordSpecificData(selectedRecord).publisher || 'N/A' }}</p>
                                            <p><strong>Publication Year:</strong> {{ getRecordSpecificData(selectedRecord).publication_year || 'N/A' }}</p>
                                            <p><strong>Category:</strong> {{ getRecordSpecificData(selectedRecord).category || 'N/A' }}</p>
                                            <p><strong>Location:</strong> {{ getRecordSpecificData(selectedRecord).location || 'N/A' }}</p>
                                        </template>

                                        <!-- Digital Resource specific fields -->
                                        <template v-else-if="selectedRecord.record_type === 'digitalResource'">
                                            <p><strong>URL:</strong>
                                                <a v-if="getRecordSpecificData(selectedRecord).url"
                                                   :href="getRecordSpecificData(selectedRecord).url"
                                                   target="_blank"
                                                   class="text-blue-600 hover:underline break-all">
                                                    {{ getRecordSpecificData(selectedRecord).url }}
                                                </a>
                                                <span v-else>N/A</span>
                                            </p>
                                            <p><strong>File Format:</strong> {{ getRecordSpecificData(selectedRecord).file_format || 'N/A' }}</p>
                                            <p><strong>File Size:</strong> {{ getRecordSpecificData(selectedRecord).file_size || 'N/A' }}</p>
                                            <p><strong>Access Type:</strong> {{ getRecordSpecificData(selectedRecord).access_type || 'N/A' }}</p>
                                        </template>

                                        <!-- Periodical specific fields -->
                                        <template v-else-if="selectedRecord.record_type === 'periodical'">
                                            <p><strong>ISSN:</strong> {{ getRecordSpecificData(selectedRecord).issn || 'N/A' }}</p>
                                            <p><strong>Volume:</strong> {{ getRecordSpecificData(selectedRecord).volume || 'N/A' }}</p>
                                            <p><strong>Issue:</strong> {{ getRecordSpecificData(selectedRecord).issue || 'N/A' }}</p>
                                            <p><strong>Publication Date:</strong> {{ getRecordSpecificData(selectedRecord).publication_date ? new Date(getRecordSpecificData(selectedRecord).publication_date).toLocaleDateString() : 'N/A' }}</p>
                                            <p><strong>Frequency:</strong> {{ getRecordSpecificData(selectedRecord).frequency || 'N/A' }}</p>
                                        </template>

                                        <!-- Thesis specific fields -->
                                        <template v-else-if="selectedRecord.record_type === 'thesis'">
                                            <p><strong>Degree Program:</strong> {{ getRecordSpecificData(selectedRecord).degree_program || 'N/A' }}</p>
                                            <p><strong>Advisor:</strong> {{ getRecordSpecificData(selectedRecord).advisor || 'N/A' }}</p>
                                            <p><strong>Department:</strong> {{ getRecordSpecificData(selectedRecord).department || 'N/A' }}</p>
                                            <p><strong>Year Submitted:</strong> {{ getRecordSpecificData(selectedRecord).year_submitted || 'N/A' }}</p>
                                        </template>
                                    </div>
                                </div>
                                <div v-else class="text-muted-foreground">
                                    <p>No record selected.</p>
                                </div>
                            </div>
                        </div>

                        <DialogFooter class="p-6 pt-0">
                            <div class="flex justify-between w-full">
                                <Button variant="outline" @click="isDialogOpen = false">Close</Button>
                                <Button @click="handleEdit(selectedRecord?.id, selectedRecord?.record_type)">
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
