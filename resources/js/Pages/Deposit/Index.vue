<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Message from 'primevue/message';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    accounts: Array,
    canDeposit: Boolean,
    settings: Object,
    manualMethodsCount: Number,
    automaticMethodsCount: Number,
});
</script>

<template>
    <Head title="Deposit Funds" />

    <div class="max-w-4xl mx-auto">
        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Deposit Funds</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Choose a deposit method to add funds to your account</p>
        </div>

        <!-- Permission Warning -->
        <Message v-if="!canDeposit" severity="warn" :closable="false" class="mb-6">
            <div class="flex items-center gap-2">
                <i class="pi pi-exclamation-triangle"></i>
                <span>Your account is not currently enabled for deposits. Please contact support for assistance.</span>
            </div>
        </Message>

        <!-- Deposit Limits Info -->
        <Card class="mb-6">
            <template #content>
                <div class="flex flex-wrap gap-6 justify-center text-center">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Minimum Deposit</p>
                        <p class="text-xl font-bold text-primary-600">{{ settings.currency_symbol }}{{ settings.deposit_min }}</p>
                    </div>
                    <div class="border-l border-gray-200 dark:border-gray-700 pl-6">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Maximum Deposit</p>
                        <p class="text-xl font-bold text-primary-600">{{ settings.currency_symbol }}{{ settings.deposit_max }}</p>
                    </div>
                    <div class="border-l border-gray-200 dark:border-gray-700 pl-6">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Available Accounts</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ accounts.length }}</p>
                    </div>
                </div>
            </template>
        </Card>

        <!-- No Accounts Warning -->
        <Message v-if="accounts.length === 0" severity="info" :closable="false" class="mb-6">
            <div class="flex items-center gap-2">
                <i class="pi pi-info-circle"></i>
                <span>You don't have any accounts yet. An account will be created automatically with your first deposit, or contact support.</span>
            </div>
        </Message>

        <!-- Deposit Method Selection -->
        <div class="grid md:grid-cols-2 gap-6" :class="{ 'opacity-50 pointer-events-none': !canDeposit }">
            <!-- Manual Deposit Card -->
            <Card class="hover:shadow-lg transition-shadow cursor-pointer">
                <template #header>
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-6 text-white text-center rounded-t-lg">
                        <i class="pi pi-building text-4xl mb-3"></i>
                        <h3 class="text-xl font-bold">Manual Deposit</h3>
                    </div>
                </template>
                <template #content>
                    <div class="text-center">
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            Transfer funds directly to our bank account or via wire transfer. Upload your receipt for verification.
                        </p>
                        <ul class="text-sm text-left text-gray-500 dark:text-gray-400 mb-4 space-y-2">
                            <li class="flex items-center gap-2">
                                <i class="pi pi-check text-green-500"></i>
                                Bank Wire Transfer
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="pi pi-check text-green-500"></i>
                                Direct Bank Deposit
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="pi pi-check text-green-500"></i>
                                Crypto Wallet Transfer
                            </li>
                        </ul>
                        <p class="text-xs text-gray-400 mb-4">
                            Processing time: 1-3 business days
                        </p>
                        <Link :href="route('deposit.manual')">
                            <Button 
                                label="Choose Manual Deposit" 
                                icon="pi pi-arrow-right" 
                                iconPos="right"
                                class="w-full"
                                :disabled="manualMethodsCount === 0"
                            />
                        </Link>
                        <p v-if="manualMethodsCount === 0" class="text-xs text-orange-500 mt-2">
                            No manual methods available
                        </p>
                    </div>
                </template>
            </Card>

            <!-- Automatic Deposit Card -->
            <Card class="hover:shadow-lg transition-shadow cursor-pointer">
                <template #header>
                    <div class="bg-gradient-to-r from-green-500 to-green-600 p-6 text-white text-center rounded-t-lg">
                        <i class="pi pi-credit-card text-4xl mb-3"></i>
                        <h3 class="text-xl font-bold">Instant Deposit</h3>
                    </div>
                </template>
                <template #content>
                    <div class="text-center">
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            Use our secure payment gateways for instant deposits via card, PayPal, or other methods.
                        </p>
                        <ul class="text-sm text-left text-gray-500 dark:text-gray-400 mb-4 space-y-2">
                            <li class="flex items-center gap-2">
                                <i class="pi pi-check text-green-500"></i>
                                Credit/Debit Cards
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="pi pi-check text-green-500"></i>
                                PayPal & Stripe
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="pi pi-check text-green-500"></i>
                                Local Payment Methods
                            </li>
                        </ul>
                        <p class="text-xs text-gray-400 mb-4">
                            Processing time: Instant
                        </p>
                        <Link :href="route('deposit.automatic')">
                            <Button 
                                label="Choose Instant Deposit" 
                                icon="pi pi-arrow-right" 
                                iconPos="right"
                                severity="success"
                                class="w-full"
                                :disabled="automaticMethodsCount === 0"
                            />
                        </Link>
                        <p v-if="automaticMethodsCount === 0" class="text-xs text-orange-500 mt-2">
                            No instant methods available
                        </p>
                    </div>
                </template>
            </Card>
        </div>

        <!-- Deposit History Link -->
        <div class="mt-8 text-center">
            <Link :href="route('deposit.history')" class="text-primary-600 hover:text-primary-700 font-medium">
                <i class="pi pi-history mr-2"></i>
                View Deposit History
            </Link>
        </div>

        <!-- Help Section -->
        <Card class="mt-8">
            <template #title>
                <div class="flex items-center gap-2">
                    <i class="pi pi-question-circle text-primary-600"></i>
                    Need Help?
                </div>
            </template>
            <template #content>
                <div class="grid md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Which method should I use?</h4>
                        <p class="text-gray-600 dark:text-gray-400">
                            Use <strong>Instant Deposit</strong> for quick transactions. 
                            Use <strong>Manual Deposit</strong> for larger amounts or if you prefer bank transfers.
                        </p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Contact Support</h4>
                        <p class="text-gray-600 dark:text-gray-400">
                            If you encounter any issues with deposits, please 
                            <Link :href="route('support-tickets.create')" class="text-primary-600 hover:underline">open a support ticket</Link>.
                        </p>
                    </div>
                </div>
            </template>
        </Card>
    </div>
</template>
