<script setup>
    import { ref, reactive, computed, watch, onMounted } from 'vue'
    import { useRoute, useRouter } from 'vue-router'
    import axios from '../plugins/axios'
    import { auth } from '../plugins/userinfo'

    const route = useRoute()
    const router = useRouter()

    const game = ref(null)
    const loadError = ref('')
    const reviews = ref([])
    const reviewTitle = ref('')
    const reviewText = ref('')
    const reviewError = ref('')
    const reviewErrors = ref({})
    const reviewDialog = ref(false)
    const entry = ref(null)
    const form = reactive({})
    const showCollectionForm = ref(false)
    const collectionError = ref('')
    const collectionMessage = ref('')
    const collectionDialog = ref(false)
    const removeDialog = ref(false)
    const today = new Date().toISOString().slice(0, 10)

    const statusVariants = [
        { value: 'playing', label: 'Spēlē' },
        { value: 'completed', label: 'Pabeigta' },
        { value: 'planned', label: 'Plānota' },
        { value: 'dropped', label: 'Pārtraukta' },
    ]

    const emptyEntry = {
        status: 'planned',
        rating: null,
        note: '',
        hours_played: null,
        completion_percent: null,
        completed_at: null,
    }

    const reviewFieldErrors = {
        title: 'Virsrakstam jābūt no 1 līdz 50 rakstzīmēm.',
        content: 'Atsauksmes tekstam jābūt no 10 līdz 500 rakstzīmēm.',
    }

    const userReview = computed(() => reviews.value.find(review => review.user.id === auth.user?.id) ?? null)

    const sortedReviews = computed(() => {
        if (!userReview.value) return reviews.value
        return [userReview.value, ...reviews.value.filter(review => review !== userReview.value)]
    })

    const facts = computed(() => [
        { label: 'Izstrādātājs', value: game.value.developer || 'Nav zināms' },
        { label: 'Izdevējs', value: game.value.publisher || 'Nav zināms' },
        { label: 'Izdošanas datums', value: game.value.release_date ? formatDate(game.value.release_date) : 'Nav zināms' },
        { label: 'Žanri', value: game.value.genres.join(', ') || 'Nav zināms' },
        { label: 'Platformas', value: game.value.platforms.join(', ') || 'Nav zināms' },
    ])

    const entryFacts = computed(() => [
        { label: 'Statuss', value: statusVariants.find(status => status.value === entry.value.status).label },
        { label: 'Nospēlētās stundas', value: isEmpty(entry.value.hours_played) ? 'Nav' : entry.value.hours_played + ' h' },
        { label: 'Pabeigšanas procents', value: isEmpty(entry.value.completion_percent) ? 'Nav' : entry.value.completion_percent + ' %' },
        { label: 'Pabeigšanas datums', value: isEmpty(entry.value.completed_at) ? 'Nav' : formatDate(entry.value.completed_at) },
        { label: 'Piezīme', value: entry.value.note || 'Nav' },
    ])

    watch(userReview, (review) => {
        reviewTitle.value = review?.title ?? ''
        reviewText.value = review?.content ?? ''
    })

    onMounted(async () => {
        try {
            const response = await axios.get(`/api/games/${route.params.id}`)
            game.value = response.data
        } catch (error) {
            loadError.value = error.response?.status === 404
                ? 'Spēle netika atrasta'
                : 'Neizdevās ielādēt spēli. Mēģini vēlreiz.'
            return
        }

        await Promise.all([getReviews(), loadEntry()])

        if (route.query.collection) {
            toggleCollectionForm()
        }
    })

    function isEmpty(value) {
        return value === null || value === ''
    }

    function formatDate(date) {
        return date.split('-').reverse().join('.')
    }

    function coverFallback(event) {
        event.target.src = 'https://placehold.co/264x352/111/444?text=' + encodeURIComponent(game.value.title)
    }

    async function getReviews() {
        const response = await axios.get('/api/database/getreviews', { params: { game_id: game.value.id } })
        reviews.value = response.data
    }

    async function loadEntry() {
        if (!auth.user) return

        const response = await axios.get('/api/database/checkcollection', { params: { game_id: game.value.id } })
        const { exists, ...fields } = response.data
        entry.value = exists ? fields : null
    }

    function toggleCollectionForm() {
        if (!auth.user) {
            router.push({ path: '/Login', query: { notice: 'collection' } })
            return
        }
        if (!auth.user.email_verified) {
            collectionError.value = 'Apstiprini savu e-pasta adresi, lai izmantotu šo funkciju'
            return
        }

        collectionError.value = ''
        Object.assign(form, emptyEntry, entry.value ?? {})
        showCollectionForm.value = !showCollectionForm.value
    }

    async function saveEntry() {
        collectionError.value = ''
        const saved = { ...form, rating: form.rating || null }

        try {
            await axios.post('/api/database/addgame', { igdb_id: game.value.id })
            const response = await axios.post('/api/database/addtocollection', { game_id: game.value.id, ...saved })
            Object.assign(game.value, response.data)
        } catch (error) {
            collectionError.value = error.response?.status === 422
                ? 'Norādīti nederīgi dati'
                : 'Neizdevās saglabāt kolekcijā. Mēģini vēlreiz.'
            return
        }

        collectionMessage.value = entry.value ? 'Kolekcijas ieraksts atjaunināts' : 'Spēle pievienota kolekcijai'
        collectionDialog.value = true
        entry.value = saved
        showCollectionForm.value = false
    }

    async function removeEntry() {
        const response = await axios.post('/api/database/removefromcollection', { game_id: game.value.id })
        Object.assign(game.value, response.data)
        entry.value = null
        showCollectionForm.value = false
        removeDialog.value = false
    }

    async function postReview() {
        reviewError.value = ''
        reviewErrors.value = {}

        try {
            await axios.post('/api/database/addgame', { igdb_id: game.value.id })
            await axios.post('/api/database/addreview', {
                game_id: game.value.id,
                title: reviewTitle.value,
                content: reviewText.value,
            })
        } catch (error) {
            const fields = Object.keys(error.response?.data?.errors ?? {})
            const known = fields.filter(field => reviewFieldErrors[field])

            if (error.response?.status === 422 && known.length) {
                reviewErrors.value = Object.fromEntries(known.map(field => [field, reviewFieldErrors[field]]))
            } else {
                reviewError.value = 'Neizdevās publicēt atsauksmi. Mēģini vēlreiz.'
            }
            return
        }

        reviewDialog.value = true
        await getReviews()
    }

    async function deleteReview() {
        await axios.post('/api/database/deletereview', { game_id: game.value.id })
        await getReviews()
    }
</script>


<template>
    <div class="game-page">
        <div class="game-wrapper">
            <div v-if="loadError" class="page-message">{{ loadError }}</div>

            <v-progress-linear v-else-if="!game" indeterminate color="white"></v-progress-linear>

            <template v-else>
                <section class="game-header">
                    <img :src="game.image" :alt="game.title" class="game-cover" @error="coverFallback">

                    <div class="game-meta">
                        <h1 class="game-title">{{ game.title }}</h1>

                        <div class="game-facts">
                            <div class="fact" v-for="fact in facts" :key="fact.label">
                                <span class="fact-label">{{ fact.label }}</span>
                                <span class="fact-value">{{ fact.value }}</span>
                            </div>
                        </div>

                        <div class="game-stats">
                            <div class="stat-block">
                                <span class="stat-num">{{ game.average_rating ? game.average_rating.toFixed(1) + ' ★' : '-' }}</span>
                                <span class="stat-lbl">{{ game.average_rating ? 'Vidējais vērtējums' : 'Vēl nav vērtējumu' }}</span>
                            </div>
                            <div class="stat-block">
                                <span class="stat-num">{{ game.player_count }}</span>
                                <span class="stat-lbl">Spēlētāju skaits</span>
                            </div>
                        </div>

                        <div class="collection-actions">
                            <v-btn class="btn-add-collection" @click="toggleCollectionForm">
                                {{ entry ? 'Rediģēt kolekciju' : 'Pievienot kolekcijai' }}
                            </v-btn>
                            <v-btn v-if="entry" class="btn-remove-collection" @click="removeDialog = true">
                                Noņemt
                            </v-btn>
                        </div>
                        <div v-if="collectionError" class="form-error">{{ collectionError }}</div>
                    </div>
                </section>

                <section v-if="showCollectionForm" class="panel collection-form">
                    <div class="collection-field">
                        <label class="collection-label">Statuss</label>
                        <select class="custom-select" v-model="form.status">
                            <option v-for="status in statusVariants" :key="status.value" :value="status.value">
                                {{ status.label }}
                            </option>
                        </select>
                    </div>

                    <div class="collection-field">
                        <label class="collection-label">Jūsu vērtējums</label>
                        <v-rating v-model="form.rating" clearable hover></v-rating>
                    </div>

                    <div class="collection-row">
                        <div class="collection-field">
                            <label class="collection-label">Nospēlētās stundas</label>
                            <input type="number" class="custom-select" v-model.number="form.hours_played" min="0" max="99999" step="1">
                        </div>

                        <div class="collection-field">
                            <label class="collection-label">Pabeigšanas procents</label>
                            <input type="number" class="custom-select" v-model.number="form.completion_percent" min="0" max="100" step="1">
                        </div>

                        <div class="collection-field">
                            <label class="collection-label">Pabeigšanas datums</label>
                            <input type="date" class="custom-select" v-model="form.completed_at" :max="today">
                        </div>
                    </div>

                    <v-textarea v-model="form.note" label="Piezīmes" maxlength="250" counter variant="outlined" density="compact"
                        rows="3" no-resize color="white">
                    </v-textarea>

                    <div class="form-actions">
                        <v-btn class="btn-send" size="small" @click="saveEntry">
                            {{ entry ? 'Saglabāt izmaiņas' : 'Pievienot' }}
                        </v-btn>
                    </div>
                </section>

                <section v-if="entry && !showCollectionForm" class="panel">
                    <h2 class="section-title">Mana kolekcija</h2>
                    <div class="entry-facts">
                        <div class="fact">
                            <span class="fact-label">Jūsu vērtējums</span>
                            <v-rating v-if="entry.rating" :model-value="entry.rating" readonly size="x-small" density="compact"></v-rating>
                            <span v-else class="fact-value">Nav</span>
                        </div>
                        <div class="fact" v-for="fact in entryFacts" :key="fact.label">
                            <span class="fact-label">{{ fact.label }}</span>
                            <span class="fact-value">{{ fact.value }}</span>
                        </div>
                    </div>
                </section>

                <section class="game-section">
                    <h2 class="section-title">Apraksts</h2>
                    <p class="game-description">{{ game.description || 'Apraksts nav pieejams' }}</p>
                </section>

                <section class="game-section">
                    <h2 class="section-title">Atsauksmes ({{ reviews.length }})</h2>

                    <div class="review-form panel" v-if="auth.user && auth.user.email_verified">
                        <v-text-field v-model="reviewTitle" label="Atsauksmes virsraksts" maxlength="50" counter variant="outlined" density="compact"
                            color="white" :error-messages="reviewErrors.title">
                        </v-text-field>
                        <v-textarea v-model="reviewText" label="Uzraksti savas domas..." maxlength="500" counter variant="outlined" density="compact"
                            rows="3" no-resize color="white" :error-messages="reviewErrors.content">
                        </v-textarea>
                        <div class="form-actions">
                            <v-btn v-if="userReview" class="btn-delete-reviewform" size="small" @click="deleteReview">
                                Dzēst
                            </v-btn>
                            <v-btn class="btn-send" size="small" @click="postReview">
                                {{ userReview ? 'Atjaunināt atsauksmi' : 'Publicēt atsauksmi' }}
                            </v-btn>
                        </div>
                        <div v-if="reviewError" class="form-error">{{ reviewError }}</div>
                    </div>

                    <div class="reviews-list">
                        <div v-for="review in sortedReviews" :key="review.id" class="review-item">
                            <img v-if="review.user.avatar_url" :src="review.user.avatar_url" class="review-avatar" alt="Lietotāja attēls" @error="$event.target.src = 'https://placehold.co/150/212121/white?text=U'">
                            <div v-else class="review-avatar review-avatar-placeholder">
                                {{ review.user.username[0].toUpperCase() }}
                            </div>

                            <div class="review-details">
                                <div class="review-author">
                                    <router-link :to="`/User/${review.user.id}/Profile`" class="author-link">
                                        {{ review.user.username }}
                                    </router-link>
                                    <span class="review-you" v-if="review === userReview">jūs</span>
                                </div>
                                <div class="review-title">{{ review.title }}</div>
                                <div class="review-text">{{ review.content }}</div>
                                <div class="review-footer">
                                    <span class="review-date">{{ formatDate(review.created_at) }}</span>
                                    <button v-if="review === userReview" class="btn-delete-review" @click="deleteReview">
                                        Dzēst
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-if="reviews.length === 0" class="empty-state">
                            Vēl nav atsauksmju. Esi pirmais, kas uzraksta!
                        </div>
                    </div>
                </section>

                <section class="game-section">
                    <h2 class="section-title">Diskusijas</h2>
                    <div class="empty-state">Vēl nav diskusiju.</div>
                </section>
            </template>
        </div>

        <v-dialog v-model="reviewDialog" max-width="400">
            <v-card class="dialog">
                <v-card-title>Atsauksme publicēta</v-card-title>
                <v-card-text>Atsauksme veiksmīgi publicēta!</v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn class="v-dialog-button" @click="reviewDialog = false">
                        <span class="v-btn-text">Aizvērt</span>
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog v-model="collectionDialog" max-width="400">
            <v-card class="dialog">
                <v-card-title>Kolekcija</v-card-title>
                <v-card-text>{{ collectionMessage }}</v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn class="v-dialog-button" @click="collectionDialog = false">
                        <span class="v-btn-text">Aizvērt</span>
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog v-model="removeDialog" max-width="400">
            <v-card class="dialog">
                <v-card-title>Noņemt spēli?</v-card-title>
                <v-card-text>Spēle „{{ game?.title }}“ tiks noņemta no kolekcijas.</v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn class="v-dialog-button" @click="removeDialog = false">
                        <span class="v-btn-text">Atcelt</span>
                    </v-btn>
                    <v-btn class="v-dialog-button btn-remove-confirm" @click="removeEntry">
                        <span class="v-btn-text">Noņemt</span>
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>


<style scoped>

:deep(.v-field__outline) {
    --v-field-border-opacity: 0.2;
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

:deep(.v-input__details) {
    padding-top: 2px;
    padding-bottom: 6px;
}

.game-page {
    min-height: 100vh;
    background: #0a0a0a;
    color: #e8e8e8;
}

.game-wrapper {
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

.game-header {
    display: flex;
    gap: 32px;
    margin-bottom: 32px;
}

.game-cover {
    flex: 0 0 auto;
    width: 220px;
    aspect-ratio: 3 / 4;
    object-fit: cover;
    border-radius: 12px;
    border: 1px solid #181818;
    background: #111;
}

.game-meta {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.game-title {
    font-size: 28px;
    font-weight: 500;
    line-height: 1.2;
    color: #f5f5f5;
    margin: 0;
    overflow-wrap: anywhere;
}

.game-facts,
.entry-facts {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.fact {
    display: flex;
    align-items: baseline;
    gap: 16px;
    font-size: 13px;
}

.fact-label {
    flex: 0 0 150px;
    color: #666;
}

.fact-value {
    color: #ddd;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
}

.game-stats {
    display: flex;
    gap: 32px;
}

.stat-block {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.stat-num {
    font-size: 20px;
    font-weight: 500;
    line-height: 1;
    color: #f5f5f5;
}

.stat-lbl {
    font-size: 10px;
    color: #555;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

.collection-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.btn-add-collection {
    background: #fff;
    color: #000;
    text-transform: none;
    font-weight: 600;
    border-radius: 8px;
}

.btn-remove-collection {
    background: transparent;
    color: #ff5555;
    border: 1px solid #ff555533;
    text-transform: none;
    font-weight: 500;
    border-radius: 8px;
}

.panel {
    background: #111;
    border: 1px solid #1f1f1f;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 32px;
}

.collection-form {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.collection-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}

.collection-field {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.collection-label {
    font-size: 11px;
    color: #555;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.custom-select {
    background: #1a1a1a;
    color: #fff;
    border: 1px solid #333;
    border-radius: 4px;
    padding: 5px 8px;
    width: 100%;
}

input[type="date"].custom-select {
    color-scheme: dark;
}

.game-section {
    margin-bottom: 32px;
}

.section-title {
    font-size: 14px;
    font-weight: 500;
    color: #f5f5f5;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin: 0 0 16px;
}

.game-description {
    font-size: 14px;
    line-height: 1.7;
    color: #aaa;
    margin: 0;
    white-space: pre-wrap;
}

.review-form {
    margin-bottom: 16px;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}

.form-error {
    font-size: 12px;
    margin-top: 6px;
    color: #ff5f5f;
}

.btn-send {
    background: #333;
    color: #fff;
    text-transform: none;
    font-size: 12px;
}

.btn-delete-reviewform {
    background: transparent;
    color: #ff5f5f;
    border: 1px solid rgba(255, 95, 95, 0.3);
    text-transform: none;
    font-size: 12px;
}

.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.review-item {
    display: flex;
    gap: 12px;
    padding: 12px;
    background: #111;
    border: 1px solid #181818;
    border-radius: 10px;
    min-width: 0;
}

.review-avatar {
    flex: 0 0 auto;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
}

.review-avatar-placeholder {
    background: #222;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 500;
    color: #666;
}

.review-details {
    flex: 1;
    min-width: 0;
}

.review-author {
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.author-link {
    color: #fff;
    text-decoration: none;
    transition: color 0.15s;
}

.author-link:hover {
    color: #4a9eff;
}

.review-you {
    font-size: 10px;
    color: #4a9eff;
    border: 1px solid rgba(74, 158, 255, 0.3);
    border-radius: 4px;
    padding: 1px 5px;
}

.review-title {
    font-weight: bold;
    font-size: 1.05rem;
    margin: 4px 0;
    color: #fff;
    overflow-wrap: anywhere;
}

.review-text {
    font-size: 13px;
    color: #aaa;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
}

.review-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 6px;
}

.review-date {
    font-size: 0.8rem;
    color: gray;
}

.btn-delete-review {
    background: transparent;
    border: none;
    color: #ff5f5f;
    font-size: 11px;
    cursor: pointer;
    padding: 0;
    opacity: 0.6;
    transition: opacity 0.15s;
}

.btn-delete-review:hover {
    opacity: 1;
}

.empty-state {
    text-align: center;
    color: #444;
    font-size: 13px;
    padding: 24px 0;
}

.dialog {
    background: #050505;
    border-radius: 14px;
    border: 1px solid rgba(255, 255, 255, 0.14);
    color: white;
    padding: 6px;
}

.v-card-title {
    color: white;
    font-size: 18px;
    font-weight: 500;
    padding-bottom: 6px;
}

.v-card-text {
    color: #b8b8b8;
    font-size: 12px;
    font-weight: 300;
    line-height: 1.6;
}

.v-btn-text {
    color: white;
    font-size: 10px;
}

.v-dialog-button {
    background-color: #000;
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.14);
    min-width: 70px;
}

.btn-remove-confirm {
    border-color: rgba(255, 95, 95, 0.3);
}

.btn-remove-confirm:hover {
    background-color: rgba(255, 95, 95, 0.12);
    border-color: rgba(255, 95, 95, 0.6);
}

@media (max-width: 600px) {
    .game-wrapper {
        padding: 20px 14px 40px;
    }

    .game-header {
        flex-direction: column;
        align-items: center;
        gap: 20px;
    }

    .game-cover {
        width: 160px;
    }

    .game-meta {
        width: 100%;
    }

    .game-title {
        font-size: 22px;
        text-align: center;
    }

    .fact {
        flex-direction: column;
        gap: 2px;
    }

    .fact-label {
        flex: none;
        font-size: 11px;
    }

    .game-stats {
        justify-content: center;
    }

    .collection-actions > * {
        flex: 1;
    }

    .collection-row {
        grid-template-columns: 1fr;
    }
}

</style>
