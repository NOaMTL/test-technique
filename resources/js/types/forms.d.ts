/**
 * Form Data Transfer Objects
 * Type-safe form data structures
 */

import type { RoomConstraints } from './models';

/**
 * Reservation Form Data
 */
export interface ReservationFormData {
    room_id: number;
    start_time: string;
    end_time: string;
    participants: number;
    purpose: string;
}

/**
 * Room Form Data
 */
export interface RoomFormData {
    name: string;
    description: string;
    floor: number;
    capacity: number;
    equipment: string[];
    is_available: boolean;
    constraints: RoomConstraints | null;
    images?: File[];
}

/**
 * User Profile Form Data
 */
export interface ProfileFormData {
    name: string;
    email: string;
    avatar?: File;
}

/**
 * Password Update Form Data
 */
export interface PasswordUpdateFormData {
    current_password: string;
    password: string;
    password_confirmation: string;
}

/**
 * Login Form Data
 */
export interface LoginFormData {
    email: string;
    password: string;
    remember: boolean;
}

/**
 * Register Form Data
 */
export interface RegisterFormData {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
}

/**
 * Filter/Search Parameters
 */
export interface ReservationFilters {
    room_id?: number;
    start_date?: string;
    end_date?: string;
    status?: 'pending' | 'confirmed' | 'cancelled';
    search?: string;
    per_page?: number;
    page?: number;
}

export interface RoomFilters {
    floor?: number;
    min_capacity?: number;
    is_available?: boolean;
    search?: string;
    per_page?: number;
    page?: number;
}
