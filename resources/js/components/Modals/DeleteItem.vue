<script setup lang="ts">
import { XIcon } from 'lucide-vue-next';

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  item: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['close', 'confirm']);

const handleConfirm = () => {
  emit('confirm', props.item.id);
};
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 bg-background/80 backdrop-blur-sm flex items-center justify-center">
    <div class="fixed left-[50%] top-[50%] z-50 grid w-full max-w-lg translate-x-[-50%] translate-y-[-50%] gap-4 border bg-background p-6 shadow-lg duration-200 sm:rounded-lg">
      <div class="flex items-center justify-between">
        <div class="flex flex-col space-y-1.5 text-center sm:text-left">
          <h2 class="text-lg font-semibold leading-none tracking-tight">Delete Inventory Item</h2>
          <p class="text-sm text-muted-foreground">Are you sure you want to delete this item?</p>
        </div>
        <button @click="emit('close')" class="rounded-full p-1 hover:bg-muted">
          <x-icon class="h-4 w-4" />
        </button>
      </div>
      <div class="py-4">
        <p>You are about to delete <strong>{{ item.brandName }}</strong> with lot number <strong>{{ item.lotNumber }}</strong>.</p>
        <p class="mt-2 text-sm text-muted-foreground">This action cannot be undone.</p>
      </div>
      <div class="flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-2">
        <button 
          type="button" 
          @click="emit('close')"
          class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2 mt-2 sm:mt-0"
        >
          Cancel
        </button>
        <button 
          type="button"
          @click="handleConfirm"
          class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-destructive text-destructive-foreground hover:bg-destructive/90 h-10 px-4 py-2"
        >
          Delete
        </button>
      </div>
    </div>
  </div>
</template>