<script setup>
import VerifyEmail from '@/Components/Auth/VerifyEmail.vue';

defineProps({
    status: {
        type: String,
    },
});
</script>

<template>
    <VerifyEmail :status="status" />
</template>
