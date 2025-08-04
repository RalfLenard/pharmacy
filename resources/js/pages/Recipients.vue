<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import { PlusIcon, PencilIcon, EyeIcon, SearchIcon, ChevronDownIcon, ClockIcon, FilterIcon, XIcon, CheckCircle, AlertCircle, XCircle, Users, FileText, Calendar } from 'lucide-vue-next';
import AddRecipientModal from '@/components/Modals/AddRecipientModal.vue';
import UpdateDistributionModal from '@/components/Modals/UpdateDistributionModal.vue';
import AddMedicineModal from '@/components/Modals/AddMedicineModal.vue';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';

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

const page = usePage();
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
const filterExactDate = ref(''); 
const modalPreparedBy = ref('');

// PDF Generation loading state
const isGeneratingPdf = ref(false);
const availableMonths = ref<number[]>([]);
const isLoadingMonths = ref(false);

const filterStartDate = ref(''); // Y-m-d format (e.g. '2025-07-20')
const filterEndDate = ref('');   // Y-m-d format (e.g. '2025-08-04')

// (plus the rest: brandName, lotNumber, etc.)


// Current date for year dropdown
const currentYear = new Date().getFullYear();
const years = Array.from({ length: 10 }, (_, i) => currentYear - i);

const flashMessage = ref('');
const flashType = ref('');
const showFlash = ref(false);

// Month names mapping
const monthNames = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
];

// Debug function to check what flash messages are available
const checkFlashMessages = () => {
    console.log('Page props:', page.props);
    
    // Check for flash messages in different possible locations
    if (page.props.flash) {
        console.log('Flash messages from page.props.flash:', page.props.flash);
    }
    
    // Check for Laravel's default session flash messages
    if (page.props.errors) {
        console.log('Errors:', page.props.errors);
    }
    
    // Check for success message
    if (page.props.success) {
        console.log('Success message:', page.props.success);
    }
};

// Function to display a flash message programmatically
const displayFlash = (message, type = 'success') => {
    flashMessage.value = message;
    flashType.value = type;
    showFlash.value = true;
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        showFlash.value = false;
    }, 5000);
};

// Load available months when year changes
const loadAvailableMonths = async (year?: string) => {
    if (!year) {
        availableMonths.value = [];
        return;
    }

    isLoadingMonths.value = true;
    try {
        const response = await axios.get('/available-months', {
            params: { year }
        });
        availableMonths.value = response.data.months || [];
    } catch (error) {
        console.error('Failed to load available months:', error);
        availableMonths.value = [];
    } finally {
        isLoadingMonths.value = false;
    }
};

// Watch for year changes in PDF modal
watch(filterYear, (newYear) => {
    if (newYear) {
        loadAvailableMonths(newYear);
        // Reset month if it's not available in the new year
        if (filterMonth.value && !availableMonths.value.includes(parseInt(filterMonth.value))) {
            filterMonth.value = '';
        }
    } else {
        availableMonths.value = [];
        filterMonth.value = '';
    }
});

// Check for flash messages on component mount and when page props change
onMounted(() => {
    checkFlashMessages();
    
    // Check for flash messages in different possible locations
    if (page.props.flash?.success) {
        displayFlash(page.props.flash.success, 'success');
    } else if (page.props.flash?.error) {
        displayFlash(page.props.flash.error, 'error');
    } else if (page.props.success) {
        displayFlash(page.props.success, 'success');
    } else if (page.props.error) {
        displayFlash(page.props.error, 'error');
    }
});

// Watch for changes in page props to detect new flash messages
watch(() => page.props, (newProps) => {
    checkFlashMessages();
    
    if (newProps.flash?.success) {
        displayFlash(newProps.flash.success, 'success');
    } else if (newProps.flash?.error) {
        displayFlash(newProps.flash.error, 'error');
    } else if (newProps.success) {
        displayFlash(newProps.success, 'success');
    } else if (newProps.error) {
        displayFlash(newProps.error, 'error');
    }
}, { deep: true });

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

// PDF generation function
const generateFilteredPdf = async () => {
    if (isGeneratingPdf.value) return;

    isGeneratingPdf.value = true;

    try {
        const params: Record<string, string> = {};

        // Inventory Filters
        if (filterBrandName.value.trim()) params.brand_name = filterBrandName.value.trim();
        if (filterGenericName.value.trim()) params.generic_name = filterGenericName.value.trim();
        if (filterLotNumber.value.trim()) params.lot_number = filterLotNumber.value.trim();

        // Recipient Filters
        if (filterBarangay.value.trim()) params.barangay = filterBarangay.value.trim();
        if (filterGender.value.trim()) params.gender = filterGender.value.trim();

        // Prepared By filter (NEW)
        if (modalPreparedBy.value.trim()) params.prepared_by = modalPreparedBy.value.trim();

        // Date Range Filter
        if (filterStartDate.value.trim() && filterEndDate.value.trim()) {
            params.start_date = filterStartDate.value.trim();
            params.end_date = filterEndDate.value.trim();
        } else if (filterExactDate.value.trim()) {
            params.date = filterExactDate.value.trim(); // Optional fallback
        } else if (filterMonth.value && filterYear.value) {
            params.month = String(filterMonth.value);
            params.year = String(filterYear.value);
        }

        // Check if data exists
        const checkResponse = await axios.get('/report/recipient-distributions/check', {
            params,
        });

        if (checkResponse.data.exists) {
            const queryString = new URLSearchParams(params).toString();
            const pdfUrl = `/report/recipient-distributions/pdf${queryString ? '?' + queryString : ''}`;

            window.open(pdfUrl, '_blank');
            displayFlash('PDF generated successfully!', 'success');
        } else {
            displayFlash('No records found for the selected filters.', 'error');
        }
    } catch (error) {
        console.error('PDF generation error:', error);
        if (axios.isAxiosError(error)) {
            if (error.response?.data?.message) {
                displayFlash(error.response.data.message, 'error');
            } else if (error.response?.status === 404) {
                displayFlash('PDF generation endpoint not found.', 'error');
            } else {
                displayFlash('Failed to generate PDF. Please try again.', 'error');
            }
        } else {
            displayFlash('Failed to generate PDF. Please try again.', 'error');
        }
    } finally {
        isGeneratingPdf.value = false;
        showModalFilteredPdf.value = false;
    }
};

// Clear PDF filters
const clearPdfFilters = () => {
    filterBrandName.value = '';
    filterGenericName.value = '';
    filterLotNumber.value = '';
    filterBarangay.value = '';
    filterGender.value = '';
    filterMonth.value = '';
    filterYear.value = '';
    availableMonths.value = [];
};

// Open PDF modal and load initial data
const openPdfModal = () => {
    showModalFilteredPdf.value = true;
    // If year is already selected, load available months
    if (filterYear.value) {
        loadAvailableMonths(filterYear.value);
    }
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
        <div class="w-full px-0 py-6 bg-green-50 min-h-screen">

            <!-- Flash Message Notification -->
            <transition
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showFlash" 
                    class="fixed top-4 right-4 z-50 max-w-md p-4 rounded-lg shadow-lg"
                    :class="[
                        flashType === 'success' 
                            ? 'bg-green-100 text-green-800 border border-green-200' 
                            : 'bg-red-100 text-red-800 border border-red-200'
                    ]">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <CheckCircle v-if="flashType === 'success'" class="h-5 w-5 text-green-500" />
                            <AlertCircle v-else class="h-5 w-5 text-red-500" />
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium">{{ flashMessage }}</p>
                        </div>
                        <div class="ml-auto pl-3">
                            <button @click="showFlash = false" 
                                class="inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2"
                                :class="[
                                    flashType === 'success' 
                                        ? 'text-green-500 hover:bg-green-200 focus:ring-green-400' 
                                        : 'text-red-500 hover:bg-red-200 focus:ring-red-400'
                                ]">
                                <span class="sr-only">Dismiss</span>
                                <XCircle class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 px-4">
                <div>
                    <h1 class="text-4xl font-bold text-green-800 mb-2">Medicine Recipients</h1>
                    <p class="text-green-600">Manage medicine recipients and track distribution records</p>
                </div>
                <button @click="openAddModal"
                    class="mt-4 md:mt-0 flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-all duration-200 shadow-md hover:shadow-lg transform hover:scale-105">
                    <PlusIcon class="w-5 h-5 mr-2" />
                    Add New Recipient
                </button>
            </div>

            <!-- Search and Filters -->
            <div class="bg-white rounded-xl shadow-sm border border-green-100 p-6 mb-8 mx-4">
                <div class="flex items-center gap-2 mb-4">
                    <FilterIcon class="h-5 w-5 text-green-600" />
                    <h3 class="text-lg font-semibold text-green-800">Search & Filter Recipients</h3>
                </div>
                
                <div class="flex flex-col md:flex-row gap-4">
                    <!-- Search input -->
                    <div class="relative flex-grow">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <SearchIcon class="h-5 w-5 text-green-400" />
                        </div>
                        <input v-model="searchQuery" type="text" placeholder="Search recipients by name..."
                            class="pl-12 pr-4 py-3 w-full border border-green-200 rounded-lg focus:ring-2 focus:ring-green-200 focus:border-green-500 transition" />
                    </div>

                    <!-- Quick filters -->
                    <div class="flex flex-wrap gap-3">
                        <!-- Brand Name Dropdown -->
                        <div class="relative">
                            <select v-model="advancedSearch.brand_name" @change="applyAdvancedSearch"
                                class="pl-4 pr-10 py-3 border border-green-200 rounded-lg focus:ring-2 focus:ring-green-200 focus:border-green-500 appearance-none bg-white min-w-[140px]">
                                <option value="">All Brands</option>
                                <option v-for="brand in uniqueBrandNames" :key="brand" :value="brand">
                                    {{ brand }}
                                </option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <ChevronDownIcon class="h-4 w-4 text-green-500" />
                            </div>
                        </div>

                        <!-- Generic Name Dropdown -->
                        <div class="relative">
                            <select v-model="advancedSearch.generic_name" @change="applyAdvancedSearch"
                                class="pl-4 pr-10 py-3 border border-green-200 rounded-lg focus:ring-2 focus:ring-green-200 focus:border-green-500 appearance-none bg-white min-w-[140px]">
                                <option value="">All Generics</option>
                                <option v-for="generic in uniqueGenericNames" :key="generic" :value="generic">
                                    {{ generic }}
                                </option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <ChevronDownIcon class="h-4 w-4 text-green-500" />
                            </div>
                        </div>

                        <!-- Barangay Dropdown -->
                        <div class="relative">
                            <select v-model="advancedSearch.barangay" @change="applyAdvancedSearch"
                                class="pl-4 pr-10 py-3 border border-green-200 rounded-lg focus:ring-2 focus:ring-green-200 focus:border-green-500 appearance-none bg-white min-w-[140px]">
                                <option value="">All Barangays</option>
                                <option v-for="barangay in uniqueBarangays" :key="barangay" :value="barangay">
                                    {{ barangay }}
                                </option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <ChevronDownIcon class="h-4 w-4 text-green-500" />
                            </div>
                        </div>

                        <!-- Advanced Search Button -->
                        <button @click="showAdvancedSearchModal = true"
                            class="px-4 py-3 bg-green-100 hover:bg-green-200 text-green-700 rounded-lg flex items-center transition">
                            <FilterIcon class="w-4 h-4 mr-2" />
                            Advanced
                        </button>

                        <!-- Clear Filters Button (only shown when filters are active) -->
                        <button v-if="hasActiveFilters" @click="clearAllFilters"
                            class="px-4 py-3 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg flex items-center transition">
                            <XIcon class="w-4 h-4 mr-2" />
                            Clear
                        </button>

                        <!-- Generate PDF Button -->
                        <button @click="openPdfModal"
                            class="px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center transition shadow-md hover:shadow-lg transform hover:scale-105">
                            <FileText class="w-4 h-4 mr-2" />
                            Generate PDF
                        </button>
                    </div>
                </div>

                <!-- Active filters display -->
                <div v-if="hasActiveFilters" class="mt-4 flex flex-wrap gap-2">
                    <div v-if="searchQuery" class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm flex items-center border border-green-200">
                        Search: {{ searchQuery }}
                        <button @click="searchQuery = ''" class="ml-2 text-green-600 hover:text-green-800">
                            <XIcon class="w-3 h-3" />
                        </button>
                    </div>
                    <div v-if="advancedSearch.barangay" class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm flex items-center border border-green-200">
                        Barangay: {{ advancedSearch.barangay }}
                        <button @click="advancedSearch.barangay = ''; applyAdvancedSearch()" class="ml-2 text-green-600 hover:text-green-800">
                            <XIcon class="w-3 h-3" />
                        </button>
                    </div>
                    <div v-if="advancedSearch.gender" class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm flex items-center border border-green-200">
                        Gender: {{ advancedSearch.gender }}
                        <button @click="advancedSearch.gender = ''; applyAdvancedSearch()" class="ml-2 text-green-600 hover:text-green-800">
                            <XIcon class="w-3 h-3" />
                        </button>
                    </div>
                    <div v-if="advancedSearch.month" class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm flex items-center border border-green-200">
                        Month: {{ getMonthName(advancedSearch.month) }}
                        <button @click="advancedSearch.month = ''; applyAdvancedSearch()" class="ml-2 text-green-600 hover:text-green-800">
                            <XIcon class="w-3 h-3" />
                        </button>
                    </div>
                    <div v-if="advancedSearch.year" class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm flex items-center border border-green-200">
                        Year: {{ advancedSearch.year }}
                        <button @click="advancedSearch.year = ''; applyAdvancedSearch()" class="ml-2 text-green-600 hover:text-green-800">
                            <XIcon class="w-3 h-3" />
                        </button>
                    </div>
                    <div v-if="advancedSearch.brand_name" class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm flex items-center border border-green-200">
                        Brand: {{ advancedSearch.brand_name }}
                        <button @click="advancedSearch.brand_name = ''; applyAdvancedSearch()" class="ml-2 text-green-600 hover:text-green-800">
                            <XIcon class="w-3 h-3" />
                        </button>
                    </div>
                    <div v-if="advancedSearch.generic_name" class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm flex items-center border border-green-200">
                        Generic: {{ advancedSearch.generic_name }}
                        <button @click="advancedSearch.generic_name = ''; applyAdvancedSearch()" class="ml-2 text-green-600 hover:text-green-800">
                            <XIcon class="w-3 h-3" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Recipients Table -->
            <div class="bg-white rounded-xl shadow-sm border border-green-100 overflow-hidden mx-4">
                <div class="overflow-x-auto w-full">
                    <table class="w-full divide-y divide-green-100">
                        <thead class="bg-green-50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider">
                                    <div class="flex items-center gap-2">
                                        <Users class="h-4 w-4" />
                                        Recipient
                                    </div>
                                </th>
                                <th scope="col"
                                    class="px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider">
                                    Gender
                                </th>
                                <th scope="col"
                                    class="px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider">
                                    <div class="flex items-center gap-2">
                                        <Calendar class="h-4 w-4" />
                                        Age
                                    </div>
                                </th>
                                <th scope="col"
                                    class="px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider">
                                    Barangay
                                </th>
                                <th scope="col"
                                    class="px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider">
                                    Medicine Distributions
                                </th>
                                <th scope="col"
                                    class="px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-green-50">
                            <template v-if="recipientsData.length">
                                <tr v-for="recipient in recipientsData" :key="recipient.id"
                                    class="hover:bg-green-25 transition-colors duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-semibold text-gray-900">{{ recipient.full_name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                            :class="recipient.gender === 'male' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800'">
                                            {{ recipient.gender }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-700 font-medium">
                                            {{ calculateAge(recipient.birthdate) }} years old
                                            <div class="text-xs text-gray-500">
                                                Born: {{ formatDate(recipient.birthdate) }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                                            {{ recipient.barangay }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div v-if="recipient.distributions && recipient.distributions.length" class="space-y-3">
                                            <!-- Most recent medicine display -->
                                            <div v-if="getMostRecentDistribution(recipient.distributions)" class="mb-3">
                                                <div class="flex items-center p-3 rounded-lg text-sm border-2" :class="{
                                                    'bg-green-50 border-green-200': isRecentDistribution(getMostRecentDistribution(recipient.distributions)!.date_given),
                                                    'bg-gray-50 border-gray-200': !isRecentDistribution(getMostRecentDistribution(recipient.distributions)!.date_given)
                                                }">
                                                    <div class="mr-3">
                                                        <ClockIcon class="w-5 h-5 text-green-600" />
                                                    </div>
                                                    <div class="flex-1">
                                                        <div class="font-semibold text-gray-900">
                                                            Latest: {{
                                                                getMostRecentDistribution(recipient.distributions)!.distribution.inventory.brand_name
                                                            }} {{
                                                                getMostRecentDistribution(recipient.distributions)!.distribution.inventory.generic_name
                                                            }} 
                                                            <span class="text-xs font-normal text-gray-500 bg-gray-100 px-2 py-1 rounded">
                                                                {{
                                                                    getMostRecentDistribution(recipient.distributions)!.distribution.inventory.lot_number
                                                                }}
                                                            </span>
                                                        </div>
                                                        <div class="text-xs text-gray-600 mt-1">
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
                                                        class="p-2 text-green-600 hover:text-green-800 hover:bg-green-100 rounded-lg transition">
                                                        <PencilIcon class="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Dropdown header with count of unique medicines -->
                                            <div @click="toggleDistributions(recipient.id)"
                                                class="flex items-center justify-between p-3 bg-green-100 rounded-lg text-sm cursor-pointer hover:bg-green-200 transition border border-green-200">
                                                <div class="font-semibold text-green-800 uppercase">
                                                    ALL MEDICINES RECEIVED ({{
                                                        countAllMedicinesReceived(recipient.distributions) }})
                                                </div>

                                                <div class="flex items-center">
                                                    <ChevronDownIcon class="w-5 h-5 transition-transform text-green-600"
                                                        :class="{ 'transform rotate-180': isExpanded(recipient.id) }" />
                                                </div>
                                            </div>

                                            <!-- Expanded distributions list -->
                                            <div v-if="isExpanded(recipient.id)"
                                                class="space-y-2 pl-4 mt-3 border-l-4 border-green-200">
                                                <!-- Medicine counts summary -->
                                                <div class="px-3 py-2 text-xs text-gray-600 flex flex-wrap gap-2 mb-3 bg-green-25 rounded-lg">
                                                    <span
                                                        v-for="(count, medicine) in countDistributionsByMedicine(recipient.distributions)"
                                                        :key="medicine"
                                                        class="inline-flex items-center px-2.5 py-1 rounded-full bg-green-100 text-green-800 border border-green-200">
                                                        {{ medicine }}: <span class="ml-1 font-semibold">{{ count }}</span>
                                                    </span>
                                                </div>

                                                <!-- Individual distributions sorted by newest first -->
                                                <div v-for="dist in sortedDistributions(recipient.distributions)"
                                                    :key="dist.id"
                                                    class="flex items-center justify-between p-3 bg-gray-50 rounded-lg text-sm border border-gray-200 hover:bg-gray-100 transition">
                                                    <div>
                                                        <span class="font-semibold text-gray-900">{{
                                                            dist.distribution.inventory.brand_name }} {{
                                                                dist.distribution.inventory.generic_name }}</span>
                                                        <span class="text-xs font-normal text-gray-500 bg-gray-200 px-2 py-1 rounded ml-2">
                                                            {{
                                                                dist.distribution.inventory.lot_number }}
                                                        </span>
                                                        <div class="text-xs text-gray-600 mt-1">
                                                            Qty: {{ dist.quantity }} • Given: {{
                                                                formatDate(dist.date_given) }}
                                                        </div>
                                                    </div>
                                                    <button @click.stop="openUpdateModal(dist)"
                                                        class="p-2 text-green-600 hover:text-green-800 hover:bg-green-100 rounded-lg transition">
                                                        <PencilIcon class="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-else class="text-sm text-gray-500 italic bg-gray-50 p-4 rounded-lg border border-gray-200">
                                            <div class="flex items-center justify-center">
                                                <FileText class="h-8 w-8 text-gray-300 mr-2" />
                                                No distributions yet
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button class="text-green-600 hover:text-green-800 hover:bg-green-100 px-3 py-2 rounded-lg transition"
                                            @click="openAddMedicineModal(recipient)">
                                            Add Medicine
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <tr v-else>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <Users class="w-16 h-16 text-green-200 mb-4" />
                                        <p class="text-lg font-medium text-gray-700">No recipients found</p>
                                        <p class="text-sm text-gray-500">Try adjusting your search criteria or add a new recipient</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div v-if="props.recipients.links && props.recipients.links.length > 3" class="px-6 py-4 border-t border-green-100 bg-green-25">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-green-700">
                            Showing {{ props.recipients.from || 0 }} to {{ props.recipients.to || 0 }} of {{ props.recipients.total || 0 }} recipients
                        </div>
                        <div class="flex space-x-2">
                            <button 
                                v-if="props.recipients.prev_page_url"
                                @click="goToPage(props.recipients.prev_page_url)"
                                class="px-3 py-2 rounded-lg border border-green-200 text-sm hover:bg-green-100 transition"
                            >
                                Previous
                            </button>
                            
                            <template v-for="(link, i) in props.recipients.links" :key="i">
                                <button 
                                    v-if="i > 0 && i < props.recipients.links.length - 1"
                                    @click="goToPage(link.url)"
                                    class="px-3 py-2 rounded-lg border text-sm transition"
                                    :class="link.active ? 'bg-green-600 text-white border-green-600' : 'border-green-200 hover:bg-green-100'"
                                >
                                    {{ link.label }}
                                </button>
                            </template>
                            
                            <button 
                                v-if="props.recipients.next_page_url"
                                @click="goToPage(props.recipients.next_page_url)"
                                class="px-3 py-2 rounded-lg border border-green-200 text-sm hover:bg-green-100 transition"
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
            class="fixed inset-0 bg-black/20 backdrop-blur-sm flex items-center justify-center z-50">
            <div class="bg-white text-gray-900 p-8 rounded-xl shadow-2xl w-full max-w-md mx-4 border border-green-100">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-green-800">Advanced Search</h3>
                    <button @click="showAdvancedSearchModal = false" class="text-gray-500 hover:text-gray-700">
                        <XIcon class="w-6 h-6" />
                    </button>
                </div>

                <div class="space-y-4">
                    <!-- Lot Number Filter -->
                    <div>
                        <label for="lotNumberSearch" class="block text-sm font-semibold text-green-700 mb-2">Lot Number</label>
                        <select v-model="advancedSearch.lot_number" id="lotNumberSearch"
                            class="w-full p-3 border border-green-200 rounded-lg focus:ring-2 focus:ring-green-200 focus:border-green-500 transition">
                            <option value="">All Lot Numbers</option>
                            <option v-for="lotNumber in uniqueLotNumbers" :key="lotNumber" :value="lotNumber">
                                {{ lotNumber }}
                            </option>
                        </select>
                    </div>

                    <!-- Brand Name Filter -->
                    <div>
                        <label for="brandNameSearch" class="block text-sm font-semibold text-green-700 mb-2">Brand Name</label>
                        <select v-model="advancedSearch.brand_name" id="brandNameSearch"
                            class="w-full p-3 border border-green-200 rounded-lg focus:ring-2 focus:ring-green-200 focus:border-green-500 transition">
                            <option value="">All Brands</option>
                            <option v-for="brand in uniqueBrandNames" :key="brand" :value="brand">
                                {{ brand }}
                            </option>
                        </select>
                    </div>

                    <!-- Generic Name Filter -->
                    <div>
                        <label for="genericNameSearch" class="block text-sm font-semibold text-green-700 mb-2">Generic Name</label>
                        <select v-model="advancedSearch.generic_name" id="genericNameSearch"
                            class="w-full p-3 border border-green-200 rounded-lg focus:ring-2 focus:ring-green-200 focus:border-green-500 transition">
                            <option value="">All Generics</option>
                            <option v-for="generic in uniqueGenericNames" :key="generic" :value="generic">
                                {{ generic }}
                            </option>
                        </select>
                    </div>

                    <!-- Barangay Filter -->
                    <div>
                        <label for="barangaySearch" class="block text-sm font-semibold text-green-700 mb-2">Barangay</label>
                        <select v-model="advancedSearch.barangay" id="barangaySearch"
                            class="w-full p-3 border border-green-200 rounded-lg focus:ring-2 focus:ring-green-200 focus:border-green-500 transition">
                            <option value="">All Barangays</option>
                            <option v-for="barangay in uniqueBarangays" :key="barangay" :value="barangay">
                                {{ barangay }}
                            </option>
                        </select>
                    </div>

                    <!-- Gender Filter -->
                    <div>
                        <label for="genderSearch" class="block text-sm font-semibold text-green-700 mb-2">Gender</label>
                        <select v-model="advancedSearch.gender" id="genderSearch"
                            class="w-full p-3 border border-green-200 rounded-lg focus:ring-2 focus:ring-green-200 focus:border-green-500 transition">
                            <option value="">All Genders</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>

                    <!-- Month Filter -->
                    <div>
                        <label for="monthSearch" class="block text-sm font-semibold text-green-700 mb-2">Month</label>
                        <select v-model="advancedSearch.month" id="monthSearch"
                            class="w-full p-3 border border-green-200 rounded-lg focus:ring-2 focus:ring-green-200 focus:border-green-500 transition">
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
                        <label for="yearSearch" class="block text-sm font-semibold text-green-700 mb-2">Year</label>
                        <select v-model="advancedSearch.year" id="yearSearch"
                            class="w-full p-3 border border-green-200 rounded-lg focus:ring-2 focus:ring-green-200 focus:border-green-500 transition">
                            <option value="">All Years</option>
                            <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-8">
                    <button @click="clearAllFilters"
                        class="px-6 py-3 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Clear All
                    </button>
                    <button @click="applyAdvancedSearch"
                        class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition shadow-md hover:shadow-lg transform hover:scale-105">
                        Apply Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Enhanced Modal for Filtered PDF Generation -->
     <div v-if="showModalFilteredPdf"
     class="fixed inset-0 bg-black/20 backdrop-blur-sm flex items-center justify-center z-50">
  <div
    class="bg-white text-gray-900 p-8 rounded-xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto mx-4 border border-green-100">
    <div class="flex justify-between items-center mb-6">
      <h3 class="text-xl font-bold text-green-800">Generate Distribution Report</h3>
      <button @click="showModalFilteredPdf = false" class="text-gray-500 hover:text-gray-700">
        <XIcon class="w-6 h-6" />
      </button>
    </div>

    <div class="space-y-4">
      <!-- Brand Name Filter -->
      <div>
        <label for="pdfBrandName" class="block text-sm font-semibold text-green-700 mb-2">Brand Name</label>
        <select v-model="filterBrandName" id="pdfBrandName"
                class="w-full p-3 border border-green-200 rounded-lg focus:ring-2 focus:ring-green-200 focus:border-green-500 transition">
          <option value="">All Brands</option>
          <option v-for="brand in uniqueBrandNames" :key="brand" :value="brand">{{ brand }}</option>
        </select>
      </div>

      <!-- Generic Name Filter -->
      <div>
        <label for="pdfGenericName" class="block text-sm font-semibold text-green-700 mb-2">Generic Name</label>
        <select v-model="filterGenericName" id="pdfGenericName"
                class="w-full p-3 border border-green-200 rounded-lg focus:ring-2 focus:ring-green-200 focus:border-green-500 transition">
          <option value="">All Generics</option>
          <option v-for="generic in uniqueGenericNames" :key="generic" :value="generic">{{ generic }}</option>
        </select>
      </div>

      <!-- Lot Number Filter -->
      <div>
        <label for="pdfLotNumber" class="block text-sm font-semibold text-green-700 mb-2">Lot Number</label>
        <select v-model="filterLotNumber" id="pdfLotNumber"
                class="w-full p-3 border border-green-200 rounded-lg focus:ring-2 focus:ring-green-200 focus:border-green-500 transition">
          <option value="">All Lot Numbers</option>
          <option v-for="lotNumber in uniqueLotNumbers" :key="lotNumber" :value="lotNumber">{{ lotNumber }}</option>
        </select>
      </div>

      <!-- Barangay Filter -->
      <div>
        <label for="pdfBarangay" class="block text-sm font-semibold text-green-700 mb-2">Barangay</label>
        <select v-model="filterBarangay" id="pdfBarangay"
                class="w-full p-3 border border-green-200 rounded-lg focus:ring-2 focus:ring-green-200 focus:border-green-500 transition">
          <option value="">All Barangays</option>
          <option v-for="barangay in uniqueBarangays" :key="barangay" :value="barangay">{{ barangay }}</option>
        </select>
      </div>

      <!-- Gender Filter -->
      <div>
        <label for="pdfGender" class="block text-sm font-semibold text-green-700 mb-2">Gender</label>
        <select v-model="filterGender" id="pdfGender"
                class="w-full p-3 border border-green-200 rounded-lg focus:ring-2 focus:ring-green-200 focus:border-green-500 transition">
          <option value="">All Genders</option>
          <option value="male">Male</option>
          <option value="female">Female</option>
        </select>
      </div>

      <!-- Date Range Filters -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <!-- Start Date -->
        <div>
          <label for="pdfStartDate" class="block text-sm font-semibold text-green-700 mb-2">Start Date</label>
          <input
            type="date"
            id="pdfStartDate"
            v-model="filterStartDate"
            class="w-full p-3 border border-green-200 rounded-lg focus:ring-2 focus:ring-green-200 focus:border-green-500 transition"
          />
        </div>

        <!-- End Date -->
        <div>
          <label for="pdfEndDate" class="block text-sm font-semibold text-green-700 mb-2">End Date</label>
          <input
            type="date"
            id="pdfEndDate"
            v-model="filterEndDate"
            class="w-full p-3 border border-green-200 rounded-lg focus:ring-2 focus:ring-green-200 focus:border-green-500 transition"
          />
        </div>

      
        
      </div>
    </div>

    <div class="mb-6">
                        <label for="preparedByInput" class="block text-sm font-semibold text-green-700 mb-2">
                            Prepared By
                        </label>
                        <select
                            id="preparedByInput"
                            v-model="modalPreparedBy"
                            class="w-full p-3 border border-green-200 rounded-lg focus:ring-2 focus:ring-green-200 focus:border-green-500 transition"
                        >
                            <option value="">Select Preparer</option>
                            <option value="Micah Laine Sabalbirino">Micah Laine Sabalbirino</option>
                            <option value="Justin Gail Tolentino">Justin Gail Tolentino</option>
                            <option value="Cristel Ann Castro">Cristel Ann Castro</option>
                            <!-- Add more as needed -->
                        </select>
                    </div>

    <!-- Action Buttons -->
    <div class="flex justify-between gap-3 mt-8">
      <button @click="clearPdfFilters"
              class="px-4 py-3 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
        Clear Filters
      </button>

      <div class="flex gap-3">
        <button @click="showModalFilteredPdf = false"
                class="px-4 py-3 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
          Cancel
        </button>
        <button @click="generateFilteredPdf"
                :disabled="isGeneratingPdf"
                class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition shadow-md hover:shadow-lg transform hover:scale-105 flex items-center">
          <svg v-if="isGeneratingPdf" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
               xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10"
                    stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 
                     1.135 5.824 3 7.938l3-2.647z">
            </path>
          </svg>
          {{ isGeneratingPdf ? 'Generating...' : 'Generate PDF' }}
        </button>
      </div>
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

<style scoped>
.hover\:bg-green-25:hover {
    background-color: #f0fdf4;
}

.bg-green-25 {
    background-color: #f0fdf4;
}
</style>