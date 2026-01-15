<script setup></script>

<template>

<header>
    <div class="header">
        <nav>      
            <a class="logo" href="#">GAMESPACE</a>
            <a href="#">CATALOG</a>
            <a href="#">ABOUT</a>  
        </nav>

        <nav>
            <a href="#">LOG IN</a>
            <p >|</p>
            <a href="#">REGISTER</a>
        </nav>
    </div>
</header>
</template>



<style scoped>
    header {
        position: sticky;
        top: 0;
        width: 100%;
        z-index: 10000;
    }

    .header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 24px 40px;
        margin: 0 auto;
    }

    nav {
        display:flex;
        gap: 40px;
    }

    nav a{
        font-size: 12px;
        letter-spacing: 2px;
        text-decoration: none;
        transition: color 0.2s;
        color: rgb(255, 255, 255);
        display: inline-flex; 
        align-items: center;
    }

    nav a:hover{
        color:rgba(255, 255, 255, 0.406);
    }

    p {
         маыцсfont-size: 12px;
        letter-spacing: 2px;
        text-decoration: none;
        transition: color 0.2s;
        color: rgb(255, 255, 255);
        display: inline-flex; 
        align-items: center;
    }

    .logo {
    font-size: 18px;
    font-weight: 400;
    letter-spacing: 8px;
    opacity: 0.9;
    }
</style>
