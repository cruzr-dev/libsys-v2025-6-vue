<script setup lang="ts">
import { Dialog, DialogContent, DialogTrigger } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';

const getCoverUrl = (path: string) => {
    return path ? `/storage/uploads/book-covers/${path}` : `/storage/placeholders/sample1.png`;
};

defineProps({
    record: Object,
});

</script>

<template>
    <Dialog class="">
        <DialogTrigger as-child class="max-w-full bg-green-50">
            <div class="flex gap-4 ">
                <div class="items-center p-2 flex">
                    <div class="font-medium leading-tight w-8">{{ record?.accession_number }}</div>
                </div>
                <div class="record-content w-full">
                    <div class="text-md font-semibold leading-tight truncate max-w-72  0">{{ record?.title }}</div>
                </div>
            </div>
        </DialogTrigger>
        <DialogContent class="grid gap-6 h-full max-h-9/10 sm:grid-cols-2 sm:max-w-6xl justify-between">
            <div class="flex items-center justify-center">
                image
            </div>

            <div class="space-y-6 overflow-y-auto">
                <h2 class="text-2xl font-bold">{{ record?.title }}</h2>
                <div class="flex my-4 gap-2">
                    <p class="text-muted-foreground">{{ record?.accession_number }}</p>

                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                </div>

            </div>
        </DialogContent>
    </Dialog>
</template>
