<script setup>
import { ref, computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Message from 'primevue/message';

defineProps({
    user: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const settings = computed(() => page.props.settings || {});
const flash = computed(() => page.props.flash || {});

const isVerifying = ref(false);

const startVerification = () => {
    isVerifying.value = true;
    window.location.href = route('auth.idme.redirect');
};

const skipForNow = () => {
    window.location.href = route('auth.idme.skip');
};
</script>

<template>
    <Head title="Verify Your Identity" />

    <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-zinc-900 py-12 px-4 sm:px-6">
        <div class="w-full max-w-2xl">
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
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl border border-gray-100 dark:border-zinc-700 p-8 sm:p-10">
                <!-- Header -->
                <div class="text-center mb-8">
                    <div class="w-20 h-20 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="pi pi-shield text-4xl text-blue-600 dark:text-blue-400"></i>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-3">
                        Verify Your Identity
                    </h2>
                    <p class="text-lg text-gray-600 dark:text-gray-400">
                        To ensure the security of your account and comply with federal regulations, we need to verify your identity.
                    </p>
                </div>

                <!-- Flash Messages -->
                <Message v-if="flash.success" severity="success" :closable="true" class="mb-6">
                    {{ flash.success }}
                </Message>
                
                <Message v-if="flash.error" severity="error" :closable="true" class="mb-6">
                    {{ flash.error }}
                </Message>

                <!-- ID.me Info -->
                <div class="bg-gradient-to-br from-blue-50 to-green-50 dark:from-blue-900/20 dark:to-green-900/20 rounded-xl p-6 mb-8 border border-blue-100 dark:border-blue-800/50">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <svg class="w-12 h-12" viewBox="0 0 200 200" fill="none">
                                <rect width="200" height="200" rx="40" fill="#1E3A8A"/>
                                <path d="M60 140V60H80V140H60Z" fill="white"/>
                                <path d="M100 140V60H140C155 60 165 70 165 85C165 100 155 110 140 110H120V140H100ZM120 95H140C145 95 150 90 150 85C150 80 145 75 140 75H120V95Z" fill="white"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                What is ID.me?
                            </h3>
                            <p class="text-gray-700 dark:text-gray-300 text-sm leading-relaxed mb-3">
                                ID.me is a trusted identity verification service used by government agencies and businesses to confirm your identity securely. It's the same service used by the IRS, VA, and Social Security Administration.
                            </p>
                            <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                                <li class="flex items-center gap-2">
                                    <i class="pi pi-check-circle text-green-600"></i>
                                    <span>Bank-level security and encryption</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <i class="pi pi-check-circle text-green-600"></i>
                                    <span>Takes just 5-10 minutes to complete</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <i class="pi pi-check-circle text-green-600"></i>
                                    <span>Your data is never shared without permission</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- What You'll Need -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="pi pi-list text-blue-600"></i>
                        What You'll Need
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex items-start gap-3 p-4 bg-gray-50 dark:bg-zinc-700/50 rounded-lg">
                            <i class="pi pi-id-card text-2xl text-blue-600 mt-1"></i>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white mb-1">Government ID</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Driver's license, State ID, or Passport</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 p-4 bg-gray-50 dark:bg-zinc-700/50 rounded-lg">
                            <i class="pi pi-mobile text-2xl text-blue-600 mt-1"></i>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white mb-1">Mobile Device</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Smartphone with camera for ID photo</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 p-4 bg-gray-50 dark:bg-zinc-700/50 rounded-lg">
                            <i class="pi pi-image text-2xl text-blue-600 mt-1"></i>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white mb-1">Selfie</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">For facial recognition verification</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 p-4 bg-gray-50 dark:bg-zinc-700/50 rounded-lg">
                            <i class="pi pi-phone text-2xl text-blue-600 mt-1"></i>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white mb-1">Phone Number</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">For two-factor authentication</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Verification Process -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="pi pi-sign-in text-blue-600"></i>
                        How It Works
                    </h3>
                    <div class="space-y-4">
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold text-sm">1</div>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white mb-1">Connect to ID.me</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">You'll be redirected to the secure ID.me verification portal</div>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold text-sm">2</div>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white mb-1">Upload Your ID</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Take photos of your government-issued identification</div>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold text-sm">3</div>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white mb-1">Take a Selfie</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Verify your identity with facial recognition technology</div>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 w-8 h-8 bg-green-600 text-white rounded-full flex items-center justify-center font-bold text-sm">
                                <i class="pi pi-check"></i>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white mb-1">Verification Complete</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Return to your account and start accessing benefits</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Privacy Notice -->
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/50 rounded-xl p-4 mb-8">
                    <div class="flex items-start gap-3">
                        <i class="pi pi-info-circle text-amber-600 dark:text-amber-400 mt-0.5"></i>
                        <div class="text-sm text-amber-900 dark:text-amber-200">
                            <span class="font-semibold">Your Privacy is Protected:</span> ID.me uses the highest standards of data security. Your personal information is encrypted and will only be used for identity verification purposes.
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="space-y-4">
                    <Button 
                        @click="startVerification"
                        :loading="isVerifying"
                        :disabled="isVerifying"
                        class="w-full justify-center py-4 text-lg font-semibold"
                        severity="success"
                    >
                        <i class="pi pi-shield mr-3 text-xl"></i>
                        Verify My Identity with ID.me
                    </Button>

                    <Button 
                        @click="skipForNow"
                        class="w-full justify-center py-3 text-sm font-medium"
                        severity="secondary"
                        text
                    >
                        <i class="pi pi-arrow-right mr-2"></i>
                        Skip for Now (Continue to Dashboard)
                    </Button>
                </div>

                <!-- Help Text -->
                <div class="mt-8 text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Need help? Contact our 
                        <a href="#" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">support team</a>
                        or call 1-800-XXX-XXXX
                    </p>
                </div>
            </div>

            <!-- Security Badge -->
            <div class="mt-6 flex items-center justify-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <i class="pi pi-shield text-green-500"></i>
                <span>Secure Identity Verification • Powered by ID.me</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
:deep(.p-button.p-button-success) {
    @apply bg-green-600 hover:bg-green-700 border-green-600 hover:border-green-700 rounded-xl;
}

:deep(.p-button.p-button-secondary) {
    @apply text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200;
}

:deep(.p-message) {
    @apply rounded-xl;
}
</style>
