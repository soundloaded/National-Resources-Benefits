<script setup>
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
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
    countries: Array,
});

const page = usePage();
const flash = computed(() => page.props.flash || {});

// Current step in the wizard
const currentStep = ref(0);
const steps = [
    { label: 'Source' },
    { label: 'Beneficiary' },
    { label: 'Bank' },
    { label: 'Amount' },
    { label: 'Confirm' },
];

// Confirmation dialog
const showConfirmDialog = ref(false);

// Form
const form = useForm({
    from_account_id: '',
    amount: null,
    // Beneficiary details
    beneficiary_name: '',
    beneficiary_address: '',
    beneficiary_country: '',
    // Bank details
    bank_name: '',
    bank_address: '',
    swift_code: '',
    iban: '',
    account_number: '',
    // Purpose and description
    purpose: '',
    description: '',
});

// Purpose options
const purposeOptions = [
    'Family Support',
    'Education Fees',
    'Medical Expenses',
    'Property Purchase',
    'Business Payment',
    'Investment',
    'Loan Repayment',
    'Gift',
    'Other',
];

// Selected account
const selectedAccount = computed(() => {
    return props.accounts.find(a => a.id === form.from_account_id);
});

// Selected country name
const selectedCountryName = computed(() => {
    const country = props.countries?.find(c => c.code === form.beneficiary_country);
    return country?.name || form.beneficiary_country;
});

// Fee calculation
const fee = computed(() => {
    if (!form.amount) return 0;
    const fixed = props.settings.wire_transfer_fee_fixed || 25;
    const percentage = props.settings.wire_transfer_fee_percentage || 0.1;
    return fixed + (percentage / 100 * form.amount);
});

const totalDebit = computed(() => {
    return (form.amount || 0) + fee.value;
});

// Validation for each step
const canProceedStep0 = computed(() => !!form.from_account_id);
const canProceedStep1 = computed(() => 
    form.beneficiary_name && 
    form.beneficiary_address && 
    form.beneficiary_country
);
const canProceedStep2 = computed(() => 
    form.bank_name && 
    form.bank_address && 
    form.swift_code?.length >= 8 &&
    form.account_number
);
const canProceedStep3 = computed(() => {
    if (!form.amount) return false;
    if (!form.purpose) return false;
    if (form.amount < (props.settings.wire_transfer_min || 100)) return false;
    if (form.amount > (props.settings.wire_transfer_max || 500000)) return false;
    if (selectedAccount.value && totalDebit.value > selectedAccount.value.balance) return false;
    return true;
});

// Navigation
const nextStep = () => {
    if (currentStep.value < 4) {
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
    form.post(route('transfer.store-wire'), {
        onSuccess: () => {
            showConfirmDialog.value = false;
        },
    });
};

// Format SWIFT code (uppercase)
const formatSwiftCode = (e) => {
    form.swift_code = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 11);
};

// Format IBAN (uppercase, remove spaces)
const formatIban = (e) => {
    form.iban = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 34);
};

// Mask account number for display
const maskedAccountNumber = computed(() => {
    if (!form.account_number) return '';
    return '****' + form.account_number.slice(-4);
});
</script>

<template>
    <Head title="Wire Transfer" />

    <div class="max-w-3xl mx-auto">
        <!-- Page Header -->
        <div class="mb-6">
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
                <Link :href="route('transfer.index')" class="hover:text-primary-600">Transfer</Link>
                <i class="pi pi-chevron-right text-xs"></i>
                <span>Wire Transfer</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">International Wire Transfer</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Send money to bank accounts worldwide</p>
        </div>

        <!-- Flash Messages -->
        <Message v-if="flash.error" severity="error" :closable="true" class="mb-6">
            {{ flash.error }}
        </Message>

        <!-- Info Banner -->
        <Message severity="info" :closable="false" class="mb-6">
            <div class="flex items-start gap-3">
                <i class="pi pi-globe text-lg mt-0.5"></i>
                <div>
                    <p class="font-medium">International wire transfers typically arrive in {{ settings.wire_processing_days || '3-5' }} business days</p>
                    <p class="text-sm mt-1">Transfer fee: {{ settings.currency_symbol }}{{ settings.wire_transfer_fee_fixed || 25 }}
                        <span v-if="settings.wire_transfer_fee_percentage"> + {{ settings.wire_transfer_fee_percentage }}%</span>
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

                <!-- Step 1: Beneficiary Details -->
                <div v-show="currentStep === 1">
                    <h3 class="text-lg font-semibold mb-4">Beneficiary Information</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Beneficiary Full Name</label>
                            <InputText 
                                v-model="form.beneficiary_name" 
                                placeholder="Full legal name of the recipient"
                                class="w-full"
                                :class="{ 'p-invalid': form.errors.beneficiary_name }"
                            />
                            <small v-if="form.errors.beneficiary_name" class="text-red-500">{{ form.errors.beneficiary_name }}</small>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Beneficiary Country</label>
                            <Dropdown 
                                v-model="form.beneficiary_country" 
                                :options="countries" 
                                optionLabel="name"
                                optionValue="code"
                                placeholder="Select country"
                                filter
                                class="w-full"
                                :class="{ 'p-invalid': form.errors.beneficiary_country }"
                            />
                            <small v-if="form.errors.beneficiary_country" class="text-red-500">{{ form.errors.beneficiary_country }}</small>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Beneficiary Address</label>
                            <Textarea 
                                v-model="form.beneficiary_address" 
                                placeholder="Full street address, city, postal code"
                                rows="3"
                                class="w-full"
                                :class="{ 'p-invalid': form.errors.beneficiary_address }"
                            />
                            <small v-if="form.errors.beneficiary_address" class="text-red-500">{{ form.errors.beneficiary_address }}</small>
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

                <!-- Step 2: Bank Details -->
                <div v-show="currentStep === 2">
                    <h3 class="text-lg font-semibold mb-4">Bank Details</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bank Name</label>
                            <InputText 
                                v-model="form.bank_name" 
                                placeholder="Full name of the receiving bank"
                                class="w-full"
                                :class="{ 'p-invalid': form.errors.bank_name }"
                            />
                            <small v-if="form.errors.bank_name" class="text-red-500">{{ form.errors.bank_name }}</small>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bank Address</label>
                            <Textarea 
                                v-model="form.bank_address" 
                                placeholder="Bank branch address"
                                rows="2"
                                class="w-full"
                                :class="{ 'p-invalid': form.errors.bank_address }"
                            />
                            <small v-if="form.errors.bank_address" class="text-red-500">{{ form.errors.bank_address }}</small>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">SWIFT/BIC Code</label>
                                <InputText 
                                    :modelValue="form.swift_code"
                                    @input="formatSwiftCode"
                                    placeholder="e.g., CHASUS33"
                                    class="w-full uppercase"
                                    maxlength="11"
                                    :class="{ 'p-invalid': form.errors.swift_code }"
                                />
                                <small class="text-gray-500">8-11 characters</small>
                                <small v-if="form.errors.swift_code" class="text-red-500 block">{{ form.errors.swift_code }}</small>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">IBAN (if applicable)</label>
                                <InputText 
                                    :modelValue="form.iban"
                                    @input="formatIban"
                                    placeholder="e.g., DE89370400440532013000"
                                    class="w-full uppercase"
                                    maxlength="34"
                                />
                                <small class="text-gray-500">Required for EU transfers</small>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Account Number</label>
                            <InputText 
                                v-model="form.account_number" 
                                placeholder="Beneficiary's bank account number"
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
                            :disabled="!canProceedStep2"
                        />
                    </div>
                </div>

                <!-- Step 3: Amount -->
                <div v-show="currentStep === 3">
                    <h3 class="text-lg font-semibold mb-4">Transfer Amount</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Amount (USD)</label>
                            <InputNumber 
                                v-model="form.amount" 
                                mode="currency" 
                                currency="USD"
                                locale="en-US"
                                :min="settings.wire_transfer_min || 100"
                                :max="settings.wire_transfer_max || 500000"
                                class="w-full"
                                :class="{ 'p-invalid': form.errors.amount }"
                            />
                            <div class="flex justify-between text-sm text-gray-500 mt-1">
                                <span>Min: {{ settings.currency_symbol }}{{ settings.wire_transfer_min || 100 }}</span>
                                <span>Max: {{ settings.currency_symbol }}{{ (settings.wire_transfer_max || 500000).toLocaleString() }}</span>
                            </div>
                            <small v-if="form.errors.amount" class="text-red-500">{{ form.errors.amount }}</small>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Purpose of Transfer</label>
                            <Dropdown 
                                v-model="form.purpose" 
                                :options="purposeOptions" 
                                placeholder="Select purpose"
                                class="w-full"
                                :class="{ 'p-invalid': form.errors.purpose }"
                            />
                            <small class="text-gray-500">Required for regulatory compliance</small>
                            <small v-if="form.errors.purpose" class="text-red-500 block">{{ form.errors.purpose }}</small>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Additional Notes (Optional)</label>
                            <Textarea 
                                v-model="form.description" 
                                placeholder="Any additional information..."
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
                                    <span class="text-gray-600">Wire Transfer Fee</span>
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
                            :disabled="!canProceedStep3"
                        />
                    </div>
                </div>

                <!-- Step 4: Confirm -->
                <div v-show="currentStep === 4">
                    <h3 class="text-lg font-semibold mb-4">Confirm Wire Transfer</h3>
                    
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
                            <p class="text-sm text-gray-500 mb-2">Beneficiary</p>
                            <div class="space-y-1">
                                <p class="font-medium">{{ form.beneficiary_name }}</p>
                                <p class="text-sm text-gray-600">{{ form.beneficiary_address }}</p>
                                <p class="text-sm text-gray-600">{{ selectedCountryName }}</p>
                            </div>
                        </div>

                        <Divider />

                        <div>
                            <p class="text-sm text-gray-500 mb-2">Bank Details</p>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <span class="text-gray-500">Bank:</span>
                                    <span class="ml-2 font-medium">{{ form.bank_name }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">SWIFT:</span>
                                    <span class="ml-2 font-medium">{{ form.swift_code }}</span>
                                </div>
                                <div v-if="form.iban">
                                    <span class="text-gray-500">IBAN:</span>
                                    <span class="ml-2 font-medium">{{ form.iban }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Account:</span>
                                    <span class="ml-2 font-medium">{{ maskedAccountNumber }}</span>
                                </div>
                                <div class="col-span-2">
                                    <span class="text-gray-500">Purpose:</span>
                                    <span class="ml-2 font-medium">{{ form.purpose }}</span>
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
                                <span class="text-gray-600">Wire Transfer Fee</span>
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
                                        International wire transfers typically arrive within {{ settings.wire_processing_days || '3-5' }} business days.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                            <div class="flex items-start gap-2">
                                <i class="pi pi-info-circle text-blue-600"></i>
                                <div class="text-sm">
                                    <p class="font-medium text-blue-800 dark:text-blue-200">Important Notice</p>
                                    <p class="text-blue-700 dark:text-blue-300">
                                        Please verify all bank details carefully. Wire transfers cannot be reversed once processed.
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
            header="Confirm Wire Transfer" 
            :modal="true"
            :style="{ width: '450px' }"
        >
            <div class="text-center">
                <i class="pi pi-globe text-5xl text-blue-500 mb-4"></i>
                <p class="mb-4">
                    You are about to send <strong>{{ settings.currency_symbol }}{{ form.amount?.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</strong> to:
                </p>
                <p class="font-semibold text-lg">{{ form.beneficiary_name }}</p>
                <p class="text-gray-500">{{ form.bank_name }}</p>
                <p class="text-sm text-gray-400">{{ selectedCountryName }}</p>
                <p class="text-sm text-gray-400 mt-2">SWIFT: {{ form.swift_code }}</p>
                
                <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg text-sm text-red-700 dark:text-red-300">
                    <i class="pi pi-exclamation-triangle mr-2"></i>
                    Wire transfers cannot be reversed. Please confirm all details are correct.
                </div>
            </div>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button 
                        label="Cancel" 
                        severity="secondary" 
                        @click="showConfirmDialog = false"
                    />
                    <Button 
                        label="Confirm Wire Transfer" 
                        icon="pi pi-check"
                        @click="submitTransfer"
                        :loading="form.processing"
                    />
                </div>
            </template>
        </Dialog>
    </div>
</template>
