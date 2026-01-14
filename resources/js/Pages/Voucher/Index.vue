<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Message from 'primevue/message';
import Dialog from 'primevue/dialog';
import Tag from 'primevue/tag';
import ProgressSpinner from 'primevue/progressspinner';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    accounts: Array,
    canUseVoucher: Boolean,
    recentRedemptions: Array,
    settings: Object,
});

const voucherCode = ref('');
const isValidating = ref(false);
const validatedVoucher = ref(null);
const validationError = ref('');
const showRedeemDialog = ref(false);

const form = useForm({
    code: '',
    account_id: null,
});

// Format voucher code as user types (uppercase, remove spaces)
const formatCode = () => {
    voucherCode.value = voucherCode.value.toUpperCase().replace(/\s/g, '');
};

// Validate voucher code
const validateVoucher = async () => {
    if (!voucherCode.value || voucherCode.value.length < 3) {
        validationError.value = 'Please enter a valid voucher code.';
        return;
    }
    
    isValidating.value = true;
    validationError.value = '';
    validatedVoucher.value = null;
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
            || document.head.querySelector('meta[name="csrf-token"]')?.content;
        
        const response = await fetch(route('voucher.validate'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ code: voucherCode.value }),
        });
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Voucher validation error:', response.status, errorText);
            validationError.value = 'Validation failed. Please try again.';
            return;
        }
        
        const data = await response.json();
        
        if (data.valid) {
            validatedVoucher.value = data.voucher;
            validationError.value = '';
        } else {
            validationError.value = data.message;
            validatedVoucher.value = null;
        }
    } catch (error) {
        console.error('Voucher validation exception:', error);
        validationError.value = 'Failed to validate voucher. Please try again.';
    } finally {
        isValidating.value = false;
    }
};

// Open redeem dialog
const openRedeemDialog = () => {
    if (!validatedVoucher.value) return;
    form.code = voucherCode.value;
    form.account_id = props.accounts.length > 0 ? props.accounts[0].id : null;
    showRedeemDialog.value = true;
};

// Submit redemption
const submitRedemption = () => {
    form.post(route('voucher.redeem'), {
        preserveScroll: true,
        onSuccess: () => {
            showRedeemDialog.value = false;
            voucherCode.value = '';
            validatedVoucher.value = null;
            form.reset();
        },
    });
};

// Clear form
const clearForm = () => {
    voucherCode.value = '';
    validatedVoucher.value = null;
    validationError.value = '';
};

const selectedAccountName = computed(() => {
    const account = props.accounts.find(a => a.id === form.account_id);
    return account?.name || 'Select Account';
});
</script>

<template>
    <Head title="Redeem Voucher" />

    <div class="max-w-4xl mx-auto">
        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Redeem Voucher</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Enter your voucher code to credit your account</p>
        </div>

        <!-- Permission Warning -->
        <Message v-if="!canUseVoucher" severity="error" :closable="false" class="mb-6">
            <div class="flex items-center gap-2">
                <i class="pi pi-ban"></i>
                <span>Your account is not enabled for voucher redemption. Please contact support.</span>
            </div>
        </Message>

        <!-- No Accounts Warning -->
        <Message v-if="accounts.length === 0" severity="warn" :closable="false" class="mb-6">
            <div class="flex items-center gap-2">
                <i class="pi pi-exclamation-triangle"></i>
                <span>You don't have any accounts to credit. Please contact support.</span>
            </div>
        </Message>

        <!-- Voucher Code Input -->
        <Card class="mb-6" :class="{ 'opacity-50 pointer-events-none': !canUseVoucher || accounts.length === 0 }">
            <template #title>
                <div class="flex items-center gap-2">
                    <i class="pi pi-ticket text-primary-600"></i>
                    Enter Voucher Code
                </div>
            </template>
            <template #content>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Voucher Code
                        </label>
                        <div class="flex gap-2">
                            <InputText 
                                v-model="voucherCode" 
                                @input="formatCode"
                                class="flex-1 font-mono text-lg tracking-widest uppercase"
                                placeholder="XXXX-XXXX-XXXX"
                                :disabled="isValidating"
                            />
                            <Button 
                                label="Validate" 
                                icon="pi pi-check" 
                                :loading="isValidating"
                                :disabled="!voucherCode || voucherCode.length < 3"
                                @click="validateVoucher"
                            />
                            <Button 
                                v-if="voucherCode"
                                icon="pi pi-times" 
                                severity="secondary" 
                                outlined
                                @click="clearForm"
                            />
                        </div>
                    </div>

                    <!-- Validation Error -->
                    <Message v-if="validationError" severity="error" :closable="false">
                        <i class="pi pi-times-circle mr-2"></i>
                        {{ validationError }}
                    </Message>

                    <!-- Validated Voucher Preview -->
                    <div v-if="validatedVoucher" class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0">
                                <i class="pi pi-check text-white text-xl"></i>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ validatedVoucher.name || 'Valid Voucher' }}</h3>
                                    <Tag value="Valid" severity="success" />
                                </div>
                                <p v-if="validatedVoucher.description" class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                    {{ validatedVoucher.description }}
                                </p>
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-500">Code:</span>
                                        <span class="ml-2 font-mono font-medium">{{ validatedVoucher.code }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Value:</span>
                                        <span class="ml-2 font-bold text-green-600">{{ settings.currency_symbol }}{{ validatedVoucher.formatted_value }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Type:</span>
                                        <span class="ml-2 capitalize">{{ validatedVoucher.voucher_type?.replace('_', ' ') }}</span>
                                    </div>
                                    <div v-if="validatedVoucher.expiration_date">
                                        <span class="text-gray-500">Expires:</span>
                                        <span class="ml-2">{{ validatedVoucher.expiration_date }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4 pt-4 border-t border-green-200 dark:border-green-700">
                            <Button 
                                label="Redeem Now" 
                                icon="pi pi-gift" 
                                severity="success" 
                                class="w-full"
                                @click="openRedeemDialog"
                            />
                        </div>
                    </div>
                </div>
            </template>
        </Card>

        <!-- How It Works -->
        <Card class="mb-6">
            <template #title>
                <div class="flex items-center gap-2">
                    <i class="pi pi-info-circle text-primary-600"></i>
                    How It Works
                </div>
            </template>
            <template #content>
                <div class="grid md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center mx-auto mb-3">
                            <span class="text-xl font-bold text-primary-600">1</span>
                        </div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Enter Code</h4>
                        <p class="text-sm text-gray-500">Type your voucher code exactly as provided</p>
                    </div>
                    <div class="text-center">
                        <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center mx-auto mb-3">
                            <span class="text-xl font-bold text-primary-600">2</span>
                        </div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Validate</h4>
                        <p class="text-sm text-gray-500">We'll verify the code is valid and available</p>
                    </div>
                    <div class="text-center">
                        <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center mx-auto mb-3">
                            <span class="text-xl font-bold text-primary-600">3</span>
                        </div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Redeem</h4>
                        <p class="text-sm text-gray-500">Funds are instantly credited to your account</p>
                    </div>
                </div>
            </template>
        </Card>

        <!-- Recent Redemptions -->
        <Card v-if="recentRedemptions.length > 0">
            <template #title>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-history text-primary-600"></i>
                        Recent Redemptions
                    </div>
                    <Link :href="route('voucher.history')" class="text-sm text-primary-600 hover:text-primary-700">
                        View All
                    </Link>
                </div>
            </template>
            <template #content>
                <div class="space-y-3">
                    <div v-for="redemption in recentRedemptions" :key="redemption.id"
                         class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                                <i class="pi pi-ticket text-green-600"></i>
                            </div>
                            <div>
                                <p class="font-mono text-sm font-medium text-gray-900 dark:text-white">{{ redemption.voucher_code }}</p>
                                <p class="text-xs text-gray-500">{{ redemption.redeemed_at }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-green-600">+{{ settings.currency_symbol }}{{ redemption.amount }}</p>
                            <p class="text-xs text-gray-500">{{ redemption.account_name }}</p>
                        </div>
                    </div>
                </div>
            </template>
        </Card>

        <!-- Redemption History Link -->
        <div class="mt-8 text-center">
            <Link :href="route('voucher.history')" class="text-primary-600 hover:text-primary-700 font-medium">
                <i class="pi pi-history mr-2"></i>
                View Full Redemption History
            </Link>
        </div>
    </div>

    <!-- Redeem Confirmation Dialog -->
    <Dialog v-model:visible="showRedeemDialog" modal header="Confirm Redemption" :style="{ width: '450px' }">
        <div class="space-y-4">
            <div class="text-center">
                <div class="w-16 h-16 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="pi pi-gift text-green-600 text-3xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                    Redeem {{ settings.currency_symbol }}{{ validatedVoucher?.formatted_value }}
                </h3>
                <p class="text-gray-500">
                    This voucher value will be credited to your selected account.
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Credit to Account
                </label>
                <Select 
                    v-model="form.account_id" 
                    :options="accounts" 
                    optionLabel="name" 
                    optionValue="id"
                    placeholder="Select account"
                    class="w-full"
                >
                    <template #option="{ option }">
                        <div class="flex justify-between w-full">
                            <span>{{ option.name }}</span>
                            <span class="text-gray-500">{{ settings.currency_symbol }}{{ option.formatted_balance }}</span>
                        </div>
                    </template>
                </Select>
            </div>

            <Message severity="info" :closable="false">
                <i class="pi pi-info-circle mr-2"></i>
                Once redeemed, vouchers cannot be reversed.
            </Message>
        </div>

        <template #footer>
            <Button label="Cancel" severity="secondary" outlined @click="showRedeemDialog = false" />
            <Button 
                label="Confirm & Redeem" 
                icon="pi pi-check" 
                severity="success"
                :loading="form.processing"
                :disabled="!form.account_id"
                @click="submitRedemption"
            />
        </template>
    </Dialog>
</template>
