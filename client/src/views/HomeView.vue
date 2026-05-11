<script setup>
//imports para Vue, router y API
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/userStore'
import { rutaApi } from '@/config.js'

const userStore = useUserStore()
const router    = useRouter()

// variables reactivas
const tasksList = ref([])
const habitsList = ref([])
const routinesList = ref([])
const isLoading = ref(true)
const errorMessage = ref('')

//nivel de energia
const energyLevel = ref(userStore.user.energy || 'medium')

// funcion cargar datos
async function loadAllData() {
  isLoading.value = true
  errorMessage.value = ''

  //se decide la URL de  lastareas segun energia
  let tasksUrl = rutaApi + "?entity=tasks&user_id=" + userStore.user.id
  if (energyLevel.value == 'high') {
    tasksUrl = tasksUrl + "&week=1"
  } else {
    tasksUrl = tasksUrl + "&today=1"
  }

  //se hacen peticiones
  // fetch para tareas
  fetch(tasksUrl)
    .then(res => res.json())
    .then(dataTasks => {
      tasksList.value = dataTasks
      
      // habtios
      return fetch(rutaApi + "?entity=habits&user_id=" + userStore.user.id + "&today=1")
    })
    .then(res => res.json())
    .then(dataHabits => {
      habitsList.value = dataHabits
      
      // rutinas
      return fetch(rutaApi + "?entity=routines&user_id=" + userStore.user.id + "&today=1")
    })
    .then(res => res.json())
    .then(dataRoutines => {
      routinesList.value = dataRoutines
      isLoading.value = false
    })
    .catch(err => {
      errorMessage.value = 'Error loading data'
      isLoading.value = false
    })
}

//funcion para el cambio de energia
async function updateEnergy(newLevel) {
  energyLevel.value = newLevel

  fetch(rutaApi + "?entity=users&id=" + userStore.user.id, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ energy: newLevel })
  })
  .then(() => {
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
    method: 'PATCH',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ done: status })
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'success') {
      if (status === 1) { task.done = true }
      else { task.done = false }
    }
  })
}

// actualizar estados de habitos
async function updateHabitState(habit) {
  let current = habit.done_today
  if (current == null) { current = 0 }
  let next = (parseInt(current) + 1) % 3

  fetch(rutaApi + "?entity=habits&id=" + habit.id, {
    method: 'PATCH',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ done: next })
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'success') {
      habit.done_today = next
    }
  })
}

//texto estados
function getHabitText(val) {
  let v = parseInt(val)
  if (v == 2) return 'Done'
  if (v == 1) return 'Tried'
  return 'Without starting'
}

//saludo personalizado por hora
const welcomeGreeting = computed(() => {
  let hour = new Date().getHours()
  if (hour < 12) return 'Good morning'
  if (hour < 18) return 'Good afternoon'
  return 'Good evening'
})

//filtro por energia
const homeFilteredTasks = computed(() => {
  if (energyLevel.value == 'low') {
    return tasksList.value.filter(t => t.done == false)
  }
  return tasksList.value.filter(t => t.done == false)
})

//contadores 
const tasksDoneCount = computed(() => tasksList.value.filter(t => t.done).length)
const habitsDoneCount = computed(() => habitsList.value.filter(h => parseInt(h.done_today) == 2).length)

onMounted(() => {
  loadAllData()
})
</script>

<template>
  <div>
    <div>
      <h1>{{ welcomeGreeting }}, {{ userStore.user.nickName }} </h1>
      <p>
        {{ tasksDoneCount }} tasks done ·
        {{ habitsDoneCount }}/{{ habitsList.length }} habits
      </p>
    </div>

    <div>
      <span>Energy level:</span>
      <button @click="updateEnergy('low')"> Low</button>
      <button @click="updateEnergy('medium')"> Medium</button>
      <button @click="updateEnergy('high')"> High</button>
    </div>

    <p v-if="errorMessage">{{ errorMessage }}</p>
    <p v-if="isLoading">Loading...</p>

    <template v-else>
      <section>
        <h2>Tasks</h2>
        <ul>
          <li v-for="task in homeFilteredTasks" :key="task.id">
            <input type="checkbox" :checked="task.done" @change="checkTask(task)">
            <span>{{ task.title }}</span>
          </li>
        </ul>
        <button @click="router.push('/tasks')">See all tasks</button>
      </section>

      <section v-if="habitsList.length > 0">
        <h2>Habits</h2>
        <ul>
          <li v-for="habit in habitsList" :key="habit.id">
            <button @click="updateHabitState(habit)">
              {{ getHabitText(habit.done_today) }}
            </button>
            <span>{{ habit.icon }} {{ habit.title }}</span>
          </li>
        </ul>
      </section>

      <section v-if="energyLevel !== 'low'">
        <h2>Routines</h2>
        <ul>
          <li v-for="routine in routinesList" :key="routine.id">
            <span>{{ routine.title }}</span>
            <span> ({{ routine.done_steps || 0 }}/{{ routine.total_steps || 0 }} steps)</span>
            <button @click="router.push('/routines')">Open</button>
          </li>
        </ul>
      </section>
    </template>
  </div>
</template>