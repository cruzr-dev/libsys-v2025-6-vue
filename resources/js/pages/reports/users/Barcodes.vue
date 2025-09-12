<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import Layout from '@/layouts/users/ReportsLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ChevronLeftIcon, ChevronRightIcon, DoubleArrowLeftIcon, DoubleArrowRightIcon } from '@radix-icons/vue';
import type { Column, ColumnDef, SortingState } from '@tanstack/vue-table';
import {
    FlexRender,
    getCoreRowModel,
    getExpandedRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    useVueTable,
} from '@tanstack/vue-table';
import { ArrowUpDown, Loader2, Plus } from 'lucide-vue-next';
import { h, ref, onMounted, watch, nextTick } from 'vue';
import * as XLSX from 'xlsx';

// API Data Interface
interface ApiResponse {
    data: any[];
    current_page: number;
    per_page: number;
    last_page: number;
    total: number;
}

// Reactive data state
const data = ref<any[]>([]);
const isLoading = ref(false);
const currentPage = ref(1);
const perPage = ref(10);
const lastPage = ref(1);
const total = ref(0);
const error = ref<string | null>(null);
const isExporting = ref(false);

// Scroll position preservation
const scrollPosition = ref(0);

// Table columns definition
const columns: ColumnDef<any>[] = [
    {
        accessorKey: 'barcode_file',
        header: () => h('div', 'Barcode'),
        cell: ({ row }) => {
            const barcodeFile = row.getValue('barcode_file');
            if (!barcodeFile) {
                return h('div', 'No barcode');
            }
            const imageUrl = `/storage/barcodes/${barcodeFile}`;
            return h('img', {
                src: imageUrl,
                alt: 'Barcode',
                class: 'w-32 h-auto object-contain',
                onError: () => h('div', 'Image not found'),
            });
        },
    },
    {
        accessorKey: 'card_number',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => ['Card #', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => h('div', { class: 'lowercase' }, row.getValue('card_number')),
    },
    {
        accessorKey: 'first_name',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => ['First Name', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => h('div', { class: 'capitalize' }, row.getValue('first_name')),
    },
    {
        accessorKey: 'middle_initial',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => ['M.I', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => h('div', { class: 'capitalize' }, row.getValue('middle_initial') ? row.getValue('middle_initial') + '.' : ''),
    },
    {
        accessorKey: 'last_name',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => ['Last Name', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => h('div', { class: 'capitalize' }, row.getValue('last_name')),
    },
    {
        accessorKey: 'sex',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => ['Sex', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => {
            const sex = row.getValue('sex');
            const displayValue = sex === 'M' ? 'Male' : sex === 'F' ? 'Female' : sex;
            return h('div', displayValue);
        },
    },
    {
        accessorKey: 'email',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => ['Email', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => h('div', { class: 'lowercase max-w-52 truncate' }, row.getValue('email')),
    },
];

// Sorting helper
function cycleSort(column: Column<any, any>) {
    const currentSort = column.getIsSorted();
    if (currentSort === false) column.toggleSorting(false);
    else if (currentSort === 'asc') column.toggleSorting(true);
    else column.clearSorting();
}

// Export to XLSX function
const exportToXLSX = async () => {
    isExporting.value = true;
    try {
        // Fetch all data (not just current page)
        const response = await fetch('/api/reports/users/barcodes?per_page=1000000', {
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
        const exportData = result.data.map(item => ({
            'Card Number': item.card_number,
            'First Name': item.first_name,
            'Middle Initial': item.middle_initial ? item.middle_initial + '.' : '',
            'Last Name': item.last_name,
            'Sex': item.sex === 'M' ? 'Male' : item.sex === 'F' ? 'Female' : item.sex,
            'Email': item.email,
            'Barcode File': item.barcode_file ? `/storage/barcodes/${item.barcode_file}` : 'No barcode',
        }));

        // Create worksheet
        const ws = XLSX.utils.json_to_sheet(exportData);

        // Customize column widths (optional)
        ws['!cols'] = [
            { wch: 15 }, // Card Number
            { wch: 20 }, // First Name
            { wch: 10 }, // Middle Initial
            { wch: 20 }, // Last Name
            { wch: 10 }, // Sex
            { wch: 30 }, // Email
            { wch: 30 }, // Barcode File
        ];

        // Create workbook
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Library Staff');

        // Generate and download the file
        XLSX.writeFile(wb, 'library_staff_report.xlsx');
    } catch (err) {
        console.error('Export error:', err);
        error.value = err instanceof Error ? err.message : 'An error occurred while exporting data';
    } finally {
        isExporting.value = false;
    }
};

// Table state
const sorting = ref<SortingState>([]);
const expanded = ref({});
const pageSizes = [5, 10, 20, 30, 40, 50];
const pagination = ref({
    pageIndex: 0,
    pageSize: 10,
});

// Scroll position management functions
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

// API fetch function
const fetchData = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        const params = new URLSearchParams();
        params.append('page', (pagination.value.pageIndex + 1).toString());
        params.append('per_page', pagination.value.pageSize.toString());

        if (sorting.value.length > 0) {
            params.append('sort_field', sorting.value[0].id);
            params.append('sort_direction', sorting.value[0].desc ? 'desc' : 'asc');
        }

        const response = await fetch(`/api/reports/users/barcodes?${params.toString()}`, {
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
        data.value = result.data || [];
        currentPage.value = result.current_page || 1;
        perPage.value = result.per_page || 10;
        lastPage.value = result.last_page || 1;
        total.value = result.total || 0;
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

// Table instance
const table = useVueTable({
    get data() {
        return data.value;
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getExpandedRowModel: getExpandedRowModel(),
    get pageCount() {
        return lastPage.value;
    },
    manualPagination: true,
    manualSorting: true,
    onPaginationChange: handlePaginationChange,
    onSortingChange: handleSortingChange,
    onExpandedChange: (updater) => {
        expanded.value = typeof updater === 'function' ? updater(expanded.value) : updater;
    },
    state: {
        get sorting() {
            return sorting.value;
        },
        get expanded() {
            return expanded.value;
        },
        get pagination() {
            return pagination.value;
        },
    },
});

// Enhanced event handlers with scroll preservation
function handlePaginationChange(updater) {
    saveScrollPosition();
    pagination.value = typeof updater === 'function' ? updater(pagination.value) : updater;
    fetchData().then(() => {
        restoreScrollPosition();
    });
}

function handleSortingChange(updaterOrValue) {
    sorting.value = typeof updaterOrValue === 'function' ? updaterOrValue(sorting.value) : updaterOrValue;
    pagination.value.pageIndex = 0;
    fetchData();
}

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

// Initialize URL parameters from current page URL
const initializeFromURL = () => {
    const urlParams = new URLSearchParams(window.location.search);
    const page = parseInt(urlParams.get('page') || '1');
    const perPageParam = parseInt(urlParams.get('per_page') || '10');
    pagination.value.pageIndex = page - 1;
    pagination.value.pageSize = perPageParam;
    const sortField = urlParams.get('sort_field');
    const sortDirection = urlParams.get('sort_direction');
    if (sortField) {
        sorting.value = [{ id: sortField, desc: sortDirection === 'desc' }];
    }
};

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: '/users' },
    { title: 'Library Staff', href: '/users/admin' },
];

// Lifecycle
onMounted(() => {
    initializeFromURL();
    fetchData();
});

watch(() => window.location.search, () => {
    initializeFromURL();
    fetchData();
});
</script>

<template>
    <Head title="Library Staff" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <Layout>
            <div class="w-full">
                <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
                    <p>{{ error }}</p>
                    <Button variant="outline" size="sm" @click="fetchData" class="mt-2">
                        Retry
                    </Button>
                </div>

                <div class="flex items-center justify-end gap-2 py-4">
                    <Button variant="secondary" @click="exportToXLSX" :disabled="isExporting || isLoading">
                        <Loader2 v-if="isExporting" class="h-4 w-4 animate-spin mr-2" />
                        <Plus v-else class="w-4 h-4 mr-2" />
                        {{ isExporting ? 'Exporting...' : 'Export XLSX' }}
                    </Button>
                </div>

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
                                <template v-for="row in table.getRowModel().rows" :key="row.id">
                                    <TableRow :data-state="row.getIsSelected() && 'selected'">
                                        <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                                            <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-if="row.getIsExpanded()">
                                        <TableCell :colspan="row.getAllCells().length">
                                            {{ JSON.stringify(row.original) }}
                                        </TableCell>
                                    </TableRow>
                                </template>
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
                                <TableCell :colspan="columns.length" class="h-24 text-center">
                                    No results found.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <div class="flex items-center justify-end space-x-2 py-4">
                    <div class="flex-1 text-sm text-muted-foreground">
                        Showing page {{ currentPage }} of {{ lastPage }} in {{ total }} {{ total === 1 || total === 0 ? 'item' : 'items' }}.
                    </div>
                    <div class="flex items-center space-x-2">
                        <p class="text-sm font-medium">Rows per page</p>
                        <Select
                            :model-value="table.getState().pagination.pageSize.toString()"
                            @update:model-value="handlePageSizeChange"
                            :disabled="isLoading"
                        >
                            <SelectTrigger class="h-8 w-[80px]">
                                <SelectValue :placeholder="table.getState().pagination.pageSize.toString()" />
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
            </div>
        </Layout>
    </AppLayout>
</template>
