<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, Boxes, Pill, Users } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Stock Room',
        href: '/inventory',
        icon: Boxes, // Represents storage or grouped items
    },
    {
        title: 'Pharmacy',
        href: '/pharmacy',
        icon: Pill, // Represents a pill or medicine
    },
    {
        title: 'Recipients',
        href: '/recipients',
        icon: Users, 
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset" class="pharmacy-sidebar">
        <SidebarHeader class="sidebar-header">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child class="sidebar-logo-button">
                        <Link :href="route('dashboard')" class="logo-link">
                            <AppLogo class="logo-icon" />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="sidebar-content">
            <div class="nav-container">
                <NavMain :items="mainNavItems" class="pharmacy-nav" />
            </div>
        </SidebarContent>

        <SidebarFooter class="sidebar-footer">
            <div class="user-container">
                <NavUser class="pharmacy-user-nav" />
            </div>
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>

<style scoped>
/* Pharmacy Sidebar Styling */
.pharmacy-sidebar {
    --sidebar-background: #f0fdf4; /* green-50 */
    --sidebar-foreground: #166534; /* green-800 */
    --sidebar-primary: #16a34a; /* green-600 */
    --sidebar-primary-foreground: #ffffff; /* white */
    --sidebar-accent: #dcfce7; /* green-100 */
    --sidebar-accent-foreground: #166534; /* green-800 */
    --sidebar-border: #bbf7d0; /* green-200 */
    --sidebar-ring: #4ade80; /* green-400 */
    box-shadow: 2px 0 10px rgba(34, 197, 94, 0.1);
}

/* Header Styling */
.sidebar-header {
    background-color: #16a34a; /* green-600 */
    border-bottom: 1px solid #15803d; /* green-700 */
}

/* Content Styling */
.sidebar-content {
    background-color: #f0fdf4; /* green-50 */
    border-right: 1px solid #bbf7d0; /* green-200 */
}

/* Footer Styling */
.sidebar-footer {
    background-color: #dcfce7; /* green-100 */
    border-top: 1px solid #bbf7d0; /* green-200 */
    border-right: 1px solid #bbf7d0; /* green-200 */
}

/* Logo Styling */
.sidebar-logo-button {
    background: transparent !important;
    border: none !important;
    padding: 0 !important;
}

.logo-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    border-radius: 0.5rem;
    transition: background-color 0.2s ease;
}

.logo-link:hover {
    background-color: rgba(34, 197, 94, 0.1);
}

.logo-icon {
    color: white;
}

/* Container Styling */
.nav-container,
.user-container {
    padding: 0.5rem;
}

/* Navigation Styling */
:deep(.pharmacy-nav) {
    .sidebar-menu-button {
        color: #374151; /* gray-700 */
        border-radius: 0.5rem;
        transition: all 0.2s ease;
        border: 1px solid transparent;
        padding: 0.5rem 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        width: 100%;
        text-align: left;
    }
    
    .sidebar-menu-button:hover {
        color: #166534; /* green-800 */
        background-color: #dcfce7; /* green-100 */
        border-color: #bbf7d0; /* green-200 */
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        transform: translateX(2px);
    }
    
    .sidebar-menu-button[data-active="true"] {
        background-color: #16a34a; /* green-600 */
        color: white;
        border-color: #16a34a; /* green-600 */
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    
    .sidebar-menu-button[data-active="true"]:hover {
        background-color: #15803d; /* green-700 */
        border-color: #15803d; /* green-700 */
        transform: translateX(0);
    }
    
    /* Icon styling */
    .sidebar-menu-button svg {
        width: 1.25rem;
        height: 1.25rem;
        transition: all 0.2s ease;
        flex-shrink: 0;
    }
    
    .sidebar-menu-button:hover svg {
        transform: scale(1.1);
    }
    
    .sidebar-menu-button[data-active="true"] svg {
        color: white;
    }
}

/* User Navigation Styling */
:deep(.pharmacy-user-nav) {
    .sidebar-menu-button {
        color: #374151; /* gray-700 */
        border-radius: 0.5rem;
        transition: all 0.2s ease;
        border: 1px solid transparent;
        padding: 0.5rem 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        width: 100%;
        text-align: left;
    }
    
    .sidebar-menu-button:hover {
        color: #166534; /* green-800 */
        background-color: #bbf7d0; /* green-200 */
        border-color: #86efac; /* green-300 */
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    }
}

/* Collapsed state styling */
:deep([data-state="collapsed"]) {
    .pharmacy-nav .sidebar-menu-button,
    .pharmacy-user-nav .sidebar-menu-button {
        justify-content: center;
        padding: 0.75rem;
    }
    
    .pharmacy-nav .sidebar-menu-button span,
    .pharmacy-user-nav .sidebar-menu-button span {
        display: none;
    }
    
    .pharmacy-nav .sidebar-menu-button svg,
    .pharmacy-user-nav .sidebar-menu-button svg {
        width: 1.5rem;
        height: 1.5rem;
    }
}

/* Enhanced hover effects for collapsed state */
:deep([data-state="collapsed"]) .pharmacy-nav .sidebar-menu-button:hover {
    background-color: #bbf7d0; /* green-200 */
    transform: scale(1.1);
}

/* Smooth transitions */
.pharmacy-sidebar * {
    transition: all 0.2s ease-in-out;
}

/* Custom scrollbar for sidebar content */
:deep(.sidebar-content) {
    scrollbar-width: thin;
    scrollbar-color: #bbf7d0 #dcfce7; /* green-200 green-100 */
}

:deep(.sidebar-content)::-webkit-scrollbar {
    width: 6px;
}

:deep(.sidebar-content)::-webkit-scrollbar-track {
    background: #dcfce7; /* green-100 */
    border-radius: 3px;
}

:deep(.sidebar-content)::-webkit-scrollbar-thumb {
    background: #bbf7d0; /* green-200 */
    border-radius: 3px;
}

:deep(.sidebar-content)::-webkit-scrollbar-thumb:hover {
    background: #4ade80; /* green-400 */
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .pharmacy-sidebar {
        box-shadow: none;
    }
}
</style>