<script setup>
// imports para Vue, router y API
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/userStore'
import { rutaApi } from '@/config.js'

const userStore = useUserStore()
const router    = useRouter()

//variables reactivas
const routineTitle = ref('')
const routineDescrip = ref('')
const routineHour = ref('')
const routineFrecuency = ref('daily')
const routineDayOfMonth = ref(null)
const routineDays = ref([])

const errorMessage = ref('')
const weekDaysList = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday']

//variables para elegir habitos ya creados
const allUserHabits = ref([])
const chosenHabitIds = ref([]) 

//variables para nuevos pasos
const routineSteps = ref([])
const stepInputText = ref('')

//pedir los habitos del usuario para mostrarlo en el checklist
async function fetchUserHabits() {
  fetch(rutaApi + "?entity=habits&user_id=" + userStore.user.id)
    .then(res => res.json())
    .then(data => {
      allUserHabits.value = data
    })
    .catch(err => console.log("No habits found"))
}

//funciones para manejar los arrays de seleccion
function toggleDaySelection(day) {
  let index = routineDays.value.indexOf(day)
  if (index == -1) {
    routineDays.value.push(day)
  } else {
    routineDays.value.splice(index, 1)
  }
}

function toggleHabitSelection(id) {
  let index = chosenHabitIds.value.indexOf(id)
  if (index == -1) {
    chosenHabitIds.value.push(id)
  } else {
    chosenHabitIds.value.splice(index, 1)
  }
}

//funcione para añadir nuevos pasos
async function addNewStep() {
  if (stepInputText.value.trim() == "") return
  routineSteps.value.push({ title: stepInputText.value.trim() })
  stepInputText.value = ''
}

//funcion para borrar pasos
function deleteStep(index) {
  routineSteps.value.splice(index, 1)
}

//funcion para guardar la rutina
function saveRoutine() {
  errorMessage.value = ''

  //validaciones basicas para rutinas
  if (routineTitle.value == '') {
    errorMessage.value = 'Title is required'
    return
  }
  if (routineFrecuency.value == 'weekly' && routineDays.value.length == 0) {
    errorMessage.value = 'Select at least one day'
    return
  }
  if (chosenHabitIds.value.length == 0 && routineSteps.value.length == 0) {
    errorMessage.value = 'Add at least one habit or step'
    return
  }

  //recorremos un array y guardamos los pasos
  let finalSteps = []
  for (let i = 0; i < routineSteps.value.length; i++) {
    finalSteps.push(routineSteps.value[i].title)
  }

  //variable para guardar objeto para el backend
  let routineData = {
    user_id: userStore.user.id,
    title: routineTitle.value,
    descrip: routineDescrip.value,
    hour: routineHour.value,
    color: '#6B8FA3',
    frecuency: routineFrecuency.value,
    dayOfMonth: routineFrecuency.value == 'monthly' ? routineDayOfMonth.value : null,
    days: routineFrecuency.value == 'weekly' ? routineDays.value : [],
    habit_ids: chosenHabitIds.value,
    steps: finalSteps
  }
//fetch post para guardar la nueva rutina
  fetch(rutaApi + "?entity=routines", {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(routineData)
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'success') {
      router.push('/routines')
    } else {
      errorMessage.value = 'Error creating routine'
    }
  })
}

onMounted(() => {
  fetchUserHabits()
})
</script>

<template>
  <div>
    <nav>
      <RouterLink to="/">Home</RouterLink> > 
      <RouterLink to="/routines">Routines</RouterLink> > 
      <span>New Routine</span>
    </nav>

    <h1>New Routine</h1>

    <p v-if="errorMessage" style="color: red;">{{ errorMessage }}</p>

    <form @submit.prevent="saveRoutine">
      <div>
        <label>Title *</label><br>
        <input v-model="routineTitle" type="text">
      </div>

      <br>

      <div>
        <label>Description</label><br>
        <input v-model="routineDescrip" type="text">
      </div>

      <br>

      <div>
        <label>Time</label><br>
        <input v-model="routineHour" type="time">
      </div>

      <br>

      <div>
        <label>Frequency *</label><br>
        <select v-model="routineFrecuency">
          <option value="daily">Daily</option>
          <option value="weekly">Weekly</option>
          <option value="monthly">Monthly</option>
        </select>
      </div>

      <br>

      <div v-if="routineFrecuency == 'weekly'">
        <label>Days:</label><br>
        <div v-for="day in weekDaysList" :key="day">
          <input type="checkbox" @change="toggleDaySelection(day)"> {{ day }}
        </div>
      </div>

      <div v-if="routineFrecuency == 'monthly'">
        <label>Day of month (1-31):</label><br>
        <input v-model="routineDayOfMonth" type="number">
      </div>

      <br>

      <div>
        <label>Add habits:</label><br>
        <div v-for="habit in allUserHabits" :key="habit.id">
          <input type="checkbox" @change="toggleHabitSelection(habit.id)">
          {{ habit.icon }} {{ habit.title }}
        </div>
      </div>

      <br>

      <div>
        <label>Add steps:</label><br>
        <input v-model="stepInputText" type="text">
        <button type="button" @click="addNewStep">Add</button>
        <ul>
          <li v-for="(s, index) in routineSteps" :key="index">
            {{ s.title }} <button type="button" @click="deleteStep(index)">x</button>
          </li>
        </ul>
      </div>

      <br>

      <button type="submit">Create Routine</button>
      <button type="button" @click="router.push('/routines')">Cancel</button>
    </form>
  </div>
</template>