<script setup lang="ts">
    import { Head } from '@inertiajs/vue3';
    import AppLayout from '@/layouts/AppLayout.vue';
    import type { BreadcrumbItem } from '@/types';
    import BookScannerDialog from '@/components/BookScannerDialog.vue';
    import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
    import BookSearchComboBox from '@/components/BookSearchComboBox.vue';

    const breadcrumbs: BreadcrumbItem[] = [
      {
          title: 'Borrowings',
          href: '/borrowings',
      },
      {
          title: 'Borrow',
          href: '/borrowings/create',
      },
    ];

    // Get the props
    const props = defineProps({
        searchAcResult: {
            type: [Object, null],
            default: null,
        },
    });

    // Now you can access it
    console.log(props.searchAcResult);

</script>

<template>
    <Head title="Borrow/Return Books" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="flex gap-4">
                <Card class="w-lg flex-1">
                    <CardHeader>
                        <CardTitle>Search Book</CardTitle>
                        <CardDescription>Find the book to borrow.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex flex-col gap-4 w-full">
                            <BookScannerDialog />
                            <BookSearchComboBox />
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
