<script setup>
// imports para Vue, router y API
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/userStore'
import { rutaApi } from '@/config.js'

const userStore = useUserStore()
const router = useRouter()

const error = ref('')

//variables reactivas
const titleInput = ref('')
const descripInput = ref('')
const difficultyInput = ref('medium')
const startDateInput = ref('')
const startTimeInput = ref('')
const expDateInput = ref('')
const expTimeInput = ref('')

//variables reactivas para las subtareas
const checklistItems = ref([])
const newItemTitle = ref('')

//funcion para añadir pasos
async function addItem() {
  if (newItemTitle.value.trim() == "") {
    return
  }
  //se añade el objeto al array
  let item = { title: newItemTitle.value.trim() }
  checklistItems.value.push(item)
  newItemTitle.value = ''
}

//quitar un paso de la lista
async function removeItem(index) {
  checklistItems.value.splice(index, 1)
}

//funcion principal
async function createNewTask() {
  error.value = ''

  //validaciones
  if (titleInput.value.trim() == "") {
    error.value = 'Title is required'
    return
  }

  if (expDateInput.value == "") {
    error.value = 'Due date is required'
    return
  }

  //se juntan fecha y hora como está puesto en la API
  let fullStartDate = null
  if (startDateInput.value !== "") {
    let timeStart = startTimeInput.value
    if (timeStart == "") {
      timeStart = "00:00:00"
    }
    fullStartDate = startDateInput.value + " " + timeStart
  }

  let timeExp = expTimeInput.value
  if (timeExp == "") {
    timeExp = "23:59:00"
  }
  let fullExpDate = expDateInput.value + " " + timeExp

  //objetos con datos para el backend
  let taskData = {
    user_id: userStore.user.id,
    title: titleInput.value,
    descrip: descripInput.value || null,
    difficulty: difficultyInput.value,
    startDate: fullStartDate,
    expDate: fullExpDate,
  }

  //fetch para guardar tareas con metodo POST
  fetch(rutaApi + "?entity=tasks", {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(taskData)
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'success') {
      //si se guarda bien, ahora guardamos sus checklist
      let taskId = data.id
      
      for (let i = 0; i < checklistItems.value.length; i++) {
        let item = checklistItems.value[i]
        
        fetch(rutaApi + "?entity=task_checklist", {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            task_id: taskId,
            title: item.title
          })
        })
      }
      router.push('/tasks')
    } else {
      error.value = 'Error creating task'
    }
  })
  .catch(err => {
    console.error(err)
    error.value = 'Connection error'
  })
}
</script>

<template>
  <div class="newtask-wrapper">
    <div class="newtask-container">

      <!-- PAN DE MIGA -->
      <nav class="breadcrumb-dopamine breadcrumb-visible mb-4 fade-up">
        <RouterLink to="/"><i class="bi bi-house me-1"></i>Home</RouterLink>
        <span class="separator"><i class="bi bi-chevron-right"></i></span>
        <RouterLink to="/tasks">Tasks</RouterLink>
        <span class="separator"><i class="bi bi-chevron-right"></i></span>
        <span class="current">New Task</span>
      </nav>

      <!-- CABECERA -->
      <div class="mb-4 fade-up delay-1">
        <h1 class="page-title">
          <em>add a new</em>
          Task
        </h1>
      </div>

      <!-- ERROR -->
      <div v-if="error" class="error-text mb-4">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>{{ error }}</strong>
      </div>

      <!-- FORMULARIO -->
      <form @submit.prevent="createNewTask" class="d-flex flex-column gap-4 fade-up delay-2">

        <!-- TÍTULO -->
        <div class="form-section">
          <label class="form-label-accessible">
            <i class="bi bi-pencil me-2"></i>Title <span class="required-star">*</span>
          </label>
          <input
            v-model="titleInput"
            type="text"
            class="form-control dopamine-input input-lg"
            placeholder="What do you need to do?"
          >
        </div>

        <!-- DESCRIPCIÓN -->
        <div class="form-section">
          <label class="form-label-accessible">
            <i class="bi bi-text-left me-2"></i>Description
          </label>
          <input
            v-model="descripInput"
            type="text"
            class="form-control dopamine-input input-lg"
            placeholder="Add more details..."
          >
        </div>

        <!-- DIFICULTAD -->
        <div class="form-section">
          <label class="form-label-accessible mb-3">
            <i class="bi bi-bar-chart me-2"></i>Difficulty <span class="required-star">*</span>
          </label>
          <div class="row g-2">
            <div class="col-4">
              <div
                class="diff-option"
                :class="difficultyInput === 'easy' ? 'sel-easy' : ''"
                @click="difficultyInput = 'easy'"
              >
                <i class="bi bi-circle d-block mb-1 diff-icon"></i>
                Easy
              </div>
            </div>
            <div class="col-4">
              <div
                class="diff-option"
                :class="difficultyInput === 'medium' ? 'sel-medium' : ''"
                @click="difficultyInput = 'medium'"
              >
                <i class="bi bi-dash-circle d-block mb-1 diff-icon"></i>
                Medium
              </div>
            </div>
            <div class="col-4">
              <div
                class="diff-option"
                :class="difficultyInput === 'hard' ? 'sel-hard' : ''"
                @click="difficultyInput = 'hard'"
              >
                <i class="bi bi-fire d-block mb-1 diff-icon"></i>
                Hard
              </div>
            </div>
          </div>
          <!-- Radios ocultos para mantener la lógica -->
          <div class="d-none">
            <input type="radio" v-model="difficultyInput" value="easy">
            <input type="radio" v-model="difficultyInput" value="medium">
            <input type="radio" v-model="difficultyInput" value="hard">
          </div>
        </div>

        <!-- FECHAS -->
        <div class="row g-4">
          <div class="col-12 col-md-6">
            <div class="form-section h-100">
              <label class="form-label-accessible mb-3">
                <i class="bi bi-calendar-plus me-2"></i>Start date
              </label>
              <div class="row g-2">
                <div class="col-7">
                  <input v-model="startDateInput" type="date" class="form-control dopamine-input input-date">
                </div>
                <div class="col-5">
                  <input v-model="startTimeInput" type="time" class="form-control dopamine-input input-date">
                </div>
              </div>
            </div>
          </div>
          <div class="col-12 col-md-6">
            <div class="form-section h-100">
              <label class="form-label-accessible mb-3">
                <i class="bi bi-calendar-x me-2"></i>Due date <span class="required-star">*</span>
              </label>
              <div class="row g-2">
                <div class="col-7">
                  <input v-model="expDateInput" type="date" class="form-control dopamine-input input-date">
                </div>
                <div class="col-5">
                  <input v-model="expTimeInput" type="time" class="form-control dopamine-input input-date">
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- CHECKLIST -->
        <div class="form-section">
          <label class="form-label-accessible mb-3">
            <i class="bi bi-list-check me-2"></i>Checklist
          </label>

          <div class="d-flex gap-2 mb-3">
            <input
              v-model="newItemTitle"
              type="text"
              class="form-control dopamine-input flex-grow-1 input-lg"
              placeholder="Add a step..."
              @keydown.enter.prevent="addItem"
            >
            <button
              type="button"
              class="btn-dopamine btn-dopamine-ghost checklist-add-btn"
              @click="addItem"
            >
              <i class="bi bi-plus me-1"></i> Add
            </button>
          </div>

          <div v-if="checklistItems.length > 0" class="d-flex flex-column gap-2">
            <div
              v-for="(item, index) in checklistItems"
              :key="index"
              class="checklist-item"
            >
              <i class="bi bi-grip-vertical checklist-drag-icon"></i>
              <span class="flex-grow-1 checklist-item-text">{{ item.title }}</span>
              <button
                type="button"
                class="btn-dopamine btn-dopamine-danger checklist-remove-btn"
                @click="removeItem(index)"
              >
                <i class="bi bi-x"></i>
              </button>
            </div>
          </div>

          <p v-else class="checklist-empty-text">
            <i class="bi bi-info-circle me-1"></i>
            No steps added yet. Break the task into smaller pieces if it helps.
          </p>
        </div>

        <!-- BOTONES -->
        <div class="d-flex gap-3 flex-wrap pb-2">
          <button type="submit" class="btn-dopamine btn-dopamine-primary form-action-btn">
            <i class="bi bi-check2 me-2"></i> Create Task
          </button>
          <button
            type="button"
            class="btn-dopamine btn-dopamine-cancel form-action-btn"
            @click="router.push('/tasks')"
          >
            <i class="bi bi-x me-2"></i> Cancel
          </button>
        </div>

      </form>
    </div>
  </div>
</template>

<style scoped>
.newtask-wrapper {
  min-height: 100vh;
  background-color: var(--bg-subtle);
  padding: 2.5rem 1.5rem 5rem;
  font-family: 'Atkinson Hyperlegible', sans-serif;
}

.newtask-container {
  max-width: 1000px;
  margin: 0 auto;
}

@media (max-width: 768px) {
  .newtask-wrapper { padding: 1.5rem 1rem 4rem; }
}

/* SECCIONES DEL FORM */
.form-section {
  background: var(--bg-card);
  border: 1.5px solid var(--vanilla-mid);
  border-radius: 12px;
  padding: 1.2rem 1.3rem;
}

/* BREADCRUMB MÁS VISIBLE */
.breadcrumb-visible {
  font-size: 1rem !important;
  font-weight: 600 !important;
  background: var(--bg-card);
  border: 1.5px solid var(--vanilla-mid);
  border-radius: 10px;
  padding: 0.7rem 1rem !important;
  
}

.breadcrumb-visible a {
  color: var(--cinnamon-mid) !important;
  font-weight: 700 !important;
  font-size: 1rem !important;
  
}

.breadcrumb-visible .current {
  color: var(--cinnamon-dark) !important;
  font-weight: 700 !important;
  font-size: 1rem !important;
  
}

.breadcrumb-visible .separator {
  font-size: 0.9rem;
  color: var(--vanilla-mid) !important;
}

/* LABELS */
.form-label-accessible {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 1rem;
  font-weight: 700;
  color: var(--cinnamon-dark);
  display: flex;
  align-items: center;
  margin-bottom: 0.5rem;
}

.required-star {
  color: var(--state-error);
  font-weight: 700;
  margin-left: 0.2rem;
}

/* INPUT GRANDE */
.input-lg {
  font-size: 1rem !important;
  padding: 0.7rem 0.9rem !important;
  min-height: 48px !important;
}

/* INPUTS DE FECHA MÁS GRANDES */
.input-date {
  font-size: 0.95rem !important;
  padding: 0.65rem 0.7rem !important;
  min-height: 48px !important;
}

/* DIFICULTAD */
.diff-option {
  padding: 0.9rem 0.5rem;
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
}

.diff-icon { font-size: 1.4rem; }

/* CHECKLIST */
.checklist-add-btn {
  min-height: 44px;
  white-space: nowrap;
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-weight: 700;
}

.checklist-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.65rem 0.9rem;
  background: var(--bg-base);
  border: 1px solid var(--vanilla-light);
  border-left: 3px solid var(--vanilla-mid);
  border-radius: 8px;
}

.checklist-drag-icon {
  color: var(--vanilla-mid);
  font-size: 1rem;
}

.checklist-item-text {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.92rem;
  font-weight: 500;
  color: var(--cinnamon-dark);
}

.checklist-remove-btn {
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

.checklist-empty-text {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.85rem;
  color: var(--cinnamon-soft);
  margin: 0.2rem 0 0;
}

/* BOTONES ACCIÓN */
.form-action-btn {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 1rem;
  font-weight: 700;
  min-height: 48px;
  padding: 0.7rem 1.5rem;
}
</style>