<script setup>
    import { ref, onMounted, watchEffect, watch } from 'vue'
    import axios from '../plugins/axios'

    const games = ref([])
    const dialog = ref(false)
    const selectedGame = ref(null)
    let timeout = null

    const props = defineProps({
        searchQuery: String
    })


    watch(() => props.searchQuery, (newVal) => {
    clearTimeout(timeout);
    if (!newVal || newVal.trim() === "") {
        standartGameLoader();
        return
    }
    
    timeout = setTimeout(() => {
        searchData(newVal);
    }, 500);
    });

    //loading by search

    async function searchData(query){
        const igdb_games = await axios.post("/api/igdb/searchbyname", {search: query })
        games.value = igdb_games.data.map(game => ({
            id: game.id,
            name: game.name || 'Unknown',
            image: game.cover
                ? 'https:' + game.cover.url.replace('t_thumb', 't_cover_big')
                : 'https://placehold.co/600x400',
            genre: game.genres?.length
                ? game.genres.map(g => g.name).join(', ')
                : 'Unknown'
        }))
    }
    
    onMounted(async () => {
            await standartGameLoader()
        })

    async function standartGameLoader() {
         const igdb_games = await axios.get('/api/igdb/games')

        games.value = igdb_games.data.map(game => ({
            id: game.id,
            name: game.name || 'Unknown',
            image: game.cover
                ? 'https:' + game.cover.url.replace('t_thumb', 't_cover_big')
                : 'https://placehold.co/600x400',
            genre: game.genres?.length
                ? game.genres.map(g => g.name).join(', ')
                : 'Unknown'
        }))
    }
    


    function openGameModal(game) {
        selectedGame.value = game
        dialog.value = true
    }
</script>


<template>
    <div class="catalog-page">
        <aside class="sidebar">
            <div class="logo">Filters</div>
            <nav class="menu">
                <h3>Platforms</h3>
                <a href="#">X</a>
                <a href="#">X</a>
                <a href="#">X</a>
                <a href="#">X</a>

                <h3>Genres</h3>
                <a href="#">X</a>
                <a href="#">X</a>
                <a href="#">X</a>
            </nav>
        </aside>

        <main class="content">
            <section class="hero">
                <h1>Catalog</h1>
                <p>Game list</p>
            </section>

            <section class="games-grid">
                <div class="game-card" v-for="game in games" :key="game.id" @click="openGameModal(game)">
                    <img :src="game.image" :alt="game.name" class="game-image" />

                    <div class="game-info">
                        <h2>{{ game.name }}</h2>
                        <div class="genre">{{ game.genre }}</div>
                        <div class="meta">id: {{ game.id }}</div>
                    </div>
                </div>
            </section>
        </main>

        <v-dialog max-width="850" v-model="dialog" transition="dialog-bottom-transition">
            <v-card class="game-modal" v-if="selectedGame">
                <div class="modal-body">
                    <div class="modal-aside">
                        <img :src="selectedGame.image" :alt="selectedGame.name" class="modal-image" />
                        <div class="modal-main-info">
                            <v-btn class="btn-add-collection" block>
                                Add to Collection
                            </v-btn>
                            <div class="modal-stats">
                                <div class="stat-item">
                                    <span class="stat-label">Rating</span>
                                    <span class="stat-value">N/A</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Genre</span>
                                    <span class="stat-value">{{ selectedGame.genre }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                
                    <div class="modal-content-area">
                        <v-card-title class="modal-title">
                            {{ selectedGame.name }}
                            <v-btn icon="mdi-close" variant="text" size="small" @click="dialog = false" class="close-btn"></v-btn>
                        </v-card-title>
                    
                        <div class="reviews-section">
                            <h3 class="section-title">Reviews</h3>
                            
                            <div class="reviews-list">
                                <div class="review-item placeholder">
                                    <div class="review-avatar"></div>
                                    <div class="review-details">
                                        <div class="review-author">User123 <span class="review-rating">9</span></div>
                                        <div class="review-text">Loer ipsum dolor sit amet, consectetur adipiscing elit.</div>
                                    </div>
                                </div>
                            </div>
                        
                            <div class="review-form">
                                <textarea placeholder="Write your thoughts..." class="custom-textarea"></textarea>
                                <div class="form-actions">
                                    <div class="rating-picker">
                                        <span>Score:</span>
                                        <select class="custom-select">
                                            <option v-for="n in 10" :key="n" :value="n">{{ n }}</option>
                                        </select>
                                    </div>
                                    <v-btn class="btn-send" size="small">Post Review</v-btn>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </v-card>
        </v-dialog>
    </div>
</template>


<style scoped>
.game-modal {
    background: #0d0d0d ;
    border: 1px solid #1f1f1f ;
    border-radius: 16px ;
    overflow: hidden;
    color: #fff;
}

.modal-body {
    display: flex;
    flex-direction: row;
    min-height: 500px;
}

@media (max-width: 600px) {
    .modal-body { flex-direction: column; }
}

.modal-aside {
    width: 300px;
    background: #111;
    border-right: 1px solid #1f1f1f;
    display: flex;
    flex-direction: column;
}

.modal-image {
    width: 100%;
    height: 380px;
    object-fit: cover;
}

.modal-main-info {
    padding: 20px;
}

.btn-add-collection {
    background: #fff ;
    color: #000 ;
    text-transform: none;
    font-weight: 600;
    border-radius: 8px;
    margin-bottom: 20px;
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

.stat-label { color: #666; }
.stat-value { color: #eee; }

.modal-content-area {
    flex: 1;
    display: flex;
    flex-direction: column;
    padding: 20px;
    position: relative;
}

.modal-title {
    font-size: 28px ;
    font-weight: 700 ;
    padding: 0 0 20px 0 ;
    line-height: 1.2;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.close-btn { color: #555; }

.reviews-section {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.section-title {
    font-size: 14px;
    text-transform: uppercase;
    color: #444;
    letter-spacing: 1px;
    margin-bottom: 15px;
}

.reviews-list {
    flex: 1;
    max-height: 200px;
    overflow-y: auto;
    margin-bottom: 20px;
}

.review-item {
    display: flex;
    gap: 12px;
    padding: 12px;
    background: #161616;
    border-radius: 10px;
    margin-bottom: 10px;
}

.review-avatar {
    width: 32px;
    height: 32px;
    background: #333;
    border-radius: 50%;
}

.review-author {
    font-size: 13px;
    font-weight: 500;
    color: #fff;
    margin-bottom: 4px;
}

.review-rating {
    color: #38ef7d;
    margin-left: 8px;
}

.review-text {
    font-size: 13px;
    color: #aaa;
}

.review-form {
    background: #111;
    border: 1px solid #1f1f1f;
    border-radius: 12px;
    padding: 12px;
}

.custom-textarea {
    width: 100%;
    background: transparent;
    border: none;
    color: #fff;
    font-size: 13px;
    resize: none;
    outline: none;
    min-height: 60px;
}

.form-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 10px;
    padding-top: 10px;
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
    background: #333 ;
    color: #fff ;
    text-transform: none;
    font-size: 12px ;
}

.catalog-page {
    display: grid;
    grid-template-columns: 180px 1fr;
    min-height: 100vh;
    background: #0a0a0a;
    color: #e8e8e8;
}

.sidebar {
    padding: 20px 16px;
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
    background: #0d0d0d;
    border-right: 1px solid #181818;
}

.logo {
    font-size: 16px;
    font-weight: 500;
    margin-bottom: 24px;
    color: #f2f2f2;
    letter-spacing: 0.4px;
}

.menu h3 {
    font-size: 11px;
    font-weight: 500;
    margin: 18px 0 10px;
    color: #8a8a8a;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}

.menu a {
    display: block;
    color: #9a9a9a;
    text-decoration: none;
    margin-bottom: 8px;
    font-size: 12px;
    font-weight: 300;
    transition: 0.15s;
}

.menu a:hover {
    color: #e2e2e2;
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
    background: #111111;
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
    color: #666666;
}

.v-card {
    background: #050505 ;
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
    color: #ffffff;
    font-weight: 400;
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
</style>