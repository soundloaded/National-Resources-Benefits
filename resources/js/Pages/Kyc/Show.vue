<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import Image from 'primevue/image';

const props = defineProps({
    document: Object,
});

// Status badge severity mapping
const getStatusSeverity = (status) => {
    const severities = {
        approved: 'success',
        pending: 'warn',
        rejected: 'danger',
    };
    return severities[status] || 'secondary';
};

// Status display text
const getStatusText = (status) => {
    const texts = {
        approved: 'Approved',
        pending: 'Pending Review',
        rejected: 'Rejected',
    };
    return texts[status] || status;
};

// Check if document path is an image
const isImage = (path) => {
    if (!path) return false;
    const ext = path.split('.').pop().toLowerCase();
    return ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext);
};
</script>

<template>
    <Head :title="`KYC Document - ${document.type}`" />

    <DashboardLayout>
        <div class="max-w-3xl mx-auto space-y-6">
            <!-- Breadcrumb -->
            <nav class="flex items-center gap-2 text-sm">
                <Link :href="route('kyc.index')" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">
                    KYC Verification
                </Link>
                <i class="pi pi-chevron-right text-gray-400 text-xs"></i>
                <span class="text-gray-600 dark:text-gray-400">{{ document.type }}</span>
            </nav>

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ document.type }}</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">Submitted: {{ document.submitted_at }}</p>
                </div>
                <Tag 
                    :severity="getStatusSeverity(document.status)" 
                    :value="getStatusText(document.status)"
                    class="text-sm"
                />
            </div>

            <!-- Status Timeline -->
            <Card class="shadow-sm">
                <template #content>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Status Timeline</h3>
                    <div class="relative">
                        <!-- Submitted -->
                        <div class="flex items-start gap-4 pb-6">
                            <div class="flex flex-col items-center">
                                <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center">
                                    <i class="pi pi-upload text-white text-sm"></i>
                                </div>
                                <div class="w-0.5 h-full bg-gray-200 dark:bg-gray-700 mt-2" 
                                     :class="{ 'bg-green-500': document.status === 'approved', 'bg-red-500': document.status === 'rejected' }">
                                </div>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">Submitted</p>
                                <p class="text-sm text-gray-500">{{ document.submitted_at }}</p>
                            </div>
                        </div>

                        <!-- Review (Pending) -->
                        <div class="flex items-start gap-4 pb-6" v-if="document.status === 'pending'">
                            <div class="flex flex-col items-center">
                                <div class="w-8 h-8 rounded-full bg-yellow-500 flex items-center justify-center animate-pulse">
                                    <i class="pi pi-clock text-white text-sm"></i>
                                </div>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">Under Review</p>
                                <p class="text-sm text-gray-500">Your document is being reviewed by our team</p>
                            </div>
                        </div>

                        <!-- Approved -->
                        <div class="flex items-start gap-4" v-if="document.status === 'approved'">
                            <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center">
                                <i class="pi pi-check text-white text-sm"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">Approved</p>
                                <p class="text-sm text-gray-500">{{ document.verified_at }}</p>
                            </div>
                        </div>

                        <!-- Rejected -->
                        <div class="flex items-start gap-4" v-if="document.status === 'rejected'">
                            <div class="w-8 h-8 rounded-full bg-red-500 flex items-center justify-center">
                                <i class="pi pi-times text-white text-sm"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">Rejected</p>
                                <p class="text-sm text-gray-500">Please review the reason and resubmit</p>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Rejection Reason -->
            <Card v-if="document.status === 'rejected' && document.rejection_reason" class="shadow-sm border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20">
                <template #content>
                    <div class="flex items-start gap-4">
                        <i class="pi pi-exclamation-triangle text-red-500 text-2xl flex-shrink-0"></i>
                        <div>
                            <h3 class="font-semibold text-red-900 dark:text-red-100 mb-1">Rejection Reason</h3>
                            <p class="text-red-700 dark:text-red-300">{{ document.rejection_reason }}</p>
                            <Link 
                                v-if="document.template"
                                :href="route('kyc.create', document.template.id)" 
                                class="inline-block mt-4"
                            >
                                <Button label="Resubmit Document" icon="pi pi-refresh" severity="warn" size="small" />
                            </Link>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Submitted Data -->
            <Card v-if="document.data && Object.keys(document.data).length > 0" class="shadow-sm">
                <template #title>
                    <span class="text-lg">Submitted Information</span>
                </template>
                <template #content>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div 
                            v-for="(value, key) in document.data" 
                            :key="key"
                            class="py-3 flex flex-col sm:flex-row sm:justify-between gap-1"
                        >
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ key }}</span>
                            <!-- Check if value is a URL (file) -->
                            <template v-if="value && typeof value === 'string' && value.startsWith('/storage/')">
                                <a 
                                    :href="value" 
                                    target="_blank"
                                    class="text-blue-600 hover:text-blue-800 dark:text-blue-400 text-sm flex items-center gap-1"
                                >
                                    <i class="pi pi-external-link text-xs"></i>
                                    View Document
                                </a>
                            </template>
                            <template v-else>
                                <span class="text-sm text-gray-900 dark:text-white">{{ value }}</span>
                            </template>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Document Preview -->
            <Card v-if="document.document_path" class="shadow-sm">
                <template #title>
                    <span class="text-lg">Document Preview</span>
                </template>
                <template #content>
                    <div class="rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-800">
                        <!-- Image Preview -->
                        <template v-if="isImage(document.document_path)">
                            <Image 
                                :src="document.document_path" 
                                :alt="document.type"
                                preview
                                class="w-full max-h-96 object-contain"
                            />
                        </template>
                        
                        <!-- PDF Preview -->
                        <template v-else>
                            <div class="p-8 text-center">
                                <i class="pi pi-file-pdf text-5xl text-red-500 mb-4"></i>
                                <p class="text-gray-600 dark:text-gray-400 mb-4">PDF Document</p>
                                <a :href="document.document_path" target="_blank">
                                    <Button label="Open PDF" icon="pi pi-external-link" severity="secondary" outlined />
                                </a>
                            </div>
                        </template>
                    </div>
                </template>
            </Card>

            <!-- Template Info -->
            <Card v-if="document.template" class="shadow-sm">
                <template #content>
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                            <i class="pi pi-file-edit text-blue-500 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ document.template.title }}</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ document.template.description }}</p>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Actions -->
            <div class="flex justify-between gap-4">
                <Link :href="route('kyc.index')">
                    <Button label="Back to KYC" icon="pi pi-arrow-left" severity="secondary" outlined />
                </Link>
                
                <Link 
                    v-if="document.status === 'rejected' && document.template"
                    :href="route('kyc.create', document.template.id)"
                >
                    <Button label="Resubmit" icon="pi pi-refresh" severity="warn" />
                </Link>
            </div>
        </div>
    </DashboardLayout>
</template>
