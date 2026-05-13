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
  <div class="newroutine-wrapper">
    <div class="newroutine-container">

      <!-- PAN DE MIGA -->
      <nav class="breadcrumb-dopamine breadcrumb-visible mb-4 fade-up">
        <RouterLink to="/"><i class="bi bi-house me-1"></i>Home</RouterLink>
        <span class="separator"><i class="bi bi-chevron-right"></i></span>
        <RouterLink to="/routines">Routines</RouterLink>
        <span class="separator"><i class="bi bi-chevron-right"></i></span>
        <span class="current">New Routine</span>
      </nav>

      <!-- CABECERA -->
      <div class="mb-4 fade-up delay-1">
        <h1 class="page-title">
          <em>build a new</em>
          Routine
        </h1>
      </div>

      <!-- ERROR -->
      <div v-if="errorMessage" class="error-text mb-4">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>{{ errorMessage }}</strong>
      </div>

      <!-- FORMULARIO -->
      <form @submit.prevent="saveRoutine" class="d-flex flex-column gap-4 fade-up delay-2">

        <!-- TÍTULO -->
        <div class="form-section">
          <label class="form-label-accessible">
            <i class="bi bi-pencil me-2"></i>Title <span class="required-star">*</span>
          </label>
          <input
            v-model="routineTitle"
            type="text"
            class="form-control dopamine-input input-lg"
            placeholder="e.g. Morning Routine, Wind down..."
          >
        </div>

        <!-- DESCRIPCIÓN -->
        <div class="form-section">
          <label class="form-label-accessible">
            <i class="bi bi-text-left me-2"></i>Description
          </label>
          <input
            v-model="routineDescrip"
            type="text"
            class="form-control dopamine-input input-lg"
            placeholder="What is this routine for?"
          >
        </div>

        <!-- HORA -->
        <div class="form-section">
          <label class="form-label-accessible mb-2">
            <i class="bi bi-clock me-2"></i>Time
          </label>
          <p class="field-hint mb-2">When does this routine happen?</p>
          <input
            v-model="routineHour"
            type="time"
            class="form-control dopamine-input input-date"
            style="max-width: 160px"
          >
        </div>

        <!-- FRECUENCIA -->
        <div class="form-section">
          <label class="form-label-accessible mb-3">
            <i class="bi bi-arrow-repeat me-2"></i>Frequency <span class="required-star">*</span>
          </label>
          <div class="row g-2">
            <div class="col-4">
              <div
                class="freq-btn"
                :class="routineFrecuency === 'daily' ? 'freq-btn-daily' : ''"
                @click="routineFrecuency = 'daily'"
              >
                <i class="bi bi-sun d-block mb-1 freq-btn-icon"></i>
                Daily
              </div>
            </div>
            <div class="col-4">
              <div
                class="freq-btn"
                :class="routineFrecuency === 'weekly' ? 'freq-btn-weekly' : ''"
                @click="routineFrecuency = 'weekly'"
              >
                <i class="bi bi-calendar-week d-block mb-1 freq-btn-icon"></i>
                Weekly
              </div>
            </div>
            <div class="col-4">
              <div
                class="freq-btn"
                :class="routineFrecuency === 'monthly' ? 'freq-btn-monthly' : ''"
                @click="routineFrecuency = 'monthly'"
              >
                <i class="bi bi-calendar-month d-block mb-1 freq-btn-icon"></i>
                Monthly
              </div>
            </div>
          </div>
          <select v-model="routineFrecuency" class="d-none">
            <option value="daily">Daily</option>
            <option value="weekly">Weekly</option>
            <option value="monthly">Monthly</option>
          </select>
        </div>

        <!-- DÍAS si es semanal -->
        <div v-if="routineFrecuency == 'weekly'" class="form-section fade-up">
          <label class="form-label-accessible mb-3">
            <i class="bi bi-calendar-week me-2 text-weekly"></i>
            <span class="text-weekly">Days of the week</span>
            <span class="required-star ms-1">*</span>
          </label>
          <div class="days-grid">
            <div
              v-for="day in weekDaysList"
              :key="day"
              class="day-btn"
              :class="routineDays.includes(day) ? 'day-btn-selected' : ''"
              @click="toggleDaySelection(day)"
            >
              <input type="checkbox" @change="toggleDaySelection(day)" class="d-none">
              {{ day.slice(0, 3).toUpperCase() }}
            </div>
          </div>
          <p class="days-hint">
            <i class="bi bi-info-circle me-1"></i>
            {{ routineDays.length }} day(s) selected
          </p>
        </div>

        <!-- DÍA DEL MES si es mensual -->
        <div v-if="routineFrecuency == 'monthly'" class="form-section fade-up">
          <label class="form-label-accessible mb-2">
            <i class="bi bi-calendar-event me-2 text-monthly"></i>
            <span class="text-monthly">Day of the month</span>
            <span class="required-star ms-1">*</span>
          </label>
          <p class="field-hint mb-2">Between 1 and 31</p>
          <input
            v-model="routineDayOfMonth"
            type="number"
            min="1"
            max="31"
            class="form-control dopamine-input input-date"
            placeholder="e.g. 15"
            style="max-width: 140px"
          >
        </div>

        <!-- HÁBITOS EXISTENTES -->
        <div class="form-section">
          <label class="form-label-accessible mb-3">
            <i class="bi bi-arrow-repeat me-2"></i>Add existing habits
          </label>

          <div v-if="allUserHabits.length == 0" class="habits-empty">
            <i class="bi bi-info-circle me-2"></i>
            You have no habits yet.
            <RouterLink to="/habits/new" class="ms-1">Create one first</RouterLink>
          </div>

          <div v-else class="d-flex flex-column gap-2">
            <div
              v-for="habit in allUserHabits"
              :key="habit.id"
              class="habit-select-item"
              :class="chosenHabitIds.includes(habit.id) ? 'habit-selected' : ''"
              @click="toggleHabitSelection(habit.id)"
            >
              <input
                type="checkbox"
                :checked="chosenHabitIds.includes(habit.id)"
                @change="toggleHabitSelection(habit.id)"
                class="d-none"
              >
              <div class="habit-select-check" :class="chosenHabitIds.includes(habit.id) ? 'check-active' : ''">
                <i v-if="chosenHabitIds.includes(habit.id)" class="bi bi-check"></i>
              </div>
              <span class="habit-select-icon" v-if="habit.icon">{{ habit.icon }}</span>
              <span class="habit-select-title flex-grow-1">{{ habit.title }}</span>
              <span class="bdg" :class="habit.frecuency === 'daily' ? 'bdg-daily' : habit.frecuency === 'weekly' ? 'bdg-weekly' : 'bdg-monthly'">
                {{ habit.frecuency }}
              </span>
            </div>
          </div>

          <p v-if="allUserHabits.length > 0" class="days-hint mt-2">
            <i class="bi bi-info-circle me-1"></i>
            {{ chosenHabitIds.length }} habit(s) selected
          </p>
        </div>

        <!-- PASOS -->
        <div class="form-section">
          <label class="form-label-accessible mb-3">
            <i class="bi bi-list-check me-2"></i>Add steps
          </label>

          <div class="d-flex gap-2 mb-3">
            <input
              v-model="stepInputText"
              type="text"
              class="form-control dopamine-input flex-grow-1"
              placeholder="e.g. Shower, Breakfast, Review the day..."
              @keydown.enter.prevent="addNewStep"
            >
            <button
              type="button"
              class="btn-dopamine btn-dopamine-ghost step-add-btn"
              @click="addNewStep"
            >
              <i class="bi bi-plus me-1"></i> Add
            </button>
          </div>

          <div v-if="routineSteps.length > 0" class="d-flex flex-column gap-2">
            <div v-for="(s, index) in routineSteps" :key="index" class="step-item">
              <i class="bi bi-grip-vertical step-drag-icon"></i>
              <span class="flex-grow-1 step-item-text">{{ s.title }}</span>
              <button
                type="button"
                class="btn-dopamine btn-dopamine-danger step-remove-btn"
                @click="deleteStep(index)"
              >
                <i class="bi bi-x"></i>
              </button>
            </div>
          </div>

          <p v-else class="days-hint">
            <i class="bi bi-info-circle me-1"></i>
            No steps added yet. Add actions specific to this routine.
          </p>
        </div>

        <!-- RESUMEN -->
        <div
          v-if="chosenHabitIds.length > 0 || routineSteps.length > 0"
          class="routine-summary"
        >
          <i class="bi bi-check2-all me-2"></i>
          This routine will have
          <strong>{{ chosenHabitIds.length }}</strong> habit(s) and
          <strong>{{ routineSteps.length }}</strong> step(s) —
          <strong>{{ chosenHabitIds.length + routineSteps.length }}</strong> items total.
        </div>

        <!-- BOTONES -->
        <div class="d-flex gap-3 flex-wrap pb-2">
          <button type="submit" class="btn-dopamine btn-dopamine-primary form-action-btn">
            <i class="bi bi-check2 me-2"></i> Create Routine
          </button>
          <button
            type="button"
            class="btn-dopamine btn-dopamine-cancel form-action-btn"
            @click="router.push('/routines')"
          >
            <i class="bi bi-x me-2"></i> Cancel
          </button>
        </div>

      </form>
    </div>
  </div>
</template>

<style scoped>
.newroutine-wrapper {
  min-height: 100vh;
  background-color: var(--bg-subtle);
  padding: 2.5rem 1.5rem 5rem;
  font-family: 'Atkinson Hyperlegible', sans-serif;
}

.newroutine-container {
  max-width: 720px;
  margin: 0 auto;
}

@media (max-width: 768px) {
  .newroutine-wrapper { padding: 1.5rem 1rem 4rem; }
}

.form-section {
  background: var(--bg-card);
  border: 1.5px solid var(--vanilla-mid);
  border-radius: 12px;
  padding: 1.2rem 1.3rem;
}

.breadcrumb-visible {
  font-size: 1rem !important;
  font-weight: 600 !important;
  background: var(--bg-card);
  border: 1.5px solid var(--vanilla-mid);
  border-radius: 10px;
  padding: 0.7rem 1rem !important;
}

.breadcrumb-visible a        { color: var(--cinnamon-mid) !important; font-weight: 700 !important; font-size: 1rem !important; }
.breadcrumb-visible .current  { color: var(--cinnamon-dark) !important; font-weight: 700 !important; }
.breadcrumb-visible .separator { color: var(--vanilla-mid) !important; }

.form-label-accessible {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 1rem;
  font-weight: 700;
  color: var(--cinnamon-dark);
  display: flex;
  align-items: center;
  margin-bottom: 0.5rem;
}

.required-star { color: var(--state-error); font-weight: 700; }
.field-hint { font-size: 0.82rem; color: var(--cinnamon-soft); margin: 0; }

.input-lg {
  font-size: 1rem !important;
  padding: 0.7rem 0.9rem !important;
  min-height: 48px !important;
}

.input-date {
  font-size: 0.95rem !important;
  padding: 0.65rem 0.7rem !important;
  min-height: 48px !important;
}

.freq-btn {
  text-align: center;
  padding: 0.9rem 0.5rem;
  cursor: pointer;
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.9rem;
  font-weight: 600;
  border-radius: 8px;
  border: 1.5px solid var(--vanilla-mid);
  background: var(--bg-subtle);
  color: var(--cinnamon-mid);
  transition: all 0.15s;
}

.freq-btn:hover   { background: var(--vanilla-light); }
.freq-btn-icon    { font-size: 1.3rem; }
.freq-btn-daily   { background: var(--bg-base);       border-color: var(--cinnamon-mid); color: var(--cinnamon-dark); }
.freq-btn-weekly  { background: var(--state-info-bg);  border-color: var(--btn-info);     color: #2A5068; }
.freq-btn-monthly { background: var(--vanilla-light);  border-color: var(--vanilla-deep); color: var(--cinnamon-dark); }

.text-weekly  { color: #2A5068; }
.text-monthly { color: var(--vanilla-deep); }

.days-grid { display: flex; flex-wrap: wrap; gap: 0.5rem; }

.day-btn {
  min-width: 52px;
  height: 52px;
  border-radius: 10px;
  border: 2px solid var(--vanilla-light);
  background: var(--bg-subtle);
  color: var(--cinnamon-mid);
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.82rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s;
  padding: 0 0.6rem;
  user-select: none;
}

.day-btn:hover      { border-color: var(--btn-info); background: var(--state-info-bg); color: #2A5068; }
.day-btn-selected   { background: var(--btn-info) !important; border-color: var(--btn-info) !important; color: #fff !important; }
.days-hint          { font-size: 0.82rem; color: var(--cinnamon-soft); margin: 0.6rem 0 0; }

.habits-empty {
  font-size: 0.9rem;
  color: var(--cinnamon-soft);
}

.habits-empty a {
  color: var(--cinnamon-dark);
  font-weight: 700;
  text-decoration: underline;
}

.habit-select-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.7rem 0.9rem;
  border-radius: 10px;
  border: 1.5px solid var(--vanilla-light);
  background: var(--bg-base);
  cursor: pointer;
  transition: all 0.15s;
  user-select: none;
}

.habit-select-item:hover { border-color: var(--cinnamon-mid); background: var(--vanilla-light); }
.habit-selected          { border-color: var(--state-ok) !important; background: var(--state-ok-bg) !important; }

.habit-select-check {
  width: 24px;
  height: 24px;
  border-radius: 6px;
  border: 2px solid var(--vanilla-mid);
  background: transparent;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  flex-shrink: 0;
  transition: all 0.15s;
}

.check-active    { background: var(--state-ok); border-color: var(--state-ok); color: #fff; }
.habit-select-icon { font-size: 1.1rem; flex-shrink: 0; }

.habit-select-title {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--cinnamon-dark);
}

.step-add-btn {
  min-height: 44px;
  white-space: nowrap;
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-weight: 700;
}

.step-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.65rem 0.9rem;
  background: var(--bg-base);
  border: 1px solid var(--vanilla-light);
  border-left: 3px solid var(--cinnamon-mid);
  border-radius: 8px;
}

.step-drag-icon { color: var(--vanilla-mid); font-size: 1rem; }

.step-item-text {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.92rem;
  font-weight: 500;
  color: var(--cinnamon-dark);
}

.step-remove-btn {
  width: 32px;
  height: 32px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  flex-shrink: 0;
  border-radius: 6px;
}

.routine-summary {
  background: var(--state-info-bg);
  border: 1px solid var(--btn-info);
  border-radius: 10px;
  padding: 0.85rem 1.1rem;
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.9rem;
  color: #2A5068;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.3rem;
}

.form-action-btn {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 1rem;
  font-weight: 700;
  min-height: 48px;
  padding: 0.7rem 1.5rem;
}
</style>