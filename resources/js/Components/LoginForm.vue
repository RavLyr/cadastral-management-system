<script setup>
import { ref, Text, toRefs } from 'vue';
import TextInput from './TextInput.vue';
import { Mail, Lock, Eye, EyeOff } from 'lucide-vue-next';
import InputError from './InputError.vue';
const props = defineProps({
    initialUsername: { type: String, default: '' },
    initialPassword: { type: String, default: '' },
    initialRemember: { type: Boolean, default: false },
    errors: { type: Object, default: () => ({}) },
    canResetPassword: { type: Boolean, default: true },
    status: { type: String, default: '' },
    processing: { type: Boolean, default: false },
});
const emit = defineEmits(['submit']);

const { initialUsername, initialPassword, initialRemember } = toRefs(props);

const username = ref(initialUsername.value);
const password = ref(initialPassword.value);
const remember = ref(initialRemember.value);
const showPassword = ref(false);

const togglePassword = () => (showPassword.value = !showPassword.value);

const onSubmit = (e) => {
    e && e.preventDefault();
    emit('submit', { username: username.value, password: password.value, remember: remember.value });
};
</script>

<template>
    <div>
        <div v-if="status" class="mt-6 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
            {{ status }}
        </div>

        <form class="mt-6 space-y-5" @submit.prevent="onSubmit">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Username</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                        <Mail :size="18" class="text-slate-400" />
                    </span>
                    <TextInput v-model="username" type="username" placeholder="Enter your username" required autofocus
                        class="block w-full pl-10 pr-4 py-3 border border-slate-200 rounded-lg bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-400" />

                </div>
                <InputError  :message="errors.email" />
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                        <Lock :size="18" class="text-slate-400" />
                    </span>
                    <TextInput v-model="password" :type="showPassword ? 'text' : 'password'"
                        placeholder="Enter your password" required
                        class="block w-full pl-10 pr-10 py-3 border border-slate-200 rounded-lg bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-400" />
                    <button type="button" @click="togglePassword"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                        <Eye v-if="!showPassword" :size="18" class="text-slate-400" />
                        <EyeOff v-else :size="18" class="text-slate-400" />
                    </button>
                </div>
                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2">
                    <input v-model="remember" type="checkbox"
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" />
                    <span class="text-sm text-gray-600">Ingat saya</span>
                </label>
                <a v-if="canResetPassword" href="/forgot-password"
                    class="text-sm text-blue-600 font-medium hover:underline">Lupa Password?</a>
            </div>

            <div>
                <button type="submit" :disabled="processing"
                    class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-full font-medium disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    <span v-if="processing">Loading...</span>
                    <span v-else>Login</span>
                </button>
            </div>
        </form>
    </div>
</template>
