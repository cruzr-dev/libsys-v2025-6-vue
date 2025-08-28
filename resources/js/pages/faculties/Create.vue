<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { LoaderCircle } from 'lucide-vue-next';
import Layout from '@/layouts/users/Layout.vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

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

const props = defineProps<{
    colleges: College[];
    nextLibraryId: number;
    nextCardNumber: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: '/users' },
    { title: 'Faculties', href: '/users/faculties' },
    { title: 'Create Faculties', href: '/users/faculties/create' },
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
    college_id: null,
    course_id: null,
    profile_image: null,
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

// Computed property to get courses based on selected college
const availableCourses = computed(() => {
    if (!form.college_id) return [];

    const selectedCollege = props.colleges.find(college => college.id === form.college_id);
    return selectedCollege?.courses || [];
});

const submit = () => {
    form.post(route('faculties.store'));
};
</script>

<template>
    <Head title="Create Faculty" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <Layout>
            <div class="flex h-full flex-1 flex-col gap-6 p-6 bg-white rounded-xl shadow-sm overflow-x-auto">
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
                                <Label for="college_id" class="text-sm font-medium">
                                    College <span class="text-red-500">*</span>
                                </Label>
                                <Select
                                    v-model="form.college_id"
                                    @update:model-value="form.clearErrors('college_id')"
                                    required
                                >
                                    <SelectTrigger id="college_id" :tabindex="8" class="h-10">
                                        <SelectValue placeholder="Select a college" class="max-w-80 truncate" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="college in colleges"
                                            :key="college.id"
                                            :value="college.id"
                                        >
                                            {{ college.code }} - {{ college.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.college_id" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="course_id" class="text-sm font-medium"> Department <span class="text-red-500">*</span> </Label>
                                <Select
                                    v-model="form.course_id"
                                    @update:model-value="form.clearErrors('course_id')"
                                    required
                                    :disabled="!form.college_id || availableCourses.length === 0"
                                >
                                    <SelectTrigger id="course_id" :tabindex="10" class="h-10">
                                        <SelectValue
                                            class="max-w-80 truncate"
                                            :placeholder="!form.college_id ? 'Select college first' : availableCourses.length === 0 ? 'No courses available' : 'Select course'"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="course in availableCourses" :key="course.id" :value="course.id">
                                            {{ course.code }} - {{ course.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.course_id" />
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
