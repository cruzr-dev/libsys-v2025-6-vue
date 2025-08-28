<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { ArrowLeft, LoaderCircle } from 'lucide-vue-next';
import Layout from '@/layouts/users/Layout.vue';
import { onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps<{
    nextLibraryId: number;
    nextCardNumber: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: '/users' },
    { title: 'Staff', href: '/users/staff' },
    { title: 'Create Staff', href: '/users/staff/create' },
];

const form = useForm({
    library_id: props.nextLibraryId.toString(),
    card_number: props.nextCardNumber.toString(),
    first_name: '',
    middle_initial: '',
    last_name: '',
    sex: '',
    contact_number: '',
    email: '',
    profile_image: null,
    office: '',
});

// --- Profile Image Preview ---
const previewUrl = ref<string | null>(null);

watch(() => form.profile_image, (newFile) => {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = null;
    }
    if (newFile instanceof File) {
        previewUrl.value = URL.createObjectURL(newFile);
    }
});

// cleanup object URL on unmount
onBeforeUnmount(() => {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }
});

const goBack = () => {
    window.history.back();
};

const submit = () => {
    form.post(route('staff.store'));
};
</script>

<template>
    <Head title="Create Staff" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <Layout>
            <div class="flex h-full flex-1 flex-col gap-6 p-6 bg-white rounded-xl shadow-sm overflow-x-auto relative">

                <div class="absolute right-4 top-4">
                    <Button variant="outline" @click="goBack">
                        <ArrowLeft class="w-4 h-4" /> Back
                    </Button>
                </div>

                <form @submit.prevent="submit" class="flex flex-col gap-8 max-w-4xl mx-auto">
                    <!-- Personal Information Section -->
                    <div class="space-y-6">
                        <h2 class="text-lg font-semibold text-gray-900">Personal Information</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div class="grid gap-2">
                                <Label for="library_id" class="text-sm font-medium">
                                    Library ID <span class="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="library_id"
                                    type="number"
                                    required
                                    :tabindex="1"
                                    v-model="form.library_id"
                                    @input="form.clearErrors('library_id')"
                                    placeholder="Library ID"
                                    class="h-10"
                                />
                                <InputError :message="form.errors.library_id" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="card_number" class="text-sm font-medium"> Card Number <span class="text-red-500">*</span> </Label>
                                <Input
                                    id="card_number"
                                    type="number"
                                    required
                                    :tabindex="8"
                                    v-model="form.card_number"
                                    @input="form.clearErrors('card_number')"
                                    placeholder="e.g., 202512345"
                                    class="h-10"
                                />
                                <InputError :message="form.errors.card_number" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="first_name" class="text-sm font-medium">
                                    First Name <span class="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="first_name"
                                    type="text"
                                    required
                                    autofocus
                                    :tabindex="2"
                                    autocomplete="given-name"
                                    v-model="form.first_name"
                                    @input="form.clearErrors('first_name')"
                                    placeholder="First name"
                                    class="h-10"
                                />
                                <InputError :message="form.errors.first_name" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="middle_initial" class="text-sm font-medium">Middle Initial</Label>
                                <Input
                                    id="middle_initial"
                                    type="text"
                                    :tabindex="3"
                                    v-model="form.middle_initial"
                                    @input="form.clearErrors('middle_initial')"
                                    placeholder="Middle Initial"
                                    maxlength="1"
                                    class="h-10"
                                />
                                <InputError :message="form.errors.middle_initial" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="last_name" class="text-sm font-medium">
                                    Last Name <span class="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="last_name"
                                    type="text"
                                    required
                                    :tabindex="4"
                                    autocomplete="family-name"
                                    v-model="form.last_name"
                                    @input="form.clearErrors('last_name')"
                                    placeholder="Last name"
                                    class="h-10"
                                />
                                <InputError :message="form.errors.last_name" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="sex" class="text-sm font-medium">
                                    Sex <span class="text-red-500">*</span>
                                </Label>
                                <Select
                                    v-model="form.sex"
                                    @update:model-value="form.clearErrors('sex')"
                                    required
                                >
                                    <SelectTrigger id="sex" :tabindex="5" class="h-10">
                                        <SelectValue placeholder="Select sex" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="m">Male</SelectItem>
                                        <SelectItem value="f">Female</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.sex" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="profile_image" class="text-sm font-medium">
                                    Profile Image
                                </Label>
                                <Input
                                    id="profile_image"
                                    type="file"
                                    accept="image/*"
                                    :tabindex="6"
                                    class="h-10"
                                    @change="form.profile_image = $event.target.files[0]; form.clearErrors('profile_image')"
                                />
                                <div v-if="previewUrl" class="mt-2">
                                    <img :src="previewUrl" alt="Preview" class="h-24 w-24 rounded-full object-cover shadow" />
                                </div>
                                <InputError :message="form.errors.profile_image" />
                            </div>

                        </div>
                    </div>

                    <!-- Contact Information Section -->
                    <div class="space-y-6">
                        <h2 class="text-lg font-semibold text-gray-900">Contact Information</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                            <div class="grid gap-2">
                                <Label for="email" class="text-sm font-medium">
                                    Email Address <span class="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    :tabindex="7"
                                    autocomplete="email"
                                    v-model="form.email"
                                    @input="form.clearErrors('email')"
                                    placeholder="email@example.com"
                                    class="h-10"
                                />
                                <InputError :message="form.errors.email" />
                            </div>
                        </div>
                    </div>

                    <!-- Account Information Section -->
                    <div class="space-y-6">
                        <h2 class="text-lg font-semibold text-gray-900">Account Information</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="grid gap-2">
                                <Label for="office" class="text-sm font-medium">
                                    Office <span class="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="office"
                                    v-model="form.office"
                                    type="text"
                                    placeholder="Enter office name"
                                    required
                                    class="h-10 max-w-80"
                                    :tabindex="8"
                                    @input="form.clearErrors('office')"
                                />
                                <InputError :message="form.errors.office" />
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end">
                        <Button
                            type="submit"
                            class="w-full md:w-auto px-8 py-2"
                            :tabindex="10"
                            :disabled="form.processing"
                        >
                            <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
                            Create Account
                        </Button>
                    </div>
                </form>
            </div>
        </Layout>
    </AppLayout>
</template>
