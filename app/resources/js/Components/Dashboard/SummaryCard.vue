<script setup>
import { computed } from 'vue'
import Icon from '../Icon.vue'
import { formatIDR, formatNumber } from '../../Shared/format.js'

const props = defineProps({
    label: { type: String, required: true },
    value: { type: [Number, String], default: 0 },
    icon: { type: String, default: 'order' },
    currency: { type: Boolean, default: false },
    info: { type: String, default: '' },
})

const formatted = computed(() => {
    if (props.value === null || props.value === undefined || props.value === '') return '0'
    if (props.currency) return formatIDR(Number(props.value)).replace(/^Rp\s*/, '')
    return formatNumber(Number(props.value))
})
</script>

<template>
    <div class="relative flex h-[75px] items-center gap-3 overflow-hidden rounded-[4px] border border-[#E8ECF5] bg-white px-4">
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-1">
                <span class="text-[11px] font-medium text-[#7C8497]">{{ label }}</span>
                <span v-if="info" class="group relative inline-flex" tabindex="0">
                    <Icon name="info" :size="13" class="text-[#B4BACE]" />
                    <span class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-1 w-max max-w-[220px] -translate-x-1/2 rounded-md bg-[#20252F] px-2.5 py-1.5 text-[11px] font-normal text-white opacity-0 shadow transition group-hover:opacity-100 group-focus:opacity-100">{{ info }}</span>
                </span>
            </div>
            <p class="mt-1 flex items-baseline gap-1 text-[14px] font-bold tracking-tight text-[#20252F]">
                <span v-if="currency" class="text-[12px] font-bold text-[#2949B5]">IDR</span>
                <span>{{ formatted }}</span>
            </p>
        </div>
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#E7EDFF] text-[#2949B5] opacity-70">
            <Icon :name="icon" :size="19" />
        </div>
    </div>
</template>