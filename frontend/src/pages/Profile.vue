<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import axios from '../plugins/axios'
import { useRoute } from 'vue-router'
import { auth } from '../plugins/userinfo'
import router from '@/router';
const route = useRoute()

const profileusername = ref("placeholder_user")
const profileBio = ref("Vēl nav bio.")
const profileRole = ref("user")
const profileAvatar = ref(null)
const counts = ref({ all: 0, playing: 0, completed: 0, planned: 0, dropped: 0 })
const collection = ref([])
const deleteConfirmation = ref(false)
const deletePassword = ref('')
const deleteError = ref('')
const isDeleting = ref(false)
const activeFilter = ref('all')
const sortOrder = ref('added_desc')
const removeDialog = ref(false)
const removeTarget = ref(null)
const removeError = ref('')
const isRemoving = ref(false)
const settingsPasswordConfirm = ref('')
const settingsDialog = ref(false)
const settingsUsername = ref('')
const settingsPassword = ref('')
const settingsBio = ref('')
const settingsAvatarFile = ref(null)
const settingsAvatarRemoved = ref(false)
const verificationDialog = ref(false)
const verificationCode = ref("")
const verificationError = ref("")
const verificationLoading = ref(false)
const verificationPurpose = ref('registration')
const emailChangeRequest = ref(null)
const resendLoading = ref(false)
const sendError = ref(false)
const collectionStatus = ref(null)
const settingsIsPrivate = ref(false)
const settingsEmail = ref('')
const settingsValid = ref(null)
const settingsForm = ref(null)
const settingsCurrentPassword = ref('')
const settingsErrors = ref({})
const settingsError = ref('')
const isSaving = ref(false)

const profileIsPrivate = ref(false)
const profileNotFound = ref(false)

const filters = [
    { key: 'all', label: 'Visi' },
    { key: 'playing', label: 'Spēlē' },
    { key: 'completed', label: 'Pabeigta' },
    { key: 'planned', label: 'Plānota' },
    { key: 'dropped', label: 'Pārtraukta' },
]

const sortOptions = [
    { value: 'added_desc', label: 'Pievienošanas datums (jaunākās)' },
    { value: 'rating_desc', label: 'Vērtējums (dilstoši)' },
    { value: 'rating_asc', label: 'Vērtējums (augoši)' },
    { value: 'hours_desc', label: 'Nospēlētās stundas (dilstoši)' },
]

const statusMeta = {
    playing:   { color: '#38ef7d', label: 'Spēlē' },
    completed: { color: '#4a9eff', label: 'Pabeigta' },
    planned:   { color: '#aaa', label: 'Plānota' },
    dropped:   { color: '#ff5f5f', label: 'Pārtraukta' },
}

function progressText(item) {
    return [
        item.hours_played !== null && `${item.hours_played} h`,
        item.completion_percent !== null && `${item.completion_percent} %`,
        item.completed_at && item.completed_at.split('-').reverse().join('.'),
    ].filter(Boolean).join(' · ')
}




const initials = computed(() => {
    return profileusername.value.slice(0, 2).toUpperCase()
})

const isOwner = computed(() => auth.user?.id == route.params.id)

const avatarError = 'Atļauti JPG, PNG vai WEBP attēli līdz 5 MB'

const avatarRules = [
    value => {
        if (!value || (['image/jpeg', 'image/png', 'image/webp'].includes(value.type) && value.size <= 5 * 1024 * 1024)) return true
        return avatarError
    }
]

const settingsAvatarPreview = computed(() => {
    if (settingsAvatarFile.value) return URL.createObjectURL(settingsAvatarFile.value)
    return settingsAvatarRemoved.value ? null : profileAvatar.value
})

const bioRules = [
    value => { if (!value || value.length <= 250) return true
    return "Biogrāfija nedrīkst būt garāka par 250 simboliem"
    }
]

const emailChanged = computed(() => {
    return !!settingsEmail.value && settingsEmail.value !== auth.user?.email
})

const needsCurrentPassword = computed(() => emailChanged.value || !!settingsPassword.value)

const usernameRules = [
        value => {
            if (/^[a-zA-Z][a-zA-Z0-9]*$/.test(value) || !value) return true

            return 'Lietotājvārds nevar sākties ar ciparu vai saturēt speciālos simbolus.'
        },
        async value => {
            if (!value || value === profileusername.value) return true
            try {
                const response = await axios.post('/api/usernamecheck', { username: value })
                if (response.data.exists === true) {
                    return 'Lietotājvārds jau ir aizņemts.'
                }
            } catch {
                // serveris pārbaudīs vēlreiz saglabāšanas brīdī
            }
            return true
        }
  ]

const passwordRules = [
        value => {
            if (value?.length >= 8 || !value) return true
            return 'Parolei jābūt vismaz 8 simboliem.'
        },
    ]

const emailRules = [
    value => {
        if (/.+@.+\..+/.test(value) || !value) return true
        return 'E-pasta adresei jābūt derīgai.'
    },
    async value => {
        if (!value || value === auth.user?.email) return true
        try {
            const response = await axios.post('/api/emailcheck', { email: value })
            if (response.data.exists === true) {
                return 'E-pasts jau ir reģistrēts.'
            }
        } catch {

        }
        return true
    }
]

    const passwordConfirmationRules = [
        value => {
            if (value) return true
            return 'Paroles apstiprinājums ir obligāts.'
        },
        value => {
            if (value === settingsPassword.value) return true
            return 'Paroles nesakrīt.'
        },
    ]

    const currentPasswordRules = [
        value => {
            if (value) return true
            return 'Ievadi pašreizējo paroli, lai mainītu e-pastu vai paroli.'
        },
    ]

async function loadCollection(status = null) {
    try{
    const res = await axios.get("/api/user/collection", {
        params: {
            user_id: route.params.id,
            status: status || undefined,
            sort: sortOrder.value
        }
    })
    collection.value = res.data.collection
    counts.value = res.data.stats

    }
    catch(error){
        if (error.response?.status === 403) {
            collection.value = []
            counts.value = { all: 0, playing: 0, completed: 0, planned: 0, dropped: 0 }
            collectionStatus.value = 'Profils ir iestatīts kā privāts'
        }
        else{
            collection.value = []
            counts.value = { all: 0, playing: 0, completed: 0, planned: 0, dropped: 0 }
            collectionStatus.value = 'Ielādējot kolekciju, radās kļūda.'
        }
    }
}

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

const verificationText = computed(() => {
    if (sendError.value) return 'Verifikācijas kodu neizdevās nosūtīt. Mēģini vēlreiz.'
    if (verificationPurpose.value === 'email_change') {
        return `Uz ${emailChangeRequest.value.email} nosūtīts 6 ciparu kods. Ievadi to, lai apstiprinātu jauno e-pastu.`
    }
    return 'Uz tavu e-pastu nosūtīts 6 ciparu kods. Ievadi to zemāk.'
})

function openEmailVerification() {
    verificationPurpose.value = 'registration'
    sendVerificationCode()
}

async function sendVerificationCode() {
    resendLoading.value = true
    sendError.value = false
    verificationError.value = ""
    verificationCode.value = ""
    try {
        if (verificationPurpose.value === 'email_change') {
            await axios.post("/api/email/change", emailChangeRequest.value)
        } else {
            await axios.post("/api/email/sendcode")
        }
        startCooldown(60)
    } catch (error) {
        if (error.response?.status === 429) {
            startCooldown(error.response.data.retry_after)
        } else {
            sendError.value = true
        }
    } finally {
        resendLoading.value = false
        verificationDialog.value = true
    }
}

async function verifyCode() {
    verificationLoading.value = true
    verificationError.value = ""
    try {
        if (verificationPurpose.value === 'email_change') {
            auth.user = (await axios.post("/api/email/change/confirm", { code: verificationCode.value })).data
        } else {
            await axios.post("/api/email/verifycode", { code: verificationCode.value })
            auth.user.email_verified = true
        }
        verificationDialog.value = false
    } catch (error) {
        verificationError.value = !error.response || error.response.data.errors
            ? 'Nepareizs vai novecojis kods. Mēģini vēlreiz.'
            : error.response.data.message
    } finally {
        verificationLoading.value = false
    }
}

function openSettings() {
    settingsEmail.value = auth.user.email 
    settingsIsPrivate.value = profileIsPrivate.value
    settingsUsername.value = profileusername.value
    settingsBio.value = profileBio.value
    settingsPassword.value = ''
    settingsPasswordConfirm.value = ''
    settingsCurrentPassword.value = ''
    settingsErrors.value = {}
    settingsError.value = ''
    settingsAvatarFile.value = null
    settingsAvatarRemoved.value = false
    settingsDialog.value = true
}

const settingsFieldErrors = {
    avatar: avatarError,
    current_password: 'Pašreizējā parole nav pareiza.',
    username: 'Lietotājvārds jau ir aizņemts vai nav derīgs.',
    email: 'E-pasts jau ir reģistrēts vai nav derīgs.',
    password: 'Parolei jābūt vismaz 8 simboliem.',
}

async function saveSettings() {
    const { valid } = await settingsForm.value.validate()
    if (!valid) return

    isSaving.value = true
    settingsErrors.value = {}
    settingsError.value = ''
    const changingEmail = emailChanged.value

    try {
        if (settingsAvatarFile.value) {
            const formData = new FormData()
            formData.append('avatar', settingsAvatarFile.value)
            profileAvatar.value = (await axios.post('/api/user/avatar', formData)).data.avatar_url
            settingsAvatarFile.value = null
        } else if (settingsAvatarRemoved.value) {
            await axios.delete('/api/user/avatar')
            profileAvatar.value = null
            settingsAvatarRemoved.value = false
        }

        if (changingEmail) {
            await axios.post('/api/email/change', {
                email: settingsEmail.value,
                current_password: settingsCurrentPassword.value,
            })
        }

        const updatedUser = await axios.post('/api/user/update', {
            username: settingsUsername.value || undefined,
            bio: settingsBio.value,
            password: settingsPassword.value || undefined,
            current_password: needsCurrentPassword.value ? settingsCurrentPassword.value : undefined,
            is_private: settingsIsPrivate.value
        })

        auth.user = updatedUser.data
        profileIsPrivate.value = settingsIsPrivate.value
        profileusername.value = settingsUsername.value || auth.user.username
        profileBio.value = settingsBio.value
        settingsDialog.value = false

        if (changingEmail) {
            verificationPurpose.value = 'email_change'
            emailChangeRequest.value = {
                email: settingsEmail.value,
                current_password: settingsPassword.value || settingsCurrentPassword.value,
            }
            verificationCode.value = ""
            verificationError.value = ""
            sendError.value = false
            startCooldown(60)
            verificationDialog.value = true
        }
    } catch (error) {
        const fields = Object.keys(error.response?.data?.errors ?? {})
        const known = fields.filter(field => settingsFieldErrors[field])

        if (error.response?.status === 422 && known.length) {
            settingsErrors.value = Object.fromEntries(known.map(field => [field, settingsFieldErrors[field]]))
        } else if (error.response?.status === 429) {
            settingsError.value = error.response.data.message
        } else {
            settingsError.value = 'Neizdevās saglabāt izmaiņas. Mēģini vēlreiz.'
        }
    } finally {
        isSaving.value = false
    }
}

async function deleteUser() {
    isDeleting.value = true
    deleteError.value = ''
    try {
        await axios.post('/api/database/deleteuser', { password: deletePassword.value })
        localStorage.removeItem('token')
        delete axios.defaults.headers.common['Authorization']
        auth.user = null
        router.push('/')
    } catch (error) {
        deleteError.value = error.response?.data?.message ?? 'Neizdevās dzēst kontu. Mēģini vēlreiz.'
    } finally {
        isDeleting.value = false
    }
}

function confirmRemove(game) {
    removeTarget.value = game
    removeError.value = ''
    removeDialog.value = true
}

async function removeGame() {
    isRemoving.value = true
    removeError.value = ''
    try {
        await axios.post('/api/database/removefromcollection', { game_id: removeTarget.value.id })
        removeDialog.value = false
        await loadCollection(activeFilter.value === 'all' ? null : activeFilter.value)
    } catch {
        removeError.value = 'Neizdevās noņemt spēli. Mēģini vēlreiz.'
    } finally {
        isRemoving.value = false
    }
}

onMounted(async () => {
    let userData
    try {
        userData = (await axios.get("/api/getuser", { params: { id: route.params.id } })).data
    } catch {
        profileNotFound.value = true
        return
    }
    profileIsPrivate.value = userData.is_private
    profileusername.value = userData.username
    profileBio.value = userData.bio
    profileAvatar.value = userData.avatar_url
    profileRole.value = userData.role

    await loadCollection()
})

watch(activeFilter, (filter) => {
    loadCollection(filter === 'all' ? null : filter)
})

watch(sortOrder, () => {
    loadCollection(activeFilter.value === 'all' ? null : activeFilter.value)
})

watch(settingsAvatarPreview, (url, oldUrl) => {
    if (oldUrl?.startsWith('blob:')) URL.revokeObjectURL(oldUrl)
})

watch(settingsAvatarFile, () => { delete settingsErrors.value.avatar })
watch(settingsUsername, () => { delete settingsErrors.value.username })
watch(settingsEmail, () => { delete settingsErrors.value.email })
watch(settingsPassword, () => { delete settingsErrors.value.password })
watch(settingsCurrentPassword, () => { delete settingsErrors.value.current_password })

watch(deleteConfirmation, (open) => {
    if (open) return
    deletePassword.value = ''
    deleteError.value = ''
})


</script>


<template>
    <div class="profile-page">
        <div v-if="profileNotFound" class="profile-wrapper">
            <div class="empty-state">Lietotājs nav atrasts.</div>
        </div>
        <div v-else class="profile-wrapper">
            <div class="profile-header">
                <div class="avatar-wrap">
                    <img v-if="profileAvatar" :src="profileAvatar" class="avatar-img" alt="Profila attēls" @error="$event.target.src = 'https://placehold.co/150/212121/white?text=U'">
                    <div v-else class="avatar-placeholder">{{ initials }}</div>
                </div>

                <div class="profile-meta">
                    <div class="profile-name">{{ profileusername }}</div>
                    <div class="profile-role-badge" :class="profileRole === 'admin' ? 'role-admin' : 'role-user'">
                        {{ profileRole === 'admin' ? 'Admins' : 'Lietotājs' }}
                    </div>
                    <div class="profile-bio">{{ profileBio }}</div>
                    <button v-if="isOwner" class="btn-settings" @click="openSettings">Rediģēt profilu</button>
                    <button
                        v-if="isOwner && !auth.user.email_verified"
                        class="btn-verify" 
                        :disabled="resendLoading"
                        @click="openEmailVerification">
                        {{ resendLoading ? 'Sūta...' : 'Apstiprināt e-pastu' }}
                    </button>
                </div>

                <div class="profile-stats">
                    <div class="stat-block" v-for="f in filters.slice(1)" :key="f.key">
                        <span class="stat-num" :style="{ color: statusMeta[f.key].color }">{{ counts[f.key] }}</span>
                        <span class="stat-lbl">{{ f.label }}</span>
                    </div>
                </div>
            </div>

            <div class="collection-section">
                <div class="section-top">
                    <h2 class="section-title">Kolekcija</h2>
                    <select class="sort-select" v-model="sortOrder" aria-label="Kārtošana">
                        <option v-for="option in sortOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>

                    <div class="filter-tabs">
                        <button
                            v-for="f in filters"
                            :key="f.key"
                            class="filter-tab"
                            :class="{ active: activeFilter === f.key }"
                            @click="activeFilter = f.key"
                        >
                            {{ f.label }}
                            <span class="tab-count">{{ counts[f.key] }}</span>
                        </button>
                    </div>
                </div>

                <div class="collection-grid">
                    <div class="game-entry" v-for="item in collection" :key="item.game.id">
                        <router-link :to="`/Game/${item.game.id}`">
                            <img :src="item.game.image" :alt="item.game.title" class="entry-cover">
                        </router-link>
                        <div class="entry-info">
                            <router-link :to="`/Game/${item.game.id}`" class="entry-name">{{ item.game.title }}</router-link>
                            <div class="entry-status" :style="{ color: statusMeta[item.status].color }">
                                <span class="status-dot" :style="{ background: statusMeta[item.status].color }"></span>
                                {{ statusMeta[item.status].label }}
                            </div>
                            <div class="entry-progress" v-if="progressText(item)">{{ progressText(item) }}</div>
                            <div class="entry-notes" v-if="item.note">{{ item.note }}</div>
                        </div>
                        <v-rating class="entry-score" v-if="item.rating !== null" :model-value="item.rating" readonly size="small"></v-rating>
                        <button v-if="isOwner" class="btn-remove-entry" @click="confirmRemove(item.game)">Noņemt</button>
                    </div>

                    <div v-if="collection.length === 0" class="empty-state">
                        {{collectionStatus ? collectionStatus  : 'Šeit vēl nekas nav.' }}
                    </div>
                </div>
            </div>
        </div>

        <v-dialog max-width="480" v-model="settingsDialog">
            <v-card class="dialog">
                <v-card-title class="v-card-title">Rediģēt profilu</v-card-title>
                <v-card-text class="v-card-text">
                    <v-form v-model="settingsValid" ref="settingsForm">
                    
                        <div class="settings-avatar-row">
                            <div class="settings-avatar-preview">
                                <img v-if="settingsAvatarPreview" :src="settingsAvatarPreview" class="avatar-img" alt="Profila attēls">
                                <div v-else class="avatar-placeholder">{{ initials }}</div>
                            </div>
                            <div class="settings-avatar-controls">
                                <v-file-input
                                    v-model="settingsAvatarFile"
                                    label="Profila attēls"
                                    accept="image/jpeg,image/png,image/webp"
                                    prepend-icon=""
                                    variant="outlined"
                                    density="compact"
                                    :rules="avatarRules"
                                    :error-messages="settingsErrors.avatar"
                                ></v-file-input>
                                <button
                                    v-if="settingsAvatarPreview"
                                    type="button"
                                    class="btn-remove-avatar"
                                    @click="settingsAvatarFile = null; settingsAvatarRemoved = true">
                                    Noņemt attēlu
                                </button>
                            </div>
                        </div>
                    
                        <v-text-field
                            v-model="settingsUsername"
                            label="Lietotājvārds"
                            placeholder="Lietotājvārds"
                            maxlength="20"
                            counter
                            variant="outlined"
                            density="compact"
                            :rules="usernameRules"
                            :error-messages="settingsErrors.username"
                        ></v-text-field>
                    
                        <v-text-field
                            v-model="settingsEmail"
                            label="E-pasts"
                            placeholder="E-pasta adrese"
                            maxlength="50"
                            variant="outlined"
                            density="compact"
                            :rules="emailRules"
                            :error-messages="settingsErrors.email"
                        ></v-text-field>
                    
                        <v-textarea
                            v-model="settingsBio"
                            placeholder="Pastāsti kaut ko par sevi..."
                            maxlength="250"
                            counter
                            variant="outlined"
                            density="compact"
                            rows="3"
                            no-resize
                            :rules="bioRules"
                        ></v-textarea>
                    
                        <div class="settings-private-row">
                            <span class="settings-label">Privāts profils</span>
                            <v-switch
                                v-model="settingsIsPrivate"
                                color="white"
                                density="compact"
                                hide-details
                            ></v-switch>
                        </div>
                    
                        <v-text-field
                            v-model="settingsPassword"
                            label="Jaunā parole"
                            placeholder="Atstāj tukšu, lai saglabātu pašreizējo"
                            type="password"
                            maxlength="250"
                            variant="outlined"
                            density="compact"
                            :rules="passwordRules"
                            :error-messages="settingsErrors.password"
                            persistent-hint
                        ></v-text-field>
                    
                        <v-text-field
                            v-if="settingsPassword"
                            v-model="settingsPasswordConfirm"
                            label="Apstiprināt paroli"
                            placeholder="Atkārtojiet jauno paroli"
                            type="password"
                            maxlength="250"
                            variant="outlined"
                            density="compact"
                            :rules="passwordConfirmationRules"
                        ></v-text-field>

                        <v-text-field
                            v-if="needsCurrentPassword"
                            v-model="settingsCurrentPassword"
                            label="Pašreizējā parole"
                            placeholder="Nepieciešama e-pasta vai paroles maiņai"
                            type="password"
                            maxlength="250"
                            variant="outlined"
                            density="compact"
                            :rules="currentPasswordRules"
                            :error-messages="settingsErrors.current_password"
                        ></v-text-field>

                        <div v-if="settingsError" class="field-hint hint-error">{{ settingsError }}</div>

                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn class="v-dialog-button btn-delete" @click="deleteConfirmation = true">
                        <p class="v-btn-text">Dzēst kontu</p>
                    </v-btn>
                    <v-btn class="v-dialog-button" @click="settingsDialog = false">
                        <p class="v-btn-text">Atcelt</p>
                    </v-btn>
                    <v-btn
                        class="v-dialog-button btn-save"
                        @click="saveSettings"
                        :disabled="settingsValid === false"
                        :loading="isSaving"
                    >
                        <p class="v-btn-text">Saglabāt</p>
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
        <v-dialog max-width="400" v-model="deleteConfirmation">
            <v-card class="dialog">
                <v-card-title class="v-card-title">Dzēst kontu</v-card-title>
                <v-card-text class="v-card-text">
                    Šī darbība neatgriezeniski dzēsīs kontu, kolekciju un atsauksmes. Ievadi paroli, lai apstiprinātu.
                    <v-text-field
                        v-model="deletePassword"
                        label="Parole"
                        type="password"
                        maxlength="250"
                        variant="outlined"
                        density="compact"
                        class="mt-4"
                        :error-messages="deleteError"
                    ></v-text-field>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn class="v-dialog-button" @click="deleteConfirmation = false">
                        <p class="v-btn-text">Atcelt</p>
                    </v-btn>
                    <v-btn
                        class="v-dialog-button btn-delete-confirm"
                        :disabled="!deletePassword"
                        :loading="isDeleting"
                        @click="deleteUser"
                    >
                        <p class="v-btn-text">Dzēst</p>
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog max-width="400" v-model="removeDialog">
            <v-card class="dialog">
                <v-card-title class="v-card-title">Noņemt spēli?</v-card-title>
                <v-card-text class="v-card-text">
                    Spēle „{{ removeTarget.title }}“ tiks noņemta no kolekcijas.
                    <div v-if="removeError" class="field-hint hint-error mt-2">{{ removeError }}</div>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn class="v-dialog-button" @click="removeDialog = false">
                        <p class="v-btn-text">Atcelt</p>
                    </v-btn>
                    <v-btn class="v-dialog-button btn-delete-confirm" :loading="isRemoving" @click="removeGame">
                        <p class="v-btn-text">Noņemt</p>
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog max-width="500" v-model="verificationDialog" persistent>
            <v-card class="dialog">
                <v-card-title class="v-card-title">E-pasta verifikācija</v-card-title>
                <v-card-text class="v-card-text">
                    {{ verificationText }}
                    <v-text-field
                        v-model="verificationCode"
                        label="Verifikācijas kods"
                        maxlength="6"
                        class="mt-4"
                        :error-messages="verificationError"
                    ></v-text-field>
                </v-card-text>
                <v-card-actions>
                    <v-btn :loading="resendLoading" :disabled="resendCooldown > 0" @click="sendVerificationCode" variant="text" style="color: rgba(255,255,255,0.5); font-size: 12px">
                        {{ resendLabel }}
                    </v-btn>
                    <v-spacer></v-spacer>
                    <v-btn class="v-dialog-button" @click="verificationDialog = false">
                        <p class="v-btn-text">Atcelt</p>
                    </v-btn>
                    <v-btn class="v-dialog-button btn-save" :loading="verificationLoading" @click="verifyCode">
                        <p class="v-btn-text">Apstiprināt</p>
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>


<style scoped>
.settings-private-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 0;
    border-top: 1px solid #1a1a1a;
    margin-top: 8px;
}

.btn-verify {
    margin-top: 6px;
    background: transparent;
    border: 1px solid rgba(255, 223, 95, 0.3);
    border-radius: 6px;
    color: #fff45f;
    font-size: 11px;
    padding: 4px 12px;
    cursor: pointer;
    transition: color 0.15s, border-color 0.15s;
}

.btn-verify:hover {
    border-color: rgba(255, 218, 95, 0.6);
    background: rgba(255, 228, 95, 0.08);
}

.v-dialog-button:hover {
    background-color: rgba(255, 255, 255, 0.06) ;
    border-color: rgba(255, 255, 255, 0.25) ;
    transition: background-color 0.15s, border-color 0.15s;
}

.btn-save:hover {
    background-color: rgba(74, 158, 255, 0.1) ;
    border-color: rgba(74, 158, 255, 0.5) ;
}

.btn-delete {
    border-color: rgba(255, 95, 95, 0.25);
    color: #ff5f5f;
}

.btn-delete:hover {
    background-color: rgba(255, 95, 95, 0.08) ;
    border-color: rgba(255, 95, 95, 0.5) ;
}

.btn-delete-confirm {
    border-color: rgba(255, 95, 95, 0.3);
    color: #ff5f5f;
}

.btn-delete-confirm:hover {
    background-color: rgba(255, 95, 95, 0.12) ;
    border-color: rgba(255, 95, 95, 0.6) ;
}

.sort-select {
    background: #0a0a0a;
    border: 1px solid #2a2a2a;
    border-radius: 6px;
    color: #ccc;
    font-size: 11px;
    padding: 4px 8px;
    cursor: pointer;
    outline: none;
    transition: border-color 0.15s;
}

.sort-select:hover,
.sort-select:focus {
    border-color: #444;
}

.btn-remove-entry {
    flex: 0 0 auto;
    background: transparent;
    border: none;
    color: #ff5f5f;
    font-size: 11px;
    padding: 0;
    cursor: pointer;
    opacity: 0.6;
    transition: opacity 0.15s;
}

.btn-remove-entry:hover {
    opacity: 1;
}


:deep(.v-field__outline) {
    --v-field-border-opacity: 0.3;
}

:deep(.v-field) {
    color: #eee;
}

:deep(.v-label) {
    color: #666;
}

:deep(.v-counter) {
    color: #555;
}

:deep(.v-messages__message) {
    color: #38ef7d;
}

:deep(.v-input--error .v-messages__message) {
    color: #ff5f5f;
}

:deep(.v-input) {
    margin-bottom: 6px;
}

:deep(.v-input__details) {
    padding-top: 2px;
    padding-bottom: 8px;
}

.field-hint {
    font-size: 11px;
    margin-top: 2px;
}

.hint-error {
    color: #ff5f5f;
}

.hint-ok {
    color: #38ef7d;
}

.profile-page {
    min-height: 100vh;
    background: #0a0a0a;
    color: #e8e8e8;
}

.profile-wrapper {
    max-width: 960px;
    margin: 0 auto;
    padding: 100px 24px 60px;
}

.profile-header {
    display: flex;
    align-items: flex-end;
    gap: 24px;
    margin-top: -48px;
    margin-bottom: 40px;
    flex-wrap: wrap;
}

.avatar-wrap {
    flex: 0 0 auto;
}

.avatar-img {
    width: 96px;
    height: 96px;
    border-radius: 50%;
    border: 3px solid #0a0a0a;
    object-fit: cover;
}

.avatar-placeholder {
    width: 96px;
    height: 96px;
    border-radius: 50%;
    border: 3px solid #0a0a0a;
    background: #1a1a1a;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    font-weight: 500;
    color: #555;
    letter-spacing: 2px;
}

.profile-meta {
    flex: 1;
    min-width: 0;
    padding-bottom: 4px;
}

.profile-name {
    font-size: 22px;
    font-weight: 500;
    color: #f5f5f5;
    margin-bottom: 6px;
}

.profile-role-badge {
    display: inline-block;
    font-size: 10px;
    font-weight: 500;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    padding: 2px 8px;
    border-radius: 4px;
    margin-bottom: 10px;
}

.role-admin {
    background: rgba(74, 158, 255, 0.12);
    color: #4a9eff;
    border: 1px solid rgba(74, 158, 255, 0.2);
}

.role-user {
    background: rgba(255, 255, 255, 0.05);
    color: #666;
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.profile-bio {
    font-size: 13px;
    color: #666;
    font-weight: 300;
    line-height: 1.5;
    overflow-wrap: anywhere;
}

.btn-settings {
    margin-top: 10px;
    background: transparent;
    border: 1px solid #2a2a2a;
    border-radius: 6px;
    color: #666;
    font-size: 11px;
    padding: 4px 12px;
    cursor: pointer;
    transition: color 0.15s, border-color 0.15s;
}

.btn-settings:hover {
    color: #ccc;
    border-color: #444;
}

.profile-stats {
    display: flex;
    gap: 28px;
    padding-bottom: 8px;
    flex-wrap: wrap;
}

.stat-block {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
}

.stat-num {
    font-size: 20px;
    font-weight: 500;
    line-height: 1;
}

.stat-lbl {
    font-size: 10px;
    color: #555;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

.section-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 12px;
}

.section-title {
    font-size: 14px;
    font-weight: 500;
    color: #f5f5f5;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin: 0;
}

.filter-tabs {
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
}

.filter-tab {
    background: transparent;
    border: 1px solid #1a1a1a;
    border-radius: 6px;
    color: #555;
    font-size: 11px;
    padding: 4px 10px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: color 0.15s, border-color 0.15s, background 0.15s;
}

.filter-tab:hover {
    color: #aaa;
    border-color: #2a2a2a;
}

.filter-tab.active {
    color: #f5f5f5;
    border-color: #333;
    background: #161616;
}

.tab-count {
    font-size: 10px;
    color: inherit;
    opacity: 0.6;
}

.collection-grid {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.game-entry {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 10px 14px;
    background: #111;
    border: 1px solid #181818;
    border-radius: 10px;
    transition: border-color 0.15s, background 0.15s;
}

.game-entry:hover {
    background: #141414;
    border-color: #222;
}

.entry-cover {
    display: block;
    width: 40px;
    height: 54px;
    object-fit: cover;
    border-radius: 4px;
    flex: 0 0 auto;
    filter: brightness(0.9);
}

.entry-info {
    flex: 1;
    min-width: 0;
}

.entry-name {
    display: block;
    font-size: 13px;
    font-weight: 400;
    color: #efefef;
    text-decoration: none;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    transition: color 0.15s;
}

.entry-name:hover {
    color: #4a9eff;
}

.entry-status {
    font-size: 11px;
    display: flex;
    align-items: center;
    gap: 5px;
    margin-bottom: 4px;
}

.status-dot {
    width: 5px;
    height: 5px;
    border-radius: 50%;
    flex: 0 0 auto;
}

.entry-progress {
    font-size: 11px;
    color: #888;
    margin-bottom: 4px;
}

.entry-notes {
    font-size: 11px;
    color: #555;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.entry-score {
    flex: 0 0 auto;
}

.empty-state {
    text-align: center;
    color: #333;
    font-size: 13px;
    padding: 40px 0;
}

.dialog {
    background-color: rgb(0, 0, 0);
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.14);
}

.v-card-title {
    color: white;
}

.v-card-text {
    color: #b8b8b8;
}

.v-dialog-button {
    background-color: rgb(0, 0, 0);
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.14);
    min-width: 70px;
}

.v-btn-text {
    color: white;
    font-size: 10px;
    margin: 0;
}

.btn-save {
    border-color: rgba(74, 158, 255, 0.3);
    color: #4a9eff;
}

.settings-avatar-row {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
}

.settings-avatar-preview .avatar-img,
.settings-avatar-preview .avatar-placeholder {
    width: 64px;
    height: 64px;
    font-size: 18px;
}

.settings-avatar-controls {
    flex: 1;
    min-width: 0;
}

.btn-remove-avatar {
    background: transparent;
    border: 1px solid rgba(255, 95, 95, 0.25);
    border-radius: 6px;
    color: #ff5f5f;
    font-size: 11px;
    padding: 4px 12px;
    cursor: pointer;
    transition: background-color 0.15s, border-color 0.15s;
}

.btn-remove-avatar:hover {
    background-color: rgba(255, 95, 95, 0.08);
    border-color: rgba(255, 95, 95, 0.5);
}

.settings-field {
    display: flex;
    flex-direction: column;
    gap: 5px;
    margin-bottom: 14px;
}

.settings-label {
    font-size: 11px;
    color: #555;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.settings-input {
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid #222;
    border-radius: 6px;
    color: #eee;
    font-size: 13px;
    padding: 8px 10px;
    outline: none;
    transition: border-color 0.15s;
    font-family: inherit;
}

.settings-input:focus {
    border-color: #444;
}

.settings-textarea {
    resize: none;
}
</style>