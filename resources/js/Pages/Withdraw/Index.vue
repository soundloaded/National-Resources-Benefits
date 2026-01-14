<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Message from 'primevue/message';
import Tag from 'primevue/tag';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    accounts: Array,
    canWithdraw: Boolean,
    withdrawalStatus: String,
    settings: Object,
    manualMethodsCount: Number,
    automaticMethodsCount: Number,
    todayWithdrawals: String,
    remainingDailyLimit: String,
    requiresVerification: Boolean,
});

const getStatusSeverity = (status) => {
    const severities = {
        approved: 'success',
        suspended: 'danger',
        hold: 'warn',
    };
    return severities[status] || 'secondary';
};

const totalBalance = () => {
    return props.accounts.reduce((sum, account) => sum + account.balance, 0);
};
</script>

<template>
    <Head title="Withdraw Funds" />

    <div class="max-w-4xl mx-auto">
        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Withdraw Funds</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Request a withdrawal from your account</p>
        </div>

        <!-- Verification Required Warning -->
        <Message v-if="requiresVerification" severity="warn" :closable="false" class="mb-6">
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-2">
                    <i class="pi pi-shield"></i>
                    <span>Verification required before you can withdraw funds.</span>
                </div>
                <Link :href="route('withdraw.verify')">
                    <Button label="Complete Verification" size="small" severity="warn" outlined />
                </Link>
            </div>
        </Message>

        <!-- Permission Warning -->
        <Message v-if="!canWithdraw" severity="error" :closable="false" class="mb-6">
            <div class="flex items-center gap-2">
                <i class="pi pi-ban"></i>
                <span>Your account is not currently enabled for withdrawals. Please contact support for assistance.</span>
            </div>
        </Message>

        <!-- Status Warning -->
        <Message v-if="withdrawalStatus !== 'approved'" severity="warn" :closable="false" class="mb-6">
            <div class="flex items-center gap-2">
                <i class="pi pi-exclamation-triangle"></i>
                <span>Your withdrawal status is: <Tag :value="withdrawalStatus" :severity="getStatusSeverity(withdrawalStatus)" /></span>
                <span class="ml-2">Please contact support for more information.</span>
            </div>
        </Message>

        <!-- Balance & Limits Info -->
        <Card class="mb-6">
            <template #content>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Balance</p>
                        <p class="text-xl font-bold text-green-600">{{ settings.currency_symbol }}{{ totalBalance().toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</p>
                    </div>
                    <div class="border-l border-gray-200 dark:border-gray-700 pl-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Min Withdrawal</p>
                        <p class="text-xl font-bold text-primary-600">{{ settings.currency_symbol }}{{ settings.withdrawal_min }}</p>
                    </div>
                    <div class="border-l border-gray-200 dark:border-gray-700 pl-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Max Withdrawal</p>
                        <p class="text-xl font-bold text-primary-600">{{ settings.currency_symbol }}{{ settings.withdrawal_max }}</p>
                    </div>
                    <div class="border-l border-gray-200 dark:border-gray-700 pl-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Daily Remaining</p>
                        <p class="text-xl font-bold text-orange-600">{{ settings.currency_symbol }}{{ remainingDailyLimit }}</p>
                    </div>
                </div>
            </template>
        </Card>

        <!-- Account Balances -->
        <Card class="mb-6">
            <template #title>
                <div class="flex items-center gap-2">
                    <i class="pi pi-wallet text-primary-600"></i>
                    Your Accounts
                </div>
            </template>
            <template #content>
                <div v-if="accounts.length === 0" class="text-center py-4 text-gray-500">
                    <i class="pi pi-inbox text-3xl mb-2"></i>
                    <p>No accounts found. Please contact support.</p>
                </div>
                <div v-else class="space-y-3">
                    <div v-for="account in accounts" :key="account.id" 
                         class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center">
                                <i class="pi pi-wallet text-primary-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ account.name }}</p>
                                <p class="text-xs text-gray-500">{{ account.currency }}</p>
                            </div>
                        </div>
                        <p class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ settings.currency_symbol }}{{ account.formatted_balance }}
                        </p>
                    </div>
                </div>
            </template>
        </Card>

        <!-- Withdrawal Method Selection -->
        <div class="grid md:grid-cols-2 gap-6" :class="{ 'opacity-50 pointer-events-none': !canWithdraw || withdrawalStatus !== 'approved' || requiresVerification }">
            <!-- Manual Withdrawal Card -->
            <Card class="hover:shadow-lg transition-shadow">
                <template #header>
                    <div class="bg-gradient-to-r from-purple-500 to-purple-600 p-6 text-white text-center rounded-t-lg">
                        <i class="pi pi-building text-4xl mb-3"></i>
                        <h3 class="text-xl font-bold">Bank Withdrawal</h3>
                    </div>
                </template>
                <template #content>
                    <div class="text-center">
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            Withdraw funds directly to your bank account via wire transfer or ACH.
                        </p>
                        <ul class="text-sm text-left text-gray-500 dark:text-gray-400 mb-4 space-y-2">
                            <li class="flex items-center gap-2">
                                <i class="pi pi-check text-green-500"></i>
                                Bank Wire Transfer
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="pi pi-check text-green-500"></i>
                                ACH Direct Deposit
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="pi pi-check text-green-500"></i>
                                International Wire
                            </li>
                        </ul>
                        <p class="text-xs text-gray-400 mb-4">
                            Processing time: 1-5 business days
                        </p>
                        <Link :href="route('withdraw.manual')">
                            <Button 
                                label="Request Bank Withdrawal" 
                                icon="pi pi-arrow-right" 
                                iconPos="right"
                                class="w-full"
                                :disabled="manualMethodsCount === 0"
                            />
                        </Link>
                        <p v-if="manualMethodsCount === 0" class="text-xs text-orange-500 mt-2">
                            No bank withdrawal methods available
                        </p>
                    </div>
                </template>
            </Card>

            <!-- Automatic Withdrawal Card -->
            <Card class="hover:shadow-lg transition-shadow">
                <template #header>
                    <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 p-6 text-white text-center rounded-t-lg">
                        <i class="pi pi-bolt text-4xl mb-3"></i>
                        <h3 class="text-xl font-bold">Express Withdrawal</h3>
                    </div>
                </template>
                <template #content>
                    <div class="text-center">
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            Fast withdrawals via supported payment processors and digital wallets.
                        </p>
                        <ul class="text-sm text-left text-gray-500 dark:text-gray-400 mb-4 space-y-2">
                            <li class="flex items-center gap-2">
                                <i class="pi pi-check text-green-500"></i>
                                PayPal Withdrawal
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="pi pi-check text-green-500"></i>
                                Crypto Wallet
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="pi pi-check text-green-500"></i>
                                E-Wallet Transfer
                            </li>
                        </ul>
                        <p class="text-xs text-gray-400 mb-4">
                            Processing time: 1-24 hours
                        </p>
                        <Link :href="route('withdraw.automatic')">
                            <Button 
                                label="Request Express Withdrawal" 
                                icon="pi pi-arrow-right" 
                                iconPos="right"
                                severity="help"
                                class="w-full"
                                :disabled="automaticMethodsCount === 0"
                            />
                        </Link>
                        <p v-if="automaticMethodsCount === 0" class="text-xs text-orange-500 mt-2">
                            No express methods available
                        </p>
                    </div>
                </template>
            </Card>
        </div>

        <!-- Daily Withdrawal Summary -->
        <Card class="mt-6">
            <template #title>
                <div class="flex items-center gap-2">
                    <i class="pi pi-chart-bar text-primary-600"></i>
                    Today's Activity
                </div>
            </template>
            <template #content>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Withdrawn Today</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ settings.currency_symbol }}{{ todayWithdrawals }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Daily Limit</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ settings.currency_symbol }}{{ settings.withdrawal_limit_daily.toLocaleString() }}</p>
                    </div>
                </div>
                <div class="mt-4 bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                    <div 
                        class="bg-primary-600 h-full transition-all duration-300"
                        :style="{ width: `${Math.min((parseFloat(todayWithdrawals.replace(/,/g, '')) / settings.withdrawal_limit_daily) * 100, 100)}%` }"
                    ></div>
                </div>
            </template>
        </Card>

        <!-- Withdrawal History Link -->
        <div class="mt-8 text-center">
            <Link :href="route('withdraw.history')" class="text-primary-600 hover:text-primary-700 font-medium">
                <i class="pi pi-history mr-2"></i>
                View Withdrawal History
            </Link>
        </div>

        <!-- Help Section -->
        <Card class="mt-8">
            <template #title>
                <div class="flex items-center gap-2">
                    <i class="pi pi-question-circle text-primary-600"></i>
                    Important Information
                </div>
            </template>
            <template #content>
                <div class="grid md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Processing Times</h4>
                        <p class="text-gray-600 dark:text-gray-400">
                            Bank withdrawals typically take 1-5 business days. Express withdrawals are processed within 24 hours.
                        </p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Verification Codes</h4>
                        <p class="text-gray-600 dark:text-gray-400">
                            You may need to enter verification codes (IMF, Tax, COT) before withdrawing. 
                            <Link :href="route('withdraw.verify')" class="text-primary-600 hover:underline">Verify now</Link>
                        </p>
                    </div>
                </div>
            </template>
        </Card>
    </div>
</template>
