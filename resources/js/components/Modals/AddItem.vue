<script setup lang="ts">
import { ref } from 'vue';
import { XIcon } from 'lucide-vue-next';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  }
});

const emit = defineEmits(['close', 'save']);

// Helper to get ISO date string (yyyy-mm-dd)
const getISODate = (offsetDays = 0) => {
  const date = new Date();
  date.setDate(date.getDate() + offsetDays);
  return date.toISOString().split('T')[0];
};

// Form errors
const formErrors = ref({});

// Set expiration to 1 year later
const form = useForm({
  dateIn: getISODate(),
  brandName: '',
  utils: '',
  genericName: '',
  lotNumber: '',
  quantity: 0,
  expirationDate: '',
});

const handleSubmit = () => {
  form.post(route('inventory.store'), {
    onSuccess: () => {
      emit('save');
      emit('close');
      form.reset();
      form.dateIn = getISODate();
      form.expirationDate = '';
    },
    onError: (errors) => {
      formErrors.value = errors;
      console.error('Form submission errors:', errors);
    }
  });
};
</script>

<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center px-4"
  >
    <div class="relative w-full max-w-2xl bg-white dark:bg-background rounded-2xl shadow-xl border border-gray-200 dark:border-muted p-6 space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between border-b pb-4">
        <div>
          <h2 class="text-xl font-semibold tracking-tight">Add New Inventory Item</h2>
          <p class="text-sm text-muted-foreground">Fill out the details to add a new item to inventory.</p>
        </div>
        <button @click="emit('close')" class="rounded-full p-2 hover:bg-muted transition">
          <XIcon class="h-5 w-5 text-gray-500" />
        </button>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <!-- Date In -->
          <div>
            <label for="date-in" class="block text-sm font-medium mb-1">Date In</label>
            <input
              id="date-in"
              type="date"
              v-model="form.dateIn"
              required
              class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-primary focus:border-primary"
              :class="{ 'border-red-500': form.errors.dateIn }"
            />
            <p v-if="form.errors.dateIn" class="mt-1 text-sm text-red-600">{{ form.errors.dateIn }}</p>
          </div>

          <!-- Expiration Date -->
          <div>
            <label for="expiration-date" class="block text-sm font-medium mb-1">Expiration Date</label>
            <input
              id="expiration-date"
              type="date"
              v-model="form.expirationDate"
              required
              class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-primary focus:border-primary"
              :class="{ 'border-red-500': form.errors.expirationDate }"
            />
            <p v-if="form.errors.expirationDate" class="mt-1 text-sm text-red-600">{{ form.errors.expirationDate }}</p>
          </div>

          <!-- Brand Name -->
          <div class="sm:col-span-2">
            <label for="brand-name" class="block text-sm font-medium mb-1">Brand Name</label>
            <input
              id="brand-name"
              type="text"
              v-model="form.brandName"
              required
              placeholder="e.g., Biogesic"
              class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-primary focus:border-primary"
              :class="{ 'border-red-500': form.errors.brandName }"
            />
            <p v-if="form.errors.brandName" class="mt-1 text-sm text-red-600">{{ form.errors.brandName }}</p>
          <!-- </p class="mt-1 text-sm text-red-600">{{ form.errors.brandName }}</p> -->
          </div>

           <!-- generic Name -->
           <div class="sm:col-span-2">
            <label for="generic-name" class="block text-sm font-medium mb-1">Generic Name</label>
            <input
              id="generic-name"
              type="text"
              v-model="form.genericName"
              required
              placeholder="e.g., Paracetamol"
              class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-primary focus:border-primary"
              :class="{ 'border-red-500': form.errors.genericName }"
            />
            <p v-if="form.errors.genericName" class="mt-1 text-sm text-red-600">{{ form.errors.genericName }}</p>
          </div>

           <!-- Utils -->
           <div class="sm:col-span-2">
            <label for="utils" class="block text-sm font-medium mb-1">Units</label>
            <input
              id="utils"
              type="text"
              v-model="form.utils"
              required
              placeholder="e.g., 500mg"
              class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-primary focus:border-primary"
              :class="{ 'border-red-500': form.errors.utils }"
            />
            <p v-if="form.errors.utils" class="mt-1 text-sm text-red-600">{{ form.errors.utils }}</p>
          </div>

          <!-- Lot/Batch Number -->
          <div class="sm:col-span-1">
            <label for="lot-number" class="block text-sm font-medium mb-1">Lot/Batch Number</label>
            <input
              id="lot-number"
              type="text"
              v-model="form.lotNumber"
              required
              placeholder="e.g., B12345"
              class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-primary focus:border-primary"
              :class="{ 'border-red-500': form.errors.lotNumber }"
            />
            <p v-if="form.errors.lotNumber" class="mt-1 text-sm text-red-600">{{ form.errors.lotNumber }}</p>
          </div>

          <!-- Quantity -->
          <div class="sm:col-span-1">
            <label for="quantity" class="block text-sm font-medium mb-1">Quantity</label>
            <input
              id="quantity"
              type="number"
              min="1"
              v-model.number="form.quantity"
              required
              class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-primary focus:border-primary"
              :class="{ 'border-red-500': form.errors.quantity }"
            />
            <p v-if="form.errors.quantity" class="mt-1 text-sm text-red-600">{{ form.errors.quantity }}</p>
          </div>
        </div>

        <!-- Buttons -->
        <div class="flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-3 pt-4 border-t mt-4">
          <button
            type="button"
            @click="emit('close')"
            class="mt-2 sm:mt-0 inline-flex items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 transition"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="form.processing"
            class="inline-flex items-center justify-center rounded-md bg-primary text-white px-4 py-2 text-sm font-medium hover:bg-primary/90 transition"
          >
            {{ form.processing ? 'Adding...' : 'Add Item' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>