import { onMounted, onUnmounted } from 'vue';

export interface KeyboardShortcut {
    key: string;
    ctrl?: boolean;
    shift?: boolean;
    alt?: boolean;
    description: string;
    handler: () => void;
}

export function useKeyboardShortcuts(shortcuts: KeyboardShortcut[]) {
    const handleKeyDown = (event: KeyboardEvent) => {
        // Ignorer si on est dans un input, textarea ou contenteditable
        const target = event.target as HTMLElement;
        const tagName = target.tagName;
        const isEditable = target.isContentEditable;
        
        if (tagName === 'INPUT' || tagName === 'TEXTAREA' || isEditable) {
            return;
        }

        for (const shortcut of shortcuts) {
            const ctrlMatch = shortcut.ctrl ? (event.ctrlKey || event.metaKey) : (!event.ctrlKey && !event.metaKey);
            const shiftMatch = shortcut.shift ? event.shiftKey : !event.shiftKey;
            const altMatch = shortcut.alt ? event.altKey : !event.altKey;
            const keyMatch = event.key.toLowerCase() === shortcut.key.toLowerCase();

            if (ctrlMatch && shiftMatch && altMatch && keyMatch) {
                // Empêcher IMMÉDIATEMENT l'action par défaut du navigateur
                event.preventDefault();
                event.stopImmediatePropagation();
                
                // Exécuter le handler
                shortcut.handler();
                return;
            }
        }
    };

    onMounted(() => {
        // Utiliser capture phase ET passive false pour pouvoir bloquer
        document.addEventListener('keydown', handleKeyDown, { capture: true, passive: false });
    });

    onUnmounted(() => {
        document.removeEventListener('keydown', handleKeyDown, { capture: true });
    });

    return {
        shortcuts,
    };
}
