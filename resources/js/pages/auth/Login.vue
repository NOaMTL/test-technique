<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { store } from '@/routes/login';
import { Form, Head } from '@inertiajs/vue3';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();

const demoAccounts = [
    { email: 'admin@example.com', password: 'password', role: 'Administrateur' },
    { email: 'john@example.com', password: 'password', role: 'Utilisateur' },
    { email: 'jane@example.com', password: 'password', role: 'Utilisateur' }
];

const fillCredentials = (email: string, password: string) => {
    const emailInput = document.getElementById('email') as HTMLInputElement;
    const passwordInput = document.getElementById('password') as HTMLInputElement;
    
    if (emailInput && passwordInput) {
        emailInput.value = email;
        passwordInput.value = password;
        
        // Trigger Vue's reactivity
        emailInput.dispatchEvent(new Event('input', { bubbles: true }));
        passwordInput.dispatchEvent(new Event('input', { bubbles: true }));
    }
};
</script>

<template>
    <AuthBase
        title="Bienvenue"
        description="Connectez-vous pour gérer vos réservations et suivre vos demandes."
    >
        <Head title="Connexion" />

        <div
            v-if="status"
            class="mb-6 rounded-2xl border border-green-200/70 bg-green-50/90 px-4 py-3 text-center text-sm font-medium text-green-700 shadow-[var(--msl-boxShadow-s)] dark:border-green-500/40 dark:bg-green-500/15 dark:text-green-200"
        >
            {{ status }}
        </div>

        <Form v-bind="store.form()" :reset-on-success="['password']" v-slot="{ errors, processing }" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email">Adresse e-mail</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="exemple@domaine.com"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Mot de passe</Label>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="Votre mot de passe"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="flex items-center justify-between">
                    <Label for="remember" class="flex items-center space-x-3 text-sm text-gray-600 dark:text-gray-300">
                        <Checkbox id="remember" name="remember" :tabindex="3" />
                        <span>Se souvenir de moi</span>
                    </Label>
                </div>

                <Button type="submit" class="mt-4 w-full bg-msl-primary font-semibold text-white shadow-[var(--msl-boxShadow-s)] transition hover:bg-msl-secondary focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-white" :tabindex="4" :disabled="processing" data-test="login-button">
                    <Spinner v-if="processing" />
                    Se connecter
                </Button>
            </div>
        </Form>

        <div class="mt-8 rounded-2xl border border-gray-200/70 bg-gray-50/90 p-6 shadow-[var(--msl-boxShadow-s)] dark:border-gray-700/40 dark:bg-gray-800/50">
            <h3 class="mb-4 text-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                Comptes de démonstration
            </h3>
            <div class="space-y-3">
                <div 
                    v-for="account in demoAccounts" 
                    :key="account.email"
                    @click="fillCredentials(account.email, account.password)"
                    class="flex cursor-pointer items-center justify-between rounded-xl border border-gray-200/50 bg-white/80 p-3 transition hover:border-msl-primary/30 hover:bg-msl-primary/5 dark:border-gray-600/50 dark:bg-gray-700/50 dark:hover:border-msl-primary/40 dark:hover:bg-msl-primary/10"
                >
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ account.email }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ account.role }}</p>
                    </div>
                    <div class="text-xs font-mono text-gray-400 dark:text-gray-500">{{ account.password }}</div>
                </div>
            </div>
            <p class="mt-3 text-center text-xs text-gray-500 dark:text-gray-400">
                Cliquez sur un compte pour remplir automatiquement les champs
            </p>
        </div>
    </AuthBase>
</template>
