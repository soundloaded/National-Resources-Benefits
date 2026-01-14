<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Message from 'primevue/message';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    status: {
        type: String,
    },
});

const toast = useToast();
const page = usePage();
const settings = computed(() => page.props.settings || {});

const verificationCode = ref('');
const form = useForm({});

const codeForm = useForm({
    code: '',
});

const submit = () => {
    form.post(route('verification.send'), {
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Email Sent',
                detail: 'A new verification link has been sent to your email address.',
                life: 5000
            });
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to send verification email. Please try again.',
                life: 5000
            });
        }
    });
};

const verifyCode = () => {
    codeForm.code = verificationCode.value;
    codeForm.post(route('verification.verify.code'), {
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
            verificationCode.value = '';
        }
    });
};

const verificationLinkSent = computed(() => form.recentlySuccessful);
</script>

<template>
    <Head title="Verify Email" />
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

            <!-- Verification Card -->
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl border border-gray-100 dark:border-zinc-700 p-8">
                <!-- Header -->
                <div class="text-center mb-8">
                    <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="pi pi-envelope text-2xl text-blue-600 dark:text-blue-400"></i>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-2">
                        Verify Your Email
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400">
                        We've sent a verification code to your email address
                    </p>
                </div>

                <!-- Status Message -->
                <Message v-if="status === 'verification-link-sent' || verificationLinkSent" severity="success" :closable="false" class="mb-6">
                    A new verification link and code have been sent to your email address.
                </Message>

                <!-- Verification Code Form -->
                <form @submit.prevent="verifyCode" class="space-y-6 mb-6">
                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Enter 6-Digit Code
                        </label>
                        <div class="relative">
                            <i class="pi pi-key absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <InputText 
                                id="code"
                                v-model="verificationCode"
                                type="text"
                                maxlength="6"
                                class="w-full input-with-icon text-center text-2xl tracking-widest font-mono"
                                :class="{ 'p-invalid': codeForm.errors.code }"
                                placeholder="000000"
                                required
                                autofocus
                            />
                        </div>
                        <small v-if="codeForm.errors.code" class="text-red-500 mt-1 block">{{ codeForm.errors.code }}</small>
                    </div>

                    <Button 
                        type="submit"
                        :loading="codeForm.processing"
                        :disabled="codeForm.processing || verificationCode.length !== 6"
                        class="w-full justify-center py-3 text-base font-semibold"
                        severity="success"
                    >
                        <i class="pi pi-check-circle mr-2"></i>
                        Verify Email
                    </Button>
                </form>

                <!-- Divider -->
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-200 dark:border-zinc-600"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-white dark:bg-zinc-800 text-gray-500 dark:text-gray-400">
                            Or verify via email link
                        </span>
                    </div>
                </div>

                <!-- Resend Email Form -->
                <form @submit.prevent="submit">
                    <div class="space-y-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400 text-center">
                            Didn't receive the email? Check your spam folder or request a new one.
                        </p>
                        
                        <Button 
                            type="submit"
                            :loading="form.processing"
                            :disabled="form.processing"
                            class="w-full justify-center py-2.5 text-sm font-semibold"
                            outlined
                            severity="secondary"
                        >
                            <i class="pi pi-refresh mr-2"></i>
                            Resend Verification Email
                        </Button>
                    </div>
                </form>

                <!-- Logout Link -->
                <div class="mt-6 text-center">
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 font-medium"
                    >
                        <i class="pi pi-sign-out mr-1"></i>
                        Log Out
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
