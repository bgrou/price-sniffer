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
        return response.data;
    } catch (error) {
        console.error('Failed to fetch data:', error);
        return []; // Return an empty array on error
    }
}

onMounted(async () => {
    data.value = await getData()
})

const fileInput = ref(null);
const files = ref([]);
const isUploading = ref(false); // Loading state

const form = useForm({
    files: [],
});

const triggerFileInput = () => {
    fileInput.value.click();
};

const handleFileChange = (event) => {
    files.value = Array.from(event.target.files);
    form.files = [...files.value]; // Ensure reactive assignment
};

const uploadFiles = () => {
    if (files.value.length === 0) return;

    isUploading.value = true;

    form.post("/upload-sheet", {
        onSuccess: () => {
            alert("Files uploaded successfully!");
            files.value = [];
            form.files = [];
            fileInput.value.value = ""; // Reset input field
            isUploading.value = false;
        },
        onError: (errors) => {
            alert("File upload failed! " + JSON.stringify(errors));
            isUploading.value = false;
        }
    });
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <!-- Hidden file input -->
        <input
            type="file"
            multiple
            ref="fileInput"
            class="hidden"
            @change="handleFileChange"
        />

        <!-- Button to trigger file selection -->
        <Button @click="triggerFileInput">Select Files</Button>

        <!-- Upload Button with Loading State -->
        <Button
            v-if="files.length"
            class="ml-2"
            @click="uploadFiles"
            :disabled="isUploading"
        >
            <span v-if="isUploading">Uploading...</span>
            <span v-else>Upload Files</span>
        </Button>

        <!-- File List Preview -->
        <ul v-if="files.length" class="mt-4">
            <li v-for="(file, index) in files" :key="index">
                {{ file.name }} ({{ (file.size / 1024).toFixed(2) }} KB)
            </li>
        </ul>

        <div class="container py-10 mx-auto">
            <DataTable :columns="columns" :data="data" />
        </div>
    </AuthenticatedLayout>
</template>
