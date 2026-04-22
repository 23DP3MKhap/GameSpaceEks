<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import axios from '../plugins/axios'
import { useRoute } from 'vue-router'
import { auth } from '../plugins/userinfo'

const route = useRoute()

const profileusername = ref("placeholder_user")
const profileBio = ref("No bio yet.")
const profileRole = ref("user")
const profileAvatar = ref(null)

const collection = ref([])

const activeFilter = ref('all')

const settingsPasswordConfirm = ref('')
const settingsDialog = ref(false)
const settingsUsername = ref('')
const settingsPassword = ref('')
const settingsBio = ref('')
const settingsAvatarUrl = ref('')
const settingsUsernameValid = ref(null)
const settingsPasswordValid = computed(() => {
    if (!settingsPassword.value) return null
    if (settingsPassword.value.length < 8) return false
    return settingsPassword.value === settingsPasswordConfirm.value
})

const filters = [
    { key: 'all',       label: 'All' },
    { key: 'playing',   label: 'Playing' },
    { key: 'completed', label: 'Completed' },
    { key: 'planned',   label: 'Planned' },
    { key: 'dropped',   label: 'Dropped' },
]

const statusMeta = {
    playing:   { color: '#38ef7d', label: 'Playing' },
    completed: { color: '#4a9eff', label: 'Completed' },
    planned:   { color: '#aaa',    label: 'Planned' },
    dropped:   { color: '#ff5f5f', label: 'Dropped' },
}

const filteredCollection = computed(() => {
    if (activeFilter.value === 'all') return collection.value
    return collection.value.filter(item => item.status === activeFilter.value)
})

const counts = computed(() => {
    const c = { all: collection.value.length }
    filters.slice(1).forEach(f => {
        c[f.key] = collection.value.filter(i => i.status === f.key).length
    })
    return c
})

const initials = computed(() => {
    return profileusername.value.slice(0, 2).toUpperCase()
})

function openSettings() {
    settingsUsername.value = profileusername.value
    settingsBio.value = profileBio.value
    settingsPassword.value = ''
    settingsAvatarUrl.value = profileAvatar.value || ''
    settingsDialog.value = true
}

async function saveSettings() {

    if (settingsUsernameValid.value === false) return
    if (settingsPasswordValid.value === false) return
    
    await axios.get('/sanctum/csrf-cookie')
    await axios.post('/api/user/update', {
        username: settingsUsername.value,
        bio: settingsBio.value,
        avatar_url: settingsAvatarUrl.value,
        password: settingsPassword.value || undefined,
    })

    profileusername.value = settingsUsername.value
    profileBio.value = settingsBio.value
    profileAvatar.value = settingsAvatarUrl.value || null
    settingsDialog.value = false
}

onMounted(async () => {
    const userData = (await axios.get("/api/getuser", { params: { id: route.params.id } })).data
    profileusername.value = userData.name
    profileBio.value = userData.bio
    profileAvatar.value = userData.avatar
    profileRole.value = userData.role

    collection.value = (await axios.get("/api/user/collection", { params: { user_id: route.params.id } })).data
})

watch(settingsUsername, async (newUsername) => {
    if (!newUsername || newUsername === profileusername.value){
        settingsUsernameValid.value = null
        return
    }

    const res = await axios.post('/api/usernamecheck', { username: newUsername })

    if (res.data.exists) {
        settingsUsernameValid.value = false
    } else {
        settingsUsernameValid.value = true
    }

})
</script>


<template>
    <div class="profile-page">
        <div class="profile-wrapper">
            <div class="profile-header">
                <div class="avatar-wrap">
                    <img v-if="profileAvatar" :src="profileAvatar" class="avatar-img" />
                    <div v-else class="avatar-placeholder">{{ initials }}</div>
                </div>

                <div class="profile-meta">
                    <div class="profile-name">{{ profileusername }}</div>
                    <div class="profile-role-badge" :class="profileRole === 'admin' ? 'role-admin' : 'role-user'">
                        {{ profileRole === 'admin' ? 'Admin' : 'User' }}
                    </div>
                    <div class="profile-bio">{{ profileBio }}</div>
                    <button
                        v-if="auth.user && auth.user.id == route.params.id"
                        class="btn-settings"
                        @click="openSettings"
                    >
                        Edit Profile
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
                    <h2 class="section-title">Collection</h2>
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
                    <div
                        class="game-entry"
                        v-for="item in filteredCollection"
                        :key="item.id"
                    >
                        <img :src="item.game.image" :alt="item.game.name" class="entry-cover" />
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

                    <div v-if="filteredCollection.length === 0" class="empty-state">
                        Nothing here yet.
                    </div>
                </div>
            </div>
        </div>

        <v-dialog max-width="480" v-model="settingsDialog">
            <v-card class="dialog">
                <v-card-title class="v-card-title">Edit Profile</v-card-title>
                <v-card-text class="v-card-text">

                    <div class="settings-avatar-row">
                        <div class="settings-avatar-preview">
                            <img v-if="settingsAvatarUrl" :src="settingsAvatarUrl" class="avatar-img" />
                            <div v-else class="avatar-placeholder">{{ initials }}</div>
                        </div>
                        <div class="settings-field" style="flex: 1; margin-bottom: 0;">
                            <label class="settings-label">Avatar URL</label>
                            <input class="settings-input" v-model="settingsAvatarUrl" placeholder="https://..." />
                        </div>
                    </div>

                    <div class="settings-field">
                        <label class="settings-label">Username</label>
                        <input class="settings-input" v-model="settingsUsername" placeholder="Username" />
                        <span v-if="settingsUsernameValid === false" class="field-hint hint-error">Username is already taken</span>
                        <span v-if="settingsUsernameValid === true" class="field-hint hint-ok">Username is available</span>
                    </div>

                    <div class="settings-field">
                        <label class="settings-label">Bio</label>
                        <textarea
                            class="settings-input settings-textarea"
                            v-model="settingsBio"
                            placeholder="Tell something about yourself..."
                            rows="3"
                        ></textarea>
                    </div>

                    <div class="settings-field">
                        <label class="settings-label">New Password</label>
                        <input class="settings-input" v-model="settingsPassword" type="password" placeholder="Leave empty to keep current" />
                    </div>

                    <div class="settings-field" v-if="settingsPassword">
                        <label class="settings-label">Confirm Password</label>
                        <input class="settings-input" v-model="settingsPasswordConfirm" type="password" placeholder="Repeat new password" />
                        <span v-if="settingsPassword.length < 8" class="field-hint hint-error">Password must be at least 8 characters</span>
                        <span v-else-if="settingsPasswordValid === false" class="field-hint hint-error">Passwords do not match</span>
                        <span v-else-if="settingsPasswordValid === true" class="field-hint hint-ok">Passwords match</span>
                    </div>

                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn class="v-dialog-button" @click="settingsDialog = false">
                        <p class="v-btn-text">Cancel</p>
                    </v-btn>
                    <v-btn
                        class="v-dialog-button btn-save"
                        @click="saveSettings"
                        :disabled="settingsUsernameValid === false || settingsPasswordValid === false"
                    >
                        <p class="v-btn-text">Save</p>
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>


<style scoped>

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