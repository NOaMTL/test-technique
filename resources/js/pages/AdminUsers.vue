<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

const page = usePage();

// Vérifier que l'utilisateur est admin
const isAdmin = computed(() => page.props.auth?.user?.role === 'admin');

if (!isAdmin.value) {
    router.visit('/dashboard');
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tableau de bord',
        href: '/dashboard',
    },
    {
        title: 'Gestion utilisateurs',
        href: '/admin/users',
    },
];

interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    reservations_count?: number;
}

const users = ref<User[]>([]);
const loading = ref(true);

onMounted(async () => {
    try {
        const response = await axios.get('/api/admin/users');
        users.value = response.data;
    } catch (error: any) {
        console.error('Erreur lors du chargement des utilisateurs:', error);
        if (error.response?.status === 403) {
            router.visit('/dashboard');
        }
    } finally {
        loading.value = false;
    }
});


</script>

<template>
    <Head title="Gestion utilisateurs" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto p-4">
            <!-- En-tête -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Gestion des utilisateurs</h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Gérez les rôles et permissions des utilisateurs
                    </p>
                </div>
            </div>

            <!-- Liste des utilisateurs -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div v-if="loading" class="text-center py-8">Chargement...</div>
                <div v-else-if="users.length === 0" class="text-center py-8 text-gray-500">
                    Aucun utilisateur trouvé.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b dark:border-gray-700">
                                <th class="text-left p-3">Nom</th>
                                <th class="text-left p-3">Email</th>
                                <th class="text-left p-3">Rôle</th>
                                <th class="text-left p-3">Réservations</th>
                              
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="user in users"
                                :key="user.id"
                                class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
                            >
                                <td class="p-3">
                                    {{ user.name }}
                                    <span
                                        v-if="user.email === page.props.auth?.user?.email"
                                        class="ml-2 text-xs text-blue-600 dark:text-blue-400"
                                    >
                                        (Vous)
                                    </span>
                                </td>
                                <td class="p-3">{{ user.email }}</td>
                                <td class="p-3">
                                    <span
                                        :class="[
                                            'px-2 py-1 text-xs rounded',
                                            user.role === 'admin'
                                                ? 'bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200'
                                                : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200'
                                        ]"
                                    >
                                        {{ user.role === 'admin' ? 'Administrateur' : 'Utilisateur' }}
                                    </span>
                                </td>
                                <td class="p-3">{{ user.reservations_count || 0 }}</td>
                                
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Légende -->
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                <h3 class="font-semibold mb-2">Permissions des rôles</h3>
                <ul class="space-y-1 text-sm">
                    <li><strong>Utilisateur :</strong> Peut créer et gérer ses propres réservations</li>
                    <li><strong>Administrateur :</strong> Peut voir toutes les réservations, modifier et supprimer n'importe quelle réservation, gérer les utilisateurs</li>
                </ul>
            </div>
        </div>
    </AppLayout>
</template>
