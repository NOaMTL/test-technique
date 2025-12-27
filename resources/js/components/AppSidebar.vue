<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import KeyboardShortcutsHelp from '@/components/KeyboardShortcutsHelp.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutGrid, CalendarDays, Users, Settings, DoorOpen, Keyboard } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { computed, ref } from 'vue';

const page = usePage();
const isAdmin = computed(() => page.props.auth?.user?.role === 'admin');

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Tableau de bord',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];

    if (isAdmin.value) {
        items.push(
            {
                title: 'Réservations',
                href: '/admin/reservations',
                icon: CalendarDays,
            },
            {
                title: 'Gestion des salles',
                href: '/admin/rooms',
                icon: DoorOpen,
            },
            {
                title: 'Gestion utilisateurs',
                href: '/admin/users',
                icon: Users,
            },
            {
                title: 'Paramètres',
                href: '/admin/settings',
                icon: Settings,
            }
        );
    } else {
        items.push(
            {
                title: 'Mes réservations',
                href: '/my-reservations',
                icon: CalendarDays,
            },
            {
                title: 'Salles Favorites',
                href: '/rooms',
                icon: DoorOpen,
            }
        );
    }

    return items;
});

const keyboardShortcutsRef = ref<InstanceType<typeof KeyboardShortcutsHelp>>();

const openKeyboardShortcuts = () => {
    keyboardShortcutsRef.value?.open();
};
</script>

<template>
    <Sidebar 
        collapsible="icon" 
        variant="inset"
        :class="{ 'admin-sidebar': isAdmin }"
    >
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton @click="openKeyboardShortcuts">
                        <Keyboard class="w-4 h-4" />
                        <span>Raccourcis clavier</span>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
    <KeyboardShortcutsHelp ref="keyboardShortcutsRef" class="hidden" />
</template>

<style scoped>
:deep(.admin-sidebar) {
    --sidebar-background: #007461;
    --sidebar-foreground: white;
    --sidebar-primary: #009980;
    --sidebar-primary-foreground: white;
    --sidebar-accent: #005d4e;
    --sidebar-accent-foreground: white;
    --sidebar-border: #005d4e;
    --sidebar-ring: #009980;
}

:deep(.admin-sidebar .group[data-sidebar="sidebar"]) {
    background-color: #006f4e;
    border-color: #005a3e;
}

:deep(.admin-sidebar) {
    color: white;
}

:deep(.admin-sidebar [data-sidebar="menu-button"]) {
    color: white;
}

:deep(.admin-sidebar [data-sidebar="menu-button"]:hover) {
    background-color: #005d4e;
    color: white;
}

:deep(.admin-sidebar [data-sidebar="menu-button"][data-active="true"]) {
    background-color: #009980;
    color: white;
}

:deep(.admin-sidebar svg) {
    color: white;
}

:deep(.admin-sidebar .text-sidebar-foreground) {
    color: white !important;
}

:deep(.admin-sidebar .text-muted-foreground) {
    color: rgba(255, 255, 255, 0.7) !important;
}
</style>
