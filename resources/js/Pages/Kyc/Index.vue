<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import Message from 'primevue/message';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Badge from 'primevue/badge';

const props = defineProps({
    templates: Array,
    documents: Array,
    kycStatus: Object,
    isVerified: Boolean,
    verifiedAt: String,
});

// Status badge severity mapping
const getStatusSeverity = (status) => {
    const severities = {
        approved: 'success',
        pending: 'warn',
        rejected: 'danger',
        verified: 'success',
        action_required: 'danger',
        partial: 'info',
        not_started: 'secondary',
    };
    return severities[status] || 'secondary';
};

// Status display text
const getStatusText = (status) => {
    const texts = {
        approved: 'Approved',
        pending: 'Pending Review',
        rejected: 'Rejected',
        verified: 'Fully Verified',
        action_required: 'Action Required',
        partial: 'Partially Complete',
        not_started: 'Not Started',
    };
    return texts[status] || status;
};

// Check if a template can be submitted/resubmitted
const canSubmit = (template) => {
    if (!template.submission) return true;
    return template.submission.status === 'rejected';
};

// Get action button text
const getActionText = (template) => {
    if (!template.submission) return 'Submit';
    if (template.submission.status === 'rejected') return 'Resubmit';
    return 'View';
};

// Overall status message
const statusMessage = computed(() => {
    switch (props.kycStatus.status) {
        case 'verified':
            return { severity: 'success', text: 'Your identity has been fully verified. You have access to all platform features.' };
        case 'pending':
            return { severity: 'info', text: 'Your documents are being reviewed. This usually takes 1-2 business days.' };
        case 'action_required':
            return { severity: 'warn', text: 'Some of your documents were rejected. Please review and resubmit.' };
        case 'partial':
            return { severity: 'info', text: 'Some documents have been approved. Please complete the remaining verifications.' };
        default:
            return { severity: 'warn', text: 'Please complete your KYC verification to unlock all platform features.' };
    }
});
</script>

<template>
    <Head title="KYC Verification" />

    <DashboardLayout>
        <div class="max-w-6xl mx-auto space-y-6">
            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">KYC Verification</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">Verify your identity to unlock all features</p>
                </div>
                <div v-if="isVerified" class="flex items-center gap-2">
                    <i class="pi pi-verified text-green-500 text-xl"></i>
                    <span class="text-green-600 dark:text-green-400 font-medium">Verified on {{ verifiedAt }}</span>
                </div>
            </div>

            <!-- Status Overview Card -->
            <Card class="shadow-sm">
                <template #content>
                    <div class="space-y-4">
                        <!-- Status Message -->
                        <Message :severity="statusMessage.severity" :closable="false" class="w-full">
                            {{ statusMessage.text }}
                        </Message>

                        <!-- Progress Section -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Progress Bar -->
                            <div>
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Verification Progress</span>
                                    <Tag :severity="getStatusSeverity(kycStatus.status)" :value="getStatusText(kycStatus.status)" />
                                </div>
                                <ProgressBar :value="kycStatus.progress" :showValue="true" class="h-3" />
                            </div>

                            <!-- Stats -->
                            <div class="grid grid-cols-4 gap-4 text-center">
                                <div>
                                    <div class="text-2xl font-bold text-green-600">{{ kycStatus.approved }}</div>
                                    <div class="text-xs text-gray-500">Approved</div>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-yellow-600">{{ kycStatus.pending }}</div>
                                    <div class="text-xs text-gray-500">Pending</div>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-red-600">{{ kycStatus.rejected }}</div>
                                    <div class="text-xs text-gray-500">Rejected</div>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-gray-600">{{ kycStatus.not_submitted }}</div>
                                    <div class="text-xs text-gray-500">Remaining</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Required Documents Section -->
            <div v-if="templates.length > 0">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Required Documents</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <Card v-for="template in templates" :key="template.id" class="shadow-sm hover:shadow-md transition-shadow">
                        <template #header>
                            <div class="p-4 pb-0">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center gap-3">
                                        <div :class="[
                                            'w-10 h-10 rounded-full flex items-center justify-center',
                                            template.submission?.status === 'approved' ? 'bg-green-100 text-green-600' :
                                            template.submission?.status === 'pending' ? 'bg-yellow-100 text-yellow-600' :
                                            template.submission?.status === 'rejected' ? 'bg-red-100 text-red-600' :
                                            'bg-gray-100 text-gray-600'
                                        ]">
                                            <i :class="[
                                                'pi text-lg',
                                                template.submission?.status === 'approved' ? 'pi-check' :
                                                template.submission?.status === 'pending' ? 'pi-clock' :
                                                template.submission?.status === 'rejected' ? 'pi-times' :
                                                'pi-file'
                                            ]"></i>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ template.title }}</h3>
                                        </div>
                                    </div>
                                    <Tag 
                                        v-if="template.submission" 
                                        :severity="getStatusSeverity(template.submission.status)"
                                        :value="getStatusText(template.submission.status)"
                                        class="text-xs"
                                    />
                                </div>
                            </div>
                        </template>
                        
                        <template #content>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                {{ template.description || 'Please submit the required document for verification.' }}
                            </p>

                            <!-- Rejection Reason -->
                            <div v-if="template.submission?.status === 'rejected' && template.submission.rejection_reason" 
                                 class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                                <p class="text-sm text-red-700 dark:text-red-400">
                                    <strong>Rejection Reason:</strong> {{ template.submission.rejection_reason }}
                                </p>
                            </div>

                            <!-- Submission Info -->
                            <div v-if="template.submission" class="text-xs text-gray-500 mb-4">
                                <p>Submitted: {{ template.submission.submitted_at }}</p>
                                <p v-if="template.submission.verified_at">Verified: {{ template.submission.verified_at }}</p>
                            </div>

                            <!-- Fields Preview -->
                            <div v-if="template.form_fields?.length" class="mb-4">
                                <p class="text-xs text-gray-500 mb-2">Required fields:</p>
                                <div class="flex flex-wrap gap-1">
                                    <Badge 
                                        v-for="(field, index) in template.form_fields.slice(0, 3)" 
                                        :key="index"
                                        :value="field.label"
                                        severity="secondary"
                                        class="text-xs"
                                    />
                                    <Badge 
                                        v-if="template.form_fields.length > 3"
                                        :value="`+${template.form_fields.length - 3} more`"
                                        severity="info"
                                        class="text-xs"
                                    />
                                </div>
                            </div>
                        </template>

                        <template #footer>
                            <div class="flex gap-2">
                                <Link 
                                    v-if="canSubmit(template)"
                                    :href="route('kyc.create', template.id)"
                                    class="flex-1"
                                >
                                    <Button 
                                        :label="getActionText(template)" 
                                        :icon="template.submission?.status === 'rejected' ? 'pi pi-refresh' : 'pi pi-upload'"
                                        :severity="template.submission?.status === 'rejected' ? 'warn' : 'primary'"
                                        class="w-full"
                                        size="small"
                                    />
                                </Link>
                                <Link 
                                    v-else-if="template.submission"
                                    :href="route('kyc.show', template.submission.id)"
                                    class="flex-1"
                                >
                                    <Button 
                                        label="View Details" 
                                        icon="pi pi-eye"
                                        severity="secondary"
                                        class="w-full"
                                        size="small"
                                        outlined
                                    />
                                </Link>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <!-- No Templates Message -->
            <Card v-else class="shadow-sm">
                <template #content>
                    <div class="text-center py-8">
                        <i class="pi pi-check-circle text-5xl text-green-500 mb-4"></i>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No Documents Required</h3>
                        <p class="text-gray-600 dark:text-gray-400">There are no KYC documents required at this time.</p>
                    </div>
                </template>
            </Card>

            <!-- Submission History -->
            <div v-if="documents.length > 0">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Submission History</h2>
                
                <!-- Desktop Table -->
                <Card class="shadow-sm hidden md:block">
                    <template #content>
                        <DataTable :value="documents" stripedRows class="p-datatable-sm">
                            <Column field="type" header="Document Type">
                                <template #body="{ data }">
                                    <span class="font-medium">{{ data.type }}</span>
                                </template>
                            </Column>
                            <Column field="status" header="Status">
                                <template #body="{ data }">
                                    <Tag :severity="getStatusSeverity(data.status)" :value="getStatusText(data.status)" />
                                </template>
                            </Column>
                            <Column field="submitted_at" header="Submitted" />
                            <Column field="verified_at" header="Verified">
                                <template #body="{ data }">
                                    {{ data.verified_at || '-' }}
                                </template>
                            </Column>
                            <Column header="Actions" style="width: 100px">
                                <template #body="{ data }">
                                    <Link :href="route('kyc.show', data.id)">
                                        <Button icon="pi pi-eye" severity="secondary" text size="small" />
                                    </Link>
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>

                <!-- Mobile List -->
                <div class="md:hidden space-y-3">
                    <Card v-for="doc in documents" :key="doc.id" class="shadow-sm">
                        <template #content>
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ doc.type }}</p>
                                    <p class="text-xs text-gray-500">{{ doc.submitted_at }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Tag :severity="getStatusSeverity(doc.status)" :value="getStatusText(doc.status)" />
                                    <Link :href="route('kyc.show', doc.id)">
                                        <Button icon="pi pi-chevron-right" severity="secondary" text size="small" />
                                    </Link>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <!-- Help Section -->
            <Card class="shadow-sm bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800">
                <template #content>
                    <div class="flex items-start gap-4">
                        <i class="pi pi-info-circle text-blue-500 text-2xl flex-shrink-0"></i>
                        <div>
                            <h3 class="font-semibold text-blue-900 dark:text-blue-100 mb-1">Need Help?</h3>
                            <p class="text-sm text-blue-700 dark:text-blue-300 mb-2">
                                If you're having trouble with the verification process, please contact our support team.
                            </p>
                            <Link :href="route('support-tickets.index')">
                                <Button label="Contact Support" icon="pi pi-headphones" severity="info" size="small" outlined />
                            </Link>
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    </DashboardLayout>
</template>
