<script setup lang="ts">
import { Dialog, DialogContent, DialogTrigger } from '@/components/ui/dialog';

defineProps<{
    user: Object,
    open?: boolean, // Make open prop optional
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void,
    (e: 'trigger'): void, // New event for explicit trigger
}>();

// Handle dialog open/close to emit update:open event
const onOpenChange = (value: boolean) => {
    emit('update:open', value);
};

// Handle click on trigger to emit trigger event
const onTriggerClick = () => {
    emit('trigger');
};
</script>

<template>
    <Dialog :open="open" @update:open="onOpenChange">
        <DialogTrigger as-child>
            <div
                class="flex w-full items-center gap-3 p-2 rounded-lg hover:bg-muted/50 transition"
                @click="onTriggerClick"
            >
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

        <DialogContent class="grid gap-6 h-full max-h-[90%] sm:grid-cols-2 sm:max-w-6xl justify-between">
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
