<script setup lang="ts">
import AuthenticatedLayout from "/resources/js/Layouts/AuthenticatedLayout.vue";
import {Head, useForm} from "@inertiajs/vue3";
import {Button} from "/shadcn/components/ui/button";
import type {ProductEntry} from '/shadcn/components/columns.ts'
import {onMounted, ref} from 'vue'
import {columns} from '/shadcn/components/columns'
import DataTable from '/shadcn/components/DataTable.vue'
import UploadFileIcon from "/resources/js/Components/UploadFileIcon.vue";
import { router } from '@inertiajs/vue3'

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

const confirmDeletion = () => {
    if (confirm('Are you sure you want to delete all sheets? This action cannot be undone.')) {
        router.delete(route('delete_sheets'));
    }
}
</script>

<template>
    <Head title="Upload Sheets" />

    <AuthenticatedLayout>
        <div class="min-h-screen flex items-center justify-center ">
            <div class="p-8 bg-yellow-50 rounded-xl shadow-md shadow-yellow-200/50 border border-yellow-200 w-full max-w-md">
                <div class="flex justify-center mb-7">
                    <UploadFileIcon></UploadFileIcon>
                </div>
                <input
                    type="file"
                    multiple
                    ref="fileInput"
                    class="hidden"
                    @change="handleFileChange"
                />

                <div class="flex flex-col gap-4">
                    <Button
                        @click="triggerFileInput"
                        class="bg-yellow-400 hover:bg-yellow-500 hover:text-yellow-950 ring-yellow-300
                   focus-visible:ring-2 focus-visible:ring-offset-2 transition-colors shadow-sm border-yellow-400"
                    >
                        📁 Select Files
                    </Button>

                    <Button
                        v-if="files.length"
                        @click="uploadFiles"
                        :disabled="isUploading"
                        class="bg-yellow-400 hover:bg-yellow-500 hover:text-yellow-950
                   disabled:opacity-50 disabled:cursor-not-allowed transition-colors
                   border-yellow-500 shadow-md"
                    >
            <span v-if="isUploading" class="flex items-center gap-2">
              <svg class="animate-spin h-4 w-4 text-yellow-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Uploading...
            </span>
                        <span v-else>🚀 Upload Files</span>
                    </Button>
                    <Button
                        @click="confirmDeletion"
                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        Delete All Sheets
                    </Button>
                </div>

                <ul v-if="files.length" class="mt-6 space-y-2">
                    <li
                        v-for="(file, index) in files"
                        :key="index"
                        class="p-3 bg-yellow-100/70 rounded-lg border border-yellow-200 text-yellow-900
                   flex justify-between items-center backdrop-blur-sm hover:bg-yellow-100 transition-colors"
                    >
                        <span class="truncate">{{ file.name }}</span>
                        <span class="text-yellow-600 text-sm ml-2">
              {{ (file.size / 1024).toFixed(2) }} KB
            </span>
                    </li>
                </ul>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
