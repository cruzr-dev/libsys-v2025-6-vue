<script setup lang="ts">
// Imports
import { Button } from '@/components/ui/button';
import {
    DropdownMenuRoot,
    DropdownMenuTrigger,
    DropdownMenuContent,
    DropdownMenuCheckboxItem,
} from 'radix-vue';
import { Input } from '@/components/ui/input';
import { valueUpdater } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import type { Column, ColumnDef, ColumnFiltersState, SortingState, VisibilityState } from '@tanstack/vue-table';
import {
    FlexRender,
    getCoreRowModel,
    getExpandedRowModel,
    getFilteredRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    useVueTable,
} from '@tanstack/vue-table';
import { ArrowUpDown, X, Plus, ChevronDown } from 'lucide-vue-next';
import { ChevronRightIcon, ChevronLeftIcon, DoubleArrowLeftIcon, DoubleArrowRightIcon } from "@radix-icons/vue";
import { h, ref } from 'vue';
import { route } from 'ziggy-js';
import DropdownAction from '../users/DataTableDemoColumn.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Layout from '@/layouts/users/Layout.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import DeleteDialog from '@/components/DeleteDialog.vue';

// Props - Add columnVisibility to props
interface Props {
    data?: { data: any[]; current_page?: number; per_page?: number; last_page?: number };
    filter?: any[];
    currentSortField?: string;
    currentSortDirection?: string;
    columnVisibility?: Record<string, boolean>; // Add this
}
const props = withDefaults(defineProps<Props>(), {
    data: () => ({ data: [], current_page: 1, per_page: 10, last_page: 1 }),
    filter: () => [],
    currentSortField: undefined,
    currentSortDirection: 'asc',
    columnVisibility: () => ({}), // Add default
});

// Table setup
type RowData = any;
const data = props.data.data;
const columns: ColumnDef<RowData>[] = [
    {
        accessorKey: 'library_id',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => ['Library ID', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => h('div', { class: 'lowercase' }, row.getValue('library_id')),
        enableHiding: false,
    },
    {
        accessorKey: 'first_name',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => ['First Name', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => h('div', { class: 'capitalize' }, row.getValue('first_name')),
        enableHiding: false,
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
        enableHiding: false,
    },
    {
        accessorKey: 'sex',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => ['Sex', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => {
            const sex = row.getValue('sex');
            const displayValue = sex === 'm' ? 'Male' : sex === 'f' ? 'Female' : sex;
            return h('div', displayValue);
        },
    },
    {
        accessorKey: 'email',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => ['Email', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => h('div', { class: 'lowercase' }, row.getValue('email')),
        enableHiding: false,
    },
    { id: 'actions', enableHiding: false, cell: ({ row }) => h(DropdownAction, { user: row.original }) },
];

// Sorting helper
function cycleSort(column: Column<RowData, any>) {
    const currentSort = column.getIsSorted();
    if (currentSort === false) column.toggleSorting(false);
    else if (currentSort === 'asc') column.toggleSorting(true);
    else column.clearSorting();
}

// State - Initialize with props values and defaults
const sorting = ref<SortingState>(props.currentSortField ? [{ id: props.currentSortField, desc: props.currentSortDirection === 'desc' }] : []);
const columnFilters = ref<ColumnFiltersState>(props.filter ? props.filter.map((f) => ({ id: f.id, value: f.value })) : []);
const columnVisibility = ref<VisibilityState>({
    ...props.columnVisibility, // Merge with props
});
const expanded = ref({});
const pageSizes = [5, 10, 20, 30, 40, 50];
const pagination = ref({
    pageIndex: (props.data?.current_page ?? 1) - 1,
    pageSize: props.data?.per_page ?? 10,
});

// Helper function to build column visibility for URL
function buildColumnVisibility(visibility: VisibilityState) {
    const result: Record<string, string> = {};
    // Include both visible and hidden columns explicitly
    Object.entries(visibility).forEach(([key, value]) => {
        if (value === true) {
            result[`show_${key}`] = '1';
        } else {
            result[`hide_${key}`] = '1';
        }
    });
    return result;
}

// Table instance
const table = useVueTable({
    data,
    columns,
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getExpandedRowModel: getExpandedRowModel(),
    pageCount: props.data?.last_page ?? 1,
    manualPagination: true,
    manualSorting: true,
    manualFiltering: true,
    onPaginationChange: handlePaginationChange,
    onSortingChange: handleSortingChange,
    onColumnFiltersChange: handleFilterChange,
    onColumnVisibilityChange: handleColumnVisibilityChange, // Updated handler
    onExpandedChange: (v) => valueUpdater(v, expanded),
    state: {
        get sorting() {
            return sorting.value;
        },
        get columnFilters() {
            return columnFilters.value;
        },
        get columnVisibility() {
            return columnVisibility.value;
        },
        get expanded() {
            return expanded.value;
        },
        get pagination() {
            return pagination.value;
        },
    },
});

// Filtering
const filterInput = ref<string>('');
const applyFilter = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        const newFilters = columnFilters.value.filter(f => f.id !== 'search');
        if (filterInput.value.trim()) {
            newFilters.push({ id: 'search', value: filterInput.value.trim() });
        }
        table.setColumnFilters(newFilters);
    }, 300); // 300ms delay
};
const initializeSearchInput = () => {
    const searchFilter = props.filter?.find(f => f.id === 'search');
    if (searchFilter) {
        filterInput.value = searchFilter.value || '';
    }
};
const clearFilter = () => {
    filterInput.value = '';
    // Remove search filter from column filters
    const newFilters = columnFilters.value.filter(f => f.id !== 'search');
    table.setColumnFilters(newFilters);
};
initializeSearchInput();
let searchTimeout: ReturnType<typeof setTimeout>;

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: '/users' },
    { title: 'Staff admins', href: '/users/admins' },
];

// Add Handling
const createNew = () => {
    router.get(route('admins.create'));
}

// Delete handling
const showDeleteAlert = ref(false);
const selectedUserId = ref(null);
const handleDelete = (id) => {
    router.delete(route('admins.destroy', id), {
        preserveState: false,
        preserveScroll: true,
    });
    showDeleteAlert.value = false;
    selectedUserId.value = null;
};

// Updated event handlers to include column visibility
function handlePaginationChange(updater) {
    pagination.value = typeof updater === 'function' ? updater(pagination.value) : updater;
    const filters = buildFilters(columnFilters.value);
    const visibilityParams = buildColumnVisibility(columnVisibility.value);

    router.get(
        route('admins.index'),
        {
            page: pagination.value.pageIndex + 1,
            per_page: pagination.value.pageSize,
            ...filters,
            ...visibilityParams
        },
        { preserveState: false, preserveScroll: true },
    );
}

function handleSortingChange(updaterOrValue) {
    sorting.value = typeof updaterOrValue === 'function' ? updaterOrValue(sorting.value) : updaterOrValue;
    const filters = buildFilters(columnFilters.value);
    const visibilityParams = buildColumnVisibility(columnVisibility.value);

    router.get(
        route('admins.index'),
        {
            page: 1,
            per_page: pagination.value.pageSize,
            sort_field: sorting.value[0]?.id,
            sort_direction: sorting.value[0]?.desc ? 'desc' : 'asc',
            ...filters,
            ...visibilityParams
        },
        { preserveState: false, preserveScroll: true },
    );
}

function handleFilterChange(updaterOrValue) {
    columnFilters.value = typeof updaterOrValue === 'function' ? updaterOrValue(columnFilters.value) : updaterOrValue;
    const filters = buildFilters(columnFilters.value);
    const visibilityParams = buildColumnVisibility(columnVisibility.value);

    router.get(
        route('admins.index'),
        {
            page: 1,
            per_page: pagination.value.pageSize,
            ...filters,
            ...visibilityParams
        },
        { preserveState: false, preserveScroll: true }
    );
}

// New handler for column visibility changes
function handleColumnVisibilityChange(updaterOrValue) {
    columnVisibility.value = typeof updaterOrValue === 'function' ? updaterOrValue(columnVisibility.value) : updaterOrValue;
    const filters = buildFilters(columnFilters.value);
    const visibilityParams = buildColumnVisibility(columnVisibility.value);

    router.get(
        route('admins.index'),
        {
            page: pagination.value.pageIndex + 1,
            per_page: pagination.value.pageSize,
            sort_field: sorting.value[0]?.id,
            sort_direction: sorting.value[0]?.desc ? 'desc' : 'asc',
            ...filters,
            ...visibilityParams
        },
        { preserveState: false, preserveScroll: true }
    );
}

function buildFilters(filtersArr: ColumnFiltersState) {
    return filtersArr.reduce(
        (acc, f) => {
            if (Array.isArray(f.value) && f.value.length > 0) acc[f.id] = f.value;
            else if (f.value !== '' && f.value !== null && f.value !== undefined) acc[f.id] = f.value;
            return acc;
        },
        {} as Record<string, any>,
    );
}
</script>

<template>
    <Head title="Staff Admins" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <Layout>
            <div class="w-full">
                <div class="flex items-center justify-between gap-2 py-4">
                    <div class="flex gap-2">
                        <div class="relative">
                            <Input
                                class="w-[320px] pr-8"
                                placeholder="Search by lib id, first name, or last name ..."
                                v-model="filterInput"
                                @keyup.enter="applyFilter"
                            />
                            <Button v-if="filterInput" variant="ghost" class="absolute top-0 right-0 h-full px-2" @click="clearFilter">
                                <X class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Button variant="secondary" @click="createNew">
                            <Plus class="h-4"></Plus>
                            Add New Admin
                        </Button>
                        <DropdownMenuRoot>
                            <DropdownMenuTrigger as-child>
                                <Button variant="outline" class="ml-auto">
                                    Columns
                                    <ChevronDown class="ml-2 h-4 w-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent
                                align="end"
                                class="min-w-[220px] bg-white rounded-md p-1 shadow-lg border border-gray-200 z-50"
                            >
                                <DropdownMenuCheckboxItem
                                    v-for="column in table.getAllColumns().filter((col) => col.getCanHide())"
                                    :key="column.id"
                                    :checked="column.getIsVisible()"
                                    @update:checked="(value) => column.toggleVisibility(!!value)"
                                    class="relative flex cursor-pointer select-none items-center rounded-sm pl-8 pr-2 py-1.5 text-sm outline-none hover:bg-gray-100"
                                >
      <span class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center">
        <svg
            v-if="column.getIsVisible()"
            class="h-4 w-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
        >
          <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M5 13l4 4L19 7"
          />
        </svg>
      </span>
                                    {{ column.id }}
                                </DropdownMenuCheckboxItem>
                            </DropdownMenuContent>
                        </DropdownMenuRoot>
                    </div>
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
                            <template v-if="table.getRowModel().rows?.length">
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

                            <TableRow v-else>
                                <TableCell :colspan="columns.length" class="h-24 text-center"> No results. </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
                <div class="flex items-center justify-end space-x-2 py-4">
                    <div class="flex-1 text-sm text-muted-foreground">
                        {{ table.getFilteredRowModel().rows.length }} items.
                    </div>
                    <div class="flex items-center space-x-2">
                        <p class="text-sm font-medium">Rows per page</p>
                        <Select
                            :model-value="table.getState().pagination.pageSize.toString()"
                            @update:model-value="(value) => table.setPageSize(Number(value))"
                        >
                            <SelectTrigger class="h-8 w-[70px]">
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
                                :disabled="!table.getCanPreviousPage()"
                                @click="table.setPageIndex(0)"
                            >
                                <DoubleArrowLeftIcon class="h-4 w-4" />
                            </Button>
                            <Button variant="outline" class="h-8 w-8 p-0" :disabled="!table.getCanPreviousPage()" @click="table.previousPage()">
                                <ChevronLeftIcon class="h-4 w-4" />
                            </Button>
                            <Button variant="outline" class="h-8 w-8 p-0" :disabled="!table.getCanNextPage()" @click="table.nextPage()">
                                <ChevronRightIcon class="h-4 w-4" />
                            </Button>
                            <Button
                                variant="outline"
                                class="hidden h-8 w-8 p-0 lg:flex"
                                :disabled="!table.getCanNextPage()"
                                @click="table.setPageIndex(table.getPageCount() - 1)"
                            >
                                <DoubleArrowRightIcon class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
            <DeleteDialog v-model:open="showDeleteAlert" :userId="selectedUserId" @confirm-delete="handleDelete" />
        </Layout>
    </AppLayout>
</template>
