<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const form = useForm({
    company: '',
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
        <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-gray-50">
            <div class="max-w-md w-full space-y-8">
                <div>
                    <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                        Create your account
                    </h2>
                    <p class="mt-2 text-center text-sm text-gray-600">
                        Already have an account?
                        <Link :href="route('login')" class="font-medium text-indigo-600 hover:text-indigo-500">
                            Sign in
                        </Link>
                    </p>
                </div>

                <form @submit.prevent="submit" class="mt-8 space-y-6 bg-white p-8 rounded-lg shadow">
                    <div class="space-y-4">
                        <div>
                            <label for="company" class="block text-sm font-medium text-gray-700">Company Name</label>
                            <input
                                v-model="form.company"
                                id="company"
                                type="text"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <p v-if="form.errors.company" class="mt-1 text-sm text-red-600">{{ form.errors.company }}</p>
                        </div>

                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Your Name</label>
                            <input
                                v-model="form.name"
                                id="name"
                                type="text"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                            <input
                                v-model="form.email"
                                id="email"
                                type="email"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                            <input
                                v-model="form.password"
                                id="password"
                                type="password"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                            <input
                                v-model="form.password_confirmation"
                                id="password_confirmation"
                                type="password"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                    >
                        Create Account
                    </button>
                </form>
            </div>
        </div>
    </PublicLayout>
</template>
