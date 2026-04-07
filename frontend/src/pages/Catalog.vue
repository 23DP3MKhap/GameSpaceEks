<script setup>
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
<h1>New and trending</h1>
<p>Based on player counts and release date</p>
</section>

<section class="filters">
<button class="filter-btn">Order by: Relevance</button>
<button class="filter-btn">Platforms</button>
</section>

<section class="games-grid">
<div class="game-card" v-for="game in games" :key="game.id">
<img :src="game.image" :alt="game.title" class="game-image" />
<div class="game-info">
<div class="platforms">{{ game.platforms }}</div>
<h2>{{ game.title }}</h2>
<div class="meta">+ {{ game.added }}</div>
</div>
</div>
</section>
</main>
</div>
</template>

<script setup>
import { ref } from 'vue'

const games = ref([
{
id: 1,
title: 'Prince of Persia: The Sands of Time Remake',
image: 'https://placehold.co/600x400',
platforms: 'PC PS5 Xbox',
added: 394
},
{
id: 2,
title: 'The Wolf Among Us 2',
image: 'https://placehold.co/600x400',
platforms: 'PC PS5 Xbox',
added: 192
},
{
id: 3,
title: 'Resident Evil 9: Requiem',
image: 'https://placehold.co/600x400',
platforms: 'PC PS5 Xbox',
added: 211
}
])
</script>

<style scoped>
.catalog-page {
display: grid;
grid-template-columns: 260px 1fr;
min-height: 100vh;
background: #121212;
color: white;
}

.sidebar {
padding: 32px 24px;
position: sticky;
top: 0;
height: 100vh;
overflow-y: auto;
background: #121212;
}

.logo {
font-size: 28px;
font-weight: 800;
margin-bottom: 40px;
}

.menu h3 {
font-size: 20px;
margin: 28px 0 14px;
}

.menu a {
display: block;
color: #d0d0d0;
text-decoration: none;
margin-bottom: 12px;
font-size: 18px;
transition: 0.2s;
}

.menu a:hover {
color: white;
transform: translateX(4px);
}

.content {
padding: 40px 32px 60px;
}

.hero h1 {
font-size: 72px;
line-height: 1;
font-weight: 900;
margin: 0 0 12px;
}

.hero p {
font-size: 22px;
color: #b0b0b0;
margin-bottom: 28px;
}

.filters {
display: flex;
gap: 16px;
margin-bottom: 32px;
}

.filter-btn {
background: #222;
color: white;
border: none;
border-radius: 14px;
padding: 14px 20px;
font-size: 16px;
cursor: pointer;
}

.games-grid {
display: grid;
grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
gap: 26px;
}

.game-card {
background: #1b1b1b;
border-radius: 22px;
overflow: hidden;
transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.game-card:hover {
transform: translateY(-8px) scale(1.02);
box-shadow: 0 18px 40px rgba(0, 0, 0, 0.35);
}

.game-image {
width: 100%;
height: 220px;
object-fit: cover;
display: block;
}

.game-info {
padding: 18px;
}

.platforms {
font-size: 13px;
color: #bdbdbd;
margin-bottom: 10px;
}

.game-info h2 {
font-size: 22px;
line-height: 1.2;
margin: 0 0 14px;
}

.meta {
display: inline-block;
background: #2a2a2a;
padding: 8px 12px;
border-radius: 10px;
font-weight: 700;
}
</style>