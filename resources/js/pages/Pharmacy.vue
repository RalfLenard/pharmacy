<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import { PlusCircle, Search, Edit, Trash2, Package, ChevronDown, ArrowUpDown } from 'lucide-vue-next';
import { ref, computed, onMounted, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pharmacy',
        href: '/pharmacy',
    },
];

const distributed = computed(() => usePage().props.distributed);

// Filters
const selectedBatch = ref('');
const searchTerm = ref('');
const isDropdownOpen = ref(false);
const isRemarksDropdownOpen = ref(false);

// Sorting
const sortField = ref('remarks');
const sortDirection = ref('asc');
const selectedRemarkSort = ref('Pharmacy'); // Default to "Pharmacy" remarks
const showModalRemarksPdf = ref(false);
const modalRemarksValue = ref('');
const modalLotNumberValue = ref(''); 

const generateRemarksPdf = () => {
    if (!modalRemarksValue.value.trim()) {
        alert('Please enter a valid remark.');
        return;
    }

    // Prepare the URL with both remark and lot number parameters
    const remark = encodeURIComponent(modalRemarksValue.value.trim());
    const lotNumber = encodeURIComponent(modalLotNumberValue.value.trim());

    let url = `/reports/distribution/remarks/${remark}`;

    // If lot number is provided, append it as a query parameter
    if (lotNumber) {
        url += `?lot_number=${lotNumber}`;
    }

    window.open(url, '_blank');
    showModalRemarksPdf.value = false;
};


// Get unique batch numbers for the dropdown
const uniqueBatchNumbers = computed(() => {
    const batches = distributed.value
        .map(item => item.inventory?.lot_number)
        .filter(Boolean);
    return [...new Set(batches)].sort();
});

// Get unique remarks for the dropdown
const uniqueRemarks = computed(() => {
    const remarks = distributed.value
        .map(item => item.remarks)
        .filter(Boolean);

    // Ensure "Pharmacy" is always in the list, even if not in the data
    const uniqueSet = new Set(remarks);
    uniqueSet.add('Pharmacy');

    return [...uniqueSet].sort();
});

// Filtered and sorted inventory
const filteredInventory = computed(() => {
    // First filter the items
    const filtered = distributed.value.filter(item => {
        // Apply batch filter first
        if (selectedBatch.value && item.inventory?.lot_number !== selectedBatch.value) {
            return false;
        }

        // Apply remarks filter if selected
        if (selectedRemarkSort.value !== 'all') {
            if (item.remarks !== selectedRemarkSort.value) {
                return false;
            }
        }

        // Then apply search filter if there is a search term
        if (searchTerm.value) {
            const search = searchTerm.value.toLowerCase();
            return (
                item.inventory?.brand_name?.toLowerCase().includes(search) ||
                item.inventory?.generic_name?.toLowerCase().includes(search) ||
                item.inventory?.lot_number?.toLowerCase().includes(search) ||
                (item.remarks && item.remarks.toLowerCase().includes(search))
            );
        }

        // If we got here, the item passed all filters
        return true;
    });

    // Then sort the filtered items
    return [...filtered].sort((a, b) => {
        let valueA, valueB;

        // Handle different sort fields
        switch (sortField.value) {
            case 'date_distribute':
                valueA = new Date(a.date_distribute || 0).getTime();
                valueB = new Date(b.date_distribute || 0).getTime();
                break;
            case 'brand_name':
                valueA = a.inventory?.brand_name?.toLowerCase() || '';
                valueB = b.inventory?.brand_name?.toLowerCase() || '';
                break;
            case 'generic_name':
                valueA = a.inventory?.generic_name?.toLowerCase() || '';
                valueB = b.inventory?.generic_name?.toLowerCase() || '';
                break;
            case 'lot_number':
                valueA = a.inventory?.lot_number?.toLowerCase() || '';
                valueB = b.inventory?.lot_number?.toLowerCase() || '';
                break;
            case 'quantity':
                valueA = a.quantity || 0;
                valueB = b.quantity || 0;
                break;
            case 'expiration_date':
                valueA = new Date(a.inventory?.expiration_date || 0).getTime();
                valueB = new Date(b.inventory?.expiration_date || 0).getTime();
                break;
            case 'remarks':
                valueA = a.remarks?.toLowerCase() || '';
                valueB = b.remarks?.toLowerCase() || '';
                break;
            default:
                valueA = a[sortField.value] || '';
                valueB = b[sortField.value] || '';
        }

        // Apply sort direction
        if (sortDirection.value === 'asc') {
            return valueA > valueB ? 1 : -1;
        } else {
            return valueA < valueB ? 1 : -1;
        }
    });
});

// Handle sorting
const toggleSort = (field) => {
    if (sortField.value === field) {
        // Toggle direction if clicking the same field
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        // Set new field and default to ascending
        sortField.value = field;
        sortDirection.value = 'asc';
    }
};

// Get sort icon class
const getSortIcon = (field) => {
    if (sortField.value !== field) return 'text-gray-400 opacity-0 group-hover:opacity-100';
    return sortDirection.value === 'asc' ? 'text-gray-700' : 'text-gray-700 transform rotate-180';
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

const expirationStatus = (expiration_date: string) => {
    const today = dayjs();
    const exp = dayjs(expiration_date);
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
            return 'bg-red-100 text-red-800 border border-red-300';
        case 'Expiring in < 1 week':
            return 'bg-orange-100 text-orange-800 border border-orange-300';
        case 'Expiring in < 1 month':
            return 'bg-yellow-100 text-yellow-800 border border-yellow-300';
        case 'Expiring in < 3 months':
            return 'bg-amber-100 text-amber-800 border border-amber-300';
        default:
            return '';
    }
};

// For debugging - log when filters change
const logFilters = () => {
    console.log('Current filters:', {
        selectedBatch: selectedBatch.value,
        searchTerm: searchTerm.value,
        sortField: sortField.value,
        sortDirection: sortDirection.value,
        selectedRemarkSort: selectedRemarkSort.value,
        filteredCount: filteredInventory.value.length,
        totalCount: distributed.value.length
    });
};

// Watch for filter changes
watch([selectedBatch, searchTerm, sortField, sortDirection, selectedRemarkSort], () => {
    logFilters();
});

// Placeholder for the distribute modal function
const openDistributeModal = (item) => {
    console.log('Open distribute modal for item:', item);
    // Implement your modal logic here
};

// Reset all filters but keep remarks as "Pharmacy"
const resetFilters = () => {
    selectedBatch.value = '';
    searchTerm.value = '';
    isDropdownOpen.value = false;
    selectedRemarkSort.value = 'Pharmacy'; // Reset to Pharmacy instead of 'all'
    // Keep sorting field and direction as is
};

// Close dropdowns when clicking outside
const closeDropdownsOnOutsideClick = (event) => {
    const batchDropdown = document.getElementById('batch-dropdown');
    if (batchDropdown && !batchDropdown.contains(event.target)) {
        isDropdownOpen.value = false;
    }

    const remarksDropdown = document.getElementById('remarks-dropdown');
    if (remarksDropdown && !remarksDropdown.contains(event.target)) {
        isRemarksDropdownOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', closeDropdownsOnOutsideClick);
    // Set default sort to pharmacy remarks
    sortField.value = 'remarks';
    sortDirection.value = 'asc';
    // Ensure "Pharmacy" is selected by default
    selectedRemarkSort.value = 'Pharmacy';
    // Log initial state
    logFilters();

    return () => {
        document.removeEventListener('click', closeDropdownsOnOutsideClick);
    };
});
</script>

<template>

    <Head title="Pharmacy" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-6 w-full min-h-screen w-full">
            <div class="w-full">


                <div class="container mx-auto">
                    <!-- Header -->
                    <div class="flex items-center justify-between border-b pb-4 mb-6">
                        <h1 class="text-3xl font-bold text-gray-800">Pharmacy Inventory</h1>
                    </div>

                    <!-- Filters -->
                    <div class="flex flex-col md:flex-row md:items-end gap-4 mb-6">
                        <!-- Batch Number Dropdown -->
                        <div class="w-full md:w-1/4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Batch Number</label>
                            <div class="relative" id="batch-dropdown">
                                <button @click="isDropdownOpen = !isDropdownOpen"
                                    class="flex items-center justify-between w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span>{{ selectedBatch || 'All Batches' }}</span>
                                    <ChevronDown class="h-4 w-4 text-gray-500" />
                                </button>

                                <!-- Dropdown Menu -->
                                <div v-if="isDropdownOpen"
                                    class="absolute z-10 mt-1 w-full rounded-md bg-white shadow-lg border border-gray-200 max-h-60 overflow-y-auto">
                                    <div class="py-1">
                                        <button @click="selectedBatch = ''; isDropdownOpen = false"
                                            class="block w-full px-4 py-2 text-left text-sm hover:bg-gray-100"
                                            :class="selectedBatch === '' ? 'bg-gray-100 font-medium' : ''">
                                            All Batches
                                        </button>
                                        <button v-for="batch in uniqueBatchNumbers" :key="batch"
                                            @click="selectedBatch = batch; isDropdownOpen = false"
                                            class="block w-full px-4 py-2 text-left text-sm hover:bg-gray-100"
                                            :class="selectedBatch === batch ? 'bg-gray-100 font-medium' : ''">
                                            {{ batch }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Remarks Dropdown -->
                        <div class="w-full md:w-1/4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Pharmacy Remarks</label>
                            <div class="relative" id="remarks-dropdown">
                                <button @click="isRemarksDropdownOpen = !isRemarksDropdownOpen"
                                    class="flex items-center justify-between w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span>{{ selectedRemarkSort }}</span>
                                    <ChevronDown class="h-4 w-4 text-gray-500" />
                                </button>

                                <!-- Dropdown Menu -->
                                <div v-if="isRemarksDropdownOpen"
                                    class="absolute z-10 mt-1 w-full rounded-md bg-white shadow-lg border border-gray-200 max-h-60 overflow-y-auto">
                                    <div class="py-1">
                                        <button @click="selectedRemarkSort = 'all'; isRemarksDropdownOpen = false"
                                            class="block w-full px-4 py-2 text-left text-sm hover:bg-gray-100"
                                            :class="selectedRemarkSort === 'all' ? 'bg-gray-100 font-medium' : ''">
                                            All Remarks
                                        </button>
                                        <button v-for="remark in uniqueRemarks" :key="remark"
                                            @click="selectedRemarkSort = remark; isRemarksDropdownOpen = false"
                                            class="block w-full px-4 py-2 text-left text-sm hover:bg-gray-100"
                                            :class="selectedRemarkSort === remark ? 'bg-gray-100 font-medium' : ''">
                                            {{ remark }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Search Input -->
                        <div class="w-full md:flex-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <div class="relative">
                                <Search class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
                                <input v-model="searchTerm" type="text"
                                    placeholder="Search by brand, generic name, batch, remarks..."
                                    class="block w-full pl-10 pr-3 py-2 rounded-md border border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                <button v-if="searchTerm" @click="searchTerm = ''"
                                    class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600">
                                    <span class="sr-only">Clear search</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20"
                                        fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Generate PDF Button -->
                        <div class="w-full md:w-auto self-end">
                            <button @click="showModalRemarksPdf = true"
                                class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 shadow">
                                Generate Remarks PDF
                            </button>
                        </div>



                        
                    </div>

                    <!-- Results count -->
                    <!-- <div class="mb-4 text-sm text-gray-600">
                    Showing {{ filteredInventory.length }} {{ filteredInventory.length === 1 ? 'item' : 'items' }}
                    <span v-if="selectedBatch"> with batch number <strong>{{ selectedBatch }}</strong></span>
                    <span v-if="selectedRemarkSort !== 'all'"> with remarks <strong>{{ selectedRemarkSort }}</strong></span>
                    <span> sorted by <strong>{{ sortField }}</strong> ({{ sortDirection === 'asc' ? 'ascending' : 'descending' }})</span>
                </div> -->

                    <!-- Inventory Table -->
                    <div class="bg-white rounded-lg shadow border overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th @click="toggleSort('date_distribute')"
                                        class="px-6 py-3 text-left text-xs font-semibold text-gray-600 cursor-pointer group">
                                        <div class="flex items-center">
                                            Date Distribute
                                            <ArrowUpDown class="ml-1 h-4 w-4 transition-all"
                                                :class="getSortIcon('date_distribute')" />
                                        </div>
                                    </th>
                                    <th @click="toggleSort('brand_name')"
                                        class="px-6 py-3 text-left text-xs font-semibold text-gray-600 cursor-pointer group">
                                        <div class="flex items-center">
                                            Brand Name
                                            <ArrowUpDown class="ml-1 h-4 w-4 transition-all"
                                                :class="getSortIcon('brand_name')" />
                                        </div>
                                    </th>
                                    <th @click="toggleSort('generic_name')"
                                        class="px-6 py-3 text-left text-xs font-semibold text-gray-600 cursor-pointer group">
                                        <div class="flex items-center">
                                            Generic Name
                                            <ArrowUpDown class="ml-1 h-4 w-4 transition-all"
                                                :class="getSortIcon('generic_name')" />
                                        </div>
                                    </th>
                                    <th @click="toggleSort('lot_number')"
                                        class="px-6 py-3 text-left text-xs font-semibold text-gray-600 cursor-pointer group">
                                        <div class="flex items-center">
                                            Lot/Batch
                                            <ArrowUpDown class="ml-1 h-4 w-4 transition-all"
                                                :class="getSortIcon('lot_number')" />
                                        </div>
                                    </th>
                                    <th @click="toggleSort('quantity')"
                                        class="px-6 py-3 text-left text-xs font-semibold text-gray-600 cursor-pointer group">
                                        <div class="flex items-center">
                                            Quantity
                                            <ArrowUpDown class="ml-1 h-4 w-4 transition-all"
                                                :class="getSortIcon('quantity')" />
                                        </div>
                                    </th>
                                    <th @click="toggleSort('stocks')"
                                        class="px-6 py-3 text-left text-xs font-semibold text-gray-600 cursor-pointer group">
                                        <div class="flex items-center">
                                            Stocks on Hand
                                            <ArrowUpDown class="ml-1 h-4 w-4 transition-all"
                                                :class="getSortIcon('stocks')" />
                                        </div>
                                    </th>
                                    <th @click="toggleSort('expiration_date')"
                                        class="px-6 py-3 text-left text-xs font-semibold text-gray-600 cursor-pointer group">
                                        <div class="flex items-center">
                                            Expires
                                            <ArrowUpDown class="ml-1 h-4 w-4 transition-all"
                                                :class="getSortIcon('expiration_date')" />
                                        </div>
                                    </th>
                                    <th @click="toggleSort('remarks')"
                                        class="px-6 py-3 text-left text-xs font-semibold text-gray-600 cursor-pointer group">
                                        <div class="flex items-center">
                                            Remarks
                                            <ArrowUpDown class="ml-1 h-4 w-4 transition-all"
                                                :class="getSortIcon('remarks')" />
                                        </div>
                                    </th>
                                    <!-- <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600">Actions</th> -->
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="item in filteredInventory" :key="item.id"
                                    class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ formatDate(item.date_distribute) }}
                                    </td>
                                    <td class="px-6 py-4 font-medium text-gray-900">{{ item.inventory.brand_name }}</td>
                                    <td class="px-6 py-4 font-medium text-gray-900">{{ item.inventory.generic_name }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ item.inventory.lot_number }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ item.quantity }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ item.stocks }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700 flex items-center gap-2">
                                        {{ formatDate(item.inventory.expiration_date) }}
                                        <span v-if="expirationStatus(item.inventory.expiration_date)"
                                            :class="badgeClass(expirationStatus(item.inventory.expiration_date))"
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border">
                                            {{ expirationStatus(item.inventory.expiration_date) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ item.remarks || '-' }}</td>
                                    <!-- <td class="px-6 py-4 text-center">
                                        <div class="flex justify-center gap-2">
                                          
                                            <button @click="openDistributeModal(item)"
                                                class="text-green-600 hover:text-green-800 text-sm" title="Distribute">
                                                <Package class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td> -->
                                </tr>
                                <tr v-if="filteredInventory.length === 0">
                                    <td colspan="8" class="px-6 py-6 text-center text-gray-500 text-sm">
                                        No inventory items found with the selected filters.
                                        <button @click="resetFilters" class="text-blue-500 hover:underline ml-1">Reset
                                            filters</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

       <!-- Modal for Generating PDF by Remarks -->
        <div v-if="showModalRemarksPdf" class="fixed inset-0 bg-white/50 backdrop-blur-sm flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 p-6 rounded-lg shadow-lg w-full max-w-md">
                <h3 class="text-lg font-semibold mb-4">Generate Distribution Report by Remarks</h3>

                <!-- Remark Input -->
                <label for="remarksInput" class="block text-sm font-medium mb-1">Enter Remark</label>
                <input id="remarksInput" v-model="modalRemarksValue" type="text" placeholder="e.g., Dispensed"
                    class="w-full p-2 mb-4 border rounded focus:ring focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />

                <!-- Lot Number Input -->
                <label for="lotNumberInput" class="block text-sm font-medium mb-1">Enter Lot Number (Optional)</label>
                <input id="lotNumberInput" v-model="modalLotNumberValue" type="text" placeholder="e.g., LOT123"
                    class="w-full p-2 mb-4 border rounded focus:ring focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />

                <div class="flex justify-end gap-2">
                    <button @click="showModalRemarksPdf = false"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 border rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                        Cancel
                    </button>
                    <button @click="generateRemarksPdf"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">
                        Generate
                    </button>
                </div>
            </div>
        </div>


    </AppLayout>
</template>