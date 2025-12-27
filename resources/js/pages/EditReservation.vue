<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import UserSearch from '@/components/UserSearch.vue';
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

const props = defineProps<{
    reservationId: number | string;
    settings: AppSettings;
}>();

const page = usePage();
const isAdmin = computed(() => page.props.auth?.user?.role === 'admin');
const currentUserId = computed(() => page.props.auth?.user?.id as number);

// Convertir en number si c'est une string
const reservationIdNum = computed(() => 
    typeof props.reservationId === 'string' ? parseInt(props.reservationId) : props.reservationId
);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: 'Tableau de bord',
        href: '/dashboard',
    },
    {
        title: isAdmin.value ? 'Réservations' : 'Mes réservations',
        href: isAdmin.value ? '/admin/reservations' : '/my-reservations',
    },
    {
        title: 'Modifier',
        href: `/reservations/${reservationIdNum.value}/edit`,
    },
]);

interface Room {
    id: number;
    nom: string;
    capacite: number;
    etage: number;
    equipement: string[];
    description: string;
    min_participants?: number;
}

const allRooms = ref<Room[]>([]);
const availableRooms = ref<Room[]>([]);
const loading = ref(true);
const submitting = ref(false);
const checkingAvailability = ref(false);
const errors = ref<Record<string, string>>({});
const availabilityChecked = ref(false);
const showRoomList = ref(true); // Afficher ou masquer la liste des salles

const form = ref({
    room_id: null as number | null,
    date: '',
    heure_debut: '',
    heure_fin: '',
    titre: '',
    description: '',
    nombre_personnes: null as number | null, // Nombre de personnes prévues
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
    const isToday = form.value.date === today;
    
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

const canCheckAvailability = computed(() => {
    return form.value.date && form.value.heure_debut && form.value.heure_fin;
});

onMounted(async () => {
    try {
        const [roomsRes, reservationRes] = await Promise.all([
            axios.get('/api/rooms'),
            axios.get(`/api/reservations/${reservationIdNum.value}`)
        ]);
        
        allRooms.value = roomsRes.data;
        
        // Charger les données de la réservation
        const reservation = reservationRes.data;
        
        // Formater la date au format YYYY-MM-DD pour le date picker
        let dateFormatted = reservation.date;
        if (dateFormatted) {
            // Extraire uniquement la partie date (YYYY-MM-DD)
            if (dateFormatted.includes('T')) {
                // Format ISO: 2025-12-20T00:00:00.000000Z
                dateFormatted = dateFormatted.split('T')[0];
            } else if (dateFormatted.includes('/')) {
                // Convertir de DD/MM/YYYY vers YYYY-MM-DD
                const parts = dateFormatted.split('/');
                if (parts.length === 3) {
                    dateFormatted = `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
                }
            }
        }
        
        form.value = {
            room_id: reservation.room_id,
            date: dateFormatted,
            heure_debut: reservation.heure_debut?.substring(0, 5) || '', // Format HH:mm
            heure_fin: reservation.heure_fin?.substring(0, 5) || '', // Format HH:mm
            titre: reservation.titre || '',
            description: reservation.description || '',
            nombre_personnes: reservation.nombre_personnes || null,
            participants: reservation.participants.map((p: any) => p.id),
            notify_participants: false,
        };

        // Vérifier automatiquement la disponibilité au chargement
        if (canCheckAvailability.value) {
            await checkAvailability();
        }
    } catch (error) {
        console.error('Erreur lors du chargement:', error);
        toast.error('Impossible de charger la réservation');
        router.visit('/my-reservations');
    } finally {
        loading.value = false;
    }
});

// Surveiller les changements de date/horaires/nombre pour re-vérifier la disponibilité
watch([() => form.value.date, () => form.value.heure_debut, () => form.value.heure_fin, () => form.value.nombre_personnes], async () => {
    if (canCheckAvailability.value) {
        await checkAvailability();
        showRoomList.value = true; // Réafficher la liste quand on change les critères
    }
});

const checkAvailability = async () => {
    if (!canCheckAvailability.value) return;

    checkingAvailability.value = true;
    errors.value = {};
    availabilityChecked.value = false;

    try {
        const response = await axios.post('/api/rooms/available', {
            date: form.value.date,
            heure_debut: form.value.heure_debut,
            heure_fin: form.value.heure_fin,
            capacite_min: form.value.nombre_personnes, // Utiliser le nombre de personnes comme capacité min
            equipements: [],
            exclude_reservation_id: reservationIdNum.value, // Exclure cette réservation de la vérification
        });

        availableRooms.value = response.data;
        availabilityChecked.value = true;

        // Vérifier si la salle actuelle est toujours disponible
        if (form.value.room_id) {
            const currentRoomAvailable = availableRooms.value.some(r => r.id === form.value.room_id);
            if (!currentRoomAvailable) {
                errors.value.room_id = 'Cette salle n\'est plus disponible pour ces horaires. Veuillez en choisir une autre.';
            }
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
        }
    } finally {
        checkingAvailability.value = false;
    }
};

const submitForm = async () => {
    // Vérifier d'abord la disponibilité
    if (!availabilityChecked.value) {
        await checkAvailability();
    }

    if (errors.value.room_id) {
        toast.error('Veuillez sélectionner une salle disponible');
        return;
    }

    errors.value = {};
    submitting.value = true;

    try {
        await axios.put(`/api/reservations/${reservationIdNum.value}`, form.value);
        toast.success('Réservation modifiée avec succès');
        setTimeout(() => {
            router.visit(isAdmin.value ? '/admin/reservations' : '/my-reservations');
        }, 1500);
    } catch (error: any) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
            // Aplatir les erreurs Laravel
            Object.keys(errors.value).forEach(key => {
                if (Array.isArray(errors.value[key])) {
                    errors.value[key] = errors.value[key][0];
                }
                toast.error(errors.value[key]);
            });
        } else {
            toast.error('Une erreur est survenue lors de la modification de la réservation');
        }
    } finally {
        submitting.value = false;
    }
};
</script>

<template>
    <Head title="Modifier la réservation" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- En-tête -->
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Modifier la réservation</h1>
                <a
                    :href="isAdmin ? '/admin/reservations' : '/my-reservations'"
                    class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition"
                >
                    Annuler
                </a>
            </div>

            <div v-if="loading" class="text-center py-8">Chargement...</div>

            <div v-else class="grid gap-4 lg:grid-cols-3 py-6">
                <!-- Formulaire -->
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-gray-800 rounded-xl px-6">
                        <form @submit.prevent="submitForm" class="space-y-4">
                            <!-- Date et horaires en premier -->
                            <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-msl-s">
                                <h3 class="font-semibold mb-3 flex items-center gap-2">
                                    Date et horaires
                                    <span v-if="checkingAvailability" class="text-sm font-normal text-msl-primary">
                                        Vérification...
                                    </span>
                                </h3>

                                <!-- Date -->
                                <div class="mb-3">
                                    <label class="block text-sm font-medium mb-2">Date *</label>
                                    <DatePicker
                                        v-model="form.date"
                                        :min-date="minDate"
                                        :max-date="maxDate"
                                        :disable-weekends="props.settings.block_weekends"
                                        placeholder="Sélectionner une date"
                                    />
                                    <p v-if="errors.date" class="mt-1 text-sm text-red-500">{{ errors.date }}</p>
                                </div>

                                <!-- Horaires -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium mb-2">Heure de début *</label>
                                        <select
                                            v-model="form.heure_debut"
                                            required
                                            class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                                            :class="{ 'border-red-500': errors.heure_debut }"
                                        >
                                            <option value="">--:--</option>
                                            <option v-for="slot in availableTimeSlots" :key="slot" :value="slot">
                                                {{ slot }}
                                            </option>
                                        </select>
                                        <p v-if="errors.heure_debut" class="mt-1 text-sm text-red-500">{{ errors.heure_debut }}</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium mb-2">Heure de fin *</label>
                                        <select
                                            v-model="form.heure_fin"
                                            required
                                            class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                                            :class="{ 'border-red-500': errors.heure_fin }"
                                        >
                                            <option value="">--:--</option>
                                            <option v-for="slot in availableTimeSlots" :key="slot" :value="slot">
                                                {{ slot }}
                                            </option>
                                        </select>
                                        <p v-if="errors.heure_fin" class="mt-1 text-sm text-red-500">{{ errors.heure_fin }}</p>
                                    </div>
                                </div>

                                <!-- Nombre de personnes -->
                                <div class="mb-3">
                                    <label class="block text-sm font-medium mb-2">
                                        Nombre de personnes
                                        <span v-if="selectedRoom?.min_participants" class="text-orange-600 dark:text-orange-400 text-xs ml-2">
                                            ⚠️ Minimum {{ selectedRoom.min_participants }} participant(s) requis
                                        </span>
                                    </label>
                                    <input
                                        v-model.number="form.nombre_personnes"
                                        type="number"
                                        :min="selectedRoom?.min_participants || 1"
                                        :max="selectedRoom?.capacite"
                                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600"
                                        :class="{ 'border-red-500': errors.nombre_personnes }"
                                        placeholder="Ex: 10"
                                    />
                                    <p v-if="errors.nombre_personnes" class="mt-1 text-sm text-red-500">{{ errors.nombre_personnes }}</p>
                                </div>

                                <!-- Statut de disponibilité -->
                                <div v-if="availabilityChecked && !checkingAvailability" class="mt-3">
                                    <div v-if="availableRooms.length > 0" class="p-2 bg-green-50 dark:bg-green-900/20 border border-green-300 dark:border-green-700 rounded text-sm">
                                        ✓ {{ availableRooms.length }} salle(s) disponible(s) pour ces horaires
                                    </div>
                                    <div v-else class="p-2 bg-red-50 dark:bg-red-900/20 border border-red-300 dark:border-red-700 rounded text-sm">
                                        ❌ Aucune salle disponible pour ces horaires
                                    </div>
                                </div>
                            </div>

                            <!-- Sélection de la salle avec cards -->
                            <div class="mt-10">
                                <label class="block text-sm font-medium mb-3">
                                    Salle *
                                    <span v-if="availableRooms.length > 0" class="text-xs text-gray-500">
                                        ({{ availableRooms.length }} disponible(s))
                                    </span>
                                </label>
                                
                                <div v-if="!availabilityChecked" class="p-3 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded text-sm text-gray-600 dark:text-gray-400">
                                    Renseignez d'abord la date et les horaires
                                </div>
                                
                                <div v-else-if="availableRooms.length === 0" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-300 dark:border-red-700 rounded text-sm text-red-600 dark:text-red-400">
                                    Aucune salle disponible pour ces horaires
                                </div>
                                
                                <div v-else>
                                    <!-- Bouton pour changer de salle -->
                                    <div v-if="form.room_id && !showRoomList" class="mb-3">
                                        <button
                                            type="button"
                                            @click="showRoomList = true"
                                            class="px-3 py-1.5 text-sm bg-msl-primary text-white rounded hover:bg-msl-secondary transition shadow-msl-s cursor-pointer"
                                        >
                                            Changer de salle
                                        </button>
                                    </div>

                                    <!-- Liste des salles (repliable) -->
                                    <div v-show="showRoomList" class="grid md:grid-cols-2 gap-3">
                                        <RoomCard
                                            v-for="room in availableRooms"
                                            :key="room.id"
                                            :room="room"
                                            :selected="form.room_id === room.id"
                                            @select="(roomId) => { form.room_id = roomId; showRoomList = false }"
                                        />
                                    </div>

                                    <!-- Carte de la salle sélectionnée (visible quand liste repliée) -->
                                    <SelectedRoomCard v-if="selectedRoom && !showRoomList" :room="selectedRoom" />
                                </div>
                                <p v-if="errors.room_id" class="mt-2 text-sm text-red-500">{{ errors.room_id }}</p>
                            </div>

                            <!-- Titre, Description, Participants -->
                            <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-msl-s space-y-6">
                                <!-- Titre -->
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

                                <!-- Description -->
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
                            <div v-if="form.participants.length > 1" class="flex items-center gap-2 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                                <input
                                    id="notify_participants"
                                    v-model="form.notify_participants"
                                    type="checkbox"
                                    class="w-4 h-4 text-[#007461] border-gray-300 rounded focus:ring-[#007461]"
                                />
                                <label for="notify_participants" class="text-sm font-medium cursor-pointer">
                                    Notifier les participants par email des modifications
                                </label>
                            </div>

                            <!-- Boutons -->
                            <div class="flex gap-3 pt-4">
                                <button
                                    type="submit"
                                    :disabled="submitting || !canSubmit"
                                    class="flex-1 px-4 py-2 bg-msl-primary text-white rounded-lg hover:bg-msl-secondary transition disabled:opacity-50 disabled:cursor-not-allowed font-medium shadow-msl-s cursor-pointer"
                                >
                                    {{ submitting ? 'Modification...' : '✓ Enregistrer les modifications' }}
                                </button>
                                <a
                                    :href="isAdmin ? '/admin/reservations' : '/my-reservations'"
                                    class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition text-center"
                                >
                                    Annuler
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Récapitulatif -->
                <div>
                    <div class="bg-msl-primary rounded-xl p-6 sticky top-4 text-white">
                        <h2 class="text-lg font-semibold mb-4">Récapitulatif</h2>
                        
                        <div class="space-y-3 text-sm">
                            <div v-if="form.date">
                                <div class="font-medium text-white/80">Date</div>
                                <div class="font-semibold">{{ new Date(form.date).toLocaleDateString('fr-FR') }}</div>
                            </div>
                            <div v-if="form.heure_debut && form.heure_fin">
                                <div class="font-medium text-white/80">Horaires</div>
                                <div class="font-semibold">{{ form.heure_debut }} - {{ form.heure_fin }}</div>
                            </div>
                            <div v-if="form.nombre_personnes">
                                <div class="font-medium text-white/80">Nombre de personnes</div>
                                <div class="font-semibold">{{ form.nombre_personnes }} personne(s)</div>
                            </div>
                            
                            <div v-if="selectedRoom" class="pt-3 border-t border-white/30">
                                <div class="font-medium text-white/80 mb-2">Salle sélectionnée</div>
                                <div class="font-semibold">{{ selectedRoom.nom }}</div>
                                <div class="text-xs text-white/80 mt-1">
                                    {{ selectedRoom.capacite }} pers. • Étage {{ selectedRoom.etage }}
                                </div>
                                
                                <div v-if="selectedRoom.equipement.length > 0" class="mt-2">
                                    <div class="font-medium text-white/80 mb-1">Équipements</div>
                                    <div class="space-y-1">
                                        <div v-for="(eq, idx) in selectedRoom.equipement" :key="idx" class="text-xs">
                                            ✓ {{ eq }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div v-else class="text-white/60 text-sm pt-3 border-t border-white/30">
                                Sélectionnez une salle pour voir ses détails
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
