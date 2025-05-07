<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Pill, Stethoscope, Clock, Phone, MapPin, Users } from 'lucide-vue-next';
</script>

<template>
    <Head title="Welcome to Municipal Pharmacy">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div class="flex min-h-screen flex-col items-center bg-[#f8f9fa] p-6 text-[#2d3748] dark:bg-[#1a202c] dark:text-[#e2e8f0] lg:justify-center lg:p-8">
        <header class="not-has-[nav]:hidden mb-6 w-full max-w-[335px] text-sm lg:max-w-4xl">
            <nav class="flex items-center justify-end gap-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="inline-block rounded-sm border border-[#4a5568] px-5 py-1.5 text-sm leading-normal text-[#2d3748] hover:border-[#2d3748] dark:border-[#4a5568] dark:text-[#e2e8f0] dark:hover:border-[#e2e8f0]"
                >
                    Dashboard
                </Link>
                <template v-else>
                    <Link
                        :href="route('login')"
                        class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#2d3748] hover:border-[#4a5568] dark:text-[#e2e8f0] dark:hover:border-[#4a5568]"
                    >
                        Log in
                    </Link>
                    <Link
                        :href="route('register')"
                        class="inline-block rounded-sm border border-[#4a5568] px-5 py-1.5 text-sm leading-normal text-[#2d3748] hover:border-[#2d3748] dark:border-[#4a5568] dark:text-[#e2e8f0] dark:hover:border-[#e2e8f0]"
                    >
                        Register
                    </Link>
                </template>
            </nav>
        </header>
        <div class="duration-750 starting:opacity-0 flex w-full items-center justify-center opacity-100 transition-opacity lg:grow">
            <main class="flex w-full max-w-[335px] flex-col-reverse overflow-hidden rounded-lg lg:max-w-4xl lg:flex-row">
                <div
                    class="flex-1 rounded-bl-lg rounded-br-lg bg-white p-6 pb-12 text-[13px] leading-[20px] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:bg-[#2d3748] dark:text-[#e2e8f0] dark:shadow-[inset_0px_0px_0px_1px_#4a556833] lg:rounded-br-none lg:rounded-tl-lg lg:p-20"
                >
                    <h1 class="mb-1 text-xl font-medium">Welcome to Municipal Pharmacy</h1>
                    <p class="mb-4 text-[#4a5568] dark:text-[#a0aec0]">
                        Your trusted community healthcare partner. <br />We offer a range of services to keep you healthy.
                    </p>
                    <ul class="mb-6 flex flex-col lg:mb-8">
                        <li
                            class="relative flex items-center gap-4 py-2 before:absolute before:bottom-0 before:left-[0.4rem] before:top-1/2 before:border-l before:border-[#e2e8f0] dark:before:border-[#4a5568]"
                        >
                            <span class="relative bg-white py-1 dark:bg-[#2d3748]">
                                <span
                                    class="flex h-3.5 w-3.5 items-center justify-center rounded-full border border-[#e2e8f0] bg-[#f8f9fa] shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] dark:border-[#4a5568] dark:bg-[#2d3748]"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#38a169] dark:bg-[#48bb78]" />
                                </span>
                            </span>
                            <span class="flex items-center">
                                <Pill class="mr-2 h-4 w-4 text-[#38a169]" />
                                Prescription Services
                            </span>
                        </li>
                        <li
                            class="relative flex items-center gap-4 py-2 before:absolute before:bottom-1/2 before:left-[0.4rem] before:top-0 before:border-l before:border-[#e2e8f0] dark:before:border-[#4a5568]"
                        >
                            <span class="relative bg-white py-1 dark:bg-[#2d3748]">
                                <span
                                    class="flex h-3.5 w-3.5 items-center justify-center rounded-full border border-[#e2e8f0] bg-[#f8f9fa] shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] dark:border-[#4a5568] dark:bg-[#2d3748]"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#38a169] dark:bg-[#48bb78]" />
                                </span>
                            </span>
                            <span class="flex items-center">
                                <Stethoscope class="mr-2 h-4 w-4 text-[#38a169]" />
                                Health Consultations
                            </span>
                        </li>
                        <li
                            class="relative flex items-center gap-4 py-2 before:absolute before:bottom-1/2 before:left-[0.4rem] before:top-0 before:border-l before:border-[#e2e8f0] dark:before:border-[#4a5568]"
                        >
                            <span class="relative bg-white py-1 dark:bg-[#2d3748]">
                                <span
                                    class="flex h-3.5 w-3.5 items-center justify-center rounded-full border border-[#e2e8f0] bg-[#f8f9fa] shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] dark:border-[#4a5568] dark:bg-[#2d3748]"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#38a169] dark:bg-[#48bb78]" />
                                </span>
                            </span>
                            <span class="flex items-center">
                                <Users class="mr-2 h-4 w-4 text-[#38a169]" />
                                Community Programs
                            </span>
                        </li>
                    </ul>
                    
                    <div class="mb-6 space-y-3 text-[#4a5568] dark:text-[#a0aec0]">
                        <div class="flex items-center">
                            <Clock class="mr-2 h-4 w-4 text-[#38a169]" />
                            <span>Open Monday-Friday: 8:00 AM - 8:00 PM</span>
                        </div>
                        <div class="flex items-center">
                            <Phone class="mr-2 h-4 w-4 text-[#38a169]" />
                            <span>Call us: (555) 123-4567</span>
                        </div>
                        <div class="flex items-center">
                            <MapPin class="mr-2 h-4 w-4 text-[#38a169]" />
                            <span>123 Health Street, Medical District</span>
                        </div>
                    </div>
                    
                    <ul class="flex gap-3 text-sm leading-normal">
                        <li>
                            <a
                                href="#"
                                class="inline-block rounded-sm border border-[#38a169] bg-[#38a169] px-5 py-1.5 text-sm leading-normal text-white hover:border-[#2f855a] hover:bg-[#2f855a] dark:border-[#48bb78] dark:bg-[#48bb78] dark:text-[#1a202c] dark:hover:border-[#38a169] dark:hover:bg-[#38a169]"
                            >
                                Book Appointment
                            </a>
                        </li>
                        <li>
                            <a
                                href="#"
                                class="inline-block rounded-sm border border-[#4a5568] px-5 py-1.5 text-sm leading-normal hover:border-[#2d3748] dark:border-[#4a5568] dark:hover:border-[#e2e8f0]"
                            >
                                Learn More
                            </a>
                        </li>
                    </ul>
                </div>
                <div
                    class="relative -mb-px aspect-335/376 w-full shrink-0 overflow-hidden rounded-t-lg bg-[#ebf8f1] dark:bg-[#1c4532] lg:-ml-px lg:mb-0 lg:aspect-auto lg:w-[438px] lg:rounded-r-lg lg:rounded-t-none"
                >
                    <!-- Pharmacy Logo/Icon -->
                    <svg
                        class="duration-750 starting:translate-y-6 starting:opacity-0 absolute left-1/2 top-1/2 w-3/4 max-w-none -translate-x-1/2 -translate-y-1/2 text-[#38a169] opacity-100 transition-all dark:text-[#48bb78]"
                        viewBox="0 0 24 24"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path d="M19 8C20.6569 8 22 6.65685 22 5C22 3.34315 20.6569 2 19 2C17.3431 2 16 3.34315 16 5C16 6.65685 17.3431 8 19 8Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M7 13H12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M7 17H16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M14 2H9C4 2 2 4 2 9V15C2 20 4 22 9 22H15C20 22 22 20 22 15V10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    
                    <!-- Decorative Pattern -->
                    <svg
                        class="absolute bottom-0 left-0 w-full opacity-20"
                        viewBox="0 0 400 200"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <circle cx="50" cy="150" r="40" fill="currentColor" />
                        <circle cx="150" cy="100" r="60" fill="currentColor" />
                        <circle cx="250" cy="150" r="40" fill="currentColor" />
                        <circle cx="350" cy="100" r="60" fill="currentColor" />
                    </svg>
                    
                    <div
                        class="absolute inset-0 rounded-t-lg shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:shadow-[inset_0px_0px_0px_1px_#4a556833] lg:overflow-hidden lg:rounded-r-lg lg:rounded-t-none"
                    />
                </div>
            </main>
        </div>
        <div class="h-14.5 hidden lg:block"></div>
    </div>
</template>