<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import dayjs from 'dayjs';
import { AlertCircle, ArrowUpDown, CheckCircle, ChevronDown, Search, Trash2, XCircle, FileText, Filter } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pharmacy',
        href: '/pharmacy',
    },
];
const page = usePage();
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

const flashMessage = ref('');
const flashType = ref('');
const showFlash = ref(false);

// Delete modal state
const showDeleteModal = ref(false);
const itemToDelete = ref(null);
const isDeleting = ref(false);

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

// Delete item function
const deleteItem = (item) => {
    itemToDelete.value = item;
    showDeleteModal.value = true;
};

// Confirm delete function
const confirmDelete = async () => {
    if (!itemToDelete.value) return;

    isDeleting.value = true;

    try {
        const response = await axios.delete(`/distribution/delete/${itemToDelete.value.id}`);

        if (response.data.success) {
            // Remove the item from the local data
            const index = distributed.value.findIndex((item) => item.id === itemToDelete.value.id);
            if (index !== -1) {
                distributed.value.splice(index, 1);
            }

            displayFlash(response.data.message || 'Distribution record deleted successfully.', 'success');
        } else {
            displayFlash(response.data.message || 'Failed to delete record.', 'error');
        }
    } catch (error) {
        console.error('Delete error:', error);
        displayFlash(error.response?.data?.message || 'An error occurred while deleting the record.', 'error');
    } finally {
        isDeleting.value = false;
        showDeleteModal.value = false;
        itemToDelete.value = null;
    }
};

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
watch(
    () => page.props,
    (newProps) => {
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
    },
    { deep: true },
);

const modalMonthValue = ref('');
const modalYearValue = ref('');

const generateRemarksPdf = async () => {
    const remarks = modalRemarksValue.value.trim();
    const lot = modalLotNumberValue.value.trim();
    const month = modalMonthValue.value;
    const year = modalYearValue.value;

    if (!remarks) {
        displayFlash('Please enter a valid remark (or type "all").', 'error');
        return;
    }

    try {
        const response = await axios.get(route('reports.distribution.check'), {
            params: {
                remarks: remarks,
                lot_number: lot || undefined,
                month: month || undefined,
                year: year || undefined,
            },
        });

        if (response.data.exists) {
            const query = new URLSearchParams();

            if (lot) query.append('lot_number', lot);
            if (month) query.append('month', month);
            if (year) query.append('year', year);

            const queryString = query.toString() ? `?${query.toString()}` : '';
            const remarkEncoded = encodeURIComponent(remarks);

            window.open(`/reports/distribution/remarks/${remarkEncoded}${queryString}`, '_blank');
            displayFlash(`Generating PDF for remarks: ${remarks}`, 'success');
        } else {
            displayFlash('No distributions found for the selected filters.', 'error');
        }
    } catch (error) {
        console.error(error);
        displayFlash('Failed to check distributions. Please try again.', 'error');
    }

    showModalRemarksPdf.value = false;
};

// Get unique batch numbers for the dropdown
const uniqueBatchNumbers = computed(() => {
    const batches = distributed.value.map((item) => item.inventory?.lot_number).filter(Boolean);
    return [...new Set(batches)].sort();
});

// Get unique remarks for the dropdown
const uniqueRemarks = computed(() => {
    const remarks = distributed.value.map((item) => item.remarks).filter(Boolean);

    // Ensure "Pharmacy" is always in the list, even if not in the data
    const uniqueSet = new Set(remarks);
    uniqueSet.add('Pharmacy');

    return [...uniqueSet].sort();
});

// Filtered and sorted inventory
const filteredInventory = computed(() => {
    // First filter the items
    const filtered = distributed.value.filter((item) => {
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
    if (sortField.value !== field) return 'text-green-400 opacity-0 group-hover:opacity-100';
    return sortDirection.value === 'asc' ? 'text-green-700' : 'text-green-700 transform rotate-180';
};

// Format date for display
function formatDate(dateString: string | null | undefined): string {
    if (!dateString) return 'N/A';

    const date = new Date(dateString);
    if (isNaN(date.getTime())) return 'Invalid date';

    return date.toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
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
        totalCount: distributed.value.length,
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
        <div class="flex h-full min-h-screen w-full flex-1 flex-col gap-4 p-6 bg-green-50">
            <div class="w-full max-w-none">
                <!-- Flash Message Notification -->
                <transition
                    enter-active-class="transform ease-out duration-300 transition"
                    enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                    enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                    leave-active-class="transition ease-in duration-200"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div
                        v-if="showFlash"
                        class="fixed top-4 right-4 z-50 max-w-md rounded-lg p-4 shadow-lg"
                        :class="[
                            flashType === 'success'
                                ? 'border border-green-200 bg-green-100 text-green-800'
                                : 'border border-red-200 bg-red-100 text-red-800'
                        ]"
                    >
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <CheckCircle v-if="flashType === 'success'" class="h-5 w-5 text-green-500" />
                                <AlertCircle v-else class="h-5 w-5 text-red-500" />
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium">{{ flashMessage }}</p>
                            </div>
                            <div class="ml-auto pl-3">
                                <button
                                    @click="showFlash = false"
                                    class="inline-flex rounded-md p-1.5 focus:ring-2 focus:ring-offset-2 focus:outline-none"
                                    :class="[
                                        flashType === 'success'
                                            ? 'text-green-500 hover:bg-green-200 focus:ring-green-400'
                                            : 'text-red-500 hover:bg-red-200 focus:ring-red-400'
                                    ]"
                                >
                                    <span class="sr-only">Dismiss</span>
                                    <XCircle class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </transition>

                <!-- Header -->
                <div class="mb-8 flex items-center justify-between border-b border-green-200 pb-6">
                    <div>
                        <h1 class="text-4xl font-bold text-green-800 mb-2">Pharmacy Distribution</h1>
                        <p class="text-green-600">Track and manage distributed medications and pharmacy inventory</p>
                    </div>
                </div>

                <!-- Filters Section -->
                <div class="bg-white rounded-xl shadow-sm border border-green-100 p-6 mb-8">
                    <div class="flex items-center gap-2 mb-4">
                        <Filter class="h-5 w-5 text-green-600" />
                        <h3 class="text-lg font-semibold text-green-800">Filter & Search Options</h3>
                    </div>
                    
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-end">
                        <!-- Batch Number Dropdown -->
                        <div class="w-full lg:w-1/4">
                            <label class="mb-2 block text-sm font-medium text-green-700">Batch Number</label>
                            <div class="relative" id="batch-dropdown">
                                <button
                                    @click="isDropdownOpen = !isDropdownOpen"
                                    class="flex w-full items-center justify-between rounded-lg border border-green-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 transition"
                                >
                                    <span class="text-gray-700">{{ selectedBatch || 'All Batches' }}</span>
                                    <ChevronDown class="h-4 w-4 text-green-500" />
                                </button>

                                <!-- Dropdown Menu -->
                                <div
                                    v-if="isDropdownOpen"
                                    class="absolute z-10 mt-1 max-h-60 w-full overflow-y-auto rounded-lg border border-green-200 bg-white shadow-lg"
                                >
                                    <div class="py-1">
                                        <button
                                            @click="
                                                selectedBatch = '';
                                                isDropdownOpen = false;
                                            "
                                            class="block w-full px-4 py-2 text-left text-sm hover:bg-green-50 transition"
                                            :class="selectedBatch === '' ? 'bg-green-100 font-medium text-green-800' : 'text-gray-700'"
                                        >
                                            All Batches
                                        </button>
                                        <button
                                            v-for="batch in uniqueBatchNumbers"
                                            :key="batch"
                                            @click="
                                                selectedBatch = batch;
                                                isDropdownOpen = false;
                                            "
                                            class="block w-full px-4 py-2 text-left text-sm hover:bg-green-50 transition"
                                            :class="selectedBatch === batch ? 'bg-green-100 font-medium text-green-800' : 'text-gray-700'"
                                        >
                                            {{ batch }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Remarks Dropdown -->
                        <div class="w-full lg:w-1/4">
                            <label class="mb-2 block text-sm font-medium text-green-700">Pharmacy Remarks</label>
                            <div class="relative" id="remarks-dropdown">
                                <button
                                    @click="isRemarksDropdownOpen = !isRemarksDropdownOpen"
                                    class="flex w-full items-center justify-between rounded-lg border border-green-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 transition"
                                >
                                    <span class="text-gray-700">{{ selectedRemarkSort }}</span>
                                    <ChevronDown class="h-4 w-4 text-green-500" />
                                </button>

                                <!-- Dropdown Menu -->
                                <div
                                    v-if="isRemarksDropdownOpen"
                                    class="absolute z-10 mt-1 max-h-60 w-full overflow-y-auto rounded-lg border border-green-200 bg-white shadow-lg"
                                >
                                    <div class="py-1">
                                        <button
                                            @click="
                                                selectedRemarkSort = 'all';
                                                isRemarksDropdownOpen = false;
                                            "
                                            class="block w-full px-4 py-2 text-left text-sm hover:bg-green-50 transition"
                                            :class="selectedRemarkSort === 'all' ? 'bg-green-100 font-medium text-green-800' : 'text-gray-700'"
                                        >
                                            All Remarks
                                        </button>
                                        <button
                                            v-for="remark in uniqueRemarks"
                                            :key="remark"
                                            @click="
                                                selectedRemarkSort = remark;
                                                isRemarksDropdownOpen = false;
                                            "
                                            class="block w-full px-4 py-2 text-left text-sm hover:bg-green-50 transition"
                                            :class="selectedRemarkSort === remark ? 'bg-green-100 font-medium text-green-800' : 'text-gray-700'"
                                        >
                                            {{ remark }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Search Input -->
                        <div class="w-full lg:flex-1">
                            <label class="mb-2 block text-sm font-medium text-green-700">Search Inventory</label>
                            <div class="relative">
                                <Search class="absolute top-3.5 left-4 h-5 w-5 text-green-400" />
                                <input
                                    v-model="searchTerm"
                                    type="text"
                                    placeholder="Search by brand, generic name, batch, remarks..."
                                    class="block w-full rounded-lg border border-green-200 py-3 pr-12 pl-12 text-sm shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 transition"
                                />
                                <button 
                                    v-if="searchTerm" 
                                    @click="searchTerm = ''" 
                                    class="absolute top-3.5 right-4 text-green-400 hover:text-green-600 transition"
                                >
                                    <span class="sr-only">Clear search</span>
                                    <XCircle class="h-5 w-5" />
                                </button>
                            </div>
                        </div>

                        <!-- Generate PDF Button -->
                        <div class="w-full lg:w-auto">
                            <button 
                                @click="showModalRemarksPdf = true" 
                                class="w-full lg:w-auto flex items-center gap-2 rounded-lg bg-green-600 px-6 py-3 text-white shadow-md hover:bg-green-700 hover:shadow-lg transition transform hover:scale-105"
                            >
                                <FileText class="h-5 w-5" />
                                Generate PDF Report
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Inventory Table -->
                <div class="bg-white rounded-xl shadow-sm border border-green-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-green-100">
                            <thead class="bg-green-50">
                                <tr>
                                    <th
                                        @click="toggleSort('date_distribute')"
                                        class="group cursor-pointer px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider hover:bg-green-100 transition"
                                    >
                                        <div class="flex items-center">
                                            Date Distribute
                                            <ArrowUpDown class="ml-1 h-4 w-4 transition-all" :class="getSortIcon('date_distribute')" />
                                        </div>
                                    </th>
                                    <th
                                        @click="toggleSort('brand_name')"
                                        class="group cursor-pointer px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider hover:bg-green-100 transition"
                                    >
                                        <div class="flex items-center">
                                            Brand Name
                                            <ArrowUpDown class="ml-1 h-4 w-4 transition-all" :class="getSortIcon('brand_name')" />
                                        </div>
                                    </th>
                                    <th
                                        @click="toggleSort('generic_name')"
                                        class="group cursor-pointer px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider hover:bg-green-100 transition"
                                    >
                                        <div class="flex items-center">
                                            Generic Name
                                            <ArrowUpDown class="ml-1 h-4 w-4 transition-all" :class="getSortIcon('generic_name')" />
                                        </div>
                                    </th>
                                    <th
                                        @click="toggleSort('lot_number')"
                                        class="group cursor-pointer px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider hover:bg-green-100 transition"
                                    >
                                        <div class="flex items-center">
                                            Lot/Batch
                                            <ArrowUpDown class="ml-1 h-4 w-4 transition-all" :class="getSortIcon('lot_number')" />
                                        </div>
                                    </th>
                                    <th
                                        @click="toggleSort('quantity')"
                                        class="group cursor-pointer px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider hover:bg-green-100 transition"
                                    >
                                        <div class="flex items-center">
                                            Quantity
                                            <ArrowUpDown class="ml-1 h-4 w-4 transition-all" :class="getSortIcon('quantity')" />
                                        </div>
                                    </th>
                                    <th
                                        @click="toggleSort('stocks')"
                                        class="group cursor-pointer px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider hover:bg-green-100 transition"
                                    >
                                        <div class="flex items-center">
                                            Stocks on Hand
                                            <ArrowUpDown class="ml-1 h-4 w-4 transition-all" :class="getSortIcon('stocks')" />
                                        </div>
                                    </th>
                                    <th
                                        @click="toggleSort('expiration_date')"
                                        class="group cursor-pointer px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider hover:bg-green-100 transition"
                                    >
                                        <div class="flex items-center">
                                            Expires
                                            <ArrowUpDown class="ml-1 h-4 w-4 transition-all" :class="getSortIcon('expiration_date')" />
                                        </div>
                                    </th>
                                    <th
                                        @click="toggleSort('remarks')"
                                        class="group cursor-pointer px-6 py-4 text-left text-xs font-semibold text-green-800 uppercase tracking-wider hover:bg-green-100 transition"
                                    >
                                        <div class="flex items-center">
                                            Remarks
                                            <ArrowUpDown class="ml-1 h-4 w-4 transition-all" :class="getSortIcon('remarks')" />
                                        </div>
                                    </th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-green-800 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-green-50">
                                <tr v-for="item in filteredInventory" :key="item.id" class="transition hover:bg-green-25 duration-200">
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ formatDate(item.date_distribute) }}</td>
                                    <td class="px-6 py-4 font-semibold text-gray-900">{{ item.inventory.brand_name }}</td>
                                    <td class="px-6 py-4 font-medium text-gray-700">{{ item.inventory.generic_name }}</td>
                                    <td class="px-6 py-4 text-sm font-mono text-gray-600 bg-gray-50 rounded">{{ item.inventory.lot_number }}</td>
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-700">{{ item.quantity }}</td>
                                    <td class="px-6 py-4 text-sm font-semibold text-green-700">{{ item.stocks }}</td>
                                    <td class="px-6 py-4 text-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="text-gray-600">{{ formatDate(item.inventory.expiration_date) }}</span>
                                            <span
                                                v-if="expirationStatus(item.inventory.expiration_date)"
                                                :class="badgeClass(expirationStatus(item.inventory.expiration_date))"
                                                class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold"
                                            >
                                                {{ expirationStatus(item.inventory.expiration_date) }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                                            {{ item.remarks || '-' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex justify-center gap-2">
                                            <button
                                                @click="deleteItem(item)"
                                                class="text-red-600 hover:text-red-800 hover:bg-red-100 p-2 rounded-lg transition"
                                                title="Delete Distribution Record"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="filteredInventory.length === 0">
                                    <td colspan="9" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center text-gray-500">
                                            <FileText class="h-12 w-12 text-green-300 mb-4" />
                                            <p class="text-lg font-medium">No distribution records found</p>
                                            <p class="text-sm mb-4">Try adjusting your search criteria or filters.</p>
                                            <button @click="resetFilters" class="text-green-600 hover:text-green-800 hover:underline font-medium">
                                                Reset all filters
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for Generating PDF by Remarks -->
        <div v-if="showModalRemarksPdf" class="fixed inset-0 z-50 flex items-center justify-center bg-black/20 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-xl bg-white p-8 text-gray-900 shadow-2xl mx-4 border border-green-100">
                <h3 class="mb-6 text-xl font-bold text-green-800">Generate Distribution Report</h3>

                <!-- Remark Input -->
                <div class="mb-4">
                    <label for="remarksInput" class="mb-2 block text-sm font-semibold text-green-700">Remark</label>
                    <input
                        id="remarksInput"
                        v-model="modalRemarksValue"
                        type="text"
                        placeholder="e.g., Dispensed or all"
                        class="w-full rounded-lg border border-green-200 p-3 focus:ring-2 focus:ring-green-200 focus:border-green-500 transition"
                    />
                </div>

                <!-- Lot Number Input -->
                <div class="mb-4">
                    <label for="lotNumberInput" class="mb-2 block text-sm font-semibold text-green-700">Lot Number (Optional)</label>
                    <input
                        id="lotNumberInput"
                        v-model="modalLotNumberValue"
                        type="text"
                        placeholder="e.g., LOT123"
                        class="w-full rounded-lg border border-green-200 p-3 focus:ring-2 focus:ring-green-200 focus:border-green-500 transition"
                    />
                </div>

                <!-- Month Select -->
                <div class="mb-4">
                    <label for="monthSelect" class="mb-2 block text-sm font-semibold text-green-700">Select Month (Optional)</label>
                    <select
                        id="monthSelect"
                        v-model="modalMonthValue"
                        class="w-full rounded-lg border border-green-200 p-3 focus:ring-2 focus:ring-green-200 focus:border-green-500 transition"
                    >
                        <option value="">-- Select Month --</option>
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

                <!-- Year Input -->
                <div class="mb-6">
                    <label for="yearInput" class="mb-2 block text-sm font-semibold text-green-700">Enter Year (Optional)</label>
                    <input
                        id="yearInput"
                        v-model="modalYearValue"
                        type="number"
                        placeholder="e.g., 2025"
                        class="w-full rounded-lg border border-green-200 p-3 focus:ring-2 focus:ring-green-200 focus:border-green-500 transition"
                    />
                </div>

                <div class="flex justify-end gap-3">
                    <button
                        @click="showModalRemarksPdf = false"
                        class="rounded-lg border border-gray-300 px-6 py-3 text-gray-600 hover:bg-gray-50 transition"
                    >
                        Cancel
                    </button>
                    <button 
                        @click="generateRemarksPdf" 
                        class="rounded-lg bg-green-600 px-6 py-3 text-white hover:bg-green-700 shadow-md hover:shadow-lg transition transform hover:scale-105"
                    >
                        Generate PDF
                    </button>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/20 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-xl bg-white p-8 text-gray-900 shadow-2xl mx-4 border border-red-100">
                <h3 class="mb-2 text-xl font-bold text-red-800">Confirm Deletion</h3>

                <p class="mb-6 text-gray-600">
                    Are you sure you want to delete this distribution record?
                    <span class="font-medium text-red-600">This action cannot be undone.</span>
                </p>

                <div v-if="itemToDelete" class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div class="font-medium text-gray-700">Brand Name:</div>
                        <div class="text-gray-600">{{ itemToDelete.inventory?.brand_name }}</div>

                        <div class="font-medium text-gray-700">Generic Name:</div>
                        <div class="text-gray-600">{{ itemToDelete.inventory?.generic_name }}</div>

                        <div class="font-medium text-gray-700">Lot/Batch:</div>
                        <div class="text-gray-600">{{ itemToDelete.inventory?.lot_number }}</div>

                        <div class="font-medium text-gray-700">Quantity:</div>
                        <div class="text-gray-600">{{ itemToDelete.quantity }}</div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button
                        @click="showDeleteModal = false"
                        class="rounded-lg border border-gray-300 px-6 py-3 text-gray-600 hover:bg-gray-50 transition"
                        :disabled="isDeleting"
                    >
                        Cancel
                    </button>
                    <button
                        @click="confirmDelete"
                        class="flex items-center gap-2 rounded-lg bg-red-600 px-6 py-3 text-white hover:bg-red-700 transition"
                        :disabled="isDeleting"
                    >
                        <span v-if="isDeleting">Deleting...</span>
                        <span v-else>Delete Record</span>
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.hover\:bg-green-25:hover {
    background-color: #f0fdf4;
}
</style>