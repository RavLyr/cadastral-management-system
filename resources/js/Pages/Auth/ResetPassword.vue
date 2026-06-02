<script setup>
import ResetPassword from '@/Components/Auth/ResetPassword.vue';

defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <ResetPassword :email="email" :token="token" />
</template>
