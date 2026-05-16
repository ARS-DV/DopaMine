<script setup>
//imports para Vue, router y API
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/userStore'
import { rutaApi } from '@/config.js'

const userStore = useUserStore()
const router    = useRouter()

// variables reactivas
const tasksList    = ref([])
const habitsList   = ref([])
const routinesList = ref([])
const isLoading    = ref(true)
const errorMessage = ref('')

//nivel de energia
const energyLevel = ref(userStore.user.energy || 'medium')

// funcion cargar datos
async function loadAllData() {
  isLoading.value    = true
  errorMessage.value = ''

  let tasksUrl = rutaApi + "?entity=tasks&user_id=" + userStore.user.id
  if (energyLevel.value == 'high') {
    tasksUrl = tasksUrl + "&week=1"
  } else {
    tasksUrl = tasksUrl + "&today=1"
  }

  fetch(tasksUrl)
    .then(function(res) { return res.json() })
    .then(function(dataTasks) {
      tasksList.value = dataTasks
      return fetch(rutaApi + "?entity=habits&user_id=" + userStore.user.id + "&today=1")
    })
    .then(function(res) { return res.json() })
    .then(function(dataHabits) {
      habitsList.value = dataHabits
      return fetch(rutaApi + "?entity=routines&user_id=" + userStore.user.id + "&today=1")
    })
    .then(function(res) { return res.json() })
    .then(function(dataRoutines) {
      routinesList.value = dataRoutines
      isLoading.value    = false
    })
    .catch(function() {
      errorMessage.value = 'Error loading data'
      isLoading.value    = false
    })
}

//funcion para el cambio de energia
function updateEnergy(newLevel) {
  energyLevel.value = newLevel

  fetch(rutaApi + "?entity=users&id=" + userStore.user.id, {
    method:  'PUT',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({ energy: newLevel })
  })
  .then(function() {
    userStore.user.energy = newLevel
    loadAllData()
  })
}

//funcion para marcar tareas
function checkTask(task) {
  let status = 0
  if (task.done == false || task.done == 0) {
    status = 1
  }

  fetch(rutaApi + "?entity=tasks&id=" + task.id, {
    method:  'PATCH',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({ done: status })
  })
  .then(function(res) { return res.json() })
  .then(function(data) {
    if (data.status === 'success') {
      if (status === 1) {
        task.done = true
      } else {
        task.done = false
      }
    }
  })
}

// actualizar estados de habitos
function updateHabitState(habit) {
  let current = habit.done_today
  if (current == null) { current = 0 }
  let next = (parseInt(current) + 1) % 3

  fetch(rutaApi + "?entity=habits&id=" + habit.id, {
    method:  'PATCH',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({ done: next })
  })
  .then(function(res) { return res.json() })
  .then(function(data) {
    if (data.status === 'success') {
      habit.done_today = next
    }
  })
}

//texto estados habito
function getHabitText(val) {
  let v = parseInt(val)
  if (v == 2) { return 'Done' }
  if (v == 1) { return 'Tried' }
  return 'Pending'
}

//icono bootstrap segun estado habito
function getHabitIcon(val) {
  let v = parseInt(val)
  if (v == 2) { return 'bi-check-circle-fill' }
  if (v == 1) { return 'bi-dash-circle' }
  return 'bi-circle'
}

//saludo personalizado por hora
const welcomeGreeting = computed(function() {
  let hour = new Date().getHours()
  if (hour < 12) { return 'Good morning' }
  if (hour < 18) { return 'Good afternoon' }
  return 'Good evening'
})

//filtro por energia
const homeFilteredTasks = computed(function() {
  return tasksList.value.filter(function(t) {
    return t.done == false
  })
})

//contadores
const tasksDoneCount = computed(function() {
  return tasksList.value.filter(function(t) { return t.done }).length
})

const habitsDoneCount = computed(function() {
  return habitsList.value.filter(function(h) { return parseInt(h.done_today) == 2 }).length
})

onMounted(function() {
  loadAllData()
})
</script>

<template>
  <div class="home-container">

    <!-- SALUDO -->
    <div class="mb-3 fade-up">
      <p class="greeting-sub mb-1" aria-hidden="true">
        {{ new Date().toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long' }) }}
      </p>
      <h1 class="page-title">
        {{ welcomeGreeting }},<br>
        <em>{{ userStore.user.nickName }}</em>
      </h1>
    </div>

    <!-- CONTADORES -->
    <div class="row g-3 mb-4 fade-up delay-1" aria-label="Today's summary">
      <div class="col-6">
        <div class="stat-strip strip-info">
          <div>
            <div class="stat-num">{{ tasksDoneCount }}</div>
            <div class="stat-label">Tasks done</div>
          </div>
          <i class="bi bi-check2-square stat-icon ms-3" aria-hidden="true"></i>
        </div>
      </div>
      <div class="col-6">
        <div class="stat-strip strip-ok">
          <div>
            <div class="stat-num">{{ habitsDoneCount }}/{{ habitsList.length }}</div>
            <div class="stat-label">Habits done</div>
          </div>
          <i class="bi bi-arrow-repeat stat-icon ms-3" aria-hidden="true"></i>
        </div>
      </div>
    </div>

    <!-- SELECTOR DE ENERGÍA -->
    <div class="energy-card mb-4 fade-up delay-2">
      <div class="d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-lightning-charge energy-icon" aria-hidden="true"></i>
        <span class="energy-label">Energy level</span>
      </div>

      <div class="row g-2 mb-3" role="group" aria-label="Select your energy level">
        <div class="col-4">
          <button
            class="energy-btn w-100"
            :class="energyLevel === 'low' ? 'energy-active-low' : ''"
            :aria-pressed="energyLevel === 'low'"
            aria-label="Low energy — shows only urgent tasks"
            @click="updateEnergy('low')"
          >
            <i class="bi bi-battery me-1" aria-hidden="true"></i> Low
          </button>
        </div>
        <div class="col-4">
          <button
            class="energy-btn w-100"
            :class="energyLevel === 'medium' ? 'energy-active-medium' : ''"
            :aria-pressed="energyLevel === 'medium'"
            aria-label="Medium energy — shows today's tasks and habits"
            @click="updateEnergy('medium')"
          >
            <i class="bi bi-battery-half me-1" aria-hidden="true"></i> Medium
          </button>
        </div>
        <div class="col-4">
          <button
            class="energy-btn w-100"
            :class="energyLevel === 'high' ? 'energy-active-high' : ''"
            :aria-pressed="energyLevel === 'high'"
            aria-label="High energy — shows this week's tasks and habits"
            @click="updateEnergy('high')"
          >
            <i class="bi bi-battery-full me-1" aria-hidden="true"></i> High
          </button>
        </div>
      </div>

      <p class="energy-desc mb-0" aria-live="polite">
        <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
        <span v-if="energyLevel === 'low'">Showing urgent tasks and today's habits only</span>
        <span v-else-if="energyLevel === 'medium'">Showing today's tasks, habits and routines</span>
        <span v-else>Showing this week's tasks, habits and routines</span>
      </p>
    </div>

    <!-- ERROR -->
    <div v-if="errorMessage" class="error-text mb-3" role="alert">
      <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>{{ errorMessage }}
    </div>

    <!-- LOADING -->
    <div v-if="isLoading" class="loading-text" aria-live="polite">
      <div class="spinner-border spinner-border-sm me-2" role="status">
        <span class="visually-hidden">Loading your data...</span>
      </div>
      Loading...
    </div>

    <template v-else>

      <!-- TAREAS -->
      <section class="mb-5 fade-up delay-3" aria-labelledby="tasks-heading">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h2 id="tasks-heading" class="section-title mb-0">
            <i class="bi bi-check2-square me-2" aria-hidden="true"></i>Tasks
          </h2>
          <button
            class="btn-dopamine btn-dopamine-ghost"
            aria-label="See all tasks"
            @click="router.push('/tasks')"
          >
            See all <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
          </button>
        </div>

        <div v-if="homeFilteredTasks.length === 0" class="empty-state">
          <i class="bi bi-check-all empty-icon" aria-hidden="true"></i>
          <p class="empty-title">All tasks done!</p>
          <button
            class="btn-dopamine btn-dopamine-primary mt-2"
            aria-label="Create a new task"
            @click="router.push('/tasks/new')"
          >
            <i class="bi bi-plus me-1" aria-hidden="true"></i> New task
          </button>
        </div>

        <div v-else class="d-flex flex-column gap-2" role="list">
          <div
            v-for="task in homeFilteredTasks"
            :key="task.id"
            class="home-card"
            :class="task.difficulty === 'hard' ? 'card-hard' : task.difficulty === 'easy' ? 'card-easy' : 'card-medium'"
            role="listitem"
          >
            <div class="d-flex align-items-center gap-3">
              <button
                class="task-check-btn"
                :class="task.done ? 'task-check-done' : ''"
                :aria-label="(task.done ? 'Uncheck' : 'Mark as done') + ': ' + task.title"
                :aria-pressed="!!task.done"
                @click="checkTask(task)"
              >
                <i v-if="task.done" class="bi bi-check" aria-hidden="true"></i>
              </button>
              <span
                class="task-title-text flex-grow-1"
                :class="task.done ? 'text-decoration-line-through' : ''"
              >
                {{ task.title }}
              </span>
              <span class="bdg" :class="'bdg-' + task.difficulty" aria-label="Difficulty: {{ task.difficulty }}">
                {{ task.difficulty }}
              </span>
            </div>
          </div>
        </div>
      </section>

      <!-- HÁBITOS -->
      <section v-if="habitsList.length > 0" class="mb-5 fade-up delay-4" aria-labelledby="habits-heading">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h2 id="habits-heading" class="section-title mb-0">
            <i class="bi bi-arrow-repeat me-2" aria-hidden="true"></i>Habits
          </h2>
          <button
            class="btn-dopamine btn-dopamine-ghost"
            aria-label="See all habits"
            @click="router.push('/habits')"
          >
            See all <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
          </button>
        </div>

        <div class="d-flex flex-column gap-2" role="list">
          <div
            v-for="habit in habitsList"
            :key="habit.id"
            class="home-card"
            role="listitem"
          >
            <div class="d-flex align-items-center gap-3">
              <button
                class="habit-state-btn"
                :class="'state-' + parseInt(habit.done_today || 0)"
                :aria-label="'Change state of ' + habit.title + ' — currently: ' + getHabitText(habit.done_today)"
                :aria-pressed="parseInt(habit.done_today) > 0"
                @click="updateHabitState(habit)"
              >
                <i class="bi" :class="getHabitIcon(habit.done_today)" aria-hidden="true"></i>
              </button>
              <span class="task-title-text flex-grow-1">
                <span v-if="habit.icon" aria-hidden="true">{{ habit.icon }} </span>{{ habit.title }}
              </span>
              <span
                class="bdg"
                :class="parseInt(habit.done_today) == 2 ? 'bdg-done' : parseInt(habit.done_today) == 1 ? 'bdg-tried' : 'bdg-daily'"
              >
                {{ getHabitText(habit.done_today) }}
              </span>
            </div>
          </div>
        </div>
      </section>

      <section v-else class="mb-5 fade-up delay-4" aria-labelledby="habits-empty-heading">
        <h2 id="habits-empty-heading" class="section-title">
          <i class="bi bi-arrow-repeat me-2" aria-hidden="true"></i>Habits
        </h2>
        <div class="empty-state">
          <i class="bi bi-plus-circle empty-icon" aria-hidden="true"></i>
          <p class="empty-title">No habits yet</p>
          <button
            class="btn-dopamine btn-dopamine-primary mt-2"
            aria-label="Create your first habit"
            @click="router.push('/habits/new')"
          >
            <i class="bi bi-plus me-1" aria-hidden="true"></i> Create your first habit
          </button>
        </div>
      </section>

      <!-- RUTINAS -->
      <section v-if="energyLevel !== 'low'" class="mb-5 fade-up delay-5" aria-labelledby="routines-heading">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h2 id="routines-heading" class="section-title mb-0">
            <i class="bi bi-list-check me-2" aria-hidden="true"></i>Routines
          </h2>
          <button
            class="btn-dopamine btn-dopamine-ghost"
            aria-label="See all routines"
            @click="router.push('/routines')"
          >
            See all <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
          </button>
        </div>

        <div v-if="routinesList.length === 0" class="empty-state">
          <i class="bi bi-calendar-x empty-icon" aria-hidden="true"></i>
          <p class="empty-title">No routines for today</p>
          <button
            class="btn-dopamine btn-dopamine-primary mt-2"
            aria-label="Create your first routine"
            @click="router.push('/routines/new')"
          >
            <i class="bi bi-plus me-1" aria-hidden="true"></i> Create your first routine
          </button>
        </div>

        <div v-else class="d-flex flex-column gap-2" role="list">
          <div
            v-for="routine in routinesList"
            :key="routine.id"
            class="home-card"
            :class="parseInt(routine.done_today) == 2 ? 'card-easy' : parseInt(routine.done_today) == 1 ? 'card-medium' : ''"
            role="listitem"
          >
            <div class="d-flex align-items-center gap-3">
              <i
                class="bi routine-state-icon"
                :class="parseInt(routine.done_today) == 2 ? 'bi-check-circle-fill text-success' : parseInt(routine.done_today) == 1 ? 'bi-dash-circle text-warning' : 'bi-circle'"
                :aria-label="'Routine state: ' + (parseInt(routine.done_today) == 2 ? 'done' : parseInt(routine.done_today) == 1 ? 'tried' : 'pending')"
              ></i>
              <div class="flex-grow-1">
                <div class="task-title-text">{{ routine.title }}</div>
                <div class="progress mt-1" style="height:5px;" role="progressbar" :aria-valuenow="routine.total_steps > 0 ? Math.round((routine.done_steps || 0) / routine.total_steps * 100) : 0" aria-valuemin="0" aria-valuemax="100" :aria-label="(routine.done_steps || 0) + ' of ' + (routine.total_steps || 0) + ' steps done'">
                  <div
                    class="progress-bar"
                    :style="{ width: routine.total_steps > 0 ? ((routine.done_steps || 0) / routine.total_steps * 100) + '%' : '0%', backgroundColor: 'var(--state-ok)' }"
                  ></div>
                </div>
                <small class="progress-text" aria-hidden="true">
                  {{ routine.done_steps || 0 }}/{{ routine.total_steps || 0 }} steps
                </small>
              </div>
              <button
                class="btn-dopamine btn-dopamine-ghost home-routine-btn"
                :aria-label="'Open routine: ' + routine.title"
                @click="router.push('/routines')"
              >
                <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- BOTÓN ESTADÍSTICAS -->
      <section class="fade-up delay-5">
        <button
          class="btn-dopamine btn-dopamine-primary w-100 stats-btn"
          aria-label="View your monthly progress report"
          @click="router.push('/progress')"
        >
          <div class="d-flex align-items-center justify-content-between w-100">
            <div class="d-flex align-items-center gap-3">
              <i class="bi bi-bar-chart-line stats-btn-icon" aria-hidden="true"></i>
              <div class="text-start">
                <div class="stats-btn-title">View Monthly Report</div>
                <div class="stats-btn-sub">
                  {{ new Date().toLocaleString('en-GB', { month: 'long', year: 'numeric' }) }}
                </div>
              </div>
            </div>
            <i class="bi bi-arrow-right" aria-hidden="true"></i>
          </div>
        </button>
      </section>

    </template>
  </div>
</template>

<style scoped>
/* CONTENEDOR ANCHO — igual que HabitsView, TasksView */
.home-container {
  width: 100%;
  padding: 2.5rem 3rem 5rem;
  font-family: 'Atkinson Hyperlegible', sans-serif;
}

@media (max-width: 768px) {
  .home-container { padding: 1.5rem 1rem 5rem; }
}

/* SALUDO */
.greeting-sub {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--cinnamon-soft);
  text-transform: uppercase;
  letter-spacing: 1.5px;
}

/* TÍTULOS DE SECCIÓN */
.section-title {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--cinnamon-dark);
}

/* ENERGÍA */
.energy-card {
  background: var(--bg-card);
  border: 1.5px solid var(--vanilla-mid);
  border-radius: 12px;
  padding: 1.3rem 1.5rem;
  box-shadow: 0 2px 8px rgba(92, 51, 23, 0.07);
}

.energy-icon {
  color: var(--cinnamon-soft);
  font-size: 1.1rem;
}

.energy-label {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.8rem;
  color: var(--cinnamon-soft);
  text-transform: uppercase;
  letter-spacing: 1.5px;
  font-weight: 600;
}

.energy-btn {
  border: 1.5px solid var(--vanilla-mid);
  color: var(--cinnamon-mid);
  background: var(--bg-subtle);
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.85rem;
  font-weight: 600;
  padding: 0.6rem 0.5rem;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.15s;
  min-height: 44px;
}

.energy-btn:hover { background: var(--vanilla-light); color: var(--cinnamon-dark); }

.energy-btn:focus-visible {
  outline: 3px solid var(--cinnamon-mid);
  outline-offset: 3px;
}

.energy-active-low    { background: var(--state-error-bg) !important; border-color: var(--state-error) !important; color: #7A2020 !important; font-weight: 700 !important; }
.energy-active-medium { background: var(--state-warn-bg)  !important; border-color: var(--state-warn)  !important; color: #7A5A00 !important; font-weight: 700 !important; }
.energy-active-high   { background: var(--state-ok-bg)    !important; border-color: var(--state-ok)    !important; color: #3A6E3E !important; font-weight: 700 !important; }

.energy-desc {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.82rem;
  color: var(--cinnamon-soft);
}

/* HOME CARD — para tareas, hábitos y rutinas */
.home-card {
  background: var(--bg-base);
  border: 1.5px solid var(--vanilla-mid);
  border-left: 4px solid var(--vanilla-mid);
  border-radius: 10px;
  padding: 0.85rem 1.1rem;
  box-shadow: 0 1px 6px rgba(92, 51, 23, 0.06);
  transition: box-shadow 0.15s;
}

.home-card:hover { box-shadow: 0 3px 12px rgba(92, 51, 23, 0.1); }

.card-hard   { border-left-color: var(--state-error); }
.card-medium { border-left-color: var(--state-warn); }
.card-easy   { border-left-color: var(--state-ok); }

/* TÍTULO DE TAREA/HÁBITO */
.task-title-text {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--cinnamon-dark);
}

/* CHECKBOX TAREA */
.task-check-btn {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  border: 2px solid var(--vanilla-mid);
  background: transparent;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s;
  font-size: 0.85rem;
  flex-shrink: 0;
}

.task-check-btn:hover { border-color: var(--cinnamon-mid); }
.task-check-btn:focus-visible { outline: 3px solid var(--cinnamon-mid); outline-offset: 3px; }
.task-check-done { background: var(--state-ok); border-color: var(--state-ok); color: #fff; }

/* HÁBITO — botón de estado */
.habit-state-btn {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 1.5px solid var(--vanilla-mid);
  background: transparent;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s;
  font-size: 1.1rem;
  flex-shrink: 0;
}

.habit-state-btn:hover { border-color: var(--cinnamon-mid); }
.habit-state-btn:focus-visible { outline: 3px solid var(--cinnamon-mid); outline-offset: 3px; }
.habit-state-btn.state-0 { color: var(--vanilla-mid); }
.habit-state-btn.state-1 { color: var(--state-warn); border-color: var(--state-warn); background: var(--state-warn-bg); }
.habit-state-btn.state-2 { color: var(--state-ok);   border-color: var(--state-ok);   background: var(--state-ok-bg); }

/* RUTINAS */
.routine-state-icon {
  font-size: 1.3rem;
  flex-shrink: 0;
  color: var(--vanilla-mid);
}

.progress-text {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.72rem;
  color: var(--cinnamon-soft);
}

.home-routine-btn {
  width: 40px;
  height: 40px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

/* BOTÓN ESTADÍSTICAS */
.stats-btn {
  padding: 1.2rem 1.5rem;
  border-radius: 12px;
}

.stats-btn-icon {
  font-size: 1.4rem;
  background: rgba(255, 255, 255, 0.15);
  padding: 0.5rem;
  border-radius: 8px;
}

.stats-btn-title {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.95rem;
  font-weight: 700;
}

.stats-btn-sub {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.75rem;
  opacity: 0.8;
}

/* RESPONSIVE */
@media (max-width: 576px) {
  .energy-btn { font-size: 0.78rem; padding: 0.5rem 0.3rem; }
}
</style>