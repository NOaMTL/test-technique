import { VALIDATION_MESSAGES } from '@/constants';

/**
 * Validate email format
 */
export function isValidEmail(email: string): boolean {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Validate phone number (French format)
 */
export function isValidPhoneNumber(phone: string): boolean {
    const phoneRegex = /^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/;
    return phoneRegex.test(phone);
}

/**
 * Validate password strength
 */
export function validatePassword(password: string): {
    valid: boolean;
    errors: string[];
    strength: 'weak' | 'medium' | 'strong';
} {
    const errors: string[] = [];
    let strength: 'weak' | 'medium' | 'strong' = 'weak';

    if (password.length < 8) {
        errors.push(VALIDATION_MESSAGES.PASSWORD_MIN_LENGTH);
    }

    const hasUpperCase = /[A-Z]/.test(password);
    const hasLowerCase = /[a-z]/.test(password);
    const hasNumbers = /\d/.test(password);
    const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(password);

    const criteriaCount = [hasUpperCase, hasLowerCase, hasNumbers, hasSpecialChar]
        .filter(Boolean).length;

    if (criteriaCount < 2) {
        errors.push('Le mot de passe doit contenir au moins 2 types de caractères (majuscules, minuscules, chiffres, caractères spéciaux)');
    } else if (criteriaCount === 2) {
        strength = 'medium';
    } else if (criteriaCount >= 3) {
        strength = 'strong';
    }

    return {
        valid: errors.length === 0,
        errors,
        strength,
    };
}

/**
 * Validate date is not in the past
 */
export function isNotPast(date: string): boolean {
    const inputDate = new Date(date);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    inputDate.setHours(0, 0, 0, 0);
    
    return inputDate >= today;
}

/**
 * Validate time range (end time after start time)
 */
export function isValidTimeRange(startTime: string, endTime: string): boolean {
    const [startHours, startMinutes] = startTime.split(':').map(Number);
    const [endHours, endMinutes] = endTime.split(':').map(Number);
    
    const startTotal = startHours * 60 + startMinutes;
    const endTotal = endHours * 60 + endMinutes;
    
    return endTotal > startTotal;
}

/**
 * Validate file type
 */
export function isValidFileType(file: File, allowedTypes: string[]): boolean {
    return allowedTypes.includes(file.type);
}

/**
 * Validate file size
 */
export function isValidFileSize(file: File, maxSizeMB: number): boolean {
    const maxSizeBytes = maxSizeMB * 1024 * 1024;
    return file.size <= maxSizeBytes;
}

/**
 * Validate URL format
 */
export function isValidUrl(url: string): boolean {
    try {
        new URL(url);
        return true;
    } catch {
        return false;
    }
}

/**
 * Validate required field
 */
export function isRequired(value: any): boolean {
    if (value === null || value === undefined) return false;
    if (typeof value === 'string') return value.trim().length > 0;
    if (Array.isArray(value)) return value.length > 0;
    return true;
}

/**
 * Validate number range
 */
export function isInRange(value: number, min: number, max: number): boolean {
    return value >= min && value <= max;
}

/**
 * Validate string length
 */
export function isValidLength(str: string, min: number, max?: number): boolean {
    if (str.length < min) return false;
    if (max && str.length > max) return false;
    return true;
}

/**
 * Generic form validator
 */
export interface ValidationRule {
    required?: boolean;
    email?: boolean;
    phone?: boolean;
    minLength?: number;
    maxLength?: number;
    min?: number;
    max?: number;
    pattern?: RegExp;
    custom?: (value: any) => boolean | string;
}

export interface ValidationResult {
    valid: boolean;
    errors: Record<string, string[]>;
}

export function validateForm(
    data: Record<string, any>,
    rules: Record<string, ValidationRule>
): ValidationResult {
    const errors: Record<string, string[]> = {};

    Object.entries(rules).forEach(([field, rule]) => {
        const value = data[field];
        const fieldErrors: string[] = [];

        // Required validation
        if (rule.required && !isRequired(value)) {
            fieldErrors.push(VALIDATION_MESSAGES.REQUIRED);
        }

        // Skip other validations if value is empty and not required
        if (!isRequired(value) && !rule.required) return;

        // Email validation
        if (rule.email && typeof value === 'string' && !isValidEmail(value)) {
            fieldErrors.push(VALIDATION_MESSAGES.EMAIL_INVALID);
        }

        // Phone validation
        if (rule.phone && typeof value === 'string' && !isValidPhoneNumber(value)) {
            fieldErrors.push('Le numéro de téléphone est invalide');
        }

        // String length validation
        if (typeof value === 'string') {
            if (rule.minLength && value.length < rule.minLength) {
                fieldErrors.push(`Minimum ${rule.minLength} caractères requis`);
            }
            if (rule.maxLength && value.length > rule.maxLength) {
                fieldErrors.push(`Maximum ${rule.maxLength} caractères autorisés`);
            }
        }

        // Number range validation
        if (typeof value === 'number') {
            if (rule.min !== undefined && value < rule.min) {
                fieldErrors.push(`La valeur minimum est ${rule.min}`);
            }
            if (rule.max !== undefined && value > rule.max) {
                fieldErrors.push(`La valeur maximum est ${rule.max}`);
            }
        }

        // Pattern validation
        if (rule.pattern && typeof value === 'string' && !rule.pattern.test(value)) {
            fieldErrors.push('Format invalide');
        }

        // Custom validation
        if (rule.custom) {
            const result = rule.custom(value);
            if (result === false) {
                fieldErrors.push('Validation personnalisée échouée');
            } else if (typeof result === 'string') {
                fieldErrors.push(result);
            }
        }

        if (fieldErrors.length > 0) {
            errors[field] = fieldErrors;
        }
    });

    return {
        valid: Object.keys(errors).length === 0,
        errors,
    };
}
