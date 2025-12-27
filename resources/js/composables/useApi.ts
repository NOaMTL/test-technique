/**
 * API Service Composables
 * Reusable composables for API interactions
 */

import { router } from '@inertiajs/vue3';

/**
 * Generic API request helper
 */
export function useApi() {
    const handleError = (error: unknown): void => {
        console.error('API Error:', error);
    };

    return {
        handleError,
    };
}

/**
 * Inertia navigation helpers with proper typing
 */
export function useInertiaNavigation() {
    const visit = (url: string, options?: Parameters<typeof router.visit>[1]) => {
        return router.visit(url, options);
    };

    const reload = (options?: Parameters<typeof router.reload>[0]) => {
        return router.reload(options);
    };

    const post = (
        url: string,
        data?: Record<string, any> | FormData,
        options?: Parameters<typeof router.post>[2]
    ) => {
        return router.post(url, data as any, options);
    };

    const put = (
        url: string,
        data?: Record<string, any> | FormData,
        options?: Parameters<typeof router.put>[2]
    ) => {
        return router.put(url, data as any, options);
    };

    const patch = (
        url: string,
        data?: Record<string, any> | FormData,
        options?: Parameters<typeof router.patch>[2]
    ) => {
        return router.patch(url, data as any, options);
    };

    const destroy = (
        url: string,
        options?: Parameters<typeof router.delete>[1]
    ) => {
        return router.delete(url, options);
    };

    return {
        visit,
        reload,
        post,
        put,
        patch,
        delete: destroy,
    };
}
