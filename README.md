# GameSpace Dokumentācija

## Saturs
1. [Kas ir GameSpace](#kas-ir-gamespace)
2. [Tehnoloģijas](#tehnoloģijas)
    * [Frontend](#frontend)
    * [Backend](#backend)
    * [.exe fails](#exe-fails)
3. [Kā sākt](#kā-sākt)
    * [Pirms sākuma (Atkarības)](#pirms-sākuma-atkarības)
    * [1. variants (izmantojot .exe)](#1-variants-izmantojot-exe)
    * [2. variants (manuāli)](#2-variants-manuāli)
4. [TestCases tabula](#testcases-tabula)


## Kas ir GameSpace
Full-stack tīmekļa lietotne, kas bāzēta uz IGDB API spēļu pārlūkošanai un kolekciju veidošanai. Tā apvieno uzlabotas filtrēšanas un meklēšanas iespējas, personīgās kolekcijas ar progresa sekošanu un statistiku, lietotāju atsauksmes, kā arī administratora paneli. Lietotne ir izvietota (deployed) Vercel un Railway (pēdējam beidzies termiņš) platformās. Izstrādāta, izmantojot Laravel, MySQL un Vue.js


## Tehnoloģijas

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

### .exe fails (Bāzēts uz Tauri & Rust)
* **Kompilēts bīnārais fails** -- Tauri lietotne apkopo Rust kodu (`lib.rs`) patstāvīgā un optimizētā `.exe` failā, kas darbojas kā operētājsistēmas fona process (Core process). Tas ir atbildīgs ir par piekļuvi OS resursiem, logu pārvaldību un komunikāciju ar frontend daļu.
* **Tauri komandas (Commands/Invoke)** -- `.exe` failā ir reģistrēti divi galvenie backend apstrādātāji (`invoke_handler`), kurus frontend daļa var izsaukt asinhroni:
    * `get_app_dir` -- pielāgota komanda pašreizējās izpildes direktorijas atrašanai. Tā izmanto `std::env::current_exe()`, lai droši noteiktu ceļu līdz mapei, kurā atrodas pats `.exe` fails, kas ir svarīgi lokālo datu glabāšanai.
* **Paplašinātā drošība un spraudņi (Plugins)** -- `.exe` faila inicializācijas posmā (`tauri::Builder`) tiek ielādēti drošības spraudņi, kas ierobežo vai atļauj kontrolētu piekļuvi operētājsistēmai:
    * `tauri_plugin_shell` -- drošai komandu palaišanai OS vidē.
    * `tauri_plugin_fs` -- kontrolētai darbam ar failu sistēmu.
    * `tauri_plugin_opener` -- saišu vai sistēmas noklusēto programmu drošai atvēršanai.


## Kā sākt

### Pirms sākuma (Atkarības)

Pirms projekta palaišanas ir nepieciešams uzstādīt un konfigurēt tālāk norādītās vides un piekļuves atslēgas (API keys):

\*  -- nepieciešams vietnes darbībai

#### 1. Datubāzes vide (MySQL) *
* **Lokālais MySQL serveris** -- lai palaistu sistēmu, datorā ir jābūt aktīvam MySQL servisam.
    * Rekomendēts izmantot lokālo vidi **[Laragon](https://laragon.org/download)**, jo tā pēc noklusējuma konfigurē datubāzi bez `root` lietotāja paroles, kas ievērojami vienkāršo sākotnējo uzstādīšanu.

#### 2. IGDB API integrācija *
* **TWITCH_APP_KEY** un **TWITCH_APP_SECRET** -- nepieciešami datubāzes sākotnējai aizpildīšanai (seeding) un kataloga bezgalīgās ritināšanas (*Infinite Scroll*) funkcijas nodrošināšanai.
    * Detalizēta instrukcija atslēgu iegūšanai ir pieejama **[IGDB rokasgrāmatā](https://api-docs.igdb.com/#account-creation)**.

#### 3. E-pastu serviss (Brevo API)
* **BREVO_API_KEY** un **Sūtītāja e-pasta adrese** -- tiek izmantoti automātisko verifikācijas e-pastu nosūtīšanai jaunu lietotāju reģistrācijas brīdī.
    * Detalizēta instrukcija atslēgu iegūšanai ir pieejama **[Brevo rokasgrāmatā](https://developers.brevo.com/docs/quickstart)**. 
    * **Sūtītāja e-pasta adrese** ir e-pasta adrese, kas ir saistīta ar jūsu Brevo kontu.
    * Šī konfigurācija **nav obligāta**, ja ir pieslēgts IGDB API. E-pastu sistēmu var apiet divos veidos:
        1. Apstiprināt manuāli reģistrēto lietotāju tieši datubāzē caur Laragon (HeidiSQL).
        2. Izmantot jau gatavu testa kontu, kas tiek izveidots automātiski datubāzes migrācijas/sēšanas laikā (nepieciešams IGDB API). 
        * **Testa konta dati:** `accountName@example.com` | **Parole:** `password`.


# Uzstādīšanas un palaišanas rokasgrāmata

---

## 1. variants (izmantojot .exe)

Projekta uzstādīšana un palaišana tiek veikta tieši caur .exe failu projekta saknes mapē (`gamespacepanel.exe`).

Lai panelis darbotos pareizi, datorā ir jābūt uzstādītam:
- **winget** (var pārbaudīt ar komandu cmd: `winget -v`)
- **WebView2** (Windows pats piedāvās lejupielādes saiti, atverot programmu, ja tas būs nepieciešams)

> -- Ļoti ieteicams palaist paneli kā administrators.

### Uzstādīšanas soļi

> **VISI PUNKTI, IZŅEMOT 6. UN 7. PUNKTU, IR JĀIZPILDA TIKAI 1 REIZI PĒC UZSTĀDĪŠANAS**

1. Palaidiet programmu `gamespacepanel.exe` / `gamespacepanel(DEBUG).exe` *(atšķirība ir tā, ka debug versijai ir pieejama DevTools konsole)*
2. Pārejiet uz cilni **Atkarības** un pārliecinieties, ka visas atkarības deg zaļā krāsā kā attēlā zemāk:
   ![Atkaribas](https://i.imgur.com/kUXwXTh.png)
   Ja kāda no atkarībām prasa lejupielādi, nospiediet lejupielādes pogu, pagaidiet līdz pabeigšanai (poga pāries no lejupielādes režīma uz parasto stāvokli) un restartējiet lietojumprogrammu.
3. Pārejiet uz cilni **Uzstādīšana**. Pirms darba uzsākšanas pārliecinieties, ka MySQL statuss ir aktīvs un deg zaļā krāsā -- bez tā uzstādīšana, sākot no 4. punkta, noritēs nepareizi. Ieslēgta MySQL statusa piemērs:
   ![MySQLstatus](https://i.imgur.com/4Aax3Qr.png)
4. **Konfigurācija `.env` failam**
   `.env` faila konfigurācijā ir jānorāda API atslēgas un e-pasta adrese no sagatavošanas punkta "Pirms sākuma (Atkarības)".
   Standarta `.env` mainīgie ir automātiski konfigurēti darbam caur Laragon -- mainiet tos tikai nepieciešamības gadījumā. Piemēram, ja ir jāmaina ports vai jūs strādājat caur citu programmu, kas prasa atšķirīgu konfigurāciju.
   Pēc `.env` faila konfigurēšanas nospiediet pogu **"Saglabāt"**. Programma paziņos, vai `.env` faila saglabāšana izdevās.
5. Atgriezieties cilnē **Uzstādīšana**, vēlreiz pārliecinieties par MySQL statusu un secīgi palaidiet sistēmas konfigurācijas soļus *(secība ir svarīga!)*. Ja viss izdevās veiksmīgi, programma rādīs zaļu krāsu; ja procesa laikā radās kļūda -- sarkanu krāsu.
6. Pārejiet uz cilni **Sākums**, pārbaudiet MySQL statusu un palaidiet Frontend un Backend.
7. Atveriet Frontend vietni. Ja viss noritēja veiksmīgi, jums vajadzētu redzēt vietnes galveno lapu ar uzrakstu **"API ir tiešsaistē"**.

---

## 2. variants -- manuāli

> **VISI PUNKTI, IZŅEMOT 8. UN 9. PUNKTU, IR JĀIZPILDA TIKAI 1 REIZI PĒC UZSTĀDĪŠANAS**

Projekta uzstādīšana un palaišana tiek veikta tieši caur termināli pilnībā manuāli.

### 1. Atkarību pārbaude

Pārbaudiet, vai datorā ir uzstādītas nepieciešamās bibliotēkas pareizai projekta darbībai:

| Bibliotēka | Pārbaudes komanda | Uzstādīšanas saite | Uzstādīšanas komanda |
|---|---|---|---|
| Node.js | `node -v` | [nodejs.org](https://nodejs.org) | `winget install -e --id OpenJS.NodeJS` |
| PHP | `php -v` | [php.net](https://www.php.net) | `winget install PHP.PHP.8.4` |
| Composer | `composer -V` | [getcomposer.org](https://getcomposer.org) | Vairākas komandas: [getcomposer.org/download/](https://getcomposer.org/download/) |

### 2. MySQL palaišana

Palaidiet MySQL (piemēram, izmantojot Laragon).
To var izdarīt arī vēlāk (līdz 6. punktam), taču ļoti ieteicams to izdarīt iepriekš.

### 3. `.env` faila konfigurācija

1. Atveriet projekta saknes mapi File Explorer logā un no turienes atveriet mapi **`backend`**.
2. Mapē izveidojiet jaunu teksta failu -- ar peles labo taustiņu uz tukšas vietas → **Jauns** → **Teksta dokuments**.
3. Piešķiriet failam nosaukumu **`.env`** *(ar punktu sākumā, bez jebkādas citas paplašinājuma daļas)*. Windows var brīdināt, ka fails paliks bez paplašinājuma -- apstipriniet to.
4. Atveriet izveidoto `.env` failu ar jebkuru teksta redaktoru (piemēram, Notepad, Notepad++, VS Code).
5. Iekopējiet failā šādu saturu:

```
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gamespace
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS=""
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"

FRONTEND_URL=http://localhost:5173

IGDBCLIENTID=
IGDBCLIENTSECRET=

BREVO_API_KEY=
```

6. Aizpildiet šādus mainīgos ar savām vērtībām:

| Mainīgais | Apraksts |
|---|---|
| `IGDBCLIENTID` | IGDB API klienta ID |
| `IGDBCLIENTSECRET` | IGDB API klienta slepenā atslēga |
| `MAIL_FROM_ADDRESS` | E-pasta adrese, no kuras tiks sūtītas vēstules |
| `BREVO_API_KEY` | Brevo pakalpojuma API atslēga |

7. Saglabājiet failu (**Ctrl+S**).

### 4. Projekta atkarību uzstādīšana

No projekta saknes mapes (cd "ceļš uz programmas saknes mapi, kurā atrodas .exe fails"):

```bash
cd backend
npm install
composer install
cd ..
cd frontend
npm install
```

### 5. Laravel atslēgas ģenerēšana

No projekta saknes mapes (cd "ceļš uz programmas saknes mapi, kurā atrodas .exe fails"):

```bash
cd backend
php artisan key:generate
```

### 6. Datubāzes `gamespace` izveide

*(Nepieciešams ieslēgts MySQL datorā)*

```bash
# Bez paroles:
echo CREATE DATABASE IF NOT EXISTS gamespace; | mysql -u root

# Ar paroli:
echo CREATE DATABASE IF NOT EXISTS gamespace; | mysql -u root -p"jūsu_root_parole"
```

### 7. Datubāzes migrāciju palaišana un aizpildīšana ar datiem

No projekta saknes mapes (cd "ceļš uz programmas saknes mapi, kurā atrodas .exe fails"):

```bash
cd backend
php artisan migrate
php artisan db:seed
```

### 8. Frontend palaišana

> -- Neaizveriet termināli ar palaisto serveri!

No projekta saknes mapes (cd "ceļš uz programmas saknes mapi, kurā atrodas .exe fails"):

```bash
cd frontend
npm run dev
```

### 9. Backend palaišana

> -- Neaizveriet termināli ar palaisto serveri!

No projekta saknes mapes (cd "ceļš uz programmas saknes mapi, kurā atrodas .exe fails"):

```bash
cd backend
php artisan serve
```

### 10. Vietnes atvēršana

Atveriet [Frontend](http://localhost:5173/).

Ja viss noritēja veiksmīgi, jums vajadzētu redzēt vietnes galveno lapu ar uzrakstu **"API ir tiešsaistē"**.
## TestCases tabulas

1. Tabula lietotājvārds ievades lauka testēšana

| Testa numurs | Ieejas dati | Izejas dati |
| :---: | :--- | :--- |
| **1** | `“”` *(tukšs)* | Lietotājvārda ievades lauks bloķē reģistrācijas pogu, un tiek parādīta kļūda: Lietotājvārds ir obligāts. |
| **2** | `1user` | Lietotājvārda ievades lauks bloķē reģistrācijas pogu, un tiek parādīta kļūda: Lietotājvārds nevar sākties ar ciparu vai saturēt speciālos simbolus. |
| **3** | `Admin` | Lietotājvārda ievades lauks bloķē reģistrācijas pogu, un tiek parādīta kļūda: Lietotājvārds jau ir aizņemts. |
| **4** | `useruesruser` | Lietotājvārda ievades lauks bloķē reģistrācijas pogu, un tiek parādīta kļūda: Lietotājvārdam jābūt īsākam par 10 rakstzīmēm. |
| **5** | `useruser!` | Lietotājvārda ievades lauks bloķē reģistrācijas pogu, un tiek parādīta kļūda: Lietotājvārds nevar sākties ar ciparu vai saturēt speciālos simbolus. |
| **6** | `user` | Lietotājvārda ievades lauks vairs neaizsedz reģistrācijas pogu. |

2. Tabula e-pasta adrese ievades lauka testēšana

| Testa numurs | Ieejas dati | Izejas dati |
| :---: | :--- | :--- |
| **1** | `“”` *(tukšs)* | E-pasta adreses ievades lauks bloķē reģistrācijas pogu un parāda kļūdu: E-pasts ir obligāts. |
| **2** | `epasts` | E-pasta adreses ievades lauks bloķē reģistrācijas pogu un parāda kļūdu: E-pasta adresei jābūt derīgai. |
| **3** | `epasts@ep` | E-pasta adreses ievades lauks bloķē reģistrācijas pogu un parāda kļūdu: E-pasta adresei jābūt derīgai. |
| **4** | `epasts@ep.` | E-pasta adreses ievades lauks bloķē reģistrācijas pogu un parāda kļūdu: E-pasta adresei jābūt derīgai. |
| **5** | `admin@example.com` | E-pasta adreses ievades lauks bloķē reģistrācijas pogu un parāda kļūdu: E-pasts jau ir reģistrēts. |
| **6** | `epasts@ep.com` | E-pasta ievades lauks vairs neaizsedz reģistrācijas pogu. |

3. Tabula paroles  ievades lauka testēšana

| Testa numurs | Ieejas dati | Izejas dati |
| :---: | :--- | :--- |
| **1** | `“”` *(tukšs)* | Paroles ievades lauks bloķē reģistrācijas pogu un parāda kļūdu: Parole ir obligāta. |
| **2** | `pass` | Paroles ievades lauks bloķē reģistrācijas pogu un parāda kļūdu: Parolei jābūt vismaz 8 rakstzīmēm. |
| **3** | `password` | Paroles lauks vairs neaizsedz reģistrācijas pogu. |

4. Tabula paroles apstiprināšanas ievades lauka testēšana
Lai pārbaudītu datus paroles ievades laukā, vērtība tika iestatīta uz “password”

| Testa numurs | Ieejas dati | Izejas dati |
| :---: | :--- | :--- |
| **1** | `“”` *(tukšs)* | Paroles apstiprināšanas ievades lauks bloķē reģistrācijas pogu un parāda kļūdu: Paroles apstiprinājums ir obligāts. |
| **2** | `pass` | Paroles apstiprināšanas ievades lauks bloķē reģistrācijas pogu un parāda kļūdu: Paroles nesakrīt. |
| **3** | `password` | Paroles apstiprināšanas lauks vairs neaizsedz reģistrācijas pogu. |

5. Lai pārbaudītu e-pasta apstiprinājuma lauka datus, datubāzē tika instalēts kods 709829.
Tabula e-pasta adrese apstiprināšanas ievades lauka testēšana

| Testa numurs | Ieejas dati | Izejas dati |
| :---: | :--- | :--- |
| **1** | `“”` *(tukšs)* | Noklikšķinot uz validācijas pogas, serveris atgriež kļūdu 422, un lietotājam tiek parādīta šāda kļūda: Nepareizs kods. Mēģini vēlreiz. |
| **2** | `-12314` | Noklikšķinot uz validācijas pogas, serveris atgriež kļūdu 422, un lietotājam tiek parādīta šāda kļūda: Nepareizs kods. Mēģini vēlreiz. |
| **3** | `passwo` | Noklikšķinot uz validācijas pogas, serveris atgriež kļūdu 422, un lietotājam tiek parādīta šāda kļūda: Nepareizs kods. Mēģini vēlreiz. |
| **4** | `709829` *(pareizs kods)* | Veiksmīgi. Lietotājs tiek novirzīts uz galveno lapu, un viņa konts ir apstiprināts. |