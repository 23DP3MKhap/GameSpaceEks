<script setup>
import { ref, onMounted, computed, watch } from 'vue'
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
const activeFilter = ref('Visi')
const sortOrder = ref('desc')
const settingsPasswordConfirm = ref('')
const settingsDialog = ref(false)
const settingsUsername = ref('')
const settingsPassword = ref('')
const settingsBio = ref('')
const settingsAvatarUrl = ref('')
const verificationDialog = ref(false)
const verificationCode = ref("")
const verificationError = ref(false)
const verificationLoading = ref(false)
const resendLoading = ref(false)
const collectionStatus = ref(null)
const settingsIsPrivate = ref(false)
const settingsEmail = ref('')
const settingsValid = ref(null)
const isSaving = ref(true)

const profileIsPrivate = ref(false)

const filters = [
    { key: 'Visi', label: 'Visi' },
    { key: 'Spēlēju', label: 'Spēlēju' },
    { key: 'Pabeigta', label: 'Pabeigta' },
    { key: 'Plānots', label: 'Plānots' },
    { key: 'Pārtraukts', label: 'Pārtraukts' },
]

const statusMeta = {
    Spēlēju:   { color: '#38ef7d', label: 'Spēlēju' },
    Pabeigta: { color: '#4a9eff', label: 'Pabeigta' },
    Plānots:   { color: '#aaa', label: 'Plānots' },
    Pārtraukts:   { color: '#ff5f5f', label: 'Pārtraukts' },
}




const initials = computed(() => {
    return profileusername.value.slice(0, 2).toUpperCase()
})

const avatarUrlRules = [
    value => {
        if (!value || /^https?:\/\/.+/.test(value)) return true
        return 'URL ir jāsākas ar http:// vai https://'
    }
]

const bioRules = [
    value => { if (!value || value.length <= 50) return true
    return "Biogrāfija nedrīkst būt garāka par 50 simboliem"
    }
]

const usernameRules = [
        
        async value => {
            if (isSaving.value) return true
            if (settingsUsername.value === value || !settingsUsername.value) return true
            const response = await axios.post('/api/usernamecheck', { username: value })
            if (response.data.exists === true) {
                return 'Lietotājvārds jau ir aizņemts.'
            }
            return true
        },
        value => {
            if (/^[a-zA-Z][\w]*$/.test(value) || !value) return true
            
            return 'Lietotājvārds nevar sākties ar ciparu vai saturēt speciālos simbolus.'
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
        if (isSaving.value) return true
        if (settingsEmail.value === value || !settingsEmail.value) return true
        const response = await axios.post('/api/emailcheck', { email: value })
        if (response.data.exists === true) {
            return 'E-pasts jau ir reģistrēts.'
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
            if (value === password.value) return true
            return 'Paroles nesakrīt.'
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
            counts.value = { Visi: 0, Spēlēju: 0, Pabeigta: 0, Plānots: 0, Pārtraukts: 0 }
            collectionStatus.value = 'Profils ir iestatīts kā privāts'
        }
        else{
            collection.value = []
            counts.value = { Visi: 0, Spēlēju: 0, Pabeigta: 0, Plānots: 0, Pārtraukts: 0 }
            collectionStatus.value = 'Ielādējot kolekciju, radās kļūda.'
        }
    }
}

async function sendVerificationCode() {
    resendLoading.value = true
    try {
        await axios.post("/api/email/sendcode")
        verificationDialog.value = true
    } finally {
        resendLoading.value = false
    }
}

async function verifyCode() {
    verificationLoading.value = true
    verificationError.value = false
    try {
        await axios.post("/api/email/verifycode", { code: verificationCode.value })
        auth.user.email_verified = true
        verificationDialog.value = false
    } catch {
        verificationError.value = true
    } finally {
        verificationLoading.value = false
    }
}

async function resendCode() {
    resendLoading.value = true
    try {
        await axios.post("/api/email/sendcode")
    } finally {
        resendLoading.value = false
    }
}

function openSettings() {
    settingsEmail.value = auth.user.email 
    settingsIsPrivate.value = profileIsPrivate.value
    settingsUsername.value = profileusername.value
    settingsBio.value = profileBio.value
    settingsPassword.value = ''
    settingsAvatarUrl.value = profileAvatar.value || ''
    settingsDialog.value = true
}

async function saveSettings() {
    const updatedUser = await axios.post('/api/user/update', {
        email: settingsEmail.value || undefined,
        username: settingsUsername.value || undefined,
        bio: settingsBio.value,
        avatar_url: settingsAvatarUrl.value,
        password: settingsPassword.value || undefined,
        is_private: settingsIsPrivate.value
    })

    auth.user = updatedUser.data
    profileIsPrivate.value = settingsIsPrivate.value
    profileusername.value = settingsUsername.value || auth.user.name
    profileBio.value = settingsBio.value
    profileAvatar.value = settingsAvatarUrl.value || null
    settingsDialog.value = false
}

async function deleteUser() {
    
    await axios.post('/api/database/deleteuser')
    auth.user = null
    router.push('/')
}

onMounted(async () => {
    const userData = (await axios.get("/api/getuser", { params: { id: route.params.id } })).data
    profileIsPrivate.value = userData.isPrivate
    profileusername.value = userData.name
    profileBio.value = userData.bio
    profileAvatar.value = userData.avatar
    profileRole.value = userData.role

    await loadCollection()
})

watch(activeFilter, (filter) => {
    loadCollection(filter === 'Visi' ? null : filter)
})

watch(sortOrder, () => {
    loadCollection(activeFilter.value === 'Visi' ? null : activeFilter.value)
})


</script>


<template>
    <div class="profile-page">
        <div class="profile-wrapper">
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
                    <button v-if="auth.user && auth.user.id == route.params.id" class="btn-settings" @click="openSettings">Rediģēt profilu</button>
                    <button 
                        v-if="auth.user && auth.user.id == route.params.id && !auth.user.email_verified" 
                        class="btn-verify" 
                        :disabled="resendLoading"
                        @click="sendVerificationCode">
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
                        <button class="btn-sort" :class="{ 'active-sort': sortOrder }" @click="sortOrder = sortOrder === 'desc' ? 'asc' : 'desc'">
                            Vērtējums {{ sortOrder === 'desc' ? '↓' : '↑' }}
                        </button>

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
                    <div class="game-entry" v-for="item in collection" :key="item.id">
                        <img :src="item.game.image" :alt="item.game.name" class="entry-cover">
                        <div class="entry-info">
                            <div class="entry-name">{{ item.game.name }}</div>
                            <div class="entry-status" :style="{ color: statusMeta[item.status].color }">
                                <span class="status-dot" :style="{ background: statusMeta[item.status].color }"></span>
                                {{ statusMeta[item.status].label }}
                            </div>
                            <div class="entry-notes" v-if="item.notes">{{ item.notes }}</div>
                        </div>
                        <div class="entry-score" v-if="item.user_score !== null">
                            <span class="score-num">{{ item.user_score }}</span>
                            <span class="score-max">/10</span>
                        </div>
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
                                <img v-if="settingsAvatarUrl" :src="settingsAvatarUrl" class="avatar-img" @error="$event.target.src = 'https://placehold.co/150/212121/white?text=U'">
                                <div v-else class="avatar-placeholder">{{ initials }}</div>
                            </div>
                            <v-text-field
                                v-model="settingsAvatarUrl"
                                label="Avatāra URL"
                                placeholder="https://..."
                                maxlength="2048"
                                counter
                                variant="outlined"
                                density="compact"
                                style="flex: 1;"
                                :rules="avatarUrlRules"
                            ></v-text-field>
                        </div>
                    
                        <v-text-field
                            v-model="settingsUsername"
                            label="Lietotājvārds"
                            placeholder="Lietotājvārds"
                            maxlength="10"
                            counter
                            variant="outlined"
                            density="compact"
                            :rules="usernameRules"
                        ></v-text-field>
                    
                        <v-text-field
                            v-model="settingsEmail"
                            label="E-pasts"
                            placeholder="E-pasta adrese"
                            maxlength="255"
                            variant="outlined"
                            density="compact"
                            :rules="emailRules"
                        ></v-text-field>
                    
                        <v-textarea
                            v-model="settingsBio"
                            placeholder="Pastāsti kaut ko par sevi..."
                            maxlength="50"
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
                            maxlength="255"
                            variant="outlined"
                            density="compact"
                            :rules="passwordRules"
                            persistent-hint
                        ></v-text-field>
                    
                        <v-text-field
                            v-if="settingsPassword"
                            v-model="settingsPasswordConfirm"
                            label="Apstiprināt paroli"
                            placeholder="Atkārtojiet jauno paroli"
                            type="password"
                            maxlength="255"
                            variant="outlined"
                            density="compact"
                            :rules="passwordConfirmationRules"
                        ></v-text-field>
                    
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
                        :disabled="!settingsValid"
                    >
                        <p class="v-btn-text">Saglabāt</p>
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
        <v-dialog max-width="400" v-model="deleteConfirmation">
            <v-card class="dialog">
                <v-card-title class="v-card-title">Dzēst kontu</v-card-title>
                <v-card-text class="v-card-text">Dzēst savu kontu?</v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn class="v-dialog-button" @click="deleteConfirmation = false">
                        <p class="v-btn-text">Atcelt</p>
                    </v-btn>
                    <v-btn class="v-dialog-button btn-delete-confirm" @click="deleteUser">
                        <p class="v-btn-text">Dzēst</p>
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog max-width="500" v-model="verificationDialog" persistent>
            <v-card class="dialog">
                <v-card-title class="v-card-title">E-pasta verifikācija</v-card-title>
                <v-card-text class="v-card-text">
                    Uz tavu e-pastu nosūtīts 6 ciparu kods. Ievadi to zemāk.
                    <v-text-field
                        v-model="verificationCode"
                        label="Verifikācijas kods"
                        maxlength="6"
                        class="mt-4"
                        :error-messages="verificationError ? 'Nepareizs kods. Mēģini vēlreiz.' : ''"
                    ></v-text-field>
                </v-card-text>
                <v-card-actions>
                    <v-btn :loading="resendLoading" @click="resendCode" variant="text" style="color: rgba(255,255,255,0.5); font-size: 12px">
                        Nosūtīt vēlreiz
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

.btn-sort {
    background: transparent;
    border: 1px solid #2a2a2a;
    border-radius: 6px;
    color: #666;
    font-size: 11px;
    padding: 4px 12px;
    cursor: pointer;
    transition: color 0.15s, border-color 0.15s;
}

.btn-sort:hover {
    color: #ccc;
    border-color: #444;
}

.btn-sort.active-sort {
    color: #ffffff;
    border-color: #666;
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
    overflow: auto
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
    font-size: 13px;
    font-weight: 400;
    color: #efefef;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
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

.entry-notes {
    font-size: 11px;
    color: #555;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.entry-score {
    flex: 0 0 auto;
    text-align: right;
}

.score-num {
    font-size: 18px;
    font-weight: 500;
    color: #f5f5f5;
}

.score-max {
    font-size: 11px;
    color: #444;
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