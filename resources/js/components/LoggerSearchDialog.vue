<script setup lang="ts">
import { Dialog, DialogContent, DialogTrigger } from '@/components/ui/dialog';
import { ref, onMounted, watch } from 'vue';

const props = defineProps<{
    user: {
        id: string | number,
        first_name: string,
        last_name: string,
        library_id: string,
        transaction_type: 'login' | 'logout'
    },
    open?: boolean, // Make open prop optional
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void,
    (e: 'trigger'): void, // Existing event for explicit trigger
    (e: 'close'): void,   // New event for dialog close
}>();

// Progress bar state
const progress = ref(100);

// API call function
const logPatronTransaction = async (userId: string | number, transactionType: string) => {
    try {

        // Get CSRF token from meta tag or cookie
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
            getCsrfTokenFromCookie();

        const response = await fetch('/api/logger/patron/transaction', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token, // Add CSRF token here
            },
            credentials: 'same-origin', // Include cookies for session
            body: JSON.stringify({
                user_id: userId,
                transaction_type: transactionType
            })
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        console.log(data);

    } catch (error) {
        console.error('Failed to log patron transaction:', error);
    }
};

// Helper function to get CSRF token from cookie (if using cookie-based CSRF)
const getCsrfTokenFromCookie = () => {
    const name = 'XSRF-TOKEN';
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) {
        return decodeURIComponent(parts.pop()?.split(';').shift() || '');
    }
    return null;
};

// Animate progress bar
const startProgressAnimation = () => {
    progress.value = 100; // Reset progress

    const duration = 3000; // 3 seconds
    const start = Date.now();

    const interval = setInterval(() => {
        const elapsed = Date.now() - start;
        const newProgress = Math.max(100 - (elapsed / duration) * 100, 0);
        progress.value = newProgress;

        if (newProgress <= 0) {
            clearInterval(interval);

            // Make API call after progress bar completes
            if (props.user?.id && props.user?.transaction_type) {
                logPatronTransaction(props.user.id, props.user.transaction_type);
            }

            // Close the dialog after a short delay to allow API call
            setTimeout(() => {
                emit('update:open', false);
                emit('close');
            }, 500);
        }
    }, 16); // ~60fps
};

// Start animation when component mounts and dialog is open
onMounted(() => {
    if (props.open) {
        startProgressAnimation();
    }
});

// Handle dialog open/close to emit update:open event
const onOpenChange = (value: boolean) => {
     ('update:open', value);
    if (!value) {
        emit('close'); // Emit close event when dialog is closed
    }
};

// Handle click on trigger to emit trigger event
const onTriggerClick = () => {
    emit('trigger');
};

// Watch for dialog opening to start progress animation
watch(() => props.open, (newValue) => {
    if (newValue) {
        startProgressAnimation();
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

                <div class="p-6 pt-10 space-y-6 overflow-y-auto">

                    <!-- Conditional message based on transaction_type -->
                    <div v-if="user?.transaction_type === 'login'" class="text-lg font-semibold text-secondary">
                        You are entering the library
                    </div>
                    <div v-else-if="user?.transaction_type === 'logout'" class="text-lg font-semibold text-destructive">
                        You are now leaving the library
                    </div>

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
