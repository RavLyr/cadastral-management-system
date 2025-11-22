<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
});

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const submit = () => {
  form.post('/register', {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
};

const togglePassword = () => {
  showPassword.value = !showPassword.value;
};

const togglePasswordConfirmation = () => {
  showPasswordConfirmation.value = !showPasswordConfirmation.value;
};
</script>

<template>
  <Head title="Register - Buku C Temurejo" />

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

      <!-- Right register form column -->
      <div class="p-10 bg-white">
        <div class="max-w-md mx-auto">
          <h2 class="text-2xl font-extrabold text-slate-800 text-center">Buat Akun Baru</h2>
          <p class="text-sm text-slate-400 text-center mt-1">Desa Temurejo</p>

          <p class="text-center text-slate-500 mt-6">Daftar untuk mengakses sistem</p>

          <form class="mt-6 space-y-5" @submit.prevent="submit">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2">Nama Lengkap</label>
              <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                  <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                  </svg>
                </span>
                <input
                  v-model="form.name"
                  type="text"
                  placeholder="Masukkan nama lengkap"
                  required
                  autofocus
                  class="block w-full pl-10 pr-4 py-3 border border-slate-200 rounded-lg bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-400"
                />
              </div>
              <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2">Email</label>
              <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                  <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 0 0 2.22 0L21 8M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2z"/>
                  </svg>
                </span>
                <input
                  v-model="form.email"
                  type="email"
                  placeholder="Masukkan email"
                  required
                  class="block w-full pl-10 pr-4 py-3 border border-slate-200 rounded-lg bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-400"
                />
              </div>
              <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2">Password</label>
              <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 0 0 2-2V11a4 4 0 0 0-4-4h-1.26A4 4 0 0 0 11 3.9V7"/>
                  </svg>
                </span>
                <input
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  placeholder="Buat password"
                  required
                  class="block w-full pl-10 pr-12 py-3 border border-slate-200 rounded-lg bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-400"
                />
                <button
                  type="button"
                  @click="togglePassword"
                  class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                >
                  <svg v-if="!showPassword" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0 1 12 19c-5 0-9-4-9-7a8.62 8.62 0 0 1 1.16-3.49M3 3l18 18"/>
                  </svg>
                  <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  </svg>
                </button>
              </div>
              <p v-if="form.errors.password" class="mt-1 text-xs text-red-500">{{ form.errors.password }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2">Konfirmasi Password</label>
              <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 0 0 2-2V11a4 4 0 0 0-4-4h-1.26A4 4 0 0 0 11 3.9V7"/>
                  </svg>
                </span>
                <input
                  v-model="form.password_confirmation"
                  :type="showPasswordConfirmation ? 'text' : 'password'"
                  placeholder="Ulangi password"
                  required
                  class="block w-full pl-10 pr-12 py-3 border border-slate-200 rounded-lg bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-400"
                />
                <button
                  type="button"
                  @click="togglePasswordConfirmation"
                  class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                >
                  <svg v-if="!showPasswordConfirmation" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0 1 12 19c-5 0-9-4-9-7a8.62 8.62 0 0 1 1.16-3.49M3 3l18 18"/>
                  </svg>
                  <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  </svg>
                </button>
              </div>
              <p v-if="form.errors.password_confirmation" class="mt-1 text-xs text-red-500">{{ form.errors.password_confirmation }}</p>
            </div>

            <div>
              <button
                type="submit"
                :disabled="form.processing"
                class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-full font-medium disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                {{ form.processing ? 'Loading...' : 'Daftar' }}
              </button>
            </div>

            <div class="text-center">
              <span class="text-sm text-gray-600">Sudah punya akun? </span>
              <a href="/login" class="text-sm text-blue-600 font-medium hover:underline">Login di sini</a>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>
</template>
