<script setup>
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import CustomerServiceIllustration from '../../Components/CustomerServiceIllustration.vue'

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

const showPassword = ref(false)
const captchaVerified = ref(false)
const captchaError = ref('')

const identifierError = computed(() => {
    const value = form.email.trim()
    if (!value) return 'Email / nomor WhatsApp wajib diisi.'
    const isEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)
    const isPhone = /^(?:\+?62|628|08)\d{7,13}$/.test(value.replace(/[\s-]/g, ''))
    if (!isEmail && !isPhone) return 'Masukkan format email atau nomor WhatsApp yang valid.'
    return ''
})

const passwordError = computed(() => {
    if (!form.password) return 'Password wajib diisi.'
    return ''
})

function togglePassword() {
    showPassword.value = !showPassword.value
}

function onCaptchaChange() {
    if (captchaVerified.value) captchaError.value = ''
}

function submit() {
    captchaError.value = ''
    if (identifierError.value || passwordError.value) return
    if (!captchaVerified.value) {
        captchaError.value = 'Verifikasi captcha terlebih dahulu untuk melanjutkan.'
        return
    }

    form.post('/login', {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <main class="flex min-h-screen w-full bg-white font-sans">
        <section class="flex w-full flex-col items-center justify-center bg-white px-6 py-12 lg:w-1/2 lg:px-16">
            <div class="w-full max-w-[400px]">
                <div class="flex flex-col items-center gap-3">
                    <a href="/login" class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#4F7DF9]" aria-label="DAZO Sales — beranda">
                        <svg class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M8 10.5h8M8 14h5" stroke-linecap="round" />
                            <path d="M20.5 7.5h-17C3 7.5 2.5 8 2.5 8.6v8.8c0 .6.5 1.1 1 1.1h5.5l1.7 2.3c.3.4.9.4 1.2 0l1.7-2.3h6.9c.6 0 1-.5 1-1.1V8.6c0-.6-.4-1.1-1-1.1Z" />
                        </svg>
                    </a>
                    <p class="text-lg font-bold tracking-tight text-slate-900">DAZO Sales</p>
                </div>

                <h1 class="mt-9 text-center text-2xl font-semibold text-slate-900">Masuk ke Akun Anda</h1>
                <p class="mt-2 text-center text-[13px] leading-relaxed text-slate-500">
                    Masukkan email dan kata sandi untuk melanjutkan ke akun Anda
                </p>

                <form class="mt-8" novalidate @submit.prevent="submit">
                    <div>
                        <label for="email" class="block text-[13px] font-medium text-slate-700">Email / Nomor Telepon Whatsapp*</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="text"
                            name="email"
                            autocomplete="username"
                            placeholder="Masukkan Email/Nomor Telepon Whatsapp"
                            required
                            class="mt-1.5 block h-10 w-full rounded-md border border-slate-200/80 bg-[#F5F8FC] px-3.5 text-[13px] text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#4F7DF9] focus:bg-white focus:ring-2 focus:ring-[#4F7DF9]/20"
                            :aria-invalid="Boolean(form.errors.email || identifierError)"
                        />
                        <p v-if="identifierError" id="email-error" class="mt-1.5 text-xs text-rose-600" role="alert">{{ identifierError }}</p>
                        <p v-else-if="form.errors.email" class="mt-1.5 text-xs text-rose-600">{{ form.errors.email }}</p>
                    </div>

                    <div class="mt-5">
                        <div class="flex items-center justify-between">
                            <label for="password" class="block text-[13px] font-medium text-slate-700">Password*</label>
                        </div>
                        <div class="relative mt-1.5">
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                name="password"
                                autocomplete="current-password"
                                placeholder="Masukkan Password"
                                required
                                class="block h-10 w-full rounded-md border border-slate-200/80 bg-[#F5F8FC] px-3.5 pr-11 text-[13px] text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#4F7DF9] focus:bg-white focus:ring-2 focus:ring-[#4F7DF9]/20"
                                :aria-invalid="Boolean(form.errors.password || passwordError)"
                            />
                            <button
                                type="button"
                                class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-slate-400 outline-none transition hover:text-slate-600 focus:text-[#4F7DF9]"
                                :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                                :aria-pressed="showPassword"
                                @click="togglePassword"
                            >
                                <svg v-if="!showPassword" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                    <path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <svg v-else class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                    <path d="M3 3l18 18M10.6 5.1c.5-.07.9-.1 1.4-.1 6 0 9.5 7 9.5 7a17.4 17.4 0 0 1-3 3.7M6.2 6.2A16.5 16.5 0 0 0 2.5 12s3.5 7 9.5 7c2.2 0 4-.7 5.5-1.8M9.9 9.9a3 3 0 0 0 4.2 4.2" />
                                </svg>
                            </button>
                        </div>
                        <p v-if="passwordError" id="password-error" class="mt-1.5 text-xs text-rose-600" role="alert">{{ passwordError }}</p>
                        <p v-else-if="form.errors.password" class="mt-1.5 text-xs text-rose-600">{{ form.errors.password }}</p>
                    </div>

                    <div class="mt-3 text-left">
                        <a href="/forgot-password" class="text-xs font-medium text-slate-500 transition hover:text-[#4F7DF9]">Lupa password?</a>
                    </div>

                    <div class="mt-4">
                        <div
                            class="flex items-center gap-3 rounded-md border px-3.5 py-3 transition"
                            :class="captchaVerified ? 'border-emerald-300 bg-emerald-50/40' : 'border-slate-200/80 bg-white'"
                        >
                            <label for="captcha" class="flex cursor-pointer select-none items-center gap-3">
                                <input
                                    id="captcha"
                                    v-model="captchaVerified"
                                    type="checkbox"
                                    class="peer sr-only"
                                    @change="onCaptchaChange"
                                />
                                <span
                                    class="flex h-[18px] w-[18px] shrink-0 items-center justify-center rounded border-2 border-slate-300 bg-white transition peer-checked:border-emerald-500 peer-checked:bg-emerald-500"
                                    aria-hidden="true"
                                >
                                    <svg v-if="captchaVerified" class="h-3 w-3 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M5 13l4 4L19 7" />
                                    </svg>
                                </span>
                                <span class="text-[13px] font-medium text-slate-700">Saya bukan robot</span>
                            </label>
                            <span class="mx-1 h-8 w-px bg-slate-200" aria-hidden="true" />
                            <span class="ml-auto flex items-center gap-2" aria-hidden="true">
                                <span class="flex h-8 w-8 items-center justify-center rounded border border-dashed border-slate-300 bg-[#F5F8FC]">
                                    <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                                        <path d="M19.5 12a7.5 7.5 0 1 1-2.2-5.3M19.5 4.5V9H15" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                            </span>
                        </div>
                        <p
                            :class="captchaError ? 'text-rose-600' : captchaVerified ? 'text-emerald-600' : 'text-slate-400'"
                            class="mt-1.5 text-xs"
                            role="status"
                        >
                            {{ captchaError || (captchaVerified ? 'Captcha berhasil diverifikasi.' : 'Verifikasi captcha untuk melanjutkan.') }}
                        </p>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="mt-6 flex h-10 w-full items-center justify-center rounded-md bg-[#EDF0F5] text-[13px] font-semibold text-slate-700 outline-none transition hover:bg-[#E2E6ED] focus-visible:ring-2 focus-visible:ring-[#4F7DF9] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <svg v-if="form.processing" class="mr-2 h-4 w-4 animate-spin text-slate-500" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z" />
                        </svg>
                        {{ form.processing ? 'Memproses...' : 'Masuk' }}
                    </button>

                    <div class="my-5 flex items-center gap-3" role="separator" aria-label="atau">
                        <span class="h-px flex-1 bg-slate-200" aria-hidden="true" />
                        <span class="text-xs text-slate-400">atau</span>
                        <span class="h-px flex-1 bg-slate-200" aria-hidden="true" />
                    </div>

                    <button
                        type="button"
                        title="Integrasi Google OAuth belum diaktifkan pada demo ini"
                        class="flex h-10 w-full items-center justify-center gap-2.5 rounded-md border border-slate-300 bg-white text-[13px] font-medium text-slate-700 outline-none transition hover:bg-slate-50 focus-visible:ring-2 focus-visible:ring-[#4F7DF9] focus-visible:ring-offset-2"
                    >
                        <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                        </svg>
                        Masuk dengan Google
                    </button>

                    <p class="mt-7 text-center text-[13px] text-slate-500">
                        Belum punya akun?
                        <a href="/register" class="font-semibold text-[#4F7DF9] transition hover:text-[#3D6BEC]">Daftar</a>
                    </p>
                </form>
            </div>

            <p class="mt-10 text-center text-xs leading-relaxed text-slate-400">
                Demo: <span class="font-medium text-slate-500">admin@dazo.test</span> / <span class="font-medium text-slate-500">password</span>
            </p>
        </section>

        <aside class="relative hidden w-1/2 flex-col items-center justify-center overflow-hidden bg-[#F4F8FF] px-10 lg:flex" aria-label="Ilustrasi layanan pelanggan">
            <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                <div class="absolute -left-24 top-8 h-72 w-72 rounded-full bg-[#E1EDFF] opacity-80 blur-2xl" />
                <div class="absolute -bottom-16 -right-16 h-80 w-80 rounded-full bg-[#E1EDFF] opacity-70 blur-2xl" />
            </div>

            <div class="relative w-full max-w-[560px]">
                <CustomerServiceIllustration class="w-full" />
                <p class="mt-1 text-center text-[15px] font-medium text-slate-700">Chatbot untuk layanan pelanggan 24/7</p>

                <div class="mt-5 flex items-center justify-center gap-2" role="tablist" aria-label="Indikator carousel ilustrasi">
                    <span v-for="(dot, i) in 3" :key="i" :class="i === 0 ? 'h-2.5 w-2.5 bg-[#4F7DF9]' : 'h-2 w-2 bg-[#C3D6FF]'" class="rounded-full" aria-hidden="true" />
                </div>
            </div>
        </aside>
    </main>
</template>