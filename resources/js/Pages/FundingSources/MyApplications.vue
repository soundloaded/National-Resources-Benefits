<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    applications: Object,
    filters: Object,
    statuses: Object,
});

const selectedStatus = ref(props.filters.status || 'all');

const filterByStatus = (status) => {
    selectedStatus.value = status;
    router.get(route('my-applications.index'), { status }, { preserveState: true });
};

const getStatusColor = (status) => {
    const colors = {
        pending: 'bg-yellow-100 text-yellow-800',
        under_review: 'bg-blue-100 text-blue-800',
        approved: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800',
        disbursed: 'bg-emerald-100 text-emerald-800',
        cancelled: 'bg-gray-100 text-gray-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <Head title="My Applications" />

    <DashboardLayout>
        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">My Applications</h1>
                    <p class="mt-1 text-sm text-gray-500">Track your funding application status</p>
                </div>
                <Link
                    :href="route('funding-sources.index')"
                    class="mt-4 sm:mt-0 inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    New Application
                </Link>
            </div>

            <!-- Status Filters -->
            <div class="mb-6 flex flex-wrap gap-2">
                <button
                    @click="filterByStatus('all')"
                    :class="[
                        'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                        selectedStatus === 'all' 
                            ? 'bg-gray-900 text-white' 
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                    ]"
                >
                    All
                </button>
                <button
                    v-for="(label, key) in statuses"
                    :key="key"
                    @click="filterByStatus(key)"
                    :class="[
                        'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                        selectedStatus === key 
                            ? 'bg-gray-900 text-white' 
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                    ]"
                >
                    {{ label }}
                </button>
            </div>

            <!-- Applications List -->
            <div v-if="applications.data.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Application #
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Funding Source
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Requested
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Approved
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Applied On
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="application in applications.data" :key="application.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900">{{ application.application_number }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-900">{{ application.funding_source }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900">${{ Number(application.requested_amount).toLocaleString() }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span v-if="application.approved_amount" class="text-sm text-green-600 font-medium">
                                        ${{ Number(application.approved_amount).toLocaleString() }}
                                    </span>
                                    <span v-else class="text-sm text-gray-400">—</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusColor(application.status)]">
                                        {{ application.status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-500">{{ application.created_at }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <Link 
                                        :href="route('my-applications.show', application.id)"
                                        class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                                    >
                                        View
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="applications.links && applications.links.length > 3" class="px-6 py-4 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <p class="text-sm text-gray-500">
                            Showing {{ applications.from }} to {{ applications.to }} of {{ applications.total }} results
                        </p>
                        <div class="flex gap-2">
                            <Link
                                v-for="link in applications.links"
                                :key="link.label"
                                :href="link.url"
                                :class="[
                                    'px-3 py-1 text-sm rounded',
                                    link.active 
                                        ? 'bg-blue-600 text-white' 
                                        : link.url 
                                            ? 'bg-gray-100 text-gray-700 hover:bg-gray-200' 
                                            : 'bg-gray-50 text-gray-400 cursor-not-allowed'
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white rounded-lg shadow p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No applications yet</h3>
                <p class="mt-2 text-gray-500">
                    Browse available funding sources and submit your first application.
                </p>
                <Link
                    :href="route('funding-sources.index')"
                    class="mt-6 inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                >
                    Browse Funding Sources
                </Link>
            </div>
        </div>
    </DashboardLayout>
</template>
