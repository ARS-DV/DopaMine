<script setup>
// imports para Vue, router y API
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/userStore'
import { rutaApi } from '@/config.js'

const userStore = useUserStore()
const router    = useRouter()

const routines = ref([])
const loading  = ref(true)
const error    = ref('')
const filter   = ref('all')

const expandedRoutines = ref({})

function toggleExpand(id) {
  if (expandedRoutines.value[id] == true) {
    expandedRoutines.value[id] = false
  } else {
    expandedRoutines.value[id] = true
  }
}

function fetchRoutines() {
  loading.value = true
  error.value   = ''

  let url = rutaApi + "?entity=routines&user_id=" + userStore.user.id

  fetch(url)
    .then(function(response) { return response.json() })
    .then(function(data) {
      routines.value = data
      loading.value  = false
    })
    .catch(function() {
      error.value   = 'Error loading routines'
      loading.value = false
    })
}

function cycleHabitInRoutine(routine, habit) {
  let currentState = habit.done_today
  if (currentState == null) { currentState = 0 }
  let nextState = (parseInt(currentState) + 1) % 3

  fetch(rutaApi + "?entity=habits&id=" + habit.id, {
    method:  'PATCH',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({ done: nextState })
  })
  .then(function(res) { return res.json() })
  .then(function(data) {
    if (data.status === 'success') {
      habit.done_today = nextState
      updateRoutineProgress(routine)
    }
  })
}

function toggleRoutineStep(routine, step) {
  let newStatus = 1
  if (step.done == true || step.done == 1) { newStatus = 0 }

  fetch(rutaApi + "?entity=routine_checklist&id=" + step.id, {
    method:  'PATCH',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({ done: newStatus })
  })
  .then(function(res) { return res.json() })
  .then(function(data) {
    if (data.status === 'success') {
      if (newStatus == 1) { step.done = true } else { step.done = false }
      updateRoutineProgress(routine)
    }
  })
}

function updateRoutineProgress(routine) {
  let habitList = routine.habits    || []
  let stepList  = routine.checklist || []
  let totalItems = habitList.length + stepList.length
  if (totalItems == 0) return

  let completedHabits = 0
  for (let i = 0; i < habitList.length; i++) {
    if (parseInt(habitList[i].done_today) == 2) { completedHabits++ }
  }

  let completedSteps = 0
  for (let j = 0; j < stepList.length; j++) {
    if (stepList[j].done == true) { completedSteps++ }
  }

  let totalDone  = completedHabits + completedSteps
  let percentage = (totalDone / totalItems) * 100

  let finalState = 0
  if (percentage === 100) { finalState = 2 }
  else if (percentage >= 50) { finalState = 1 }

  fetch(rutaApi + "?entity=routines&id=" + routine.id, {
    method:  'PATCH',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({ done: finalState, done_steps: totalDone, total_steps: totalItems })
  })
  .then(function(res) { return res.json() })
  .then(function(data) {
    if (data.status === 'success') {
      routine.done_today  = finalState
      routine.done_steps  = totalDone
      routine.total_steps = totalItems
      if (data.current_streak !== undefined) { routine.streak = data.current_streak }
    }
  })
}

function deleteRoutine(id) {
  let check = confirm('Delete this routine?')
  if (check == false) return

  fetch(rutaApi + "?entity=routines&id=" + id, { method: 'DELETE' })
    .then(function(res) { return res.json() })
    .then(function(data) {
      if (data.status === 'success') {
        routines.value = routines.value.filter(function(r) { return r.id !== id })
      } else {
        error.value = 'Error deleting routine'
      }
    })
}

function getStatusLabel(val) {
  let v = parseInt(val)
  if (v == 2) { return 'Done' }
  if (v == 1) { return 'Tried' }
  return 'Pending'
}

function getHabitIcon(val) {
  let v = parseInt(val)
  if (v == 2) { return 'bi-check-circle-fill' }
  if (v == 1) { return 'bi-dash-circle' }
  return 'bi-circle'
}

function calculatePercentage(routine) {
  let total = (routine.habits ? routine.habits.length : 0) + (routine.checklist ? routine.checklist.length : 0)
  if (total == 0) return 0
  let doneH = routine.habits    ? routine.habits.filter(function(h)    { return parseInt(h.done_today) == 2 }).length : 0
  let doneS = routine.checklist ? routine.checklist.filter(function(s) { return s.done == true }).length : 0
  return Math.round(((doneH + doneS) / total) * 100)
}

// stats para el strip
const bestStreak   = computed(function() {
  let max = 0
  routines.value.forEach(function(r) { if ((r.streak || 0) > max) { max = r.streak } })
  return max
})
const doneTodayCount = computed(function() {
  return routines.value.filter(function(r) { return parseInt(r.done_today) == 2 }).length
})

const filteredRoutines = computed(function() {
  if (filter.value == 'daily')   { return routines.value.filter(function(r) { return r.frecuency == 'daily' }) }
  if (filter.value == 'weekly')  { return routines.value.filter(function(r) { return r.frecuency == 'weekly' }) }
  if (filter.value == 'monthly') { return routines.value.filter(function(r) { return r.frecuency == 'monthly' }) }
  if (filter.value == 'done')    { return routines.value.filter(function(r) { return parseInt(r.done_today) == 2 }) }
  if (filter.value == 'tried')   { return routines.value.filter(function(r) { return parseInt(r.done_today) == 1 }) }
  return routines.value
})

onMounted(function() { fetchRoutines() })
</script>

<template>
  <div class="routines-container">

    <!-- CABECERA -->
    <div class="d-flex align-items-end justify-content-between mb-4 fade-up">
      <h1 class="page-title">
        <em>stick to your</em>
        Routines
      </h1>
      <button class="btn-dopamine btn-dopamine-primary" @click="router.push('/routines/new')">
        <i class="bi bi-plus me-1"></i> New routine
      </button>
    </div>

    <!-- STATS STRIP -->
    <div class="row g-3 mb-4 fade-up delay-1">
      <div class="col-12 col-sm-4">
        <div class="stat-strip strip-warn">
          <div>
            <div class="stat-num">{{ bestStreak }}</div>
            <div class="stat-label">Best streak</div>
          </div>
          <i class="bi bi-fire stat-icon ms-3"></i>
        </div>
      </div>
      <div class="col-12 col-sm-4">
        <div class="stat-strip strip-ok">
          <div>
            <div class="stat-num">{{ doneTodayCount }}/{{ routines.length }}</div>
            <div class="stat-label">Done today</div>
          </div>
          <i class="bi bi-check2-square stat-icon ms-3"></i>
        </div>
      </div>
      <div class="col-12 col-sm-4">
        <div class="stat-strip strip-neutral">
          <div>
            <div class="stat-num">{{ routines.length }}</div>
            <div class="stat-label">Total routines</div>
          </div>
          <i class="bi bi-list-check stat-icon ms-3"></i>
        </div>
      </div>
    </div>

    <!-- ERROR -->
    <div v-if="error" class="error-text mb-3">
      <i class="bi bi-exclamation-triangle me-2"></i>{{ error }}
    </div>

    <!-- LOADING -->
    <div v-if="loading" class="loading-text">
      <div class="spinner-border spinner-border-sm me-2" role="status"></div>
      Loading...
    </div>

    <template v-else>

      <!-- FILTROS -->
      <div class="d-flex flex-wrap gap-2 mb-4 fade-up delay-2">
        <button class="filter-tab" :class="filter === 'all'     ? 'active' : ''" @click="filter = 'all'">All ({{ routines.length }})</button>
        <button class="filter-tab" :class="filter === 'daily'   ? 'active' : ''" @click="filter = 'daily'">Daily</button>
        <button class="filter-tab" :class="filter === 'weekly'  ? 'active' : ''" @click="filter = 'weekly'">Weekly</button>
        <button class="filter-tab" :class="filter === 'monthly' ? 'active' : ''" @click="filter = 'monthly'">Monthly</button>
        <button class="filter-tab" :class="filter === 'done'    ? 'active' : ''" @click="filter = 'done'">Done</button>
        <button class="filter-tab" :class="filter === 'tried'   ? 'active' : ''" @click="filter = 'tried'">Tried</button>
      </div>

      <!-- LISTA VACÍA -->
      <div v-if="filteredRoutines.length === 0" class="empty-state fade-up">
        <i class="bi bi-list-check empty-icon"></i>
        <p class="empty-title">No routines found</p>
        <button class="btn-dopamine btn-dopamine-primary mt-2" @click="router.push('/routines/new')">
          <i class="bi bi-plus me-1"></i> Create your first routine
        </button>
      </div>

      <!-- LISTA DE RUTINAS -->
      <div v-else class="d-flex flex-column gap-3">
        <div
          v-for="(routine, index) in filteredRoutines"
          :key="routine.id"
          class="routine-card fade-up"
          :class="parseInt(routine.done_today) == 2 ? 'card-done' : parseInt(routine.done_today) == 1 ? 'card-tried' : 'card-pending'"
          :style="{ animationDelay: (index * 0.05) + 's' }"
        >
          <!-- CABECERA -->
          <div class="rcard-header">

            <!-- IZQUIERDA: icono + título + badges + descripción + progreso -->
            <div class="flex-grow-1" style="min-width: 0">
              <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                <!-- icono según frecuencia -->
                <i
                  class="bi rcard-freq-icon"
                  :class="routine.frecuency === 'daily' ? 'bi-sun' : routine.frecuency === 'weekly' ? 'bi-calendar-week' : 'bi-calendar-month'"
                ></i>
                <span class="rcard-title">{{ routine.title }}</span>
                <span class="bdg" :class="routine.frecuency === 'daily' ? 'bdg-daily' : routine.frecuency === 'weekly' ? 'bdg-weekly' : 'bdg-monthly'">
                  {{ routine.frecuency }}
                </span>
                <span v-if="parseInt(routine.done_today) > 0" class="bdg" :class="parseInt(routine.done_today) == 2 ? 'bdg-done' : 'bdg-tried'">
                  {{ getStatusLabel(routine.done_today) }}
                </span>
                <span v-if="routine.hour" class="bdg bdg-info">
                  <i class="bi bi-clock"></i> {{ routine.hour }}
                </span>
              </div>

              <!-- descripción -->
              <p v-if="routine.descrip" class="rcard-descrip">{{ routine.descrip }}</p>

              <!-- BARRA DE PROGRESO -->
              <div class="d-flex align-items-center gap-2 mt-2">
                <div class="progress flex-grow-1" style="height: 6px; background-color: var(--vanilla-light)">
                  <div
                    class="progress-bar"
                    :style="{
                      width: calculatePercentage(routine) + '%',
                      backgroundColor: parseInt(routine.done_today) == 2 ? 'var(--state-ok)' : parseInt(routine.done_today) == 1 ? 'var(--state-warn)' : 'var(--vanilla-mid)'
                    }"
                  ></div>
                </div>
                <small class="rcard-progress-text">
                  {{ routine.done_steps || 0 }}/{{ routine.total_steps || 0 }} done
                </small>
              </div>
            </div>

            <!-- DERECHA: racha -->
            <div class="rcard-streak">
              <i class="bi bi-fire rcard-streak-icon"></i>
              <div class="rcard-streak-num">{{ routine.streak || 0 }}</div>
              <div class="rcard-streak-label">day streak</div>
            </div>
          </div>

          <!-- FILA INFERIOR: botones -->
          <div class="rcard-footer">
            <button
              class="btn-dopamine btn-dopamine-danger btn-sm rcard-btn"
              @click="deleteRoutine(routine.id)"
            >
              <i class="bi bi-trash"></i>
            </button>
            <button
              class="btn-dopamine btn-dopamine-ghost btn-sm rcard-btn"
              @click="toggleExpand(routine.id)"
            >
              <i class="bi" :class="expandedRoutines[routine.id] ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
            </button>
          </div>

          <!-- CUERPO EXPANDIDO -->
          <div v-if="expandedRoutines[routine.id]" class="rcard-body">

            <!-- HÁBITOS -->
            <div v-if="routine.habits && routine.habits.length > 0" class="mb-3">
              <p class="items-label"><i class="bi bi-arrow-repeat me-1"></i>HABITS</p>
              <div class="d-flex flex-column gap-2">
                <div
                  v-for="habit in routine.habits"
                  :key="habit.id"
                  class="inner-item habit-item"
                  :class="parseInt(habit.done_today) == 2 ? 'item-done' : ''"
                >
                  <button
                    class="inner-state-btn"
                    :class="'state-' + parseInt(habit.done_today || 0)"
                    @click="cycleHabitInRoutine(routine, habit)"
                  >
                    <i class="bi" :class="getHabitIcon(habit.done_today)"></i>
                  </button>
                  <span class="inner-text" :class="parseInt(habit.done_today) == 2 ? 'text-decoration-line-through' : ''">
                    {{ habit.title }}
                  </span>
                </div>
              </div>
            </div>

            <!-- PASOS -->
            <div v-if="routine.checklist && routine.checklist.length > 0">
              <p class="items-label"><i class="bi bi-list-check me-1"></i>STEPS</p>
              <div class="d-flex flex-column gap-2">
                <div
                  v-for="step in routine.checklist"
                  :key="step.id"
                  class="inner-item step-item"
                  :class="step.done ? 'item-done' : ''"
                >
                  <div
                    class="inner-check"
                    :class="step.done ? 'checked' : ''"
                    @click="toggleRoutineStep(routine, step)"
                  >
                    <i v-if="step.done" class="bi bi-check"></i>
                  </div>
                  <span class="inner-text" :class="step.done ? 'text-decoration-line-through' : ''">
                    {{ step.title }}
                  </span>
                </div>
              </div>
            </div>

            <p
              v-if="(!routine.habits || routine.habits.length == 0) && (!routine.checklist || routine.checklist.length == 0)"
              class="no-items-text"
            >
              <i class="bi bi-info-circle me-1"></i> No habits or steps added yet
            </p>

          </div>
        </div>
      </div>
    </template>

    <!-- FAB -->
    <button class="fab" @click="router.push('/routines/new')" title="New routine">
      <i class="bi bi-plus"></i>
    </button>

  </div>
</template>

<style scoped>
/* CONTENEDOR MÁS ANCHO */
.routines-container {
  width: 100%;
  padding: 2.5rem 3rem 5rem;
}

@media (max-width: 768px) {
  .routines-container { padding: 1.5rem 1rem 5rem; }
}

/* ROUTINE CARD */
.routine-card {
  border-radius: 12px;
  border: 1.5px solid var(--vanilla-mid);
  border-left: 4px solid var(--vanilla-mid);
  background: var(--bg-base);
  overflow: hidden;
  box-shadow: 0 2px 10px rgba(92, 51, 23, 0.07);
  transition: box-shadow 0.15s;
}

.routine-card:hover { box-shadow: 0 4px 18px rgba(92, 51, 23, 0.12); }

.card-done    { border-left-color: var(--state-ok);   background: var(--state-ok-bg); }
.card-tried   { border-left-color: var(--state-warn);  }
.card-pending { border-left-color: var(--cinnamon-mid); }

/* CABECERA */
.rcard-header {
  padding: 1.2rem 1.4rem 0.8rem;
  display: flex;
  align-items: flex-start;
  gap: 1.5rem;
}

.rcard-freq-icon {
  font-size: 1.1rem;
  color: var(--cinnamon-soft);
  flex-shrink: 0;
  margin-top: 2px;
}

.rcard-title {
  font-family: var(--font-serif);
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--cinnamon-dark);
}

.rcard-descrip {
  font-size: 0.78rem;
  color: var(--cinnamon-soft);
  margin: 0.2rem 0 0;
}

.rcard-progress-text {
  font-size: 0.68rem;
  color: var(--cinnamon-soft);
  white-space: nowrap;
}

/* RACHA */
.rcard-streak {
  flex-shrink: 0;
  text-align: center;
  min-width: 60px;
}

.rcard-streak-icon {
  font-size: 1.3rem;
  color: var(--state-warn);
  display: block;
}

.rcard-streak-num {
  font-family: var(--font-serif);
  font-size: 1.8rem;
  font-weight: 700;
  color: var(--state-warn);
  line-height: 1;
}

.rcard-streak-label {
  font-size: 0.58rem;
  color: var(--cinnamon-soft);
  text-transform: uppercase;
  letter-spacing: 1px;
}

/* FILA INFERIOR DE BOTONES */
.rcard-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.4rem;
  padding: 0 1.4rem 0.8rem;
}

.rcard-btn {
  width: 44px;
  height: 44px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  border-radius: 6px;
}

/* CUERPO EXPANDIDO */
.rcard-body {
  padding: 1rem 1.4rem 1.2rem;
  border-top: 1px solid #F0EBE3;
  background: var(--bg-card);
}

.items-label {
  font-size: 0.65rem;
  color: var(--cinnamon-soft);
  text-transform: uppercase;
  letter-spacing: 1.5px;
  margin-bottom: 0.5rem;
}

/* ITEMS */
.inner-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 0.85rem;
  border-radius: 8px;
  border: 1px solid var(--vanilla-light);
  background: var(--bg-base);
}

.habit-item { border-left: 3px solid var(--cinnamon-mid); }
.step-item  { border-left: 3px solid var(--vanilla-mid); }

.inner-item.item-done { background: var(--state-ok-bg); border-color: #C8E4CA; }
.inner-item.item-done.habit-item,
.inner-item.item-done.step-item { border-left-color: var(--state-ok); }

.inner-text {
  font-size: 0.82rem;
  color: var(--cinnamon-dark);
  flex-grow: 1;
}

.inner-state-btn {
  width: 24px; height: 24px;
  border-radius: 50%;
  border: 1.5px solid var(--vanilla-mid);
  background: transparent;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all 0.15s;
  font-size: 0.82rem; flex-shrink: 0;
}

.inner-state-btn.state-0 { color: var(--vanilla-mid); }
.inner-state-btn.state-1 { color: var(--state-warn); border-color: var(--state-warn); background: var(--state-warn-bg); }
.inner-state-btn.state-2 { color: var(--state-ok);   border-color: var(--state-ok);   background: var(--state-ok-bg); }

.inner-check {
  width: 20px; height: 20px;
  border-radius: 4px;
  border: 1.5px solid var(--vanilla-mid);
  background: transparent;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all 0.15s;
  font-size: 0.65rem; flex-shrink: 0;
}

.inner-check:hover  { border-color: var(--cinnamon-mid); }
.inner-check.checked { background: var(--state-ok); border-color: var(--state-ok); color: #fff; }

.no-items-text { font-size: 0.78rem; color: var(--cinnamon-soft); margin: 0; }
</style>