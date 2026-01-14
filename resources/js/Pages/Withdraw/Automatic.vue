<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputNumber from 'primevue/inputnumber';
import Message from 'primevue/message';
import Steps from 'primevue/steps';
import Dialog from 'primevue/dialog';
import Divider from 'primevue/divider';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    accounts: Array,
    gateways: Array,
    settings: Object,
    requiresVerification: Boolean,
    verificationStatus: Object,
});

const currentStep = ref(0);
const showConfirmDialog = ref(false);

const steps = [
    { label: 'Account' },
    { label: 'Gateway' },
    { label: 'Amount' },
    { label: 'Confirm' },
];

const form = useForm({
    account_id: null,
    gateway_id: null,
    amount: null,
    bank_details: {}, // For automatic, we typically don't need bank details
});

const selectedAccount = computed(() => {
    return props.accounts.find(a => a.id === form.account_id);
});

const selectedGateway = computed(() => {
    return props.gateways.find(g => g.id === form.gateway_id);
});

const calculatedFee = computed(() => {
    if (!form.amount || !selectedGateway.value) return 0;
    const feeFixed = selectedGateway.value.fee_fixed || 0;
    const feePercentage = selectedGateway.value.fee_percentage || 0;
    return feeFixed + (form.amount * feePercentage / 100);
});

const totalDeduction = computed(() => {
    return (form.amount || 0) + calculatedFee.value;
});

const netAmount = computed(() => {
    return form.amount || 0;
});

const minLimit = computed(() => {
    if (!selectedGateway.value) return props.settings.withdrawal_min;
    return Math.max(props.settings.withdrawal_min, selectedGateway.value.min_limit || 0);
});

const maxLimit = computed(() => {
    if (!selectedGateway.value) return props.settings.withdrawal_max;
    const gatewayMax = selectedGateway.value.max_limit || Infinity;
    return Math.min(props.settings.withdrawal_max, gatewayMax);
});

const maxWithdrawable = computed(() => {
    if (!selectedAccount.value || !selectedGateway.value) return 0;
    const balance = selectedAccount.value.balance;
    const feeFixed = selectedGateway.value.fee_fixed || 0;
    const feePercentage = selectedGateway.value.fee_percentage || 0;
    const maxFromBalance = (balance - feeFixed) / (1 + feePercentage / 100);
    return Math.min(Math.max(0, maxFromBalance), maxLimit.value);
});

const canProceedStep1 = computed(() => form.account_id !== null);
const canProceedStep2 = computed(() => form.gateway_id !== null);
const canProceedStep3 = computed(() => {
    return form.amount >= minLimit.value && 
           form.amount <= maxLimit.value &&
           totalDeduction.value <= (selectedAccount.value?.balance || 0);
});

const nextStep = () => {
    if (currentStep.value < steps.length - 1) {
        currentStep.value++;
    }
};

const prevStep = () => {
    if (currentStep.value > 0) {
        currentStep.value--;
    }
};

const goToStep = (index) => {
    if (index <= currentStep.value) {
        currentStep.value = index;
    }
};

const setMaxAmount = () => {
    form.amount = Math.floor(maxWithdrawable.value * 100) / 100;
};

const submitWithdrawal = () => {
    showConfirmDialog.value = false;
    form.post(route('withdraw.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            currentStep.value = 0;
        },
    });
};
</script>

<template>
    <Head title="Express Withdrawal" />

    <div class="max-w-3xl mx-auto">
        <!-- Page Header -->
        <div class="mb-6">
            <Link :href="route('withdraw.index')" class="text-primary-600 hover:text-primary-700 text-sm mb-2 inline-flex items-center">
                <i class="pi pi-arrow-left mr-2"></i>
                Back to Withdraw
            </Link>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Express Withdrawal</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Fast withdrawal via digital payment methods</p>
        </div>

        <!-- Verification Required -->
        <Message v-if="requiresVerification" severity="warn" :closable="false" class="mb-6">
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-2">
                    <i class="pi pi-shield"></i>
                    <span>Please complete verification before withdrawing.</span>
                </div>
                <Link :href="route('withdraw.verify')">
                    <Button label="Verify Now" size="small" severity="warn" />
                </Link>
            </div>
        </Message>

        <!-- No Gateways Available -->
        <Message v-if="gateways.length === 0" severity="info" :closable="false" class="mb-6">
            <div class="flex items-center gap-2">
                <i class="pi pi-info-circle"></i>
                <span>No express withdrawal methods are currently available. Please try bank withdrawal or contact support.</span>
            </div>
        </Message>

        <!-- Wizard Content -->
        <div v-if="gateways.length > 0 && !requiresVerification">
            <!-- Steps Indicator -->
            <Card class="mb-6">
                <template #content>
                    <Steps :model="steps" :activeStep="currentStep" :readonly="false" @step-change="(e) => goToStep(e.index)" />
                </template>
            </Card>

            <!-- Step 1: Select Account -->
            <Card v-if="currentStep === 0" class="mb-6">
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-wallet text-primary-600"></i>
                        Select Source Account
                    </div>
                </template>
                <template #content>
                    <div class="space-y-3">
                        <div v-for="account in accounts" :key="account.id"
                             class="p-4 border-2 rounded-lg cursor-pointer transition-all duration-300 ease-in-out"
                             :class="form.account_id === account.id 
                                 ? 'border-green-500 bg-green-50 dark:bg-green-900/20 shadow-md shadow-green-500/20' 
                                 : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'"
                             @click="form.account_id = account.id">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center transition-all duration-300"
                                         :class="form.account_id === account.id ? 'bg-green-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-500'">
                                        <i :class="form.account_id === account.id ? 'pi pi-check' : 'pi pi-wallet'" class="transition-all duration-300"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ account.name }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ account.currency }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold" :class="form.account_id === account.id ? 'text-green-600' : 'text-gray-900 dark:text-white'">
                                        {{ settings.currency_symbol }}{{ account.formatted_balance }}
                                    </p>
                                    <p class="text-xs text-gray-500">Available</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end mt-6">
                        <Button label="Continue" icon="pi pi-arrow-right" iconPos="right" 
                                :disabled="!canProceedStep1" @click="nextStep" />
                    </div>
                </template>
            </Card>

            <!-- Step 2: Select Gateway -->
            <Card v-if="currentStep === 1" class="mb-6">
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-bolt text-primary-600"></i>
                        Select Payment Method
                    </div>
                </template>
                <template #content>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div v-for="gateway in gateways" :key="gateway.id"
                             class="p-4 border-2 rounded-lg cursor-pointer transition-all duration-300 ease-in-out"
                             :class="form.gateway_id === gateway.id 
                                 ? 'border-green-500 bg-green-50 dark:bg-green-900/20 shadow-md shadow-green-500/20' 
                                 : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'"
                             @click="form.gateway_id = gateway.id">
                            <div class="flex flex-col items-center text-center">
                                <div class="w-16 h-16 rounded-xl flex items-center justify-center transition-all duration-300 mb-3"
                                     :class="form.gateway_id === gateway.id ? 'bg-green-500' : 'bg-gray-100 dark:bg-gray-700'">
                                    <i v-if="form.gateway_id === gateway.id" class="pi pi-check text-2xl text-white"></i>
                                    <img v-else-if="gateway.logo" :src="gateway.logo" :alt="gateway.name" class="w-10 h-10 object-contain" />
                                    <i v-else class="pi pi-bolt text-2xl text-gray-500"></i>
                                </div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ gateway.name }}</p>
                                <p v-if="gateway.fee_fixed > 0 || gateway.fee_percentage > 0" class="text-xs text-orange-600 mt-1">
                                    Fee: {{ settings.currency_symbol }}{{ gateway.fee_fixed }}
                                    <span v-if="gateway.fee_percentage > 0"> + {{ gateway.fee_percentage }}%</span>
                                </p>
                                <p v-else class="text-xs text-green-600 mt-1">No fees</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between mt-6">
                        <Button label="Back" icon="pi pi-arrow-left" severity="secondary" outlined @click="prevStep" />
                        <Button label="Continue" icon="pi pi-arrow-right" iconPos="right" 
                                :disabled="!canProceedStep2" @click="nextStep" />
                    </div>
                </template>
            </Card>

            <!-- Step 3: Enter Amount -->
            <Card v-if="currentStep === 2" class="mb-6">
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-dollar text-primary-600"></i>
                        Enter Amount
                    </div>
                </template>
                <template #content>
                    <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-500">Available Balance:</span>
                            <span class="font-medium">{{ settings.currency_symbol }}{{ selectedAccount?.formatted_balance }}</span>
                        </div>
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-500">Minimum Withdrawal:</span>
                            <span class="font-medium">{{ settings.currency_symbol }}{{ minLimit.toLocaleString() }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Maximum Withdrawal:</span>
                            <span class="font-medium">{{ settings.currency_symbol }}{{ maxLimit.toLocaleString() }}</span>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Withdrawal Amount
                        </label>
                        <div class="flex gap-2">
                            <InputNumber v-model="form.amount" 
                                         :min="minLimit" 
                                         :max="maxLimit"
                                         mode="currency" 
                                         currency="USD" 
                                         locale="en-US"
                                         class="flex-1"
                                         placeholder="Enter amount" />
                            <Button label="Max" severity="secondary" outlined @click="setMaxAmount" />
                        </div>
                        <p v-if="form.errors.amount" class="text-red-500 text-sm mt-1">{{ form.errors.amount }}</p>
                    </div>

                    <!-- Fee Breakdown -->
                    <div class="p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg mb-6">
                        <h4 class="font-medium text-gray-900 dark:text-white mb-3">Transaction Summary</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Withdrawal Amount:</span>
                                <span class="font-medium">{{ settings.currency_symbol }}{{ (form.amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Processing Fee:</span>
                                <span class="font-medium text-orange-600">-{{ settings.currency_symbol }}{{ calculatedFee.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</span>
                            </div>
                            <Divider />
                            <div class="flex justify-between text-base">
                                <span class="font-semibold text-gray-900 dark:text-white">Total Deduction:</span>
                                <span class="font-bold text-primary-600">{{ settings.currency_symbol }}{{ totalDeduction.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</span>
                            </div>
                            <div class="flex justify-between text-base">
                                <span class="font-semibold text-gray-900 dark:text-white">You'll Receive:</span>
                                <span class="font-bold text-green-600">{{ settings.currency_symbol }}{{ netAmount.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</span>
                            </div>
                        </div>
                    </div>

                    <Message v-if="totalDeduction > (selectedAccount?.balance || 0)" severity="error" :closable="false">
                        Insufficient balance. Total deduction exceeds available balance.
                    </Message>

                    <div class="flex justify-between mt-6">
                        <Button label="Back" icon="pi pi-arrow-left" severity="secondary" outlined @click="prevStep" />
                        <Button label="Review Withdrawal" icon="pi pi-arrow-right" iconPos="right" 
                                :disabled="!canProceedStep3" @click="nextStep" />
                    </div>
                </template>
            </Card>

            <!-- Step 4: Confirmation -->
            <Card v-if="currentStep === 3" class="mb-6">
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-check-circle text-primary-600"></i>
                        Review & Confirm
                    </div>
                </template>
                <template #content>
                    <div class="space-y-4">
                        <!-- Account Info -->
                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Source Account</h4>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ selectedAccount?.name }}</p>
                            <p class="text-sm text-gray-500">Balance: {{ settings.currency_symbol }}{{ selectedAccount?.formatted_balance }}</p>
                        </div>

                        <!-- Gateway Info -->
                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Payment Method</h4>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-gray-200 dark:bg-gray-700">
                                    <img v-if="selectedGateway?.logo" :src="selectedGateway.logo" :alt="selectedGateway.name" class="w-6 h-6 object-contain" />
                                    <i v-else class="pi pi-bolt text-gray-500"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ selectedGateway?.name }}</p>
                                    <p class="text-xs text-gray-500">Express processing</p>
                                </div>
                            </div>
                        </div>

                        <!-- Amount Summary -->
                        <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Transaction Summary</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Withdrawal Amount:</span>
                                    <span class="font-medium">{{ settings.currency_symbol }}{{ form.amount?.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Fee:</span>
                                    <span class="font-medium text-orange-600">-{{ settings.currency_symbol }}{{ calculatedFee.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</span>
                                </div>
                                <Divider />
                                <div class="flex justify-between text-lg">
                                    <span class="font-bold text-gray-900 dark:text-white">You'll Receive:</span>
                                    <span class="font-bold text-green-600">{{ settings.currency_symbol }}{{ netAmount.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <Message severity="info" :closable="false" class="mt-4">
                        <i class="pi pi-clock mr-2"></i>
                        Express withdrawals are typically processed within 1-24 hours.
                    </Message>

                    <div class="flex justify-between mt-6">
                        <Button label="Back" icon="pi pi-arrow-left" severity="secondary" outlined @click="prevStep" />
                        <Button label="Submit Withdrawal" icon="pi pi-check" severity="success" 
                                :loading="form.processing" @click="showConfirmDialog = true" />
                    </div>
                </template>
            </Card>
        </div>

        <!-- Confirmation Dialog -->
        <Dialog v-model:visible="showConfirmDialog" modal header="Confirm Withdrawal" :style="{ width: '450px' }">
            <div class="text-center">
                <i class="pi pi-exclamation-triangle text-5xl text-yellow-500 mb-4"></i>
                <p class="mb-4 text-gray-600 dark:text-gray-400">
                    Are you sure you want to withdraw 
                    <strong class="text-gray-900 dark:text-white">{{ settings.currency_symbol }}{{ form.amount?.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</strong>
                    via {{ selectedGateway?.name }}?
                </p>
                <p class="text-sm text-gray-500">
                    This action cannot be undone.
                </p>
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" outlined @click="showConfirmDialog = false" />
                <Button label="Confirm Withdrawal" severity="success" :loading="form.processing" @click="submitWithdrawal" />
            </template>
        </Dialog>
    </div>
</template>
