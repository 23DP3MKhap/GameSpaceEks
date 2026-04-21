<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from '../plugins/axios'
import { useRoute } from 'vue-router'

const route = useRoute()

const profileusername = ref("placeholder_user")
const profileBio = ref("No bio yet.")
const profileRole = ref("user")
const profileAvatar = ref(null)

const collection = ref([])

const activeFilter = ref('all')

const filters = [
  { key: 'all',       label: 'All' },
  { key: 'playing',   label: 'Playing' },
  { key: 'completed', label: 'Completed' },
  { key: 'planned',   label: 'Planned' },
  { key: 'dropped',   label: 'Dropped' },
]

const statusMeta = {
  playing:   { color: '#38ef7d', label: 'Playing' },
  completed: { color: '#4a9eff', label: 'Completed' },
  planned:   { color: '#aaa',    label: 'Planned' },
  dropped:   { color: '#ff5f5f', label: 'Dropped' },
}

const filteredCollection = computed(() => {
  if (activeFilter.value === 'all') return collection.value
  return collection.value.filter(item => item.status === activeFilter.value)
})

const counts = computed(() => {
  const c = { all: collection.value.length }
  filters.slice(1).forEach(f => {
    c[f.key] = collection.value.filter(i => i.status === f.key).length
  })
  return c
})

const initials = computed(() => {
  return profileusername.value.slice(0, 2).toUpperCase()
})

onMounted(async () => {
  profileusername.value = (await axios.get("/api/user/username", { params: { id: route.params.id } })).data
  collection.value = (await axios.get("/api/user/collection", { params: { user_id: route.params.id } })).data
  
})
</script>

<template>
  <div class="profile-page">


    <div class="profile-wrapper">
      <div class="profile-header">
        <div class="avatar-wrap">
          <img v-if="profileAvatar" :src="profileAvatar" class="avatar-img" />
          <div v-else class="avatar-placeholder">{{ initials }}</div>
        </div>

        <div class="profile-meta">
          <div class="profile-name">{{ profileusername }}</div>
          <div class="profile-role-badge" :class="profileRole === 'admin' ? 'role-admin' : 'role-user'">
            {{ profileRole === 'admin' ? 'Admin' : 'User' }}
          </div>
          <div class="profile-bio">{{ profileBio }}</div>
        </div>

        <div class="profile-stats">
          <div class="stat-block" v-for="f in filters.slice(1)" :key="f.key">
            <span class="stat-num" :style="{ color: statusMeta[f.key].color }">{{ counts[f.key] }}</span>
            <span class="stat-lbl">{{ f.label }}</span>
          </div>
        </div>
      </div>

      <div class="collection-section">
        <div class="section-top">
          <h2 class="section-title">Collection</h2>
          <div class="filter-tabs">
            <button
              v-for="f in filters"
              :key="f.key"
              class="filter-tab"
              :class="{ active: activeFilter === f.key }"
              @click="activeFilter = f.key"
            >
              {{ f.label }}
              <span class="tab-count">{{ counts[f.key] }}</span>
            </button>
          </div>
        </div>

        <div class="collection-grid">
          <div
            class="game-entry"
            v-for="item in filteredCollection"
            :key="item.id"
          >
            <img :src="item.game.image" :alt="item.game.name" class="entry-cover" />
            <div class="entry-info">
              <div class="entry-name">{{ item.game.name }}</div>
              <div class="entry-status" :style="{ color: statusMeta[item.status].color }">
                <span class="status-dot" :style="{ background: statusMeta[item.status].color }"></span>
                {{ statusMeta[item.status].label }}
              </div>
              <div class="entry-notes" v-if="item.notes">{{ item.notes }}</div>
            </div>
            <div class="entry-score" v-if="item.user_score !== null">
              <span class="score-num">{{ item.user_score }}</span>
              <span class="score-max">/10</span>
            </div>
          </div>

          <div v-if="filteredCollection.length === 0" class="empty-state">
            Nothing here yet.
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.profile-page {
  min-height: 100vh;
  background: #0a0a0a;
  color: #e8e8e8;
}

.profile-banner {
  width: 100%;
  height: 180px;
  background: linear-gradient(135deg, #0f0f0f 0%, #141414 50%, #0d0d0d 100%);
  border-bottom: 1px solid #1a1a1a;
  position: relative;
  overflow: hidden;
}

.profile-wrapper {
  max-width: 960px;
  margin: 0 auto;
  padding: 100px 24px 60px;
  
}

.profile-header {
  display: flex;
  align-items: flex-end;
  gap: 24px;
  margin-top: -48px;
  margin-bottom: 40px;
  flex-wrap: wrap;
}

.avatar-wrap {
  flex: 0 0 auto;
}

.avatar-img {
  width: 96px;
  height: 96px;
  border-radius: 50%;
  border: 3px solid #0a0a0a;
  object-fit: cover;
}

.avatar-placeholder {
  width: 96px;
  height: 96px;
  border-radius: 50%;
  border: 3px solid #0a0a0a;
  background: #1a1a1a;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  font-weight: 500;
  color: #555;
  letter-spacing: 2px;
}

.profile-meta {
  flex: 1;
  min-width: 0;
  padding-bottom: 4px;
}

.profile-name {
  font-size: 22px;
  font-weight: 500;
  color: #f5f5f5;
  margin-bottom: 6px;
}

.profile-role-badge {
  display: inline-block;
  font-size: 10px;
  font-weight: 500;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  padding: 2px 8px;
  border-radius: 4px;
  margin-bottom: 10px;
}

.role-admin {
  background: rgba(74, 158, 255, 0.12);
  color: #4a9eff;
  border: 1px solid rgba(74, 158, 255, 0.2);
}

.role-user {
  background: rgba(255,255,255,0.05);
  color: #666;
  border: 1px solid rgba(255,255,255,0.08);
}

.profile-bio {
  font-size: 13px;
  color: #666;
  font-weight: 300;
  line-height: 1.5;
}

.profile-stats {
  display: flex;
  gap: 28px;
  padding-bottom: 8px;
  flex-wrap: wrap;
}

.stat-block {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
}

.stat-num {
  font-size: 20px;
  font-weight: 500;
  line-height: 1;
}

.stat-lbl {
  font-size: 10px;
  color: #555;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.collection-section {}

.section-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
  flex-wrap: wrap;
  gap: 12px;
}

.section-title {
  font-size: 14px;
  font-weight: 500;
  color: #f5f5f5;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  margin: 0;
}

.filter-tabs {
  display: flex;
  gap: 4px;
  flex-wrap: wrap;
}

.filter-tab {
  background: transparent;
  border: 1px solid #1a1a1a;
  border-radius: 6px;
  color: #555;
  font-size: 11px;
  padding: 4px 10px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: color 0.15s, border-color 0.15s, background 0.15s;
}

.filter-tab:hover {
  color: #aaa;
  border-color: #2a2a2a;
}

.filter-tab.active {
  color: #f5f5f5;
  border-color: #333;
  background: #161616;
}

.tab-count {
  font-size: 10px;
  color: inherit;
  opacity: 0.6;
}

.collection-grid {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.game-entry {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 10px 14px;
  background: #111;
  border: 1px solid #181818;
  border-radius: 10px;
  transition: border-color 0.15s, background 0.15s;
}

.game-entry:hover {
  background: #141414;
  border-color: #222;
}

.entry-cover {
  width: 40px;
  height: 54px;
  object-fit: cover;
  border-radius: 4px;
  flex: 0 0 auto;
  filter: brightness(0.9);
}

.entry-info {
  flex: 1;
  min-width: 0;
}

.entry-name {
  font-size: 13px;
  font-weight: 400;
  color: #efefef;
  margin-bottom: 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.entry-status {
  font-size: 11px;
  display: flex;
  align-items: center;
  gap: 5px;
  margin-bottom: 4px;
}

.status-dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  flex: 0 0 auto;
}

.entry-notes {
  font-size: 11px;
  color: #555;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.entry-score {
  flex: 0 0 auto;
  text-align: right;
}

.score-num {
  font-size: 18px;
  font-weight: 500;
  color: #f5f5f5;
}

.score-max {
  font-size: 11px;
  color: #444;
}

.empty-state {
  text-align: center;
  color: #333;
  font-size: 13px;
  padding: 40px 0;
}
</style>