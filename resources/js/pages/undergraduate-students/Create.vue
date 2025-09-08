<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import Layout from '@/layouts/users/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, ArrowLeft } from 'lucide-vue-next';
import { computed, watch, ref, onBeforeUnmount } from 'vue';

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
    majors: Major[];
}

interface Major {
    id: number;
    course_id: number;
    name: string;
}

// Define the props passed from the controller
const props = defineProps<{
    colleges: College[];
    nextLibraryId: number;
    nextCardNumber: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: '/users' },
    { title: 'Students', href: '/users/undergraduate' },
    { title: 'Add GraduateStudent', href: '/users/undergraduate/create' },
];

// Initialize the form with all necessary fields (pre-filled with incremented values)
const form = useForm({
    library_id: props.nextLibraryId.toString(), // Pre-filled with incremented value
    first_name: '',
    middle_initial: '',
    last_name: '',
    sex: '',
    contact_number: '',
    email: '',
    student_type: '',
    card_number: props.nextCardNumber.toString(), // Pre-filled with incremented value
    college_id: null,
    course_id: null,
    major_id: null,
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

// Computed property to get majors based on selected course
const availableMajors = computed(() => {
    if (!form.course_id) return [];

    const selectedCourse = availableCourses.value.find(course => course.id === form.course_id);
    return selectedCourse?.majors || [];
});

// Watch for college changes to reset dependent fields
watch(() => form.college_id, (newCollegeId) => {
    if (newCollegeId !== null) {
        // Reset course and major when college changes
        form.course_id = null;
        form.major_id = null;
        form.clearErrors('course_id');
        form.clearErrors('major_id');
    }
});

// Watch for course changes to reset major field
watch(() => form.course_id, (newCourseId) => {
    if (newCourseId !== null) {
        // Reset major when course changes
        form.major_id = null;
        form.clearErrors('major_id');
    }
});

// Handle form submission
const submit = () => {
    form.post(route('undergraduate.store'));
};

const goBack = () => {
    window.history.back();
};
</script>

<template>
    <Head title="Add Student" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <Layout>
            <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl bg-white p-6 shadow-sm relative">

                <!-- Back Button -->
                <div class="absolute right-4 top-4">
                    <Button variant="outline" @click="goBack">
                        <ArrowLeft class="w-4 h-4" /> Back
                    </Button>
                </div>

                <form @submit.prevent="submit" class="mx-auto flex max-w-4xl flex-col gap-8">
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
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="contact_number" class="text-sm font-medium">Contact Number</Label>
                                <Input
                                    id="contact_number"
                                    type="text"
                                    :tabindex="6"
                                    v-model="form.contact_number"
                                    @input="form.clearErrors('contact_number')"
                                    placeholder="10 Digit Contact Number"
                                    class="h-10"
                                />
                                <InputError :message="form.errors.contact_number" />
                            </div>

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

                    <!-- Academic Information Section -->
                    <div class="space-y-6">
                        <h2 class="text-lg font-semibold text-gray-900">Academic Information</h2>
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                            <!-- College Select Input -->
                            <div class="grid gap-2">
                                <Label for="college_id" class="text-sm font-medium"> College <span class="text-red-500">*</span> </Label>
                                <Select v-model="form.college_id" @update:model-value="form.clearErrors('college_id')" required>
                                    <SelectTrigger id="college_id" :tabindex="9" class="h-10">
                                        <SelectValue placeholder="Select college" class="max-w-80 truncate"/>
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="college in colleges" :key="college.id" :value="college.id">
                                            {{ college.code }} - {{ college.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.college_id" />
                            </div>

                            <!-- Course Select Input (Dynamic based on college) -->
                            <div class="grid gap-2">
                                <Label for="course_id" class="text-sm font-medium"> Course <span class="text-red-500">*</span> </Label>
                                <Select
                                    v-model="form.course_id"
                                    @update:model-value="form.clearErrors('course_id')"
                                    required
                                    :disabled="!form.college_id || availableCourses.length === 0"
                                >
                                    <SelectTrigger id="course_id" :tabindex="10" class="h-10">
                                        <SelectValue
                                            class="max-w-sm truncate"
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
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                            <!-- Major Select Input (Dynamic based on course) -->
                            <div class="grid gap-2">
                                <Label for="major_id" class="text-sm font-medium">
                                    Major
                                    <span v-if="availableMajors.length > 0" class="text-red-500">*</span>
                                </Label>
                                <Select
                                    v-model="form.major_id"
                                    @update:model-value="form.clearErrors('major_id')"
                                    :required="availableMajors.length > 0"
                                    :disabled="!form.course_id || availableMajors.length === 0"
                                >
                                    <SelectTrigger id="major_id" :tabindex="11" class="h-10">
                                        <SelectValue
                                            class="max-w-sm truncate"
                                            :placeholder="!form.course_id ? 'Select course first' : availableMajors.length === 0 ? 'No majors available' : 'Select major'"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="major in availableMajors" :key="major.id" :value="major.id">
                                            {{ major.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.major_id" />
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end pt-4">
                        <Button type="submit" class="w-full px-8 py-2 md:w-auto" :tabindex="13" :disabled="form.processing">
                            <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                            Add Student Account
                        </Button>
                    </div>
                </form>
            </div>
        </Layout>
    </AppLayout>
</template>
