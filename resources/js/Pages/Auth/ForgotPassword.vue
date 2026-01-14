<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Message from 'primevue/message';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <Head title="Forgot Password" />

    <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-zinc-900 p-6">
        <div class="w-full max-w-md">
            <!-- Logo -->
            <div class="text-center mb-8">
                <Link :href="route('welcome')" class="inline-block">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        National<span class="text-green-600">Resource</span>Benefits
                    </h1>
                </Link>
            </div>

            <!-- Card -->
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl border border-gray-100 dark:border-zinc-700 p-8">
                <!-- Header -->
                <div class="text-center mb-6">
                    <div class="w-16 h-16 mx-auto mb-4 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                        <i class="pi pi-lock text-2xl text-blue-600 dark:text-blue-400"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                        Forgot Password?
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">
                        No problem. Enter your email address and we'll send you a link to reset your password.
                    </p>
                </div>

                <!-- Status Message -->
                <Message v-if="status" severity="success" :closable="false" class="mb-6">
                    {{ status }}
                </Message>

                <!-- Error Message -->
                <Message v-if="form.errors.email" severity="error" :closable="true" class="mb-6">
                    {{ form.errors.email }}
                </Message>

                <!-- Form -->
                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Email Address
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 z-10">
                                <i class="pi pi-envelope"></i>
                            </span>
                            <InputText 
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="w-full pl-10"
                                :class="{ 'p-invalid': form.errors.email }"
                                placeholder="Enter your email address"
                                required
                                autofocus
                                autocomplete="email"
                            />
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <Button 
                        type="submit"
                        :loading="form.processing"
                        :disabled="form.processing"
                        class="w-full justify-center py-3 text-base font-semibold"
                        severity="success"
                    >
                        <i class="pi pi-send mr-2"></i>
                        Send Reset Link
                    </Button>
                </form>

                <!-- Back to Login -->
                <div class="mt-6 text-center">
                    <Link :href="route('login')" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white inline-flex items-center gap-2">
                        <i class="pi pi-arrow-left text-xs"></i>
                        Back to Sign In
                    </Link>
                </div>
            </div>

            <!-- Footer Links -->
            <div class="mt-8 flex items-center justify-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                <a href="#" class="hover:text-gray-700 dark:hover:text-gray-300">Privacy Policy</a>
                <span>•</span>
                <a href="#" class="hover:text-gray-700 dark:hover:text-gray-300">Terms of Service</a>
                <span>•</span>
                <a href="#" class="hover:text-gray-700 dark:hover:text-gray-300">Contact Support</a>
            </div>
        </div>
    </div>
</template>

<style scoped>
:deep(.p-inputtext) {
    @apply bg-white dark:bg-zinc-800 border-gray-300 dark:border-zinc-600 rounded-xl py-3;
}

:deep(.p-inputtext:focus) {
    @apply ring-2 ring-green-500 border-green-500;
}

:deep(.p-inputtext.p-invalid) {
    @apply border-red-500;
}

:deep(.p-button.p-button-success) {
    @apply bg-green-600 hover:bg-green-700 border-green-600 hover:border-green-700 rounded-xl;
}
</style>
