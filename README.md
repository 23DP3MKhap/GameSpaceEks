# GameSpace Documentation

## Content
1. [What is GameSpace](#what-is-gamespace)
2. [Technologies](#technologies)
    * [Frontend](#frontend)
    * [Backend](#backend)
    * [.exe file](#exe-file)
3. [How to start](#how-to-start)
    * [Before start (Dependencies)](#Before-start-Dependencies)
    * [1. variant (via .exe)](#1-variant-via-exe)
    * [2. variant (manual)](#2-variant-manual)
4. [TestCases table](#testcases-table)


## What is GameSpace
A full-stack web application based on the IGDB API for viewing games and creating collections, which combines advanced filtering and searching capabilities, personal collections with progress tracking and statistics, user reviews, and an admin panel. Deployed on Vercel and Railway(expired). Built with Laravel, MySQL, and Vue.js


## Technologies 

### Backend (Servera puse)
* **PHP (v8.4.0)** -- īpaši izstrādāts tīmekļa izstrādei, padarot to daudz ātrāku un vieglāku. Tas ir pielāgots HTTP pieprasījumiem un datubāzes mijiedarbībai. Satur noderīgas iebūvētas funkcijas darbam ar datiem, piemēram, `date()`, `rand()`, `implode()`.
    * **Laravel (v12.0)** -- ietvars, kas izvēlēts darba vienkāršošanas, paātrināšanas un strukturēšanas nolūkos. Nodrošina gatavu projekta struktūru, maršrutēšanu, validāciju un autentifikāciju.
        * **Eloquent ORM** -- ļauj strādāt ar datubāzi bez SQL vaicājumu rakstīšanas.
        * **Iebūvētā migrāciju sistēma** -- ļauj pārvaldīt datubāzi ar koda palīdzību un viegli pārcelt to uz citu vidi (piemēram, hostingu).
        * **Pieprasījumu validācija** -- nodrošina ērtu lietotāja ievades apstrādi un kļūdu izvadīšanu klientu daļai.
        * **Laravel Blade** -- iebūvētais veidņu dzinējs, kas izmantots verifikācijas e-pastu HTML satura ģenerēšanai.
        * **Laravel Sanctum (v4.0)** -- viegla pakotne API autentifikācijai caur pilnvarām (tokens) SPA vidē. Satur savu `auth:sanctum` middleware aizsardzībai un ir vienkāršāka alternatīva pilnajam OAuth2 protokolam (Laravel Passport).

### Datubāzes un lokālā vide
* **MySQL (v8.4.3)** -- relāciju datubāzu pārvaldības sistēma. Pieprasa skaidru strukturizāciju un saites starp tabulām (piemēram, *many-to-many* starp spēlēm un žanriem). Izvēlēta pilnīgas saderības dēļ ar Laravel un Eloquent ORM.
* **Laragon (v7.0.6)** -- bezmaksas lokālā izstrādes vide ar vienkāršu saskarni projekta testēšanai pirms izvietošanas uz hostinga.
    * **HeidiSQL** -- rīks, kas iet komplektā ar Laragon un ļauj manuāli rediģēt datubāzi.

### Frontend (Klienta daļa)
* **HTML (v5)** -- tīmekļa lapu izstrādes standarts ar universālu visu pārlūkprogrammu atbalstu.
* **CSS (v3)** -- kaskādstilu valoda pilnīgai stila pielāgošanai bez papildu atkarībām. Izmantota arī specifiskā funkcija `:deep()`, lai pārdefinētu stilus Vuetify komponentu iekšienē.
* **JavaScript (vES2025)** -- standarta programmēšanas valoda klienta daļas izstrādei, ko pilnībā atbalsta pārlūkprogrammas.
    * **Vue.js (v3.5.26)** -- ietvars reaktīvu lietotāja saskarņu izveidei, bāzēts uz komponentu arhitektūru.
        * **Composition API** -- ļauj grupēt loģiku pēc funkcionalitātes un atkārtoti izmantot kodu starp komponentiem.
        * **Vue Router (v4.6.4)** -- oficiālā Vue bibliotēka, kas nodrošina pilnu savietojamību un SPA navigāciju bez lapas pārlādēšanas.
        * **Vuetify (v3.11.6)** -- gatavu UI komponentu bibliotēka, kas pilnībā realizē Vue reaktīvo sistēmu un ietaupa izstrādes laiku.
    * **Axios (v1.13.6)** -- uz Promise bāzēts HTTP klients. Automātiski pārveido atbildes JSON formātā un ļauj konfigurēt lietošanas šablonus (piemēram, automātisku `Authorization: Bearer` galveņu iestatīšanu).
        * **Interceptors sistēma** -- satur `interceptors.response`, kas ļauj pārtvert kļūdas un ērti realizēt lietotāja izrakstīšanu, ja sesija ir beigusies laika limita dēļ.
    * **Web API** -- standarta pārlūkprogrammas rīki, piemēram, `IntersectionObserver` un `localStorage`, kas izmantoti bez papildu bibliotēku uzstādīšanas.

### .exe file
* **Lorem** -- ipsum dolor sit amet, consectetur adipiscing elit. 
* **Lorem** -- ipsum dolor sit amet, consectetur adipiscing elit. 
* **Lorem** -- ipsum dolor sit amet, consectetur adipiscing elit. 


## How to start

### Before start (Dependencies)


### 1. variant (via .exe)
1. Lorem -- ipsum dolor sit amet, consectetur adipiscing elit. 
2. Lorem -- ipsum dolor sit amet, consectetur adipiscing elit. 
3. Lorem -- ipsum dolor sit amet, consectetur adipiscing elit. 

### 2. variant (manual)
1. Lorem -- ipsum dolor sit amet, consectetur adipiscing elit. 
2. Lorem -- ipsum dolor sit amet, consectetur adipiscing elit. 
3. Lorem -- ipsum dolor sit amet, consectetur adipiscing elit. 



## TestCases table

|Lorem | Lorem | Lorem| Lorem| Lorem |
| :--- | :--- | :--- | :--- | :---:  |
| Lorem | Lorem | Lorem | Lorem | Lorem |
| Lorem | ILorem | Lorem| Lorem | Lorem |