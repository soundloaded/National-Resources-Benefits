<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import FileUpload from 'primevue/fileupload';
import Message from 'primevue/message';

const props = defineProps({
    template: Object,
});

// Initialize form with empty fields based on template
const initialFields = {};
props.template.form_fields?.forEach((field, index) => {
    initialFields[index] = field.type === 'file' ? null : '';
});

const form = useForm({
    fields: initialFields,
});

// Track file previews
const filePreviews = ref({});

// Handle file selection
const onFileSelect = (event, index) => {
    const file = event.files[0];
    if (file) {
        form.fields[index] = file;
        
        // Create preview for images
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                filePreviews.value[index] = e.target.result;
            };
            reader.readAsDataURL(file);
        } else {
            filePreviews.value[index] = null;
        }
    }
};

// Clear file
const onFileClear = (index) => {
    form.fields[index] = null;
    delete filePreviews.value[index];
};

// Check if a field is required
const isRequired = (field) => {
    return field.required === 'true' || field.required === true;
};

// Get field error
const getFieldError = (index) => {
    return form.errors[`fields.${index}`];
};

// Submit form
const submit = () => {
    form.post(route('kyc.store', props.template.id), {
        forceFormData: true,
        onError: () => {
            // Scroll to first error
            const firstError = document.querySelector('.p-message-error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        },
    });
};

// Check if form is valid (all required fields filled)
const isFormValid = computed(() => {
    return props.template.form_fields?.every((field, index) => {
        if (!isRequired(field)) return true;
        const value = form.fields[index];
        return value !== null && value !== '';
    });
});
</script>

<template>
    <Head :title="`Submit ${template.title}`" />

    <DashboardLayout>
        <div class="max-w-3xl mx-auto space-y-6">
            <!-- Breadcrumb -->
            <nav class="flex items-center gap-2 text-sm">
                <Link :href="route('kyc.index')" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">
                    KYC Verification
                </Link>
                <i class="pi pi-chevron-right text-gray-400 text-xs"></i>
                <span class="text-gray-600 dark:text-gray-400">{{ template.title }}</span>
            </nav>

            <!-- Page Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ template.title }}</h1>
                <p v-if="template.description" class="text-gray-600 dark:text-gray-400 mt-1">
                    {{ template.description }}
                </p>
            </div>

            <!-- Instructions -->
            <Message severity="info" :closable="false" class="w-full">
                <div class="flex items-start gap-3">
                    <i class="pi pi-info-circle text-lg flex-shrink-0 mt-0.5"></i>
                    <div>
                        <p class="font-medium mb-1">Submission Guidelines:</p>
                        <ul class="text-sm space-y-1 list-disc list-inside">
                            <li>Ensure all documents are clear and readable</li>
                            <li>File formats accepted: JPG, PNG, PDF (max 10MB)</li>
                            <li>Double-check all information before submitting</li>
                            <li>Review typically takes 1-2 business days</li>
                        </ul>
                    </div>
                </div>
            </Message>

            <!-- Form -->
            <form @submit.prevent="submit">
                <Card class="shadow-sm">
                    <template #content>
                        <div class="space-y-6">
                            <div 
                                v-for="(field, index) in template.form_fields" 
                                :key="index"
                                class="space-y-2"
                            >
                                <!-- Field Label -->
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ field.label }}
                                    <span v-if="isRequired(field)" class="text-red-500">*</span>
                                </label>

                                <!-- Text Input -->
                                <div v-if="field.type === 'text'">
                                    <InputText
                                        v-model="form.fields[index]"
                                        :placeholder="`Enter ${field.label.toLowerCase()}`"
                                        class="w-full"
                                        :class="{ 'p-invalid': getFieldError(index) }"
                                    />
                                </div>

                                <!-- Number Input -->
                                <div v-else-if="field.type === 'number'">
                                    <InputNumber
                                        v-model="form.fields[index]"
                                        :placeholder="`Enter ${field.label.toLowerCase()}`"
                                        class="w-full"
                                        :class="{ 'p-invalid': getFieldError(index) }"
                                        :useGrouping="false"
                                    />
                                </div>

                                <!-- File Upload -->
                                <div v-else-if="field.type === 'file'">
                                    <div v-if="!form.fields[index]">
                                        <FileUpload
                                            mode="basic"
                                            :auto="false"
                                            accept="image/*,.pdf"
                                            :maxFileSize="10485760"
                                            @select="(e) => onFileSelect(e, index)"
                                            chooseLabel="Choose File"
                                            class="w-full"
                                            :class="{ 'p-invalid': getFieldError(index) }"
                                        />
                                        <p class="text-xs text-gray-500 mt-1">
                                            Accepted: JPG, PNG, PDF (max 10MB)
                                        </p>
                                    </div>

                                    <!-- File Preview -->
                                    <div v-else class="border rounded-lg p-4 bg-gray-50 dark:bg-gray-800">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <!-- Image Preview -->
                                                <img 
                                                    v-if="filePreviews[index]"
                                                    :src="filePreviews[index]"
                                                    class="w-16 h-16 object-cover rounded-lg"
                                                    alt="Preview"
                                                />
                                                <!-- PDF Icon -->
                                                <div v-else class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                                                    <i class="pi pi-file-pdf text-2xl text-red-500"></i>
                                                </div>
                                                <div>
                                                    <p class="font-medium text-gray-900 dark:text-white text-sm">
                                                        {{ form.fields[index]?.name }}
                                                    </p>
                                                    <p class="text-xs text-gray-500">
                                                        {{ (form.fields[index]?.size / 1024 / 1024).toFixed(2) }} MB
                                                    </p>
                                                </div>
                                            </div>
                                            <Button 
                                                icon="pi pi-times" 
                                                severity="danger" 
                                                text 
                                                rounded
                                                @click="onFileClear(index)"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Field Error -->
                                <Message 
                                    v-if="getFieldError(index)" 
                                    severity="error" 
                                    :closable="false"
                                    class="mt-1"
                                >
                                    {{ getFieldError(index) }}
                                </Message>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Form Actions -->
                <div class="flex flex-col-reverse sm:flex-row justify-between gap-4 mt-6">
                    <Link :href="route('kyc.index')">
                        <Button 
                            type="button"
                            label="Cancel" 
                            severity="secondary" 
                            outlined
                            class="w-full sm:w-auto"
                        />
                    </Link>
                    <Button 
                        type="submit"
                        label="Submit for Review"
                        icon="pi pi-check"
                        :loading="form.processing"
                        :disabled="!isFormValid || form.processing"
                        class="w-full sm:w-auto"
                    />
                </div>
            </form>

            <!-- Security Notice -->
            <div class="flex items-start gap-3 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                <i class="pi pi-shield text-green-500 text-xl flex-shrink-0"></i>
                <div>
                    <p class="font-medium text-green-900 dark:text-green-100 text-sm">Your Data is Secure</p>
                    <p class="text-xs text-green-700 dark:text-green-300">
                        All documents are encrypted and stored securely. Your information is only used for verification purposes.
                    </p>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
