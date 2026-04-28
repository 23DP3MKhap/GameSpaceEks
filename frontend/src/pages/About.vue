<script setup></script>

<template>
    <div class="page">
        <div class="hero">
            <div class="hero-label">GAMESPACE</div>
            <h1 class="hero-title">About the Project</h1>
            <p class="hero-sub">Catalog of games</p>
        </div>

        <div class="rows">
            <div class="row">
                <div class="row-text">
                    <h2>Hosting</h2>
                    <p><a href="https://vercel.com/" target="_blank">Vercel</a> is a hosting for a frontend part and 
                        <a href="https://render.com/" target="_blank">Render</a>
                        is a hosting for a backend part.
                    </p>
                </div>
                <div class="image-wrap">
                    <div class="image-frame">
                        <img src="https://darwin-public.s3.eu-west-1.amazonaws.com/framer/integration_logos/Vercel_Token_Auth.png" alt="Hosting">     
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="image-wrap">
                    <div class="image-frame">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/9/95/Vue.js_Logo_2.svg/1280px-Vue.js_Logo_2.svg.png" 
                        alt="Frontend">
                    </div>
                </div>
                <div class="row-text">
                    <h2>Frontend</h2>
                    <p>
                        The entire frontend was created using the <a href="https://vuejs.org/" target="_blank">Vue.js</a> 
                        framework and the <a href="https://vuetifyjs.com/" target="_blank">Vuetify</a> UI element library.
                    </p>
                </div>
            </div>

            <div class="row">
                <div class="row-text">
                    <h2>Backend</h2>
                    <p>The entire backend was built using the <a href="https://laravel.com/" target="_blank">Laravel</a> 
                        framework and a MySQL database as well as Laravel for user authentication.
                    </p>
                </div>
                <div class="image-wrap">
                    <div class="image-frame">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/9/9a/Laravel.svg/3840px-Laravel.svg.png" alt="Backend">
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>

<style scoped>
.page {
    min-height: 100vh;
    background: #0a0a0a;
    color: #e8e8e8;
    padding-bottom: 80px;
}

.hero {
    text-align: center;
    padding: 80px 24px 64px;
    border-bottom: 1px solid #141414;
}

.hero-label {
    font-size: 10px;
    letter-spacing: 4px;
    color: #333;
    text-transform: uppercase;
    margin-bottom: 20px;
}

.hero-title {
    font-size: 48px;
    font-weight: 500;
    color: #f5f5f5;
    margin: 0 0 16px;
    letter-spacing: -0.5px;
    line-height: 1.1;
}

.hero-sub {
    font-size: 14px;
    color: #555;
    font-weight: 300;
    max-width: 480px;
    margin: 0 auto;
    line-height: 1.6;
}

.rows {
    max-width: 1000px;
    margin: 0 auto;
    padding: 0 24px;
    display: flex;
    flex-direction: column;
}

.row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
    border-bottom: 1px solid #141414;
    padding: 56px 0;
    align-items: center;
}

.row-text {
    padding-right: 48px;
}

.row-text a {
    color: #61dafb;
    text-decoration: none;
    transition: color 0.2s;
}

.row-text a:hover {
    color: #f8f8f8;
}

.row-text h2 {
    font-size: 22px;
    font-weight: 500;
    color: #f5f5f5;
    margin: 0 0 14px;
    letter-spacing: -0.2px;
}

.row-text p {
    font-size: 13px;
    color: #666;
    font-weight: 300;
    line-height: 1.7;
    margin: 0;
}

.image-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
}


.image-frame img {
    width: auto;
    height: 40vh;
    object-fit: cover;
    display: block;
    opacity: 0.9;
}


.row > .image-wrap + .row-text {
    padding-right: 0;
    padding-left: 48px;
}

@media (max-width: 768px) {
    .hero-title {
        font-size: 34px;
    }

    .row {
        grid-template-columns: 1fr;
        gap: 28px;
        padding: 40px 0;
    }

    .row-text,
    .row > .image-wrap + .row-text {
        padding: 0;
    }
}

@media (max-width: 480px) {
    .hero {
        padding: 56px 16px 48px;
    }

    .hero-title {
        font-size: 28px;
    }

    .rows {
        padding: 0 16px;
    }

    .row {
        padding: 32px 0;
    }
}
</style>