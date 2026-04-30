<script setup>
    import { ref } from 'vue'
    import axios from '../plugins/axios'
    import router from '@/router';
    import { auth } from '../plugins/userinfo'
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
            try {
                await axios.post("/api/register", {email: email.value, username: username.value, password: password.value})
                dialog.value = true

                try{
                    await axios.post("/login", {email: email.value, password: password.value})
                    auth.user = (await axios.get("/api/user")).data 
                    router.push("/")
                }
                catch{
                    console.log("login error")
                    router.push("/login")
                }

                username.value = ''
                email.value = ''
                password.value = ''
                passwordConfirmation.value = ''


            }
            catch{
                console.log("register Error");
            } 

        }
        else {
            console.log("Error")
            dialogerror.value = true
        } 
    }
 
       
    const usernameRules = [
        value => {
            if (value) return true
            return 'Lietotājvārds ir obligāts.'
        },
        value => {
            if (value?.length <= 10) return true
            return 'Lietotājvārdam jābūt īsākam par 10 rakstzīmēm.'
        },
        async value => {
            await axios.get('/sanctum/csrf-cookie')
            const response = await axios.post('/api/usernamecheck', { username: value })
            if (response.data.exists === true) {
                return 'Lietotājvārds jau ir aizņemts.'
            }
            return true
        },
        value => {
            if (/^[a-zA-Z][\w]*$/.test(value)){
                return true
            }
            return 'Lietotājvārds nevar sākties ar ciparu vai saturēt speciālos simbolus.'
        }
  ]

    const passwordRules = [
        value => {
            if (value) return true
            return 'Parole ir obligāta.'
        },
        value => {
            if (value?.length >= 8) return true
            return 'Parolei jābūt vismaz 8 rakstzīmēm.'
        },
    ]

    const emailRules = [
        value => {
            if (value) return true
            return 'E-pasts ir obligāts.'
        },
        value => {
            if (/.+@.+\..+/.test(value)) return true
            return 'E-pasta adresei jābūt derīgai.'
        },
        async value => {
            await axios.get('/sanctum/csrf-cookie')
            const response = await axios.post('/api/emailcheck', { email: value })
            if (response.data.exists === true) {
                return 'E-pasts jau ir reģistrēts.'
            }
            return true
        }
    ]

    const passwordConfirmationRules = [
        value => {
            if (value) return true
            return 'Paroles apstiprinājums ir obligāts.'
        },
        value => {
            if (value === password.value) return true
            return 'Paroles nesakrīt.'
        },
    ] 
  
</script>



<template>
    <div class="page"> 
        <div class="background"><img alt="Background" src="/backgrounds/register-background.png"></div>
        <div class="page-wrapper">
            <div class="register-form">
                <v-form v-model="valid" @submit.prevent="register">
                    <v-container>
                        <h1>Reģistrācija</h1>

                        <v-col class="register-row" cols="12" >
                            <v-text-field
                                v-model="username"
                                :rules="usernameRules"
                                :counter="10"
                                label="Lietotājvārds"
                                required
                            ></v-text-field>
                        </v-col>
                    

                        <v-col class="register-row" cols="12" >
                            <v-text-field
                                v-model="email"
                                :rules="emailRules"
                                label="E-pasts"
                                required
                            ></v-text-field>
                        </v-col>                   
                   
                        <v-col class="register-row" cols="12" >
                            <v-text-field
                                type="password"
                                v-model="password"
                                :rules="passwordRules"
                                label="Parole"
                                required
                            ></v-text-field>
                        </v-col>
                    
                        <v-col class="register-row" cols="12" >
                            <v-text-field
                                type="password"
                                v-model="passwordConfirmation"
                                :rules="passwordConfirmationRules"
                                label="Apstiprināt paroli"
                                required
                            ></v-text-field>
                        </v-col>

                    <v-btn type="submit" :disabled="!valid" class="register-button">IZVEIDOT KONTU</v-btn>
                    </v-container>
                </v-form>
            </div>
        </div>

        <v-dialog max-width="500" v-model="dialog">
            <v-card class=dialog>
                <v-card-title class="v-card-title">Reģistrācija</v-card-title>
                <v-card-text class="v-card-text">Konts veiksmīgi izveidots!</v-card-text>
            
            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn class="v-dialog-button" @click="dialog = false"><p class="v-btn-text">Aizvērt</p></v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    
        <v-dialog max-width="500" v-model="dialogerror">
            <v-card>
                <v-card-title class="v-card-title">Reģistrācijas kļūda</v-card-title>
                <v-card-text class="v-card-text">Lūdzu, pārbaudiet ievadītos datus un mēģiniet vēlreiz.</v-card-text>
            
                <v-card-actions>
                    <v-spacer></v-spacer>
                
                    <v-btn class="v-dialog-button" @click="dialogerror = false"><p class="v-btn-text">Aizvērt</p></v-btn>
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
