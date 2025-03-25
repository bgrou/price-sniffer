<script setup lang="ts">
import AuthenticatedLayout from "/resources/js/Layouts/AuthenticatedLayout.vue";
import {Head, useForm} from "@inertiajs/vue3";
import {Button} from "/shadcn/components/ui/button";
import type {ProductEntry} from '/shadcn/components/columns.ts'
import {onMounted, ref, onUnmounted} from 'vue'
import {columns} from '/shadcn/components/columns'
import DataTable from '/shadcn/components/DataTable.vue'
import UploadFileIcon from "/resources/js/Components/UploadFileIcon.vue";
import { router } from '@inertiajs/vue3'
import axios from 'axios'

const fileInput = ref(null);
const files = ref([]);
const isUploading = ref(false);
const processingFiles = ref([]);
const pollingInterval = ref(null);

const form = useForm({
    files: [],
});

// Start polling for file status updates
const startStatusPolling = () => {
    if (pollingInterval.value) return;
    
    checkFileStatus();
    pollingInterval.value = setInterval(checkFileStatus, 3000);
};

// Stop polling when component is unmounted
onUnmounted(() => {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
    }
});

// Check file processing status
const checkFileStatus = () => {
    axios.get('/upload-status')
        .then(response => {
            processingFiles.value = response.data;
            
            // If all files are completed, stop polling
            if (processingFiles.value.length > 0 && 
                processingFiles.value.every(file => file.status === 'completed')) {
                if (pollingInterval.value) {
                    clearInterval(pollingInterval.value);
                    pollingInterval.value = null;
                }
            }
        })
        .catch(error => {
            console.error('Error checking file status:', error);
        });
};

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
        onSuccess: (response) => {
            // Clear file input
            files.value = [];
            form.files = [];
            fileInput.value.value = "";
            isUploading.value = false;
            
            // Start polling for status updates
            startStatusPolling();
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

                <!-- Selected files waiting to be uploaded -->
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
                
                <!-- Processing files status -->
                <div v-if="processingFiles.length" class="mt-6">
                    <h3 class="font-semibold text-lg mb-2">Processing Files</h3>
                    <ul class="space-y-2">
                        <li
                            v-for="(file, index) in processingFiles"
                            :key="index"
                            :class="{
                                'p-3 rounded-lg border flex flex-col gap-1': true,
                                'bg-yellow-100/70 border-yellow-200 text-yellow-900': file.status === 'queued' || file.status === 'processing',
                                'bg-green-100/70 border-green-200 text-green-900': file.status === 'completed',
                                'bg-red-100/70 border-red-200 text-red-900': file.status === 'error'
                            }"
                        >
                            <div class="flex justify-between items-center">
                                <span class="font-medium">{{ file.name }}</span>
                                <span class="text-sm">
                                    <span v-if="file.status === 'queued'">Queued</span>
                                    <span v-else-if="file.status === 'processing'">Processing</span>
                                    <span v-else-if="file.status === 'completed'">Completed</span>
                                    <span v-else-if="file.status === 'error'">Error</span>
                                </span>
                            </div>
                            
                            <!-- Progress bar -->
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div 
                                    class="h-2.5 rounded-full transition-all duration-300 ease-in-out" 
                                    :class="{
                                        'bg-yellow-400': file.status === 'queued' || file.status === 'processing',
                                        'bg-green-500': file.status === 'completed',
                                        'bg-red-500': file.status === 'error'
                                    }"
                                    :style="{ width: file.progress + '%' }"
                                ></div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
