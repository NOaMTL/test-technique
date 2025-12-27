<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { X, Upload, GripVertical } from 'lucide-vue-next';
import { toast } from '@/components/ui/toast';
import draggable from 'vuedraggable';

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Administration', href: '/admin/reservations' },
    { title: 'Gestion des salles', href: '/admin/rooms' },
    { title: 'Nouvelle salle' },
];

const form = useForm({
    nom: '',
    capacite: 1,
    etage: 0,
    equipement: [] as string[],
    description: '',
    images: [] as File[],
    constraints: {
        time_period: null as 'morning' | 'afternoon' | 'full_day' | null,
        days_allowed: [] as number[],
        advance_booking_days: null as number | null,
        weekly_hours_quota: null as number | null,
        daily_booking_limit: null as number | null,
        min_participants: null as number | null,
    },
});

const newEquipement = ref('');

// Gestion des images avec drag & drop
interface ImagePreview {
    file: File;
    preview: string;
    tempId: string;
}

const imagePreviews = ref<ImagePreview[]>([]);
const dragOptions = {
    animation: 200,
    handle: '.drag-handle',
    ghostClass: 'ghost',
};

const addEquipement = () => {
    if (newEquipement.value.trim()) {
        form.equipement.push(newEquipement.value.trim());
        newEquipement.value = '';
    }
};

const removeEquipement = (index: number) => {
    form.equipement.splice(index, 1);
};

const handleImageUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const files = Array.from(target.files || []);
    
    files.forEach(file => {
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                imagePreviews.value.push({
                    file: file,
                    preview: e.target?.result as string,
                    tempId: `temp-${Date.now()}-${Math.random()}`,
                });
            };
            reader.readAsDataURL(file);
        }
    });
    
    target.value = '';
};

const removeImage = (tempId: string) => {
    const index = imagePreviews.value.findIndex(img => img.tempId === tempId);
    if (index !== -1) {
        imagePreviews.value.splice(index, 1);
    }
};

const submit = () => {
    // Mettre à jour form.images avec l'ordre actuel
    form.images = imagePreviews.value.map(img => img.file);
    
    form.post('/admin/rooms', {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Salle créée avec succès');
        },
        onError: () => {
            toast.error('Une erreur est survenue lors de la création');
        },
    });
};
</script>

<template>
    <AppLayout :breadcrumb-items="breadcrumbItems">
        <Head title="Nouvelle Salle" />

        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Nouvelle Salle</h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Créer une nouvelle salle de réunion
                    </p>
                </div>
                <a
                    href="/admin/rooms"
                    class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition"
                >
                    Annuler
                </a>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Informations de base -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-msl-s space-y-4">
                    <h2 class="text-lg font-semibold mb-4">Informations de base</h2>

                    <!-- Nom -->
                    <div>
                        <label for="room-nom" class="block text-sm font-medium mb-2">
                            Nom de la salle *
                        </label>
                        <input
                            id="room-nom"
                            v-model="form.nom"
                            type="text"
                            required
                            class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                            :class="{ 'border-red-500': form.errors.nom }"
                            placeholder="Ex: Salle de réunion A"
                            :aria-describedby="form.errors.nom ? 'nom-error' : undefined"
                            aria-required="true"
                        />
                        <p v-if="form.errors.nom" id="nom-error" class="mt-1 text-sm text-red-500" role="alert">{{ form.errors.nom }}</p>
                    </div>

                    <!-- Capacité et Étage -->
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label for="room-capacite" class="block text-sm font-medium mb-2">
                                Capacité *
                            </label>
                            <input
                                id="room-capacite"
                                v-model.number="form.capacite"
                                type="number"
                                required
                                min="1"
                                class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                                :class="{ 'border-red-500': form.errors.capacite }"
                                placeholder="Ex: 10"
                                :aria-describedby="form.errors.capacite ? 'capacite-error' : undefined"
                                aria-required="true"
                            />
                            <p v-if="form.errors.capacite" id="capacite-error" class="mt-1 text-sm text-red-500" role="alert">{{ form.errors.capacite }}</p>
                        </div>

                        <div>
                            <label for="room-etage" class="block text-sm font-medium mb-2">
                                Étage *
                            </label>
                            <input
                                id="room-etage"
                                v-model.number="form.etage"
                                type="number"
                                required
                                class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                                :class="{ 'border-red-500': form.errors.etage }"
                                placeholder="Ex: 2"
                                :aria-describedby="form.errors.etage ? 'etage-error' : undefined"
                                aria-required="true"
                            />
                            <p v-if="form.errors.etage" id="etage-error" class="mt-1 text-sm text-red-500" role="alert">{{ form.errors.etage }}</p>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="room-description" class="block text-sm font-medium mb-2">
                            Description
                        </label>
                        <textarea
                            id="room-description"
                            v-model="form.description"
                            rows="3"
                            class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                            :class="{ 'border-red-500': form.errors.description }"
                            placeholder="Description de la salle..."
                            :aria-describedby="form.errors.description ? 'description-error' : undefined"
                        ></textarea>
                        <p v-if="form.errors.description" id="description-error" class="mt-1 text-sm text-red-500" role="alert">{{ form.errors.description }}</p>
                    </div>
                </div>

                <!-- Équipements -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-msl-s space-y-4">
                    <h2 class="text-lg font-semibold mb-4">Équipements</h2>

                    <div>
                        <label class="block text-sm font-medium mb-2">
                            Ajouter un équipement
                        </label>
                        <div class="flex gap-2">
                            <input
                                v-model="newEquipement"
                                type="text"
                                class="flex-1 px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                                placeholder="Ex: Vidéoprojecteur"
                                @keyup.enter="addEquipement"
                            />
                            <button
                                type="button"
                                @click="addEquipement"
                                class="px-4 py-2 bg-msl-primary text-white rounded-lg hover:bg-msl-secondary transition"
                            >
                                Ajouter
                            </button>
                        </div>
                        <p v-if="form.errors.equipement" class="mt-1 text-sm text-red-500">{{ form.errors.equipement }}</p>
                    </div>

                    <!-- Liste des équipements -->
                    <div v-if="form.equipement.length > 0" class="space-y-2">
                        <p class="text-sm font-medium">Équipements ajoutés :</p>
                        <div class="flex flex-wrap gap-2">
                            <div
                                v-for="(eq, index) in form.equipement"
                                :key="index"
                                class="flex items-center gap-2 px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-lg"
                            >
                                <span class="text-sm">{{ eq }}</span>
                                <button
                                    type="button"
                                    @click="removeEquipement(index)"
                                    class="text-red-500 hover:text-red-700"
                                >
                                    <X class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Images -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-msl-s space-y-4">
                    <h2 class="text-lg font-semibold mb-4">Images</h2>

                    <div>
                        <label class="block text-sm font-medium mb-2">
                            Ajouter des images
                        </label>
                        <div class="flex items-center justify-center w-full">
                            <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <Upload class="w-8 h-8 mb-2 text-gray-500 dark:text-gray-400" />
                                    <p class="mb-2 text-sm text-gray-500 dark:text-gray-400">
                                        <span class="font-semibold">Cliquez pour uploader</span> ou glissez-déposez
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">PNG, JPG, GIF, WEBP (MAX. 2MB)</p>
                                </div>
                                <input
                                    type="file"
                                    class="hidden"
                                    accept="image/*"
                                    multiple
                                    @change="handleImageUpload"
                                />
                            </label>
                        </div>
                        <p v-if="form.errors.images" class="mt-1 text-sm text-red-500">{{ form.errors.images }}</p>
                    </div>

                    <!-- Preview des images avec drag & drop -->
                    <div v-if="imagePreviews.length > 0" class="space-y-2">
                        <p class="text-sm font-medium">Images à ajouter : <span class="text-xs text-gray-500">(glissez pour réorganiser)</span></p>
                        <draggable
                            v-model="imagePreviews"
                            item-key="tempId"
                            class="grid grid-cols-2 md:grid-cols-4 gap-4"
                            v-bind="dragOptions"
                        >
                            <template #item="{ element: imagePreview }">
                                <div class="relative group bg-white dark:bg-gray-700 rounded-lg shadow-sm">
                                    <div class="drag-handle absolute top-2 left-2 p-1.5 bg-gray-800/70 text-white rounded cursor-move opacity-0 group-hover:opacity-100 transition-opacity z-10">
                                        <GripVertical class="w-4 h-4" />
                                    </div>
                                    <img
                                        :src="imagePreview.preview"
                                        alt="Preview"
                                        class="w-full h-32 object-cover rounded-lg"
                                    />
                                    <button
                                        type="button"
                                        @click="removeImage(imagePreview.tempId)"
                                        class="absolute top-2 right-2 p-1.5 bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600 cursor-pointer z-10"
                                    >
                                        <X class="w-4 h-4" />
                                    </button>
                                </div>
                            </template>
                        </draggable>
                    </div>
                </div>

                <!-- Contraintes dynamiques -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-msl-s space-y-4">
                    <h2 class="text-lg font-semibold mb-4">Contraintes de réservation</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Configurez les règles spécifiques pour cette salle
                    </p>

                    <!-- Période de la journée -->
                    <div>
                        <label class="block text-sm font-medium mb-2">
                            Période de la journée
                        </label>
                        <select
                            v-model="form.constraints.time_period"
                            class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                        >
                            <option :value="null">Toute la journée</option>
                            <option value="morning">Matin uniquement (avant 12h)</option>
                            <option value="afternoon">Après-midi uniquement (après 12h)</option>
                        </select>
                    </div>

                    <!-- Jours autorisés -->
                    <div>
                        <label class="block text-sm font-medium mb-2">
                            Jours autorisés
                        </label>
                        <div class="grid grid-cols-7 gap-2">
                            <label
                                v-for="day in [
                                    { value: 1, label: 'Lun' },
                                    { value: 2, label: 'Mar' },
                                    { value: 3, label: 'Mer' },
                                    { value: 4, label: 'Jeu' },
                                    { value: 5, label: 'Ven' },
                                    { value: 6, label: 'Sam' },
                                    { value: 7, label: 'Dim' },
                                ]"
                                :key="day.value"
                                class="flex items-center justify-center p-2 border rounded-lg cursor-pointer transition"
                                :class="{
                                    'bg-msl-primary text-white border-msl-primary': form.constraints.days_allowed.includes(day.value),
                                    'hover:bg-gray-50 dark:hover:bg-gray-700': !form.constraints.days_allowed.includes(day.value)
                                }"
                            >
                                <input
                                    type="checkbox"
                                    :value="day.value"
                                    v-model="form.constraints.days_allowed"
                                    class="sr-only"
                                />
                                <span class="text-sm font-medium">{{ day.label }}</span>
                            </label>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            Laissez vide pour autoriser tous les jours
                        </p>
                    </div>

                    <!-- Réservation à l'avance -->
                    <div>
                        <label class="block text-sm font-medium mb-2">
                            Réservation à l'avance (jours)
                        </label>
                        <input
                            v-model.number="form.constraints.advance_booking_days"
                            type="number"
                            min="1"
                            class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                            placeholder="Ex: 7 pour 1 semaine"
                        />
                        <p class="mt-1 text-xs text-gray-500">
                            Maximum de jours à l'avance pour réserver (laissez vide pour illimité)
                        </p>
                    </div>

                    <!-- Quota hebdomadaire -->
                    <div>
                        <label class="block text-sm font-medium mb-2">
                            Quota hebdomadaire (heures)
                        </label>
                        <input
                            v-model.number="form.constraints.weekly_hours_quota"
                            type="number"
                            min="1"
                            step="0.5"
                            class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                            placeholder="Ex: 4"
                        />
                        <p class="mt-1 text-xs text-gray-500">
                            Nombre maximum d'heures cumulées par utilisateur par semaine (laissez vide pour illimité)
                        </p>
                    </div>

                    <!-- Limite quotidienne -->
                    <div>
                        <label class="block text-sm font-medium mb-2">
                            Limite de réservations par jour
                        </label>
                        <input
                            v-model.number="form.constraints.daily_booking_limit"
                            type="number"
                            min="1"
                            class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                            placeholder="Ex: 1"
                        />
                        <p class="mt-1 text-xs text-gray-500">
                            Nombre maximum de réservations par jour par utilisateur (laissez vide pour illimité)
                        </p>
                    </div>

                    <!-- Participants minimum -->
                    <div>
                        <label class="block text-sm font-medium mb-2">
                            Nombre minimum de participants
                        </label>
                        <input
                            v-model.number="form.constraints.min_participants"
                            type="number"
                            min="1"
                            class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                            placeholder="Ex: 3"
                        />
                        <p class="mt-1 text-xs text-gray-500">
                            Nombre minimum de participants incluant l'utilisateur qui réserve (laissez vide pour aucun minimum)
                        </p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-3">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex-1 px-4 py-3 bg-msl-primary text-white rounded-lg hover:bg-msl-secondary transition disabled:opacity-50 font-medium shadow-msl-s cursor-pointer"
                    >
                        {{ form.processing ? 'Création...' : 'Créer la salle' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
