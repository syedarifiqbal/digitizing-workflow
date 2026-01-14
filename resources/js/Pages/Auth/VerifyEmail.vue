<script setup>
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const resendForm = useForm({});
const logoutForm = useForm({});
const page = usePage();

const status = computed(() => page.props.flash?.status);

const resend = () => {
    resendForm.post(route('verification.send'));
};

const logout = () => {
    logoutForm.post(route('logout'));
};
</script>

<template>
    <AppLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Verify Your Email</h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 space-y-6">
                        <p class="text-gray-700">
                            Thanks for signing up! Before continuing, please verify your email address by clicking the link
                            we just sent you. If you didn't receive the email, you can send another one below.
                        </p>

                        <div
                            v-if="status === 'verification-link-sent'"
                            class="rounded-md bg-green-50 p-4 text-sm text-green-700"
                        >
                            A new verification link has been sent to your email address.
                        </div>

                        <div class="flex flex-wrap gap-4">
                            <button
                                type="button"
                                @click="resend"
                                :disabled="resendForm.processing"
                                class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
                            >
                                Resend verification email
                            </button>

                            <button
                                type="button"
                                @click="logout"
                                :disabled="logoutForm.processing"
                                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
                            >
                                Log out
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
