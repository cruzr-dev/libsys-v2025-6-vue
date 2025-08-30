<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import Layout from '@/layouts/records/Layout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ArrowUpDown, Search, X, Loader2 } from 'lucide-vue-next';
import { h, ref, onMounted, watch, nextTick } from 'vue';
import {
    FlexRender,
    getCoreRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    useVueTable,
} from '@tanstack/vue-table';

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
const filterInput = ref<string>('');

// Columns (only accession no + title)
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
];

// sort helper
function cycleSort(column) {
    const currentSort = column.getIsSorted();
    if (currentSort === false) column.toggleSorting(false);
    else if (currentSort === 'asc') column.toggleSorting(true);
    else column.clearSorting();
}

// fetch data
const fetchData = async () => {
    isLoading.value = true;
    error.value = null;
    try {
        const response = await fetch(`/api/records?page=${currentPage.value}`);
        const result: ApiResponse = await response.json();
        data.value = result.data;
        currentPage.value = result.current_page;
        lastPage.value = result.last_page;
        total.value = result.total;
    } catch (err) {
        error.value = 'Failed to load data.';
    } finally {
        isLoading.value = false;
    }
};

// table instance
const table = useVueTable({
    get data() { return data.value; },
    columns,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    manualPagination: true,
    pageCount: lastPage.value,
    state: { sorting: sorting.value },
});

// lifecycle
onMounted(() => {
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
                            class="w-[380px] pr-8"
                            placeholder="Search by acc no. or title..."
                            v-model="filterInput"
                        />
                        <Button v-if="filterInput" variant="ghost" class="absolute top-0 right-0 h-full px-2" @click="filterInput = ''">
                            <X class="h-4 w-4" />
                        </Button>
                        <div v-else class="absolute top-0 right-0 h-full px-2 flex items-center justify-center pointer-events-none">
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
            </div>
        </Layout>
    </AppLayout>
</template>
