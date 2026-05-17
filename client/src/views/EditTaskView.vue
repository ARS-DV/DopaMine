<script setup>
// imports para Vue, router y API
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useUserStore } from '@/stores/userStore'
import { rutaApi } from '@/config.js'

const userStore = useUserStore()
const router    = useRouter()
const route     = useRoute()

const taskId = route.params.id

const error   = ref('')
const loading = ref(true)

//variables reactivas del formulario
const titleInput      = ref('')
const iconInput       = ref('')
const descripInput    = ref('')
const difficultyInput = ref('medium')
const startDateInput  = ref('')
const startTimeInput  = ref('')
const expDateInput    = ref('')
const expTimeInput    = ref('')

// hasta 3 links
const urlInputs = ref(['', '', ''])

//checklist existente y nuevos pasos
const checklistItems = ref([])
const newItemTitle   = ref('')

//funcion para cargar los datos de la tarea al montar la vista
function loadTaskData() {
  loading.value = true

  fetch(rutaApi + "?entity=tasks&id=" + taskId)
    .then(res => res.json())
    .then(data => {
      if (!data || data.status === 'error') {
        error.value   = 'Task not found'
        loading.value = false
        return
      }

      // rellenar los campos del formulario
      titleInput.value      = data.title      || ''
      iconInput.value       = data.icon       || ''
      descripInput.value    = data.descrip    || ''
      difficultyInput.value = data.difficulty || 'medium'

      // cargar links
      urlInputs.value[0] = data.url  || ''
      urlInputs.value[1] = data.url2 || ''
      urlInputs.value[2] = data.url3 || ''

      // separar fecha y hora del startDate
      if (data.startDate) {
        let parts = data.startDate.split(' ')
        startDateInput.value = parts[0] || ''
        startTimeInput.value = parts[1] ? parts[1].slice(0, 5) : ''
      }

      // separar fecha y hora del expDate
      if (data.expDate) {
        let parts = data.expDate.split(' ')
        expDateInput.value = parts[0] || ''
        expTimeInput.value = parts[1] ? parts[1].slice(0, 5) : ''
      }

      // cargar checklist
      checklistItems.value = data.checklist || []
      loading.value = false
    })
    .catch(err => {
      error.value   = 'Error loading task'
      loading.value = false
    })
}

//funcion para añadir pasos al checklist
function addItem() {
  if (newItemTitle.value.trim() == "") { return }

  // guardar el nuevo item en la BD
  fetch(rutaApi + "?entity=task_checklist", {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({
      task_id: taskId,
      title:   newItemTitle.value.trim()
    })
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'success') {
      checklistItems.value.push({ id: data.id, title: newItemTitle.value.trim(), done: false })
      newItemTitle.value = ''
    }
  })
}

//funcion para borrar un paso del checklist
function removeItem(item, index) {
  fetch(rutaApi + "?entity=task_checklist&id=" + item.id, { method: 'DELETE' })
    .then(res => res.json())
    .then(data => {
      if (data.status === 'success') {
        checklistItems.value.splice(index, 1)
      }
    })
}

//funcion principal para guardar los cambios
function updateTask() {
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

  // combinar fecha y hora
  let fullStartDate = null
  if (startDateInput.value !== "") {
    let timeStart = startTimeInput.value !== "" ? startTimeInput.value : "00:00:00"
    fullStartDate = startDateInput.value + " " + timeStart
  }

  let timeExp     = expTimeInput.value !== "" ? expTimeInput.value : "23:59:00"
  let fullExpDate = expDateInput.value + " " + timeExp

  let taskData = {
    title:      titleInput.value,
    icon:       iconInput.value || null,
    descrip:    descripInput.value || null,
    difficulty: difficultyInput.value,
    startDate:  fullStartDate,
    expDate:    fullExpDate,
    url:        urlInputs.value[0] || null,
    url2:       urlInputs.value[1] || null,
    url3:       urlInputs.value[2] || null,
  }

  fetch(rutaApi + "?entity=tasks&id=" + taskId, {
    method:  'PUT',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify(taskData)
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'success') {
      router.push('/tasks')
    } else {
      error.value = 'Error updating task'
    }
  })
  .catch(err => {
    error.value = 'Connection error'
  })
}

onMounted(() => {
  loadTaskData()
})
</script>

<template>
  <div class="edittask-wrapper">
    <div class="edittask-container">

      <!-- PAN DE MIGA -->
      <nav class="breadcrumb-dopamine breadcrumb-visible mb-4 fade-up" aria-label="Breadcrumb navigation">
        <RouterLink to="/home"><i class="bi bi-house me-1" aria-hidden="true"></i>Home</RouterLink>
        <span class="separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
        <RouterLink to="/tasks">Tasks</RouterLink>
        <span class="separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
        <span class="current" aria-current="page">Edit Task</span>
      </nav>

      <!-- CABECERA -->
      <div class="mb-4 fade-up delay-1">
        <h1 class="page-title">
          <em>editing a</em>
          Task
        </h1>
      </div>

      <!-- LOADING -->
      <div v-if="loading" class="loading-text" aria-live="polite">
        <div class="spinner-border spinner-border-sm me-2" role="status">
          <span class="visually-hidden">Loading task data...</span>
        </div>
        Loading task...
      </div>

      <!-- ERROR -->
      <div v-if="error" class="error-text mb-4" role="alert">
        <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>
        <strong>{{ error }}</strong>
      </div>

      <!-- FORMULARIO -->
      <form v-if="!loading" @submit.prevent="updateTask" class="d-flex flex-column gap-4 fade-up delay-2" novalidate>

        <!-- TÍTULO -->
        <div class="form-section">
          <label for="edit-title" class="form-label-accessible">
            <i class="bi bi-pencil me-2" aria-hidden="true"></i>Title <span class="required-star" aria-hidden="true">*</span>
          </label>
          <input
            id="edit-title"
            v-model="titleInput"
            type="text"
            class="form-control dopamine-input input-lg"
            placeholder="Task name"
            maxlength="200"
            aria-required="true"
          >
          <p class="char-hint text-end mt-1" aria-live="polite">{{ titleInput.length }}/200</p>
        </div>

        <!-- ICONO -->
        <div class="form-section">
          <label for="edit-icon" class="form-label-accessible">
            <i class="bi bi-emoji-smile me-2" aria-hidden="true"></i>Icon
          </label>
          <input
            id="edit-icon"
            v-model="iconInput"
            type="text"
            class="form-control dopamine-input input-lg icon-input"
            placeholder="e.g. 📌"
            maxlength="4"
            aria-describedby="edit-icon-hint"
          >
          <p id="edit-icon-hint" class="field-hint mt-2">
            <i class="bi bi-keyboard me-1" aria-hidden="true"></i>
            <strong>Windows:</strong> press <kbd>Win + .</kbd> &nbsp;·&nbsp;
            <strong>Mac:</strong> press <kbd>Cmd + Ctrl + Space</kbd>
          </p>
        </div>

        <!-- DESCRIPCIÓN -->
        <div class="form-section">
          <label for="edit-descrip" class="form-label-accessible">
            <i class="bi bi-text-left me-2" aria-hidden="true"></i>Description
          </label>
          <textarea
            id="edit-descrip"
            v-model="descripInput"
            class="form-control dopamine-input textarea-field"
            placeholder="Add more details..."
            rows="3"
            maxlength="1000"
          ></textarea>
        </div>

        <!-- DIFICULTAD -->
        <div class="form-section">
          <label class="form-label-accessible mb-3">
            <i class="bi bi-bar-chart me-2" aria-hidden="true"></i>Difficulty <span class="required-star" aria-hidden="true">*</span>
          </label>
          <div class="row g-2" role="radiogroup" aria-label="Select difficulty">
            <div class="col-4">
              <div class="diff-option" :class="difficultyInput === 'easy' ? 'sel-easy' : ''"
                role="radio" :aria-checked="difficultyInput === 'easy'" tabindex="0"
                @click="difficultyInput = 'easy'" @keydown.enter="difficultyInput = 'easy'" @keydown.space.prevent="difficultyInput = 'easy'">
                <i class="bi bi-circle d-block mb-1" style="font-size:1.2rem" aria-hidden="true"></i>Easy
              </div>
            </div>
            <div class="col-4">
              <div class="diff-option" :class="difficultyInput === 'medium' ? 'sel-medium' : ''"
                role="radio" :aria-checked="difficultyInput === 'medium'" tabindex="0"
                @click="difficultyInput = 'medium'" @keydown.enter="difficultyInput = 'medium'" @keydown.space.prevent="difficultyInput = 'medium'">
                <i class="bi bi-dash-circle d-block mb-1" style="font-size:1.2rem" aria-hidden="true"></i>Medium
              </div>
            </div>
            <div class="col-4">
              <div class="diff-option" :class="difficultyInput === 'hard' ? 'sel-hard' : ''"
                role="radio" :aria-checked="difficultyInput === 'hard'" tabindex="0"
                @click="difficultyInput = 'hard'" @keydown.enter="difficultyInput = 'hard'" @keydown.space.prevent="difficultyInput = 'hard'">
                <i class="bi bi-fire d-block mb-1" style="font-size:1.2rem" aria-hidden="true"></i>Hard
              </div>
            </div>
          </div>
        </div>

        <!-- FECHAS -->
        <div class="row g-4">
          <div class="col-12 col-md-6">
            <div class="form-section h-100">
              <label class="form-label-accessible mb-3">
                <i class="bi bi-calendar-plus me-2" aria-hidden="true"></i>Start date
              </label>
              <div class="row g-2">
                <div class="col-7">
                  <label for="edit-start-date" class="visually-hidden">Start date</label>
                  <input id="edit-start-date" v-model="startDateInput" type="date" class="form-control dopamine-input input-date">
                </div>
                <div class="col-5">
                  <label for="edit-start-time" class="visually-hidden">Start time</label>
                  <input id="edit-start-time" v-model="startTimeInput" type="time" class="form-control dopamine-input input-date">
                </div>
              </div>
            </div>
          </div>
          <div class="col-12 col-md-6">
            <div class="form-section h-100">
              <label class="form-label-accessible mb-3">
                <i class="bi bi-calendar-x me-2" aria-hidden="true"></i>Due date <span class="required-star" aria-hidden="true">*</span>
              </label>
              <div class="row g-2">
                <div class="col-7">
                  <label for="edit-exp-date" class="visually-hidden">Due date</label>
                  <input id="edit-exp-date" v-model="expDateInput" type="date" class="form-control dopamine-input input-date" aria-required="true">
                </div>
                <div class="col-5">
                  <label for="edit-exp-time" class="visually-hidden">Due time</label>
                  <input id="edit-exp-time" v-model="expTimeInput" type="time" class="form-control dopamine-input input-date">
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- LINKS (máx. 3) -->
        <div class="form-section">
          <label class="form-label-accessible mb-3">
            <i class="bi bi-link-45deg me-2" aria-hidden="true"></i>Links
            <span class="links-max-hint">max. 3</span>
          </label>
          <div class="d-flex flex-column gap-2">
            <div v-for="(url, i) in urlInputs" :key="i" class="d-flex align-items-center gap-2">
              <span class="link-num" aria-hidden="true">{{ i + 1 }}</span>
              <label :for="'edit-url-' + i" class="visually-hidden">Link {{ i + 1 }}</label>
              <input
                :id="'edit-url-' + i"
                v-model="urlInputs[i]"
                type="url"
                class="form-control dopamine-input flex-grow-1"
                :placeholder="'https://link ' + (i + 1)"
                :aria-label="'Link ' + (i + 1)"
              >
            </div>
          </div>
          <p class="field-hint mt-2">
            <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
            Attach relevant URLs to this task
          </p>
        </div>

        <!-- CHECKLIST -->
        <div class="form-section">
          <label class="form-label-accessible mb-3">
            <i class="bi bi-list-check me-2" aria-hidden="true"></i>Steps
          </label>

          <div class="d-flex gap-2 mb-3">
            <label for="edit-new-step" class="visually-hidden">Add a new step</label>
            <input
              id="edit-new-step"
              v-model="newItemTitle"
              type="text"
              class="form-control dopamine-input flex-grow-1 input-lg"
              placeholder="Add a step..."
              maxlength="300"
              @keydown.enter.prevent="addItem"
            >
            <button
              type="button"
              class="btn-dopamine btn-dopamine-ghost step-add-btn"
              aria-label="Add step to checklist"
              @click="addItem"
            >
              <i class="bi bi-plus me-1" aria-hidden="true"></i> Add
            </button>
          </div>

          <div v-if="checklistItems.length > 0" class="d-flex flex-column gap-2" role="list" aria-label="Task steps">
            <div
              v-for="(item, index) in checklistItems"
              :key="item.id || index"
              class="step-item"
              :class="item.done ? 'step-item-done' : ''"
              role="listitem"
            >
              <i class="bi bi-grip-vertical step-drag-icon" aria-hidden="true"></i>
              <span class="flex-grow-1 step-item-text" :class="item.done ? 'text-decoration-line-through' : ''">
                {{ item.title }}
              </span>
              <button
                type="button"
                class="btn-dopamine btn-dopamine-danger step-remove-btn"
                :aria-label="'Remove step: ' + item.title"
                @click="removeItem(item, index)"
              >
                <i class="bi bi-x" aria-hidden="true"></i>
              </button>
            </div>
          </div>

          <p v-else class="no-items-text">
            <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
            No steps added yet.
          </p>
        </div>

        <!-- BOTONES -->
        <div class="d-flex gap-3 flex-wrap pb-2">
          <button type="submit" class="btn-dopamine btn-dopamine-primary form-action-btn">
            <i class="bi bi-check2 me-2" aria-hidden="true"></i> Save Changes
          </button>
          <button
            type="button"
            class="btn-dopamine btn-dopamine-cancel form-action-btn"
            aria-label="Cancel and go back to tasks"
            @click="router.push('/tasks')"
          >
            <i class="bi bi-x me-2" aria-hidden="true"></i> Cancel
          </button>
        </div>

      </form>
    </div>
  </div>
</template>

<style scoped>
.edittask-wrapper {
  min-height: 100vh; background-color: var(--bg-subtle);
  padding: 2.5rem 1.5rem 5rem; font-family: 'Atkinson Hyperlegible', sans-serif;
}

.edittask-container { max-width: 720px; margin: 0 auto; }

@media (max-width: 768px) { .edittask-wrapper { padding: 1.5rem 1rem 4rem; } }

.form-section { background: var(--bg-card); border: 1.5px solid var(--vanilla-mid); border-radius: 12px; padding: 1.2rem 1.3rem; }

.breadcrumb-visible { font-size: 1rem !important; font-weight: 600 !important; background: var(--bg-card); border: 1.5px solid var(--vanilla-mid); border-radius: 10px; padding: 0.7rem 1rem !important; }
.breadcrumb-visible a        { color: var(--cinnamon-mid) !important; font-weight: 700 !important; font-size: 1rem !important; }
.breadcrumb-visible .current { color: var(--cinnamon-dark) !important; font-weight: 700 !important; }
.breadcrumb-visible .separator { color: var(--vanilla-mid) !important; }

.form-label-accessible { font-family: 'Atkinson Hyperlegible', sans-serif; font-size: 1rem; font-weight: 700; color: var(--cinnamon-dark); display: flex; align-items: center; margin-bottom: 0.5rem; }
.required-star { color: var(--state-error); font-weight: 700; }
.field-hint    { font-size: 0.82rem; color: var(--cinnamon-soft); margin: 0; }
.char-hint     { font-size: 0.72rem; color: var(--cinnamon-soft); margin: 0; }

.input-lg   { font-size: 1rem !important; padding: 0.7rem 0.9rem !important; min-height: 48px !important; }
.input-date { font-size: 0.95rem !important; padding: 0.65rem 0.7rem !important; min-height: 48px !important; }
.icon-input { font-size: 1.4rem !important; max-width: 120px; }

kbd { font-family: 'Atkinson Hyperlegible', sans-serif; font-size: 0.78rem; background: var(--bg-subtle); border: 1px solid var(--vanilla-mid); border-radius: 4px; padding: 0.1rem 0.4rem; color: var(--cinnamon-dark); box-shadow: 0 1px 0 var(--vanilla-mid); }

.textarea-field { font-size: 0.95rem !important; padding: 0.7rem 0.9rem !important; resize: vertical; min-height: 90px; line-height: 1.6; }

.diff-option { text-align: center; padding: 0.9rem 0.5rem; font-family: 'Atkinson Hyperlegible', sans-serif; font-size: 0.9rem; font-weight: 700; cursor: pointer; border-radius: 8px; border: 1.5px solid var(--vanilla-mid); background: var(--bg-subtle); color: var(--cinnamon-mid); transition: all 0.15s; }
.diff-option:focus-visible { outline: 3px solid var(--cinnamon-mid); outline-offset: 2px; }

.links-max-hint { font-size: 0.72rem; font-weight: 400; color: var(--cinnamon-soft); margin-left: 0.5rem; }
.link-num { font-family: 'Atkinson Hyperlegible', sans-serif; font-size: 0.8rem; font-weight: 700; color: var(--cinnamon-soft); width: 20px; text-align: center; flex-shrink: 0; }

.step-add-btn { min-height: 48px; white-space: nowrap; font-family: 'Atkinson Hyperlegible', sans-serif; font-weight: 700; }

.step-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.65rem 0.9rem; background: var(--bg-base); border: 1px solid var(--vanilla-light); border-left: 3px solid var(--cinnamon-mid); border-radius: 8px; }
.step-item-done { background: var(--state-ok-bg); border-color: #C8E4CA; border-left-color: var(--state-ok); }
.step-drag-icon { color: var(--vanilla-mid); font-size: 1rem; }
.step-item-text { font-family: 'Atkinson Hyperlegible', sans-serif; font-size: 0.92rem; font-weight: 600; color: var(--cinnamon-dark); }
.step-remove-btn { width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; flex-shrink: 0; border-radius: 6px; }
.no-items-text { font-size: 0.85rem; color: var(--cinnamon-soft); margin: 0.2rem 0 0; }

.form-action-btn { font-family: 'Atkinson Hyperlegible', sans-serif; font-size: 1rem; font-weight: 700; min-height: 48px; padding: 0.7rem 1.5rem; }
</style>