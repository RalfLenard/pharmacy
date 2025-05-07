<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { PlusIcon, PencilIcon, EyeIcon, SearchIcon, ChevronDownIcon, ClockIcon, FilterIcon, XIcon } from 'lucide-vue-next';
import AddRecipientModal from '@/components/Modals/AddRecipientModal.vue';
import UpdateDistributionModal from '@/components/Modals/UpdateDistributionModal.vue';
import AddMedicineModal from '@/components/Modals/AddMedicineModal.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Recipients',
        href: '/recipients',
    },
];

// Types
interface Recipient {
    id: number;
    full_name: string;
    birthdate: string;
    barangay: string;
    gender: string;
    distributions: RecipientDistribution[];
    created_at: string;
}

interface RecipientDistribution {
    id: number;
    recipient_id: number;
    distribution_id: number;
    quantity: number;
    date_given: string;
    distribution: {
        id: number;
        name: string;
        inventory: {
            id: number;
            generic_name: string;
            brand_name: string;
            lot_number: string;
        }
    }
}

interface PaginatedData {
    current_page: number;
    data: Recipient[];
    first_page_url: string;
    from: number;
    last_page: number;
    last_page_url: string;
    links: Array<{url: string | null, label: string, active: boolean}>;
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number;
    total: number;
}

// Props from controller
interface Props {
    recipients: PaginatedData;
    filters?: {
        search?: string;
        barangay?: string;
        gender?: string;
        month?: string;
        year?: string;
        brand_name?: string;
        generic_name?: string;
        lot_number?: string;
    };
}

const props = defineProps<Props>();
const showModalFilteredPdf = ref(false);
const showAdvancedSearchModal = ref(false);
const filterMedicine = ref('');
const filterBarangay = ref(props.filters?.barangay || '');
const filterGender = ref(props.filters?.gender || '');
const filterMonth = ref(props.filters?.month || '');
const filterYear = ref(props.filters?.year || '');
const filterLotNumber = ref(props.filters?.lot_number || '');
const filterBrandName = ref(props.filters?.brand_name || '');
const filterGenericName = ref(props.filters?.generic_name || '');

// Current date for year dropdown
const currentYear = new Date().getFullYear();
const years = Array.from({ length: 10 }, (_, i) => currentYear - i);

// Advanced search form
const advancedSearch = ref({
    barangay: props.filters?.barangay || '',
    gender: props.filters?.gender || '',
    month: props.filters?.month || '',
    year: props.filters?.year || '',
    brand_name: props.filters?.brand_name || '',
    generic_name: props.filters?.generic_name || '',
    lot_number: props.filters?.lot_number || ''
});

// Get recipients data from paginated object
const recipientsData = computed(() => {
    return props.recipients.data || [];
});

// Unique lot numbers from all distributions
const uniqueLotNumbers = computed(() => {
    const lotNumbers = new Set<string>();
    
    if (!recipientsData.value) return [];
    
    recipientsData.value.forEach(recipient => {
        if (!recipient.distributions) return;
        
        recipient.distributions.forEach(dist => {
            if (dist.distribution?.inventory?.lot_number) {
                lotNumbers.add(dist.distribution.inventory.lot_number);
            }
        });
    });
    
    return Array.from(lotNumbers).sort();
});

// Unique brand names from all distributions
const uniqueBrandNames = computed(() => {
    const brandNames = new Set<string>();
    
    if (!recipientsData.value) return [];
    
    recipientsData.value.forEach(recipient => {
        if (!recipient.distributions) return;
        
        recipient.distributions.forEach(dist => {
            if (dist.distribution?.inventory?.brand_name) {
                brandNames.add(dist.distribution.inventory.brand_name);
            }
        });
    });
    
    return Array.from(brandNames).sort();
});

// Unique generic names from all distributions
const uniqueGenericNames = computed(() => {
    const genericNames = new Set<string>();
    
    if (!recipientsData.value) return [];
    
    recipientsData.value.forEach(recipient => {
        if (!recipient.distributions) return;
        
        recipient.distributions.forEach(dist => {
            if (dist.distribution?.inventory?.generic_name) {
                genericNames.add(dist.distribution.inventory.generic_name);
            }
        });
    });
    
    return Array.from(genericNames).sort();
});

// Unique barangays from all recipients
const uniqueBarangays = computed(() => {
    const barangays = new Set<string>();
    
    if (!recipientsData.value) return [];
    
    recipientsData.value.forEach(recipient => {
        if (recipient.barangay) {
            barangays.add(recipient.barangay);
        }
    });
    
    return Array.from(barangays).sort();
});

const generateFilteredPdf = () => {
    // Start with the base URL for generating the PDF
    let url = `/report/recipient-distributions/pdf?`;  // Change to match the route

    // Add query parameters if the user has entered values
    if (filterMedicine.value) {
        url += `medicine=${encodeURIComponent(filterMedicine.value)}&`;
    }
    if (filterBarangay.value) {
        url += `barangay=${encodeURIComponent(filterBarangay.value)}&`;
    }
    if (filterGender.value) {
        url += `gender=${encodeURIComponent(filterGender.value)}&`;
    }
    if (filterMonth.value) {
        url += `month=${encodeURIComponent(filterMonth.value)}&`;
    }
    if (filterYear.value) {
        url += `year=${encodeURIComponent(filterYear.value)}&`;
    }
    if (filterBrandName.value) {
        url += `brand_name=${encodeURIComponent(filterBrandName.value)}&`;
    }
    if (filterGenericName.value) {
        url += `generic_name=${encodeURIComponent(filterGenericName.value)}&`;
    }
    if (filterLotNumber.value) {
        url += `lot_number=${encodeURIComponent(filterLotNumber.value)}&`;
    }

    // Remove trailing "&"
    url = url.slice(0, -1);

    // Open the URL that will trigger the controller method to generate the PDF
    window.open(url, '_blank');

    // Close the modal after generating the PDF
    showModalFilteredPdf.value = false;
};

// Apply advanced search filters
const applyAdvancedSearch = () => {
    router.get('/recipients', {
        search: searchQuery.value,
        barangay: advancedSearch.value.barangay,
        gender: advancedSearch.value.gender,
        month: advancedSearch.value.month,
        year: advancedSearch.value.year,
        brand_name: advancedSearch.value.brand_name,
        generic_name: advancedSearch.value.generic_name,
        lot_number: advancedSearch.value.lot_number
    }, {
        preserveState: true,
        replace: true
    });
    
    showAdvancedSearchModal.value = false;
};

// Clear all filters
const clearAllFilters = () => {
    searchQuery.value = '';
    advancedSearch.value = {
        barangay: '',
        gender: '',
        month: '',
        year: '',
        brand_name: '',
        generic_name: '',
        lot_number: ''
    };
    
    router.get('/recipients', {}, {
        preserveState: true,
        replace: true
    });
};

// State
const searchQuery = ref(props.filters?.search || '');
const showAddModal = ref(false);
const showUpdateModal = ref(false);
const showAddMedicineModal = ref(false);
const selectedDistribution = ref<RecipientDistribution | null>(null);
const selectedRecipient = ref<Recipient | null>(null);
const expandedRecipients = ref<Record<number, boolean>>({});

// Watch for changes in search query and apply filter with debounce
let searchTimeout: number | null = null;
watch(searchQuery, (newValue) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    
    searchTimeout = setTimeout(() => {
        router.get('/recipients', {
            search: newValue,
            barangay: advancedSearch.value.barangay,
            gender: advancedSearch.value.gender,
            month: advancedSearch.value.month,
            year: advancedSearch.value.year,
            brand_name: advancedSearch.value.brand_name,
            generic_name: advancedSearch.value.generic_name,
            lot_number: advancedSearch.value.lot_number
        }, {
            preserveState: true,
            replace: true
        });
    }, 500) as unknown as number;
});

// Check if any filters are active
const hasActiveFilters = computed(() => {
    return searchQuery.value || 
           advancedSearch.value.barangay || 
           advancedSearch.value.gender || 
           advancedSearch.value.month ||
           advancedSearch.value.year ||
           advancedSearch.value.brand_name ||
           advancedSearch.value.generic_name ||
           advancedSearch.value.lot_number;
});

// Methods
const openAddModal = () => {
    showAddModal.value = true;
};

const openUpdateModal = (distribution: RecipientDistribution) => {
    selectedDistribution.value = distribution;
    showUpdateModal.value = true;
};

const openAddMedicineModal = (recipient: Recipient) => {
    selectedRecipient.value = recipient;
    showAddMedicineModal.value = true;
};

const toggleDistributions = (recipientId: number) => {
    expandedRecipients.value[recipientId] = !expandedRecipients.value[recipientId];
};

const isExpanded = (recipientId: number) => {
    return !!expandedRecipients.value[recipientId];
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
};

const calculateAge = (birthdate: string) => {
    const birth = new Date(birthdate);
    const today = new Date();
    let age = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();

    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
        age--;
    }

    return age;
};

// Sort distributions by date (newest first)
const sortedDistributions = (distributions: RecipientDistribution[]) => {
    return [...distributions].sort((a, b) => {
        return new Date(b.date_given).getTime() - new Date(a.date_given).getTime();
    });
};

// Get the most recent distribution
const getMostRecentDistribution = (distributions: RecipientDistribution[]) => {
    if (!distributions.length) return null;

    return sortedDistributions(distributions)[0];
};

// Count unique medicines
const countUniqueMedicines = (distributions: RecipientDistribution[]) => {
    const uniqueMedicines = new Set();

    distributions.forEach(dist => {
        uniqueMedicines.add(dist.distribution.inventory.brand_name);
    });

    return uniqueMedicines.size;
};

const countAllMedicinesReceived = (distributions: RecipientDistribution[]) => {
    return distributions.length;
};

// Count distributions by medicine
const countDistributionsByMedicine = (distributions: RecipientDistribution[]) => {
    const counts: Record<string, number> = {};

    distributions.forEach(dist => {
        const medicineName = dist.distribution.inventory.id;
        counts[medicineName] = (counts[medicineName] || 0) + 1;
    });

    return counts;
};

// Check if a distribution is recent (within the last 7 days)
const isRecentDistribution = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = Math.abs(now.getTime() - date.getTime());
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    return diffDays <= 7;
};

// Get month name from number
const getMonthName = (monthNumber: string) => {
    const months = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];
    return months[parseInt(monthNumber) - 1] || '';
};

// Navigate to a specific page
const goToPage = (url: string | null) => {
    if (url) {
        router.visit(url, {
            preserveState: true,
            replace: true
        });
    }
};
</script>

<template>
    <Head title="Recipients" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full px-0 py-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 px-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Recipients</h1>
                    <p class="text-gray-600">Manage medicine recipients and distributions</p>
                </div>
                <button @click="openAddModal"
                    class="mt-4 md:mt-0 flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md transition-colors">
                    <PlusIcon class="w-4 h-4 mr-2" />
                    Add Recipient
                </button>
            </div>

            <!-- Search and Filters -->
            <div class="bg-white rounded-lg shadow-sm col-span-2 p-4 mb-6 mx-4">
                <div class="flex flex-col md:flex-row gap-4">
                    <!-- Search input -->
                    <div class="relative flex-grow">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <SearchIcon class="h-5 w-5 text-gray-400" />
                        </div>
                        <input v-model="searchQuery" type="text" placeholder="Search recipients by name..."
                            class="pl-10 pr-4 py-2 w-full border border-gray-300 rounded-md focus:ring-emerald-500 focus:border-emerald-500" />
                    </div>

                    <!-- Quick filters -->
                    <div class="flex flex-wrap gap-2">
                        <!-- Brand Name Dropdown -->
                        <div class="relative">
                            <select v-model="advancedSearch.brand_name" @change="applyAdvancedSearch"
                                class="pl-3 pr-8 py-2 border border-gray-300 rounded-md focus:ring-emerald-500 focus:border-emerald-500 appearance-none">
                                <option value="">All Brands</option>
                                <option v-for="brand in uniqueBrandNames" :key="brand" :value="brand">
                                    {{ brand }}
                                </option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                <ChevronDownIcon class="h-4 w-4 text-gray-400" />
                            </div>
                        </div>

                        <!-- Generic Name Dropdown -->
                        <div class="relative">
                            <select v-model="advancedSearch.generic_name" @change="applyAdvancedSearch"
                                class="pl-3 pr-8 py-2 border border-gray-300 rounded-md focus:ring-emerald-500 focus:border-emerald-500 appearance-none">
                                <option value="">All Generics</option>
                                <option v-for="generic in uniqueGenericNames" :key="generic" :value="generic">
                                    {{ generic }}
                                </option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                <ChevronDownIcon class="h-4 w-4 text-gray-400" />
                            </div>
                        </div>

                        <!-- Barangay Dropdown -->
                        <div class="relative">
                            <select v-model="advancedSearch.barangay" @change="applyAdvancedSearch"
                                class="pl-3 pr-8 py-2 border border-gray-300 rounded-md focus:ring-emerald-500 focus:border-emerald-500 appearance-none">
                                <option value="">All Barangays</option>
                                <option v-for="barangay in uniqueBarangays" :key="barangay" :value="barangay">
                                    {{ barangay }}
                                </option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                <ChevronDownIcon class="h-4 w-4 text-gray-400" />
                            </div>
                        </div>

                        <!-- Advanced Search Button -->
                        <button @click="showAdvancedSearchModal = true"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md flex items-center">
                            <FilterIcon class="w-4 h-4 mr-2" />
                            Advanced
                        </button>

                        <!-- Clear Filters Button (only shown when filters are active) -->
                        <button v-if="hasActiveFilters" @click="clearAllFilters"
                            class="px-4 py-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-md flex items-center">
                            <XIcon class="w-4 h-4 mr-2" />
                            Clear
                        </button>
                    </div>
                </div>

                <!-- Active filters display -->
                <div v-if="hasActiveFilters" class="mt-3 flex flex-wrap gap-2">
                    <div v-if="searchQuery" class="px-2 py-1 bg-emerald-50 text-emerald-700 rounded-full text-xs flex items-center">
                        Search: {{ searchQuery }}
                        <button @click="searchQuery = ''" class="ml-1 text-emerald-500 hover:text-emerald-700">
                            <XIcon class="w-3 h-3" />
                        </button>
                    </div>
                    <div v-if="advancedSearch.barangay" class="px-2 py-1 bg-emerald-50 text-emerald-700 rounded-full text-xs flex items-center">
                        Barangay: {{ advancedSearch.barangay }}
                        <button @click="advancedSearch.barangay = ''; applyAdvancedSearch()" class="ml-1 text-emerald-500 hover:text-emerald-700">
                            <XIcon class="w-3 h-3" />
                        </button>
                    </div>
                    <div v-if="advancedSearch.gender" class="px-2 py-1 bg-emerald-50 text-emerald-700 rounded-full text-xs flex items-center">
                        Gender: {{ advancedSearch.gender }}
                        <button @click="advancedSearch.gender = ''; applyAdvancedSearch()" class="ml-1 text-emerald-500 hover:text-emerald-700">
                            <XIcon class="w-3 h-3" />
                        </button>
                    </div>
                    <div v-if="advancedSearch.month" class="px-2 py-1 bg-emerald-50 text-emerald-700 rounded-full text-xs flex items-center">
                        Month: {{ getMonthName(advancedSearch.month) }}
                        <button @click="advancedSearch.month = ''; applyAdvancedSearch()" class="ml-1 text-emerald-500 hover:text-emerald-700">
                            <XIcon class="w-3 h-3" />
                        </button>
                    </div>
                    <div v-if="advancedSearch.year" class="px-2 py-1 bg-emerald-50 text-emerald-700 rounded-full text-xs flex items-center">
                        Year: {{ advancedSearch.year }}
                        <button @click="advancedSearch.year = ''; applyAdvancedSearch()" class="ml-1 text-emerald-500 hover:text-emerald-700">
                            <XIcon class="w-3 h-3" />
                        </button>
                    </div>
                    <div v-if="advancedSearch.brand_name" class="px-2 py-1 bg-emerald-50 text-emerald-700 rounded-full text-xs flex items-center">
                        Brand: {{ advancedSearch.brand_name }}
                        <button @click="advancedSearch.brand_name = ''; applyAdvancedSearch()" class="ml-1 text-emerald-500 hover:text-emerald-700">
                            <XIcon class="w-3 h-3" />
                        </button>
                    </div>
                    <div v-if="advancedSearch.generic_name" class="px-2 py-1 bg-emerald-50 text-emerald-700 rounded-full text-xs flex items-center">
                        Generic: {{ advancedSearch.generic_name }}
                        <button @click="advancedSearch.generic_name = ''; applyAdvancedSearch()" class="ml-1 text-emerald-500 hover:text-emerald-700">
                            <XIcon class="w-3 h-3" />
                        </button>
                    </div>
                </div>

                <!-- Div wrapping the Button -->
                <div class="flex justify-end mt-4">
                    <button @click="showModalFilteredPdf = true"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Generate Filtered PDF
                    </button>
                </div>
            </div>

            <!-- Recipients Table -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden mx-0">
                <div class="overflow-x-auto w-full">
                    <table class="w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Recipient
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Gender
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Age
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Barangay
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Distributions
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template v-if="recipientsData.length">
                                <tr v-for="recipient in recipientsData" :key="recipient.id"
                                    class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-medium text-gray-900">{{ recipient.full_name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-medium text-gray-900">{{ recipient.gender }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500">
                                            {{ calculateAge(recipient.birthdate) }} years
                                            <div class="text-xs text-gray-400">
                                                Born: {{ formatDate(recipient.birthdate) }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500">{{ recipient.barangay }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div v-if="recipient.distributions && recipient.distributions.length" class="space-y-2">
                                            <!-- Most recent medicine display -->
                                            <div v-if="getMostRecentDistribution(recipient.distributions)" class="mb-2">
                                                <div class="flex items-center p-2 rounded-md text-sm" :class="{
                                                    'bg-emerald-50 border border-emerald-200': isRecentDistribution(getMostRecentDistribution(recipient.distributions)!.date_given),
                                                    'bg-gray-50 border border-gray-200': !isRecentDistribution(getMostRecentDistribution(recipient.distributions)!.date_given)
                                                }">
                                                    <div class="mr-2">
                                                        <ClockIcon class="w-4 h-4 text-emerald-500" />
                                                    </div>
                                                    <div class="flex-1">
                                                        <div class="font-medium">
                                                            Latest: {{
                                                                getMostRecentDistribution(recipient.distributions)!.distribution.inventory.brand_name
                                                            }} {{
                                                                getMostRecentDistribution(recipient.distributions)!.distribution.inventory.generic_name
                                                            }} 
                                                            <span class="text-xs font-normal text-gray-500">
                                                                ({{
                                                                    getMostRecentDistribution(recipient.distributions)!.distribution.inventory.lot_number
                                                                }})
                                                            </span>
                                                        </div>
                                                        <div class="text-xs text-gray-500">
                                                            Qty: {{
                                                                getMostRecentDistribution(recipient.distributions)!.quantity
                                                            }} •
                                                            Given: {{
                                                                formatDate(getMostRecentDistribution(recipient.distributions)!.date_given)
                                                            }}
                                                        </div>
                                                    </div>
                                                    <button
                                                        @click.stop="openUpdateModal(getMostRecentDistribution(recipient.distributions)!)"
                                                        class="p-1 text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 rounded-full">
                                                        <PencilIcon class="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Dropdown header with count of unique medicines -->
                                            <div @click="toggleDistributions(recipient.id)"
                                                class="flex items-center justify-between p-2 bg-gray-100 rounded-md text-sm cursor-pointer hover:bg-gray-200">
                                                <div class="font-medium uppercase">
                                                    ALL MEDICINES RECEIVED ({{
                                                        countAllMedicinesReceived(recipient.distributions) }})
                                                </div>

                                                <div class="flex items-center">
                                                    <ChevronDownIcon class="w-4 h-4 transition-transform"
                                                        :class="{ 'transform rotate-180': isExpanded(recipient.id) }" />
                                                </div>
                                            </div>

                                            <!-- Expanded distributions list -->
                                            <div v-if="isExpanded(recipient.id)"
                                                class="space-y-2 pl-2 mt-2 border-l-2 border-emerald-100">
                                                <!-- Medicine counts summary -->
                                                <div class="px-2 py-1 text-xs text-gray-500 flex flex-wrap gap-2 mb-2">
                                                    <span
                                                        v-for="(count, medicine) in countDistributionsByMedicine(recipient.distributions)"
                                                        :key="medicine"
                                                        class="inline-flex items-center px-2 py-1 rounded-full bg-emerald-50 text-emerald-700">
                                                        {{ medicine }}: <span class="ml-1 font-medium">{{ count
                                                            }}</span>
                                                    </span>
                                                </div>

                                                <!-- Individual distributions sorted by newest first -->
                                                <div v-for="dist in sortedDistributions(recipient.distributions)"
                                                    :key="dist.id"
                                                    class="flex items-center justify-between p-2 bg-gray-50 rounded-md text-sm">
                                                    <div>
                                                        <span class="font-medium">{{
                                                            dist.distribution.inventory.brand_name }} {{
                                                                dist.distribution.inventory.generic_name }}</span>
                                                        <span class="text-xs font-normal text-gray-500">
                                                            ({{
                                                                dist.distribution.inventory.lot_number }})
                                                        </span>
                                                        <div class="text-xs text-gray-500">
                                                            Qty: {{ dist.quantity }} • Given: {{
                                                                formatDate(dist.date_given) }}
                                                        </div>
                                                    </div>
                                                    <button @click.stop="openUpdateModal(dist)"
                                                        class="p-1 text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 rounded-full">
                                                        <PencilIcon class="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-else class="text-sm text-gray-500 italic">
                                            No distributions yet
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button class="text-emerald-600 hover:text-emerald-900 mr-3"
                                            @click="openAddMedicineModal(recipient)">
                                            Add Medicine
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <tr v-else>
                                <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-24 h-24 text-gray-200 mb-4"></div>
                                        <p class="text-lg font-medium">No recipients found</p>
                                        <p class="text-sm">Try adjusting your search or add a new recipient</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div v-if="props.recipients.links && props.recipients.links.length > 3" class="px-6 py-4 border-t">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500">
                            Showing {{ props.recipients.from || 0 }} to {{ props.recipients.to || 0 }} of {{ props.recipients.total || 0 }} recipients
                        </div>
                        <div class="flex space-x-1">
                            <button 
                                v-if="props.recipients.prev_page_url"
                                @click="goToPage(props.recipients.prev_page_url)"
                                class="px-3 py-1 rounded border text-sm hover:bg-gray-50"
                            >
                                Previous
                            </button>
                            
                            <template v-for="(link, i) in props.recipients.links" :key="i">
                                <button 
                                    v-if="i > 0 && i < props.recipients.links.length - 1"
                                    @click="goToPage(link.url)"
                                    class="px-3 py-1 rounded border text-sm"
                                    :class="link.active ? 'bg-emerald-50 text-emerald-600 border-emerald-200' : 'hover:bg-gray-50'"
                                >
                                    {{ link.label }}
                                </button>
                            </template>
                            
                            <button 
                                v-if="props.recipients.next_page_url"
                                @click="goToPage(props.recipients.next_page_url)"
                                class="px-3 py-1 rounded border text-sm hover:bg-gray-50"
                            >
                                Next
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Advanced Search Modal -->
        <div v-if="showAdvancedSearchModal"
            class="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center z-50">
            <div
                class="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 p-6 rounded-lg shadow-lg w-full max-w-md">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Advanced Search</h3>
                    <button @click="showAdvancedSearchModal = false" class="text-gray-500 hover:text-gray-700">
                        <XIcon class="w-5 h-5" />
                    </button>
                </div>

                <div class="space-y-4">
                    <!-- Brand Name Filter -->
                    <div>
                        <label for="lotNumberSearch" class="block text-sm font-medium mb-1">Lot Number</label>
                        <select v-model="advancedSearch.lot_number" id="lotNumberSearch"
                            class="w-full p-2 border rounded focus:ring focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">All Lot Numbers</option>
                            <option v-for="lotNumber in uniqueLotNumbers" :key="lotNumber" :value="lotNumber">
                                {{ lotNumber }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label for="brandNameSearch" class="block text-sm font-medium mb-1">Brand Name</label>
                        <select v-model="advancedSearch.brand_name" id="brandNameSearch"
                            class="w-full p-2 border rounded focus:ring focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">All Brands</option>
                            <option v-for="brand in uniqueBrandNames" :key="brand" :value="brand">
                                {{ brand }}
                            </option>
                        </select>
                    </div>

                    <!-- Generic Name Filter -->
                    <div>
                        <label for="genericNameSearch" class="block text-sm font-medium mb-1">Generic Name</label>
                        <select v-model="advancedSearch.generic_name" id="genericNameSearch"
                            class="w-full p-2 border rounded focus:ring focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">All Generics</option>
                            <option v-for="generic in uniqueGenericNames" :key="generic" :value="generic">
                                {{ generic }}
                            </option>
                        </select>
                    </div>

                    <!-- Barangay Filter -->
                    <div>
                        <label for="barangaySearch" class="block text-sm font-medium mb-1">Barangay</label>
                        <select v-model="advancedSearch.barangay" id="barangaySearch"
                            class="w-full p-2 border rounded focus:ring focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">All Barangays</option>
                            <option v-for="barangay in uniqueBarangays" :key="barangay" :value="barangay">
                                {{ barangay }}
                            </option>
                        </select>
                    </div>

                    <!-- Gender Filter -->
                    <div>
                        <label for="genderSearch" class="block text-sm font-medium mb-1">Gender</label>
                        <select v-model="advancedSearch.gender" id="genderSearch"
                            class="w-full p-2 border rounded focus:ring focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">All Genders</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>

                    <!-- Month Filter -->
                    <div>
                        <label for="monthSearch" class="block text-sm font-medium mb-1">Month</label>
                        <select v-model="advancedSearch.month" id="monthSearch"
                            class="w-full p-2 border rounded focus:ring focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">All Months</option>
                            <option value="1">January</option>
                            <option value="2">February</option>
                            <option value="3">March</option>
                            <option value="4">April</option>
                            <option value="5">May</option>
                            <option value="6">June</option>
                            <option value="7">July</option>
                            <option value="8">August</option>
                            <option value="9">September</option>
                            <option value="10">October</option>
                            <option value="11">November</option>
                            <option value="12">December</option>
                        </select>
                    </div>

                    <!-- Year Filter -->
                    <div>
                        <label for="yearSearch" class="block text-sm font-medium mb-1">Year</label>
                        <select v-model="advancedSearch.year" id="yearSearch"
                            class="w-full p-2 border rounded focus:ring focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">All Years</option>
                            <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button @click="clearAllFilters"
                        class="px-4 py-2 text-gray-700 dark:text-gray-300 border rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                        Clear All
                    </button>
                    <button @click="applyAdvancedSearch"
                        class="px-4 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700">
                        Apply Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal for Filtered PDF Generation -->
        <div v-if="showModalFilteredPdf"
            class="fixed inset-0 bg-white/50 backdrop-blur-sm flex items-center justify-center z-50">
            <div
                class="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 p-6 rounded-lg shadow-lg w-full max-w-md">
                <h3 class="text-lg font-semibold mb-4">Generate Filtered Distribution Report</h3>

                <!-- Medicine Filter -->
                <label for="medicineInput" class="block text-sm font-medium mb-1">Medicine (Brand/Generic)</label>
                <input v-model="filterMedicine" id="medicineInput" type="text" placeholder="e.g., Paracetamol"
                    class="w-full p-2 mb-4 border rounded focus:ring focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />

                <!-- Brand Name Filter -->
                <label for="brandNameInput" class="block text-sm font-medium mb-1">Brand Name</label>
                <select v-model="filterBrandName" id="brandNameInput"
                    class="w-full p-2 mb-4 border rounded focus:ring focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">All Brands</option>
                    <option v-for="brand in uniqueBrandNames" :key="brand" :value="brand">
                        {{ brand }}
                    </option>
                </select>

                <!-- Generic Name Filter -->
                <label for="genericNameInput" class="block text-sm font-medium mb-1">Generic Name</label>
                <select v-model="filterGenericName" id="genericNameInput"
                    class="w-full p-2 mb-4 border rounded focus:ring focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">All Generics</option>
                    <option v-for="generic in uniqueGenericNames" :key="generic" :value="generic">
                        {{ generic }}
                    </option>
                </select>

                <!-- Lot Number Filter -->
                <label for="lotNumberInput" class="block text-sm font-medium mb-1">Lot Number</label>
                <select v-model="filterLotNumber" id="lotNumberInput"
                    class="w-full p-2 mb-4 border rounded focus:ring focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">All Lot Numbers</option>
                    <option v-for="lotNumber in uniqueLotNumbers" :key="lotNumber" :value="lotNumber">
                        {{ lotNumber }}
                    </option>
                </select>

                <!-- Barangay Filter -->
                <label for="barangayInput" class="block text-sm font-medium mb-1">Barangay</label>
                <select v-model="filterBarangay" id="barangayInput"
                    class="w-full p-2 mb-4 border rounded focus:ring focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">All Barangays</option>
                    <option v-for="barangay in uniqueBarangays" :key="barangay" :value="barangay">
                        {{ barangay }}
                    </option>
                </select>

                <!-- Gender Filter -->
                <label for="genderInput" class="block text-sm font-medium mb-1">Gender</label>
                <select v-model="filterGender" id="genderInput"
                    class="w-full p-2 mb-4 border rounded focus:ring focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">All Genders</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>

                <!-- Month Filter -->
                <label for="monthInput" class="block text-sm font-medium mb-1">Month</label>
                <select v-model="filterMonth" id="monthInput"
                    class="w-full p-2 mb-4 border rounded focus:ring focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">All Months</option>
                    <option value="1">January</option>
                    <option value="2">February</option>
                    <option value="3">March</option>
                    <option value="4">April</option>
                    <option value="5">May</option>
                    <option value="6">June</option>
                    <option value="7">July</option>
                    <option value="8">August</option>
                    <option value="9">September</option>
                    <option value="10">October</option>
                    <option value="11">November</option>
                    <option value="12">December</option>
                </select>

                <!-- Year Filter -->
                <label for="yearInput" class="block text-sm font-medium mb-1">Year</label>
                <select v-model="filterYear" id="yearInput"
                    class="w-full p-2 mb-4 border rounded focus:ring focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">All Years</option>
                    <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                </select>

                <div class="flex justify-end gap-2">
                    <button @click="showModalFilteredPdf = false"
                        class="px-4 py-2 text-gray-700 dark:text-gray-300 border rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                        Cancel
                    </button>
                    <button @click="generateFilteredPdf"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Generate PDF
                    </button>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <AddRecipientModal v-if="showAddModal" @close="showAddModal = false" />

        <UpdateDistributionModal v-if="showUpdateModal" :distribution="selectedDistribution"
            @close="showUpdateModal = false" />

        <!-- New Add Medicine Modal -->
        <AddMedicineModal v-if="showAddMedicineModal" :recipient="selectedRecipient"
            @close="showAddMedicineModal = false" />
    </AppLayout>
</template>