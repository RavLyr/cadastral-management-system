<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import LoginForm from '@/Components/LoginForm.vue';

const onSubmit = (payload) => {
    // UI-only behavior for the welcome page: log submitted credentials
    // Replace with a real authentication flow if you want this page to login
    // eslint-disable-next-line no-console
    console.log('login submit (welcome)', payload);
};
</script>

<template>
    <Head title="Buku C Temurejo" />

    <div class="min-h-screen flex items-center justify-center bg-slate-50 py-12">
        <div class="w-full max-w-5xl rounded-2xl shadow-lg grid grid-cols-1 md:grid-cols-2 overflow-hidden bg-white">

            <!-- Left artwork column -->
            <div class="bg-blue-50 flex items-center justify-center p-10">
                <div class="relative w-72 h-72 md:w-96 md:h-96">
                    <!-- Blue accent arcs -->
                    <div class="absolute inset-x-0 top-0 h-12 bg-blue-500 rounded-t-full -translate-y-6"></div>
                    <div class="absolute inset-x-0 bottom-0 h-12 bg-blue-500 rounded-b-full translate-y-6"></div>

                    <!-- SVG blob with masked image -->
                    <svg viewBox="0 0 600 600" class="w-full h-full drop-shadow-xl rounded-3xl">
                        <defs>
                            <clipPath id="blobClip">
                                <path fill="#F6F9FF" d="M421.5,335.5Q399,421,322.5,439Q246,457,181.5,414Q117,371,79,309Q41,247,88.5,185Q136,123,204,94Q272,65,340.5,91Q409,117,431,180Q453,243,421.5,335.5Z"/>
                            </clipPath>
                        </defs>
                        <image clip-path="url(#blobClip)" href="https://images.unsplash.com/photo-1501785888041-af3ef285b470?auto=format&fit=crop&w=1000&q=80" x="0" y="0" width="600" height="600" preserveAspectRatio="xMidYMid slice"/>
                        <path d="M421.5,335.5Q399,421,322.5,439Q246,457,181.5,414Q117,371,79,309Q41,247,88.5,185Q136,123,204,94Q272,65,340.5,91Q409,117,431,180Q453,243,421.5,335.5Z" fill="rgba(59,130,246,0.08)"/>
                    </svg>
                </div>
            </div>

            <!-- Right login form column -->
            <div class="p-10 bg-white">
                <div class="max-w-md mx-auto">
                    <h2 class="text-2xl font-extrabold text-slate-800 text-center">Buku C Digital</h2>
                    <p class="text-sm text-slate-400 text-center mt-1">Desa Temurejo</p>

                    <p class="text-center text-slate-500 mt-6">Selamat datang! Silakan masuk.</p>

                    <LoginForm @submit="onSubmit" />
                </div>
            </div>

        </div>
    </div>
</template>
