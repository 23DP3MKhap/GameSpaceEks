<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from '../plugins/axios'
import { auth } from '../plugins/userinfo'

const route = useRoute()
const router = useRouter()

const list = ref(null)
const loadError = ref('')
const editDialog = ref(false)
const editForm = ref(null)
const editTitle = ref('')
const editDescription = ref('')
const editIsPublic = ref(true)
const editError = ref('')
const isSaving = ref(false)
const deleteDialog = ref(false)
const deleteError = ref('')
const isDeleting = ref(false)

const isOwner = computed(() => auth.user?.id === list.value.user_id)

const titleRules = [
    value => !!value?.trim() || 'Nosaukumam jābūt no 1 līdz 100 rakstzīmēm.',
]

onMounted(async () => {
    try {
        list.value = (await axios.get(`/api/lists/${route.params.id}`)).data
    } catch (error) {
        loadError.value = error.response?.status === 404
            ? 'Saraksts nav atrasts.'
            : 'Neizdevās ielādēt sarakstu. Mēģini vēlreiz.'
    }
})

function formatDate(date) {
    return date.slice(0, 10).split('-').reverse().join('.')
}

function openEdit() {
    editTitle.value = list.value.title
    editDescription.value = list.value.description ?? ''
    editIsPublic.value = list.value.is_public
    editError.value = ''
    editDialog.value = true
}

async function saveList() {
    const { valid } = await editForm.value.validate()
    if (!valid) return

    isSaving.value = true
    editError.value = ''
    try {
        const response = await axios.put(`/api/lists/${list.value.id}`, {
            title: editTitle.value,
            description: editDescription.value,
            is_public: editIsPublic.value,
        })
        Object.assign(list.value, response.data)
        editDialog.value = false
    } catch (error) {
        editError.value = error.response?.status === 422
            ? 'Norādīti nederīgi dati'
            : 'Neizdevās saglabāt sarakstu. Mēģini vēlreiz.'
    } finally {
        isSaving.value = false
    }
}

function openDelete() {
    deleteError.value = ''
    deleteDialog.value = true
}

async function deleteList() {
    isDeleting.value = true
    deleteError.value = ''
    try {
        await axios.delete(`/api/lists/${list.value.id}`)
        router.push(`/User/${list.value.user_id}/Profile`)
    } catch {
        deleteError.value = 'Neizdevās dzēst sarakstu. Mēģini vēlreiz.'
    } finally {
        isDeleting.value = false
    }
}
</script>


<template>
    <div class="list-page">
        <div class="list-wrapper">
            <div v-if="loadError" class="page-message">{{ loadError }}</div>

            <v-progress-linear v-else-if="!list" indeterminate color="white"></v-progress-linear>

            <section v-else class="list-header">
                <span class="list-badge" :class="{ 'badge-private': !list.is_public }">
                    {{ list.is_public ? 'Publisks saraksts' : 'Privāts saraksts' }}
                </span>

                <h1 class="list-title">{{ list.title }}</h1>

                <div class="list-meta">
                    <router-link :to="`/User/${list.user.id}/Profile`" class="author-link">
                        <img v-if="list.user.avatar_url" :src="list.user.avatar_url" class="author-avatar" alt="Lietotāja attēls">
                        <span v-else class="author-avatar author-avatar-placeholder">{{ list.user.username[0].toUpperCase() }}</span>
                        {{ list.user.username }}
                    </router-link>
                    <span class="list-date">Atjaunināts {{ formatDate(list.updated_at) }}</span>
                </div>

                <p v-if="list.description" class="list-description">{{ list.description }}</p>

                <div v-if="isOwner" class="list-actions">
                    <v-btn class="btn-edit" @click="openEdit">Rediģēt</v-btn>
                    <v-btn class="btn-delete" @click="openDelete">Dzēst</v-btn>
                </div>
            </section>
        </div>

        <v-dialog max-width="480" v-model="editDialog">
            <v-card class="dialog">
                <v-card-title class="v-card-title">Rediģēt sarakstu</v-card-title>
                <v-card-text class="v-card-text">
                    <v-form ref="editForm" @submit.prevent="saveList">
                        <v-text-field
                            v-model="editTitle"
                            label="Nosaukums"
                            maxlength="100"
                            counter
                            variant="outlined"
                            density="compact"
                            :rules="titleRules"
                        ></v-text-field>

                        <v-textarea
                            v-model="editDescription"
                            label="Apraksts"
                            maxlength="500"
                            counter
                            variant="outlined"
                            density="compact"
                            rows="3"
                            no-resize
                        ></v-textarea>

                        <div class="visibility-row">
                            <span class="visibility-label">Publisks saraksts</span>
                            <v-switch v-model="editIsPublic" color="white" density="compact" hide-details></v-switch>
                        </div>

                        <div v-if="editError" class="form-error">{{ editError }}</div>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn class="v-dialog-button" @click="editDialog = false">
                        <p class="v-btn-text">Atcelt</p>
                    </v-btn>
                    <v-btn class="v-dialog-button btn-save" :loading="isSaving" @click="saveList">
                        <p class="v-btn-text">Saglabāt</p>
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog max-width="400" v-model="deleteDialog">
            <v-card class="dialog">
                <v-card-title class="v-card-title">Dzēst sarakstu?</v-card-title>
                <v-card-text class="v-card-text">
                    Saraksts „{{ list?.title }}“ tiks neatgriezeniski dzēsts.
                    <div v-if="deleteError" class="form-error mt-2">{{ deleteError }}</div>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn class="v-dialog-button" @click="deleteDialog = false">
                        <p class="v-btn-text">Atcelt</p>
                    </v-btn>
                    <v-btn class="v-dialog-button btn-delete-confirm" :loading="isDeleting" @click="deleteList">
                        <p class="v-btn-text">Dzēst</p>
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>


<style scoped>
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

.list-page {
    min-height: 100vh;
    background: #0a0a0a;
    color: #e8e8e8;
}

.list-wrapper {
    max-width: 960px;
    margin: 0 auto;
    padding: 40px 24px 60px;
}

.page-message {
    text-align: center;
    color: #888;
    font-size: 15px;
    padding: 80px 0;
}

.list-header {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 14px;
}

.list-badge {
    font-size: 10px;
    font-weight: 500;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    padding: 2px 8px;
    border-radius: 4px;
    background: rgba(56, 239, 125, 0.08);
    color: #38ef7d;
    border: 1px solid rgba(56, 239, 125, 0.2);
}

.badge-private {
    background: rgba(255, 255, 255, 0.05);
    color: #888;
    border-color: rgba(255, 255, 255, 0.1);
}

.list-title {
    font-size: 28px;
    font-weight: 500;
    line-height: 1.2;
    color: #f5f5f5;
    margin: 0;
    overflow-wrap: anywhere;
}

.list-meta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    font-size: 13px;
}

.author-link {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #fff;
    text-decoration: none;
    transition: color 0.15s;
}

.author-link:hover {
    color: #4a9eff;
}

.author-avatar {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    object-fit: cover;
}

.author-avatar-placeholder {
    background: #222;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 500;
    color: #666;
}

.list-date {
    color: #666;
}

.list-description {
    font-size: 14px;
    line-height: 1.7;
    color: #aaa;
    margin: 0;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
}

.list-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.btn-edit {
    background: #fff;
    color: #000;
    text-transform: none;
    font-weight: 600;
    border-radius: 8px;
}

.btn-delete {
    background: transparent;
    color: #ff5555;
    border: 1px solid #ff555533;
    text-transform: none;
    font-weight: 500;
    border-radius: 8px;
}

.form-error {
    font-size: 12px;
    color: #ff5f5f;
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

.v-dialog-button:hover {
    background-color: rgba(255, 255, 255, 0.06);
    border-color: rgba(255, 255, 255, 0.25);
    transition: background-color 0.15s, border-color 0.15s;
}

.v-btn-text {
    color: white;
    font-size: 10px;
    margin: 0;
}

.visibility-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 0;
    border-top: 1px solid #1a1a1a;
}

.visibility-label {
    font-size: 11px;
    color: #555;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.btn-save {
    border-color: rgba(74, 158, 255, 0.3);
    color: #4a9eff;
}

.btn-save:hover {
    background-color: rgba(74, 158, 255, 0.1);
    border-color: rgba(74, 158, 255, 0.5);
}

.btn-delete-confirm {
    border-color: rgba(255, 95, 95, 0.3);
}

.btn-delete-confirm:hover {
    background-color: rgba(255, 95, 95, 0.12);
    border-color: rgba(255, 95, 95, 0.6);
}

@media (max-width: 600px) {
    .list-wrapper {
        padding: 20px 14px 40px;
    }

    .list-title {
        font-size: 22px;
    }

    .list-actions > * {
        flex: 1;
    }
}
</style>
