<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Select from 'primevue/select';
import InputNumber from 'primevue/inputnumber';
import Message from 'primevue/message';
import Divider from 'primevue/divider';
import Dialog from 'primevue/dialog';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    accounts: Array,
    methods: Array,
    settings: Object,
});

const selectedMethod = ref(null);
const selectedAccount = ref(props.accounts.length > 0 ? props.accounts[0] : null);
const amount = ref(null);
const showConfirmDialog = ref(false);

// Compute effective limits (method limits override global if set)
const effectiveLimits = computed(() => {
    const method = selectedMethod.value;
    return {
        min: method?.min_limit > 0 ? method.min_limit : props.settings.deposit_min,
        max: method?.max_limit > 0 ? method.max_limit : props.settings.deposit_max,
    };
});

// Calculate fee
const calculatedFee = computed(() => {
    if (!selectedMethod.value || !amount.value) return 0;
    const method = selectedMethod.value;
    const fixedFee = parseFloat(method.fee_fixed) || 0;
    const percentageFee = ((parseFloat(method.fee_percentage) || 0) / 100) * amount.value;
    return fixedFee + percentageFee;
});

// Total amount user pays
const totalAmount = computed(() => {
    return (amount.value || 0) + calculatedFee.value;
});

// Amount to be credited
const creditAmount = computed(() => {
    return amount.value || 0;
});

// Form validation
const isValid = computed(() => {
    if (!selectedMethod.value || !selectedAccount.value || !amount.value) return false;
    if (amount.value < effectiveLimits.value.min) return false;
    if (effectiveLimits.value.max && amount.value > effectiveLimits.value.max) return false;
    return true;
});

// Form for submission
const form = useForm({
    gateway_id: null,
    account_id: null,
    amount: null,
});

const openConfirmDialog = () => {
    if (!isValid.value) return;
    showConfirmDialog.value = true;
};

const submitDeposit = () => {
    form.gateway_id = selectedMethod.value.id;
    form.account_id = selectedAccount.value.id;
    form.amount = amount.value;
    
    form.post(route('payment.deposit'), {
        onSuccess: () => {
            showConfirmDialog.value = false;
        },
    });
};

// Copy to clipboard helper
const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
};
</script>

<template>
    <Head title="Manual Deposit" />

    <div class="max-w-5xl mx-auto">
        <!-- Page Header -->
        <div class="mb-6">
            <Link :href="route('deposit.index')" class="text-primary-600 hover:text-primary-700 text-sm mb-2 inline-flex items-center gap-1">
                <i class="pi pi-arrow-left"></i>
                Back to Deposit Methods
            </Link>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Manual Deposit</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Transfer funds directly to our bank account</p>
        </div>

        <!-- No Methods Available -->
        <Message v-if="methods.length === 0" severity="warn" :closable="false" class="mb-6">
            <div class="flex items-center gap-2">
                <i class="pi pi-exclamation-triangle"></i>
                <span>No manual deposit methods are currently available. Please try instant deposit or contact support.</span>
            </div>
        </Message>

        <div v-else class="grid lg:grid-cols-3 gap-6">
            <!-- Method Selection & Amount Entry -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Step 1: Select Method -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center font-bold text-sm">1</span>
                            Select Deposit Method
                        </div>
                    </template>
                    <template #content>
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div 
                                v-for="method in methods" 
                                :key="method.id"
                                @click="selectedMethod = method"
                                :class="[
                                    'p-4 border rounded-lg cursor-pointer transition-all',
                                    selectedMethod?.id === method.id 
                                        ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' 
                                        : 'border-gray-200 dark:border-gray-700 hover:border-primary-300'
                                ]"
                            >
                                <div class="flex items-start gap-3">
                                    <div class="w-12 h-12 bg-gray-100 dark:bg-gray-800 rounded-lg flex items-center justify-center">
                                        <img v-if="method.logo" :src="method.logo" :alt="method.name" class="w-8 h-8 object-contain">
                                        <i v-else class="pi pi-building text-xl text-gray-500"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-gray-900 dark:text-white">{{ method.name }}</h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Min: {{ settings.currency_symbol }}{{ method.min_limit || settings.deposit_min }}
                                            <span v-if="method.max_limit"> | Max: {{ settings.currency_symbol }}{{ method.max_limit }}</span>
                                        </p>
                                        <p v-if="method.fee_fixed > 0 || method.fee_percentage > 0" class="text-xs text-orange-500 mt-1">
                                            Fee: 
                                            <span v-if="method.fee_fixed > 0">{{ settings.currency_symbol }}{{ method.fee_fixed }}</span>
                                            <span v-if="method.fee_fixed > 0 && method.fee_percentage > 0"> + </span>
                                            <span v-if="method.fee_percentage > 0">{{ method.fee_percentage }}%</span>
                                        </p>
                                    </div>
                                    <i v-if="selectedMethod?.id === method.id" class="pi pi-check-circle text-primary-500"></i>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Step 2: Select Account -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center font-bold text-sm">2</span>
                            Select Account
                        </div>
                    </template>
                    <template #content>
                        <Select 
                            v-model="selectedAccount" 
                            :options="accounts" 
                            optionLabel="name"
                            placeholder="Select an account"
                            class="w-full"
                        >
                            <template #option="{ option }">
                                <div class="flex justify-between items-center w-full">
                                    <span>{{ option.name }}</span>
                                    <span class="text-gray-500">{{ option.currency }} {{ option.balance }}</span>
                                </div>
                            </template>
                        </Select>
                    </template>
                </Card>

                <!-- Step 3: Enter Amount -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center font-bold text-sm">3</span>
                            Enter Amount
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Amount to Deposit
                                </label>
                                <InputNumber 
                                    v-model="amount" 
                                    :min="effectiveLimits.min"
                                    :max="effectiveLimits.max || 999999999"
                                    mode="currency" 
                                    currency="USD" 
                                    locale="en-US"
                                    class="w-full"
                                    placeholder="Enter amount"
                                />
                                <p class="text-xs text-gray-500 mt-1">
                                    Limits: {{ settings.currency_symbol }}{{ effectiveLimits.min }} - 
                                    {{ effectiveLimits.max ? settings.currency_symbol + effectiveLimits.max : 'No max' }}
                                </p>
                            </div>

                            <!-- Fee Breakdown -->
                            <div v-if="amount && calculatedFee > 0" class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Deposit Amount</span>
                                    <span>{{ settings.currency_symbol }}{{ amount.toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between text-sm text-orange-500">
                                    <span>Processing Fee</span>
                                    <span>{{ settings.currency_symbol }}{{ calculatedFee.toFixed(2) }}</span>
                                </div>
                                <Divider class="my-2" />
                                <div class="flex justify-between font-semibold">
                                    <span>Amount to be Credited</span>
                                    <span class="text-green-600">{{ settings.currency_symbol }}{{ creditAmount.toFixed(2) }}</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Bank Details & Summary Sidebar -->
            <div class="space-y-6">
                <!-- Bank Details Card -->
                <Card v-if="selectedMethod">
                    <template #title>
                        <i class="pi pi-building mr-2"></i>
                        Bank Details
                    </template>
                    <template #content>
                        <div class="space-y-3">
                            <div v-for="(value, key) in selectedMethod.bank_details" :key="key" class="flex justify-between items-start">
                                <span class="text-sm text-gray-500 dark:text-gray-400 capitalize">{{ key.replace(/_/g, ' ') }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white text-right">{{ value }}</span>
                                    <Button 
                                        icon="pi pi-copy" 
                                        size="small" 
                                        text 
                                        rounded
                                        @click="copyToClipboard(value)"
                                        v-tooltip="'Copy'"
                                    />
                                </div>
                            </div>
                            
                            <Message v-if="Object.keys(selectedMethod.bank_details || {}).length === 0" severity="info" :closable="false">
                                Bank details will be displayed after initiating the deposit.
                            </Message>
                        </div>

                        <!-- Instructions -->
                        <div v-if="selectedMethod.instructions" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <h4 class="font-medium text-gray-900 dark:text-white mb-2">Instructions</h4>
                            <div class="text-sm text-gray-600 dark:text-gray-400 prose prose-sm dark:prose-invert" v-html="selectedMethod.instructions"></div>
                        </div>
                    </template>
                </Card>

                <!-- Summary Card -->
                <Card>
                    <template #title>
                        <i class="pi pi-file-edit mr-2"></i>
                        Deposit Summary
                    </template>
                    <template #content>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Method</span>
                                <span class="font-medium">{{ selectedMethod?.name || 'Not selected' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Account</span>
                                <span class="font-medium">{{ selectedAccount?.name || 'Not selected' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Amount</span>
                                <span class="font-medium">{{ amount ? settings.currency_symbol + amount.toFixed(2) : '-' }}</span>
                            </div>
                            <Divider />
                            <div class="flex justify-between text-lg font-bold">
                                <span>To be Credited</span>
                                <span class="text-green-600">{{ settings.currency_symbol }}{{ creditAmount.toFixed(2) }}</span>
                            </div>
                        </div>

                        <Button 
                            label="Initiate Deposit" 
                            icon="pi pi-check"
                            class="w-full mt-4"
                            :disabled="!isValid"
                            :loading="form.processing"
                            @click="openConfirmDialog"
                        />

                        <p v-if="!isValid" class="text-xs text-center text-gray-500 mt-2">
                            Please complete all fields to continue
                        </p>
                    </template>
                </Card>
            </div>
        </div>
    </div>

    <!-- Confirmation Dialog -->
    <Dialog v-model:visible="showConfirmDialog" modal header="Confirm Deposit" :style="{ width: '450px' }">
        <div class="space-y-4">
            <p class="text-gray-600 dark:text-gray-400">
                You are about to initiate a manual deposit. Please confirm the details below:
            </p>
            
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Method</span>
                    <span class="font-medium">{{ selectedMethod?.name }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Account</span>
                    <span class="font-medium">{{ selectedAccount?.name }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Amount to Deposit</span>
                    <span class="font-medium">{{ settings.currency_symbol }}{{ amount?.toFixed(2) }}</span>
                </div>
                <div v-if="calculatedFee > 0" class="flex justify-between text-sm text-orange-500">
                    <span>Fee</span>
                    <span>{{ settings.currency_symbol }}{{ calculatedFee.toFixed(2) }}</span>
                </div>
                <Divider class="my-2" />
                <div class="flex justify-between font-bold">
                    <span>To be Credited</span>
                    <span class="text-green-600">{{ settings.currency_symbol }}{{ creditAmount.toFixed(2) }}</span>
                </div>
            </div>

            <Message severity="info" :closable="false">
                After initiating, you'll receive bank details to complete your transfer. Your deposit will be credited once we confirm receipt.
            </Message>
        </div>

        <template #footer>
            <Button label="Cancel" severity="secondary" @click="showConfirmDialog = false" />
            <Button label="Confirm & Continue" icon="pi pi-check" :loading="form.processing" @click="submitDeposit" />
        </template>
    </Dialog>
</template>
