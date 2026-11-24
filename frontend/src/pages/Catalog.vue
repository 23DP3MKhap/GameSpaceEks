<script setup>
    // Imports

    import { ref, onMounted, watch } from 'vue'
    import axios from '../plugins/axios'


    // variables
    const genresList = ref([])
    const platformsList = ref([])
    const selectedGenres = ref([])
    const selectedPlatforms = ref([])
    const games = ref([])
    const dbOffset = ref(0)
    const igdbOffset = ref(0)
    const dbExhausted = ref(false)
    const hasMore = ref(true)
    const scrollTrigger = ref(null)
    const isLoading = ref(false)
    const scrollTriggerBlock= ref(true)
    const catalogEmpty = ref(false)
    
    let timeout = null
    let lastRequestId = 0

    const props = defineProps({ searchValue: String })


    // Startup

    onMounted(async () => {
        await getGenres()
        await getPlatforms()
        await gameLoader()

        if (scrollTrigger.value){

            const observer = new IntersectionObserver(async (elements) => {
            if (elements[0].isIntersecting && !scrollTriggerBlock.value) {
                await gameLoader()
            }
            }, { threshold: 0.1 })

            observer.observe(scrollTrigger.value)
        }  
    })

    

    // watchers

    watch(() => props.searchValue, () => {
        clearTimeout(timeout)
        resetCatalog()
        scrollTriggerBlock.value = true
        timeout = setTimeout(() => {
        gameLoader()
        }, 500)
    })

    watch([selectedGenres, selectedPlatforms], () => {
        resetCatalog()
        gameLoader()
    });


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

    async function getGenres() {
        const response = await axios.get('/api/database/getgenres')
        genresList.value = response.data
    }

    async function getPlatforms() {
        const response = await axios.get('/api/database/getplatforms')
        platformsList.value = response.data
    }

    function resetCatalog() {
        lastRequestId++
        games.value = []
        dbOffset.value = 0
        igdbOffset.value = 0
        dbExhausted.value = false
        hasMore.value = true
    }

    async function gameLoader() {
        if (!hasMore.value) return

        const requestId = ++lastRequestId
        const filters = { search: props.searchValue, genres: selectedGenres.value, platforms: selectedPlatforms.value }
        let dbgames = []
        let apigames = []

        scrollTriggerBlock.value = true
        isLoading.value = true

        try {
            if (!dbExhausted.value) {
                const database_games = await axios.get('/api/database/getgames', {
                    params: { ...filters, offset: dbOffset.value }
                })
                dbgames = database_games.data
            }

            const igdbLimit = 50 - dbgames.length
            if (igdbLimit > 0) {
                const igdb_games = await axios.get('/api/igdb/games', {
                    params: { ...filters, limit: igdbLimit, offset: igdbOffset.value }
                })
                apigames = igdb_games.data
            }

            if (requestId !== lastRequestId) return

            dbOffset.value += dbgames.length
            igdbOffset.value += apigames.length
            dbExhausted.value = dbgames.length < 50
            hasMore.value = apigames.length === igdbLimit

            const existingIds = new Set(games.value.map(game => game.id))
            const uniqueNew = [...dbgames, ...apigames].filter(function (game){return !existingIds.has(game.id)})
            games.value = [...games.value, ...uniqueNew]

            if (games.value.length === 0){
                catalogEmpty.value = true
            }
            else{
                catalogEmpty.value = false
            }
        } finally {
            if (requestId === lastRequestId) {
                isLoading.value = false
                scrollTriggerBlock.value = false
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
            <div v-if="catalogEmpty"><p>Nav spēļu jūsu pieprasījumam, lūdzu mēģiniet citu</p></div>
            <section class="games-grid">
                <router-link class="game-card" v-for="game in games" :key="game.id" :to="`/Game/${game.id}`">
                    <img :src="game.image" :alt="game.title" class="game-image">
                    <div class="game-info">
                        <h2>{{ game.title }}</h2>
                        <div class="genre">{{ game.genre }}</div>
                        <div class="meta">Izstrādātājs: {{ game.developer }}</div>
                        <div class="meta">Izdevējs: {{ game.publisher }}</div>
                        <div class="meta" v-if="game.average_rating">Vidējais vērtējums: {{ game.average_rating.toFixed(1) }} ★</div>
                        <div class="meta">id: {{ game.id }} {{ game.source }}</div>
                    </div>
                </router-link>
            </section>
            <div class="loading-wrap" v-if="isLoading">
                <v-progress-linear v-if="isLoading" indeterminate color="white"></v-progress-linear>
            </div>
            <div ref="scrollTrigger" class="scroll-trigger"></div>
        </main>
    </div>
</template>


<style scoped>

.loading-wrap {
    display: flex;
    justify-content: center;
}

.scroll-trigger {
    height: 1px;
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
    background: #111;
    border: 1px solid #181818;
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.18s ease, border-color 0.18s ease;
    cursor: pointer;
    color: inherit;
    text-decoration: none;
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