<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage, router } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import { 
    PlusCircle, 
    Search, 
    Edit, 
    Trash2, 
    Package, 
    CheckCircle, 
    XCircle, 
    AlertCircle,
    PlusIcon, 
    SearchIcon, 
    ChevronDownIcon,
    FilterIcon,
    FileText,
    Calendar,
    ChevronLeftIcon,
    ChevronRightIcon,
    PackageIcon
} from 'lucide-vue-next';
import { ref, computed, watch, onMounted } from 'vue';

// Import modal components
import AddItemModal from '@/components/Modals/AddItem.vue';
import EditItemModal from '@/components/Modals/EditItem.vue';
import DeleteItemModal from '@/components/Modals/DeleteItem.vue';
import DistributeItem from '@/components/Modals/DistributeItem.vue';
import axios from 'axios';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Stock Room',
        href: '/inventory',
    },
];

// Types
interface InventoryItem {
    id: number;
    brand_name: string;
    generic_name: string;
    lot_number: string;
    expiration_date: string;
    date_in: string;
    quantity: number;
    stocks: number;
    utils: string;
    supplier?: string;
    created_at: string;
    updated_at: string;
}

interface PaginatedInventory {
    current_page: number;
    data: InventoryItem[];
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

interface Props {
    inventory: PaginatedInventory;
    filters?: {
        search?: string;
    };
}

const page = usePage();
const props = defineProps<Props>();

// Get inventory data from paginated object
const inventoryData = computed(() => {
    return props.inventory.data || [];
});

// Flash messages
const flashMessage = ref('');
const flashType = ref('');
const showFlash = ref(false);

// Function to display a flash message programmatically
const displayFlash = (message: string, type = 'success') => {
    flashMessage.value = message;
    flashType.value = type;
    showFlash.value = true;
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        showFlash.value = false;
    }, 5000);
};

// Check for flash messages on component mount and when page props change
onMounted(() => {
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

// Search functionality
const searchQuery = ref(props.filters?.search || '');

// Search functionality with debounce
let searchTimeout: number | null = null;
watch(searchQuery, (newValue) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    
    searchTimeout = setTimeout(() => {
        router.get('/inventory', {
            search: newValue
        }, {
            preserveState: true,
            replace: true
        });
    }, 500) as unknown as number;
});

// Filters
const selectedBatch = ref('');

// Modal states
const isAddingItem = ref(false);
const isEditingItem = ref(false);
const isDeletingItem = ref(false);
const selectedItem = ref<InventoryItem | null>(null);
const showModalPdf = ref(false);
const modalLotNumber = ref('');
const modalMonth = ref('');
const modalYear = ref('');

// Pagination navigation
const goToPage = (url: string | null) => {
    if (url) {
        router.visit(url, {
            preserveState: true,
            replace: true
        });
    }
};

// Get unique batch numbers for dropdown
const uniqueBatchNumbers = computed(() => {
    const batchNumbers = inventoryData.value.map(item => item.lot_number);
    return [...new Set(batchNumbers)];
});

// Filter inventory based on selected batch number
const filteredInventory = computed(() => {
    if (!selectedBatch.value) {
        return inventoryData.value;
    }
    return inventoryData.value.filter(item => item.lot_number === selectedBatch.value);
});

const generatePdfByLot = async () => {
    const lot = modalLotNumber.value.trim();
    const month = modalMonth.value;
    const year = modalYear.value;

    try {
        // Check if specific lot exists (only if lot number is filled)
        if (lot) {
            const response = await axios.get(getRoute('reports.inventory.check', lot));
            if (!response.data.exists) {
                displayFlash('No inventory found for this lot number.', 'error');
                return;
            }
        }

        const params = new URLSearchParams();
        if (lot) params.append('lot_number', lot);
        if (month) params.append('month', month);
        if (year) params.append('year', year);

        const pdfUrl = getRoute('reports.inventory.pdf') + '?' + params.toString();
        window.open(pdfUrl, '_blank');
        displayFlash('Generating inventory PDF report...', 'success');
    } catch (error) {
        console.error(error);
        displayFlash('Error generating PDF. Please try again.', 'error');
    }

    showModalPdf.value = false;
};

// Format date for display
function formatDate(dateString: string | null | undefined): string {
    if (!dateString) return 'N/A';

    const date = new Date(dateString);
    if (isNaN(date.getTime())) return 'Invalid date';

    return date.toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

const editItem = (item: InventoryItem) => {
    selectedItem.value = item;
    isEditingItem.value = true;
};

const deleteItem = (item: InventoryItem) => {
    selectedItem.value = item;
    isDeletingItem.value = true;
};

const confirmDelete = (id: number) => {
    isDeletingItem.value = false;
    selectedItem.value = null;
    // Refresh the page to update inventory
    router.reload();
};

const isDistributeModalOpen = ref(false);

function openDistributeModal(item: InventoryItem) {
    selectedItem.value = item;
    isDistributeModalOpen.value = true;
}

// Handle modal events
const handleItemAdded = () => {
    isAddingItem.value = false;
    if (!page.props.flash?.success && !page.props.success) {
        displayFlash('Item added successfully!', 'success');
    }
};

const handleItemEdited = () => {
    isEditingItem.value = false;
    selectedItem.value = null;
    if (!page.props.flash?.success && !page.props.success) {
        displayFlash('Item updated successfully!', 'success');
    }
};

const handleItemDistributed = () => {
    isDistributeModalOpen.value = false;
    selectedItem.value = null;
    if (!page.props.flash?.success && !page.props.success) {
        displayFlash('Item distributed successfully!', 'success');
    }
};

const expirationStatus = (expirationDate: string) => {
    const today = dayjs();
    const exp = dayjs(expirationDate);
    const diff = exp.diff(today, 'day');

    if (diff <= 0) return 'Expired';
    if (diff <= 7) return 'Expiring in < 1 week';
    if (diff <= 30) return 'Expiring in < 1 month';
    if (diff <= 90) return 'Expiring in < 3 months';

    return null;
};

const badgeClass = (status: string) => {
    switch (status) {
        case 'Expired':
            return 'bg-red-100 text-red-800 border border-red-200';
        case 'Expiring in < 1 week':
            return 'bg-orange-100 text-orange-800 border border-orange-200';
        case 'Expiring in < 1 month':
            return 'bg-yellow-100 text-yellow-800 border border-yellow-200';
        case 'Expiring in < 3 months':
            return 'bg-amber-100 text-amber-800 border border-amber-200';
        default:
            return '';
    }
};

// Clear search
const clearSearch = () => {
    searchQuery.value = '';
};

// Check if any filters are active
const hasActiveFilters = computed(() => {
    return searchQuery.value || selectedBatch.value;
});

// Clear all filters
const clearAllFilters = () => {
    searchQuery.value = '';
    selectedBatch.value = '';
};

// Add this function to access routes
const getRoute = (name: string, params?: any) => {
    return (window as any).route(name, params);
};
</script>

<template>
    <Head title="Stock Room" />

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
                    <h1 class="text-4xl font-bold text-green-800 mb-2">Stock Room Inventory</h1>
                    <p class="text-green-600">Manage your pharmacy's medicine stock and inventory</p>
                </div>
                <button @click="isAddingItem = true"
                    class="mt-4 md:mt-0 flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-all duration-200 shadow-md hover:shadow-lg transform hover:scale-105">
                    <PlusIcon class="w-5 h-5 mr-2" />
                    Add New Item
                </button>
            </div>

            <!-- Search and Filters -->
            <div class="bg-white rounded-xl shadow-sm border border-green-100 p-6 mb-8 mx-4">
                <div class="flex items-center gap-2 mb-4">
                    <FilterIcon class="h-5 w-5 text-green-600" />
                    <h3 class="text-lg font-semibold text-green-800">Search & Filter Inventory</h3>
                </div>
                
                <div class="flex flex-col md:flex-row gap-4 items-end">
                    <!-- Search Input -->
                    <div class="relative flex-grow">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <SearchIcon class="h-5 w-5 text-green-400" />
                        </div>
                        <input 
                            v-model="searchQuery" 
                            type="text" 
                            placeholder="Search by brand name, generic name, or lot number..."
                            class="pl-12 pr-12 py-3 w-full border border-green-200 rounded-lg focus:ring-2 focus:ring-green-200 focus:border-green-500 transition"
                        />
                        <button 
                            v-if="searchQuery" 
                            @click="clearSearch" 
                            class="absolute top-3.5 right-4 text-green-400 hover:text-green-600 transition"
                        >
                            <XCircle class="h-5 w-5" />
                        </button>
                    </div>

                    <!-- Batch Filter -->
                    <div class="relative min-w-[160px]">
                        <select v-model="selectedBatch"
                            class="pl-4 pr-10 py-3 border border-green-200 rounded-lg focus:ring-2 focus:ring-green-200 focus:border-green-500 appearance-none bg-white w-full">
                            <option value="">All Batches</option>
                            <option v-for="batch in uniqueBatchNumbers" :key="batch" :value="batch">
                                {{ batch }}
                            </option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <ChevronDownIcon class="h-4 w-4 text-green-500" />
                        </div>
                    </div>

                    <!-- Clear Filters Button -->
                    <button v-if="hasActiveFilters" @click="clearAllFilters"
                        class="px-4 py-3 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg flex items-center transition">
                        <XCircle class="w-4 h-4 mr-2" />
                        Clear
                    </button>

                    <!-- Generate PDF Button -->
                    <button @click="showModalPdf = true"
                        class="px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center transition shadow-md hover:shadow-lg transform hover:scale-105">
                        <FileText class="w-4 h-4 mr-2" />
                        Generate PDF
                    </button>
                </div>

                <!-- Active filters display -->
                <div v-if="hasActiveFilters" class="mt-4 flex flex-wrap gap-2">
                    <div v-if="searchQuery" class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm flex items-center border border-green-200">
                        Search: "{{ searchQuery }}"
                        <button @click="clearSearch" class="ml-2 text-green-600 hover:text-green-800">
                            <XCircle class="w-3 h-3" />
                        </button>
                    </div>
                    <div v-if="selectedBatch" class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm flex items-center border border-green-200">
                        Batch: {{ selectedBatch }}
                        <button @click="selectedBatch = ''" class="ml-2 text-green-600 hover:text-green-800">
                            <XCircle class="w-3 h-3" />
                        </button>
                    </div>
                </div>

                <!-- Stats Summary -->
                <div class="mt-4 flex gap-6 text-sm">
                    <div class="text-center">
                        <div class="font-semibold text-green-800">{{ props.inventory.total }}</div>
                        <div class="text-green-600">Total Items</div>
                    </div>
                    <div class="text-center">
                        <div class="font-semibold text-green-800">{{ props.inventory.from || 0 }}-{{ props.inventory.to || 0 }}</div>
                        <div class="text-green-600">Showing</div>
                    </div>
                    <div class="text-center">
                        <div class="font-semibold text-green-800">{{ filteredInventory.length }}</div>
                        <div class="text-green-600">Filtered</div>
                    </div>
                </div>
            </div>

            <!-- Inventory Table -->
            <div class="bg-white rounded-xl shadow-sm border border-green-100 overflow-hidden mx-4">
                <div class="overflow-x-auto">
                    <table class="w-full divide-y divide-green-100">
                        <thead class="bg-green-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider">
                                    <div class="flex items-center gap-2">
                                        <Calendar class="h-4 w-4" />
                                        Date In
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider">
                                    <div class="flex items-center gap-2">
                                        <PackageIcon class="h-4 w-4" />
                                        Medicine Details
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider">
                                    Units
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider">
                                    Lot/Batch
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider">
                                    Quantity
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider">
                                    Stocks on Hand
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider">
                                    <div class="flex items-center gap-2">
                                        <Calendar class="h-4 w-4" />
                                        Expiration
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-green-800 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-green-50">
                            <template v-if="filteredInventory.length">
                                <tr v-for="item in filteredInventory" :key="item.id" 
                                    class="hover:bg-green-25 transition-colors duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        {{ formatDate(item.date_in) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div>
                                            <div class="font-semibold text-gray-900">{{ item.brand_name }}</div>
                                            <div class="text-sm text-gray-600">{{ item.generic_name }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        {{ item.utils }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-mono bg-gray-100 text-gray-800 border border-gray-200">
                                            {{ item.lot_number }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                        {{ item.quantity }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-semibold text-gray-900">{{ item.stocks }}</span>
                                            <span v-if="item.stocks <= 10" 
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800 border border-red-200">
                                                Low Stock
                                            </span>
                                            <span v-else-if="item.stocks <= 50" 
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800 border border-yellow-200">
                                                Medium Stock
                                            </span>
                                            <span v-else 
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 border border-green-200">
                                                Good Stock
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm text-gray-700">{{ formatDate(item.expiration_date) }}</span>
                                            <span v-if="expirationStatus(item.expiration_date)"
                                                :class="badgeClass(expirationStatus(item.expiration_date))"
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border">
                                                {{ expirationStatus(item.expiration_date) }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex justify-center gap-2">
                                            <!-- Edit Button -->
                                            <button @click="editItem(item)"
                                                class="p-2 text-green-600 hover:text-green-800 hover:bg-green-100 rounded-lg transition"
                                                title="Edit Item">
                                                <Edit class="h-4 w-4" />
                                            </button>

                                            <!-- Delete Button -->
                                            <button @click="deleteItem(item)"
                                                class="p-2 text-red-600 hover:text-red-800 hover:bg-red-100 rounded-lg transition"
                                                title="Delete Item">
                                                <Trash2 class="h-4 w-4" />
                                            </button>

                                            <!-- Distribute Button -->
                                            <button @click="openDistributeModal(item)"
                                                class="p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-100 rounded-lg transition"
                                                title="Distribute Item">
                                                <Package class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr v-else>
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-500">
                                        <PackageIcon class="h-16 w-16 text-green-200 mb-4" />
                                        <p class="text-lg font-medium text-gray-700">No inventory items found</p>
                                        <p class="text-sm text-gray-500">
                                            {{ hasActiveFilters ? 'Try adjusting your search criteria' : 'Add your first medicine to get started' }}
                                        </p>
                                        <button 
                                            v-if="hasActiveFilters" 
                                            @click="clearAllFilters" 
                                            class="mt-2 text-green-600 hover:text-green-800 hover:underline font-medium"
                                        >
                                            Clear all filters
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="props.inventory.links && props.inventory.links.length > 3" 
                     class="px-6 py-4 border-t border-green-100 bg-green-25">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-green-700">
                            Showing {{ props.inventory.from || 0 }} to {{ props.inventory.to || 0 }} 
                            of {{ props.inventory.total || 0 }} items
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            <!-- Previous Button -->
                            <button 
                                v-if="props.inventory.prev_page_url"
                                @click="goToPage(props.inventory.prev_page_url)"
                                class="flex items-center px-3 py-2 rounded-lg border border-green-200 text-sm text-green-700 hover:bg-green-100 transition"
                            >
                                <ChevronLeftIcon class="h-4 w-4 mr-1" />
                                Previous
                            </button>
                            
                            <!-- Page Numbers -->
                            <template v-for="(link, i) in props.inventory.links" :key="i">
                                <button 
                                    v-if="i > 0 && i < props.inventory.links.length - 1"
                                    @click="goToPage(link.url)"
                                    class="px-3 py-2 rounded-lg border text-sm transition"
                                    :class="link.active 
                                        ? 'bg-green-600 text-white border-green-600 font-semibold' 
                                        : 'border-green-200 text-green-700 hover:bg-green-100'"
                                >
                                    {{ link.label }}
                                </button>
                            </template>
                            
                            <!-- Next Button -->
                            <button 
                                v-if="props.inventory.next_page_url"
                                @click="goToPage(props.inventory.next_page_url)"
                                class="flex items-center px-3 py-2 rounded-lg border border-green-200 text-sm text-green-700 hover:bg-green-100 transition"
                            >
                                Next
                                <ChevronRightIcon class="h-4 w-4 ml-1" />
                            </button>
                        </div>
                    </div>
                    
                    <!-- Page Info for Mobile -->
                    <div class="mt-3 text-center text-xs text-green-600 md:hidden">
                        Page {{ props.inventory.current_page }} of {{ props.inventory.last_page }}
                    </div>
                </div>
            </div>
        </div>

        <!-- PDF Generation Modal -->
        <div v-if="showModalPdf"
            class="fixed inset-0 bg-black/20 backdrop-blur-sm flex items-center justify-center z-50">
            <div class="bg-white text-gray-900 p-8 rounded-xl shadow-2xl w-full max-w-md mx-4 border border-green-100">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-green-800">Generate Inventory Report</h3>
                    <button @click="showModalPdf = false" class="text-gray-500 hover:text-gray-700">
                        <XCircle class="w-6 h-6" />
                    </button>
                </div>

                <div class="space-y-4">
                    <!-- Lot Number Input -->
                    <div>
                        <label for="lotInput" class="block text-sm font-semibold text-green-700 mb-2">
                            Lot Number (optional)
                        </label>
                        <input 
                            id="lotInput" 
                            v-model="modalLotNumber" 
                            type="text" 
                            placeholder="e.g., LOT123"
                            class="w-full p-3 border border-green-200 rounded-lg focus:ring-2 focus:ring-green-200 focus:border-green-500 transition"
                        />
                    </div>

                    <!-- Month Dropdown -->
                    <div>
                        <label for="monthInput" class="block text-sm font-semibold text-green-700 mb-2">
                            Month (optional)
                        </label>
                        <select 
                            id="monthInput" 
                            v-model="modalMonth"
                            class="w-full p-3 border border-green-200 rounded-lg focus:ring-2 focus:ring-green-200 focus:border-green-500 transition"
                        >
                            <option value="">All Months</option>
                            <option v-for="m in 12" :key="m" :value="m">
                                {{ new Date(0, m - 1).toLocaleString('default', { month: 'long' }) }}
                            </option>
                        </select>
                    </div>

                    <!-- Year Input -->
                    <div>
                        <label for="yearInput" class="block text-sm font-semibold text-green-700 mb-2">
                            Year (optional)
                        </label>
                        <input 
                            id="yearInput" 
                            v-model="modalYear" 
                            type="number" 
                            placeholder="e.g., 2025"
                            class="w-full p-3 border border-green-200 rounded-lg focus:ring-2 focus:ring-green-200 focus:border-green-500 transition"
                        />
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end gap-3 mt-8">
                    <button @click="showModalPdf = false"
                        class="px-6 py-3 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button @click="generatePdfByLot"
                        class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition shadow-md hover:shadow-lg transform hover:scale-105">
                        Generate PDF
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>

    <!-- Modals -->
    <AddItemModal :is-open="isAddingItem" @close="isAddingItem = false" @save="handleItemAdded" />
    <EditItemModal v-if="selectedItem" :is-open="isEditingItem" :item="selectedItem" @close="isEditingItem = false" @save="handleItemEdited" />
    <DeleteItemModal v-if="selectedItem" :is-open="isDeletingItem" :item="selectedItem" @close="isDeletingItem = false" @confirm="confirmDelete" />
    <DistributeItem :is-open="isDistributeModalOpen" :item="selectedItem" @close="isDistributeModalOpen = false" @save="handleItemDistributed" />
</template>

<style scoped>
.hover\:bg-green-25:hover {
    background-color: #f0fdf4;
}

.bg-green-25 {
    background-color: #f0fdf4;
}
</style>