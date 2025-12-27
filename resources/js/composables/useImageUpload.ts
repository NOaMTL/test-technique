import { ref } from 'vue';

export interface ImageUploadOptions {
    maxSize?: number; // in MB
    maxFiles?: number;
    acceptedFormats?: string[];
}

export function useImageUpload(options: ImageUploadOptions = {}) {
    const {
        maxSize = 2, // 2MB default
        maxFiles = 10,
        acceptedFormats = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'],
    } = options;

    const files = ref<File[]>([]);
    const previews = ref<string[]>([]);
    const errors = ref<string[]>([]);
    const uploading = ref(false);

    /**
     * Validate a single file
     */
    const validateFile = (file: File): { valid: boolean; error?: string } => {
        // Check file type
        if (!acceptedFormats.includes(file.type)) {
            return {
                valid: false,
                error: `Format non accepté: ${file.name}. Formats acceptés: ${acceptedFormats.join(', ')}`,
            };
        }

        // Check file size
        const fileSizeMB = file.size / (1024 * 1024);
        if (fileSizeMB > maxSize) {
            return {
                valid: false,
                error: `Fichier trop volumineux: ${file.name} (${fileSizeMB.toFixed(2)}MB). Max: ${maxSize}MB`,
            };
        }

        return { valid: true };
    };

    /**
     * Add files to upload queue
     */
    const addFiles = (newFiles: FileList | File[]) => {
        errors.value = [];
        const fileArray = Array.from(newFiles);

        // Check total count
        if (files.value.length + fileArray.length > maxFiles) {
            errors.value.push(`Maximum ${maxFiles} fichiers autorisés`);
            return;
        }

        fileArray.forEach(file => {
            const validation = validateFile(file);
            
            if (validation.valid) {
                files.value.push(file);
                
                // Create preview
                const reader = new FileReader();
                reader.onload = (e) => {
                    if (e.target?.result) {
                        previews.value.push(e.target.result as string);
                    }
                };
                reader.readAsDataURL(file);
            } else if (validation.error) {
                errors.value.push(validation.error);
            }
        });
    };

    /**
     * Remove a file from queue
     */
    const removeFile = (index: number) => {
        files.value.splice(index, 1);
        previews.value.splice(index, 1);
    };

    /**
     * Clear all files
     */
    const clearFiles = () => {
        files.value = [];
        previews.value = [];
        errors.value = [];
    };

    /**
     * Reorder files (for drag & drop)
     */
    const reorderFiles = (oldIndex: number, newIndex: number) => {
        const fileToMove = files.value.splice(oldIndex, 1)[0];
        const previewToMove = previews.value.splice(oldIndex, 1)[0];
        
        files.value.splice(newIndex, 0, fileToMove);
        previews.value.splice(newIndex, 0, previewToMove);
    };

    /**
     * Get FormData for upload
     */
    const getFormData = (fieldName: string = 'images'): FormData => {
        const formData = new FormData();
        files.value.forEach((file, index) => {
            formData.append(`${fieldName}[${index}]`, file);
        });
        return formData;
    };

    /**
     * Delete existing image (API call)
     */
    const deleteExistingImage = async (
        roomId: number,
        imageId: number
    ): Promise<boolean> => {
        try {
            const response = await fetch(`/api/admin/rooms/${roomId}/images/${imageId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (!response.ok) {
                throw new Error('Erreur lors de la suppression');
            }

            return true;
        } catch (e) {
            errors.value.push(
                e instanceof Error ? e.message : 'Erreur lors de la suppression de l\'image'
            );
            return false;
        }
    };

    /**
     * Update images order (API call)
     */
    const updateImagesOrder = async (
        roomId: number,
        imageIds: number[]
    ): Promise<boolean> => {
        try {
            const response = await fetch(`/api/admin/rooms/${roomId}/images-order`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ existing_images_order: imageIds }),
            });

            if (!response.ok) {
                throw new Error('Erreur lors de la mise à jour de l\'ordre');
            }

            return true;
        } catch (e) {
            errors.value.push(
                e instanceof Error ? e.message : 'Erreur lors de la mise à jour de l\'ordre'
            );
            return false;
        }
    };

    return {
        files,
        previews,
        errors,
        uploading,
        addFiles,
        removeFile,
        clearFiles,
        reorderFiles,
        getFormData,
        deleteExistingImage,
        updateImagesOrder,
    };
}
