<script setup lang="ts">
import { ref } from "vue";
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Eye, EyeOff } from 'lucide-vue-next';

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

// 👁 toggles
const showPassword = ref(false);
const showConfirmPassword = ref(false);
</script>

<template>
    <AuthBase title="Create an account" description="Enter your details below to create your account">
        <Head title="Register" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="w-full max-w-md p-6">
                <div class="grid gap-4">
                    <!-- First name -->
                    <div class="grid gap-2">
                        <Label for="first_name">First name</Label>
                        <Input
                            id="first_name"
                            type="text"
                            required
                            autofocus
                            tabindex="1"
                            autocomplete="given-name"
                            v-model="form.first_name"
                            @input="form.clearErrors('first_name')"
                            placeholder="First name"
                            class="w-full"
                        />
                        <InputError :message="form.errors.first_name" />
                    </div>

                    <!-- Last name -->
                    <div class="grid gap-2">
                        <Label for="last_name">Last name</Label>
                        <Input
                            id="last_name"
                            type="text"
                            required
                            tabindex="2"
                            autocomplete="family-name"
                            v-model="form.last_name"
                            @input="form.clearErrors('last_name')"
                            placeholder="Last name"
                            class="w-full"
                        />
                        <InputError :message="form.errors.last_name" />
                    </div>

                    <!-- Email -->
                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            required
                            tabindex="3"
                            autocomplete="email"
                            v-model="form.email"
                            @input="form.clearErrors('email')"
                            placeholder="email@example.com"
                            class="w-full"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <!-- Password -->
                    <div class="grid gap-2">
                        <Label for="password">Password</Label>
                        <span class="text-xs text-gray-500">
                            Must be at least 8 characters, include uppercase, lowercase, number, and symbol.
                        </span>
                        <div class="relative">
                            <Input
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                tabindex="4"
                                autocomplete="new-password"
                                v-model="form.password"
                                @input="form.clearErrors('password')"
                                placeholder="Password"
                                class="w-full pr-10"
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

                    <!-- Confirm password -->
                    <div class="grid gap-2">
                        <Label for="password_confirmation">Confirm password</Label>
                        <div class="relative">
                            <Input
                                id="password_confirmation"
                                :type="showConfirmPassword ? 'text' : 'password'"
                                required
                                tabindex="5"
                                autocomplete="new-password"
                                v-model="form.password_confirmation"
                                @input="form.clearErrors('password_confirmation')"
                                placeholder="Confirm password"
                                class="w-full pr-10"
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

                    <!-- Submit -->
                    <Button type="submit" class="mt-4 w-full flex items-center justify-center" tabindex="6" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="h-5 w-5 animate-spin mr-2" />
                        Create account
                    </Button>
                </div>
            </div>

            <!-- Login link -->
            <div
                v-if="$page.props.config.login_enabled"
                class="text-center text-sm text-muted-foreground">
                Already have an account?
                <TextLink :href="route('login')" class="underline underline-offset-4" :tabindex="6">Log in</TextLink>
            </div>
        </form>
    </AuthBase>
</template>
