<script setup lang="ts">
import { Dialog, DialogContent, DialogTrigger } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';

const getCoverUrl = (path: string) => {
    return path ? `/storage/uploads/book-covers/${path}` : `/storage/placeholders/sample1.png`;
};

const props = defineProps({
    record: Object,
});

</script>

<template>
    <Dialog class="">
        <DialogTrigger as-child class="p-2">

                <div class="w-full grid gap-2">
                    <div class="text-md font-semibold leading-tight truncate w-full 0">{{ record?.title }}</div>
                    <div class="flex w-full justify-between">
                        <div class="leading-tight">{{ record?.accession_number }}</div>
                        <div>
                            <span v-if="record.book">book</span>
                            <span v-if="record.thesis">thesis/dissertation</span>
                            <span v-if="record.digital_resource">multimedia</span>
                            <span v-if="record.periodical">periodical/magazine</span>
                        </div>
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
