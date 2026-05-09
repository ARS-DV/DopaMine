<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/userStore'
import { rutaApi } from '@/config.js'

const userStore  = useUserStore()
const router     = useRouter()

const error      = ref('')
const dias       = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday']

// Hábitos existentes del usuario para poder añadirlos a la rutina
const userHabits    = ref([])
const selectedHabits = ref([]) // ids de hábitos seleccionados

// Pasos del checklist propio de la rutina
const steps    = ref([])
const newStep  = ref('')

const form = ref({
  title:      '',
  descrip:    '',
  hour:       '',
  color:      '#6B8FA3',
  frecuency:  'daily',
  dayOfMonth: null,
  days:       []
})

// ── CARGAR HÁBITOS DEL USUARIO ────────────────────────────────

async function fetchUserHabits() {
  try {
    const res  = await fetch(`${rutaApi}?entity=habits&user_id=${userStore.user.id}`)
    const data = await res.json()
    userHabits.value = data
  } catch (e) {
    // si no hay hábitos no es un error crítico
  }
}

// ── HELPERS ───────────────────────────────────────────────────

function toggleDay(day) {
  const idx = form.value.days.indexOf(day)
  if (idx === -1) form.value.days.push(day)
  else            form.value.days.splice(idx, 1)
}

function toggleHabit(id) {
  const idx = selectedHabits.value.indexOf(id)
  if (idx === -1) selectedHabits.value.push(id)
  else            selectedHabits.value.splice(idx, 1)
}

function addStep() {
  if (!newStep.value.trim()) return
  steps.value.push({ title: newStep.value.trim() })
  newStep.value = ''
}

function removeStep(index) {
  steps.value.splice(index, 1)
}

// ── CREAR RUTINA ──────────────────────────────────────────────

async function createRoutine() {
  error.value = ''

  if (!form.value.title.trim()) {
    error.value = 'Title is required'
    return
  }

  if (form.value.frecuency === 'weekly' && form.value.days.length === 0) {
    error.value = 'Select at least one day'
    return
  }

  if (form.value.frecuency === 'monthly' && !form.value.dayOfMonth) {
    error.value = 'Day of month is required'
    return
  }

  if (selectedHabits.value.length === 0 && steps.value.length === 0) {
    error.value = 'Add at least one habit or step'
    return
  }

  const body = {
    user_id:    userStore.user.id,
    title:      form.value.title,
    descrip:    form.value.descrip || null,
    hour:       form.value.hour    || null,
    color:      form.value.color,
    frecuency:  form.value.frecuency,
    dayOfMonth: form.value.frecuency === 'monthly' ? form.value.dayOfMonth : null,
    days:       form.value.frecuency === 'weekly'  ? form.value.days       : [],
    habit_ids:  selectedHabits.value,
    steps:      steps.value.map(s => s.title)
  }

  const res  = await fetch(`${rutaApi}?entity=routines`, {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify(body)
  })
  const data = await res.json()

  if (data.status === 'success') {
    router.push('/routines')
  } else {
    error.value = 'Error creating routine'
  }
}

// ── LIFECYCLE ─────────────────────────────────────────────────

onMounted(() => fetchUserHabits())
</script>

<template>
  <div>

    <!-- PAN DE MIGA -->
    <nav>
      <RouterLink to="/">Home</RouterLink>
      <span> > </span>
      <RouterLink to="/routines">Routines</RouterLink>
      <span> > </span>
      <span>New Routine</span>
    </nav>

    <h1>New Routine</h1>

    <p v-if="error">{{ error }}</p>

    <form @submit.prevent="createRoutine">

      <!-- TÍTULO -->
      <div>
        <label>Title *</label>
        <input v-model="form.title" type="text" placeholder="Routine name">
      </div>

      <!-- DESCRIPCIÓN -->
      <div>
        <label>Description</label>
        <input v-model="form.descrip" type="text" placeholder="Optional description">
      </div>

      <!-- hour -->
      <div>
        <label>Time (optional)</label>
        <input v-model="form.hour" type="time">
      </div>

      <!-- FRECUENCIA -->
      <div>
        <label>Frequency *</label>
        <select v-model="form.frecuency">
          <option value="daily">Daily</option>
          <option value="weekly">Weekly</option>
          <option value="monthly">Monthly</option>
        </select>
      </div>

      <!-- Días específicos si es semanal -->
      <div v-if="form.frecuency === 'weekly'">
        <label>Days *</label>
        <div>
          <label v-for="day in dias" :key="day">
            <input
              type="checkbox"
              :value="day"
              :checked="form.days.includes(day)"
              @change="toggleDay(day)"
            >
            {{ day }}
          </label>
        </div>
      </div>

      <!-- Día del mes si es mensual -->
      <div v-if="form.frecuency === 'monthly'">
        <label>Day of month (1-31) *</label>
        <input v-model.number="form.dayOfMonth" type="number" min="1" max="31">
      </div>

      <!-- AÑADIR HÁBITOS EXISTENTES -->
      <div>
        <label>Add existing habits (optional)</label>

        <p v-if="userHabits.length === 0">
          You have no habits yet.
          <RouterLink to="/habits/new">Create one first</RouterLink>
        </p>

        <div v-else>
          <label v-for="habit in userHabits" :key="habit.id">
            <input
              type="checkbox"
              :value="habit.id"
              :checked="selectedHabits.includes(habit.id)"
              @change="toggleHabit(habit.id)"
            >
            {{ habit.icon }} {{ habit.title }}
            <span>[{{ habit.frecuency }}]</span>
          </label>
        </div>
      </div>

      <!-- AÑADIR PASOS PROPIOS DE LA RUTINA -->
      <div>
        <label>Add steps (optional)</label>

        <div>
          <input
            v-model="newStep"
            type="text"
            placeholder="Step description..."
            @keydown.enter.prevent="addStep"
          >
          <button type="button" @click="addStep">Add</button>
        </div>

        <ul v-if="steps.length > 0">
          <li v-for="(step, index) in steps" :key="index">
            {{ step.title }}
            <button type="button" @click="removeStep(index)">✕</button>
          </li>
        </ul>
      </div>

      <!-- RESUMEN -->
      <div v-if="selectedHabits.length > 0 || steps.length > 0">
        <p>
          This routine will have
          {{ selectedHabits.length }} habit(s) and
          {{ steps.length }} step(s).
          Total: {{ selectedHabits.length + steps.length }} items.
        </p>
        <p>
          It will be marked as <strong>Done</strong> when 100% is completed,
          <strong>Tried</strong> when at least 50% is completed.
        </p>
      </div>

      <!-- ACCIONES -->
      <button type="submit">Create Routine</button>
      <button type="button" @click="router.push('/routines')">Cancel</button>

    </form>

  </div>
</template>
