/**
 * Domain Models Type Definitions
 * Centralized type definitions for all domain entities
 */

/**
 * User Model
 */
export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    role?: 'admin' | 'user';
    created_at: string;
    updated_at: string;
}

/**
 * Room Model
 */
export interface Room {
    id: number;
    name: string;
    nom?: string; // French alias
    description: string;
    floor: number;
    etage?: number; // French alias
    capacity: number;
    capacite?: number; // French alias
    equipment: string[] | null;
    equipement?: string[] | null; // French alias
    is_available: boolean;
    constraints: RoomConstraints | null;
    images?: RoomImage[];
    created_at: string;
    updated_at: string;
}

/**
 * Room Image Model
 */
export interface RoomImage {
    id: number;
    room_id: number;
    path: string;
    is_primary: boolean;
    created_at: string;
    updated_at: string;
}

/**
 * Room Constraints
 */
export interface RoomConstraints {
    time_period?: 'morning' | 'afternoon' | 'full_day' | null;
    days_allowed?: number[]; // 1-7 (Monday-Sunday)
    advance_booking_days?: number;
    weekly_hours_quota?: number;
    daily_booking_limit?: number;
    min_participants?: number;
}

/**
 * Reservation Model
 */
export interface Reservation {
    id: number;
    user_id: number;
    room_id: number;
    start_time: string;
    end_time: string;
    date?: string; // French format alias
    heure_debut?: string; // French alias
    heure_fin?: string; // French alias
    participants: number;
    nombre_personnes?: number; // French alias
    purpose: string;
    titre?: string | null; // French alias
    description?: string | null;
    status: 'pending' | 'confirmed' | 'cancelled';
    user?: User;
    room?: Room;
    participants_list?: User[]; // Array of participant users
    created_at: string;
    updated_at: string;
}

/**
 * Setting Model
 */
export interface Setting {
    id: number;
    key: string;
    value: string | number | boolean | object;
    created_at: string;
    updated_at: string;
}

/**
 * Pagination Meta
 */
export interface PaginationMeta {
    current_page: number;
    from: number | null;
    last_page: number;
    per_page: number;
    to: number | null;
    total: number;
}

/**
 * Pagination Links
 */
export interface PaginationLinks {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
}

/**
 * Paginated Response
 */
export interface PaginatedResponse<T> {
    data: T[];
    meta: PaginationMeta;
    links: PaginationLinks;
}

/**
 * API Response
 */
export interface ApiResponse<T = unknown> {
    success: boolean;
    message?: string;
    data?: T;
    errors?: Record<string, string[]>;
}
