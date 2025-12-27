<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { Trash2, Database } from 'lucide-vue-next';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tableau de bord',
        href: '/dashboard',
    },
    {
        title: 'Administration',
        href: '/admin/reservations',
    },
    {
        title: 'Paramètres',
        href: '/admin/settings',
    },
];

interface SettingItem {
    id: number;
    key: string;
    title: string;
    value: any;
    type: string;
    description: string;
}

interface Props {
    settings: Record<string, SettingItem[]>;
}

const props = defineProps<Props>();

// Créer une copie mutable des settings
const localSettings = ref<Record<string, SettingItem[]>>(JSON.parse(JSON.stringify(props.settings)));
const saving = ref(false);

const groupLabels: Record<string, string> = {
    'reservations': 'Réservations',
    'notifications': 'Notifications',
    'general': 'Général',
};

const getInputType = (type: string) => {
    switch (type) {
        case 'integer':
        case 'float':
            return 'number';
        case 'boolean':
            return 'checkbox';
        default:
            return 'text';
    }
};

const getInputStep = (type: string) => {
    return type === 'float' ? '0.01' : '1';
};

const isTimeField = (key: string) => {
    return key.includes('_time') || key.includes('opening') || key.includes('closing');
};

const saveSettings = () => {
    saving.value = true;
    
    // Aplatir tous les settings dans un seul tableau
    const allSettings = Object.values(localSettings.value).flat();
    
    router.put('/admin/settings', {
        settings: allSettings.map(s => ({
            id: s.id,
            value: s.value
        }))
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Paramètres enregistrés avec succès');
            saving.value = false;
        },
        onError: (errors) => {
            Object.values(errors).forEach((err: any) => {
                toast.error(err);
            });
            saving.value = false;
        }
    });
};

// Gestion du cache
const clearingCache = ref(false);

const clearAllCache = async () => {
    if (!confirm('Êtes-vous sûr de vouloir vider tout le cache ?')) {
        return;
    }

    clearingCache.value = true;

    try {
        const response = await axios.post('/api/admin/cache/clear');
        if (response.data.success) {
            toast.success(response.data.message);
        }
    } catch (error: any) {
        toast.error(error.response?.data?.message || 'Erreur lors de la suppression du cache');
    } finally {
        clearingCache.value = false;
    }
};

const clearSpecificCache = async (type: string) => {
    clearingCache.value = true;

    try {
        const response = await axios.post('/api/admin/cache/clear-specific', { type });
        if (response.data.success) {
            toast.success(response.data.message);
        }
    } catch (error: any) {
        toast.error(error.response?.data?.message || 'Erreur lors de la suppression du cache');
    } finally {
        clearingCache.value = false;
    }
};

const cacheTypes = [
    { key: 'rooms', label: 'Salles', description: 'Cache des salles et images' },
    { key: 'users', label: 'Utilisateurs', description: 'Cache de recherche utilisateurs' },
    { key: 'equipments', label: 'Équipements', description: 'Liste des équipements' },
    { key: 'floors', label: 'Étages', description: 'Liste des étages' },
    { key: 'favorites', label: 'Favoris', description: 'Favoris utilisateurs' },
    { key: 'reservations', label: 'Réservations', description: 'Cache des réservations (5min)' },
];

</script>

<template>
    <Head title="Paramètres" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 py-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Paramètres de l'application</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Configurez les paramètres globaux de l'application</p>
            </div>

            <form @submit.prevent="saveSettings" class="space-y-6">
                <!-- Parcourir chaque groupe -->
                <div 
                    v-for="(groupSettings, groupKey) in localSettings" 
                    :key="groupKey"
                    class="bg-white dark:bg-gray-800 rounded-xl shadow-msl-s p-6"
                >
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        {{ groupLabels[groupKey] || groupKey }}
                    </h2>

                    <div class="space-y-4">
                        <div 
                            v-for="setting in groupSettings" 
                            :key="setting.id"
                            class="border-b border-gray-200 dark:border-gray-700 pb-4 last:border-0 last:pb-0"
                        >
                            <!-- Setting avec checkbox (boolean) -->
                            <div v-if="setting.type === 'boolean'" class="flex items-start gap-3">
                                <input
                                    :id="`setting-${setting.id}`"
                                    v-model="setting.value"
                                    type="checkbox"
                                    class="mt-1 w-4 h-4 text-msl-primary border-gray-300 rounded focus:ring-msl-primary"
                                />
                                <div class="flex-1">
                                    <label 
                                        :for="`setting-${setting.id}`"
                                        class="block font-medium text-gray-900 dark:text-gray-100 cursor-pointer"
                                    >
                                        {{ setting.title || setting.description }}
                                    </label>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        {{ setting.description }}
                                    </p>
                                    
                                </div>
                            </div>

                            <!-- Setting avec input (autres types) -->
                            <div v-else>
                                <label 
                                    :for="`setting-${setting.id}`"
                                    class="block font-medium text-gray-900 dark:text-gray-100 mb-1"
                                >
                                    {{ setting.title || setting.description }}
                                </label>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">
                                    {{ setting.description }}
                                </p>
                               

                                <!-- Input time pour les champs de temps -->
                                <input
                                    v-if="isTimeField(setting.key)"
                                    :id="`setting-${setting.id}`"
                                    v-model="setting.value"
                                    type="time"
                                    class="w-full max-w-md px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 focus:ring-2 focus:ring-msl-primary focus:border-transparent"
                                />

                                <!-- Input standard pour les autres -->
                                <input
                                    v-else
                                    :id="`setting-${setting.id}`"
                                    v-model="setting.value"
                                    :type="getInputType(setting.type)"
                                    :step="getInputStep(setting.type)"
                                    class="w-full max-w-md px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 focus:ring-2 focus:ring-msl-primary focus:border-transparent"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section Cache -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-msl-s p-6">
                    <div class="flex items-center gap-2 mb-4">
                        <Database class="w-5 h-5 text-msl-primary" />
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                            Gestion du Cache
                        </h2>
                    </div>

                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                        Videz le cache pour forcer le rechargement des données.
                    </p>

                    <!-- Cache général -->
                    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="font-medium text-red-900 dark:text-red-300 mb-1">
                                    Vider tout le cache
                                </h3>
                                <p class="text-sm text-red-700 dark:text-red-400">
                                    ⚠️ Supprime toutes les données en cache de l'application
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="clearAllCache"
                                :disabled="clearingCache"
                                class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition disabled:opacity-50"
                            >
                                <Trash2 class="w-4 h-4" />
                                Tout vider
                            </button>
                        </div>
                    </div>

                    <!-- Caches spécifiques -->
                    <div class="space-y-3">
                        <h3 class="font-medium text-gray-900 dark:text-gray-100 mb-3">
                            Cache par type
                        </h3>
                        <div
                            v-for="cacheType in cacheTypes"
                            :key="cacheType.key"
                            class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
                        >
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ cacheType.label }}
                                </p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">
                                    {{ cacheType.description }}
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="clearSpecificCache(cacheType.key)"
                                :disabled="clearingCache"
                                class="px-3 py-1.5 text-sm bg-gray-200 dark:bg-gray-600 text-gray-900 dark:text-gray-100 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500 transition disabled:opacity-50"
                            >
                                Vider
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Boutons d'action -->
                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        @click="router.visit('/admin/reservations')"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition"
                    >
                        Annuler
                    </button>
                    <button
                        type="submit"
                        :disabled="saving"
                        class="px-6 py-2 bg-msl-primary text-white rounded-lg hover:bg-msl-secondary transition disabled:opacity-50 font-medium shadow-msl-s"
                    >
                        {{ saving ? 'Enregistrement...' : '✓ Enregistrer' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
