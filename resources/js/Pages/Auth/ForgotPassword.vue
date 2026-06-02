<script setup>
import ForgotPassword from '@/Components/Auth/ForgotPassword.vue';

defineProps({
    status: {
        type: String,
    },
});
</script>

<template>
    <ForgotPassword :status="status" />
</template>
