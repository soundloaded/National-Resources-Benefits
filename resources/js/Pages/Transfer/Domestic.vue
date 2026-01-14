<script setup>
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import Textarea from 'primevue/textarea';
import Message from 'primevue/message';
import Divider from 'primevue/divider';
import Steps from 'primevue/steps';
import Dialog from 'primevue/dialog';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    accounts: Array,
    settings: Object,
    savedBanks: Array,
});

const page = usePage();
const flash = computed(() => page.props.flash || {});

// Current step in the wizard
const currentStep = ref(0);
const steps = [
    { label: 'Source' },
    { label: 'Bank Details' },
    { label: 'Amount' },
    { label: 'Confirm' },
];

// Confirmation dialog
const showConfirmDialog = ref(false);

// Form
const form = useForm({
    from_account_id: '',
    amount: null,
    bank_name: '',
    account_holder_name: '',
    account_number: '',
    routing_number: '',
    account_type: 'checking',
    description: '',
});

// Account type options
const accountTypes = [
    { label: 'Checking', value: 'checking' },
    { label: 'Savings', value: 'savings' },
];

// Common bank names
const commonBanks = [
    'Bank of America',
    'Chase',
    'Wells Fargo',
    'Citibank',
    'US Bank',
    'PNC Bank',
    'Capital One',
    'TD Bank',
    'Truist',
    'Other',
];

// Selected account
const selectedAccount = computed(() => {
    return props.accounts.find(a => a.id === form.from_account_id);
});

// Fee calculation
const fee = computed(() => {
    if (!form.amount) return 0;
    const fixed = props.settings.domestic_transfer_fee_fixed || 0;
    const percentage = props.settings.domestic_transfer_fee_percentage || 0;
    return fixed + (percentage / 100 * form.amount);
});

const totalDebit = computed(() => {
    return (form.amount || 0) + fee.value;
});

// Validation for each step
const canProceedStep0 = computed(() => !!form.from_account_id);
const canProceedStep1 = computed(() => 
    form.bank_name && 
    form.account_holder_name && 
    form.account_number && 
    form.routing_number?.length === 9 &&
    form.account_type
);
const canProceedStep2 = computed(() => {
    if (!form.amount) return false;
    if (form.amount < (props.settings.domestic_transfer_min || 10)) return false;
    if (form.amount > (props.settings.domestic_transfer_max || 100000)) return false;
    if (selectedAccount.value && totalDebit.value > selectedAccount.value.balance) return false;
    return true;
});

// Navigation
const nextStep = () => {
    if (currentStep.value < 3) {
        currentStep.value++;
    }
};

const prevStep = () => {
    if (currentStep.value > 0) {
        currentStep.value--;
    }
};

// Submit
const submitTransfer = () => {
    form.post(route('transfer.store-domestic'), {
        onSuccess: () => {
            showConfirmDialog.value = false;
        },
    });
};

// Format routing number with dashes
const formatRoutingNumber = (e) => {
    let value = e.target.value.replace(/\D/g, '').slice(0, 9);
    form.routing_number = value;
};

// Mask account number for display
const maskedAccountNumber = computed(() => {
    if (!form.account_number) return '';
    return '****' + form.account_number.slice(-4);
});
</script>

<template>
    <Head title="Domestic Transfer" />

    <div class="max-w-3xl mx-auto">
        <!-- Page Header -->
        <div class="mb-6">
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
                <Link :href="route('transfer.index')" class="hover:text-primary-600">Transfer</Link>
                <i class="pi pi-chevron-right text-xs"></i>
                <span>Domestic Transfer</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Domestic Bank Transfer</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Transfer funds to any US bank account</p>
        </div>

        <!-- Flash Messages -->
        <Message v-if="flash.error" severity="error" :closable="true" class="mb-6">
            {{ flash.error }}
        </Message>

        <!-- Info Banner -->
        <Message severity="info" :closable="false" class="mb-6">
            <div class="flex items-start gap-3">
                <i class="pi pi-info-circle text-lg mt-0.5"></i>
                <div>
                    <p class="font-medium">Domestic transfers typically arrive in {{ settings.domestic_processing_days || '1-3' }} business days</p>
                    <p class="text-sm mt-1">Transfer fee: {{ settings.currency_symbol }}{{ settings.domestic_transfer_fee_fixed || 5 }}
                        <span v-if="settings.domestic_transfer_fee_percentage"> + {{ settings.domestic_transfer_fee_percentage }}%</span>
                    </p>
                </div>
            </div>
        </Message>

        <!-- Steps -->
        <div class="mb-6">
            <Steps :model="steps" :activeStep="currentStep" :readonly="true" />
        </div>

        <Card>
            <template #content>
                <!-- Step 0: Source Account -->
                <div v-show="currentStep === 0">
                    <h3 class="text-lg font-semibold mb-4">Select Source Account</h3>
                    
                    <div class="grid gap-3">
                        <div 
                            v-for="account in accounts" 
                            :key="account.id"
                            @click="form.from_account_id = account.id"
                            class="relative p-4 border-2 rounded-lg cursor-pointer transition-all duration-300 ease-in-out"
                            :class="form.from_account_id === account.id 
                                ? 'border-green-500 bg-green-50 dark:bg-green-900/20 shadow-md shadow-green-500/20' 
                                : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'"
                        >
                            <!-- Checkmark indicator -->
                            <div 
                                class="absolute top-3 right-3 w-6 h-6 rounded-full flex items-center justify-center transition-all duration-300 border-2"
                                :class="form.from_account_id === account.id 
                                    ? 'bg-green-500 border-green-500 scale-100' 
                                    : 'bg-transparent border-gray-300 dark:border-gray-600 scale-90'"
                            >
                                <i 
                                    class="pi pi-check text-xs font-bold transition-all duration-300"
                                    :class="form.from_account_id === account.id 
                                        ? 'text-white opacity-100' 
                                        : 'opacity-0'"
                                ></i>
                            </div>

                            <div class="flex justify-between items-center pr-8">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ account.name }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Available Balance</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xl font-bold" :class="form.from_account_id === account.id ? 'text-green-600' : 'text-primary-600'">
                                        {{ settings.currency_symbol }}{{ account.balance_formatted }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end mt-6">
                        <Button 
                            label="Next" 
                            icon="pi pi-arrow-right" 
                            iconPos="right"
                            @click="nextStep"
                            :disabled="!canProceedStep0"
                        />
                    </div>
                </div>

                <!-- Step 1: Bank Details -->
                <div v-show="currentStep === 1">
                    <h3 class="text-lg font-semibold mb-4">Recipient Bank Details</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bank Name</label>
                            <Dropdown 
                                v-model="form.bank_name" 
                                :options="commonBanks" 
                                placeholder="Select or type bank name"
                                editable
                                class="w-full"
                                :class="{ 'p-invalid': form.errors.bank_name }"
                            />
                            <small v-if="form.errors.bank_name" class="text-red-500">{{ form.errors.bank_name }}</small>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Account Holder Name</label>
                            <InputText 
                                v-model="form.account_holder_name" 
                                placeholder="Full name as it appears on the account"
                                class="w-full"
                                :class="{ 'p-invalid': form.errors.account_holder_name }"
                            />
                            <small v-if="form.errors.account_holder_name" class="text-red-500">{{ form.errors.account_holder_name }}</small>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Routing Number</label>
                                <InputText 
                                    :modelValue="form.routing_number"
                                    @input="formatRoutingNumber"
                                    placeholder="9-digit routing number"
                                    class="w-full"
                                    maxlength="9"
                                    :class="{ 'p-invalid': form.errors.routing_number }"
                                />
                                <small class="text-gray-500">{{ form.routing_number?.length || 0 }}/9 digits</small>
                                <small v-if="form.errors.routing_number" class="text-red-500 block">{{ form.errors.routing_number }}</small>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Account Type</label>
                                <Dropdown 
                                    v-model="form.account_type" 
                                    :options="accountTypes" 
                                    optionLabel="label"
                                    optionValue="value"
                                    class="w-full"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Account Number</label>
                            <InputText 
                                v-model="form.account_number" 
                                placeholder="Bank account number"
                                class="w-full"
                                :class="{ 'p-invalid': form.errors.account_number }"
                            />
                            <small v-if="form.errors.account_number" class="text-red-500">{{ form.errors.account_number }}</small>
                        </div>
                    </div>

                    <div class="flex justify-between mt-6">
                        <Button 
                            label="Back" 
                            icon="pi pi-arrow-left" 
                            severity="secondary"
                            @click="prevStep"
                        />
                        <Button 
                            label="Next" 
                            icon="pi pi-arrow-right" 
                            iconPos="right"
                            @click="nextStep"
                            :disabled="!canProceedStep1"
                        />
                    </div>
                </div>

                <!-- Step 2: Amount -->
                <div v-show="currentStep === 2">
                    <h3 class="text-lg font-semibold mb-4">Transfer Amount</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Amount (USD)</label>
                            <InputNumber 
                                v-model="form.amount" 
                                mode="currency" 
                                currency="USD"
                                locale="en-US"
                                :min="settings.domestic_transfer_min || 10"
                                :max="settings.domestic_transfer_max || 100000"
                                class="w-full"
                                :class="{ 'p-invalid': form.errors.amount }"
                            />
                            <div class="flex justify-between text-sm text-gray-500 mt-1">
                                <span>Min: {{ settings.currency_symbol }}{{ settings.domestic_transfer_min || 10 }}</span>
                                <span>Max: {{ settings.currency_symbol }}{{ (settings.domestic_transfer_max || 100000).toLocaleString() }}</span>
                            </div>
                            <small v-if="form.errors.amount" class="text-red-500">{{ form.errors.amount }}</small>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description (Optional)</label>
                            <Textarea 
                                v-model="form.description" 
                                placeholder="Add a note for your records..."
                                rows="2"
                                class="w-full"
                            />
                        </div>

                        <!-- Fee Summary -->
                        <div v-if="form.amount" class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <h4 class="font-medium mb-3">Transfer Summary</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Transfer Amount</span>
                                    <span>{{ settings.currency_symbol }}{{ form.amount.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Transfer Fee</span>
                                    <span class="text-orange-500">{{ settings.currency_symbol }}{{ fee.toFixed(2) }}</span>
                                </div>
                                <Divider />
                                <div class="flex justify-between font-semibold">
                                    <span>Total Debit</span>
                                    <span>{{ settings.currency_symbol }}{{ totalDebit.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</span>
                                </div>
                                <div v-if="selectedAccount" class="flex justify-between text-gray-500">
                                    <span>Remaining Balance</span>
                                    <span :class="{ 'text-red-500': totalDebit > selectedAccount.balance }">
                                        {{ settings.currency_symbol }}{{ (selectedAccount.balance - totalDebit).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between mt-6">
                        <Button 
                            label="Back" 
                            icon="pi pi-arrow-left" 
                            severity="secondary"
                            @click="prevStep"
                        />
                        <Button 
                            label="Review Transfer" 
                            icon="pi pi-arrow-right" 
                            iconPos="right"
                            @click="nextStep"
                            :disabled="!canProceedStep2"
                        />
                    </div>
                </div>

                <!-- Step 3: Confirm -->
                <div v-show="currentStep === 3">
                    <h3 class="text-lg font-semibold mb-4">Confirm Transfer</h3>
                    
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500">From Account</p>
                                <p class="font-medium">{{ selectedAccount?.name }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Amount</p>
                                <p class="font-bold text-xl text-primary-600">
                                    {{ settings.currency_symbol }}{{ form.amount?.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                                </p>
                            </div>
                        </div>

                        <Divider />

                        <div>
                            <p class="text-sm text-gray-500 mb-2">Recipient Bank Details</p>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <span class="text-gray-500">Bank:</span>
                                    <span class="ml-2 font-medium">{{ form.bank_name }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Account Type:</span>
                                    <span class="ml-2 font-medium capitalize">{{ form.account_type }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Account Holder:</span>
                                    <span class="ml-2 font-medium">{{ form.account_holder_name }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Account Number:</span>
                                    <span class="ml-2 font-medium">{{ maskedAccountNumber }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Routing Number:</span>
                                    <span class="ml-2 font-medium">{{ form.routing_number }}</span>
                                </div>
                            </div>
                        </div>

                        <Divider />

                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Transfer Amount</span>
                                <span>{{ settings.currency_symbol }}{{ form.amount?.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Fee</span>
                                <span class="text-orange-500">{{ settings.currency_symbol }}{{ fee.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between font-bold text-lg pt-2 border-t">
                                <span>Total</span>
                                <span>{{ settings.currency_symbol }}{{ totalDebit.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</span>
                            </div>
                        </div>

                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3">
                            <div class="flex items-start gap-2">
                                <i class="pi pi-clock text-yellow-600"></i>
                                <div class="text-sm">
                                    <p class="font-medium text-yellow-800 dark:text-yellow-200">Processing Time</p>
                                    <p class="text-yellow-700 dark:text-yellow-300">
                                        Domestic transfers typically arrive within {{ settings.domestic_processing_days || '1-3' }} business days.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between mt-6">
                        <Button 
                            label="Back" 
                            icon="pi pi-arrow-left" 
                            severity="secondary"
                            @click="prevStep"
                        />
                        <Button 
                            label="Confirm & Send" 
                            icon="pi pi-check" 
                            @click="showConfirmDialog = true"
                            :disabled="form.processing"
                        />
                    </div>
                </div>
            </template>
        </Card>

        <!-- Confirmation Dialog -->
        <Dialog 
            v-model:visible="showConfirmDialog" 
            header="Confirm Domestic Transfer" 
            :modal="true"
            :style="{ width: '400px' }"
        >
            <div class="text-center">
                <i class="pi pi-exclamation-triangle text-5xl text-yellow-500 mb-4"></i>
                <p class="mb-4">
                    You are about to transfer <strong>{{ settings.currency_symbol }}{{ form.amount?.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</strong> to:
                </p>
                <p class="font-semibold text-lg">{{ form.account_holder_name }}</p>
                <p class="text-gray-500">{{ form.bank_name }}</p>
                <p class="text-sm text-gray-400 mt-2">Account ending in {{ form.account_number?.slice(-4) }}</p>
            </div>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button 
                        label="Cancel" 
                        severity="secondary" 
                        @click="showConfirmDialog = false"
                    />
                    <Button 
                        label="Confirm Transfer" 
                        icon="pi pi-check"
                        @click="submitTransfer"
                        :loading="form.processing"
                    />
                </div>
            </template>
        </Dialog>
    </div>
</template>
