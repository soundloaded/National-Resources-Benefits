<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    fundingSource: Object,
});

const form = useForm({
    requested_amount: '',
    purpose: '',
    documents: [],
    custom_fields: {},
});

const fileInput = ref(null);
const selectedFiles = ref([]);

const handleFileSelect = (e) => {
    const files = Array.from(e.target.files);
    selectedFiles.value = [...selectedFiles.value, ...files];
    form.documents = selectedFiles.value;
};

const removeFile = (index) => {
    selectedFiles.value.splice(index, 1);
    form.documents = selectedFiles.value;
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const submit = () => {
    form.post(route('funding-sources.apply.store', props.fundingSource.id), {
        forceFormData: true,
        onError: () => {
            // Handle errors
        },
    });
};
</script>

<template>
    <Head :title="`Apply - ${fundingSource.title}`" />

    <DashboardLayout>
        <div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Back Link -->
            <Link 
                :href="route('funding-sources.show', fundingSource.id)"
                class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-6"
            >
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back to Details
            </Link>

            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">Apply for Funding</h1>
                <p class="mt-1 text-gray-600">{{ fundingSource.title }}</p>
                <div class="mt-2 flex items-center gap-4 text-sm text-gray-500">
                    <span v-if="fundingSource.category" class="bg-blue-100 text-blue-800 px-2 py-1 rounded">
                        {{ fundingSource.category }}
                    </span>
                    <span v-if="fundingSource.deadline">
                        Deadline: {{ fundingSource.deadline }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Application Form -->
                <div class="lg:col-span-2">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- General Error -->
                        <div v-if="form.errors.general" class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <p class="text-sm text-red-600">{{ form.errors.general }}</p>
                        </div>

                        <!-- Requested Amount -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Funding Request</h3>
                            
                            <div>
                                <label for="requested_amount" class="block text-sm font-medium text-gray-700 mb-1">
                                    Requested Amount *
                                </label>
                                <div class="relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">$</span>
                                    </div>
                                    <input
                                        id="requested_amount"
                                        v-model="form.requested_amount"
                                        type="number"
                                        step="0.01"
                                        :min="fundingSource.amount_min"
                                        :max="fundingSource.amount_max"
                                        class="block w-full pl-7 pr-12 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                        :class="{ 'border-red-500': form.errors.requested_amount }"
                                        placeholder="0.00"
                                        required
                                    />
                                </div>
                                <p class="mt-1 text-xs text-gray-500">
                                    Amount range: ${{ Number(fundingSource.amount_min || 0).toLocaleString() }} 
                                    <template v-if="fundingSource.amount_max">
                                        - ${{ Number(fundingSource.amount_max).toLocaleString() }}
                                    </template>
                                </p>
                                <p v-if="form.errors.requested_amount" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.requested_amount }}
                                </p>
                            </div>
                        </div>

                        <!-- Purpose -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Purpose Statement</h3>
                            
                            <div>
                                <label for="purpose" class="block text-sm font-medium text-gray-700 mb-1">
                                    How will you use these funds? *
                                </label>
                                <textarea
                                    id="purpose"
                                    v-model="form.purpose"
                                    rows="5"
                                    class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    :class="{ 'border-red-500': form.errors.purpose }"
                                    placeholder="Please describe in detail how you plan to use this funding..."
                                    required
                                ></textarea>
                                <p class="mt-1 text-xs text-gray-500">
                                    Minimum 50 characters. Be specific about your plans.
                                </p>
                                <p v-if="form.errors.purpose" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.purpose }}
                                </p>
                            </div>
                        </div>

                        <!-- Custom Fields -->
                        <div v-if="fundingSource.form_fields && fundingSource.form_fields.length > 0" class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Additional Information</h3>
                            
                            <div class="space-y-4">
                                <div v-for="field in fundingSource.form_fields" :key="field.name">
                                    <label :for="field.name" class="block text-sm font-medium text-gray-700 mb-1">
                                        {{ field.label }} <span v-if="field.required" class="text-red-500">*</span>
                                    </label>
                                    
                                    <!-- Text Input -->
                                    <input
                                        v-if="field.type === 'text'"
                                        :id="field.name"
                                        v-model="form.custom_fields[field.name]"
                                        type="text"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                        :required="field.required"
                                    />
                                    
                                    <!-- Textarea -->
                                    <textarea
                                        v-else-if="field.type === 'textarea'"
                                        :id="field.name"
                                        v-model="form.custom_fields[field.name]"
                                        rows="3"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                        :required="field.required"
                                    ></textarea>
                                    
                                    <!-- Number -->
                                    <input
                                        v-else-if="field.type === 'number'"
                                        :id="field.name"
                                        v-model="form.custom_fields[field.name]"
                                        type="number"
                                        class="block w-full border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                        :required="field.required"
                                    />
                                    
                                    <p v-if="form.errors[`custom_fields.${field.name}`]" class="mt-1 text-sm text-red-600">
                                        {{ form.errors[`custom_fields.${field.name}`] }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Documents -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Supporting Documents</h3>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Upload Documents (optional)
                                </label>
                                
                                <!-- Upload Area -->
                                <div 
                                    class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-400 transition-colors cursor-pointer"
                                    @click="fileInput.click()"
                                >
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-600">
                                        Click to upload or drag and drop
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500">
                                        PDF, JPG, PNG, DOC up to 10MB each
                                    </p>
                                    <input
                                        ref="fileInput"
                                        type="file"
                                        multiple
                                        accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                        class="hidden"
                                        @change="handleFileSelect"
                                    />
                                </div>

                                <!-- Selected Files -->
                                <div v-if="selectedFiles.length > 0" class="mt-4 space-y-2">
                                    <div 
                                        v-for="(file, index) in selectedFiles" 
                                        :key="index"
                                        class="flex items-center justify-between bg-gray-50 p-3 rounded-lg"
                                    >
                                        <div class="flex items-center min-w-0">
                                            <svg class="flex-shrink-0 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <span class="ml-2 text-sm text-gray-700 truncate">{{ file.name }}</span>
                                            <span class="ml-2 text-xs text-gray-500">({{ formatFileSize(file.size) }})</span>
                                        </div>
                                        <button 
                                            type="button"
                                            @click="removeFile(index)"
                                            class="ml-2 text-red-500 hover:text-red-700"
                                        >
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <p v-if="form.errors.documents" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.documents }}
                                </p>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="flex justify-end gap-4">
                            <Link
                                :href="route('funding-sources.show', fundingSource.id)"
                                class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span v-if="form.processing">Submitting...</span>
                                <span v-else>Submit Application</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    <!-- Requirements -->
                    <div v-if="fundingSource.requirements && fundingSource.requirements.length > 0" class="bg-white rounded-lg shadow p-6 mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Eligibility Requirements</h3>
                        <ul class="space-y-2">
                            <li 
                                v-for="(req, index) in fundingSource.requirements" 
                                :key="index"
                                class="flex items-start"
                            >
                                <svg class="flex-shrink-0 h-5 w-5 text-green-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="ml-2 text-sm text-gray-600">{{ req }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Important Info -->
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-yellow-800 mb-3">Before You Apply</h3>
                        <ul class="space-y-2 text-sm text-yellow-700">
                            <li class="flex items-start">
                                <span class="mr-2">•</span>
                                Review all requirements carefully
                            </li>
                            <li class="flex items-start">
                                <span class="mr-2">•</span>
                                Ensure your information is accurate
                            </li>
                            <li class="flex items-start">
                                <span class="mr-2">•</span>
                                Prepare all required documents
                            </li>
                            <li class="flex items-start">
                                <span class="mr-2">•</span>
                                Applications cannot be edited after submission
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
