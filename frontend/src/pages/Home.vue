<script setup>
import { ref, onMounted } from 'vue'

const apiStatus = ref(null) 

onMounted(async () => {
  try {
    const response = await fetch('http://localhost:8000/')
    apiStatus.value = response.ok
  } catch (error) {
    apiStatus.value = false
  }
})
</script>


<template>
    
    <div class="main-text">

        <h1>DISCOVER GAMES WITHOUT LIMITS</h1>
        <p>Explore worlds, genres and platforms All your games in one space</p>
    </div>

    <div class="main-cards">
        
        <div class="about">
            <v-card class="v-about" variant="outlined">
              <v-card-title class="about-title">HOW TO START</v-card-title>
              <v-card-text class="about-text">
                Start your adventure by logging into your account
              </v-card-text>
              <v-card-actions>
                <v-btn class="card-register">REGISTER</v-btn>
                <span class="separator">|</span>
                <v-btn class="card-login">LOG IN</v-btn>
              </v-card-actions>
            </v-card>
        </div>

        <div class="api-status">
            <v-card class="v-api-status" variant="outlined">
              <v-card-title class="api-status-title">API Status</v-card-title>
              <v-card-text class="api-text">
                <div v-if="apiStatus === null">Checking API status...</div>
                <div v-else-if="apiStatus === true" style="color: lightgreen;">API is online</div>
                <div v-else style="color: red;">API is offline</div>
              </v-card-text>
            </v-card>
        </div>
    </div>

</template>


<style scoped>
.main-text {
color: white;
margin-top: 50px;
padding: 30px;
text-align: center;
}

.main-text h1 {
  font-size: 56px;
  font-weight: 500;
  letter-spacing: 2px;
}

.main-text p {
  font-size: 16px;
  opacity: 0.85;
}

.main-cards {
  color: white;
  margin: 60px;
  gap: 32px;
  display: flex;
  justify-content: center;
}

.about, .api-status {
  width: 500px;
}

.v-about, .v-api-status {
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.14);
  background: linear-gradient(rgba(0, 0, 0, 0.35) 100%);
  padding: 28px;
  min-height: 220px;
}

.about-title, .api-status-title {
  font-size: 28px;
  font-weight: 600;
  letter-spacing: 1px;
  text-transform: uppercase;
  padding: 0; 
  margin-bottom: 14px;
}
.about-text, .api-text {
  padding: 0;            
  font-size: 14px;
  line-height: 1.6;
  opacity: 0.85;
}

.card-register, .card-login {
  border-radius: 12px;
  border: 1px solid rgb(255, 255, 255);
  transition: border-color 0.2s ease, color 0.2s ease;
}

.card-register:hover, .card-login:hover {
  border-color: rgba(255, 255, 255, 0.476);
  color: rgba(255, 255, 255, 0.476)
}

@media (max-width: 1200px) {
  .main-cards {
    padding: 0 28px;
    gap: 24px;
  }

  .main-text {
    padding: 28px;
  }

  .main-text h1 {
    font-size: 48px;
  }

  .card-login, .card-register {
    font-size: 10px;

  }
}

@media (max-width: 900px) {
  .main-text {
  padding: 24px 20px;
  }

  .main-text h1 {
    font-size: 40px;
    letter-spacing: 1px;
  }

  .main-cards {
    flex-direction: column;
    align-items: center;
    padding: 0 20px;
    gap: 20px;
  }

  .about,
  .api-status {
    width: 100%;
    max-width: 640px;
  }

  .v-about,
  .v-api-status {
    padding: 22px;
  }

  .about-title,
  .api-status-title {
    font-size: 24px;
  }
}

@media (max-width: 520px) {
  .main-text {
    margin-top: 28px;
    padding: 18px 16px;
    text-align: left;
  }

  .main-text h1 {
    font-size: 30px;
    line-height: 1.1;
  }

  .main-text p {
    font-size: 14px;
  }

  .main-cards {
    margin-top: 28px;
    padding: 0 16px;
  }

  .v-about,
  .v-api-status {
    padding: 18px;
    min-height: 200px;
  }

  .about-title,
  .api-status-title {
    font-size: 20px;
  }

  .card-register,
  .card-login {
    border-radius: 10px;
  }
}
</style>
