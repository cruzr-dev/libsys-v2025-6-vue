<script setup lang="ts">
import { Dialog, DialogContent, DialogTrigger } from '@/components/ui/dialog';
import { ref, onMounted, watch } from 'vue';
import { CircleUser } from 'lucide-vue-next';

const props = defineProps<{
    user: {
        id: string | number,
        first_name: string,
        last_name: string,
        library_id: string,
        transaction_type: 'login' | 'logout'
    },
    open?: boolean,
    isNumericSearch?: boolean, // Add isNumericSearch prop
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void,
    (e: 'trigger'): void,
    (e: 'close'): void,
}>();

// Progress bar state
const progress = ref(100);

// API call function
const logPatronTransaction = async (userId: string | number, transactionType: string) => {
    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
            getCsrfTokenFromCookie();

        const response = await fetch('/api/logger/patron/transaction', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token,
            },
            credentials: 'same-origin',
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

// Helper function to get CSRF token from cookie
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
    progress.value = 100;
    const duration = 2000;
    const start = Date.now();

    const interval = setInterval(() => {
        const elapsed = Date.now() - start;
        const newProgress = Math.max(100 - (elapsed / duration) * 100, 0);
        progress.value = newProgress;

        if (newProgress <= 0) {
            clearInterval(interval);
            if (props.user?.id && props.user?.transaction_type) {
                logPatronTransaction(props.user.id, props.user.transaction_type);
            }
            setTimeout(() => {
                emit('update:open', false);
                emit('close');
            }, 500);
        }
    }, 16);
};

// Start animation when component mounts and dialog is open
onMounted(() => {
    if (props.open) {
        startProgressAnimation();
    }
});

// Handle dialog open/close
const onOpenChange = (value: boolean) => {
    emit('update:open', value);
    if (!value) {
        emit('close');
    }
};

// Handle click on trigger
const onTriggerClick = () => {
    emit('trigger');
};

// Watch for dialog opening
watch(() => props.open, (newValue) => {
    if (newValue) {
        startProgressAnimation();
    }
});

// Utility function to mask names
const maskName = (name: string): string => {
    if (!name) return '';
    if (name.length <= 2) return name;

    // Always keep first and last character, alternate masking in the middle starting with mask
    return name
        .split('')
        .map((char, index) => {
            // Keep first and last character
            if (index === 0 || index === name.length - 1) return char;
            // In the middle section, start masking from position 1, then alternate
            const middleIndex = index - 1; // Adjust index for middle section
            return (middleIndex % 2 === 0) ? '*' : char;
        })
        .join('');
};

</script>

<template>
    <Dialog :open="open" @update:open="onOpenChange">
        <!-- Conditionally render DialogTrigger based on isNumericSearch -->
        <DialogTrigger v-if="!isNumericSearch" as-child>
            <div
                class="flex w-full items-center gap-3 p-2 rounded-lg hover:bg-muted/50 transition"
                @click="onTriggerClick"
            >
                <div class="text-sm font-medium text-muted-foreground">
                    {{ user?.first_name }}
                </div>
                <div class="flex-1">
                    <div class="text-base font-semibold truncate">
                        {{ user?.last_name }}
                    </div>
                </div>
                <div class="text-xs font-mono text-muted-foreground">
                    {{ user?.library_id }}
                </div>
            </div>
        </DialogTrigger>

        <DialogContent class="h-full max-h-[60%] sm:max-w-xl p-0 overflow-clip">
            <div class="relative flex flex-col min-h-[400px] w-full max-w-2xl mx-auto">
                <!-- Progress Bar -->
                <div class="absolute top-0 left-0 w-full">
                    <div class="w-full bg-gray-200 h-2.5">
                        <div
                            class="bg-primary h-2.5 transition-all duration-200 ease-linear"
                            :style="{ width: `${progress}%` }"
                        ></div>
                    </div>
                </div>

                <!-- Content Container -->
                <div class="flex flex-col items-center justify-center flex-grow p-6 pt-12 space-y-8">
                    <!-- Status Message -->
                    <div v-if="user?.transaction_type === 'login'" class="text-2xl font-semibold text-secondary text-center">
                        You are entering the library
                    </div>
                    <div v-else-if="user?.transaction_type === 'logout'" class="text-2xl font-semibold text-destructive text-center">
                        You are now leaving the library
                    </div>

                    <!-- User Info Section -->
                    <div class="flex flex-col items-center text-center space-y-4">
                        <!-- User Icon -->
                        <div class="flex justify-center">
                            <CircleUser class="w-24 h-24 text-muted-foreground" />
                        </div>

                        <!-- User Name -->
                        <h2 class="text-2xl font-bold">{{ maskName(user?.first_name) }}</h2>

                        <!-- Last Name -->
                        <p class="text-xl text-muted-foreground">{{ maskName(user?.last_name) }}</p>
                    </div>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
