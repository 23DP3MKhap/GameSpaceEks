<script setup>
  import { ref, onMounted } from 'vue'
  import axios from '../plugins/axios'
  import { auth } from '../plugins/userinfo'
  const apiStatus = ref(null) 
  const dialog = ref(false)
  const igdb_expires = ref(null)
  onMounted(async () => {
    try {
        const response = await fetch('http://localhost:8000/api/ping')
        await axios.get('/sanctum/csrf-cookie')
        igdb_expires.value = (await axios.get('/api/igdb/client')).data
        
        console.log(igdb_expires.value)
        apiStatus.value = response.ok
    } catch (error) {
        apiStatus.value = false
        console.log(error)
    }
})

  async function logout(){
        try {
            await axios.get('/sanctum/csrf-cookie')
            await axios.post("/logout")
            auth.user = null
            dialog.value = true
            return console.log("logged out")

    } catch {
        return console.log("error")
    }}

</script>


<template>
  <div class="page">
    <div class="background"><img src="/backgrounds/home-background.jpg" alt="Background"></div>
    <div class="main-text">

        <h1>DISCOVER GAMES WITHOUT LIMITS</h1>
        <p>Explore worlds, genres and platforms All your games in one space</p>
    </div>

    <div class="main-cards">
        
        <div class="about">
          <div v-if="auth.user">
            <v-card class="v-about" variant="outlined">
                <v-card-title class="about-title">WELCOME!</v-card-title>
                <v-card-text class="about-text"><router-link :to = "{path: `/User/${auth.user.id}/Profile`}" class="router-link">Hello, {{ auth.user.name }}!</router-link></v-card-text>
                <v-card-actions class="card-actions">
                  <router-link to="/Catalog" class="router-link"><v-btn class="card-register">CATALOG</v-btn></router-link>
                  <span class="separator">|</span>
                  <v-btn class="card-login" @click="logout">LOG OUT</v-btn>
                </v-card-actions>
              </v-card>
          </div>

          <div v-if="!auth.user">
              <v-card class="v-about" variant="outlined">
                <v-card-title class="about-title">HOW TO START</v-card-title>
                <v-card-text class="about-text">
                  Start your adventure by logging into your account
                </v-card-text>
                <v-card-actions class="card-actions">
                  <router-link to="/Register" class="router-link"><v-btn class="card-register">REGISTER</v-btn></router-link>
                  <span class="separator">|</span>
                  <router-link to="/Login" class="router-link"><v-btn class="card-login">LOG IN</v-btn></router-link>
                </v-card-actions>
            </v-card>
          </div>
        </div>

        <div class="api-status">
            <v-card class="v-api-status" variant="outlined">
              <v-card-title class="api-status-title">API Status</v-card-title>
              <v-card-text class="api-text">
                <div v-if="apiStatus === null">Checking API status...</div>
                <div v-else-if="apiStatus === true" > 
                    <p style="color: lightgreen;" >API is online</p>
                </div>
                <div v-else style="color: red;">API is offline</div>
              </v-card-text>
            </v-card>
        </div>
      </div>
    


  <v-dialog max-width="500" v-model="dialog">
    <v-card class="v-card" color="black">
      <v-card-title class="v-card-title">Logout</v-card-title>
      <v-card-text class="v-card-text">
        Successfully logged out!
      </v-card-text>

      <v-card-actions>
        <v-spacer></v-spacer>
        <v-btn
          class="v-dialog-button"
          @click="dialog = false" 
        ><p class="v-btn-text">Close</p></v-btn>
      </v-card-actions>
    </v-card>
</v-dialog>
</div>
</template>





<style scoped>
.v-card{
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.587);
      }

.v-card-title, .v-card-text {
  color: white;
}

.v-btn-text {
  color:white;
  font-size: 10px;
}

.dialog, .v-dialog-button{
  background-color: rgb(0, 0, 0);
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.14);
}


.background {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: -1;
}

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

.router-link{
  text-decoration: none;
  color: white;
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
  padding: 0px;
}

.card-register:hover, .card-login:hover {
  color: rgba(255, 255, 255, 0.476)
}

.card-actions {
  padding: 0px;
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
