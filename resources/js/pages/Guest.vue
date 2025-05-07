<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { LogOut, ShieldAlert, UserPlus, LogIn, Home } from 'lucide-vue-next';

// Function to handle logout
const handleLogout = () => {
  // Use Inertia router for navigation instead of route helper
  router.post('/logout');
};
</script>

<template>
  <Head title="Unauthorized Access - Municipal Pharmacy">
    <link rel="preconnect" href="https://rsms.me/" />
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
  </Head>
  <div class="flex min-h-screen flex-col items-center bg-[#f8f9fa] p-6 text-[#2d3748] dark:bg-[#1a202c] dark:text-[#e2e8f0] lg:justify-center lg:p-8">
    <div class="duration-750 starting:opacity-0 flex w-full items-center justify-center opacity-100 transition-opacity lg:grow">
      <main class="w-full max-w-[500px] overflow-hidden rounded-lg">
        <div class="rounded-t-lg bg-[#fed7d7] p-4 text-center dark:bg-[#742a2a]">
          <ShieldAlert class="mx-auto h-12 w-12 text-[#e53e3e] dark:text-[#fc8181]" />
        </div>
        
        <div class="rounded-b-lg bg-white p-8 shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:bg-[#2d3748] dark:shadow-[inset_0px_0px_0px_1px_#4a556833]">
          <h1 class="mb-4 text-center text-2xl font-bold text-[#e53e3e] dark:text-[#fc8181]">Unauthorized Access</h1>
          
          <div class="mb-6 space-y-4 text-center">
            <p class="text-[#4a5568] dark:text-[#a0aec0]">
              You don't have permission to access this page. This could be because:
            </p>
            
            <ul class="mx-auto max-w-md list-inside list-disc space-y-2 text-left text-[#4a5568] dark:text-[#a0aec0]">
              <li>Your session has expired</li>
              <li>You need to be logged in to view this content</li>
              <li>You don't have the required permissions</li>
              <li>You're trying to access a restricted area</li>
            </ul>
          </div>
          
          <div class="mb-8 flex flex-col items-center justify-center space-y-4 sm:flex-row sm:space-x-4 sm:space-y-0">
            <a
              href="/login"
              class="inline-flex w-full items-center justify-center rounded-sm border border-[#4a5568] bg-white px-4 py-2 text-sm font-medium text-[#2d3748] transition-colors hover:bg-[#edf2f7] dark:border-[#4a5568] dark:bg-[#2d3748] dark:text-[#e2e8f0] dark:hover:bg-[#1a202c] sm:w-auto"
            >
              <LogIn class="mr-2 h-4 w-4" />
              Log In
            </a>
            
            <a
              href="/register"
              class="inline-flex w-full items-center justify-center rounded-sm border border-[#4a5568] bg-white px-4 py-2 text-sm font-medium text-[#2d3748] transition-colors hover:bg-[#edf2f7] dark:border-[#4a5568] dark:bg-[#2d3748] dark:text-[#e2e8f0] dark:hover:bg-[#1a202c] sm:w-auto"
            >
              <UserPlus class="mr-2 h-4 w-4" />
              Register
            </a>
            
            <a
              href="/"
              class="inline-flex w-full items-center justify-center rounded-sm border border-[#4a5568] bg-white px-4 py-2 text-sm font-medium text-[#2d3748] transition-colors hover:bg-[#edf2f7] dark:border-[#4a5568] dark:bg-[#2d3748] dark:text-[#e2e8f0] dark:hover:bg-[#1a202c] sm:w-auto"
            >
              <Home class="mr-2 h-4 w-4" />
              Home
            </a>
          </div>
          
          <div class="border-t border-[#e2e8f0] pt-6 dark:border-[#4a5568]">
            <p class="mb-4 text-center text-sm text-[#4a5568] dark:text-[#a0aec0]">
              Already logged in but seeing this page? You may need to log out and log back in.
            </p>
            
            <button
              @click="handleLogout"
              class="mx-auto flex items-center justify-center rounded-sm border border-[#e53e3e] bg-[#e53e3e] px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-[#c53030] dark:border-[#fc8181] dark:bg-[#fc8181] dark:text-[#1a202c] dark:hover:bg-[#e53e3e]"
            >
              <LogOut class="mr-2 h-4 w-4" />
              Logout
            </button>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>