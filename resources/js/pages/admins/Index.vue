<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Head, router } from '@inertiajs/vue3';
import {
    FlexRender,
    getCoreRowModel,
    getExpandedRowModel,
    getFilteredRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    useVueTable,
    VisibilityState,
} from '@tanstack/vue-table';
import { ArrowUpDown, ChevronDown, X } from 'lucide-vue-next';
import { route } from 'ziggy-js';

import { Checkbox } from '@/components/ui/checkbox';
import { DropdownMenu, DropdownMenuCheckboxItem, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { valueUpdater } from '@/lib/utils';
import { ChevronLeftIcon, ChevronRightIcon, DoubleArrowLeftIcon, DoubleArrowRightIcon } from '@radix-icons/vue';
import { h, ref } from 'vue';
import DropdownAction from '../users/DataTableDemoColumn.vue';

import { Plus } from 'lucide-vue-next';

interface Props {
    data?: {
        data: any[];
        current_page?: number;
        per_page?: number;
        last_page?: number;
    };
    filter?: any[];
    currentSortField?: string;
    currentSortDirection?: string;
}

const props = withDefaults(defineProps<Props>(), {
    data: () => ({ data: [], current_page: 1, per_page: 10, last_page: 1 }),
    filter: () => [],
    currentSortField: undefined,
    currentSortDirection: 'asc',
});

import type { Column, ColumnDef, ColumnFiltersState, Row, SortingState, Table } from '@tanstack/vue-table';
type RowData = any;
const data = props.data.data; // Now safe to access directly
const columns: ColumnDef<RowData>[] = [
    {
        id: 'search',
        // This is a virtual column for searching, not displayed
        accessorFn: (row) => `${row.first_name} ${row.last_name}`,
        enableSorting: false,
        enableHiding: false,
    },
    {
        id: 'select',
        header: ({ table }: { table: Table<RowData> }) =>
            h(Checkbox, {
                checked: table.getIsAllPageRowsSelected() || (table.getIsSomePageRowsSelected() && 'indeterminate'),
                'onUpdate:checked': (value: boolean) => table.toggleAllPageRowsSelected(!!value),
                ariaLabel: 'Select all',
            }),
        cell: ({ row }: { row: Row<RowData> }) =>
            h(Checkbox, {
                checked: row.getIsSelected(),
                'onUpdate:checked': (value: boolean) => row.toggleSelected(!!value),
                ariaLabel: 'Select row',
            }),
        enableSorting: false,
        enableHiding: false,
    },
    {
        accessorKey: 'library_id',
        header: ({ column }: { column: Column<RowData, any> }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => {
                        // Get current sort state
                        const currentSort = column.getIsSorted();

                        // Cycle through: none -> asc -> desc -> none
                        if (currentSort === false) {
                            column.toggleSorting(false); // Set to ascending
                        } else if (currentSort === 'asc') {
                            column.toggleSorting(true); // Set to descending
                        } else {
                            column.clearSorting(); // Clear sorting
                        }
                    },
                },
                () => ['Library ID ', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
        cell: ({ row }: { row: Row<RowData> }) => h('div', { class: 'lowercase' }, row.getValue('library_id')),
        enableHiding: false,
    },
    {
        accessorKey: 'first_name',
        header: ({ column }: { column: Column<RowData, any> }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => {
                        const currentSort = column.getIsSorted();
                        if (currentSort === false) {
                            column.toggleSorting(false); // asc
                        } else if (currentSort === 'asc') {
                            column.toggleSorting(true); // desc
                        } else {
                            column.clearSorting(); // none
                        }
                    },
                },
                () => ['First Name', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
        cell: ({ row }: { row: Row<RowData> }) => h('div', { class: 'capitalize' }, row.getValue('first_name')),
    },
    {
        accessorKey: 'middle_initial',
        header: ({ column }: { column: Column<RowData, any> }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => {
                        const currentSort = column.getIsSorted();
                        if (currentSort === false) {
                            column.toggleSorting(false);
                        } else if (currentSort === 'asc') {
                            column.toggleSorting(true);
                        } else {
                            column.clearSorting();
                        }
                    },
                },
                () => ['M.I', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
        cell: ({ row }: { row: Row<RowData> }) => {
            const middleInitial = row.getValue('middle_initial');
            return h('div', { class: 'capitalize' }, middleInitial ? middleInitial + '.' : '');
        },
    },
    {
        accessorKey: 'last_name',
        header: ({ column }: { column: Column<RowData, any> }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => {
                        const currentSort = column.getIsSorted();
                        if (currentSort === false) {
                            column.toggleSorting(false);
                        } else if (currentSort === 'asc') {
                            column.toggleSorting(true);
                        } else {
                            column.clearSorting();
                        }
                    },
                },
                () => ['Last Name', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
        cell: ({ row }: { row: Row<RowData> }) => h('div', { class: 'capitalize' }, row.getValue('last_name')),
    },
    {
        accessorKey: 'sex',
        header: ({ column }: { column: Column<RowData, any> }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => {
                        const currentSort = column.getIsSorted();
                        if (currentSort === false) {
                            column.toggleSorting(false);
                        } else if (currentSort === 'asc') {
                            column.toggleSorting(true);
                        } else {
                            column.clearSorting();
                        }
                    },
                },
                () => ['Sex', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
        cell: ({ row }: { row: Row<RowData> }) => h('div', { class: 'lowercase' }, row.getValue('sex')),
    },
    {
        accessorKey: 'email',
        header: ({ column }: { column: Column<RowData, any> }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => {
                        const currentSort = column.getIsSorted();
                        if (currentSort === false) {
                            column.toggleSorting(false);
                        } else if (currentSort === 'asc') {
                            column.toggleSorting(true);
                        } else {
                            column.clearSorting();
                        }
                    },
                },
                () => ['Email', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
        cell: ({ row }: { row: Row<RowData> }) => h('div', { class: 'lowercase' }, row.getValue('email')),
        enableHiding: false,
    },
    {
        id: 'actions',
        enableHiding: false,
        cell: ({ row }: { row: Row<RowData> }) => {
            const user = row.original;

            return h(
                'div',
                { class: 'relative' },
                h(DropdownAction, {
                    user,
                    onExpand: row.toggleExpanded,
                    onEdit: (id) => {
                        // Handle edit functionality
                        console.log('Edit clicked for ID:', id);
                        // Add your edit logic here
                        // For example: router.get(route('admins.edit', id));
                    },
                    onDelete: (id) => {
                        showDeleteAlert.value = true;
                        selectedUserId.value = id;
                    },
                }),
            );
        },
    },
];

const sorting = ref<SortingState>(
    props.currentSortField
        ? [
              {
                  id: props.currentSortField,
                  desc: props.currentSortDirection === 'desc',
              },
          ]
        : [],
);
const columnFilters = ref<ColumnFiltersState>(props.filter ? props.filter.map((f) => ({ id: f.id, value: f.value })) : []);
const columnVisibility = ref<VisibilityState>({
    search: false, // Hide the search column by default
});
const rowSelection = ref({});
const expanded = ref({});
const pageSizes = [1, 2, 3, 5, 10, 15, 30, 40, 50, 100];
const pagination = ref({
    pageIndex: (props.data?.current_page ?? 1) - 1,
    pageSize: props.data?.per_page ?? 10,
});

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
    onPaginationChange: (updater) => {
        if (typeof updater === 'function') {
            pagination.value = updater(pagination.value);
        } else {
            pagination.value = updater;
        }
        router.get(
            route('admins.index'),
            {
                page: pagination.value.pageIndex + 1,
                per_page: pagination.value.pageSize,
                sort_field: sorting.value[0]?.id,
                sort_direction: sorting.value.length == 0 ? undefined : sorting.value[0]?.desc ? 'desc' : 'asc',
            },
            { preserveState: false, preserveScroll: true },
        );
    },
    onSortingChange: (updaterOrValue) => {
        if (typeof updaterOrValue === 'function') {
            sorting.value = updaterOrValue(sorting.value);
        } else {
            sorting.value = updaterOrValue;
        }

        // Build filters object (same logic as above)
        let filters: Record<string, any> = {};
        if (columnFilters.value && columnFilters.value.length > 0) {
            filters = columnFilters.value.reduce((acc: Record<string, any>, filter) => {
                if (Array.isArray(filter.value) && filter.value.length > 0) {
                    acc[filter.id] = filter.value;
                } else if (!Array.isArray(filter.value) && filter.value !== '' && filter.value !== null && filter.value !== undefined) {
                    acc[filter.id] = filter.value;
                }
                return acc;
            }, {});
        }

        router.get(
            route('admins.index'),
            {
                page: 1, // Reset to first page when sorting changes
                per_page: pagination.value.pageSize,
                sort_field: sorting.value[0]?.id,
                sort_direction: sorting.value.length == 0 ? undefined : sorting.value[0]?.desc ? 'desc' : 'asc',
                ...filters,
            },
            { preserveState: false, preserveScroll: true },
        );
    },
    onColumnFiltersChange: (updaterOrValue) => {
        if (typeof updaterOrValue === 'function') {
            columnFilters.value = updaterOrValue(columnFilters.value);
        } else {
            columnFilters.value = updaterOrValue;
        }

        // Build filters object
        let filters: Record<string, any> = {};
        if (columnFilters.value && columnFilters.value.length > 0) {
            filters = columnFilters.value.reduce((acc: Record<string, any>, filter) => {
                // Handle array values (for multi-select filters)
                if (Array.isArray(filter.value) && filter.value.length > 0) {
                    acc[filter.id] = filter.value;
                } else if (!Array.isArray(filter.value) && filter.value !== '' && filter.value !== null && filter.value !== undefined) {
                    acc[filter.id] = filter.value;
                }
                return acc;
            }, {});
        }

        router.get(
            route('admins.index'),
            {
                page: 1, // Reset to first page when filtering
                per_page: pagination.value.pageSize,
                sort_field: sorting.value[0]?.id,
                sort_direction: sorting.value.length == 0 ? undefined : sorting.value[0]?.desc ? 'desc' : 'asc',
                ...filters,
            },
            { preserveState: false, preserveScroll: true },
        );
    },
    onColumnVisibilityChange: (updaterOrValue) => valueUpdater(updaterOrValue, columnVisibility),
    onRowSelectionChange: (updaterOrValue) => valueUpdater(updaterOrValue, rowSelection),
    onExpandedChange: (updaterOrValue) => valueUpdater(updaterOrValue, expanded),
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
        get rowSelection() {
            return rowSelection.value;
        },
        get expanded() {
            return expanded.value;
        },
        get pagination() {
            return pagination.value;
        },
    },
});

// Local state for the input
const filterInput = ref<string>((table.getColumn('search')?.getFilterValue() as string) ?? ''); // Function to apply the filter
const applyFilter = () => {
    table.getColumn('search')?.setFilterValue(filterInput.value);
};

const clearFilter = () => {
    filterInput.value = '';
    table.getColumn('search')?.setFilterValue('');
};

import DeleteDialog from '@/components/DeleteDialog.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Layout from '@/layouts/users/Layout.vue';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: '/users',
    },
    {
        title: 'Staff admins',
        href: '/users/admins',
    },
];

const createNewStaffAdmin = () => {
    router.get(route('admins.create'));
};

const showDeleteAlert = ref(false);
const selectedUserId = ref(null);

const handleDelete = (id) => {
    console.log('Deleting user with ID:', id);

    router.delete(route('admins.destroy', id), {
        preserveState: false, // Important: Don't preserve state so fresh data is fetched
        preserveScroll: true, // Keep scroll position
        onSuccess: () => {
            console.log('Delete successful');
            // Optional: Force reload if still having issues
            // router.reload({ only: ['data'] });
        },
        onError: (errors) => {
            console.error('Delete failed:', errors);
        },
    });

    showDeleteAlert.value = false;
    selectedUserId.value = null;
};
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
                                @blur="applyFilter"
                            />
                            <Button v-if="filterInput" variant="ghost" class="absolute top-0 right-0 h-full px-2" @click="clearFilter">
                                <X class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Button variant="outline" @click="createNewStaffAdmin">
                            <Plus class="h-4"></Plus>
                            Create New
                        </Button>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="outline" class="ml-auto">
                                    Columns
                                    <ChevronDown class="ml-2 h-4 w-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuCheckboxItem
                                    v-for="column in table.getAllColumns().filter((column) => column.getCanHide())"
                                    :key="column.id"
                                    class="capitalize"
                                    :checked="column.getIsVisible()"
                                    @update:checked="
                                        (value: boolean | 'indeterminate') => {
                                            column.toggleVisibility(!!value);
                                        }
                                    "
                                >
                                    {{ column.id }}
                                </DropdownMenuCheckboxItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
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
                        {{ table.getFilteredSelectedRowModel().rows.length }} of {{ table.getFilteredRowModel().rows.length }} row(s) selected.
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
