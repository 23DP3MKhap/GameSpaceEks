<script setup>
    // Imports

    import { ref, onMounted, watch , computed} from 'vue'
    import axios from '../plugins/axios'
    import { auth } from '../plugins/userinfo'
    import { useRouter } from 'vue-router'


    // variables
    const router = useRouter()
    const genresList = ref([])
    const platformsList = ref([])
    const selectedGenres = ref([])
    const selectedPlatforms = ref([])
    const games = ref([])
    const dialog = ref(false)
    const selectedGame = ref(null)
    const reviewTitle = ref("")
    const reviewText = ref("")
    const reviewRating = ref(1)
    const reviewdialog = ref(false)
    const reviews = ref([])
    const collectionExists = ref(false)
    const collectionStatus = ref('planned')
    const collectionScore = ref(5)
    const collectionNotes = ref('')
    const showCollectionForm = ref(false)
    const loadingOffset = ref(0)
    const scrollTrigger = ref(null)
    const isLoading = ref(false)
    const scrollTriggerBlock= ref(true)
    
    const userReview = computed(function() {
        if (auth.user){
            const founded = reviews.value.find(function(review){
                return review.user.id === auth.user.id
            })
            
            if (founded){
                return founded
            } else {
                return null
            }

        }
    })

    
    const sortedReviews = computed(() => {
    if (!auth.user){return reviews.value}
    return [
        ...reviews.value.filter(function(review){return review.user.id === auth.user.id}),
        ...reviews.value.filter(function(review){return review.user.id !== auth.user.id}),
    ]
})
    const statusVariants = [
        { value: 'Plānots', label: 'Plānots' },
        { value: 'Spēlēju', label: 'Spēlēju' },
        { value: 'Pabeigta', label: 'Pabeigta' },
        { value: 'Pārtraukts', label: 'Pārtraukts' },
    ]

    let timeout = null

    const props = defineProps({ searchValue: String })


    // Startup

    onMounted(async () => {
        await getGenres()
        await getPlatforms()
        await gameLoader()

        if (scrollTrigger.value){

            const observer = new IntersectionObserver(async (elements) => {
            if (elements[0].isIntersecting && !scrollTriggerBlock.value) {
                loadingOffset.value += 24
                await gameLoader()
            }
            }, { threshold: 0.1 })

            observer.observe(scrollTrigger.value)
        }  
    })

    

    // watchers

    watch(() => props.searchValue, () => {
        clearTimeout(timeout)
        games.value = []
        loadingOffset.value = 0
        scrollTriggerBlock.value = true
        timeout = setTimeout(() => {
        gameLoader()
        }, 500)
    })

    watch(userReview, (review) => {
        if (review) {
            reviewTitle.value = review.title
            reviewText.value = review.content
            reviewRating.value = review.rating
        } else {
            reviewTitle.value = ''
            reviewText.value = ''
            reviewRating.value = 1
        }
    })

    watch([selectedGenres, selectedPlatforms], () => {
        games.value = []
        loadingOffset.value = 0
        gameLoader()
    });

    watch(dialog, (opened) => {
        if (!opened) {
            collectionExists.value = false
        }
    })


    //functions

    function toggleAllGenres(el) {
        if (el.target.checked) {
            selectedGenres.value = genresList.value.map(genre => genre.id)
        } else {
            selectedGenres.value = []
        }
    }

    function toggleAllPlatforms(e) {
        if (e.target.checked) {
            selectedPlatforms.value = platformsList.value.map(platform => platform.id)
        } else {
            selectedPlatforms.value = []
        }
    }

    async function getReviews(gameId) {
        const response = await axios.get("/api/database/getreviews", { params: { game_id: gameId } })
        reviews.value = response.data
    }

    async function getGenres() {
        const response = await axios.get('/api/database/getgenres')
        genresList.value = response.data
    }

    async function getPlatforms() {
        const response = await axios.get('/api/database/getplatforms')
        platformsList.value = response.data
    }

    async function postReview(game) {
        await axios.get('/sanctum/csrf-cookie')
        await axios.post("/api/database/addgame", { igdb_id: game.id })
        await axios.post("/api/database/addreview", {
            user_id: auth.user.id,
            game_id: game.id,
            title: reviewTitle.value,
            content: reviewText.value,
            rating: reviewRating.value
        })
        reviewTitle.value = ""
        reviewText.value = ""
        reviewRating.value = 1
        reviewdialog.value = true
        getReviews(game.id)
        return console.log("Review posted")
    }


    async function gameLoader() {
        let newGames = []

        scrollTriggerBlock.value = true
        isLoading.value = true
        
        const database_games = await axios.get('/api/database/getgames', {
            params: { search: props.searchValue, genres: selectedGenres.value, platforms: selectedPlatforms.value, offset: loadingOffset.value}
        })

        const dbgames = database_games.data

        newGames = [...dbgames]

        if (dbgames.length < 24) {
            const igdb_games = await axios.get('/api/igdb/games', {
                params: { search: props.searchValue, genres: selectedGenres.value, platforms: selectedPlatforms.value, dbgamesquantity: dbgames.length,
                        dbgamesids: dbgames.map(genre => genre.id).join(',') || null, offset: loadingOffset.value
                }
            })
            const apigames = igdb_games.data
            newGames = [...dbgames, ...apigames]
        }

        const existingIds = new Set(games.value.map(game => game.id))
        const uniqueNew = newGames.filter(function (game){return !existingIds.has(game)})
        games.value = [...games.value, ...uniqueNew]
        isLoading.value = false     
        scrollTriggerBlock.value = false
    }

    async function openGameModal(game) {
        selectedGame.value = game
        showCollectionForm.value = false
        collectionStatus.value = 'Plānots'
        collectionScore.value = 5
        collectionNotes.value = ''

        if (auth.user) {
            const res = await axios.get('/api/database/checkcollection', { params: { game_id: game.id } })
            if (res.data.exists) {
                collectionExists.value = true
                collectionStatus.value = res.data.status
                collectionScore.value = res.data.user_score
                collectionNotes.value = res.data.notes || ''
            }
        }

        await getReviews(game.id)
        dialog.value = true
    }

    async function deleteCollection(gameId) {
        await axios.get('/sanctum/csrf-cookie')
        await axios.post("/api/database/removefromcollection", { game_id: gameId })
        collectionExists.value = false
    }

    async function addToCollection(gameId) {
        await axios.get('/sanctum/csrf-cookie')
        await axios.post("/api/database/addgame", { igdb_id: gameId })
        await axios.post("/api/database/addtocollection", {
            game_id: gameId,
            status: collectionStatus.value,
            user_score: collectionScore.value,
            notes: collectionNotes.value
        })
        collectionExists.value = true
        showCollectionForm.value = false
    }


    async function deleteReview(gameId) {
        await axios.get('/sanctum/csrf-cookie')
        await axios.post("/api/database/deletereview", { game_id: gameId })
        getReviews(gameId)
    }

</script>


<template>
    <div class="page">
        <main class="content">
            <section class="hero">
                <h1>Filters</h1>
                <p>shift + scroll to scroll list</p>

                <div style="padding: 1rem 0;">
                    <div class="track-row">
                        <span class="track-label">Genres: </span>
                        <div class="track-scroll">
                            <div class="filter-item">
                                <input type="checkbox" id="g-all" :checked="selectedGenres.length === genresList.length" @change="toggleAllGenres">
                                <label for="g-all">| ALL |</label>
                            </div>
                            <div class="filter-item" v-for="genre in genresList" :key="genre.id">
                                <input type="checkbox" :id="'g-' + genre.id" :value="genre.id" v-model="selectedGenres">
                                <label :for="'g-' + genre.id">{{ genre.name }}</label>
                            </div>
                        </div>
                    </div>

                    <div class="track-row">
                        <span class="track-label">Platforms: </span>
                        <div class="track-scroll">
                            <div class="filter-item">
                                <input type="checkbox" id="p-all" :checked="selectedPlatforms.length === platformsList.length" @change="toggleAllPlatforms">
                                <label for="p-all">| ALL |</label>
                            </div>
                            <div class="filter-item" v-for="platform in platformsList" :key="platform.id">
                                <input type="checkbox" :id="'p-' + platform.id" :value="platform.id" v-model="selectedPlatforms">
                                <label :for="'p-' + platform.id">{{ platform.name }}</label>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <v-progress-linear v-if="isLoading" indeterminate color="white"></v-progress-linear>
            <section class="games-grid">
                <div class="game-card" v-for="game in games" :key="game.id" @click="openGameModal(game)">
                    <img :src="game.image" :alt="game.name" class="game-image">
                    <div class="game-info">
                        <h2>{{ game.name }}</h2>
                        <div class="genre">{{ game.genre }}</div>
                        <div class="meta">Developer: {{ game.developer }}</div>
                        <div class="meta">Publisher: {{ game.publisher }}</div>
                        <div class="meta">id: {{ game.id }} {{ game.source }}</div>
                    </div>
                </div>
            </section>
            <div class="loading-wrap" v-if="isLoading">
                <v-progress-linear v-if="isLoading" indeterminate color="white"></v-progress-linear>
            </div>
            <div ref="scrollTrigger" class="scroll-trigger"></div>
        </main>

        <v-dialog max-width="850" max-height="80vh" v-model="dialog" transition="dialog-bottom-transition">
            <v-card class="game-modal" v-if="selectedGame">
                <div class="modal-body">
                    <div class="modal-main-info">
                        <template v-if="auth.user">
                            <v-btn v-if="!collectionExists" class="btn-add-collection" block @click="showCollectionForm = !showCollectionForm"> 
                                Add to Collection
                            </v-btn>

                            <template v-if="collectionExists">
                                <v-btn class="btn-add-collection" block @click="showCollectionForm = !showCollectionForm" style="margin-bottom: 8px">
                                    Edit Collection
                                </v-btn>
                                <v-btn class="btn-remove-collection" block @click="deleteCollection(selectedGame.id)">
                                    Remove
                                </v-btn>
                            </template>
                        </template>

                        <div v-if="showCollectionForm" class="collection-form">
                            <div class="collection-field">
                                <label class="collection-label">Status</label>
                                <select class="custom-select full-width" v-model="collectionStatus">
                                    <option v-for="status in statusVariants" :key="status.value" :value="status.value">
                                        {{ status.label }}
                                    </option>
                                </select>
                            </div>
                        
                            <div class="collection-field">
                                <label class="collection-label">Your Score</label>
                                <select class="custom-select full-width" v-model="collectionScore">
                                    <option v-for="score in 10" :key="score" :value="score">{{ score }}</option>
                                </select>
                            </div>
                        
                            <v-textarea v-model="collectionNotes" label="Notes" maxlength="50" counter variant="outlined" density="compact" 
                            rows="3" no-resize color="white">
                            </v-textarea>
                        
                            <v-btn class="btn-send" block size="small" @click="addToCollection(selectedGame.id)">
                                {{ collectionExists ? 'Save Changes' : 'Add' }}
                            </v-btn>
                        </div>

                        <div class="modal-stats" :style="showCollectionForm ? 'margin-top: 12px' : ''">
                            <div class="stat-item" v-if="collectionExists">
                                <span class="stat-label">Status</span>
                                <span class="stat-value">{{ collectionStatus }}</span>
                            </div>
                            <div class="stat-item" v-if="collectionExists">
                                <span class="stat-label">Your Score</span>
                                <span class="stat-value">{{ collectionScore }}</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Note</span>
                                <span class="stat-value">{{ collectionNotes || 'N/A' }}</span>
                            </div>
                            <div class="stat-item" style="flex-direction: column; gap: 4px;">
                                <span class="stat-label">Genre:</span>
                                <span class="stat-value">{{ selectedGame.genre }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="modal-content-area">
                        <v-card-title class="modal-title">
                            {{ selectedGame.name }}
                        </v-card-title>

                        <div class="reviews-section">
                            <h3 class="section-title">Reviews ({{ reviews.length }})</h3>

                            <div class="reviews-list">
                                <div v-for="review in sortedReviews" :key="review.id" class="review-item">
                                    <div class="review-avatar-wrap">
                                        <img v-if="review.user?.avatar" :src="review.user.avatar" class="review-avatar-img" alt="userAvatar">
                                        <div v-else class="review-avatar-placeholder">
                                            {{review.user.name[0].toUpperCase()}}
                                        </div>
                                    </div>
                                
                                    <div class="review-details">
                                        <div class="review-author">
                                            <span class="author-link" @click="router.push(`/User/${review.user?.id}/Profile`)">
                                            {{ review.user.name }}
                                            <span class="review-you" v-if="review.user?.id === auth.user?.id">you</span>
                                            </span>
                                            <span class="review-rating">{{ review.rating }}/10</span>
                                        </div>
                                        <div class="review-title">{{ review.title }}</div>
                                        <div class="review-text">{{ review.content }}</div>
                                        <div class="review-footer">
                                            <span class="review-date">{{ review.created_at  }}</span>
                                            <button v-if="review.user?.id === auth.user?.id" class="btn-delete-review" @click="deleteReview(selectedGame.id)">
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            
                                <div v-if="reviews.length === 0" class="no-reviews">
                                    <p>No reviews yet. Be the first to write one!</p>
                                </div>
                            </div>
                        
                            <div class="review-form" v-if="auth.user">
                                <v-text-field v-model="reviewTitle" label="Review Title" maxlength="20" counter variant="outlined" density="compact" 
                                    color="white">
                                </v-text-field>
                                <v-textarea v-model="reviewText" label="Write your thoughts..." maxlength="500" counter variant="outlined" density="compact" 
                                    rows="3" no-resize color="white">
                                </v-textarea>
                                <div class="form-actions">
                                    <div class="rating-picker">
                                        <span>Score:</span>
                                        <select class="custom-select" v-model="reviewRating">
                                            <option v-for="n in 10" :key="n" :value="n">{{ n }}</option>
                                        </select>
                                    </div>
                                    <div style="display: flex; gap: 8px;">
                                        <v-btn v-if="userReview" class="btn-delete-reviewform" size="small" @click="deleteReview(selectedGame.id)">
                                            Delete
                                        </v-btn>
                                        <v-btn class="btn-send" size="small" @click="postReview(selectedGame)">
                                            {{ userReview ? 'Update Review' : 'Post Review' }}
                                        </v-btn>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </v-card>
        </v-dialog>

        <v-dialog v-model="reviewdialog" max-width="400">
            <v-card class="dialog">
                <v-card-title>Review published</v-card-title>
                <v-card-text>Successfully published review!</v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn class="v-dialog-button" @click="reviewdialog = false">
                        <span class="v-btn-text">Close</span>
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

:deep(.v-input) {
    margin-bottom: 4px;
}

:deep(.v-input__details) {
    padding-top: 2px;
    padding-bottom: 6px;
}

.author-link {
    cursor: pointer;
    transition: color 0.15s;
}

.author-link:hover {
    color: #4a9eff;
}

.loading-wrap {
    display: flex;
    justify-content: center;
}

.scroll-trigger {
    height: 1px;
}

.review-avatar-wrap {
    flex: 0 0 auto;
}

.review-avatar-img {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
}

.review-avatar-placeholder {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #222;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 500;
    color: #666;
}

.review-you {
    font-size: 10px;
    color: #4a9eff;
    border: 1px solid rgba(74, 158, 255, 0.3);
    border-radius: 4px;
    padding: 1px 5px;
    margin-left: 6px;
    vertical-align: middle;
}

.review-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 4px;
}

.review-date {
    font-size: 0.8rem;
    color: gray;
}

.review-details {
    flex: 1;
    min-width: 0;
    overflow: hidden;
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

.btn-delete-reviewform {
    background: transparent;
    color: #ff5f5f;
    border: 1px solid rgba(255, 95, 95, 0.3);
    text-transform: none;
    font-size: 12px;
}

.btn-remove-collection {
    background: transparent;
    color: #ff5555;
    border: 1px solid #ff555533;
    text-transform: none;
    font-weight: 500;
    border-radius: 8px;
    margin-bottom: 12px;
}

.collection-form {
    background: #0d0d0d;
    border: 1px solid #1f1f1f;
    border-radius: 10px;
    padding: 12px;
    margin-bottom: 12px;
    display: flex;
    flex-direction: column;
    gap: 10px;
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

.full-width {
    width: 100%;
    padding: 5px 8px;
}

.track-row {
    display: flex;
    align-items: center;
    margin: 0 0 0.75rem;
}

.track-label {
    flex: 0 0 auto;
    font-size: 14px;
    color: #fff;
    white-space: nowrap;
    padding-right: 14px;
    padding-bottom: 4px;
    min-width: 80px;
}

.track-scroll {
    display: flex;
    gap: 4px;
    overflow-x: auto;
    overflow-y: hidden;
    flex-wrap: nowrap;
    min-width: 0;
    padding-bottom: 4px;
    scrollbar-width: none;
}

.track-scroll::-webkit-scrollbar {
    display: none;
}

.filter-item {
    display: inline-block;
    flex: 0 0 auto;
}

.filter-item input[type="checkbox"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
    pointer-events: none;
}

.filter-item label {
    display: block;
    font-size: 12px;
    padding: 4px 10px;
    border-radius: 20px;
    cursor: pointer;
    white-space: nowrap;
    color: #888;
    transition: color 0.15s, background 0.15s;
    user-select: none;
}

.filter-item label:hover {
    color: #fff;
}

.filter-item input:checked + label {
    color: #fff;
    background: #1f1f1f;
    font-weight: 500;
}

.v-card-title,
.v-card-text {
    color: white;
}

.v-btn-text {
    color: white;
    font-size: 10px;
}

.dialog,
.v-dialog-button {
    background-color: #000;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.14);
}

.v-card {
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.587);
}

.review-title {
    font-weight: bold;
    font-size: 1.1rem;
    margin: 4px 0;
    color: #fff;
    word-break: break-word;
    overflow-wrap: break-word;
}

.game-modal {
    background: #0d0d0d;
    border: 1px solid #1f1f1f;
    border-radius: 16px;
    overflow: hidden;
    color: #fff;
    height: 80vh;
    max-height: 80vh;
    display: flex;
    flex-direction: column;
}

.modal-body {
    display: flex;
    flex: 1;
    min-height: 0;
    overflow: hidden;
}

.modal-main-info {
    flex: 0 0 220px;
    width: 220px;
    padding: 20px 16px;
    overflow-y: auto;
    overflow-x: hidden;
    min-height: 0;
    border-right: 1px solid #1a1a1a;
    scrollbar-width: thin;
    scrollbar-color: #333 transparent;
}

.modal-main-info::-webkit-scrollbar {
    width: 4px;
}

.modal-main-info::-webkit-scrollbar-track {
    background: transparent;
}

.modal-main-info::-webkit-scrollbar-thumb {
    background: #333;
    border-radius: 2px;
}

.btn-add-collection {
    background: #fff;
    color: #000;
    text-transform: none;
    font-weight: 600;
    border-radius: 8px;
    margin-bottom: 12px;
}

.modal-stats {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
}

.stat-label {
    color: #666;
}

.stat-value {
    color: #eee;
    white-space: pre-wrap;
    word-break: break-word;
    text-align: right;
    max-width: 60%;
}

.modal-content-area {
    flex: 1;
    display: flex;
    flex-direction: column;
    padding: 20px;
    min-height: 0;
    overflow: hidden;
}

.modal-title {
    font-size: 22px;
    font-weight: 700;
    padding: 0 0 16px;
    line-height: 1.2;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-shrink: 0;
}

.close-btn {
    color: #555;
}

.reviews-section {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
    overflow: hidden;
}

.section-title {
    font-size: 14px;
    text-transform: uppercase;
    color: #444;
    letter-spacing: 1px;
    margin-bottom: 12px;
    flex-shrink: 0;
}

.reviews-list {
    flex: 1;
    overflow-y: auto;
    min-height: 0;
    margin-bottom: 12px;
    scrollbar-width: thin;
    scrollbar-color: #333 transparent;
}

.reviews-list::-webkit-scrollbar {
    width: 4px;
}

.reviews-list::-webkit-scrollbar-track {
    background: transparent;
}

.reviews-list::-webkit-scrollbar-thumb {
    background: #333;
    border-radius: 2px;
}

.review-item {
    display: flex;
    gap: 12px;
    padding: 12px;
    background: #161616;
    border-radius: 10px;
    margin-bottom: 8px;
    min-width: 0;
}

.review-avatar {
    width: 32px;
    height: 32px;
    background: #333;
    border-radius: 50%;
    flex-shrink: 0;
}

.review-author {
    font-size: 13px;
    font-weight: 500;
    color: #fff;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 4px;
}

.review-rating {
    color: #38ef7d;
    margin-left: 8px;
}

.review-text {
    font-size: 13px;
    color: #aaa;
    white-space: pre-wrap;
    word-break: break-word;
    overflow-wrap: break-word;
}

.no-reviews {
    color: #555;
    font-size: 13px;
    text-align: center;
    padding: 20px 0;
}

.review-form {
    flex-shrink: 0;
    background: #111;
    border: 1px solid #1f1f1f;
    border-radius: 12px;
    padding: 12px;
}

.form-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px solid #1f1f1f;
}

.rating-picker {
    font-size: 12px;
    color: #666;
    display: flex;
    align-items: center;
    gap: 8px;
}

.custom-select {
    background: #1a1a1a;
    color: #fff;
    border: 1px solid #333;
    border-radius: 4px;
    padding: 2px 6px;
}

.btn-send {
    background: #333;
    color: #fff;
    text-transform: none;
    font-size: 12px;
}

.page {
    min-height: 100vh;
    background: #0a0a0a;
    color: #e8e8e8;
}

.content {
    padding: 24px 20px 40px;
}

.hero {
    margin-bottom: 22px;
}

.hero h1 {
    font-size: 24px;
    line-height: 1.1;
    font-weight: 500;
    margin: 0 0 4px;
    color: #f5f5f5;
}

.hero p {
    font-size: 12px;
    font-weight: 300;
    color: #727272;
    margin: 0;
}

.games-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
    gap: 14px;
}

.game-card {
    background: #111;
    border: 1px solid #181818;
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.18s ease, border-color 0.18s ease;
    cursor: pointer;
}

.game-card:hover {
    transform: translateY(-3px);
    border-color: #242424;
}

.game-image {
    width: 100%;
    height: 210px;
    object-fit: cover;
    display: block;
    filter: brightness(0.92);
}

.game-info {
    padding: 10px 10px 12px;
}

.game-info h2 {
    font-size: 13px;
    line-height: 1.3;
    font-weight: 400;
    margin: 0 0 5px;
    color: #efefef;
}

.genre {
    font-size: 11px;
    font-weight: 300;
    color: #8f8f8f;
    margin-bottom: 4px;
}

.meta {
    font-size: 10px;
    font-weight: 300;
    color: #666;
}

.v-card {
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

.v-card-text p {
    margin: 0 0 6px;
}

.modal-label {
    color: #fff;
    font-weight: 400;
}

.v-dialog-button {
    background-color: #000;
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.14);
    min-width: 70px;
}

@media (max-width: 768px) {
    .games-grid {
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 10px;
    }

    .game-image {
        height: 180px;
    }

    .content {
        padding: 16px 12px 32px;
    }

    .hero h1 {
        font-size: 20px;
    }
}

@media (max-width: 600px) {
    .games-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }

    .game-image {
        height: 160px;
    }

    .game-info h2 {
        font-size: 12px;
    }

    .content {
        padding: 12px 10px 24px;
    }

    .hero {
        margin-bottom: 14px;
    }

    .hero h1 {
        font-size: 18px;
    }

    .hero p {
        display: none;
    }

    .track-label {
        font-size: 12px;
        min-width: 66px;
        padding-right: 8px;
    }

    .filter-item label {
        font-size: 11px;
        padding: 3px 8px;
    }

    .game-modal {
        height: 92vh;
        max-height: 92vh;
        border-radius: 12px;
        display: flex;
        flex-direction: column;
    }

    .modal-body {
        flex-direction: column;
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
        min-height: 0;
    }

    .modal-main-info {
        flex: 0 0 auto;
        width: 100%;
        padding: 12px 14px;
        border-right: none;
        border-bottom: 1px solid #1a1a1a;
        overflow: visible;
        min-height: 0;
    }

    .btn-add-collection {
        margin-bottom: 8px;
    }

    .modal-stats {
        flex-direction: row;
        flex-wrap: wrap;
        gap: 8px;
    }

    .stat-item {
        flex-direction: column;
        gap: 2px;
        min-width: 80px;
    }

    .stat-value {
        text-align: left;
        max-width: 100%;
    }

    .modal-content-area {
        flex: 0 0 auto;
        padding: 14px;
        overflow: visible;
        min-height: 0;
    }

    .reviews-section {
        flex: 0 0 auto;
        overflow: visible;
        min-height: 0;
    }

    .reviews-list {
        max-height: 300px;
        overflow-y: auto;
    }

    .modal-title {
        font-size: 18px;
        padding-bottom: 12px;
    }

    .review-item {
        padding: 10px;
    }

    .review-title {
        font-size: 1rem;
    }

    .review-text {
        font-size: 12px;
    }

    .form-actions {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }

    .form-actions > div:last-child {
        width: 100%;
        justify-content: flex-end;
    }
}

@media (max-width: 380px) {
    .games-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 6px;
    }

    .game-image {
        height: 140px;
    }

    .modal-title {
        font-size: 16px;
    }
}


</style>