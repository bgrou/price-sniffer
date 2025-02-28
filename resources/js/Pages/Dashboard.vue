<script setup lang="ts">
import AuthenticatedLayout from "/resources/js/Layouts/AuthenticatedLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { Button } from "/shadcn/components/ui/button";
import type { ProductEntry } from '/shadcn/components/columns.ts'
import { onMounted, ref } from 'vue'
import { columns } from '/shadcn/components/columns'
import DataTable from '/shadcn/components/DataTable.vue'

const data = ref<ProductEntry[]>([])

async function getData(): Promise<ProductEntry[]> {
    // Fetch data from your API here.
    try {
        const response = await axios.get('/product-entries'); // Adjust the URL to your API endpoint
        console.log(response.data);
        return response.data;
    } catch (error) {
        console.error('Failed to fetch data:', error);
        return []; // Return an empty array on error
    }
}

onMounted(async () => {
    data.value = await getData()
})
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="container py-10 mx-auto">
            <DataTable :columns="columns" :data=null />
        </div>
    </AuthenticatedLayout>
</template>
