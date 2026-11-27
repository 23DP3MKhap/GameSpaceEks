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
const gamesError = ref('')
const isSavingOrder = ref(false)
const dragIndex = ref(null)
const noteDialog = ref(false)
const noteGame = ref(null)
const noteText = ref('')
const noteError = ref('')
const isSavingNote = ref(false)
const removeDialog = ref(false)
const gameToRemove = ref(null)
const removeError = ref('')
const isRemoving = ref(false)
let orderBeforeDrag = []

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

function coverOf(game) {
    return game.cover_url ?? 'https://placehold.co/60x80/111/444?text=' + encodeURIComponent(game.title)
}

function gameIds() {
    return list.value.games.map(game => game.id)
}

function moveGame(from, to) {
    const games = list.value.games
    games.splice(to, 0, games.splice(from, 1)[0])
}

async function saveOrder(previousIds) {
    isSavingOrder.value = true
    gamesError.value = ''
    try {
        await axios.put(`/api/lists/${list.value.id}/order`, { game_ids: gameIds() })
    } catch {
        list.value.games.sort((a, b) => previousIds.indexOf(a.id) - previousIds.indexOf(b.id))
        gamesError.value = 'Neizdevās saglabāt secību. Mēģini vēlreiz.'
    } finally {
        isSavingOrder.value = false
    }
}

function shiftGame(index, offset) {
    const previousIds = gameIds()
    moveGame(index, index + offset)
    saveOrder(previousIds)
}

function onDragStart(index, event) {
    dragIndex.value = index
    orderBeforeDrag = gameIds()
    event.dataTransfer.effectAllowed = 'move'
    event.dataTransfer.setData('text/plain', '')
}

function onDragOver(index, event) {
    if (dragIndex.value === null || index === dragIndex.value) return

    const rect = event.currentTarget.getBoundingClientRect()
    const middle = rect.top + rect.height / 2
    if (index > dragIndex.value && event.clientY < middle) return
    if (index < dragIndex.value && event.clientY > middle) return

    moveGame(dragIndex.value, index)
    dragIndex.value = index
}

function onDragEnd() {
    dragIndex.value = null
    if (gameIds().some((id, index) => id !== orderBeforeDrag[index])) {
        saveOrder(orderBeforeDrag)
    }
}

function openNote(game) {
    noteGame.value = game
    noteText.value = game.pivot.note ?? ''
    noteError.value = ''
    noteDialog.value = true
}

async function saveNote() {
    isSavingNote.value = true
    noteError.value = ''
    try {
        await axios.put(`/api/lists/${list.value.id}/games/${noteGame.value.id}`, { note: noteText.value })
        noteGame.value.pivot.note = noteText.value.trim() || null
        noteDialog.value = false
    } catch {
        noteError.value = 'Neizdevās saglabāt piezīmi. Mēģini vēlreiz.'
    } finally {
        isSavingNote.value = false
    }
}

function openRemove(game) {
    gameToRemove.value = game
    removeError.value = ''
    removeDialog.value = true
}

async function removeGame() {
    isRemoving.value = true
    removeError.value = ''
    try {
        await axios.delete(`/api/lists/${list.value.id}/games/${gameToRemove.value.id}`)
        list.value.games = list.value.games.filter(game => game.id !== gameToRemove.value.id)
        removeDialog.value = false
    } catch {
        removeError.value = 'Neizdevās noņemt spēli. Mēģini vēlreiz.'
    } finally {
        isRemoving.value = false
    }
}
</script>


<template>
    <div class="list-page">
        <div class="list-wrapper">
            <div v-if="loadError" class="page-message">{{ loadError }}</div>

            <v-progress-linear v-else-if="!list" indeterminate color="white"></v-progress-linear>

            <template v-else>
                <section class="list-header">
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
    
                <section class="list-games">
                    <h2 class="section-title">Spēles ({{ list.games.length }})</h2>
    
                    <div v-if="gamesError" class="form-error games-error">{{ gamesError }}</div>
    
                    <div class="games-list">
                        <div
                            v-for="(game, index) in list.games"
                            :key="game.id"
                            class="game-row"
                            :class="{ 'is-draggable': isOwner, 'is-dragging': dragIndex === index }"
                            :draggable="isOwner && !isSavingOrder"
                            @dragstart="onDragStart(index, $event)"
                            @dragover.prevent="onDragOver(index, $event)"
                            @drop.prevent
                            @dragend="onDragEnd"
                        >
                            <span class="game-position">{{ index + 1 }}</span>
    
                            <router-link :to="`/Game/${game.id}`" draggable="false">
                                <img :src="coverOf(game)" :alt="game.title" class="game-cover" draggable="false">
                            </router-link>
    
                            <div class="game-info">
                                <router-link :to="`/Game/${game.id}`" class="game-title" draggable="false">{{ game.title }}</router-link>
                                <span v-if="game.release_date" class="game-year">{{ game.release_date.slice(0, 4) }}</span>
                                <p v-if="game.pivot.note" class="game-note">{{ game.pivot.note }}</p>
                            </div>
    
                            <div v-if="isOwner" class="game-controls">
                                <button class="control-btn" aria-label="Pārvietot uz augšu" :disabled="index === 0 || isSavingOrder" @click="shiftGame(index, -1)">↑</button>
                                <button class="control-btn" aria-label="Pārvietot uz leju" :disabled="index === list.games.length - 1 || isSavingOrder" @click="shiftGame(index, 1)">↓</button>
                                <button class="control-btn" @click="openNote(game)">Piezīme</button>
                                <button class="control-btn control-remove" @click="openRemove(game)">Noņemt</button>
                            </div>
                        </div>
    
                        <div v-if="list.games.length === 0" class="empty-state">
                            Sarakstā vēl nav spēļu.
                            <template v-if="isOwner">
                                Atver spēli <router-link to="/Catalog" class="empty-link">katalogā</router-link> un nospied „Pievienot sarakstam“.
                            </template>
                        </div>
                    </div>
                </section>
            </template>
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

        <v-dialog max-width="480" v-model="noteDialog">
            <v-card class="dialog">
                <v-card-title class="v-card-title">Piezīme</v-card-title>
                <v-card-text class="v-card-text">
                    <div class="note-game">{{ noteGame?.title }}</div>
                    <v-textarea
                        v-model="noteText"
                        label="Piezīme"
                        maxlength="250"
                        counter
                        variant="outlined"
                        density="compact"
                        rows="3"
                        no-resize
                    ></v-textarea>
                    <div v-if="noteError" class="form-error">{{ noteError }}</div>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn class="v-dialog-button" @click="noteDialog = false">
                        <p class="v-btn-text">Atcelt</p>
                    </v-btn>
                    <v-btn class="v-dialog-button btn-save" :loading="isSavingNote" @click="saveNote">
                        <p class="v-btn-text">Saglabāt</p>
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog max-width="400" v-model="removeDialog">
            <v-card class="dialog">
                <v-card-title class="v-card-title">Noņemt spēli?</v-card-title>
                <v-card-text class="v-card-text">
                    Spēle „{{ gameToRemove?.title }}“ tiks noņemta no saraksta.
                    <div v-if="removeError" class="form-error mt-2">{{ removeError }}</div>
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

.list-games {
    margin-top: 40px;
}

.section-title {
    font-size: 14px;
    font-weight: 500;
    color: #f5f5f5;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin: 0 0 16px;
}

.games-error {
    margin-bottom: 12px;
}

.games-list {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.game-row {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 10px 14px;
    background: #111;
    border: 1px solid #181818;
    border-radius: 10px;
    transition: border-color 0.15s, background 0.15s, opacity 0.15s;
}

.game-row:hover {
    background: #141414;
    border-color: #222;
}

.is-draggable {
    cursor: grab;
}

.is-dragging {
    opacity: 0.4;
    border-style: dashed;
    border-color: #444;
}

.game-position {
    flex: 0 0 24px;
    text-align: right;
    font-size: 13px;
    color: #888;
    font-variant-numeric: tabular-nums;
}

.game-cover {
    display: block;
    width: 40px;
    height: 54px;
    object-fit: cover;
    border-radius: 4px;
}

.game-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 2px;
}

.game-title {
    font-size: 14px;
    color: #efefef;
    text-decoration: none;
    overflow-wrap: anywhere;
    transition: color 0.15s;
}

.game-title:hover {
    color: #4a9eff;
}

.game-year {
    font-size: 11px;
    color: #888;
}

.game-note {
    margin: 4px 0 0;
    font-size: 12px;
    line-height: 1.5;
    color: #aaa;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
}

.game-controls {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    gap: 4px;
}

.control-btn {
    min-width: 30px;
    height: 30px;
    padding: 0 8px;
    background: transparent;
    border: 1px solid #262626;
    border-radius: 6px;
    color: #bbb;
    font-size: 12px;
    cursor: pointer;
    transition: color 0.15s, border-color 0.15s;
}

.control-btn:hover:not(:disabled) {
    color: #fff;
    border-color: #444;
}

.control-btn:disabled {
    opacity: 0.3;
    cursor: default;
}

.control-remove {
    color: #ff5f5f;
    border-color: rgba(255, 95, 95, 0.25);
}

.control-remove:hover:not(:disabled) {
    color: #ff5f5f;
    border-color: rgba(255, 95, 95, 0.6);
}

.empty-state {
    text-align: center;
    color: #888;
    font-size: 13px;
    line-height: 1.6;
    padding: 40px 0;
}

.empty-link {
    color: #4a9eff;
    text-decoration: none;
}

.empty-link:hover {
    text-decoration: underline;
}

.note-game {
    font-size: 13px;
    color: #eee;
    margin-bottom: 12px;
    overflow-wrap: anywhere;
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

    .game-row {
        flex-wrap: wrap;
        gap: 10px;
        padding: 10px;
    }

    .game-position {
        flex-basis: 20px;
    }

    .game-controls {
        flex-basis: 100%;
        justify-content: flex-end;
    }

    .control-btn {
        min-width: 36px;
        height: 34px;
    }
}
</style>
