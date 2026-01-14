<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import Tag from 'primevue/tag';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    verificationStatus: Object,
    requiresVerification: Boolean,
});

const activeCodeType = ref(null);

const form = useForm({
    code_type: '',
    code: '',
});

const requiredCodes = computed(() => {
    return Object.entries(props.verificationStatus)
        .filter(([key, status]) => status.required)
        .map(([key, status]) => ({
            key,
            ...status,
        }));
});

const allVerified = computed(() => {
    return requiredCodes.value.every(code => code.verified);
});

const pendingCodes = computed(() => {
    return requiredCodes.value.filter(code => !code.verified);
});

const verifiedCodes = computed(() => {
    return requiredCodes.value.filter(code => code.verified);
});

const selectCode = (codeKey) => {
    activeCodeType.value = codeKey;
    form.code_type = codeKey;
    form.code = '';
};

const submitCode = () => {
    form.post(route('withdraw.verify.code'), {
        preserveScroll: true,
        onSuccess: () => {
            form.code = '';
            activeCodeType.value = null;
        },
    });
};

const getCodeIcon = (codeKey) => {
    const icons = {
        imf_code: 'pi pi-globe',
        tax_code: 'pi pi-file',
        cot_code: 'pi pi-dollar',
    };
    return icons[codeKey] || 'pi pi-key';
};
</script>

<template>
    <Head title="Verification" />

    <div class="max-w-2xl mx-auto">
        <!-- Page Header -->
        <div class="mb-6">
            <Link :href="route('withdraw.index')" class="text-primary-600 hover:text-primary-700 text-sm mb-2 inline-flex items-center">
                <i class="pi pi-arrow-left mr-2"></i>
                Back to Withdraw
            </Link>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Withdrawal Verification</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Complete verification to enable withdrawals</p>
        </div>

        <!-- All Verified Message -->
        <Message v-if="allVerified && requiredCodes.length > 0" severity="success" :closable="false" class="mb-6">
            <div class="flex items-center gap-2">
                <i class="pi pi-check-circle text-xl"></i>
                <div>
                    <strong>All verifications complete!</strong>
                    <p class="text-sm mt-1">You can now proceed with withdrawals.</p>
                </div>
            </div>
        </Message>

        <!-- No Codes Required -->
        <Message v-if="requiredCodes.length === 0" severity="info" :closable="false" class="mb-6">
            <div class="flex items-center gap-2">
                <i class="pi pi-info-circle"></i>
                <span>No verification codes are required for your account.</span>
            </div>
        </Message>

        <!-- Verification Status Overview -->
        <Card v-if="requiredCodes.length > 0" class="mb-6">
            <template #title>
                <div class="flex items-center gap-2">
                    <i class="pi pi-shield text-primary-600"></i>
                    Verification Status
                </div>
            </template>
            <template #content>
                <div class="grid gap-3">
                    <div v-for="code in requiredCodes" :key="code.key"
                         class="flex items-center justify-between p-4 border rounded-lg"
                         :class="code.verified ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20' : 'border-gray-200 dark:border-gray-700'">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center"
                                 :class="code.verified ? 'bg-green-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-500'">
                                <i :class="getCodeIcon(code.key)"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ code.label }}</p>
                                <p class="text-sm text-gray-500">{{ code.description }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <Tag v-if="code.verified" value="Verified" severity="success" />
                            <Tag v-else value="Pending" severity="warn" />
                            <Button v-if="!code.verified" 
                                    icon="pi pi-key" 
                                    size="small" 
                                    severity="secondary" 
                                    rounded
                                    @click="selectCode(code.key)" />
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mt-6">
                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-gray-500 dark:text-gray-400">Verification Progress</span>
                        <span class="font-medium text-gray-900 dark:text-white">
                            {{ verifiedCodes.length }} / {{ requiredCodes.length }} Complete
                        </span>
                    </div>
                    <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                        <div class="bg-green-500 h-full transition-all duration-500"
                             :style="{ width: `${(verifiedCodes.length / requiredCodes.length) * 100}%` }"></div>
                    </div>
                </div>
            </template>
        </Card>

        <!-- Code Entry Form -->
        <Card v-if="activeCodeType" class="mb-6">
            <template #title>
                <div class="flex items-center gap-2">
                    <i :class="getCodeIcon(activeCodeType)" class="text-primary-600"></i>
                    Enter {{ verificationStatus[activeCodeType].label }}
                </div>
            </template>
            <template #subtitle>
                {{ verificationStatus[activeCodeType].description }}
            </template>
            <template #content>
                <form @submit.prevent="submitCode" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Verification Code
                        </label>
                        <InputText v-model="form.code" 
                                   class="w-full font-mono text-lg tracking-widest"
                                   placeholder="Enter your code"
                                   :disabled="form.processing" />
                        <p v-if="form.errors[activeCodeType]" class="text-red-500 text-sm mt-1">
                            {{ form.errors[activeCodeType] }}
                        </p>
                        <p v-if="form.errors.code" class="text-red-500 text-sm mt-1">
                            {{ form.errors.code }}
                        </p>
                    </div>

                    <Message severity="info" :closable="false">
                        <i class="pi pi-info-circle mr-2"></i>
                        Enter the verification code exactly as provided. Codes are case-sensitive.
                    </Message>

                    <div class="flex justify-between">
                        <Button type="button" 
                                label="Cancel" 
                                severity="secondary" 
                                outlined 
                                @click="activeCodeType = null" />
                        <Button type="submit" 
                                label="Verify Code" 
                                icon="pi pi-check" 
                                :loading="form.processing"
                                :disabled="!form.code" />
                    </div>
                </form>
            </template>
        </Card>

        <!-- Help Section -->
        <Card class="mb-6">
            <template #title>
                <div class="flex items-center gap-2">
                    <i class="pi pi-question-circle text-primary-600"></i>
                    Need Help?
                </div>
            </template>
            <template #content>
                <div class="space-y-4 text-sm">
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-1">What are verification codes?</h4>
                        <p class="text-gray-600 dark:text-gray-400">
                            Verification codes are security measures required before processing large withdrawals. 
                            They help ensure the security of your funds and comply with international regulations.
                        </p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Where do I get my codes?</h4>
                        <p class="text-gray-600 dark:text-gray-400">
                            Verification codes are provided by our support team after identity verification. 
                            If you haven't received your codes, please contact support.
                        </p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-1">Code not working?</h4>
                        <p class="text-gray-600 dark:text-gray-400">
                            Make sure you're entering the code exactly as provided, including any special characters. 
                            If you continue to have issues, 
                            <Link :href="route('support-tickets.create')" class="text-primary-600 hover:underline">
                                open a support ticket
                            </Link>.
                        </p>
                    </div>
                </div>
            </template>
        </Card>

        <!-- Action Buttons -->
        <div class="flex justify-between">
            <Link :href="route('withdraw.index')">
                <Button label="Back to Withdraw" icon="pi pi-arrow-left" severity="secondary" outlined />
            </Link>
            <Link v-if="allVerified" :href="route('withdraw.manual')">
                <Button label="Proceed to Withdraw" icon="pi pi-arrow-right" iconPos="right" severity="success" />
            </Link>
        </div>
    </div>
</template>
