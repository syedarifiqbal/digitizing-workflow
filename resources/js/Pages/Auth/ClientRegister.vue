<script setup>
import { useForm, Link, Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import Button from '@/Components/Button.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <PublicLayout>
        <Head title="Create Account" />
        <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-slate-50">
            <div class="max-w-md w-full space-y-8">
                <div>
                    <h2 class="mt-6 text-center text-3xl font-extrabold text-slate-900">
                        Create your account
                    </h2>
                    <p class="mt-2 text-center text-sm text-slate-600">
                        Already have an account?
                        <Link :href="route('login')" class="font-medium text-indigo-600 hover:text-indigo-500">
                            Sign in
                        </Link>
                    </p>
                </div>

                <form @submit.prevent="submit" class="mt-8 space-y-6 bg-white p-8 rounded-lg shadow">
                    <div class="space-y-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-slate-700">Your Name</label>
                            <input
                                v-model="form.name"
                                id="name"
                                type="text"
                                required
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                        </div>

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

                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirm Password</label>
                            <input
                                v-model="form.password_confirmation"
                                id="password_confirmation"
                                type="password"
                                required
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

                    <Button
                        as="button"
                        html-type="submit"
                        variant="primary"
                        :disabled="form.processing"
                        extra-class="w-full justify-center"
                    >
                        Create Account
                    </Button>
                </form>
            </div>
        </div>
    </PublicLayout>
</template>
