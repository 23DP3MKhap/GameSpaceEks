<script setup>
    // Imports

    import { ref, computed, onMounted, watch } from 'vue'
    import axios from '../plugins/axios'
    import { auth } from '../plugins/userinfo'


    // variables
    const PAGE_SIZE = 40

    const genresList = ref([])
    const platformsList = ref([])
    const selectedGenres = ref([])
    const selectedPlatforms = ref([])
    const games = ref([])
    const total = ref(0)
    const partial = ref(false)
    const page = ref(1)
    const isLoading = ref(true)
    const loadError = ref('')

    let timeout = null
    let lastRequestId = 0

    const props = defineProps({ searchValue: String })

    const search = computed(() => (props.searchValue ?? '').trim())
    const pageCount = computed(() => Math.ceil(total.value / PAGE_SIZE))

    const foundText = computed(() => {
        const count = total.value
        const single = count % 10 === 1 && count % 100 !== 11
        return `${single ? 'Atrasta' : 'Atrastas'} ${count.toLocaleString('lv-LV')} ${single ? 'spēle' : 'spēles'}`
    })


    // Startup

    onMounted(async () => {
        const [genres, platforms] = await Promise.all([
            axios.get('/api/database/getgenres'),
            axios.get('/api/database/getplatforms'),
            loadGames(),
        ])
        genresList.value = genres.data
        platformsList.value = platforms.data
    })



    // watchers

    watch(search, () => {
        clearTimeout(timeout)
        timeout = setTimeout(resetPage, 500)
    })

    watch([selectedGenres, selectedPlatforms], resetPage)

    watch(page, () => {
        window.scrollTo({ top: 0 })
        loadGames()
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

    function resetPage() {
        if (page.value === 1) {
            loadGames()
        } else {
            page.value = 1
        }
    }

    async function loadGames() {
        const requestId = ++lastRequestId
        isLoading.value = true

        try {
            const response = await axios.get('/api/games', {
                params: {
                    search: search.value,
                    genres: selectedGenres.value,
                    platforms: selectedPlatforms.value,
                    page: page.value,
                },
            })

            if (requestId !== lastRequestId) return

            games.value = response.data.games
            total.value = response.data.total
            partial.value = response.data.partial
            loadError.value = ''
        } catch {
            if (requestId === lastRequestId) {
                loadError.value = 'Neizdevās ielādēt spēles. Mēģini vēlreiz.'
            }
        } finally {
            if (requestId === lastRequestId) {
                isLoading.value = false
            }
        }
    }


</script>


<template>
    <div class="page">
        <main class="content">
            <section class="hero">
                <h1>Filtri</h1>
                <p>shift + scroll, lai ritinātu sarakstu</p>

                <div style="padding: 1rem 0;">
                    <div class="track-row">
                        <span class="track-label">Žanri: </span>
                        <div class="track-scroll">
                            <div class="filter-item">
                                <input type="checkbox" id="g-all" :checked="selectedGenres.length === genresList.length" @change="toggleAllGenres">
                                <label for="g-all">| VISI |</label>
                            </div>
                            <div class="filter-item" v-for="genre in genresList" :key="genre.id">
                                <input type="checkbox" :id="'g-' + genre.id" :value="genre.id" v-model="selectedGenres">
                                <label :for="'g-' + genre.id">{{ genre.name }}</label>
                            </div>
                        </div>
                    </div>

                    <div class="track-row">
                        <span class="track-label">Platformas: </span>
                        <div class="track-scroll">
                            <div class="filter-item">
                                <input type="checkbox" id="p-all" :checked="selectedPlatforms.length === platformsList.length" @change="toggleAllPlatforms">
                                <label for="p-all">| VISAS |</label>
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
            <p v-if="loadError" class="catalog-message">{{ loadError }}</p>
            <p v-else-if="partial" class="catalog-message">Daļa rezultātu pašlaik nav pieejama, mēģini vēlāk</p>
            <p v-else-if="!isLoading && total === 0" class="catalog-message">Pēc norādītajiem kritērijiem spēles netika atrastas</p>
            <p v-if="total > 0" class="found-count">{{ foundText }}</p>
            <section class="games-grid">
                <article class="game-card" v-for="game in games" :key="game.id">
                    <router-link class="game-link" :to="`/Game/${game.id}`">
                        <img :src="game.image" :alt="game.title" class="game-image">
                        <div class="game-info">
                            <h2>{{ game.title }}</h2>
                            <div class="meta">{{ game.year ?? 'Gads nav zināms' }}</div>
                            <div class="genre" v-if="game.genres.length">{{ game.genres.join(', ') }}</div>
                            <div class="rating" v-if="game.average_rating">★ {{ game.average_rating.toFixed(1) }}</div>
                        </div>
                    </router-link>
                    <div class="game-actions" v-if="auth.user">
                        <v-btn class="btn-add-collection" size="small" block :to="{ path: `/Game/${game.id}`, query: { collection: 1 } }">
                            Pievienot kolekcijai
                        </v-btn>
                    </div>
                </article>
            </section>
            <v-pagination v-if="pageCount > 1" v-model="page" :length="pageCount" :total-visible="7" class="catalog-pagination"></v-pagination>
        </main>
    </div>
</template>


<style scoped>

.catalog-message {
    font-size: 13px;
    color: #8f8f8f;
    margin: 12px 0;
}

.found-count {
    font-size: 13px;
    color: #bdbdbd;
    margin: 12px 0;
}

.catalog-pagination {
    margin-top: 24px;
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
    display: flex;
    flex-direction: column;
    background: #111;
    border: 1px solid #181818;
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.18s ease, border-color 0.18s ease;
}

.game-link {
    flex: 1;
    color: inherit;
    text-decoration: none;
}

.game-actions {
    padding: 0 10px 12px;
}

.btn-add-collection {
    background: #fff;
    color: #000;
    text-transform: none;
    font-weight: 600;
    border-radius: 8px;
}

.rating {
    font-size: 12px;
    color: #efefef;
    margin-top: 4px;
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
}

@media (max-width: 380px) {
    .games-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 6px;
    }

    .game-image {
        height: 140px;
    }
}


</style>