<script setup>
  import { ref } from 'vue'
  import axios from '../plugins/axios'
  const email = ref("")
  const username = ref("")
  const password = ref("")
  const passwordConfirmation = ref("")
  const valid = ref(false)
  const dialog = ref(false)
  const dialogerror = ref(false)
  async function register() {
    if (valid.value === true){
      await axios.get('/sanctum/csrf-cookie')
      await axios.post("/api/register", {email: email.value, username: username.value, password: password.value})
      console.log("Success")
      dialog.value = true

      username.value = ''
      email.value = ''
      password.value = ''
      passwordConfirmation.value = ''
    }
    else {
      console.log("Error")
      dialogerror.value = true
    }
  }
       
  const usernameRules = [
    value => {
      if (value) return true
      return 'Username is required.'
    },
    value => {
      if (value?.length <= 10) return true
      return 'Username must be less than 10 characters.'
    },
    async value => {
      await axios.get('/sanctum/csrf-cookie')
      const response = await axios.post('/api/usernamecheck', { username: value })
      if (response.data.exists === true) {
        return 'Username already in use.'
      }
      return true
    }
  ]

  const passwordRules = [
    value => {
      if (value) return true
      return 'Password is required.'
    },
    value => {
      if (value?.length >= 8) return true
      return 'Password must be at least 8 characters.'
    },
  ]

  const emailRules = [
    value => {
      if (value) return true
      return 'E-mail is required.'
    },
    value => {
      if (/.+@.+\..+/.test(value)) return true
      return 'E-mail must be valid.'
    },
    async value => {
      await axios.get('/sanctum/csrf-cookie')
      const response = await axios.post('/api/emailcheck', { email: value })
      if (response.data.exists === true) {
        return 'Email already in use.'
      }
      return true
    }
  ]

  const passwordConfirmationRules = [
    value => {
      if (value) return true
      return 'Password confirmation is required.'
    },
    value => {
      if (value === password.value) return true
      return 'Passwords do not match.'
    },
  ]
  
</script>



<template>
  <div class="page"> 
    <div class="background"><img src="/backgrounds/register-background.png"></div>
    <div class="page-wrapper">
        <div class="register-form">
            <v-form v-model="valid" @submit.prevent="register">
                <v-container>
                    <h1>Register</h1>

                    <v-col class="register-row" cols="12" >
                      <v-text-field
                        v-model="username"
                        :rules="usernameRules"
                        :counter="10"
                        label="Username"
                        required
                      ></v-text-field>
                    </v-col>
                    

                    <v-col class="register-row" cols="12" >
                      <v-text-field
                        v-model="email"
                        :rules="emailRules"
                        label="E-mail"
                        required
                      ></v-text-field>
                    </v-col>                   
                   
                    <v-col class="register-row" cols="12" >
                      <v-text-field
                        type="password"
                        v-model="password"
                        :rules="passwordRules"
                        label="Password"
                        required
                      ></v-text-field>
                    </v-col>
                    
                    <v-col class="register-row" cols="12" >
                      <v-text-field
                        type="password"
                        v-model="passwordConfirmation"
                        :rules="passwordConfirmationRules"
                        label="Confirm Password"
                        required
                      ></v-text-field>
                    </v-col>

                    <v-btn type="submit" :disabled="!valid" class="register-button">CREATE ACCOUNT</v-btn>
                </v-container>
            </v-form>
        </div>
    </div>

    <v-dialog max-width="500" v-model="dialog">
      <v-card class=dialog>
        <v-card-title class="v-card-title">Registration</v-card-title>
        <v-card-text class="v-card-text">
          Successfully created account!
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

    <v-dialog max-width="500" v-model="dialogerror">
        <v-card>
          <v-card-title class="v-card-title">Registration error</v-card-title>
          <v-card-text class="v-card-text">
            Please check your data and try again.
          </v-card-text>

          <v-card-actions>
            <v-spacer></v-spacer>

            <v-btn
              class="v-dialog-button"
              @click="dialogerror = false"
            >
              <p class="v-btn-text">Close</p>
            </v-btn>
          </v-card-actions>
        </v-card>
    </v-dialog>

  </div>
</template>

<style scoped>

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


    h1{
        color:white;
        width: 100%;
        text-align: center;
        font-size: 30px;
    }

    .background {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: -1;
    }

    .register-form {
        width: 100%;
        max-width: 420px;
        min-height: 500px;
        display: flex;
        flex-direction: column;

        padding: 40px;

        background: rgba(0, 0, 0, 0.452);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 16px;
        backdrop-filter: blur(8px);

    }

    .register-row {
        color: white;
    }

    .page-wrapper {
        min-height: 70vh;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    

    .background::after {
        content: '';
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.5);
    }

    .register-button {
        background: rgba(0, 0, 0, 0.452);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 16px;
        color:rgb(255, 255, 255);
        width: 100%;
        margin-top: 30px;

    }

    .register-button:disabled{
        background: rgb(0, 0, 0);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 16px;
        color:rgba(255, 255, 255, 0.228)
    }
</style>
