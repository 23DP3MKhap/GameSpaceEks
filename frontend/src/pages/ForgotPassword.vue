<script setup>
import { ref, computed, onUnmounted } from 'vue'
import axios from '../plugins/axios'
import router from '@/router';

const step = ref(1)
const email = ref("")
const code = ref("")
const password = ref("")
const passwordConfirmation = ref("")
const emailValid = ref(false)
const resetValid = ref(false)
const sendLoading = ref(false)
const resetLoading = ref(false)
const error = ref("")
const successDialog = ref(false)
const resendCooldown = ref(0)
let cooldownTimer = null

const resendLabel = computed(() => {
    if (resendCooldown.value <= 0) return 'Nosūtīt vēlreiz'
    const minutes = Math.floor(resendCooldown.value / 60)
    const seconds = String(resendCooldown.value % 60).padStart(2, '0')
    return `Nosūtīt vēlreiz (${minutes}:${seconds})`
})

function startCooldown(seconds) {
    clearInterval(cooldownTimer)
    resendCooldown.value = seconds
    cooldownTimer = setInterval(() => {
        resendCooldown.value--
        if (resendCooldown.value <= 0) clearInterval(cooldownTimer)
    }, 1000)
}

onUnmounted(() => clearInterval(cooldownTimer))

async function sendCode() {
    sendLoading.value = true
    error.value = ""
    try {
        await axios.post("/api/password/forgot", { email: email.value })
        step.value = 2
        startCooldown(60)
    } catch (e) {
        if (e.response?.status === 429) {
            step.value = 2
            startCooldown(e.response.data.retry_after)
        } else {
            error.value = e.response?.data?.errors
                ? 'E-pasta adresei jābūt derīgai.'
                : e.response?.data?.message ?? 'Neizdevās nosūtīt kodu. Mēģini vēlreiz.'
        }
    } finally {
        sendLoading.value = false
    }
}

async function resetPassword() {
    resetLoading.value = true
    error.value = ""
    try {
        await axios.post("/api/password/reset", {
            email: email.value,
            code: code.value,
            password: password.value,
            password_confirmation: passwordConfirmation.value
        })
        successDialog.value = true
    } catch (e) {
        error.value = e.response?.data?.errors
            ? 'Nepareizs vai novecojis kods. Mēģini vēlreiz.'
            : e.response?.data?.message ?? 'Neizdevās nomainīt paroli. Mēģini vēlreiz.'
    } finally {
        resetLoading.value = false
    }
}

const emailRules = [
    value => {
        if (value) return true
        return 'E-pasts ir obligāts.'
    },
    value => {
        if (/.+@.+\..+/.test(value)) return true
        return 'E-pasta adresei jābūt derīgai.'
    },
]

const codeRules = [
    value => {
        if (/^\d{6}$/.test(value)) return true
        return 'Kodam jābūt 6 cipariem.'
    },
]

const passwordRules = [
    value => {
        if (value) return true
        return 'Parole ir obligāta.'
    },
    value => {
        if (value?.length >= 8) return true
        return 'Parolei jābūt vismaz 8 rakstzīmēm.'
    },
]

const passwordConfirmationRules = [
    value => {
        if (value) return true
        return 'Paroles apstiprinājums ir obligāts.'
    },
    value => {
        if (value === password.value) return true
        return 'Paroles nesakrīt.'
    },
]
</script>

<template>
    <div class="page">
        <div class="background"><img alt="Fons" src="/backgrounds/login-background.png"></div>
        <div class="page-wrapper">
            <div class="reset-form">
                <v-form v-if="step === 1" v-model="emailValid" @submit.prevent="sendCode">
                    <v-container>
                        <h1>Paroles atjaunošana</h1>
                        <p class="reset-text">Ievadi sava konta e-pasta adresi, un mēs nosūtīsim 6 ciparu kodu paroles atjaunošanai.</p>

                        <v-col class="reset-row" cols="12">
                            <v-text-field v-model="email" :rules="emailRules" maxlength="50" label="E-pasts" required></v-text-field>
                        </v-col>

                        <p v-if="error" class="reset-error">{{ error }}</p>

                        <v-btn type="submit" :disabled="!emailValid" :loading="sendLoading" class="reset-button">NOSŪTĪT KODU</v-btn>
                        <router-link to="/Login" class="back-link">Atpakaļ uz pieteikšanos</router-link>
                    </v-container>
                </v-form>

                <v-form v-else v-model="resetValid" @submit.prevent="resetPassword">
                    <v-container>
                        <h1>Jauna parole</h1>
                        <p class="reset-text">Ja konts ar adresi {{ email }} eksistē, uz to nosūtīts 6 ciparu kods. Ievadi to un jauno paroli.</p>

                        <v-col class="reset-row" cols="12">
                            <v-text-field v-model="code" :rules="codeRules" maxlength="6" label="Verifikācijas kods" required></v-text-field>
                        </v-col>

                        <v-col class="reset-row" cols="12">
                            <v-text-field type="password" v-model="password" :rules="passwordRules" maxlength="250" label="Jaunā parole" required></v-text-field>
                        </v-col>

                        <v-col class="reset-row" cols="12">
                            <v-text-field type="password" v-model="passwordConfirmation" :rules="passwordConfirmationRules"
                             maxlength="250" label="Apstiprināt paroli" required></v-text-field>
                        </v-col>

                        <p v-if="error" class="reset-error">{{ error }}</p>

                        <v-btn type="submit" :disabled="!resetValid" :loading="resetLoading" class="reset-button">MAINĪT PAROLI</v-btn>
                        <v-btn :loading="sendLoading" :disabled="resendCooldown > 0" @click="sendCode" variant="text" class="resend-button">
                            {{ resendLabel }}
                        </v-btn>
                    </v-container>
                </v-form>
            </div>
        </div>

        <v-dialog max-width="500" v-model="successDialog" persistent>
            <v-card class="v-card" color="black">
                <v-card-title class="v-card-title">Paroles atjaunošana</v-card-title>
                <v-card-text class="v-card-text">
                    Parole veiksmīgi nomainīta. Piesakies ar jauno paroli.
                </v-card-text>

                <v-card-actions>
                    <v-spacer></v-spacer>

                    <v-btn class="v-dialog-button" @click="router.push('/Login')">
                        <p class="v-btn-text">Pieteikties</p>
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<style scoped>
    .background img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
        pointer-events: none;
        user-select: none;
    }

    h1{
        color:white;
        width: 100%;
        text-align: center;
    }

    .background {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: -1;
    }

    .background::after {
        content: '';
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.5);
    }

    .page-wrapper {
        min-height: 70vh;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .reset-form {
        width: 100%;
        max-width: 420px;
        display: flex;
        flex-direction: column;

        padding: 40px;

        background: rgba(0, 0, 0, 0.452);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 16px;
        backdrop-filter: blur(8px);
    }

    .reset-row {
        color: white;
    }

    .reset-text {
        color: rgba(255, 255, 255, 0.7);
        font-size: 14px;
        text-align: center;
        margin: 12px 0;
    }

    .reset-error {
        color: #ff5252;
        font-size: 13px;
        padding: 0 12px;
    }

    .reset-button {
        background: rgba(0, 0, 0, 0.452);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 16px;
        color:rgb(255, 255, 255);
        width: 100%;
        margin-top: 30px;
    }

    .reset-button:disabled{
        background: rgb(0, 0, 0);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 16px;
        color:rgba(255, 255, 255, 0.228)
    }

    .resend-button {
        width: 100%;
        margin-top: 12px;
        color: rgba(255, 255, 255, 0.5);
        font-size: 12px;
    }

    .back-link {
        display: block;
        text-align: center;
        margin-top: 16px;
        font-size: 13px;
        color: rgba(255, 255, 255, 0.6);
        text-decoration: none;
    }

    .back-link:hover {
        color: white;
    }

    .v-card-title, .v-card-text {
        color: white;
    }

    .v-btn-text {
        color:white;
        font-size: 10px;
    }

    .v-dialog-button {
        background-color: rgb(0, 0, 0);
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.14);
    }

    .v-card {
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.587);
    }
</style>
