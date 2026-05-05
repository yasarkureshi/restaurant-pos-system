<template>
    <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium', classes]">
        {{ label }}
    </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({ status: String })

const config = {
    draft: { label: 'Draft', classes: 'bg-gray-100 text-gray-600' },
    placed: { label: 'Placed', classes: 'bg-blue-100 text-blue-700' },
    confirmed: { label: 'Confirmed', classes: 'bg-indigo-100 text-indigo-700' },
    preparing: { label: 'Preparing', classes: 'bg-yellow-100 text-yellow-700' },
    ready: { label: 'Ready', classes: 'bg-teal-100 text-teal-700' },
    served: { label: 'Served', classes: 'bg-green-100 text-green-700' },
    completed: { label: 'Completed', classes: 'bg-green-200 text-green-800' },
    cancelled: { label: 'Cancelled', classes: 'bg-red-100 text-red-700' },
    refunded: { label: 'Refunded', classes: 'bg-orange-100 text-orange-700' },
    pending: { label: 'Pending', classes: 'bg-yellow-100 text-yellow-700' },
    partial: { label: 'Partial', classes: 'bg-orange-100 text-orange-700' },
    paid: { label: 'Paid', classes: 'bg-green-100 text-green-700' },
    available: { label: 'Available', classes: 'bg-green-100 text-green-700' },
    occupied: { label: 'Occupied', classes: 'bg-red-100 text-red-700' },
    reserved: { label: 'Reserved', classes: 'bg-blue-100 text-blue-700' },
    cleaning: { label: 'Cleaning', classes: 'bg-yellow-100 text-yellow-700' },
    blocked: { label: 'Blocked', classes: 'bg-gray-200 text-gray-600' },
}

const label = computed(() => config[props.status]?.label ?? props.status)
const classes = computed(() => config[props.status]?.classes ?? 'bg-gray-100 text-gray-600')
</script>
