<script setup>
import { ref, onMounted } from 'vue'
import axios from '../plugins/axios'
import { auth } from '../plugins/userinfo'
import { useRouter } from 'vue-router'

const router = useRouter()

const users = ref([])
const games = ref([])
const reviews = ref([])

const editUserDialog = ref(false)
const editGameDialog = ref(false)

const editingUser = ref({})
const editingGame = ref({})

const confirmDialog = ref(false)
const confirmAction = ref(null)
const confirmMessage = ref('')

onMounted(async () => {
    if (!auth.user || auth.user.role !== 'admin') {
        router.push('/')
        return
    }
    await loadAll()
})

async function loadAll() {
    users.value = (await axios.get('/api/admin/users')).data
    games.value = (await axios.get('/api/admin/games')).data
    reviews.value = (await axios.get('/api/admin/reviews')).data
}

function openEditUser(user) {
    editingUser.value = { ...user }
    editUserDialog.value = true
}

async function saveUser() {
    
    await axios.put(`/api/admin/users/${editingUser.value.id}`, editingUser.value)
    editUserDialog.value = false
    await loadAll()
}

function openEditGame(game) {
    editingGame.value = { ...game }
    editGameDialog.value = true
}

async function saveGame() {
    
    await axios.put(`/api/admin/games/${editingGame.value.id}`, editingGame.value)
    editGameDialog.value = false
    await loadAll()
}

function confirm(message, action) {
    confirmMessage.value = message
    confirmAction.value = action
    confirmDialog.value = true
}

async function runConfirm() {
    await confirmAction.value()
    confirmDialog.value = false
}

async function deleteUser(id) {
    
    await axios.delete(`/api/admin/users/${id}`)
    await loadAll()
}

async function deleteGame(id) {
    
    await axios.delete(`/api/admin/games/${id}`)
    await loadAll()
}

async function deleteReview(id) {
    
    await axios.delete(`/api/admin/reviews/${id}`)
    await loadAll()
}

async function deleteUserCollection(userId) {
    
    await axios.delete(`/api/admin/users/${userId}/collection`)
    await loadAll()
}
</script>

<template>
    <div class="page">
        <div class="wrapper">
            <h1 class="admin-title">Administratora Panelis</h1>

            <v-expansion-panels variant="accordion" class="panels">

                <v-expansion-panel>
                    <v-expansion-panel-title class="panel-title">
                        Lietotāji <span class="count">{{ users.length }}</span>
                    </v-expansion-panel-title>
                    <v-expansion-panel-text class="panel-text">
                        <div class="table-wrap">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Vārds</th>
                                        <th>E-pasts</th>
                                        <th>Loma</th>
                                        <th>Darbības</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="user in users" :key="user.id">
                                        <td>
                                            <a class="user-link" @click="router.push(`/User/${user.id}/Profile`)">
                                                {{ user.id }}
                                            </a>
                                        </td>
                                        <td>{{ user.name }}</td>
                                        <td>{{ user.email }}</td>
                                        <td>
                                            <span class="role-badge" :class="user.role === 'admin' ? 'role-admin' : 'role-user'">
                                                {{ user.role }}
                                            </span>
                                        </td>
                                        <td class="actions">
                                            <button class="btn-edit" @click="openEditUser(user)">Rediģēt</button>
                                            <button class="btn-warn" @click="confirm(`Notīrīt kolekciju lietotājam ${user.name}`, function(){deleteUserCollection(user.id)})">Notīrīt Kolekciju</button>
                                            <button class="btn-delete" @click="confirm(`Dzēst lietotāju ${user.name}`, function(){deleteUser(user.id)})">Dzēst</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </v-expansion-panel-text>
                </v-expansion-panel>

                <v-expansion-panel>
                    <v-expansion-panel-title class="panel-title">
                        Spēles <span class="count">{{ games.length }}</span>
                    </v-expansion-panel-title>
                    <v-expansion-panel-text class="panel-text">
                        <div class="table-wrap">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nosaukums</th>
                                        <th>Izstrādātājs</th>
                                        <th>Izdevējs</th>
                                        <th>Vērtējums</th>
                                        <th>Darbības</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="game in games" :key="game.id">
                                        <td>{{ game.id }}</td>
                                        <td>{{ game.name }}</td>
                                        <td>{{ game.developer || '-' }}</td>
                                        <td>{{ game.publisher || '-' }}</td>
                                        <td>{{ game.rating ? game.rating : '-' }}</td>
                                        <td class="actions">
                                            <button class="btn-edit" @click="openEditGame(game)">Rediģēt</button>
                                            <button class="btn-delete" @click="confirm(`Dzēst spēli ${game.name}?`, function(){deleteGame(game.id)})">Dzēst</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </v-expansion-panel-text>
                </v-expansion-panel>

                <v-expansion-panel>
                    <v-expansion-panel-title class="panel-title">
                        Atsauksmes <span class="count">{{ reviews.length }}</span>
                    </v-expansion-panel-title>
                    <v-expansion-panel-text class="panel-text">
                        <div class="table-wrap">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Lietotājs</th>
                                        <th>Spēle</th>
                                        <th>Virsraksts</th>
                                        <th>Saturs</th>
                                        <th>Vērtējums</th>
                                        <th>Darbības</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="review in reviews" :key="review.id">
                                        <td>{{ review.id }}</td>
                                        <td>{{ review.user?.name || '-' }}</td>
                                        <td>{{ review.game?.name || '-' }}</td>
                                        <td>{{ review.title }}</td>
                                        <td class="content-cell">{{ review.content }}</td>
                                        <td>{{ review.rating }}/10</td>
                                        <td class="actions">
                                            <button class="btn-delete" @click="confirm(`Dzēst atsauksmi no ${review.user?.name}?`, function(){deleteReview(review.id)})">Dzēst</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </v-expansion-panel-text>
                </v-expansion-panel>

            </v-expansion-panels>
        </div>

        <v-dialog max-width="480" v-model="editUserDialog">
            <v-card class="dialog">
                <v-card-title class="v-card-title">Rediģēt Lietotāju</v-card-title>
                <v-card-text class="v-card-text">
                    <div class="edit-field">
                        <label class="edit-label">Vārds</label>
                        <input class="edit-input" v-model="editingUser.name">
                    </div>
                    <div class="edit-field">
                        <label class="edit-label">E-pasts</label>
                        <input class="edit-input" v-model="editingUser.email">
                    </div>
                    <div class="edit-field">
                        <label class="edit-label">Biogrāfija</label>
                        <textarea class="edit-input edit-textarea" v-model="editingUser.bio"></textarea>
                    </div>
                    <div class="edit-field">
                        <label class="edit-label">Avatara URL</label>
                        <input class="edit-input" v-model="editingUser.avatar">
                    </div>
                    <div class="edit-field">
                        <label class="edit-label">Loma</label>
                        <select class="edit-input" v-model="editingUser.role">
                            <option value="user">Lietotājs</option>
                            <option value="admin">Administrators</option>
                        </select>
                    </div>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn class="v-dialog-button" @click="editUserDialog = false">
                        <p class="v-btn-text">Atcelt</p>
                    </v-btn>
                    <v-btn class="v-dialog-button btn-save" @click="saveUser">
                        <p class="v-btn-text">Saglabāt</p>
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog max-width="480" v-model="editGameDialog">
            <v-card class="dialog">
                <v-card-title class="v-card-title">Rediģēt Spēli</v-card-title>
                <v-card-text class="v-card-text">
                    <div class="edit-field">
                        <label class="edit-label">Nosaukums</label>
                        <input class="edit-input" v-model="editingGame.name">
                    </div>
                    <div class="edit-field">
                        <label class="edit-label">Izstrādātājs</label>
                        <input class="edit-input" v-model="editingGame.developer">
                    </div>
                    <div class="edit-field">
                        <label class="edit-label">Izdevējs</label>
                        <input class="edit-input" v-model="editingGame.publisher">
                    </div>
                    <div class="edit-field">
                        <label class="edit-label">Iznākšanas Datums</label>
                        <input class="edit-input" type="date" v-model="editingGame.release_date">
                    </div>
                    <div class="edit-field">
                        <label class="edit-label">Vērtējums</label>
                        <input class="edit-input" type="number" min="0" max="100" v-model="editingGame.rating">
                    </div>
                    <div class="edit-field">
                        <label class="edit-label">Vāka URL</label>
                        <input class="edit-input" v-model="editingGame.cover_url">
                    </div>
                    <div class="edit-field">
                        <label class="edit-label">Apraksts</label>
                        <textarea class="edit-input edit-textarea" v-model="editingGame.description"></textarea>
                    </div>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn class="v-dialog-button" @click="editGameDialog = false">
                        <p class="v-btn-text">Atcelt</p>
                    </v-btn>
                    <v-btn class="v-dialog-button btn-save" @click="saveGame">
                        <p class="v-btn-text">Saglabāt</p>
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog max-width="400" v-model="confirmDialog">
            <v-card class="dialog">
                <v-card-title class="v-card-title">Apstiprināt</v-card-title>
                <v-card-text class="v-card-text">{{ confirmMessage }}</v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn class="v-dialog-button" @click="confirmDialog = false">
                        <p class="v-btn-text">Atcelt</p>
                    </v-btn>
                    <v-btn class="v-dialog-button btn-delete-confirm" @click="runConfirm">
                        <p class="v-btn-text">Apstiprināt</p>
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<style scoped>
.page {
    min-height: 100vh;
    background: #0a0a0a;
    color: #e8e8e8;
}

.wrapper {
    max-width: 1100px;
    margin: 0 auto;
    padding: 40px 24px;
}

.admin-title {
    font-size: 22px;
    font-weight: 500;
    color: #f5f5f5;
    margin-bottom: 24px;
}

.panels {
    background: transparent;
}

.panel-title {
    background: #111 !important;
    color: #eee !important;
    font-size: 13px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.count {
    margin-left: 10px;
    font-size: 11px;
    color: #555;
}

.panel-text {
    background: #0d0d0d !important;
    color: #ccc !important;
}

.table-wrap {
    overflow-x: auto;
}

.table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}

.table th {
    text-align: left;
    padding: 10px 12px;
    color: #555;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 1px solid #1a1a1a;
}

.table td {
    padding: 10px 12px;
    color: #ccc;
    border-bottom: 1px solid #111;
}

.table tr:hover td {
    background: #141414;
}

.content-cell {
    max-width: 200px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.actions {
    display: flex;
    gap: 8px;
    align-items: center;
}

.user-link {
    color: #4a9eff;
    cursor: pointer;
    font-size: 12px;
    text-decoration: none;
}

.user-link:hover {
    text-decoration: underline;
}

.btn-edit {
    background: transparent;
    border: 1px solid #2a2a2a;
    border-radius: 5px;
    color: #888;
    font-size: 11px;
    padding: 3px 10px;
    cursor: pointer;
    transition: color 0.15s, border-color 0.15s;
}

.btn-edit:hover {
    color: #fff;
    border-color: #444;
}

.btn-warn {
    background: transparent;
    border: 1px solid rgba(255, 180, 0, 0.2);
    border-radius: 5px;
    color: #ffb400;
    font-size: 11px;
    padding: 3px 10px;
    cursor: pointer;
    opacity: 0.7;
    transition: opacity 0.15s;
}

.btn-warn:hover {
    opacity: 1;
}

.btn-delete {
    background: transparent;
    border: 1px solid rgba(255, 85, 85, 0.2);
    border-radius: 5px;
    color: #ff5555;
    font-size: 11px;
    padding: 3px 10px;
    cursor: pointer;
    opacity: 0.7;
    transition: opacity 0.15s;
}

.btn-delete:hover {
    opacity: 1;
}

.role-badge {
    font-size: 10px;
    padding: 2px 7px;
    border-radius: 4px;
    font-weight: 500;
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

.dialog {
    background-color: #000;
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
    background-color: #000;
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

.btn-delete-confirm {
    border-color: rgba(255, 85, 85, 0.3);
    color: #ff5555;
}

.edit-field {
    display: flex;
    flex-direction: column;
    gap: 5px;
    margin-bottom: 14px;
}

.edit-label {
    font-size: 11px;
    color: #555;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.edit-input {
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

.edit-input:focus {
    border-color: #444;
}

.edit-textarea {
    resize: none;
    min-height: 80px;
}
</style>