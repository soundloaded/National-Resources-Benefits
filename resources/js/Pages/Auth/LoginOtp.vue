<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Message from 'primevue/message';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

const toast = useToast();
const page = usePage();
const settings = computed(() => page.props.settings || {});
const user = computed(() => page.props.auth.user);

const otpCode = ref('');

const form = useForm({
    otp: '',
});

const resendForm = useForm({});

const verifyOtp = () => {
    form.otp = otpCode.value;
    form.post(route('auth.login-otp.verify'), {
        preserveScroll: true,
        onError: (errors) => {
            Object.values(errors).forEach(error => {
                toast.add({
                    severity: 'error',
                    summary: 'Verification Failed',
                    detail: error,
                    life: 5000
                });
            });
            otpCode.value = '';
        }
    });
};

const resendOtp = () => {
    resendForm.post(route('auth.login-otp.resend'), {
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'OTP Sent',
                detail: 'A new OTP code has been sent to your email.',
                life: 5000
            });
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to resend OTP. Please try again.',
                life: 5000
            });
        }
    });
};
</script>

<template>
    <Head title="Verify Login OTP" />
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

            <!-- OTP Card -->
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl border border-gray-100 dark:border-zinc-700 p-8">
                <!-- Header -->
                <div class="text-center mb-8">
                    <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="pi pi-shield text-2xl text-green-600 dark:text-green-400"></i>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-2">
                        Two-Factor Authentication
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400">
                        We've sent a 6-digit code to
                    </p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                        {{ user?.email }}
                    </p>
                </div>

                <!-- OTP Form -->
                <form @submit.prevent="verifyOtp" class="space-y-6">
                    <div>
                        <label for="otp" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Enter 6-Digit Code
                        </label>
                        <div class="relative">
                            <i class="pi pi-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <InputText 
                                id="otp"
                                v-model="otpCode"
                                type="text"
                                maxlength="6"
                                class="w-full input-with-icon text-center text-2xl tracking-widest font-mono"
                                :class="{ 'p-invalid': form.errors.otp }"
                                placeholder="000000"
                                required
                                autofocus
                            />
                        </div>
                        <small v-if="form.errors.otp" class="text-red-500 mt-1 block">{{ form.errors.otp }}</small>
                    </div>

                    <Button 
                        type="submit"
                        :loading="form.processing"
                        :disabled="form.processing || otpCode.length !== 6"
                        class="w-full justify-center py-3 text-base font-semibold"
                        severity="success"
                    >
                        <i class="pi pi-check-circle mr-2"></i>
                        Verify & Continue
                    </Button>
                </form>

                <!-- Resend OTP -->
                <div class="mt-6">
                    <p class="text-sm text-gray-600 dark:text-gray-400 text-center mb-3">
                        Didn't receive the code?
                    </p>
                    
                    <Button 
                        @click="resendOtp"
                        :loading="resendForm.processing"
                        :disabled="resendForm.processing"
                        class="w-full justify-center py-2.5 text-sm font-semibold"
                        outlined
                        severity="secondary"
                    >
                        <i class="pi pi-refresh mr-2"></i>
                        Resend OTP Code
                    </Button>
                </div>

                <!-- Security Notice -->
                <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-800">
                    <div class="flex items-start gap-3">
                        <i class="pi pi-info-circle text-blue-600 dark:text-blue-400 mt-0.5"></i>
                        <div>
                            <p class="text-sm font-medium text-blue-900 dark:text-blue-300">Security Tip</p>
                            <p class="text-xs text-blue-700 dark:text-blue-400 mt-1">
                                Never share your OTP code with anyone. Our team will never ask for this code.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Cancel Link -->
                <div class="mt-6 text-center">
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 font-medium"
                    >
                        <i class="pi pi-times mr-1"></i>
                        Cancel & Log Out
                    </Link>
                </div>
            </div>

            <!-- Help Text -->
            <div class="mt-6 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Having trouble? <a href="#" class="text-green-600 dark:text-green-400 hover:underline">Contact Support</a>
                </p>
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

:deep(.p-inputtext:focus) {
    @apply ring-2 ring-green-500 border-green-500;
}

:deep(.p-inputtext.p-invalid) {
    @apply border-red-500;
}

:deep(.p-button.p-button-success) {
    @apply bg-green-600 hover:bg-green-700 border-green-600 hover:border-green-700 rounded-xl;
}

:deep(.p-button.p-button-outlined) {
    @apply border-gray-300 dark:border-zinc-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-700 rounded-xl;
}

:deep(.p-button:disabled) {
    @apply opacity-50 cursor-not-allowed;
}

:deep(.p-message) {
    @apply rounded-xl;
}
</style>
