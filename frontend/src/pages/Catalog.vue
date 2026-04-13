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
        if (!props.searchQuery){
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
        }))}

    })

   

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

        <v-dialog max-width="500" v-model="dialog">
            <v-card class="v-card" color="black">
                <v-card-title class="v-card-title">
                    {{ selectedGame.name }}
                </v-card-title>

                <v-card-text class="v-card-text">
                    <p><span class="modal-label">Genre:</span> {{ selectedGame.genre }}</p>
                    <p><span class="modal-label">ID:</span> {{ selectedGame.id }}</p>
                </v-card-text>

                <v-card-actions>
                    <v-spacer></v-spacer>

                    <v-btn
                        class="v-dialog-button"
                        @click="dialog = false"
                    >
                        <p class="v-btn-text">Close</p>
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>


<style scoped>
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
    background: #050505 !important;
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