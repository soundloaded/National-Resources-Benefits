<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Message from 'primevue/message';
import Tag from 'primevue/tag';
import Divider from 'primevue/divider';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    transaction: Object,
    gateway: Object,
});

// Copy to clipboard helper
const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
};

// Format bank details key to readable label
const formatLabel = (key) => {
    return key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
};

// Get safe bank details from gateway credentials
const getBankDetails = () => {
    const creds = props.gateway?.credentials || {};
    const safeFields = ['bank_name', 'account_name', 'account_number', 'routing_number', 
                        'swift_code', 'iban', 'bank_address', 'reference', 'wallet_address'];
    
    return Object.entries(creds)
        .filter(([key]) => safeFields.includes(key))
        .map(([key, value]) => ({ key, label: formatLabel(key), value }));
};

const bankDetails = getBankDetails();

// Transaction status styling
const getStatusSeverity = (status) => {
    const severities = {
        'pending': 'warn',
        'completed': 'success',
        'failed': 'danger',
        'cancelled': 'secondary',
    };
    return severities[status] || 'info';
};

// Timeline steps
const steps = [
    { 
        status: 'completed',
        label: 'Deposit Initiated', 
        description: 'Your deposit request has been created',
        icon: 'pi pi-check',
        color: 'bg-green-500'
    },
    { 
        status: props.transaction?.status === 'pending' ? 'current' : 'completed',
        label: 'Awaiting Transfer', 
        description: 'Transfer funds using the bank details below',
        icon: 'pi pi-clock',
        color: props.transaction?.status === 'pending' ? 'bg-yellow-500' : 'bg-green-500'
    },
    { 
        status: props.transaction?.status === 'completed' ? 'completed' : 'pending',
        label: 'Verification', 
        description: 'We verify your transfer (1-3 business days)',
        icon: 'pi pi-search',
        color: props.transaction?.status === 'completed' ? 'bg-green-500' : 'bg-gray-300'
    },
    { 
        status: props.transaction?.status === 'completed' ? 'completed' : 'pending',
        label: 'Funds Credited', 
        description: 'Funds are added to your account',
        icon: 'pi pi-wallet',
        color: props.transaction?.status === 'completed' ? 'bg-green-500' : 'bg-gray-300'
    },
];
</script>

<template>
    <Head title="Deposit Instructions" />

    <div class="max-w-4xl mx-auto">
        <!-- Page Header -->
        <div class="mb-6">
            <Link :href="route('deposit.index')" class="text-primary-600 hover:text-primary-700 text-sm mb-2 inline-flex items-center gap-1">
                <i class="pi pi-arrow-left"></i>
                Back to Deposit
            </Link>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Deposit Instructions</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">Complete your transfer using the details below</p>
                </div>
                <Tag :severity="getStatusSeverity(transaction?.status)" :value="transaction?.status?.toUpperCase()" />
            </div>
        </div>

        <!-- Success/Info Message -->
        <Message v-if="transaction?.status === 'pending'" severity="info" :closable="false" class="mb-6">
            <div class="flex items-start gap-3">
                <i class="pi pi-info-circle text-xl"></i>
                <div>
                    <strong>Important:</strong> Please transfer exactly <strong>${{ Number(transaction.amount).toFixed(2) }}</strong> 
                    to the account below. Include your reference number in the transfer description for faster processing.
                </div>
            </div>
        </Message>

        <Message v-if="transaction?.status === 'completed'" severity="success" :closable="false" class="mb-6">
            <div class="flex items-center gap-2">
                <i class="pi pi-check-circle"></i>
                <span>Your deposit has been verified and credited to your account!</span>
            </div>
        </Message>

        <div class="grid lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Progress Timeline -->
                <Card>
                    <template #title>
                        <i class="pi pi-list-check mr-2"></i>
                        Deposit Progress
                    </template>
                    <template #content>
                        <div class="flex flex-col sm:flex-row gap-4">
                            <div 
                                v-for="(step, index) in steps" 
                                :key="index"
                                class="flex-1 relative"
                            >
                                <!-- Connector Line -->
                                <div 
                                    v-if="index < steps.length - 1" 
                                    class="hidden sm:block absolute top-4 left-1/2 w-full h-0.5"
                                    :class="step.status === 'completed' ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-700'"
                                ></div>
                                
                                <div class="flex flex-col items-center text-center relative z-10">
                                    <div 
                                        :class="[
                                            'w-8 h-8 rounded-full flex items-center justify-center mb-2',
                                            step.color,
                                            step.status === 'pending' ? 'opacity-50' : ''
                                        ]"
                                    >
                                        <i :class="[step.icon, 'text-white text-sm']"></i>
                                    </div>
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ step.label }}</h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ step.description }}</p>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Bank Details -->
                <Card>
                    <template #title>
                        <div class="flex items-center justify-between">
                            <span>
                                <i class="pi pi-building mr-2"></i>
                                Bank Transfer Details
                            </span>
                            <span class="text-sm font-normal text-gray-500">{{ gateway?.name }}</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <!-- Bank Details Grid -->
                            <div class="grid sm:grid-cols-2 gap-4">
                                <div 
                                    v-for="detail in bankDetails" 
                                    :key="detail.key"
                                    class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4"
                                >
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ detail.label }}</p>
                                            <p class="font-semibold text-gray-900 dark:text-white mt-1 break-all">{{ detail.value }}</p>
                                        </div>
                                        <Button 
                                            icon="pi pi-copy" 
                                            size="small" 
                                            text 
                                            rounded
                                            @click="copyToClipboard(detail.value)"
                                            v-tooltip.left="'Copy'"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Reference Number -->
                            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="text-xs text-yellow-700 dark:text-yellow-400 uppercase tracking-wide font-semibold">
                                            <i class="pi pi-exclamation-triangle mr-1"></i>
                                            Reference Number (Include in Transfer)
                                        </p>
                                        <p class="font-bold text-lg text-yellow-800 dark:text-yellow-300 mt-1 font-mono">
                                            {{ transaction?.id?.substring(0, 8).toUpperCase() || 'TXN-XXXXX' }}
                                        </p>
                                    </div>
                                    <Button 
                                        icon="pi pi-copy" 
                                        label="Copy" 
                                        size="small" 
                                        severity="warn"
                                        @click="copyToClipboard(transaction?.id?.substring(0, 8).toUpperCase())"
                                    />
                                </div>
                            </div>

                            <!-- No Bank Details Warning -->
                            <Message v-if="bankDetails.length === 0" severity="warn" :closable="false">
                                Bank details are not available. Please contact support for transfer instructions.
                            </Message>
                        </div>
                    </template>
                </Card>

                <!-- Instructions -->
                <Card v-if="gateway?.instructions">
                    <template #title>
                        <i class="pi pi-info-circle mr-2"></i>
                        Transfer Instructions
                    </template>
                    <template #content>
                        <div 
                            class="prose prose-sm dark:prose-invert max-w-none text-gray-600 dark:text-gray-400"
                            v-html="gateway.instructions"
                        ></div>
                    </template>
                </Card>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Transaction Summary -->
                <Card>
                    <template #title>
                        <i class="pi pi-receipt mr-2"></i>
                        Transaction Details
                    </template>
                    <template #content>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Transaction ID</span>
                                <span class="font-mono text-xs">{{ transaction?.id?.substring(0, 12) }}...</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Method</span>
                                <span class="font-medium">{{ gateway?.name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Status</span>
                                <Tag :severity="getStatusSeverity(transaction?.status)" :value="transaction?.status" />
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Created</span>
                                <span>{{ new Date(transaction?.created_at).toLocaleDateString() }}</span>
                            </div>
                            <Divider />
                            <div class="flex justify-between text-lg font-bold">
                                <span>Amount</span>
                                <span class="text-green-600">${{ Number(transaction?.amount || 0).toFixed(2) }}</span>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Help Card -->
                <Card>
                    <template #title>
                        <i class="pi pi-question-circle mr-2"></i>
                        Need Help?
                    </template>
                    <template #content>
                        <div class="space-y-3 text-sm">
                            <p class="text-gray-600 dark:text-gray-400">
                                If you have any questions about your deposit or need assistance, our support team is here to help.
                            </p>
                            <Link :href="route('support-tickets.create')">
                                <Button label="Contact Support" icon="pi pi-envelope" class="w-full" severity="secondary" outlined />
                            </Link>
                        </div>
                    </template>
                </Card>

                <!-- Important Notes -->
                <Card>
                    <template #content>
                        <div class="space-y-3">
                            <h4 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                <i class="pi pi-exclamation-circle text-yellow-500"></i>
                                Important Notes
                            </h4>
                            <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
                                <li class="flex items-start gap-2">
                                    <i class="pi pi-check text-green-500 mt-0.5"></i>
                                    Transfer the exact amount shown
                                </li>
                                <li class="flex items-start gap-2">
                                    <i class="pi pi-check text-green-500 mt-0.5"></i>
                                    Include the reference number
                                </li>
                                <li class="flex items-start gap-2">
                                    <i class="pi pi-check text-green-500 mt-0.5"></i>
                                    Processing takes 1-3 business days
                                </li>
                                <li class="flex items-start gap-2">
                                    <i class="pi pi-check text-green-500 mt-0.5"></i>
                                    You'll receive a notification when credited
                                </li>
                            </ul>
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </div>
</template>
