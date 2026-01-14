<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Message from 'primevue/message';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    accounts: Array,
    canTransfer: Boolean,
    settings: Object,
    hasMultipleAccounts: Boolean,
});

const page = usePage();
const flash = computed(() => page.props.flash || {});

// Calculate total balance
const totalBalance = computed(() => {
    return props.accounts.reduce((sum, acc) => sum + parseFloat(acc.balance), 0);
});
</script>

<template>
    <Head title="Transfer Funds" />

    <div class="max-w-4xl mx-auto">
        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Transfer Funds</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Send money to other users or between your accounts</p>
        </div>

        <!-- Flash Messages -->
        <Message v-if="flash.success" severity="success" :closable="true" class="mb-6">
            {{ flash.success }}
        </Message>
        <Message v-if="flash.error" severity="error" :closable="true" class="mb-6">
            {{ flash.error }}
        </Message>

        <!-- Permission Warning -->
        <Message v-if="!canTransfer" severity="warn" :closable="false" class="mb-6">
            <div class="flex items-center gap-2">
                <i class="pi pi-exclamation-triangle"></i>
                <span>Your account is not currently enabled for transfers. Please contact support for assistance.</span>
            </div>
        </Message>

        <!-- Balance Overview -->
        <Card class="mb-6">
            <template #content>
                <div class="flex flex-wrap gap-6 justify-center text-center">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Available Balance</p>
                        <p class="text-2xl font-bold text-primary-600">
                            {{ settings.currency_symbol }}{{ totalBalance.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                        </p>
                    </div>
                    <div class="border-l border-gray-200 dark:border-gray-700 pl-6">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Transfer Limits</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ settings.currency_symbol }}{{ settings.transfer_min }} - {{ settings.currency_symbol }}{{ settings.transfer_max?.toLocaleString() }}
                        </p>
                    </div>
                    <div v-if="settings.transfer_fee_fixed > 0 || settings.transfer_fee_percentage > 0" class="border-l border-gray-200 dark:border-gray-700 pl-6">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Transfer Fee</p>
                        <p class="text-lg font-semibold text-orange-500">
                            <span v-if="settings.transfer_fee_fixed > 0">{{ settings.currency_symbol }}{{ settings.transfer_fee_fixed }}</span>
                            <span v-if="settings.transfer_fee_fixed > 0 && settings.transfer_fee_percentage > 0"> + </span>
                            <span v-if="settings.transfer_fee_percentage > 0">{{ settings.transfer_fee_percentage }}%</span>
                        </p>
                    </div>
                </div>
            </template>
        </Card>

        <!-- No Accounts Warning -->
        <Message v-if="accounts.length === 0" severity="info" :closable="false" class="mb-6">
            <div class="flex items-center gap-2">
                <i class="pi pi-info-circle"></i>
                <span>You don't have any accounts yet. Please make a deposit first to create an account.</span>
            </div>
        </Message>

        <!-- Transfer Type Selection -->
        <div class="grid md:grid-cols-2 gap-6" :class="{ 'opacity-50 pointer-events-none': !canTransfer || accounts.length === 0 }">
            <!-- Internal Transfer Card -->
            <Card v-if="settings.transfer_internal_active" class="hover:shadow-lg transition-shadow">
                <template #header>
                    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 p-6 text-white text-center rounded-t-lg">
                        <i class="pi pi-users text-4xl mb-3"></i>
                        <h3 class="text-xl font-bold">Send to User</h3>
                    </div>
                </template>
                <template #content>
                    <div class="text-center">
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            Transfer funds instantly to another user on the platform.
                        </p>
                        <ul class="text-sm text-left text-gray-500 dark:text-gray-400 mb-4 space-y-2">
                            <li class="flex items-center gap-2">
                                <i class="pi pi-bolt text-green-500"></i>
                                Instant transfer
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="pi pi-search text-green-500"></i>
                                Search by email or name
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="pi pi-shield text-green-500"></i>
                                Secure & verified
                            </li>
                        </ul>
                        <Link :href="route('transfer.internal')">
                            <Button 
                                label="Send to User" 
                                icon="pi pi-arrow-right" 
                                iconPos="right"
                                class="w-full"
                            />
                        </Link>
                    </div>
                </template>
            </Card>

            <!-- Own Accounts Transfer Card -->
            <Card class="hover:shadow-lg transition-shadow">
                <template #header>
                    <div class="bg-gradient-to-r from-green-500 to-teal-600 p-6 text-white text-center rounded-t-lg">
                        <i class="pi pi-sync text-4xl mb-3"></i>
                        <h3 class="text-xl font-bold">Between Accounts</h3>
                    </div>
                </template>
                <template #content>
                    <div class="text-center">
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            Move funds between your own accounts instantly.
                        </p>
                        <ul class="text-sm text-left text-gray-500 dark:text-gray-400 mb-4 space-y-2">
                            <li class="flex items-center gap-2">
                                <i class="pi pi-bolt text-green-500"></i>
                                Instant transfer
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="pi pi-check text-green-500"></i>
                                No fees
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="pi pi-wallet text-green-500"></i>
                                Multiple wallets
                            </li>
                        </ul>
                        <Link :href="route('transfer.own-accounts')">
                            <Button 
                                label="Transfer Between Accounts" 
                                icon="pi pi-arrow-right" 
                                iconPos="right"
                                severity="success"
                                class="w-full"
                                :disabled="!hasMultipleAccounts"
                            />
                        </Link>
                        <p v-if="!hasMultipleAccounts" class="text-xs text-orange-500 mt-2">
                            You need at least 2 accounts
                        </p>
                    </div>
                </template>
            </Card>

            <!-- Domestic Transfer Card (Coming Soon) -->
            <Card v-if="settings.transfer_domestic_active" class="hover:shadow-lg transition-shadow opacity-60">
                <template #header>
                    <div class="bg-gradient-to-r from-purple-500 to-pink-600 p-6 text-white text-center rounded-t-lg">
                        <i class="pi pi-building text-4xl mb-3"></i>
                        <h3 class="text-xl font-bold">Domestic Transfer</h3>
                    </div>
                </template>
                <template #content>
                    <div class="text-center">
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            Transfer to other banks within the country.
                        </p>
                        <ul class="text-sm text-left text-gray-500 dark:text-gray-400 mb-4 space-y-2">
                            <li class="flex items-center gap-2">
                                <i class="pi pi-clock text-yellow-500"></i>
                                1-3 business days
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="pi pi-building text-yellow-500"></i>
                                Bank to bank
                            </li>
                        </ul>
                        <Button 
                            label="Coming Soon" 
                            icon="pi pi-clock" 
                            severity="secondary"
                            class="w-full"
                            disabled
                        />
                    </div>
                </template>
            </Card>

            <!-- Wire Transfer Card (Coming Soon) -->
            <Card v-if="settings.transfer_wire_active" class="hover:shadow-lg transition-shadow opacity-60">
                <template #header>
                    <div class="bg-gradient-to-r from-orange-500 to-red-600 p-6 text-white text-center rounded-t-lg">
                        <i class="pi pi-globe text-4xl mb-3"></i>
                        <h3 class="text-xl font-bold">Wire Transfer</h3>
                    </div>
                </template>
                <template #content>
                    <div class="text-center">
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            International wire transfers worldwide.
                        </p>
                        <ul class="text-sm text-left text-gray-500 dark:text-gray-400 mb-4 space-y-2">
                            <li class="flex items-center gap-2">
                                <i class="pi pi-clock text-yellow-500"></i>
                                3-5 business days
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="pi pi-globe text-yellow-500"></i>
                                International
                            </li>
                        </ul>
                        <Button 
                            label="Coming Soon" 
                            icon="pi pi-clock" 
                            severity="secondary"
                            class="w-full"
                            disabled
                        />
                    </div>
                </template>
            </Card>
        </div>

        <!-- Transfer History Link -->
        <div class="mt-8 text-center">
            <Link :href="route('transfer.history')" class="text-primary-600 hover:text-primary-700 font-medium">
                <i class="pi pi-history mr-2"></i>
                View Transfer History
            </Link>
        </div>

        <!-- Help Section -->
        <Card class="mt-8">
            <template #title>
                <div class="flex items-center gap-2">
                    <i class="pi pi-question-circle text-primary-600"></i>
                    Transfer Information
                </div>
            </template>
            <template #content>
                <div class="grid md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Internal Transfers</h4>
                        <p class="text-gray-600 dark:text-gray-400">
                            Send money instantly to other users on the platform. The recipient will receive the funds immediately in their account.
                        </p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Need Help?</h4>
                        <p class="text-gray-600 dark:text-gray-400">
                            If you encounter any issues with transfers, please 
                            <Link :href="route('support-tickets.create')" class="text-primary-600 hover:underline">open a support ticket</Link>.
                        </p>
                    </div>
                </div>
            </template>
        </Card>
    </div>
</template>
