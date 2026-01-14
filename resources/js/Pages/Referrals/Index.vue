<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Avatar from 'primevue/avatar';
import Badge from 'primevue/badge';
import Message from 'primevue/message';
import Divider from 'primevue/divider';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    referralCode: String,
    referralLink: String,
    referrals: Array,
    referralRecords: Array,
    stats: Object,
    referrer: Object,
    maxReferralLevel: Number,
    settings: Object,
});

const toast = useToast();
const copySuccess = ref(false);

const formatCurrency = (value) => {
    const symbol = props.settings?.currency_symbol || '$';
    return `${symbol}${parseFloat(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const copyToClipboard = async (text, type = 'link') => {
    try {
        await navigator.clipboard.writeText(text);
        copySuccess.value = true;
        toast.add({
            severity: 'success',
            summary: 'Copied!',
            detail: type === 'code' ? 'Referral code copied to clipboard' : 'Referral link copied to clipboard',
            life: 3000,
        });
        setTimeout(() => {
            copySuccess.value = false;
        }, 2000);
    } catch (err) {
        toast.add({
            severity: 'error',
            summary: 'Failed',
            detail: 'Could not copy to clipboard',
            life: 3000,
        });
    }
};

const shareVia = (platform) => {
    const text = `Join me on National Resource Benefits! Use my referral code: ${props.referralCode}`;
    const url = props.referralLink;
    
    let shareUrl = '';
    
    switch (platform) {
        case 'twitter':
            shareUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(url)}`;
            break;
        case 'facebook':
            shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
            break;
        case 'whatsapp':
            shareUrl = `https://wa.me/?text=${encodeURIComponent(text + ' ' + url)}`;
            break;
        case 'telegram':
            shareUrl = `https://t.me/share/url?url=${encodeURIComponent(url)}&text=${encodeURIComponent(text)}`;
            break;
        case 'email':
            shareUrl = `mailto:?subject=${encodeURIComponent('Join National Resource Benefits')}&body=${encodeURIComponent(text + '\n\n' + url)}`;
            break;
    }
    
    if (shareUrl) {
        window.open(shareUrl, '_blank');
    }
};

const getStatusSeverity = (status) => {
    const map = {
        'pending': 'warn',
        'verified': 'success',
        'completed': 'success',
        'paid': 'info',
    };
    return map[status] || 'secondary';
};
</script>

<template>
    <Head title="Referrals" />
    
    <DashboardLayout>
        <template #header>Referral Program</template>
        
        <Toast />
        
        <div class="space-y-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <Card class="shadow-sm">
                    <template #content>
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                                <i class="pi pi-users text-xl text-blue-600"></i>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900">{{ stats?.total_referrals || 0 }}</p>
                                <p class="text-sm text-gray-500">Total Referrals</p>
                            </div>
                        </div>
                    </template>
                </Card>
                
                <Card class="shadow-sm">
                    <template #content>
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                                <i class="pi pi-check-circle text-xl text-green-600"></i>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900">{{ stats?.verified_referrals || 0 }}</p>
                                <p class="text-sm text-gray-500">Verified</p>
                            </div>
                        </div>
                    </template>
                </Card>
                
                <Card class="shadow-sm">
                    <template #content>
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center">
                                <i class="pi pi-dollar text-xl text-purple-600"></i>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-green-600">{{ formatCurrency(stats?.total_earnings || 0) }}</p>
                                <p class="text-sm text-gray-500">Total Earned</p>
                            </div>
                        </div>
                    </template>
                </Card>
                
                <Card class="shadow-sm">
                    <template #content>
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-yellow-100 flex items-center justify-center">
                                <i class="pi pi-clock text-xl text-yellow-600"></i>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-yellow-600">{{ formatCurrency(stats?.pending_earnings || 0) }}</p>
                                <p class="text-sm text-gray-500">Pending</p>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
            
            <!-- Referral Link Card -->
            <Card class="shadow-sm bg-gradient-to-br from-primary-50 to-blue-50">
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-share-alt text-primary-600"></i>
                        <span>Your Referral Link</span>
                    </div>
                </template>
                <template #content>
                    <div class="space-y-6">
                        <!-- Referral Code -->
                        <div class="flex flex-col md:flex-row gap-4">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Referral Code</label>
                                <div class="flex gap-2">
                                    <InputText 
                                        :value="referralCode" 
                                        readonly 
                                        class="w-full font-mono text-lg font-bold tracking-wider"
                                    />
                                    <Button 
                                        icon="pi pi-copy" 
                                        severity="secondary"
                                        @click="copyToClipboard(referralCode, 'code')"
                                        v-tooltip.top="'Copy Code'"
                                    />
                                </div>
                            </div>
                            
                            <div class="flex-[2]">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Referral Link</label>
                                <div class="flex gap-2">
                                    <InputText 
                                        :value="referralLink" 
                                        readonly 
                                        class="w-full text-sm"
                                    />
                                    <Button 
                                        icon="pi pi-copy" 
                                        severity="secondary"
                                        @click="copyToClipboard(referralLink, 'link')"
                                        v-tooltip.top="'Copy Link'"
                                    />
                                </div>
                            </div>
                        </div>
                        
                        <!-- Share Buttons -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Share via</label>
                            <div class="flex flex-wrap gap-2">
                                <Button 
                                    icon="pi pi-twitter" 
                                    label="Twitter"
                                    severity="info"
                                    outlined
                                    size="small"
                                    @click="shareVia('twitter')"
                                />
                                <Button 
                                    icon="pi pi-facebook" 
                                    label="Facebook"
                                    severity="info"
                                    outlined
                                    size="small"
                                    @click="shareVia('facebook')"
                                />
                                <Button 
                                    icon="pi pi-whatsapp" 
                                    label="WhatsApp"
                                    severity="success"
                                    outlined
                                    size="small"
                                    @click="shareVia('whatsapp')"
                                />
                                <Button 
                                    icon="pi pi-telegram" 
                                    label="Telegram"
                                    severity="info"
                                    outlined
                                    size="small"
                                    @click="shareVia('telegram')"
                                />
                                <Button 
                                    icon="pi pi-envelope" 
                                    label="Email"
                                    severity="secondary"
                                    outlined
                                    size="small"
                                    @click="shareVia('email')"
                                />
                            </div>
                        </div>
                    </div>
                </template>
            </Card>
            
            <!-- Deposit Commission Levels -->
            <Card v-if="settings?.deposit_enabled && settings?.deposit_levels?.length" class="shadow-sm">
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-download text-green-600"></i>
                        <span>Deposit Commission Levels</span>
                        <Badge :value="`${maxReferralLevel} of ${settings.deposit_levels.length} Unlocked`" severity="success" />
                    </div>
                </template>
                <template #subtitle>
                    <span class="text-sm text-gray-500">Earn commission when your referrals make deposits</span>
                </template>
                <template #content>
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                        <div 
                            v-for="level in settings.deposit_levels" 
                            :key="level.level"
                            :class="[
                                'text-center p-4 rounded-lg border transition-all',
                                level.unlocked 
                                    ? 'border-green-200 bg-green-50' 
                                    : 'border-gray-200 bg-gray-50 opacity-60'
                            ]"
                        >
                            <div :class="[
                                'w-10 h-10 rounded-full flex items-center justify-center mx-auto mb-2',
                                level.unlocked ? 'bg-green-100' : 'bg-gray-200'
                            ]">
                                <i v-if="!level.unlocked" class="pi pi-lock text-gray-400"></i>
                                <span v-else class="font-bold text-green-600">{{ level.level }}</span>
                            </div>
                            <p :class="['text-2xl font-bold', level.unlocked ? 'text-gray-900' : 'text-gray-400']">
                                {{ level.commission }}%
                            </p>
                            <p class="text-xs text-gray-500">Level {{ level.level }}</p>
                            <Tag 
                                v-if="level.unlocked" 
                                value="Active" 
                                severity="success" 
                                class="mt-2"
                                size="small"
                            />
                            <Tag 
                                v-else 
                                value="Locked" 
                                severity="secondary" 
                                class="mt-2"
                                size="small"
                            />
                        </div>
                    </div>
                </template>
            </Card>
            
            <!-- Payment Commission Levels -->
            <Card v-if="settings?.payment_enabled && settings?.payment_levels?.length" class="shadow-sm">
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-credit-card text-purple-600"></i>
                        <span>Payment Commission Levels</span>
                        <Badge :value="`${maxReferralLevel} of ${settings.payment_levels.length} Unlocked`" severity="info" />
                    </div>
                </template>
                <template #subtitle>
                    <span class="text-sm text-gray-500">Earn commission when your referrals make payments</span>
                </template>
                <template #content>
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                        <div 
                            v-for="level in settings.payment_levels" 
                            :key="level.level"
                            :class="[
                                'text-center p-4 rounded-lg border transition-all',
                                level.unlocked 
                                    ? 'border-purple-200 bg-purple-50' 
                                    : 'border-gray-200 bg-gray-50 opacity-60'
                            ]"
                        >
                            <div :class="[
                                'w-10 h-10 rounded-full flex items-center justify-center mx-auto mb-2',
                                level.unlocked ? 'bg-purple-100' : 'bg-gray-200'
                            ]">
                                <i v-if="!level.unlocked" class="pi pi-lock text-gray-400"></i>
                                <span v-else class="font-bold text-purple-600">{{ level.level }}</span>
                            </div>
                            <p :class="['text-2xl font-bold', level.unlocked ? 'text-gray-900' : 'text-gray-400']">
                                {{ level.commission }}%
                            </p>
                            <p class="text-xs text-gray-500">Level {{ level.level }}</p>
                            <Tag 
                                v-if="level.unlocked" 
                                value="Active" 
                                severity="info" 
                                class="mt-2"
                                size="small"
                            />
                            <Tag 
                                v-else 
                                value="Locked" 
                                severity="secondary" 
                                class="mt-2"
                                size="small"
                            />
                        </div>
                    </div>
                </template>
            </Card>
            
            <!-- Commission Info -->
            <Message v-if="settings?.deposit_enabled || settings?.payment_enabled" severity="info" :closable="false">
                <template #messageicon>
                    <i class="pi pi-info-circle"></i>
                </template>
                Higher ranks unlock more referral levels! Level 1 earns from direct referrals, Level 2 from their referrals, and so on.
            </Message>
            
            <!-- Referred By -->
            <Card v-if="referrer" class="shadow-sm">
                <template #content>
                    <div class="flex items-center gap-4">
                        <Avatar 
                            :label="referrer.name?.charAt(0).toUpperCase()" 
                            size="large"
                            shape="circle"
                            class="bg-primary-100 text-primary-700"
                        />
                        <div>
                            <p class="text-sm text-gray-500">You were referred by</p>
                            <p class="font-medium text-gray-900">{{ referrer.name }}</p>
                        </div>
                    </div>
                </template>
            </Card>
            
            <!-- My Referrals Table -->
            <Card class="shadow-sm">
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-users text-primary-600"></i>
                        <span>My Referrals</span>
                        <Badge :value="referrals?.length || 0" severity="secondary" />
                    </div>
                </template>
                <template #content>
                    <DataTable 
                        :value="referrals || []"
                        :paginator="referrals?.length > 10"
                        :rows="10"
                        dataKey="id"
                        stripedRows
                        responsiveLayout="scroll"
                        class="p-datatable-sm"
                    >
                        <template #empty>
                            <div class="text-center py-12">
                                <i class="pi pi-users text-4xl text-gray-300 mb-4"></i>
                                <p class="text-gray-500 mb-2">No referrals yet</p>
                                <p class="text-sm text-gray-400">Share your referral link to start earning!</p>
                            </div>
                        </template>
                        
                        <Column header="User" style="min-width: 200px">
                            <template #body="{ data }">
                                <div class="flex items-center gap-3">
                                    <Avatar 
                                        :label="data.name?.charAt(0).toUpperCase()" 
                                        shape="circle"
                                        class="bg-gray-100 text-gray-600"
                                    />
                                    <div>
                                        <p class="font-medium text-gray-900">{{ data.name }}</p>
                                        <p class="text-xs text-gray-500">{{ data.email }}</p>
                                    </div>
                                </div>
                            </template>
                        </Column>
                        
                        <Column field="status" header="Status" style="min-width: 120px">
                            <template #body="{ data }">
                                <Tag 
                                    :value="data.status === 'verified' ? 'Verified' : 'Pending'" 
                                    :severity="getStatusSeverity(data.status)"
                                />
                            </template>
                        </Column>
                        
                        <Column field="joined_at" header="Joined" style="min-width: 150px">
                            <template #body="{ data }">
                                <span class="text-sm text-gray-600">{{ data.joined_at_human }}</span>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>
            
            <!-- Referral Rewards History -->
            <Card v-if="referralRecords && referralRecords.length > 0" class="shadow-sm">
                <template #title>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="pi pi-history text-primary-600"></i>
                            <span>Reward History</span>
                        </div>
                        <Link v-if="route().has('referrals.earnings')" :href="route('referrals.earnings')">
                            <Button label="View All" icon="pi pi-arrow-right" iconPos="right" text size="small" />
                        </Link>
                    </div>
                </template>
                <template #content>
                    <DataTable 
                        :value="referralRecords.slice(0, 5)"
                        dataKey="id"
                        stripedRows
                        responsiveLayout="scroll"
                        class="p-datatable-sm"
                    >
                        <Column header="Referee" style="min-width: 150px">
                            <template #body="{ data }">
                                <span class="font-medium">{{ data.referee_name }}</span>
                            </template>
                        </Column>
                        
                        <Column field="reward_amount" header="Reward" style="min-width: 120px">
                            <template #body="{ data }">
                                <span class="font-medium text-green-600">{{ formatCurrency(data.reward_amount) }}</span>
                            </template>
                        </Column>
                        
                        <Column field="status" header="Status" style="min-width: 100px">
                            <template #body="{ data }">
                                <Tag 
                                    :value="data.status?.charAt(0).toUpperCase() + data.status?.slice(1)" 
                                    :severity="getStatusSeverity(data.status)"
                                />
                            </template>
                        </Column>
                        
                        <Column field="completed_at" header="Date" style="min-width: 130px">
                            <template #body="{ data }">
                                <span class="text-sm text-gray-600">
                                    {{ formatDate(data.completed_at || data.created_at) }}
                                </span>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>
            
            <!-- How It Works -->
            <Card class="shadow-sm">
                <template #title>
                    <div class="flex items-center gap-2">
                        <i class="pi pi-question-circle text-primary-600"></i>
                        <span>How It Works</span>
                    </div>
                </template>
                <template #content>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="text-center">
                            <div class="w-14 h-14 rounded-full bg-primary-100 flex items-center justify-center mx-auto mb-3">
                                <span class="text-2xl font-bold text-primary-600">1</span>
                            </div>
                            <h4 class="font-semibold text-gray-900 mb-1">Share Your Link</h4>
                            <p class="text-sm text-gray-500">Share your unique referral link with friends and family</p>
                        </div>
                        
                        <div class="text-center">
                            <div class="w-14 h-14 rounded-full bg-primary-100 flex items-center justify-center mx-auto mb-3">
                                <span class="text-2xl font-bold text-primary-600">2</span>
                            </div>
                            <h4 class="font-semibold text-gray-900 mb-1">They Sign Up</h4>
                            <p class="text-sm text-gray-500">When they register using your link, they become your referral</p>
                        </div>
                        
                        <div class="text-center">
                            <div class="w-14 h-14 rounded-full bg-primary-100 flex items-center justify-center mx-auto mb-3">
                                <span class="text-2xl font-bold text-primary-600">3</span>
                            </div>
                            <h4 class="font-semibold text-gray-900 mb-1">Earn Rewards</h4>
                            <p class="text-sm text-gray-500">Earn commission when your referrals make deposits</p>
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    </DashboardLayout>
</template>
