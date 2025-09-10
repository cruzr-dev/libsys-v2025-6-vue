<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { ArrowLeft, LoaderCircle, Eye, EyeOff } from 'lucide-vue-next';
import Layout from '@/layouts/users/Layout.vue';
import { onBeforeUnmount, ref, watch } from 'vue';
import { CardDescription } from '@/components/ui/card';

const props = defineProps<{
    nextLibraryId: number;
    nextCardNumber: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: '/users' },
    { title: 'Library Staff', href: '/users/admins' },
    { title: 'Create Library Staff', href: '/users/admins/create' },
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
    password: '',
    password_confirmation: '',
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
    router.visit(route('admins.index'))
}

const submit = () => {
    form.post(route('admins.store'));
};

// 👁 toggles
const showPassword = ref(false);
const showConfirmPassword = ref(false);
</script>

<template>
    <Head title="Create Library Staff" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <Layout>
            <div class="flex h-full flex-1 flex-col gap-6 p-6 bg-white rounded-xl shadow-sm overflow-x-auto relative">

                <h2 class="text-xl text-center font-semibold text-gray-900">Add Library Staff</h2>

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
                            <!-- Library ID -->
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

                            <!-- Card Number -->
                            <div class="grid gap-2">
                                <Label for="card_number" class="text-sm font-medium">
                                    Card Number <span class="text-red-500">*</span>
                                </Label>
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

                            <!-- First Name -->
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

                            <!-- Middle Initial -->
                            <div class="grid gap-2">
                                <Label for="middle_initial" class="text-sm font-medium">Middle Initial</Label>
                                <Input
                                    id="middle_initial"
                                    type="text"
                                    :tabindex="3"
                                    v-model="form.middle_initial"
                                    @input="form.clearErrors('middle_initial')"
                                    placeholder="M"
                                    maxlength="1"
                                    class="h-10"
                                />
                                <InputError :message="form.errors.middle_initial" />
                            </div>

                            <!-- Last Name -->
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

                            <!-- Sex -->
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

                            <!-- Profile Image -->
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
                        <CardDescription class="mt-8 text-xs">
                            Passwords must be at least 8 characters, include uppercase, lowercase, number, and symbol.
                        </CardDescription>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Password -->
                            <div class="grid gap-2">
                                <Label for="password" class="text-sm font-medium">
                                    Password <span class="text-red-500">*</span>
                                </Label>
                                <div class="relative">
                                    <Input
                                        id="password"
                                        :type="showPassword ? 'text' : 'password'"
                                        required
                                        :tabindex="9"
                                        autocomplete="new-password"
                                        v-model="form.password"
                                        @input="form.clearErrors('password')"
                                        placeholder="Password"
                                        class="h-10 pr-10"
                                    />
                                    <button
                                        type="button"
                                        class="absolute inset-y-0 right-2 flex items-center text-gray-500 hover:text-gray-700"
                                        @click="showPassword = !showPassword"
                                        tabindex="-1"
                                    >
                                        <Eye v-if="!showPassword" class="w-5 h-5" />
                                        <EyeOff v-else class="w-5 h-5" />
                                    </button>
                                </div>
                                <InputError :message="form.errors.password" />
                            </div>

                            <!-- Confirm Password -->
                            <div class="grid gap-2">
                                <Label for="password_confirmation" class="text-sm font-medium">
                                    Confirm Password <span class="text-red-500">*</span>
                                </Label>
                                <div class="relative">
                                    <Input
                                        id="password_confirmation"
                                        :type="showConfirmPassword ? 'text' : 'password'"
                                        required
                                        :tabindex="10"
                                        autocomplete="new-password"
                                        v-model="form.password_confirmation"
                                        @input="form.clearErrors('password_confirmation')"
                                        placeholder="Confirm password"
                                        class="h-10 pr-10"
                                    />
                                    <button
                                        type="button"
                                        class="absolute inset-y-0 right-2 flex items-center text-gray-500 hover:text-gray-700"
                                        @click="showConfirmPassword = !showConfirmPassword"
                                        tabindex="-1"
                                    >
                                        <Eye v-if="!showConfirmPassword" class="w-5 h-5" />
                                        <EyeOff v-else class="w-5 h-5" />
                                    </button>
                                </div>
                                <InputError :message="form.errors.password_confirmation" />
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end">
                        <Button
                            type="submit"
                            class="w-full md:w-auto px-8 py-2"
                            :tabindex="11"
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
