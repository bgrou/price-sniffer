<script setup lang="ts">
import AuthenticatedLayout from "/resources/js/Layouts/AuthenticatedLayout.vue";
import {onMounted} from "vue";
import ExchangeRatesBoard from "/resources/js/Components/ExchangeRatesBoard.vue";
import {Head} from "@inertiajs/vue3";

// Define the component's props so that Inertia can pass the loaded rates here
defineProps({ rates: Object })

</script>

<template>
    <Head title="Currency Rates" />
    <AuthenticatedLayout>
        <div class="min-h-screen w-full flex items-center justify-center ">
            <ExchangeRatesBoard :rates="rates" />
        </div>
    </AuthenticatedLayout>
</template>
