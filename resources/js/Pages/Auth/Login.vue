<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';
import Message from 'primevue/message';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    canRegister: {
        type: Boolean,
        default: true,
    },
});

const toast = useToast();
const page = usePage();
const settings = computed(() => page.props.settings || {});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
        onError: (errors) => {
            Object.values(errors).forEach(error => {
                toast.add({
                    severity: 'error',
                    summary: 'Login Failed',
                    detail: error,
                    life: 5000
                });
            });
        },
    });
};
</script>

<template>
    <Head title="Sign In" />
    <Toast position="top-right" />

    <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-zinc-900 py-12 px-4 sm:px-6">
        <div class="w-full max-w-md">
            <!-- Logo -->
            <div class="text-center mb-8">
                <Link href="/">
                    <img v-if="settings.site_logo" :src="settings.site_logo" :alt="settings.site_name || 'Logo'" class="h-12 max-w-[220px] object-contain mx-auto dark:hidden" />
                    <img v-if="settings.site_logo_dark" :src="settings.site_logo_dark" :alt="settings.site_name || 'Logo'" class="h-12 max-w-[220px] object-contain mx-auto hidden dark:block" />
                    <h1 v-if="!settings.site_logo" class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ settings.site_name || 'NationalResourceBenefits' }}
                    </h1>
                </Link>
            </div>

            <!-- Login Card -->
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl border border-gray-100 dark:border-zinc-700 p-8">
                <!-- Header -->
                <div class="text-center mb-8">
                    <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="pi pi-sign-in text-2xl text-green-600 dark:text-green-400"></i>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-2">
                        Welcome Back
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400">
                        Sign in to continue to your account
                    </p>
                </div>

                <!-- Status Message -->
                <Message v-if="status" severity="success" :closable="false" class="mb-6">
                    {{ status }}
                </Message>

                <!-- Login Form -->
                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Email Address <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <i class="pi pi-envelope absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <InputText 
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="w-full input-with-icon"
                                :class="{ 'p-invalid': form.errors.email }"
                                placeholder="Enter your email"
                                required
                                autofocus
                                autocomplete="email"
                            />
                        </div>
                        <small v-if="form.errors.email" class="text-red-500 mt-1 block">{{ form.errors.email }}</small>
                    </div>

                    <!-- Password Field -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Password <span class="text-red-500">*</span>
                            </label>
                            <Link 
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-sm text-green-600 hover:text-green-700 dark:text-green-400 dark:hover:text-green-300 font-medium"
                            >
                                Forgot password?
                            </Link>
                        </div>
                        <div class="relative">
                            <i class="pi pi-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <InputText 
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                class="w-full input-with-icon-both"
                                :class="{ 'p-invalid': form.errors.password }"
                                placeholder="Enter your password"
                                required
                                autocomplete="current-password"
                            />
                            <button 
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                            >
                                <i :class="showPassword ? 'pi pi-eye-slash' : 'pi pi-eye'"></i>
                            </button>
                        </div>
                        <small v-if="form.errors.password" class="text-red-500 mt-1 block">{{ form.errors.password }}</small>
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <Checkbox v-model="form.remember" :binary="true" />
                            <span class="text-sm text-gray-600 dark:text-gray-400">Remember me</span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <Button 
                        type="submit"
                        :loading="form.processing"
                        :disabled="form.processing"
                        class="w-full justify-center py-3 text-base font-semibold"
                        severity="success"
                    >
                        <i class="pi pi-sign-in mr-2"></i>
                        Sign In
                    </Button>
                </form>

                <!-- Security Notice -->
                <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-800">
                    <div class="flex items-start gap-3">
                        <i class="pi pi-shield text-blue-600 dark:text-blue-400 mt-0.5"></i>
                        <div>
                            <p class="text-sm font-medium text-blue-900 dark:text-blue-300">Secure Connection</p>
                            <p class="text-xs text-blue-700 dark:text-blue-400 mt-1">
                                Your connection is encrypted and your data is protected with bank-level security.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Register Link -->
                <p v-if="canRegister" class="mt-6 text-center text-gray-600 dark:text-gray-400">
                    Don't have an account?
                    <Link :href="route('register')" class="font-semibold text-green-600 hover:text-green-700 dark:text-green-400 dark:hover:text-green-300 ml-1">
                        Create Account
                    </Link>
                </p>
            </div>

            <!-- Footer Links -->
            <div class="mt-6 flex items-center justify-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                <a href="#" class="hover:text-gray-700 dark:hover:text-gray-300">Privacy Policy</a>
                <span>•</span>
                <a href="#" class="hover:text-gray-700 dark:hover:text-gray-300">Terms of Service</a>
                <span>•</span>
                <a href="#" class="hover:text-gray-700 dark:hover:text-gray-300">Contact Support</a>
            </div>

            <!-- Security Badge -->
            <div class="mt-4 flex items-center justify-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <i class="pi pi-shield text-green-500"></i>
                <span>256-bit SSL Encrypted • Your information is secure</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
:deep(.p-inputtext) {
    @apply bg-white dark:bg-zinc-800 border-gray-300 dark:border-zinc-600 rounded-xl py-3 px-4;
}

:deep(.p-inputtext.input-with-icon) {
    padding-left: 2.75rem !important;
}

:deep(.p-inputtext.input-with-icon-both) {
    padding-left: 2.75rem !important;
    padding-right: 2.75rem !important;
}

:deep(.p-inputtext:focus) {
    @apply ring-2 ring-green-500 border-green-500;
}

:deep(.p-inputtext.p-invalid) {
    @apply border-red-500;
}

:deep(.p-checkbox .p-checkbox-box) {
    @apply rounded-md border-gray-300 dark:border-zinc-600;
}

:deep(.p-checkbox .p-checkbox-box.p-highlight) {
    @apply bg-green-600 border-green-600;
}

:deep(.p-button.p-button-success) {
    @apply bg-green-600 hover:bg-green-700 border-green-600 hover:border-green-700 rounded-xl;
}

:deep(.p-button:disabled) {
    @apply opacity-50 cursor-not-allowed;
}

:deep(.p-message) {
    @apply rounded-xl;
}
</style>
