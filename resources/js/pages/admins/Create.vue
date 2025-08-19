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

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: '/users',
    },
    {
        title: 'Staff admins',
        href: '/users/admins',
    },
    {
        title: 'Create Admins',
        href: '/users/admins/create',
    },
];

const form = useForm({
    library_id: '',
    first_name: '',
    middle_initial: '',
    last_name: '',
    sex: '',
    contact_number: '',
    role_title: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('admins.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

</script>

<template>
    <Head title="Create Admins" />

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
                                <Input id="library_id" type="number" required :tabindex="1" v-model="form.library_id" placeholder="Library ID" class="h-10" />
                                <InputError :message="form.errors.library_id" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="first_name" class="text-sm font-medium">
                                    First Name <span class="text-red-500">*</span>
                                </Label>
                                <Input id="first_name" type="text" required autofocus :tabindex="2" autocomplete="given-name" v-model="form.first_name" placeholder="First name" class="h-10" />
                                <InputError :message="form.errors.first_name" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="middle_initial" class="text-sm font-medium">Middle Initial</Label>
                                <Input id="middle_initial" type="text" :tabindex="3" v-model="form.middle_initial" placeholder="Middle Initial" maxlength="1" class="h-10" />
                                <InputError :message="form.errors.middle_initial" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="last_name" class="text-sm font-medium">
                                    Last Name <span class="text-red-500">*</span>
                                </Label>
                                <Input id="last_name" type="text" required :tabindex="4" autocomplete="family-name" v-model="form.last_name" placeholder="Last name" class="h-10" />
                                <InputError :message="form.errors.last_name" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="sex" class="text-sm font-medium">
                                    Sex <span class="text-red-500">*</span>
                                </Label>
                                <Select v-model="form.sex" required>
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
                        </div>
                    </div>

                    <!-- Contact Information Section -->
                    <div class="space-y-6">
                        <h2 class="text-lg font-semibold text-gray-900">Contact Information</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="grid gap-2">
                                <Label for="contact_number" class="text-sm font-medium">Contact Number</Label>
                                <Input id="contact_number" type="text" :tabindex="6" v-model="form.contact_number"
                                       placeholder="ex. 9123456789" class="h-10" />
                                <InputError :message="form.errors.contact_number" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="email" class="text-sm font-medium">
                                    Email Address <span class="text-red-500">*</span>
                                </Label>                                <Input id="email" type="email" required :tabindex="7" autocomplete="email" v-model="form.email" placeholder="email@example.com" class="h-10" />
                                <InputError :message="form.errors.email" />
                            </div>
                        </div>
                    </div>

                    <!-- Account Information Section -->
                    <div class="space-y-6">
                        <h2 class="text-lg font-semibold text-gray-900">Account Information</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="grid gap-2">
                                <Label for="role_title" class="text-sm font-medium">
                                    Role Title <span class="text-red-500">*</span>
                                </Label>
                                <Input id="role_title" type="text" required :tabindex="8" v-model="form.role_title" placeholder="Role Title" class="h-10" />
                                <InputError :message="form.errors.role_title" />
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="grid gap-2">
                                <Label for="password" class="text-sm font-medium">
                                    Password <span class="text-red-500">*</span>
                                </Label>                                <span class="text-xs text-gray-500">
                                    Must be at least 8 characters, include uppercase, lowercase, number, and symbol.
                                </span>
                                <Input
                                    id="password"
                                    type="password"
                                    required
                                    :tabindex="9"
                                    autocomplete="new-password"
                                    v-model="form.password"
                                    placeholder="Password"
                                    class="h-10"
                                />
                                <InputError :message="form.errors.password" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="password_confirmation" class="text-sm font-medium">
                                    Confirm Password <span class="text-red-500">*</span>
                                </Label>                                <Input
                                    id="password_confirmation"
                                    type="password"
                                    required
                                    :tabindex="10"
                                    autocomplete="new-password"
                                    v-model="form.password_confirmation"
                                    placeholder="Confirm password"
                                    class="h-10"
                                />
                                <InputError :message="form.errors.password_confirmation" />
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end">
                        <Button type="submit" class="w-full md:w-auto px-8 py-2" tabindex="11" :disabled="form.processing">
                            <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
                            Create Account
                        </Button>
                    </div>
                </form>
            </div>
        </Layout>
    </AppLayout>
</template>
