<script setup>
import { useForm, Link, Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import Button from '@/Components/Button.vue';

const props = defineProps({
    tenantName: String,
    registrationEnabled: {
        type: Boolean,
        default: true,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <PublicLayout>
        <Head title="Login" />
        <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-slate-50">
            <div class="max-w-md w-full space-y-8">
                <div>
                    <h2 v-if="tenantName" class="text-center text-2xl font-bold text-indigo-600">
                        {{ tenantName }}
                    </h2>
                    <h2 class="mt-6 text-center text-3xl font-extrabold text-slate-900">
                        Sign in to your account
                    </h2>
                    <p v-if="registrationEnabled" class="mt-2 text-center text-sm text-slate-600">
                        Don't have an account?
                        <Link :href="route('register')" class="font-medium text-indigo-600 hover:text-indigo-500">
                            Register
                        </Link>
                    </p>
                </div>

                <form @submit.prevent="submit" class="mt-8 space-y-6 bg-white p-8 rounded-lg shadow">
                    <div class="space-y-4">
                        <div>
                            <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                            <input
                                v-model="form.email"
                                id="email"
                                type="email"
                                required
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                            <input
                                v-model="form.password"
                                id="password"
                                type="password"
                                required
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <input
                                    v-model="form.remember"
                                    id="remember"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <label for="remember" class="ml-2 block text-sm text-slate-700">
                                    Remember me
                                </label>
                            </div>

                            <Link :href="route('password.request')" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                                Forgot password?
                            </Link>
                        </div>
                    </div>

                    <Button
                        as="button"
                        html-type="submit"
                        variant="primary"
                        :disabled="form.processing"
                        extra-class="w-full justify-center"
                    >
                        Sign in
                    </Button>
                </form>
            </div>
        </div>
    </PublicLayout>
</template>
