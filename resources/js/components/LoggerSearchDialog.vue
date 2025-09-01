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
            <div class="flex w-full items-center gap-3 p-2 rounded-lg hover:bg-muted/50 transition">
                <!-- First Name -->
                <div class="text-sm font-medium text-muted-foreground">
                    {{ user?.first_name }}
                </div>

                <!-- Last Name -->
                <div class="flex-1">
                    <div class="text-base font-semibold truncate">
                        {{ user?.last_name }}
                    </div>
                </div>

                <!-- Library Number -->
                <div class="text-xs font-mono text-muted-foreground">
                    {{ user?.library_id }}
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
