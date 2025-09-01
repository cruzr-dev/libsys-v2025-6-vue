<script setup lang="ts">
import { Dialog, DialogContent, DialogTrigger } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';

const getCoverUrl = (path: string) => {
    return path ? `/storage/uploads/book-covers/${path}` : `/storage/placeholders/sample1.png`;
};

defineProps({
    user: Object,
});

</script>

<template>
    <Dialog class="">
        <DialogTrigger as-child>
            <div class="flex gap-4 w-full">
                <div class="items-center flex">
                    <div class="font-medium leading-tight">{{ user?.first_name }}</div>
                </div>
                <div class="record-content w-full">
                    <div class="text-md font-semibold leading-tight truncate w-sm  0">{{ user?.last_name }}</div>
                </div>
            </div>
        </DialogTrigger>
        <DialogContent class="grid gap-6 h-full max-h-9/10 sm:grid-cols-2 sm:max-w-6xl justify-between">
            <div class="flex items-center justify-center">
                image
            </div>

            <div class="space-y-6 overflow-y-auto">
                <h2 class="text-2xl font-bold">{{ user?.first_name }}</h2>
                <div class="flex my-4 gap-2">
                    <p class="text-muted-foreground">{{ user?.email }}</p>

                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                </div>

            </div>
        </DialogContent>
    </Dialog>
</template>
