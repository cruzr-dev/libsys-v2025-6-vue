<script setup lang="ts">

import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { UserRoundSearch } from 'lucide-vue-next';
import { router, useForm } from '@inertiajs/vue3';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue
} from '@/components/ui/select';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader, DialogTitle,
    DialogTrigger
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { ref, watch } from 'vue';

const props = defineProps({
    patron: Object,
    purposes: Object,
    search_button: Boolean,
    is_logout: Boolean,
});

const form = useForm({
    search: '',
    patron_id: null,
    purpose_id: null,
});

const errorMessage = ref('')

const search = () => {
    if (form.search.length !== 5) {
        errorMessage.value = 'Library ID must be exactly 5 characters.'
        return
    }

    errorMessage.value = ''
    router.get(route('logger.create'), { search: form.search, search_button: true }, { preserveState: true });
};

const clearSearch = () => {
    form.search = '';
    errorMessage.value = ''
};

const open = ref(false)
const selectedPurpose = ref('')

// Watch for patron changes and open dialog
watch(() => props.patron, (newPatron) => {
    if (newPatron) {
        form.patron_id = newPatron.id
        open.value = true
    }
}, { immediate: true })

const handlePurposeChange = (value: string) => {
    selectedPurpose.value = value
    form.purpose_id = value
}

const submitForm = () => {
    form.post(route('logger.store'), {
        onSuccess: () => {
            open.value = false
            // Reset form or handle success
            form.reset()
            selectedPurpose.value = ''
        },
        onError: (errors) => {
            console.error('Form submission errors:', errors)
        }
    })
}

</script>

<template>
    <div class="relative w-full max-w-sm items-center dark:text-card-foreground">
        <form @submit.prevent="search">
            <Input v-model="form.search" id="search" type="number"
                   placeholder="Enter Library ID" class="pl-10" autocomplete="off"/>
        </form>
        <span class="absolute start-0 inset-y-0 flex items-center justify-center px-2">
          <UserRoundSearch class="size-6 text-muted-foreground" />
        </span>
        <p v-if="errorMessage" class="text-red-500 text-sm mt-1">{{ errorMessage }}</p>
    </div>
    <div class="flex gap-2 dark:text-card-foreground">
        <Button variant="outline" v-if="form.search" @click="clearSearch">Clear</Button>
        <Button @click="search">Search</Button>
    </div>

    <div v-if="patron" class="max-w-md">

        <Dialog v-model:open="open">
            <DialogTrigger as-child class="hidden">
                <Button
                    ref="triggerRef"
                    variant="outline"
                >
                    Edit Profile
                </Button>
            </DialogTrigger>
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Welcome back {{ patron.first_name }}!</DialogTitle>
                    <DialogDescription v-if="!is_logout">
                        What transaction would you do today?
                    </DialogDescription>
                    <div v-if="is_logout">Would you like to log-out now?</div>
                </DialogHeader>
                <form @submit.prevent="submitForm">
                    <div v-if="!is_logout" class="grid gap-4 py-4">
                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label for="purpose" class="text-right">
                                Purpose
                            </Label>

                            <Select v-model="selectedPurpose" @update:modelValue="handlePurposeChange">
                                <SelectTrigger class="w-[280px]">
                                    <SelectValue placeholder="Select a purpose" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectLabel>Purpose</SelectLabel>
                                        <SelectItem
                                            v-for="purpose in purposes"
                                            :key="purpose.id"
                                            :value="purpose.id.toString()"
                                        >
                                            {{ purpose.name }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Show validation errors if any -->
                        <div v-if="form.errors.purpose_id" class="text-red-500 text-sm">
                            {{ form.errors.purpose_id }}
                        </div>
                    </div>
                    <DialogFooter>
                        <Button
                            v-if="!is_logout"
                            type="submit"
                            :disabled="form.processing || !selectedPurpose"
                        >
                            <span v-if="form.processing">Processing...</span>
                            <span v-else>Save changes</span>
                        </Button>
                        <Button
                            v-else
                            type="submit"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing">Processing...</span>
                            <span v-else>Logout</span>
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

    </div>
    <div v-else-if="!patron && search_button" class="dark:text-card-foreground">
        No records found
    </div>
</template>

<style scoped>

</style>
