<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/userStore'
import { rutaApi } from '@/config.js'

const userStore = useUserStore()
const router    = useRouter()

//arrays para guardar los estados 
const tasks    = ref([])
const habits   = ref([])
const routines = ref([])
const loading  = ref(true)
const error    = ref('')

//constante de la energia del usuario (declarada en el userStore)
const energy = ref(userStore.user.energy || 'medium')

//peticiones asincronas
async function fetchHomeData() {
  loading.value = true
  error.value   = ''
  try {
    // Tareas — según energía pedimos distintos rangos
    let tasksUrl  
    if(energy.value == 'high'){
      tasksUrl = rutaApi + '?entity=tasks&user_id=' + userStore.user.id + '&week=1'
    }else{
      tasksUrl = rutaApi + '?entity=tasks&user_id=' + userStore.user.id + '&today=1'
    }
     
    const [tasksRes, habitsRes, routinesRes] = await Promise.all([
      fetch(tasksUrl),
      fetch(`${rutaApi}?entity=habits&user_id=${userStore.user.id}&today=1`),
      fetch(`${rutaApi}?entity=routines&user_id=${userStore.user.id}&today=1`)
    ])

    tasks.value    = await tasksRes.json()
    habits.value   = await habitsRes.json()
    routines.value = await routinesRes.json()

  } catch (e) {
    error.value = 'Error loading data'
  } finally {
    loading.value = false
  }
}

//funcion asincrona para cambiar el nivel de energia

async function changeEnergy(level) {
  energy.value = level

  // Actualizar en BD
  await fetch(`${rutaApi}?entity=users&id=${userStore.user.id}`, {
    method:  'PUT',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({ energy: level })
  })

  //se actualiza en el store la energia del usuario
  userStore.user.energy = level

  //recargamos la funcion para actualizar la pantalla
  fetchHomeData()
}

// ── MARCAR TAREA COMO DONE ────────────────────────────────────

async function toggleTask(task) {
  const newDone = task.done ? 0 : 1
  const res  = await fetch(`${rutaApi}?entity=tasks&id=${task.id}`, {
    method:  'PATCH',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({ done: newDone })
  })
  const data = await res.json()
  if (data.status === 'success') task.done = !!newDone
}

// ── MARCAR HÁBITO (ciclo 0 → 1 → 2 → 0) ─────────────────────

async function cycleHabit(habit) {
  const next = (parseInt(habit.done_today ?? 0) + 1) % 3
  const res  = await fetch(`${rutaApi}?entity=habits&id=${habit.id}`, {
    method:  'PATCH',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({ done: next })
  })
  const data = await res.json()
  if (data.status === 'success') habit.done_today = next
}

// ── HELPERS ───────────────────────────────────────────────────

function habitStateLabel(val) {
  const v = parseInt(val ?? 0)
  if (v === 2) return '✓ Done'
  if (v === 1) return '~ Tried'
  return '○'
}

function routineStateLabel(val) {
  const v = parseInt(val ?? 0)
  if (v === 2) return '✓ Done'
  if (v === 1) return '~ Tried'
  return '○'
}

function isOverdue(task) {
  return !task.done && new Date(task.expDate) < new Date()
}

function isDueToday(task) {
  if (task.done) return false
  const today   = new Date().toISOString().split('T')[0]
  const expDate = task.expDate?.split('T')[0] ?? task.expDate?.split(' ')[0]
  return expDate === today
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('en-GB')
}

// Saludo según hora del día
const greeting = computed(() => {
  const hour = new Date().getHours()
  if (hour < 12) return 'Good morning'
  if (hour < 18) return 'Good afternoon'
  return 'Good evening'
})

// Tareas filtradas según energía
const filteredTasks = computed(() => {
  if (!tasks.value.length) return []

  if (energy.value === 'low') {
    // Solo urgentes: vencidas o que vencen hoy
    return tasks.value.filter(t => !t.done && (isOverdue(t) || isDueToday(t)))
  }

  // medium y high: todas las que vengan de la API (hoy o semana)
  return tasks.value.filter(t => !t.done)
})

// Contadores para el resumen
const doneTasks    = computed(() => tasks.value.filter(t => t.done).length)
const doneHabits   = computed(() => habits.value.filter(h => parseInt(h.done_today) === 2).length)
const doneRoutines = computed(() => routines.value.filter(r => parseInt(r.done_today) === 2).length)

// ── LIFECYCLE ─────────────────────────────────────────────────

onMounted(() => fetchHomeData())
</script>

<template>
  <div>

    <!-- SALUDO -->
    <div>
      <h1>{{ greeting }}, {{ userStore.user.nickName }} 👋</h1>
      <p>
        {{ doneTasks }} tasks done ·
        {{ doneHabits }}/{{ habits.length }} habits ·
        {{ doneRoutines }}/{{ routines.length }} routines
      </p>
    </div>

    <!-- SELECTOR DE ENERGÍA -->
    <div>
      <span>Energy level:</span>

      <button
        @click="changeEnergy('low')"
        :disabled="energy === 'low'"
      >
        🔋 Low
      </button>

      <button
        @click="changeEnergy('medium')"
        :disabled="energy === 'medium'"
      >
        ⚡ Medium
      </button>

      <button
        @click="changeEnergy('high')"
        :disabled="energy === 'high'"
      >
        🚀 High
      </button>

      <!-- Descripción de qué muestra cada nivel -->
      <p v-if="energy === 'low'">
        Showing only urgent tasks · daily habits
      </p>
      <p v-else-if="energy === 'medium'">
        Showing today's tasks · habits · routines
      </p>
      <p v-else>
        Showing this week's tasks · today's habits · routines
      </p>
    </div>

    <p v-if="error">{{ error }}</p>
    <p v-if="loading">Loading...</p>

    <template v-else>

      <!-- ── TAREAS ── -->
      <section>
        <h2>
          Tasks
          <span v-if="energy === 'high'">— this week</span>
          <span v-else>— today</span>
        </h2>

        <p v-if="filteredTasks.length === 0">
          <span v-if="energy === 'low'">No urgent tasks 🎉</span>
          <span v-else>No pending tasks for today 🎉</span>
        </p>

        <ul v-else>
          <li v-for="task in filteredTasks" :key="task.id">
            <input
              type="checkbox"
              :checked="task.done"
              @change="toggleTask(task)"
            >
            <span>{{ task.title }}</span>
            <span>[{{ task.difficulty }}]</span>
            <span v-if="isOverdue(task)">⚠ Overdue</span>
            <span v-else-if="isDueToday(task)">📅 Today</span>
            <span v-else>{{ formatDate(task.expDate) }}</span>
          </li>
        </ul>

        <button @click="router.push('/tasks')">See all tasks →</button>
      </section>

      <!-- ── HÁBITOS (solo si energía no es low con todos, o low con diarios) ── -->
      <section v-if="habits.length > 0">
        <h2>Habits — today</h2>

        <ul>
          <li v-for="habit in habits" :key="habit.id">
            <button @click="cycleHabit(habit)">
              {{ habitStateLabel(habit.done_today) }}
            </button>
            <span>{{ habit.icon }} {{ habit.title }}</span>
            <span>🔥 {{ habit.streak }}</span>
          </li>
        </ul>

        <button @click="router.push('/habits')">See all habits →</button>
      </section>

      <section v-else-if="!loading">
        <h2>Habits</h2>
        <p>No habits for today</p>
        <button @click="router.push('/habits/new')">+ Create your first habit</button>
      </section>

      <!-- ── RUTINAS (solo si energía no es low) ── -->
      <section v-if="energy !== 'low'">
        <h2>Routines — today</h2>

        <p v-if="routines.length === 0">No routines for today</p>

        <ul v-else>
          <li v-for="routine in routines" :key="routine.id">
            <span>{{ routineStateLabel(routine.done_today) }}</span>
            <span>{{ routine.title }}</span>
            <span v-if="routine.hour">{{ routine.hour }}</span>
            <span>
              {{ routine.done_steps || 0 }}/{{ routine.total_steps }} steps
            </span>
            <span>🔥 {{ routine.streak || 0 }}</span>
            <button @click="router.push('/routines')">Open →</button>
          </li>
        </ul>

        <button @click="router.push('/routines')">See all routines →</button>
      </section>

      <!-- ── BOTÓN ESTADÍSTICAS ── -->
      <section>
        <button @click="router.push('/progress')">
          📊 View Monthly Report
        </button>
      </section>

    </template>
  </div>
</template>