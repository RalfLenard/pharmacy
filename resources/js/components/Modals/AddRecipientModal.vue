<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { XIcon } from 'lucide-vue-next';
import { useForm } from '@inertiajs/vue3';

// Types
interface Distribution {
    id: number;
    name: string;
    quantity: number;
    inventory: {
        id: number;
        brand_name: string;
        lot_number: string;
        generic_name: string;
        utils: string;
    }
}

// State
const distributions = ref<Distribution[]>([]);
const loading = ref(false);
const selectedLotNumber = ref('');
const selectedDistributionId = ref('');

// Computed properties
const lotNumberOptions = computed(() => {
    const options = new Map();
    
    distributions.value.forEach(dist => {
        const lotNumber = dist.inventory.lot_number;
        if (!options.has(lotNumber)) {
            options.set(lotNumber, lotNumber);
        }
    });
    
    return Array.from(options.values());
});

const medicineOptions = computed(() => {
    if (!selectedLotNumber.value) return [];
    
    return distributions.value
        .filter(dist => dist.inventory.lot_number === selectedLotNumber.value)
        .map(dist => ({
            id: dist.id,
            name: `${dist.inventory.brand_name} ${dist.inventory.generic_name} ${dist.inventory.utils}`,
            stocks: dist.stocks
        }));
});

const selectedDistribution = computed(() => {
    if (!selectedDistributionId.value) return null;
    return distributions.value.find(dist => dist.id.toString() === selectedDistributionId.value) || null;
});

// Form
const form = useForm({
    full_name: '',
    gender: '',
    birthdate: '',
    barangay: '',
    distribution_id: '',
    quantity: 1,
    date_given: new Date().toISOString().split('T')[0],
});

// Emits
const emit = defineEmits(['close']);

// Methods
const closeModal = () => {
    emit('close');
};

const fetchDistributions = async () => {
    loading.value = true;
    try {
        // Replace with your actual API endpoint
        const response = await fetch('/medicines');
        distributions.value = await response.json();
    } catch (error) {
        console.error('Failed to fetch distributions:', error);
    } finally {
        loading.value = false;
    }
};

const handleLotNumberChange = () => {
    selectedDistributionId.value = '';
    form.distribution_id = '';
};

const handleMedicineChange = () => {
    form.distribution_id = selectedDistributionId.value;
};

const submitForm = () => {
    form.post('/recipients/store-medicine', {
        onSuccess: () => {
            closeModal();
        },
    });
};

// Lifecycle
onMounted(() => {
    fetchDistributions();
});
</script>

<template>
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeModal"></div>

            <!-- Modal panel -->
            <div
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex justify-between items-start">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Add New Recipient with Medicine
                        </h3>
                        <button @click="closeModal" class="text-gray-400 hover:text-gray-500">
                            <XIcon class="h-5 w-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitForm" class="mt-4">
                        <div class="space-y-4">
                            <!-- Recipient Information -->
                            <div class="border-b pb-4">
                                <h4 class="text-sm font-medium text-gray-700 mb-3">Recipient Information</h4>

                                <div class="mb-3">
                                    <label for="full_name" class="block text-sm font-medium text-gray-700">Full
                                        Name</label>
                                    <input v-model="form.full_name" type="text" id="full_name"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm"
                                        required />
                                    <div v-if="form.errors.full_name" class="text-red-500 text-xs mt-1">
                                        {{ form.errors.full_name }}
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="gender" class="block text-sm font-medium text-gray-700">Gender</label>
                                    <select v-model="form.gender" id="gender"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm"
                                        required>
                                        <option disabled value="">Select Gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                    <div v-if="form.errors.gender" class="text-red-500 text-xs mt-1">
                                        {{ form.errors.gender }}
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="birthdate"
                                        class="block text-sm font-medium text-gray-700">Birthdate</label>
                                    <input v-model="form.birthdate" type="date" id="birthdate"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm"
                                        required />
                                    <div v-if="form.errors.birthdate" class="text-red-500 text-xs mt-1">
                                        {{ form.errors.birthdate }}
                                    </div>
                                </div>

                                <div>
                                    <label for="barangay"
                                        class="block text-sm font-medium text-gray-700">Barangay</label>
                                    <input v-model="form.barangay" type="text" id="barangay"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm"
                                        required />
                                    <div v-if="form.errors.barangay" class="text-red-500 text-xs mt-1">
                                        {{ form.errors.barangay }}
                                    </div>
                                </div>
                            </div>

                            <!-- Medicine Distribution -->
                            <div>
                                <h4 class="text-sm font-medium text-gray-700 mb-3">Medicine Distribution</h4>

                                <!-- Batch/Lot Number Selection First -->
                                <div class="mb-3">
                                    <label for="lot_number"
                                        class="block text-sm font-medium text-gray-700">Batch/Lot Number</label>
                                    <select v-model="selectedLotNumber" id="lot_number" @change="handleLotNumberChange"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm"
                                        required>
                                        <option value="" disabled>Select a batch/lot number</option>
                                        <option v-for="lotNumber in lotNumberOptions" :key="lotNumber" :value="lotNumber">
                                            {{ lotNumber }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Medicine Selection Second -->
                                <div class="mb-3" v-if="selectedLotNumber">
                                    <label for="medicine_id"
                                        class="block text-sm font-medium text-gray-700">Medicine</label>
                                    <select v-model="selectedDistributionId" id="medicine_id" @change="handleMedicineChange"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm"
                                        required>
                                        <option value="" disabled>Select a medicine</option>
                                        <option v-for="option in medicineOptions" :key="option.id" :value="option.id.toString()">
                                            {{ option.name }} (Available: {{ option.stocks }})
                                        </option>
                                    </select>
                                    <div v-if="form.errors.distribution_id" class="text-red-500 text-xs mt-1">
                                        {{ form.errors.distribution_id }}
                                    </div>
                                </div>

                                <div class="mb-3" v-if="selectedDistribution">
                                    <label for="quantity"
                                        class="block text-sm font-medium text-gray-700">Quantity</label>
                                    <input v-model="form.quantity" type="number" id="quantity" min="1"
                                        :max="selectedDistribution.quantity"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm"
                                        required />
                                    <div v-if="form.errors.quantity" class="text-red-500 text-xs mt-1">
                                        {{ form.errors.quantity }}
                                    </div>
                                </div>

                                <div>
                                    <label for="date_given" class="block text-sm font-medium text-gray-700">Date
                                        Given</label>
                                    <input v-model="form.date_given" type="date" id="date_given"
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm"
                                        required />
                                    <div v-if="form.errors.date_given" class="text-red-500 text-xs mt-1">
                                        {{ form.errors.date_given }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                            <button type="submit"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-emerald-600 text-base font-medium text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 sm:col-start-2 sm:text-sm"
                                :disabled="form.processing">
                                {{ form.processing ? 'Saving...' : 'Save' }}
                            </button>
                            <button type="button" @click="closeModal"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>