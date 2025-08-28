<script setup lang="ts">
// Imports
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import Layout from '@/layouts/users/Layout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ChevronLeftIcon, ChevronRightIcon, DoubleArrowLeftIcon, DoubleArrowRightIcon } from '@radix-icons/vue';
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
import { ArrowUpDown, ChevronDown, X, Loader2, Eye, Search, Plus } from 'lucide-vue-next';
import { DropdownMenuCheckboxItem, DropdownMenuContent, DropdownMenuRoot, DropdownMenuTrigger } from 'radix-vue';
import { h, ref, onMounted, watch, nextTick } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";

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

// Scroll position preservation
const scrollPosition = ref(0);

// Search input ref for focus preservation
const searchInputRef = ref(null);

// Show handler function
const isDialogOpen = ref(false);
const selectedUser = ref<any | null>(null);

const handleShow = (user: any) => {
    selectedUser.value = user;
    isDialogOpen.value = true;
};

// Table columns definition
const columns: ColumnDef<any>[] = [
    {
        accessorKey: 'library_id',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => ['Lib ID', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => h('div', { class: 'lowercase' }, row.getValue('library_id')),
        enableHiding: false,
    },
    {
        accessorKey: 'card_number',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => ['Card #', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => h('div', { class: 'lowercase' }, row.getValue('card_number')),
    },
    {
        accessorKey: 'school_id',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => ['Scl ID', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => h('div', { class: 'lowercase' }, row.getValue('school_id')),
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
            const displayValue = sex === 'M' ? 'Male' : sex === 'F' ? 'Female' : sex;
            return h('div', displayValue);
        },
    },
    {
        accessorKey: 'email',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => ['Email', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => h('div', { class: 'lowercase max-w-52 truncate' }, row.getValue('email')),
        enableHiding: false,
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

// Sorting helper
function cycleSort(column: Column<any, any>) {
    const currentSort = column.getIsSorted();
    if (currentSort === false) column.toggleSorting(false);
    else if (currentSort === 'asc') column.toggleSorting(true);
    else column.clearSorting();
}

// Table state
const sorting = ref<SortingState>([]);
const columnFilters = ref<ColumnFiltersState>([]);
const columnVisibility = ref<VisibilityState>({
    school_id: false,
    sex: false,
});
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

// Debounce utility
function debounce<T extends (...args: any[]) => any>(func: T, wait: number): T {
    let timeout: ReturnType<typeof setTimeout>;
    return ((...args: any[]) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(null, args), wait);
    }) as T;
}

// API fetch function
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

        // Column visibility
        Object.entries(columnVisibility.value).forEach(([key, value]) => {
            if (value === true) {
                params.append(`show_${key}`, '1');
            } else {
                params.append(`hide_${key}`, '1');
            }
        });

        // Make API request
        const response = await fetch(`/api/admin?${params.toString()}`, {
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
        perPage.value = result.per_page || 10;
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

// Table instance
const table = useVueTable({
    get data() {
        return data.value;
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getExpandedRowModel: getExpandedRowModel(),
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
    onExpandedChange: (updater) => {
        expanded.value = typeof updater === 'function' ? updater(expanded.value) : updater;
    },
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

// Enhanced event handlers with scroll preservation
function handlePaginationChange(updater) {
    // Save current scroll position before pagination change
    saveScrollPosition();

    pagination.value = typeof updater === 'function' ? updater(pagination.value) : updater;

    // Fetch data and restore scroll position after DOM update
    fetchData().then(() => {
        restoreScrollPosition();
    });
}

function handleSortingChange(updaterOrValue) {
    sorting.value = typeof updaterOrValue === 'function' ? updaterOrValue(sorting.value) : updaterOrValue;
    // Reset to first page when sorting changes
    pagination.value.pageIndex = 0;
    // Don't preserve scroll position for sorting - user expects to see top
    fetchData();
}

function handleFilterChange(updaterOrValue) {
    columnFilters.value = typeof updaterOrValue === 'function' ? updaterOrValue(columnFilters.value) : updaterOrValue;
    // Reset to first page when filters change
    pagination.value.pageIndex = 0;
    // Don't preserve scroll position for filtering - user expects to see top
    debouncedFetch(); // Use debounced version for filters
}

function handleColumnVisibilityChange(updaterOrValue) {
    columnVisibility.value = typeof updaterOrValue === 'function' ? updaterOrValue(columnVisibility.value) : updaterOrValue;
    fetchData();
}

// Enhanced pagination navigation functions with scroll preservation
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

// Search functionality
const filterInput = ref<string>('');

const applyFilter = () => {
    const newFilters = columnFilters.value.filter((f) => f.id !== 'search');
    if (filterInput.value.trim()) {
        newFilters.push({ id: 'search', value: filterInput.value.trim() });
    }
    table.setColumnFilters(newFilters);
};

const clearFilter = () => {
    filterInput.value = '';
    const newFilters = columnFilters.value.filter((f) => f.id !== 'search');
    table.setColumnFilters(newFilters);
};

// Debounced search - automatically triggers on input change
const debouncedApplyFilter = debounce(() => {
    applyFilter();
}, 300);

// Watch for search input changes with focus preservation
watch(filterInput, (newValue, oldValue) => {
    if (newValue !== oldValue) {
        // Store focus state before applying filter
        const hadFocus = document.activeElement === searchInputRef.value;

        debouncedApplyFilter();

        // Restore focus after next DOM update
        if (hadFocus) {
            nextTick(() => {
                searchInputRef.value?.focus();
            });
        }
    }
});

// Initialize URL parameters from current page URL
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

// Watch for external changes that might require refetch
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

                <!-- Error message -->
                <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
                    <p>{{ error }}</p>
                    <Button variant="outline" size="sm" @click="fetchData" class="mt-2">
                        Retry
                    </Button>
                </div>

                <div class="flex items-center justify-between gap-2 py-4">
                    <div class="flex gap-2">
                        <div class="relative">
                            <Input
                                ref="searchInputRef"
                                class="w-[380px] pr-8"
                                placeholder="Search by lib id, card #, first name, or last name ..."
                                v-model="filterInput"
                            />
                            <Button v-if="filterInput" variant="ghost" class="absolute top-0 right-0 h-full px-2" @click="clearFilter">
                                <X class="h-4 w-4" />
                            </Button>
                            <div v-else class="absolute top-0 right-0 h-full px-2 flex items-center justify-center pointer-events-none">
                                <Search class="h-4 w-4 text-foreground" />
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Link :href="route('admins.create')">
                            <Button variant="secondary">
                                <Plus class="w-4 h-4" /> Add Library Staff
                            </Button>
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

            <Dialog v-model:open="isDialogOpen">
                <DialogContent class="sm:max-w-xl grid-rows-[auto_minmax(0,1fr)_auto] p-0 max-h-[90dvh]">
                    <DialogHeader class="p-6 pb-0">
                        <DialogTitle>Library Staff Details</DialogTitle>
                        <DialogDescription>
                            Viewing library staff profile information.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 py-4 px-6 overflow-y-auto">
                        <!-- Profile Image and Barcode -->
                        <div class="flex flex-col items-center md:items-start gap-4">
                            <!-- Profile Image -->
                            <img
                                v-if="selectedUser?.profile_image"
                                :src="'/storage/profile_images/' + selectedUser.profile_image"
                                alt="Profile Image"
                                class="h-32 w-32 rounded-full object-cover border shadow-md"
                            />
                            <div
                                v-else
                                class="h-32 w-32 rounded-full flex items-center justify-center bg-muted text-muted-foreground border shadow-md"
                            >
                                <span class="text-sm">No Image</span>
                            </div>

                            <!-- Barcode Image -->
                            <div v-if="selectedUser?.barcode_path" class="flex flex-col items-center">
                                <img
                                    :src="'/storage/' + selectedUser.barcode_path"
                                    alt="User Barcode"
                                    class="h-16 w-auto border shadow-md"
                                />
                                <span class="text-xs text-muted-foreground mt-2">Barcode: {{ selectedUser.card_number }}</span>
                            </div>
                            <div
                                v-else
                                class="h-16 w-32 flex items-center justify-center bg-muted text-muted-foreground border shadow-md"
                            >
                                <span class="text-xs">No Barcode</span>
                            </div>
                        </div>

                        <!-- User Details -->
                        <div class="md:col-span-2">
                            <div v-if="selectedUser" class="grid gap-2 text-sm">
                                <p><strong>Library ID:</strong> {{ selectedUser.library_id }}</p>
                                <p><strong>Card #:</strong> {{ selectedUser.card_number }}</p>
                                <p><strong>School ID:</strong> {{ selectedUser.school_id }}</p>
                                <p><strong>Name:</strong> {{ selectedUser.first_name }} {{ selectedUser.middle_initial + '.' }} {{ selectedUser.last_name }}</p>
                                <p><strong>Email:</strong> {{ selectedUser.email }}</p>
                                <p><strong>Contact:</strong> {{ selectedUser.contact_number }}</p>
                                <p><strong>Sex:</strong> {{ selectedUser.sex === 'm' ? 'Male' : selectedUser.sex === 'f' ? 'Female' : selectedUser.sex }}</p>
                                <p><strong>User Type:</strong> {{ selectedUser.user_type?.name }}</p>
                            </div>
                            <div v-else class="text-muted-foreground">
                                <p>No library staff selected.</p>
                            </div>
                        </div>
                    </div>

                    <DialogFooter class="p-6 pt-0">
                        <Button @click="isDialogOpen = false">Close</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

        </Layout>
    </AppLayout>
</template>
