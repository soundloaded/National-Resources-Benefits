<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Message from 'primevue/message';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

// Password strength indicator
const passwordStrength = computed(() => {
    const password = form.password;
    if (!password) return { level: 0, text: '', color: '' };
    
    let strength = 0;
    if (password.length >= 8) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;
    
    const levels = [
        { level: 0, text: '', color: '' },
        { level: 1, text: 'Weak', color: 'bg-red-500' },
        { level: 2, text: 'Fair', color: 'bg-orange-500' },
        { level: 3, text: 'Good', color: 'bg-yellow-500' },
        { level: 4, text: 'Strong', color: 'bg-green-500' },
        { level: 5, text: 'Excellent', color: 'bg-green-600' },
    ];
    
    return levels[strength];
});
</script>

<template>
    <Head title="Reset Password" />

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
                    <div class="w-16 h-16 mx-auto mb-4 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                        <i class="pi pi-key text-2xl text-green-600 dark:text-green-400"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                        Reset Password
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">
                        Create a new secure password for your account.
                    </p>
                </div>

                <!-- Error Messages -->
                <Message v-if="Object.keys(form.errors).length > 0" severity="error" :closable="true" class="mb-6">
                    <ul class="list-disc list-inside text-sm">
                        <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                    </ul>
                </Message>

                <!-- Form -->
                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Email Field (read-only) -->
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
                                class="w-full pl-10 bg-gray-50 dark:bg-zinc-700"
                                readonly
                            />
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            New Password
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 z-10">
                                <i class="pi pi-lock"></i>
                            </span>
                            <InputText 
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                class="w-full pl-10 pr-10"
                                :class="{ 'p-invalid': form.errors.password }"
                                placeholder="Enter new password"
                                required
                                autofocus
                                autocomplete="new-password"
                            />
                            <button 
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 z-10"
                            >
                                <i :class="showPassword ? 'pi pi-eye-slash' : 'pi pi-eye'"></i>
                            </button>
                        </div>
                        <!-- Password Strength Indicator -->
                        <div v-if="form.password" class="mt-2">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 h-1.5 bg-gray-200 dark:bg-zinc-700 rounded-full overflow-hidden">
                                    <div 
                                        class="h-full transition-all duration-300"
                                        :class="passwordStrength.color"
                                        :style="{ width: `${(passwordStrength.level / 5) * 100}%` }"
                                    ></div>
                                </div>
                                <span class="text-xs font-medium" :class="{
                                    'text-red-500': passwordStrength.level <= 1,
                                    'text-orange-500': passwordStrength.level === 2,
                                    'text-yellow-600': passwordStrength.level === 3,
                                    'text-green-500': passwordStrength.level >= 4,
                                }">{{ passwordStrength.text }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Confirm Password Field -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Confirm New Password
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 z-10">
                                <i class="pi pi-lock"></i>
                            </span>
                            <InputText 
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                :type="showConfirmPassword ? 'text' : 'password'"
                                class="w-full pl-10 pr-10"
                                :class="{ 'p-invalid': form.errors.password_confirmation }"
                                placeholder="Confirm new password"
                                required
                                autocomplete="new-password"
                            />
                            <button 
                                type="button"
                                @click="showConfirmPassword = !showConfirmPassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 z-10"
                            >
                                <i :class="showConfirmPassword ? 'pi pi-eye-slash' : 'pi pi-eye'"></i>
                            </button>
                        </div>
                        <!-- Password Match Indicator -->
                        <div v-if="form.password_confirmation" class="mt-1">
                            <span v-if="form.password === form.password_confirmation" class="text-xs text-green-600 flex items-center gap-1">
                                <i class="pi pi-check text-xs"></i> Passwords match
                            </span>
                            <span v-else class="text-xs text-red-500 flex items-center gap-1">
                                <i class="pi pi-times text-xs"></i> Passwords don't match
                            </span>
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
                        <i class="pi pi-check mr-2"></i>
                        Reset Password
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
