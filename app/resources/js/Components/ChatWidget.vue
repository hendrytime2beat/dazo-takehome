<script setup>
import { ref } from 'vue'
import Icon from './Icon.vue'

const open = ref(false)

const messages = ref([
    { from: 'bot', text: 'Halo! Ada yang bisa kami bantu?' },
    { from: 'user', text: 'Apakah stok produk saya sudah aman?' },
])

const chatText = ref('')

function send() {
    const text = chatText.value.trim()
    if (!text) return
    messages.value.push({ from: 'user', text })
    chatText.value = ''
}
</script>

<template>
    <div class="fixed bottom-5 right-5 z-50 flex flex-col items-end gap-3">
        <transition
            enter-active-class="transition-all ease-out duration-200"
            enter-from-class="opacity-0 translate-y-3 scale-95"
            leave-active-class="transition-all ease-in duration-150"
            leave-to-class="opacity-0 translate-y-3 scale-95"
        >
            <div v-if="open" class="flex w-[300px] flex-col overflow-hidden rounded-lg border border-[#E8ECF5] bg-white shadow-2xl">
                <div class="flex items-center gap-2 border-b border-[#E8ECF5] bg-[#2949B5] px-3 py-2.5 text-white">
                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-white/20"><Icon name="chat" :size="15" /></span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[12px] font-semibold">Chat Dukungan</p>
                        <p class="text-[10px] text-white/70">Admin Dukungan · Online</p>
                    </div>
                    <button type="button" class="rounded p-1 hover:bg-white/20" :aria-label="'Tutup chat'" @click="open = false"><Icon name="x" :size="14" /></button>
                </div>

                <div class="h-56 space-y-2 overflow-y-auto bg-[#F7F9FE] p-3">
                    <div v-for="(msg, i) in messages" :key="i" :class="[msg.from === 'user' ? 'justify-end' : 'justify-start', 'flex']">
                        <p :class="[msg.from === 'user' ? 'bg-[#2949B5] text-white' : 'bg-white text-[#20252F] ring-1 ring-[#E8ECF5]', 'max-w-[80%] rounded-lg px-2.5 py-1.5 text-[12px]']">{{ msg.text }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-2 border-t border-[#E8ECF5] p-2">
                    <input
                        v-model="chatText"
                        type="text"
                        placeholder="Ketik pesan..."
                        class="h-9 flex-1 rounded-md border border-[#E8ECF5] bg-[#F7F9FE] px-3 text-[12px] outline-none placeholder:text-[#B4BACE] focus:border-[#2949B5]"
                        @keyup.enter="send"
                    />
                    <button type="button" class="flex h-9 w-9 items-center justify-center rounded-md bg-[#2949B5] text-white transition hover:bg-[#1F3A96]" :disabled="!chatText.trim()" :class="{ 'opacity-40': !chatText.trim() }" aria-label="Kirim pesan" @click="send">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 14-7-4 14-3-6-7-1Z" /></svg>
                    </button>
                </div>

                <p class="border-t border-[#E8ECF5] bg-white px-3 py-1.5 text-center text-[10px] text-[#B4BACE]">Demo — pesan tidak terkirim ke server</p>
            </div>
        </transition>

        <button
            type="button"
            class="flex h-10 items-center gap-2 rounded-full bg-[#2949B5] px-4 text-[13px] font-semibold text-white shadow-lg transition hover:bg-[#1F3A96]"
            :aria-expanded="open"
            @click="open = !open"
        >
            <Icon name="chat" :size="16" class="text-white" />
            <span>Chat</span>
        </button>
    </div>
</template>