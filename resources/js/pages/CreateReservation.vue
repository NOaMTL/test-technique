<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import UserSearch from '@/components/UserSearch.vue';
import { Check } from 'lucide-vue-next';
import { DatePicker } from '@/components/ui/calendar';
import { toast } from '@/components/ui/toast';
import RoomCard from '@/components/RoomCard.vue';
import SelectedRoomCard from '@/components/SelectedRoomCard.vue';


interface AppSettings {
    opening_time: string;
    closing_time: string;
    slot_duration: number;
    min_advance_hours: number;
    max_advance_days: number;
    block_weekends: boolean;
}

interface Props {
    settings: AppSettings;
}

const props = defineProps<Props>();

const page = usePage();
const isAdmin = computed(() => page.props.auth?.user?.role === 'admin');
const currentUserId = computed(() => page.props.auth?.user?.id as number);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tableau de bord',
        href: '/dashboard',
    },
    {
        title: 'Nouvelle réservation',
        href: '/reservations/create',
    },
];

interface Room {
    id: number;
    nom: string;
    capacite: number;
    etage: number;
    equipement: string[];
    description: string;
    is_favorite?: boolean;
    min_participants?: number;
}

const allRooms = ref<Room[]>([]);
const loading = ref(true);
const submitting = ref(false);
const errors = ref<Record<string, string>>({});
const checkingAvailability = ref(false);
const availableRooms = ref<Room[]>([]);
const step = ref(1); // 1: Critères, 2: Sélection salle
const showRoomList = ref(true); // Pour replier la liste une fois salle sélectionnée

// Critères de recherche
const criteria = ref({
    date: '',
    heure_debut: '',
    heure_fin: '',
    nombre_personnes: null as number | null,
    equipements: [] as string[],
});

// Formulaire final
const form = ref({
    room_id: null as number | null,
    date: '',
    heure_debut: '',
    heure_fin: '',
    titre: '',
    description: '',
    nombre_personnes: null as number | null,
    participants: [] as number[],
    notify_participants: false,
});

const selectedRoom = computed(() => {
    if (!form.value.room_id) return null;
    return availableRooms.value.find(r => r.id === form.value.room_id) || allRooms.value.find(r => r.id === form.value.room_id);
});

const canSubmit = computed(() => {
    if (!form.value.room_id || !form.value.titre) return false;
    
    // Vérifier min_participants basé uniquement sur les participants ajoutés
    if (selectedRoom.value?.min_participants) {
        // Le créateur est toujours inclus automatiquement
        const totalParticipants = form.value.participants.length;
        
        if (totalParticipants < selectedRoom.value.min_participants) {
            return false;
        }
    }
    
    return true;
});

const availableTimeSlots = computed(() => {
    const slots: string[] = [];
    const now = new Date();
    const today = now.toISOString().split('T')[0];
    const isToday = criteria.value.date === today;
    
    // Parser les heures d'ouverture et fermeture depuis les settings
    const [openHour, openMinute] = props.settings.opening_time.split(':').map(Number);
    const [closeHour, closeMinute] = props.settings.closing_time.split(':').map(Number);
    const slotDuration = props.settings.slot_duration;
    
    const openingMinutes = openHour * 60 + openMinute;
    const closingMinutes = closeHour * 60 + closeMinute;
    
    for (let minutes = openingMinutes; minutes < closingMinutes; minutes += slotDuration) {
        const hour = Math.floor(minutes / 60);
        const minute = minutes % 60;
        const timeString = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
        
        if (isToday) {
            // Si c'est aujourd'hui, ne montrer que les créneaux futurs
            const slotTime = new Date(now);
            slotTime.setHours(hour, minute, 0, 0);
            
            if (slotTime > now) {
                slots.push(timeString);
            }
        } else {
            slots.push(timeString);
        }
    }
    return slots;
});

const minDate = computed(() => {
    return new Date().toISOString().split('T')[0];
});

const maxDate = computed(() => {
    const max = new Date();
    max.setDate(max.getDate() + props.settings.max_advance_days);
    return max.toISOString().split('T')[0];
});

const allEquipments = computed(() => {
    const equipSet = new Set<string>();
    allRooms.value.forEach(room => {
        room.equipement.forEach(eq => equipSet.add(eq));
    });
    return Array.from(equipSet).sort();
});

onMounted(async () => {
    try {
        const roomsRes = await axios.get('/api/rooms');
        allRooms.value = roomsRes.data;
    } catch (error) {
        console.error('Erreur lors du chargement:', error);
    } finally {
        loading.value = false;
    }
});

const searchAvailableRooms = async () => {
    if (!criteria.value.date || !criteria.value.heure_debut || !criteria.value.heure_fin) {
        toast.error('Veuillez renseigner la date et les horaires');
        return;
    }

    checkingAvailability.value = true;
    errors.value = {};

    try {
        // Appeler l'API Laravel qui fait tout le filtrage
        const response = await axios.post('/api/rooms/available', {
            date: criteria.value.date,
            heure_debut: criteria.value.heure_debut,
            heure_fin: criteria.value.heure_fin,
            capacite_min: criteria.value.nombre_personnes,
            nombre_personnes: criteria.value.nombre_personnes,
            equipements: criteria.value.equipements,
        });

        availableRooms.value = response.data;
        
        // Trier les salles : favoris en premier
        availableRooms.value.sort((a, b) => {
            if (a.is_favorite && !b.is_favorite) return -1;
            if (!a.is_favorite && b.is_favorite) return 1;
            return a.nom.localeCompare(b.nom);
        });

        if (availableRooms.value.length === 0) {
            errors.value.general = 'Aucune salle disponible pour ces critères';
        } else {
            // Passer à l'étape 2
            step.value = 2;
            showRoomList.value = true; // Réafficher la liste à chaque recherche
            // Pré-remplir le formulaire
            form.value.date = criteria.value.date;
            form.value.heure_debut = criteria.value.heure_debut;
            form.value.heure_fin = criteria.value.heure_fin;
            form.value.nombre_personnes = criteria.value.nombre_personnes;
        }
    } catch (error: any) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
            Object.keys(errors.value).forEach(key => {
                if (Array.isArray(errors.value[key])) {
                    errors.value[key] = errors.value[key][0];
                }
                toast.error(errors.value[key]);
            });
        } else {
            errors.value.general = 'Erreur lors de la recherche des salles disponibles';
            toast.error('Erreur lors de la recherche des salles disponibles');
        }
    } finally {
        checkingAvailability.value = false;
    }
};

const selectRoom = (roomId: number) => {
    form.value.room_id = roomId;
    // Ajouter le créateur comme participant par défaut
    if (!form.value.participants.includes(currentUserId.value)) {
        form.value.participants = [currentUserId.value];
    }
    showRoomList.value = false; // Replier la liste après sélection
};

const backToSearch = () => {
    step.value = 1;
    form.value.room_id = null;
    availableRooms.value = [];
    errors.value = {};
    showRoomList.value = true; // Réafficher la liste
};

const submitForm = async () => {
    if (!form.value.room_id) {
        toast.error('Veuillez sélectionner une salle');
        return;
    }

    errors.value = {};
    submitting.value = true;

    try {
        await axios.post('/api/reservations', form.value);
        toast.success('Réservation créée avec succès');
        setTimeout(() => {
            router.visit(isAdmin.value ? '/admin/reservations' : '/my-reservations');
        }, 1500);
    } catch (error: any) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
            Object.keys(errors.value).forEach(key => {
                if (Array.isArray(errors.value[key])) {
                    errors.value[key] = errors.value[key][0];
                }
                toast.error(errors.value[key]);
            });
        } else {
            toast.error('Une erreur est survenue lors de la création de la réservation');
        }
    } finally {
        submitting.value = false;
    }
};
</script>

<template>
    <Head title="Nouvelle réservation" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 w-full">
            <!-- En-tête -->
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Nouvelle réservation</h1>
                <a
                    href="/dashboard"
                    class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition"
                >
                    Annuler
                </a>
            </div>

            <!-- Stepper -->
            <div class="max-w-2xl mx-auto w-full">
                <div class="flex items-center justify-between">
                    <!-- Étape 1 -->
                    <div class="flex items-center flex-1">
                        <div class="flex flex-col items-center">
                            <div 
                                class="w-10 h-10 rounded-full flex items-center justify-center font-semibold transition"
                                :class="step >= 1 ? 'bg-msl-primary text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-500'"
                            >
                                <Check v-if="step > 1" class="w-5 h-5" />
                                <span v-else>1</span>
                            </div>
                            <div class="mt-2 text-sm font-medium text-center">
                                <div :class="step >= 1 ? 'text-msl-primary' : 'text-gray-500'">Critères</div>
                            </div>
                        </div>
                        <div class="flex-1 h-0.5 mx-4" :class="step >= 2 ? 'bg-msl-primary' : 'bg-gray-200 dark:bg-gray-700'"></div>
                    </div>
                    
                    <!-- Étape 2 -->
                    <div class="flex items-center">
                        <div class="flex flex-col items-center">
                            <div 
                                class="w-10 h-10 rounded-full flex items-center justify-center font-semibold transition"
                                :class="step >= 2 ? 'bg-msl-primary text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-500'"
                            >
                                2
                            </div>
                            <div class="mt-2 text-sm font-medium text-center">
                                <div :class="step >= 2 ? 'text-msl-primary' : 'text-gray-500'">Confirmation</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Étape 1: Critères -->
            <div v-if="step === 1">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6">
                    <div class="mb-4">
                        <h2 class="text-lg font-semibold">Rechercher une salle disponible</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Indiquez vos critères pour trouver les salles disponibles
                        </p>
                    </div>

                    <div class="space-y-4">
                        <!-- Date et horaires -->
                        <div class="grid md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-2">Date *</label>
                                <DatePicker
                                    v-model="criteria.date"
                                    :min-date="minDate"
                                    :max-date="maxDate"
                                    :disable-weekends="props.settings.block_weekends"
                                    placeholder="Sélectionner une date"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Début *</label>
                                <select
                                    v-model="criteria.heure_debut"
                                    required
                                    class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                                >
                                    <option value="">--:--</option>
                                    <option v-for="slot in availableTimeSlots" :key="slot" :value="slot">
                                        {{ slot }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Fin *</label>
                                <select
                                    v-model="criteria.heure_fin"
                                    required
                                    class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                                >
                                    <option value="">--:--</option>
                                    <option v-for="slot in availableTimeSlots" :key="slot" :value="slot">
                                        {{ slot }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Nombre de personnes -->
                        <div>
                            <label class="block text-sm font-medium mb-2">
                                Nombre de personnes
                                <span v-if="selectedRoom?.min_participants" class="text-orange-600 dark:text-orange-400 text-xs ml-2">
                                    ⚠️ Minimum {{ selectedRoom.min_participants }} participant(s) requis
                                </span>
                            </label>
                            <input
                                v-model.number="criteria.nombre_personnes"
                                type="number"
                                :min="selectedRoom?.min_participants || 1"
                                :max="selectedRoom?.capacite"
                                class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                                placeholder="Ex: 10"
                            />
                        </div>

                        <!-- Équipements -->
                        <div>
                            <label class="block text-sm font-medium mb-2">Équipements requis</label>
                            <div class="flex flex-wrap gap-2">
                                <label
                                    v-for="equip in allEquipments"
                                    :key="equip"
                                    class="flex items-center gap-2 px-3 py-2 border rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700"
                                    :class="{ 'bg-msl-primary/10 border-msl-primary': criteria.equipements.includes(equip) }"
                                >
                                    <input
                                        type="checkbox"
                                        :value="equip"
                                        v-model="criteria.equipements"
                                        class="rounded"
                                    />
                                    <span class="text-sm">{{ equip }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Message d'erreur -->
                        <div v-if="errors.general" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <p class="text-sm text-red-600 dark:text-red-400">{{ errors.general }}</p>
                        </div>

                        <!-- Bouton recherche -->
                        <button
                            @click="searchAvailableRooms"
                            :disabled="checkingAvailability"
                            class="w-full px-4 py-3 bg-msl-primary text-white rounded-lg hover:bg-msl-secondary transition disabled:opacity-50 font-medium shadow-msl-s cursor-pointer"
                        >
                            {{ checkingAvailability ? 'Recherche en cours...' : 'Rechercher les salles disponibles' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Étape 2: Sélection et confirmation -->
            <div v-if="step === 2" class="grid gap-4 lg:grid-cols-3">
                <!-- Liste des salles disponibles -->
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h2 class="text-lg font-semibold">
                                    Salles disponibles ({{ availableRooms.length }})
                                    <span v-if="form.room_id && !showRoomList" class="text-sm font-normal text-gray-600 dark:text-gray-400">
                                        - {{ selectedRoom?.nom }} sélectionnée
                                    </span>
                                </h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ new Date(criteria.date).toLocaleDateString('fr-FR') }} • 
                                    {{ criteria.heure_debut }} - {{ criteria.heure_fin }}
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <button
                                    v-if="form.room_id && !showRoomList"
                                    @click="showRoomList = true"
                                    class="px-3 py-1.5 text-sm bg-msl-primary text-white rounded hover:bg-msl-secondary transition shadow-msl-s cursor-pointer"
                                >
                                    Changer de salle
                                </button>
                                <button
                                    @click="backToSearch"
                                    class="px-3 py-1.5 text-sm bg-gray-200 dark:bg-gray-700 rounded hover:bg-gray-300 dark:hover:bg-gray-600 transition cursor-pointer"
                                >
                                    ← Modifier les critères
                                </button>
                            </div>
                        </div>

                        <!-- Liste des salles (repliable) -->
                        <div v-show="showRoomList" class="grid md:grid-cols-2 gap-3 mb-6">
                            <RoomCard
                                v-for="room in availableRooms"
                                :key="room.id"
                                :room="room"
                                :selected="form.room_id === room.id"
                                @select="selectRoom"
                            />
                        </div>

                        <!-- Carte de la salle sélectionnée (visible quand liste repliée) -->
                        <SelectedRoomCard v-if="selectedRoom && !showRoomList" :room="selectedRoom" class="mb-4" />

                        <!-- Formulaire complémentaire -->
                        <div v-if="form.room_id" class="space-y-4" :class="{ 'border-t pt-6': showRoomList }">
                            <h3 class="font-semibold">Détails de la réservation</h3>
                            
                            <!-- Titre, Description, Participants -->
                            <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-msl-s space-y-6">
                                <div>
                                    <label class="block text-sm font-medium mb-2">
                                        Titre <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.titre"
                                        type="text"
                                        required
                                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                                        :class="{ 'border-red-500': !form.titre }"
                                        placeholder="Ex: Réunion d'équipe"
                                    />
                                    <p v-if="!form.titre" class="mt-1 text-xs text-red-500">Le titre est obligatoire</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium mb-2">Description</label>
                                    <textarea
                                        v-model="form.description"
                                        rows="3"
                                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                                        placeholder="Détails de la réservation..."
                                    ></textarea>
                                </div>

                                <!-- Participants -->
                                <div>
                                    <label class="block text-sm font-medium mb-2">Participants</label>
                                    <UserSearch
                                        v-model="form.participants"
                                        :current-user-id="currentUserId"
                                        :max-participants="selectedRoom?.capacite"
                                    />
                                    <div v-if="selectedRoom?.min_participants" class="mt-2 text-xs">
                                        <span class="text-gray-600 dark:text-gray-400">
                                            {{ form.participants.length }} / {{ selectedRoom.min_participants }} participant(s) requis
                                        </span>
                                        <span v-if="form.participants.length < selectedRoom.min_participants" class="text-orange-600 dark:text-orange-400 ml-2">
                                            ⚠️ Ajoutez {{ selectedRoom.min_participants - form.participants.length }} personne(s) de plus
                                        </span>
                                        <span v-else class="text-green-600 dark:text-green-400 ml-2">
                                            ✓ Minimum atteint
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Notification par email -->
                            <div v-if="form.participants.length > 1" class="flex items-center gap-2 p-3 bg-msl-primary/10 border border-msl-primary/30 rounded-lg shadow-msl-s">
                                <input
                                    id="notify_participants"
                                    v-model="form.notify_participants"
                                    type="checkbox"
                                    class="w-4 h-4 text-msl-primary border-gray-300 rounded focus:ring-msl-primary"
                                />
                                <label for="notify_participants" class="text-sm font-medium cursor-pointer">
                                    Notifier les participants par email
                                </label>
                            </div>

                            <div v-if="Object.keys(errors).length > 0" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                                <p v-for="(error, key) in errors" :key="key" class="text-sm text-red-600 dark:text-red-400">
                                    {{ error }}
                                </p>
                            </div>

                            <div class="flex gap-3">
                                <button
                                    @click="submitForm"
                                    :disabled="submitting || !canSubmit"
                                    class="flex-1 px-4 py-2 bg-msl-primary text-white rounded-lg hover:bg-msl-secondary transition disabled:opacity-50 disabled:cursor-not-allowed font-medium shadow-msl-s cursor-pointer"
                                >
                                    {{ submitting ? 'Création...' : '✓ Confirmer la réservation' }}
                                </button>
                                <button
                                    @click="backToSearch"
                                    class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition cursor-pointer"
                                >
                                    Annuler
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Récapitulatif -->
                <div>
                    <div class="bg-msl-primary rounded-xl p-6 sticky top-4 text-white">
                        <h2 class="text-lg font-semibold mb-4">Récapitulatif</h2>
                        
                        <div class="space-y-3 text-sm">
                            <div>
                                <div class="font-medium text-white/80">Date</div>
                                <div class="font-semibold">{{ new Date(criteria.date).toLocaleDateString('fr-FR') }}</div>
                            </div>
                            <div>
                                <div class="font-medium text-white/80">Horaires</div>
                                <div class="font-semibold">{{ criteria.heure_debut }} - {{ criteria.heure_fin }}</div>
                            </div>
                            <div v-if="criteria.nombre_personnes">
                                <div class="font-medium text-white/80">Nombre de personnes</div>
                                <div class="font-semibold">{{ criteria.nombre_personnes }} personne(s)</div>
                            </div>
                            <div v-if="criteria.equipements.length > 0">
                                <div class="font-medium text-white/80 mb-1">Équipements</div>
                                <div class="space-y-1">
                                    <div v-for="eq in criteria.equipements" :key="eq" class="text-xs">
                                        ✓ {{ eq }}
                                    </div>
                                </div>
                            </div>
                            
                            <div v-if="selectedRoom" class="pt-3 border-t border-white/30">
                                <div class="font-medium text-white/80 mb-2">Salle sélectionnée</div>
                                <div class="font-semibold">{{ selectedRoom.nom }}</div>
                                <div class="text-xs text-white/80 mt-1">
                                    {{ selectedRoom.capacite }} pers. • Étage {{ selectedRoom.etage }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
