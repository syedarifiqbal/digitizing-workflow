<script setup>
import { useForm, Link, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const form = useForm({
    email: '',
});

const page = usePage();

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <PublicLayout>
        <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-gray-50">
            <div class="max-w-md w-full space-y-8">
                <div>
                    <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                        Reset your password
                    </h2>
                    <p class="mt-2 text-center text-sm text-gray-600">
                        Enter your email and we'll send you a reset link.
                    </p>
                </div>

                <div v-if="page.props.flash?.status" class="bg-green-50 border border-green-200 rounded-md p-4">
                    <p class="text-sm text-green-700">{{ page.props.flash.status }}</p>
                </div>

                <form @submit.prevent="submit" class="mt-8 space-y-6 bg-white p-8 rounded-lg shadow">
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

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                    >
                        Send Reset Link
                    </button>

                    <div class="text-center">
                        <Link :href="route('login')" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                            Back to login
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </PublicLayout>
</template>
