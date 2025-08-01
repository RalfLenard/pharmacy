<script setup lang="ts">
import { watch, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { XIcon } from 'lucide-vue-next';

const props = defineProps({
  isOpen: Boolean,
  item: Object,
});

const emit = defineEmits(['close', 'save']);

// Initialize the form with default values
// Add to useForm:
const form = useForm({
  id: null,
  brandName: '',
  genericName: '',
  utils: '',
  lotNumber: '',
  quantity: 0,
  dateIn: '',
  expirationDate: '',
  stockType: 'Stock Room', // <-- added
});

// Add to the watcher:
watch(() => props.item, (item) => {
  if (item) {
    form.id = item.id;
    form.brandName = item.brand_name;
    form.genericName = item.generic_name;
    form.utils = item.utils;
    form.lotNumber = item.lot_number;
    form.quantity = item.quantity;
    form.dateIn = formatDateForInput(item.date_in);
    form.expirationDate = formatDateForInput(item.expiration_date);
    form.stockType = item.stock_type ?? 'Stock Room'; // <-- added
  }
}, { immediate: true });


// Format date to match input field format (YYYY-MM-DD)
function formatDateForInput(dateString: string | null | undefined): string {
  if (!dateString) return '';

  const date = new Date(dateString);
  if (isNaN(date.getTime())) return '';

  // Format as YYYY-MM-DD (required for input type="date")
  const year = date.getFullYear();
  const month = (date.getMonth() + 1).toString().padStart(2, '0'); // Ensure two digits
  const day = date.getDate().toString().padStart(2, '0'); // Ensure two digits
  return `${year}-${month}-${day}`;
}

// Helper to get ISO date string (yyyy-mm-dd)
const getISODate = (offsetDays = 0) => {
  const date = new Date();
  date.setDate(date.getDate() + offsetDays);
  return date.toISOString().split('T')[0];
};


const handleSubmit = () => {
  if (!form.id) return; // Prevent calling if ID is missing

  form.put(route('inventory.update', { id: form.id }), {
    onSuccess: () => {
      emit('save');
      emit('close');
    },
    onError: (errors) => {
      console.error('Form submission errors:', errors);
    }
  });
};

</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center px-4">
    <div
      class="relative w-full max-w-2xl bg-white dark:bg-background rounded-2xl shadow-xl border border-gray-200 dark:border-muted p-6 space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between border-b pb-4">
        <div>
          <h2 class="text-xl font-semibold tracking-tight">Edit Inventory Item</h2>
          <p class="text-sm text-muted-foreground">Update the details of this inventory item.</p>
        </div>
        <button @click="emit('close')" class="rounded-full p-2 hover:bg-muted transition">
          <XIcon class="h-5 w-5 text-gray-500" />
        </button>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <!-- Stock Type -->
          <div class="sm:col-span-2">
            <label for="stock-type" class="block text-sm font-medium mb-1">Stock Type</label>
            <select id="stock-type" v-model="form.stockType" required
              class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-primary focus:border-primary"
              :class="{ 'border-red-500': form.errors.stockType }">
              <option value="LGU Procured">LGU Procured</option>
              <option value="DOH">DOH</option>
              <option value="Trust Funds">Trust Funds</option>
              <option value="Donations">Donations</option>
            </select>
            <p v-if="form.errors.stockType" class="mt-1 text-sm text-red-600">{{ form.errors.stockType }}</p>
          </div>

          <!-- Date In -->
          <div>
            <label for="date-in" class="block text-sm font-medium mb-1">Date In</label>
            <input id="date-in" type="date" v-model="form.dateIn" required
              class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-primary focus:border-primary"
              :class="{ 'border-red-500': form.errors.dateIn }" />
            <p v-if="form.errors.dateIn" class="mt-1 text-sm text-red-600">{{ form.errors.dateIn }}</p>
          </div>

          <!-- Expiration Date -->
          <div>
            <label for="expiration-date" class="block text-sm font-medium mb-1">Expiration Date</label>
            <input id="expiration-date" type="date" v-model="form.expirationDate" required
              class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-primary focus:border-primary"
              :class="{ 'border-red-500': form.errors.expirationDate }" />
            <p v-if="form.errors.expirationDate" class="mt-1 text-sm text-red-600">{{ form.errors.expirationDate }}</p>
          </div>

          <!-- Brand Name -->
          <div class="sm:col-span-2">
            <label for="brand-name" class="block text-sm font-medium mb-1">Brand Name</label>
            <input id="brand-name" type="text" v-model="form.brandName" required placeholder="e.g., Biogesic"
              class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-primary focus:border-primary"
              :class="{ 'border-red-500': form.errors.brandName }" />
            <p v-if="form.errors.brandName" class="mt-1 text-sm text-red-600">{{ form.errors.brandName }}</p>
          </div>

          <!-- generic Name -->
          <div class="sm:col-span-2">
            <label for="generic-name" class="block text-sm font-medium mb-1">Generic Name</label>
            <input id="generic-name" type="text" v-model="form.genericName" required placeholder="e.g., Paracetamol"
              class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-primary focus:border-primary"
              :class="{ 'border-red-500': form.errors.genericName }" />
            <p v-if="form.errors.genericName" class="mt-1 text-sm text-red-600">{{ form.errors.genericName }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="utils" class="block text-sm font-medium mb-1">Units</label>
            <input id="utils" type="text" v-model="form.utils" required
              class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-primary focus:border-primary"
              :class="{ 'border-red-500': form.errors.utils }" />
            <p v-if="form.errors.utils" class="mt-1 text-sm text-red-600">{{ form.errors.utils }}</p>
          </div>

          <!-- Lot/Batch Number -->
          <div class="sm:col-span-1">
            <label for="lot-number" class="block text-sm font-medium mb-1">Lot/Batch Number</label>
            <input id="lot-number" type="text" v-model="form.lotNumber" required placeholder="e.g., B12345"
              class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-primary focus:border-primary"
              :class="{ 'border-red-500': form.errors.lotNumber }" />
            <p v-if="form.errors.lotNumber" class="mt-1 text-sm text-red-600">{{ form.errors.lotNumber }}</p>
          </div>

          <!-- Quantity -->
          <div class="sm:col-span-1">
            <label for="quantity" class="block text-sm font-medium mb-1">Quantity</label>
            <input id="quantity" type="number" min="1" v-model.number="form.quantity" required
              class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-primary focus:border-primary"
              :class="{ 'border-red-500': form.errors.quantity }" />
            <p v-if="form.errors.quantity" class="mt-1 text-sm text-red-600">{{ form.errors.quantity }}</p>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-3 pt-4 border-t mt-4">
          <button type="button" @click="emit('close')"
            class="mt-2 sm:mt-0 inline-flex items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 transition">
            Cancel
          </button>
          <button type="submit" :disabled="form.processing"
            class="inline-flex items-center justify-center rounded-md bg-primary text-white px-4 py-2 text-sm font-medium hover:bg-primary/90 transition">
            {{ form.processing ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>