<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import Layout from '@/layouts/users/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { LoaderCircle, ArrowLeft } from 'lucide-vue-next';
import { ref, onBeforeUnmount } from 'vue';
import DeleteDialog from '@/components/DeleteDialog.vue';

// Updated interface to match your Laravel controller structure
interface College {
    id: number;
    code: string;
    name: string;
    courses: Course[];
}

interface Course {
    id: number;
    college_id: number;
    code: string;
    name: string;
}

// Define the props passed from the controller
const props = defineProps<{
    admin: {
        id: number;
        library_id: string;
        first_name: string;
        middle_initial: string | null;
        last_name: string;
        sex: string;
        contact_number: string | null;
        email: string;
        student_type: string;
        card_number: string;
        profile_image: string | null;
    };
    colleges: College[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Patrons', href: '/users' },
    { title: 'Faculties', href: '/users/admins' },
    { title: 'Edit Library Staff', href: `/users/students/${props.admin.id}/edit` },
];

// Initialize the form with admin data - DON'T include profile_image in the initial form data
const form = useForm({
    library_id: props.admin.library_id,
    first_name: props.admin.first_name,
    middle_initial: props.admin.middle_initial || '',
    last_name: props.admin.last_name,
    sex: props.admin.sex,
    email: props.admin.email,
    profile_image: props.admin.profile_image,
    student_type: props.admin.student_type,
    card_number: props.admin.card_number,
    password: '',
    password_confirmation: '',
});

// Separate ref for handling the profile image file
const profileImageFile = ref<File | null>(null);

// --- Profile Image Preview ---
const previewUrl = ref<string | null>(null);

// Initialize preview URL with existing profile image
if (props.admin.profile_image) {
    previewUrl.value = `/storage/profile_images/${props.admin.profile_image}`;
}

// Handle file input change
const handleProfileImageChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] || null;

    profileImageFile.value = file;
    form.clearErrors('profile_image');

    // Update preview
    if (previewUrl.value && previewUrl.value.startsWith('blob:')) {
        URL.revokeObjectURL(previewUrl.value);
    }

    if (file) {
        previewUrl.value = URL.createObjectURL(file);
    } else if (props.admin.profile_image) {
        // Reset to original image if file input is cleared
        previewUrl.value = `/storage/profile_images/${props.admin.profile_image}`;
    } else {
        previewUrl.value = null;
    }
};

// cleanup object URL on unmount (only if it's a blob URL)
onBeforeUnmount(() => {
    if (previewUrl.value && previewUrl.value.startsWith('blob:')) {
        URL.revokeObjectURL(previewUrl.value);
    }
});

// Show handler function
const isDialogOpen = ref(false);

const handleDelete = () => {
    isDialogOpen.value = true;
};

const deleteStudent = (id: number | null) => {
    if (!id) return
    // send a delete request via Inertia or Axios
    router.delete(route('admins.destroy', id))
}

// Handle form submission
const submit = () => {
    // Create FormData to handle file upload
    const formData = new FormData();

    // Add all form fields
    Object.keys(form.data()).forEach(key => {
        const value = form.data()[key];
        if (value !== null && value !== undefined && value !== '') {
            formData.append(key, value);
        }
    });

    // Add profile image if selected
    if (profileImageFile.value) {
        formData.append('profile_image', profileImageFile.value);
    }

    // Add _method field for PATCH request
    formData.append('_method', 'PATCH');

    // Send the FormData using Inertia's router
    router.post(route('admins.update', props.admin.id), formData, {
        headers: {
            'Content-Type': 'multipart/form-data',
        },
        onBefore: () => {
            form.processing = true; // Set processing state manually
        },
        onFinish: () => {
            form.processing = false; // Reset processing state
        },
        onError: (errors) => {
            form.errors = errors; // Set form errors if any
        },
    });
};

const goBack = () => {
    window.history.back();
};
</script>

<template>
    <Head title="Edit Library Staff" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <Layout>
            <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl bg-white p-6 shadow-sm relative">

                <h2 class="text-xl text-center font-semibold text-gray-900">Edit Library Staff Details</h2>

                <!-- Back Button -->
                <div class="absolute right-4 top-4">
                    <Button variant="outline" @click="goBack">
                        <ArrowLeft class="w-4 h-4" /> Back
                    </Button>
                </div>

                <form @submit.prevent="submit" enctype="multipart/form-data" class="mx-auto flex max-w-4xl flex-col gap-8">
                    <!-- Personal Information Section -->
                    <div class="space-y-6">
                        <h2 class="text-lg font-semibold text-gray-900">Personal Information</h2>
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                            <div class="grid gap-2">
                                <Label for="library_id" class="text-sm font-medium"> Library ID <span class="text-red-500">*</span> </Label>
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
                                <Label for="first_name" class="text-sm font-medium"> First Name <span class="text-red-500">*</span> </Label>
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
                                    placeholder="MI"
                                    maxlength="1"
                                    class="h-10"
                                />
                                <InputError :message="form.errors.middle_initial" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="last_name" class="text-sm font-medium"> Last Name <span class="text-red-500">*</span> </Label>
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
                                <Label for="sex" class="text-sm font-medium"> Sex <span class="text-red-500">*</span> </Label>
                                <Select v-model="form.sex" @update:model-value="form.clearErrors('sex')" required>
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
                                    @change="handleProfileImageChange"
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
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                            <div class="grid gap-2">
                                <Label for="email" class="text-sm font-medium"> Email Address <span class="text-red-500">*</span> </Label>
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
                        <p class="mt-2 text-xs text-gray-500">
                            Passwords must be at least 8 characters, include uppercase, lowercase, number, and symbol.
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Password -->
                            <div class="grid gap-2">
                                <Label for="password" class="text-sm font-medium">
                                    Password <span class="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="password"
                                    type="password"
                                    :tabindex="9"
                                    autocomplete="new-password"
                                    v-model="form.password"
                                    @input="form.clearErrors('password')"
                                    placeholder="Password"
                                    class="h-10"
                                />
                                <InputError :message="form.errors.password" />
                            </div>

                            <!-- Confirm Password -->
                            <div class="grid gap-2">
                                <Label for="password_confirmation" class="text-sm font-medium">
                                    Confirm Password <span class="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="password_confirmation"
                                    type="password"
                                    :tabindex="10"
                                    autocomplete="new-password"
                                    v-model="form.password_confirmation"
                                    @input="form.clearErrors('password_confirmation')"
                                    placeholder="Confirm password"
                                    class="h-10"
                                />
                                <InputError :message="form.errors.password_confirmation" />
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-between pt-4">
                        <Button variant="link" @click="handleDelete()" type="button">Delete Account</Button>
                        <Button type="submit" class="w-full px-8 py-2 md:w-auto" :tabindex="13" :disabled="form.processing">
                            <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                            Update Student Account
                        </Button>
                    </div>
                </form>
            </div>

            <DeleteDialog
                v-model:open="isDialogOpen"
                :user-id="admin.id"
                @confirm-delete="deleteStudent"
            />

        </Layout>
    </AppLayout>
</template>
