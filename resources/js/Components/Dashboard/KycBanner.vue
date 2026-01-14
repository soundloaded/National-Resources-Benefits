<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    kycStatus: {
        type: Object,
        default: () => ({
            verified: false,
            status: 'not_started',
            documents: { pending: 0, approved: 0, rejected: 0, total: 0 }
        })
    }
});

const statusConfig = computed(() => {
    const configs = {
        verified: {
            severity: 'success',
            icon: 'pi pi-check-circle',
            title: 'Identity Verified',
            message: 'Your account has been verified. You have full access to all features.',
            action: null,
            bgClass: 'bg-green-50 border-green-200 dark:bg-green-900/30 dark:border-green-700'
        },
        pending: {
            severity: 'warn',
            icon: 'pi pi-clock',
            title: 'Verification in Progress',
            message: `We're reviewing your documents. This usually takes 1-2 business days.`,
            action: { label: 'View Status', route: 'kyc.index' },
            bgClass: 'bg-yellow-50 border-yellow-200 dark:bg-yellow-900/30 dark:border-yellow-700'
        },
        rejected: {
            severity: 'error',
            icon: 'pi pi-times-circle',
            title: 'Documents Rejected',
            message: 'Some of your documents were rejected. Please re-submit with valid documents.',
            action: { label: 'Re-submit', route: 'kyc.index' },
            bgClass: 'bg-red-50 border-red-200 dark:bg-red-900/30 dark:border-red-700'
        },
        not_started: {
            severity: 'info',
            icon: 'pi pi-id-card',
            title: 'Verify Your Identity',
            message: 'Complete KYC verification to unlock all features including withdrawals and transfers.',
            action: { label: 'Start Verification', route: 'kyc.index' },
            bgClass: 'bg-blue-50 border-blue-200 dark:bg-blue-900/30 dark:border-blue-700'
        }
    };
    return configs[props.kycStatus.status] || configs.not_started;
});

const progressPercent = computed(() => {
    if (props.kycStatus.verified) return 100;
    const docs = props.kycStatus.documents;
    if (docs.total === 0) return 0;
    return Math.round((docs.approved / Math.max(docs.total, 1)) * 100);
});
</script>

<template>
    <!-- Don't show any banner if user is fully verified -->
    <template v-if="kycStatus.status !== 'verified' && !kycStatus.verified">
        <div 
            class="rounded-xl border p-4"
            :class="statusConfig.bgClass"
        >
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i :class="[statusConfig.icon, 'text-2xl']" 
                       :style="{ color: kycStatus.status === 'rejected' ? '#ef4444' : kycStatus.status === 'pending' ? '#eab308' : '#3b82f6' }"></i>
                </div>
                <div class="ml-4 flex-1">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ statusConfig.title }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                        {{ statusConfig.message }}
                    </p>
                    
                    <!-- Progress bar for pending -->
                    <div v-if="kycStatus.status === 'pending' && kycStatus.documents.total > 0" class="mt-3">
                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                            <span>Documents reviewed</span>
                            <span>{{ kycStatus.documents.approved }}/{{ kycStatus.documents.total }}</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div 
                                class="bg-yellow-500 h-2 rounded-full transition-all duration-300"
                                :style="{ width: `${progressPercent}%` }"
                            ></div>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <div v-if="statusConfig.action" class="mt-3">
                        <Link 
                            :href="route(statusConfig.action.route)"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors"
                            :class="{
                                'bg-blue-600 text-white hover:bg-blue-700': kycStatus.status === 'not_started',
                                'bg-yellow-600 text-white hover:bg-yellow-700': kycStatus.status === 'pending',
                                'bg-red-600 text-white hover:bg-red-700': kycStatus.status === 'rejected'
                            }"
                        >
                            {{ statusConfig.action.label }}
                            <i class="pi pi-arrow-right ml-2 text-xs"></i>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </template>
</template>
