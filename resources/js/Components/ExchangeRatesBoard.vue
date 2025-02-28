<script setup lang="ts">
import { Head } from '@inertiajs/vue3'

defineProps({
    rates: {
        type: Object,
        default: null
    },
});


</script>

<template>
    <div class="p-6">
        <div v-if="rates.length" class="border-white rounded-lg overflow-hidden bg-stone-900">
            <h4 class="px-8 py-4 font-semibold text-white">Updated Currency Rates</h4>
            <table class="w-full mb-6">

                <tbody class="bg-stone-900">
                <tr class="justify-between " v-for="rate in rates" :key="rate.symbol">
                    <td class="px-8 py-3 font-bold whitespace-nowrap text-xs text-white">{{ rate.symbol }}</td>
                    <td class="px-8  w-1/4 py-3 whitespace-nowrap text-xs text-center">
                        <div class="inline-block rounded-md text-white px-4 py-0"
                             :class="{
                                        'bg-green-600': rate.status === 'up',
                                        'bg-red-600': rate.status === 'down',
                                        'bg-gray-600': rate.status === 'equal'
                                     }"
                        >
                            {{ rate.rate.toFixed(5) }}
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <div v-else class="text-center py-8 text-white">
            No exchange rates available.
        </div>
    </div>
</template>
