<script setup lang="ts">
import { Dialog, DialogContent, DialogTrigger } from '@/components/ui/dialog';
import { ref, onMounted, watch } from 'vue';

defineProps<{
    user: Object,
    open?: boolean, // Make open prop optional
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void,
    (e: 'trigger'): void, // Existing event for explicit trigger
    (e: 'close'): void,   // New event for dialog close
}>();

// Progress bar state
const progress = ref(100);

// Animate progress bar
onMounted(() => {
    const duration = 2000; // 2 seconds
    const start = Date.now();
    const interval = setInterval(() => {
        const elapsed = Date.now() - start;
        const newProgress = Math.max(100 - (elapsed / duration) * 100, 0);
        progress.value = newProgress;

        if (newProgress <= 0) {
            clearInterval(interval);
            emit('update:open', false); // Close the dialog when progress reaches 0
            emit('close'); // Emit close event
        }
    }, 16); // ~60fps
});

// Handle dialog open/close to emit update:open event
const onOpenChange = (value: boolean) => {
    emit('update:open', value);
    if (!value) {
        emit('close'); // Emit close event when dialog is closed
    }
};

// Handle click on trigger to emit trigger event
const onTriggerClick = () => {
    emit('trigger');
};

watch(() => open, (newValue) => {
    if (newValue) {
        progress.value = 100; // Reset progress when dialog opens
    }
});
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

        <DialogContent class="h-full max-h-[60%] sm:max-w-xl p-0 overflow-clip">
            <div class="relative">
                <!-- Progress Bar fixed to top -->
                <div class="absolute top-0 left-0 w-full">
                    <div class="w-full bg-gray-200 h-2.5">
                        <div
                            class="bg-primary h-2.5 transition-all duration-200 ease-linear"
                            :style="{ width: `${progress}%` }"
                        ></div>
                    </div>
                </div>

                <!-- Content below progress bar -->
                <div class="p-6 pt-10 space-y-6 overflow-y-auto">
                    <h2 class="text-2xl font-bold">{{ user?.first_name }}</h2>
                    <div class="flex my-4 gap-2">
                        <p class="text-muted-foreground">{{ user?.last_name }}</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- More content here -->
                    </div>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
