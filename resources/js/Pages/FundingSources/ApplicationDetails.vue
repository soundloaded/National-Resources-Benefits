<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    application: Object,
});

const showCancelModal = ref(false);

const cancelForm = useForm({});

const cancelApplication = () => {
    cancelForm.post(route('my-applications.cancel', props.application.id), {
        onSuccess: () => {
            showCancelModal.value = false;
        },
    });
};

const getStatusColor = (status) => {
    const colors = {
        pending: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        under_review: 'bg-blue-100 text-blue-800 border-blue-200',
        approved: 'bg-green-100 text-green-800 border-green-200',
        rejected: 'bg-red-100 text-red-800 border-red-200',
        disbursed: 'bg-emerald-100 text-emerald-800 border-emerald-200',
        cancelled: 'bg-gray-100 text-gray-800 border-gray-200',
    };
    return colors[status] || 'bg-gray-100 text-gray-800 border-gray-200';
};

const getStatusIcon = (status) => {
    switch (status) {
        case 'pending':
            return `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />`;
        case 'under_review':
            return `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />`;
        case 'approved':
            return `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />`;
        case 'rejected':
            return `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />`;
        case 'disbursed':
            return `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />`;
        default:
            return `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />`;
    }
};
</script>

<template>
    <Head :title="`Application ${application.application_number}`" />

    <DashboardLayout>
        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <!-- Back Link -->
            <Link 
                :href="route('my-applications.index')"
                class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-6"
            >
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back to My Applications
            </Link>

            <!-- Header -->
            <div class="mb-8 flex flex-col sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Application Details</h1>
                    <p class="mt-1 text-sm text-gray-500">{{ application.application_number }}</p>
                </div>
                <div class="mt-4 sm:mt-0 flex items-center gap-4">
                    <!-- Status Badge -->
                    <div :class="['px-4 py-2 rounded-lg border flex items-center gap-2', getStatusColor(application.status)]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="getStatusIcon(application.status)"></svg>
                        <span class="font-medium">{{ application.status_label }}</span>
                    </div>
                    
                    <!-- Cancel Button -->
                    <button
                        v-if="application.can_cancel"
                        @click="showCancelModal = true"
                        class="px-4 py-2 border border-red-300 text-red-600 rounded-lg hover:bg-red-50 transition-colors"
                    >
                        Cancel Application
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Funding Source Info -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Funding Source</h3>
                        <div class="flex items-start justify-between">
                            <div>
                                <h4 class="text-xl font-medium text-gray-900">{{ application.funding_source.title }}</h4>
                                <p v-if="application.funding_source.category" class="mt-1 text-sm text-gray-500">
                                    {{ application.funding_source.category }}
                                </p>
                            </div>
                            <Link 
                                :href="route('funding-sources.show', application.funding_source.id)"
                                class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                            >
                                View Details →
                            </Link>
                        </div>
                    </div>

                    <!-- Amount Details -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Funding Amount</h3>
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <p class="text-sm text-gray-500">Requested Amount</p>
                                <p class="text-2xl font-bold text-gray-900">
                                    ${{ Number(application.requested_amount).toLocaleString() }}
                                </p>
                            </div>
                            <div v-if="application.approved_amount">
                                <p class="text-sm text-gray-500">Approved Amount</p>
                                <p class="text-2xl font-bold text-green-600">
                                    ${{ Number(application.approved_amount).toLocaleString() }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Purpose -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Purpose Statement</h3>
                        <p class="text-gray-700 whitespace-pre-wrap">{{ application.purpose }}</p>
                    </div>

                    <!-- Custom Fields -->
                    <div v-if="application.custom_fields && Object.keys(application.custom_fields).length > 0" class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Additional Information</h3>
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div v-for="(value, key) in application.custom_fields" :key="key">
                                <dt class="text-sm text-gray-500 capitalize">{{ key.replace(/_/g, ' ') }}</dt>
                                <dd class="mt-1 text-gray-900">
                                    <template v-if="typeof value === 'object' && value.path">
                                        <a :href="`/storage/${value.path}`" target="_blank" class="text-blue-600 hover:underline">
                                            {{ value.name || 'View File' }}
                                        </a>
                                    </template>
                                    <template v-else>{{ value }}</template>
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Documents -->
                    <div v-if="application.documents && application.documents.length > 0" class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Uploaded Documents</h3>
                        <ul class="divide-y divide-gray-200">
                            <li v-for="(doc, index) in application.documents" :key="index" class="py-3 flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span class="ml-3 text-sm text-gray-900">{{ doc.name }}</span>
                                </div>
                                <a 
                                    :href="`/storage/${doc.path}`" 
                                    target="_blank"
                                    class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                                >
                                    Download
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Rejection Reason -->
                    <div v-if="application.status === 'rejected' && application.rejection_reason" class="bg-red-50 border border-red-200 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-red-800 mb-2">Rejection Reason</h3>
                        <p class="text-red-700">{{ application.rejection_reason }}</p>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    <!-- Timeline -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Timeline</h3>
                        <div class="space-y-4">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">Application Submitted</p>
                                    <p class="text-xs text-gray-500">{{ application.created_at }}</p>
                                </div>
                            </div>
                            
                            <div v-if="application.reviewed_at" class="flex items-start">
                                <div class="flex-shrink-0 w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">Application Reviewed</p>
                                    <p class="text-xs text-gray-500">{{ application.reviewed_at }}</p>
                                </div>
                            </div>
                            
                            <div v-if="application.disbursed_at" class="flex items-start">
                                <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">Funds Disbursed</p>
                                    <p class="text-xs text-gray-500">{{ application.disbursed_at }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Help Card -->
                    <div class="mt-6 bg-gray-50 rounded-lg p-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-2">Need Help?</h3>
                        <p class="text-sm text-gray-600 mb-4">
                            If you have questions about your application, please contact our support team.
                        </p>
                        <Link 
                            :href="route('support-tickets.create')"
                            class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                        >
                            Contact Support →
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cancel Modal -->
        <div v-if="showCancelModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black opacity-50" @click="showCancelModal = false"></div>
                
                <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Cancel Application</h3>
                    <p class="text-gray-600 mb-6">
                        Are you sure you want to cancel this application? This action cannot be undone.
                    </p>
                    <div class="flex justify-end gap-4">
                        <button
                            @click="showCancelModal = false"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                        >
                            Keep Application
                        </button>
                        <button
                            @click="cancelApplication"
                            :disabled="cancelForm.processing"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50"
                        >
                            {{ cancelForm.processing ? 'Cancelling...' : 'Yes, Cancel' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
