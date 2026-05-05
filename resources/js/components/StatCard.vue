<template>
    <div class="bg-white rounded-xl shadow-sm p-5">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm text-gray-500 font-medium">{{ title }}</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ value }}</p>
                <p v-if="sub" class="text-xs text-gray-400 mt-1">{{ sub }}</p>
            </div>
            <div :class="['w-10 h-10 rounded-xl flex items-center justify-center', colorClasses]">
                <component :is="icon" class="w-5 h-5" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    title: String,
    value: [String, Number],
    sub: String,
    icon: Object,
    color: { type: String, default: 'orange' },
})

const colorMap = {
    orange: 'bg-orange-100 text-orange-600',
    blue: 'bg-blue-100 text-blue-600',
    green: 'bg-green-100 text-green-600',
    yellow: 'bg-yellow-100 text-yellow-600',
    red: 'bg-red-100 text-red-600',
    purple: 'bg-purple-100 text-purple-600',
}

const colorClasses = computed(() => colorMap[props.color] ?? colorMap.orange)
</script>
