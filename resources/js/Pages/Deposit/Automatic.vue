<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Select from 'primevue/select';
import InputNumber from 'primevue/inputnumber';
import Message from 'primevue/message';
import Divider from 'primevue/divider';
import Dialog from 'primevue/dialog';
import ProgressSpinner from 'primevue/progressspinner';
import axios from 'axios';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    accounts: Array,
    gateways: Array,
    settings: Object,
});

const selectedGateway = ref(null);
const selectedAccount = ref(props.accounts.length > 0 ? props.accounts[0] : null);
const amount = ref(null);
const showConfirmDialog = ref(false);
const isRedirecting = ref(false);
const isProcessing = ref(false);
const errorMessage = ref(null);

// Compute effective limits (gateway limits override global if set)
const effectiveLimits = computed(() => {
    const gateway = selectedGateway.value;
    return {
        min: gateway?.min_limit > 0 ? gateway.min_limit : props.settings.deposit_min,
        max: gateway?.max_limit > 0 ? gateway.max_limit : props.settings.deposit_max,
    };
});

// Calculate fee
const calculatedFee = computed(() => {
    if (!selectedGateway.value || !amount.value) return 0;
    const gateway = selectedGateway.value;
    const fixedFee = parseFloat(gateway.fee_fixed) || 0;
    const percentageFee = ((parseFloat(gateway.fee_percentage) || 0) / 100) * amount.value;
    return fixedFee + percentageFee;
});

// Total amount user pays (including fee)
const totalToPay = computed(() => {
    return (amount.value || 0) + calculatedFee.value;
});

// Amount to be credited
const creditAmount = computed(() => {
    return amount.value || 0;
});

// Form validation
const isValid = computed(() => {
    if (!selectedGateway.value || !selectedAccount.value || !amount.value) return false;
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
    errorMessage.value = null;
    showConfirmDialog.value = true;
};

const submitDeposit = async () => {
    isProcessing.value = true;
    isRedirecting.value = true;
    errorMessage.value = null;
    
    try {
        // Use axios to make the request and get the redirect URL
        // We explicitly set headers to ensure Laravel returns JSON instead of a redirect
        const response = await axios.post(route('payment.deposit'), {
            gateway_id: selectedGateway.value.id,
            account_id: selectedAccount.value.id,
            amount: amount.value,
        }, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        
        // Check if we got a redirect URL
        if (response.data.redirect_url) {
            // Redirect to the payment gateway
            window.location.href = response.data.redirect_url;
        } else {
            // If no redirect URL, something went wrong
            throw new Error('No redirect URL received from server');
        }
    } catch (error) {
        isRedirecting.value = false;
        isProcessing.value = false;
        
        // Handle validation errors
        if (error.response?.status === 422) {
            const errors = error.response.data.errors;
            errorMessage.value = Object.values(errors).flat().join(', ');
        } else if (error.response?.data?.message) {
            errorMessage.value = error.response.data.message;
        } else if (error.response?.data?.error) {
            errorMessage.value = error.response.data.error;
        } else {
            errorMessage.value = error.message || 'An error occurred. Please try again.';
        }
    }
};

// Gateway icon mapping
const getGatewayIcon = (code) => {
    const icons = {
        'stripe': 'pi pi-credit-card',
        'paypal': 'pi pi-paypal',
        'paystack': 'pi pi-wallet',
        'flutterwave': 'pi pi-bolt',
        'monnify': 'pi pi-money-bill',
    };
    return icons[code] || 'pi pi-credit-card';
};

// Gateway color mapping
const getGatewayColor = (code) => {
    const colors = {
        'stripe': 'from-purple-500 to-indigo-600',
        'paypal': 'from-blue-500 to-blue-700',
        'paystack': 'from-teal-500 to-green-600',
        'flutterwave': 'from-orange-500 to-yellow-500',
        'monnify': 'from-blue-600 to-cyan-500',
    };
    return colors[code] || 'from-gray-500 to-gray-700';
};
</script>

<template>
    <Head title="Instant Deposit" />

    <div class="max-w-5xl mx-auto">
        <!-- Page Header -->
        <div class="mb-6">
            <Link :href="route('deposit.index')" class="text-primary-600 hover:text-primary-700 text-sm mb-2 inline-flex items-center gap-1">
                <i class="pi pi-arrow-left"></i>
                Back to Deposit Methods
            </Link>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Instant Deposit</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Deposit instantly using our secure payment gateways</p>
        </div>

        <!-- No Gateways Available -->
        <Message v-if="gateways.length === 0" severity="warn" :closable="false" class="mb-6">
            <div class="flex items-center gap-2">
                <i class="pi pi-exclamation-triangle"></i>
                <span>No instant deposit methods are currently available. Please try manual deposit or contact support.</span>
            </div>
        </Message>

        <div v-else class="grid lg:grid-cols-3 gap-6">
            <!-- Gateway Selection & Amount Entry -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Step 1: Select Gateway -->
                <Card>
                    <template #title>
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center font-bold text-sm">1</span>
                            Select Payment Gateway
                        </div>
                    </template>
                    <template #content>
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div 
                                v-for="gateway in gateways" 
                                :key="gateway.id"
                                @click="selectedGateway = gateway"
                                :class="[
                                    'relative overflow-hidden rounded-xl cursor-pointer transition-all transform hover:scale-[1.02]',
                                    selectedGateway?.id === gateway.id 
                                        ? 'ring-2 ring-primary-500 ring-offset-2' 
                                        : 'hover:shadow-lg'
                                ]"
                            >
                                <div :class="['bg-gradient-to-r p-4 text-white', getGatewayColor(gateway.code)]">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                                            <img v-if="gateway.logo" :src="gateway.logo" :alt="gateway.name" class="w-8 h-8 object-contain">
                                            <i v-else :class="[getGatewayIcon(gateway.code), 'text-2xl']"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-lg">{{ gateway.name }}</h4>
                                            <p class="text-white/80 text-xs">
                                                Min: {{ settings.currency_symbol }}{{ gateway.min_limit || settings.deposit_min }}
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3 pt-3 border-t border-white/20 flex justify-between text-sm">
                                        <span class="text-white/80">
                                            <i class="pi pi-bolt mr-1"></i>
                                            Instant
                                        </span>
                                        <span v-if="gateway.fee_fixed > 0 || gateway.fee_percentage > 0" class="text-white/80">
                                            Fee: 
                                            <span v-if="gateway.fee_fixed > 0">{{ settings.currency_symbol }}{{ gateway.fee_fixed }}</span>
                                            <span v-if="gateway.fee_fixed > 0 && gateway.fee_percentage > 0"> + </span>
                                            <span v-if="gateway.fee_percentage > 0">{{ gateway.fee_percentage }}%</span>
                                        </span>
                                        <span v-else class="text-white/80">No fee</span>
                                    </div>
                                </div>
                                
                                <!-- Selected indicator -->
                                <div v-if="selectedGateway?.id === gateway.id" class="absolute top-2 right-2">
                                    <span class="w-6 h-6 bg-white rounded-full flex items-center justify-center">
                                        <i class="pi pi-check text-primary-600 text-sm"></i>
                                    </span>
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

                            <!-- Quick Amount Buttons -->
                            <div class="flex flex-wrap gap-2">
                                <Button 
                                    v-for="quickAmount in [50, 100, 250, 500, 1000]"
                                    :key="quickAmount"
                                    :label="settings.currency_symbol + quickAmount"
                                    size="small"
                                    :severity="amount === quickAmount ? 'primary' : 'secondary'"
                                    :outlined="amount !== quickAmount"
                                    @click="amount = quickAmount"
                                />
                            </div>

                            <!-- Fee Breakdown -->
                            <div v-if="amount && selectedGateway" class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Deposit Amount</span>
                                    <span>{{ settings.currency_symbol }}{{ amount.toFixed(2) }}</span>
                                </div>
                                <div v-if="calculatedFee > 0" class="flex justify-between text-sm text-orange-500">
                                    <span>Processing Fee</span>
                                    <span>{{ settings.currency_symbol }}{{ calculatedFee.toFixed(2) }}</span>
                                </div>
                                <Divider class="my-2" />
                                <div class="flex justify-between font-semibold">
                                    <span>Total to Pay</span>
                                    <span>{{ settings.currency_symbol }}{{ totalToPay.toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between font-semibold text-green-600">
                                    <span>Amount Credited</span>
                                    <span>{{ settings.currency_symbol }}{{ creditAmount.toFixed(2) }}</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Summary Sidebar -->
            <div class="space-y-6">
                <!-- Security Badge -->
                <Card>
                    <template #content>
                        <div class="text-center py-2">
                            <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-3">
                                <i class="pi pi-shield text-3xl text-green-600"></i>
                            </div>
                            <h4 class="font-semibold text-gray-900 dark:text-white">Secure Payment</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Your payment is processed securely through our trusted payment partners.
                            </p>
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
                                <span class="text-gray-500 dark:text-gray-400">Gateway</span>
                                <span class="font-medium">{{ selectedGateway?.name || 'Not selected' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Account</span>
                                <span class="font-medium">{{ selectedAccount?.name || 'Not selected' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Amount</span>
                                <span class="font-medium">{{ amount ? settings.currency_symbol + amount.toFixed(2) : '-' }}</span>
                            </div>
                            <div v-if="calculatedFee > 0" class="flex justify-between text-orange-500">
                                <span>Fee</span>
                                <span>{{ settings.currency_symbol }}{{ calculatedFee.toFixed(2) }}</span>
                            </div>
                            <Divider />
                            <div class="flex justify-between text-lg font-bold">
                                <span>Total to Pay</span>
                                <span>{{ settings.currency_symbol }}{{ totalToPay.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between text-green-600 font-semibold">
                                <span>To be Credited</span>
                                <span>{{ settings.currency_symbol }}{{ creditAmount.toFixed(2) }}</span>
                            </div>
                        </div>

                        <Button 
                            label="Proceed to Payment" 
                            icon="pi pi-external-link"
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

                <!-- Info Card -->
                <Card>
                    <template #content>
                        <div class="text-sm space-y-3">
                            <div class="flex gap-3">
                                <i class="pi pi-info-circle text-primary-600 mt-0.5"></i>
                                <p class="text-gray-600 dark:text-gray-400">
                                    You'll be redirected to a secure payment page to complete your deposit.
                                </p>
                            </div>
                            <div class="flex gap-3">
                                <i class="pi pi-clock text-primary-600 mt-0.5"></i>
                                <p class="text-gray-600 dark:text-gray-400">
                                    Funds are credited instantly upon successful payment.
                                </p>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </div>

    <!-- Confirmation Dialog -->
    <Dialog v-model:visible="showConfirmDialog" modal header="Confirm Payment" :style="{ width: '450px' }" :closable="!isRedirecting">
        <!-- Redirecting State -->
        <div v-if="isRedirecting" class="text-center py-6">
            <ProgressSpinner class="mb-4" />
            <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Redirecting to Payment...</h4>
            <p class="text-gray-500">Please wait while we connect you to {{ selectedGateway?.name }}.</p>
        </div>

        <!-- Confirmation State -->
        <div v-else class="space-y-4">
            <!-- Error Message -->
            <Message v-if="errorMessage" severity="error" :closable="false" class="mb-4">
                {{ errorMessage }}
            </Message>
            
            <p class="text-gray-600 dark:text-gray-400">
                You'll be redirected to <strong>{{ selectedGateway?.name }}</strong> to complete your payment securely.
            </p>
            
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Gateway</span>
                    <span class="font-medium">{{ selectedGateway?.name }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Account</span>
                    <span class="font-medium">{{ selectedAccount?.name }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Amount</span>
                    <span class="font-medium">{{ settings.currency_symbol }}{{ amount?.toFixed(2) }}</span>
                </div>
                <div v-if="calculatedFee > 0" class="flex justify-between text-sm text-orange-500">
                    <span>Fee</span>
                    <span>{{ settings.currency_symbol }}{{ calculatedFee.toFixed(2) }}</span>
                </div>
                <Divider class="my-2" />
                <div class="flex justify-between font-bold">
                    <span>Total to Pay</span>
                    <span>{{ settings.currency_symbol }}{{ totalToPay.toFixed(2) }}</span>
                </div>
                <div class="flex justify-between font-bold text-green-600">
                    <span>To be Credited</span>
                    <span>{{ settings.currency_symbol }}{{ creditAmount.toFixed(2) }}</span>
                </div>
            </div>

            <Message severity="info" :closable="false">
                <span class="text-sm">Do not close this window until payment is complete. You'll be redirected back automatically.</span>
            </Message>
        </div>

        <template #footer v-if="!isRedirecting">
            <Button label="Cancel" severity="secondary" @click="showConfirmDialog = false" />
            <Button 
                label="Pay Now" 
                icon="pi pi-external-link" 
                :loading="isProcessing" 
                @click="submitDeposit" 
            />
        </template>
    </Dialog>
</template>
