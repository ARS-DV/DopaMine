<script setup>
// imports para Vue, router y API
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/userStore'
import { rutaApi } from '@/config.js'

const userStore = useUserStore()
const router    = useRouter()

//variables reactivas
const routines = ref([])
const loading  = ref(true)
const error    = ref('')
const filter   = ref('all')

//constante reactica para saber si la rutina está expandida
const expandedRoutines = ref({})

async function toggleExpand(id) {
  //se cambia el valor booleano
  if (expandedRoutines.value[id] == true) {
    expandedRoutines.value[id] = false
  } else {
    expandedRoutines.value[id] = true
  }
}

//funcion principal
async function fetchRoutines() {
  loading.value = true
  error.value   = ''
  
  let url = rutaApi + "?entity=routines&user_id=" + userStore.user.id
  
  fetch(url)
    .then(response => response.json())
    .then(data => {
      routines.value = data
      loading.value = false
    })
    .catch(err => {
      error.value = 'Error loading routines'
      loading.value = false
    })
}

//funcion para cambar el estado del habito dentro de la rutina
function cycleHabitInRoutine(routine, habit) {
  let currentState = habit.done_today
  if (currentState == null) { currentState = 0 }
  
  let nextState = (parseInt(currentState) + 1) % 3

  fetch(rutaApi + "?entity=habits&id=" + habit.id, {
    method: 'PATCH',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ done: nextState })
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'success') {
      habit.done_today = nextState
      //despues de marcar habito, se cambia el progreso de la rutina
      updateRoutineProgress(routine)
    }
  })
}

//funcion para marcar las checklists
async function toggleRoutineStep(routine, step) {
  let newStatus = 1
  if (step.done == true || step.done == 1) {
    newStatus = 0
  }

  fetch(rutaApi + "?entity=routine_checklist&id=" + step.id, {
    method: 'PATCH',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ done: newStatus })
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'success') {
      if (newStatus == 1) { step.done = true } 
      else { step.done = false }
      
      //actualizacion rutina
      updateRoutineProgress(routine)
    }
  })
}

//calcular porcentaje y actualizar ddbb
async function updateRoutineProgress(routine) {
  let habitList = routine.habits || []
  let stepList = routine.checklist || []

  let totalItems = habitList.length + stepList.length
  if (totalItems == 0) return

  //contamos los habitos completados
  let completedHabits = 0
  for (let i = 0; i < habitList.length; i++) {
    if (parseInt(habitList[i].done_today) == 2) {
      completedHabits++
    }
  }

  //contador de checklist
  let completedSteps = 0
  for (let j = 0; j < stepList.length; j++) {
    if (stepList[j].done == true) {
      completedSteps++
    }
  }

  let totalDone = completedHabits + completedSteps
  let percentage = (totalDone / totalItems) * 100

  //marcar que si está al 100% marcado como done y si +50 como tried
  let finalState = 0
  if (percentage === 100) {
    finalState = 2
  } else if (percentage >= 50) {
    finalState = 1
  }

  //fetch para guardar nuevos estados en el servidor
  fetch(rutaApi + "?entity=routines&id=" + routine.id, {
    method: 'PATCH',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      done: finalState,
      done_steps: totalDone,
      total_steps: totalItems
    })
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'success') {
      routine.done_today = finalState
      routine.done_steps = totalDone
      routine.total_steps = totalItems
      if (data.current_streak !== undefined) {
        routine.streak = data.current_streak
      }
    }
  })
}

// funcion para borrar rutina
async function deleteRoutine(id) {
  let check = confirm('Delete this routine?')
  if (check == false) return

  fetch(rutaApi + "?entity=routines&id=" + id, { method: 'DELETE' })
    .then(res => res.json())
    .then(data => {
      if (data.status === 'success') {
        routines.value = routines.value.filter(r => r.id !== id)
      } else {
        error.value = 'Error deleting routine'
      }
    })
}

//etiquetas para los estados
function getStatusLabel(val) {
  let v = parseInt(val)
  if (v == 2) return 'Done'
  if (v == 1) return 'Tried'
  return '○ Pending'
}

function getHabitStatus(val) {
  let v = parseInt(val)
  if (v == 2) return 'Done'
  if (v == 1) return 'Tried'
  return '○'
}

//funcion para calcular el porcentaje de avance
function calculatePercentage(routine) {
  let habitsCount = routine.habits ? routine.habits.length : 0
  let stepsCount = routine.checklist ? routine.checklist.length : 0
  let total = habitsCount + stepsCount
  
  if (total == 0) return 0
  
  let doneH = 0
  if (routine.habits) {
    doneH = routine.habits.filter(h => parseInt(h.done_today) == 2).length
  }
  
  let doneS = 0
  if (routine.checklist) {
    doneS = routine.checklist.filter(s => s.done == true).length
  }
  
  let result = ((doneH + doneS) / total) * 100
  return Math.round(result)
}

// filtros
const filteredRoutines = computed(() => {
  if (filter.value == 'daily') {
    return routines.value.filter(r => r.frecuency == 'daily')
  } else if (filter.value === 'weekly') {
    return routines.value.filter(r => r.frecuency == 'weekly')
  } else if (filter.value === 'monthly') {
    return routines.value.filter(r => r.frecuency == 'monthly')
  } else if (filter.value === 'done') {
    return routines.value.filter(r => parseInt(r.done_today) == 2)
  } else if (filter.value === 'tried') {
    return routines.value.filter(r => parseInt(r.done_today) == 1)
  } else {
    return routines.value
  }
})

onMounted(() => {
  fetchRoutines()
})
</script>

<template>
  <div>
    <h1>Routines</h1>

    <p v-if="error">{{ error }}</p>
    <p v-if="loading">Loading...</p>

    <template v-else>
      <button @click="router.push('/routines/new')">+ New Routine</button>

      <div>
        <button @click="filter = 'all'">All ({{ routines.length }})</button>
        <button @click="filter = 'daily'">Daily</button>
        <button @click="filter = 'weekly'">Weekly</button>
        <button @click="filter = 'monthly'">Monthly</button>
        <button @click="filter = 'done'">Done</button>
        <button @click="filter = 'tried'">Tried</button>
      </div>

      <p v-if="filteredRoutines.length === 0">No routines found</p>

      <ul v-else>
        <li v-for="routine in filteredRoutines" :key="routine.id">
          <div>
            <button @click="toggleExpand(routine.id)">
              {{ expandedRoutines[routine.id] ? '▲' : '▼' }}
            </button>

            <span>{{ routine.title }}</span>
            <span>[{{ routine.frecuency }}]</span>
            <span v-if="routine.hour">{{ routine.hour }}</span>

            <span>{{ getStatusLabel(routine.done_today) }}</span>
            <span>{{ calculatePercentage(routine) }}%</span>

            <span>Streak: {{ routine.streak || 0 }}</span>

            <button @click="deleteRoutine(routine.id)">Delete</button>
          </div>

          <div v-if="expandedRoutines[routine.id]">
            <div v-if="routine.habits && routine.habits.length > 0">
              <strong>Habits</strong>
              <ul>
                <li v-for="habit in routine.habits" :key="habit.id">
                  <button @click="cycleHabitInRoutine(routine, habit)">
                    {{ getHabitStatus(habit.done_today) }}
                  </button>
                  <span>{{ habit.icon }} {{ habit.title }}</span>
                </li>
              </ul>
            </div>

            <div v-if="routine.checklist && routine.checklist.length > 0">
              <strong>Steps</strong>
              <ul>
                <li v-for="step in routine.checklist" :key="step.id">
                  <input
                    type="checkbox"
                    :checked="step.done"
                    @change="toggleRoutineStep(routine, step)"
                  >
                  <span>{{ step.title }}</span>
                </li>
              </ul>
            </div>
          </div>
        </li>
      </ul>
    </template>
  </div>
</template>