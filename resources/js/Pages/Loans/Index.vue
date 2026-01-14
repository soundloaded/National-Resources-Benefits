<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import Message from 'primevue/message';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import Dialog from 'primevue/dialog';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Textarea from 'primevue/textarea';
import { ref, computed, watch } from 'vue';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    loansEnabled: Boolean,
    activeLoan: Object,
    loanHistory: Array,
    loanPlans: Array,
    accounts: Array,
    settings: Object,
    canApplyForLoan: Boolean,
    disabledReason: String,
});

const showApplyDialog = ref(false);
const selectedPlan = ref(null);

const form = useForm({
    loan_plan_id: null,
    account_id: null,
    amount: null,
    duration_months: 3,
    purpose: '',
});

const interestRate = computed(() => selectedPlan.value?.interest_rate || props.settings?.loan_interest_rate || 5);

const calculatedInterest = computed(() => {
    if (!form.amount) return 0;
    return (form.amount * interestRate.value * form.duration_months) / 100;
});

const totalPayable = computed(() => {
    return (form.amount || 0) + calculatedInterest.value;
});

const monthlyPayment = computed(() => {
    if (!form.duration_months || form.duration_months === 0) return 0;
    return totalPayable.value / form.duration_months;
});

const durationOptions = computed(() => {
    if (!selectedPlan.value) {
        return [
            { label: '3 Months', value: 3 },
            { label: '6 Months', value: 6 },
            { label: '9 Months', value: 9 },
            { label: '12 Months', value: 12 },
        ];
    }
    const options = [];
    for (let i = selectedPlan.value.min_duration_months; i <= selectedPlan.value.max_duration_months; i++) {
        options.push({ label: `${i} Month${i > 1 ? 's' : ''}`, value: i });
    }
    return options;
});

const openApplyDialog = (plan = null) => {
    selectedPlan.value = plan;
    if (plan) {
        form.loan_plan_id = plan.id;
        form.amount = Math.floor(plan.max_amount / 2); // Default to half of max
        form.duration_months = plan.default_duration_months;
    } else {
        form.loan_plan_id = null;
        form.amount = null;
        form.duration_months = 3;
    }
    form.purpose = '';
    showApplyDialog.value = true;
};

const submitApplication = () => {
    form.post(route('loans.apply'), {
        preserveScroll: true,
        onSuccess: () => {
            showApplyDialog.value = false;
            form.reset();
            selectedPlan.value = null;
        },
    });
};

const getStatusSeverity = (status) => {
    const severities = {
        pending: 'warn',
        approved: 'info',
        disbursed: 'info',
        active: 'success',
        completed: 'success',
        rejected: 'danger',
        defaulted: 'danger',
        cancelled: 'secondary',
    };
    return severities[status] || 'secondary';
};

const getGradientStyle = (plan) => {
    const colorMap = {
        'green-500': '#22c55e', 'green-600': '#16a34a', 'emerald-600': '#059669',
        'blue-500': '#3b82f6', 'blue-600': '#2563eb', 'blue-700': '#1d4ed8',
        'purple-500': '#a855f7', 'purple-600': '#9333ea', 'violet-600': '#7c3aed',
        'amber-500': '#f59e0b', 'amber-600': '#d97706',
        'red-500': '#ef4444', 'red-600': '#dc2626',
        'indigo-500': '#6366f1', 'indigo-600': '#4f46e5',
        'teal-500': '#14b8a6', 'teal-600': '#0d9488',
        'pink-500': '#ec4899', 'pink-600': '#db2777',
    };
    const fromColor = colorMap[plan.gradient_from] || '#3b82f6';
    const toColor = colorMap[plan.gradient_to] || '#1d4ed8';
    return `background: linear-gradient(to bottom right, ${fromColor}, ${toColor})`;
};

const getColorClasses = (plan, type = 'bg') => {
    const colorMap = {
        green: { bg: 'bg-green-100 dark:bg-green-900/30', text: 'text-green-600 dark:text-green-400', border: 'border-green-500', button: 'success' },
        blue: { bg: 'bg-blue-100 dark:bg-blue-900/30', text: 'text-blue-600 dark:text-blue-400', border: 'border-blue-500', button: 'info' },
        purple: { bg: 'bg-purple-100 dark:bg-purple-900/30', text: 'text-purple-600 dark:text-purple-400', border: 'border-purple-500', button: 'help' },
        amber: { bg: 'bg-amber-100 dark:bg-amber-900/30', text: 'text-amber-600 dark:text-amber-400', border: 'border-amber-500', button: 'warning' },
        red: { bg: 'bg-red-100 dark:bg-red-900/30', text: 'text-red-600 dark:text-red-400', border: 'border-red-500', button: 'danger' },
        indigo: { bg: 'bg-indigo-100 dark:bg-indigo-900/30', text: 'text-indigo-600 dark:text-indigo-400', border: 'border-indigo-500', button: 'info' },
        teal: { bg: 'bg-teal-100 dark:bg-teal-900/30', text: 'text-teal-600 dark:text-teal-400', border: 'border-teal-500', button: 'success' },
        pink: { bg: 'bg-pink-100 dark:bg-pink-900/30', text: 'text-pink-600 dark:text-pink-400', border: 'border-pink-500', button: 'help' },
    };
    return colorMap[plan.color]?.[type] || colorMap.blue[type];
};

const formatCurrency = (amount) => {
    return props.settings?.currency_symbol + parseFloat(amount).toLocaleString('en-US', { minimumFractionDigits: 2 });
};
</script>

<template>
    <Head title="Loans" />

    <div class="max-w-6xl mx-auto">
        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Loans</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Apply for a loan or manage your existing loan</p>
        </div>

        <!-- Disabled Warning -->
        <Message v-if="!loansEnabled" severity="warn" :closable="false" class="mb-6">
            <div class="flex items-center gap-2">
                <i class="pi pi-lock"></i>
                <span>Loan services are currently disabled. Please check back later.</span>
            </div>
        </Message>

        <!-- Active Loan Card -->
        <Card v-if="activeLoan" class="mb-6 border-l-4 border-l-primary-500">
            <template #title>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="pi pi-money-bill text-primary-600"></i>
                        Active Loan
                    </div>
                    <Tag :value="activeLoan.status" :severity="getStatusSeverity(activeLoan.status)" />
                </div>
            </template>
            <template #content>
                <div class="grid md:grid-cols-4 gap-4 mb-4">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Loan ID</p>
                        <p class="font-mono font-medium text-gray-900 dark:text-white">{{ activeLoan.loan_id }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Amount Borrowed</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(activeLoan.amount) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Payable</p>
                        <p class="text-xl font-bold text-primary-600">{{ formatCurrency(activeLoan.total_payable) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Due Date</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ activeLoan.due_date }}</p>
                    </div>
                </div>

                <!-- Repayment Progress -->
                <div class="mb-4">
                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-gray-600 dark:text-gray-400">Repayment Progress</span>
                        <span class="font-medium text-gray-900 dark:text-white">
                            {{ formatCurrency(activeLoan.amount_paid) }} / {{ formatCurrency(activeLoan.total_payable) }}
                        </span>
                    </div>
                    <ProgressBar :value="activeLoan.progress_percentage" :showValue="true" 
                                 :class="activeLoan.progress_percentage >= 100 ? 'progress-complete' : ''" />
                </div>

                <!-- Quick Stats -->
                <div class="grid grid-cols-3 gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Interest Rate</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ activeLoan.interest_rate }}%</p>
                    </div>
                    <div class="text-center border-x border-gray-200 dark:border-gray-700">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Remaining</p>
                        <p class="text-lg font-bold text-orange-600">{{ formatCurrency(activeLoan.remaining_balance) }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Monthly Payment</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ formatCurrency(activeLoan.monthly_payment) }}</p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 mt-4">
                    <Link :href="route('loans.show', activeLoan.id)">
                        <Button label="View Details" icon="pi pi-eye" severity="secondary" outlined />
                    </Link>
                    <Link v-if="activeLoan.status === 'active'" :href="route('loans.show', activeLoan.id)">
                        <Button label="Make Payment" icon="pi pi-credit-card" />
                    </Link>
                </div>
            </template>
        </Card>

        <!-- Apply for Loan Section -->
        <div v-if="!activeLoan && loansEnabled">
            <!-- Eligibility Check -->
            <Message v-if="!canApplyForLoan" severity="warn" :closable="false" class="mb-6">
                <div class="flex items-center gap-2">
                    <i class="pi pi-info-circle"></i>
                    <span>{{ disabledReason || 'You are not currently eligible for a loan.' }}</span>
                </div>
            </Message>

            <!-- Loan Plans -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Available Loan Options</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Choose a plan that fits your needs</p>
                    </div>
                </div>

                <!-- No Plans Available -->
                <div v-if="!loanPlans || loanPlans.length === 0" class="text-center py-12 bg-gray-50 dark:bg-gray-800 rounded-xl">
                    <i class="pi pi-inbox text-4xl text-gray-400 mb-4"></i>
                    <p class="text-gray-500 dark:text-gray-400">No loan plans available at the moment.</p>
                    <p class="text-sm text-gray-400 mt-1">Please check back later.</p>
                </div>
                
                <!-- Dynamic Loan Plan Cards -->
                <div v-else class="grid md:grid-cols-3 gap-6">
                    <div v-for="plan in loanPlans" :key="plan.id"
                         class="group relative bg-white dark:bg-gray-800 rounded-2xl border-2 overflow-hidden transition-all duration-300 cursor-pointer"
                         :class="[
                             plan.is_featured 
                                 ? 'border-primary-500 bg-gradient-to-b from-amber-50 to-white dark:from-amber-900/20 dark:to-gray-800 transform md:-translate-y-2 hover:shadow-xl hover:shadow-primary-500/20' 
                                 : `border-gray-200 dark:border-gray-700 hover:${getColorClasses(plan, 'border')} hover:shadow-xl`,
                             { 'opacity-50 cursor-not-allowed': !canApplyForLoan }
                         ]"
                         @click="canApplyForLoan && openApplyDialog(plan)">
                        
                        <!-- Featured Badge -->
                        <div v-if="plan.is_featured" class="absolute -top-0 left-1/2 -translate-x-1/2 z-10">
                            <div class="bg-gradient-to-r from-yellow-400 to-amber-500 text-amber-900 text-xs font-bold px-4 py-1.5 rounded-b-lg shadow-lg">
                                <i class="pi pi-star-fill mr-1"></i> MOST POPULAR
                            </div>
                        </div>
                        
                        <!-- Header -->
                        <div class="p-6 text-white" :class="[plan.is_featured ? 'pt-8' : '']"
                             :style="getGradientStyle(plan)">
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                                    <i :class="plan.icon" class="text-2xl"></i>
                                </div>
                                <span class="text-xs font-medium bg-white/20 px-3 py-1 rounded-full uppercase">
                                    {{ plan.max_duration_months }} months
                                </span>
                            </div>
                            <h3 class="text-xl font-bold mb-1">{{ plan.name }}</h3>
                            <p class="text-white/80 text-sm">{{ plan.tagline || plan.description }}</p>
                        </div>
                        
                        <!-- Content -->
                        <div class="p-6">
                            <div class="text-center mb-6">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Borrow up to</p>
                                <p class="text-3xl font-bold text-gray-900 dark:text-white">
                                    {{ formatCurrency(plan.max_amount) }}
                                </p>
                                <p class="text-xs text-gray-400 mt-1">Min: {{ formatCurrency(plan.min_amount) }}</p>
                            </div>
                            
                            <!-- Features -->
                            <div class="space-y-3">
                                <!-- Interest Rate -->
                                <div class="flex items-center gap-3 text-sm">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" :class="getColorClasses(plan, 'bg')">
                                        <i class="pi pi-percentage" :class="getColorClasses(plan, 'text')"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ plan.interest_rate }}% Interest</p>
                                        <p class="text-xs text-gray-500">Monthly rate</p>
                                    </div>
                                </div>
                                
                                <!-- Duration -->
                                <div class="flex items-center gap-3 text-sm">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" :class="getColorClasses(plan, 'bg')">
                                        <i class="pi pi-calendar" :class="getColorClasses(plan, 'text')"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">Flexible Duration</p>
                                        <p class="text-xs text-gray-500">{{ plan.min_duration_months }}-{{ plan.max_duration_months }} months</p>
                                    </div>
                                </div>
                                
                                <!-- Approval Time -->
                                <div class="flex items-center gap-3 text-sm">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" :class="getColorClasses(plan, 'bg')">
                                        <i class="pi pi-clock" :class="getColorClasses(plan, 'text')"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{ plan.approval_days === 1 ? 'Quick Approval' : `${plan.approval_days} Day Approval` }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ plan.approval_days === 1 ? 'Within 24 hours' : `${plan.approval_days} business days` }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Dynamic Features from Database -->
                                <div v-for="(feature, idx) in (plan.features || []).slice(0, 2)" :key="idx" 
                                     class="flex items-center gap-3 text-sm">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" :class="getColorClasses(plan, 'bg')">
                                        <i :class="[feature.icon || 'pi pi-check-circle', getColorClasses(plan, 'text')]"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ feature.title }}</p>
                                        <p class="text-xs text-gray-500">{{ feature.description }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <Button label="Apply Now" icon="pi pi-arrow-right" iconPos="right" 
                                        class="w-full" :severity="getColorClasses(plan, 'button')" :disabled="!canApplyForLoan" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loan History -->
        <Card>
            <template #title>
                <div class="flex items-center gap-2">
                    <i class="pi pi-history text-primary-600"></i>
                    Loan History
                </div>
            </template>
            <template #content>
                <DataTable v-if="loanHistory.length > 0" :value="loanHistory" stripedRows 
                           class="p-datatable-sm" responsiveLayout="scroll">
                    <Column field="loan_id" header="Loan ID">
                        <template #body="{ data }">
                            <Link :href="route('loans.show', data.id)" class="text-primary-600 hover:underline font-mono">
                                {{ data.loan_id }}
                            </Link>
                        </template>
                    </Column>
                    <Column field="amount" header="Amount">
                        <template #body="{ data }">
                            {{ formatCurrency(data.amount) }}
                        </template>
                    </Column>
                    <Column field="total_payable" header="Total Payable">
                        <template #body="{ data }">
                            {{ formatCurrency(data.total_payable) }}
                        </template>
                    </Column>
                    <Column field="status" header="Status">
                        <template #body="{ data }">
                            <Tag :value="data.status" :severity="getStatusSeverity(data.status)" />
                        </template>
                    </Column>
                    <Column field="created_at" header="Applied">
                        <template #body="{ data }">
                            {{ data.created_at }}
                        </template>
                    </Column>
                    <Column header="Actions">
                        <template #body="{ data }">
                            <Link :href="route('loans.show', data.id)">
                                <Button icon="pi pi-eye" size="small" severity="secondary" text rounded />
                            </Link>
                        </template>
                    </Column>
                </DataTable>
                <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <i class="pi pi-inbox text-4xl mb-3"></i>
                    <p>No loan history yet.</p>
                </div>
            </template>
        </Card>

        <!-- Apply Dialog -->
        <Dialog v-model:visible="showApplyDialog" modal header="Apply for Loan" :style="{ width: '500px' }">
            <form @submit.prevent="submitApplication" class="space-y-4">
                <!-- Selected Plan Info -->
                <div v-if="selectedPlan" class="p-4 bg-primary-50 dark:bg-primary-900/20 rounded-lg mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-primary-100 dark:bg-primary-800 rounded-lg flex items-center justify-center">
                            <i :class="selectedPlan.icon" class="text-primary-600 dark:text-primary-400"></i>
                        </div>
                        <div>
                            <p class="font-medium text-primary-700 dark:text-primary-300">{{ selectedPlan.name }}</p>
                            <p class="text-sm text-primary-600 dark:text-primary-400">
                                Up to {{ formatCurrency(selectedPlan.max_amount) }} • {{ selectedPlan.interest_rate }}% interest
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Account Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Disbursement Account
                    </label>
                    <Select v-model="form.account_id" :options="accounts" optionLabel="name" optionValue="id"
                            placeholder="Select account" class="w-full" :class="{ 'p-invalid': form.errors.account_id }" />
                    <small v-if="form.errors.account_id" class="text-red-500">{{ form.errors.account_id }}</small>
                </div>

                <!-- Loan Amount -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Loan Amount ({{ settings.currency_symbol }})
                    </label>
                    <InputNumber v-model="form.amount" 
                                 :min="selectedPlan?.min_amount || settings.loan_min_amount" 
                                 :max="selectedPlan?.max_amount || settings.loan_max_amount"
                                 mode="currency" :currency="settings.currency || 'USD'" locale="en-US"
                                 class="w-full" :class="{ 'p-invalid': form.errors.amount }" />
                    <small class="text-gray-500">
                        Min: {{ formatCurrency(selectedPlan?.min_amount || settings.loan_min_amount) }} • 
                        Max: {{ formatCurrency(selectedPlan?.max_amount || settings.loan_max_amount) }}
                    </small>
                    <small v-if="form.errors.amount" class="block text-red-500">{{ form.errors.amount }}</small>
                </div>

                <!-- Duration -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Repayment Duration
                    </label>
                    <Select v-model="form.duration_months" :options="durationOptions" 
                            optionLabel="label" optionValue="value" class="w-full" />
                </div>

                <!-- Purpose -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Purpose of Loan
                    </label>
                    <Textarea v-model="form.purpose" rows="2" class="w-full" 
                              placeholder="Briefly describe why you need this loan..."
                              :class="{ 'p-invalid': form.errors.purpose }" />
                    <small v-if="form.errors.purpose" class="text-red-500">{{ form.errors.purpose }}</small>
                </div>

                <!-- Loan Summary -->
                <div v-if="form.amount" class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <h4 class="font-medium text-gray-900 dark:text-white mb-3">Loan Summary</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Principal Amount</span>
                            <span class="font-medium">{{ formatCurrency(form.amount) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Interest ({{ interestRate }}% × {{ form.duration_months }} mo)</span>
                            <span class="font-medium">{{ formatCurrency(calculatedInterest) }}</span>
                        </div>
                        <hr class="border-gray-200 dark:border-gray-700">
                        <div class="flex justify-between text-base">
                            <span class="font-medium text-gray-900 dark:text-white">Total Payable</span>
                            <span class="font-bold text-primary-600">{{ formatCurrency(totalPayable) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Monthly Payment</span>
                            <span class="font-medium">{{ formatCurrency(monthlyPayment) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end gap-3 pt-4">
                    <Button label="Cancel" severity="secondary" text @click="showApplyDialog = false" />
                    <Button type="submit" label="Submit Application" icon="pi pi-send" 
                            :loading="form.processing" :disabled="!form.amount || !form.account_id || !form.purpose" />
                </div>
            </form>
        </Dialog>
    </div>
</template>

<style scoped>
.progress-complete :deep(.p-progressbar-value) {
    background: linear-gradient(to right, #10b981, #059669);
}
</style>
