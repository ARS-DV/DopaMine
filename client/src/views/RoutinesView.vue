<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/userStore'
import { rutaApi } from '@/config.js'

const userStore = useUserStore()
const router    = useRouter()

// ── ESTADO ───────────────────────────────────────────────────
const routines = ref([])
const loading  = ref(true)
const error    = ref('')
const filter   = ref('all')

// Controla qué rutinas están expandidas
const expanded = ref({})

function toggleExpand(id) {
  expanded.value[id] = !expanded.value[id]
}

// ── API CALLS ─────────────────────────────────────────────────

async function fetchRoutines() {
  loading.value = true
  error.value   = ''
  try {
    const res  = await fetch(`${rutaApi}?entity=routines&user_id=${userStore.user.id}`)
    const data = await res.json()
    routines.value = data
  } catch (e) {
    error.value = 'Error loading routines'
  } finally {
    loading.value = false
  }
}

// Marcar hábito dentro de una rutina (cicla 0 → 1 → 2 → 0)
async function cycleHabit(routine, habit) {
  const next = (parseInt(habit.done_today ?? 0) + 1) % 3

  const res  = await fetch(`${rutaApi}?entity=habits&id=${habit.id}`, {
    method:  'PATCH',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({ done: next })
  })
  const data = await res.json()

  if (data.status === 'success') {
    habit.done_today = next
    await updateRoutineState(routine)
  }
}

// Marcar paso del checklist de la rutina (toggle simple)
async function toggleStep(routine, step) {
  const newDone = step.done ? 0 : 1

  const res  = await fetch(`${rutaApi}?entity=routine_checklist&id=${step.id}`, {
    method:  'PATCH',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({ done: newDone })
  })
  const data = await res.json()

  if (data.status === 'success') {
    step.done = !!newDone
    await updateRoutineState(routine)
  }
}

// Recalcula el estado de la rutina y envía PATCH
async function updateRoutineState(routine) {
  const habits    = routine.habits    || []
  const checklist = routine.checklist || []

  // Total de pasos = hábitos + checklist
  const totalSteps = habits.length + checklist.length

  if (totalSteps === 0) return

  // Pasos completados:
  // hábito done_today = 2 cuenta como completo
  // checklist done = true cuenta como completo
  const doneHabits = habits.filter(h => parseInt(h.done_today) === 2).length
  const doneSteps  = checklist.filter(s => s.done).length
  const totalDone  = doneHabits + doneSteps

  const pct = (totalDone / totalSteps) * 100

  // Determinar estado según porcentaje
  let state = 0
  if (pct === 100)     state = 2 // done
  else if (pct >= 50)  state = 1 // tried

  // Actualizar routine_record
  const res = await fetch(`${rutaApi}?entity=routines&id=${routine.id}`, {
    method:  'PATCH',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({
      done:       state,
      done_steps: totalDone,
      total_steps: totalSteps
    })
  })
  const data = await res.json()

  if (data.status === 'success') {
    routine.done_today   = state
    routine.done_steps   = totalDone
    routine.total_steps  = totalSteps
    if (data.current_streak !== undefined) {
      routine.streak = data.current_streak
    }
  }
}

async function deleteRoutine(id) {
  if (!confirm('Delete this routine?')) return

  const res  = await fetch(`${rutaApi}?entity=routines&id=${id}`, {
    method: 'DELETE'
  })
  const data = await res.json()

  if (data.status === 'success') {
    routines.value = routines.value.filter(r => r.id !== id)
  } else {
    error.value = 'Error deleting routine'
  }
}

// ── HELPERS ───────────────────────────────────────────────────

// Etiqueta del estado de la rutina
function stateLabel(val) {
  const v = parseInt(val ?? 0)
  if (v === 2) return '✓ Done'
  if (v === 1) return '~ Tried'
  return '○ Pending'
}

// Etiqueta del estado del hábito dentro de la rutina
function habitStateLabel(val) {
  const v = parseInt(val ?? 0)
  if (v === 2) return '✓'
  if (v === 1) return '~'
  return '○'
}

// Calcula el porcentaje de completitud de una rutina
function completionPct(routine) {
  const total = (routine.habits?.length || 0) + (routine.checklist?.length || 0)
  if (total === 0) return 0
  const done = (routine.habits?.filter(h => parseInt(h.done_today) === 2).length || 0)
             + (routine.checklist?.filter(s => s.done).length || 0)
  return Math.round((done / total) * 100)
}

// ── FILTROS ───────────────────────────────────────────────────

const filteredRoutines = computed(() => {
  switch (filter.value) {
    case 'daily':   return routines.value.filter(r => r.frecuency === 'daily')
    case 'weekly':  return routines.value.filter(r => r.frecuency === 'weekly')
    case 'monthly': return routines.value.filter(r => r.frecuency === 'monthly')
    case 'done':    return routines.value.filter(r => parseInt(r.done_today) === 2)
    case 'tried':   return routines.value.filter(r => parseInt(r.done_today) === 1)
    default:        return routines.value
  }
})

// ── LIFECYCLE ─────────────────────────────────────────────────

onMounted(() => fetchRoutines())
</script>

<template>
  <div>
    <h1>Routines</h1>

    <p v-if="error">{{ error }}</p>
    <p v-if="loading">Loading...</p>

    <template v-else>

      <!-- BOTÓN CREAR -->
      <button @click="router.push('/routines/new')">+ New Routine</button>

      <!-- FILTROS -->
      <div>
        <button @click="filter = 'all'">All ({{ routines.length }})</button>
        <button @click="filter = 'daily'">Daily</button>
        <button @click="filter = 'weekly'">Weekly</button>
        <button @click="filter = 'monthly'">Monthly</button>
        <button @click="filter = 'done'">Done</button>
        <button @click="filter = 'tried'">Tried</button>
      </div>

      <!-- LISTA VACÍA -->
      <p v-if="filteredRoutines.length === 0">No routines found</p>

      <!-- LISTA DE RUTINAS -->
      <ul v-else>
        <li v-for="routine in filteredRoutines" :key="routine.id">

          <!-- CABECERA DE LA RUTINA -->
          <div>
            <button @click="toggleExpand(routine.id)">
              {{ expanded[routine.id] ? '▲' : '▼' }}
            </button>

            <span>{{ routine.title }}</span>
            <span>[{{ routine.frecuency }}]</span>
            <span v-if="routine.hour">{{ routine.hour }}</span>

            <!-- ESTADO -->
            <span>{{ stateLabel(routine.done_today) }}</span>

            <!-- PROGRESO -->
            <span>{{ completionPct(routine) }}%</span>
            <span v-if="routine.total_steps > 0">
              ({{ routine.done_steps || 0 }}/{{ routine.total_steps }} steps)
            </span>

            <!-- RACHAS -->
            <span>🔥 Streak: {{ routine.streak || 0 }}</span>
            <span>⭐ Best: {{ routine.best_streak || 0 }}</span>

            <!-- DÍAS si es semanal -->
            <span v-if="routine.days && routine.days.length">
              {{ routine.days.join(', ') }}
            </span>

            <button @click="deleteRoutine(routine.id)">Delete</button>
          </div>

          <!-- CONTENIDO EXPANDIDO -->
          <div v-if="expanded[routine.id]">

            <!-- HÁBITOS DE LA RUTINA -->
            <div v-if="routine.habits && routine.habits.length > 0">
              <strong>Habits</strong>
              <ul>
                <li v-for="habit in routine.habits" :key="habit.id">
                  <button @click="cycleHabit(routine, habit)">
                    {{ habitStateLabel(habit.done_today) }}
                  </button>
                  <span>{{ habit.icon }} {{ habit.title }}</span>
                  <span v-if="parseInt(habit.done_today) === 1">(tried)</span>
                  <span v-if="parseInt(habit.done_today) === 2">(done)</span>
                </li>
              </ul>
            </div>

            <!-- PASOS / CHECKLIST DE LA RUTINA -->
            <div v-if="routine.checklist && routine.checklist.length > 0">
              <strong>Steps</strong>
              <ul>
                <li v-for="step in routine.checklist" :key="step.id">
                  <input
                    type="checkbox"
                    :checked="step.done"
                    @change="toggleStep(routine, step)"
                  >
                  <span :style="step.done ? 'text-decoration:line-through' : ''">
                    {{ step.title }}
                  </span>
                </li>
              </ul>
            </div>

            <!-- Sin contenido -->
            <p v-if="(!routine.habits || routine.habits.length === 0)
                   && (!routine.checklist || routine.checklist.length === 0)">
              No habits or steps added yet
            </p>

          </div>

        </li>
      </ul>

    </template>
  </div>
</template>
