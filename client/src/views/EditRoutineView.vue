<script setup>
//imports para vue, router y api
import { ref, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import { useUserStore } from "@/stores/userStore";
import { rutaApi } from "@/config.js";

const userStore = useUserStore();
const router = useRouter();
const route = useRoute();
const routineId = route.params.id;

const loading = ref(true);
const errorMessage = ref("");

//variables reactivas del formulario
const routineTitle = ref("");
const routineDescrip = ref("");
const routineIcon = ref("");
const routineHour = ref("");
const routineFrecuency = ref("daily");
const routineDayOfMonth = ref(null);
const routineDays = ref([]);

//checklist existente
const routineSteps = ref([]);
const stepInputText = ref("");

const weekDaysList = [
  "monday",
  "tuesday",
  "wednesday",
  "thursday",
  "friday",
  "saturday",
  "sunday",
];

//funcion para cargar datos de la rutina
async function loadRoutineData() {
  loading.value = true;

  fetch(rutaApi + "?entity=routines&id=" + routineId)
    .then((res) => res.json())
    .then((data) => {
      if (!data || data.status === "error") {
        errorMessage.value = "Routine not found";
        loading.value = false;
        return;
      }

      //se rellena los campos del formulario
      routineTitle.value = data.title || "";
      routineDescrip.value = data.descrip || "";
      routineIcon.value = data.icon || "";

      if (data.hour) {
        routineHour.value = data.hour.slice(0, 5);
      } else {
        routineHour.value = "";
      }

      routineFrecuency.value = data.frecuency || "daily";
      routineDayOfMonth.value = data.dayOfMonth || null;
      routineDays.value = data.days || [];
      routineSteps.value = data.checklist || [];
      loading.value = false;
    })
    .catch((err) => {
      errorMessage.value = "Error loading routine";
      loading.value = false;
    });
}

//funcion para seleccionar o quitar dias de la semana
function toggleDaySelection(day) {
  let index = routineDays.value.indexOf(day);
  if (index == -1) {
    routineDays.value.push(day);
  } else {
    routineDays.value.splice(index, 1);
  }
}

//añadir subtarea directamente bbdd
async function addNewStep() {
  if (stepInputText.value.trim() == "") return;

  fetch(rutaApi + "?entity=routine_checklist", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      routine_id: routineId,
      title: stepInputText.value.trim(),
    }),
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.status === "success") {
        routineSteps.value.push({
          id: data.id,
          title: stepInputText.value.trim(),
          done: false,
        });
        stepInputText.value = "";
      }
    });
}

//borrar paso bbdd
async function deleteStep(step, index) {
  fetch(rutaApi + "?entity=routine_checklist&id=" + step.id, {
    method: "DELETE",
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.status === "success") {
        routineSteps.value.splice(index, 1);
      }
    });
}

//funcion para guardar los cambios de la rutina
async function updateRoutine() {
  errorMessage.value = "";

  // validaciones antes de guardar
  if (routineTitle.value == "") {
    errorMessage.value = "Title is required";
    return;
  }
  if (routineFrecuency.value == "weekly" && routineDays.value.length == 0) {
    errorMessage.value = "Select at least one day";
    return;
  }

  let dayOfMonthValue = null;
  if (routineFrecuency.value == "monthly") {
    dayOfMonthValue = routineDayOfMonth.value;
  }

  let daysValue = [];
  if (routineFrecuency.value == "weekly") {
    daysValue = routineDays.value;
  }

  // objeto con datos para enviar al backend
  let routineData = {
    title: routineTitle.value,
    descrip: routineDescrip.value,
    icon: routineIcon.value || null,
    hour: routineHour.value,
    color: "#6B8FA3",
    frecuency: routineFrecuency.value,
    dayOfMonth: dayOfMonthValue,
    days: daysValue,
  };

  fetch(rutaApi + "?entity=routines&id=" + routineId, {
    method: "PUT",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(routineData),
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.status === "success") {
        router.push("/routines");
      } else {
        errorMessage.value = "Error updating routine";
      }
    })
    .catch((err) => {
      errorMessage.value = "Connection error";
    });
}

// cuando carga la vista se buscan los datos
onMounted(() => {
  loadRoutineData();
});
</script>

<template>
  <div class="editroutine-wrapper">
    <div class="editroutine-container">
      <nav
        class="breadcrumb-dopamine breadcrumb-visible mb-4 fade-up"
        aria-label="Breadcrumb navigation"
      >
        <RouterLink to="/"
          ><i class="bi bi-house me-1" aria-hidden="true"></i>Home</RouterLink
        >
        <span class="separator" aria-hidden="true"
          ><i class="bi bi-chevron-right"></i
        ></span>
        <RouterLink to="/routines">Routines</RouterLink>
        <span class="separator" aria-hidden="true"
          ><i class="bi bi-chevron-right"></i
        ></span>
        <span class="current" aria-current="page">Edit Routine</span>
      </nav>

      <div class="mb-4 fade-up delay-1">
        <h1 class="page-title">
          <em>editing a</em>
          Routine
        </h1>
      </div>

      <div v-if="loading" class="loading-text" aria-live="polite">
        <div class="spinner-border spinner-border-sm me-2" role="status">
          <span class="visually-hidden">Loading routine data...</span>
        </div>
        Loading routine...
      </div>

      <div v-if="errorMessage" class="error-text mb-4" role="alert">
        <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>
        <strong>{{ errorMessage }}</strong>
      </div>

      <form
        v-if="!loading"
        @submit.prevent="updateRoutine"
        class="d-flex flex-column gap-4 fade-up delay-2"
        novalidate
      >
        <div class="form-section">
          <label for="edit-title" class="form-label-accessible">
            <i class="bi bi-pencil me-2" aria-hidden="true"></i>Title
            <span class="required-star" aria-hidden="true">*</span>
          </label>
          <input
            id="edit-title"
            v-model="routineTitle"
            type="text"
            class="form-control dopamine-input input-lg"
            placeholder="e.g. Morning Routine, Wind down..."
            aria-required="true"
          />
        </div>

        <div class="form-section">
          <label for="edit-descrip" class="form-label-accessible">
            <i class="bi bi-text-left me-2" aria-hidden="true"></i>Description
          </label>
          <input
            id="edit-descrip"
            v-model="routineDescrip"
            type="text"
            class="form-control dopamine-input input-lg"
            placeholder="What is this routine for?"
          />
        </div>

        <div class="form-section">
          <label for="edit-icon" class="form-label-accessible">
            <i class="bi bi-emoji-smile me-2" aria-hidden="true"></i>Icon
          </label>
          <input
            id="edit-icon"
            v-model="routineIcon"
            type="text"
            class="form-control dopamine-input input-lg icon-input"
            placeholder="e.g. 🌅"
            maxlength="4"
            aria-describedby="edit-icon-hint"
          />
          <p id="edit-icon-hint" class="field-hint mt-2">
            <i class="bi bi-keyboard me-1" aria-hidden="true"></i>
            <strong>Windows:</strong> press <kbd>Win + .</kbd> &nbsp;·&nbsp;
            <strong>Mac:</strong> press <kbd>Cmd + Ctrl + Space</kbd>
          </p>
        </div>

        <div class="form-section">
          <label for="edit-hour" class="form-label-accessible mb-2">
            <i class="bi bi-clock me-2" aria-hidden="true"></i>Time
          </label>
          <p class="field-hint mb-2">When does this routine happen?</p>
          <input
            id="edit-hour"
            v-model="routineHour"
            type="time"
            class="form-control dopamine-input input-date"
            style="max-width: 160px"
          />
        </div>

        <div class="form-section">
          <label class="form-label-accessible mb-3">
            <i class="bi bi-arrow-repeat me-2" aria-hidden="true"></i>Frequency
            <span class="required-star" aria-hidden="true">*</span>
          </label>
          <div class="row g-2" role="radiogroup" aria-label="Select frequency">
            <div class="col-4">
              <div
                class="freq-btn"
                :class="routineFrecuency === 'daily' ? 'freq-btn-daily' : ''"
                role="radio"
                :aria-checked="routineFrecuency === 'daily'"
                tabindex="0"
                @click="routineFrecuency = 'daily'"
                @keydown.enter="routineFrecuency = 'daily'"
                @keydown.space.prevent="routineFrecuency = 'daily'"
              >
                <i
                  class="bi bi-sun d-block mb-1 freq-btn-icon"
                  aria-hidden="true"
                ></i
                >Daily
              </div>
            </div>
            <div class="col-4">
              <div
                class="freq-btn"
                :class="routineFrecuency === 'weekly' ? 'freq-btn-weekly' : ''"
                role="radio"
                :aria-checked="routineFrecuency === 'weekly'"
                tabindex="0"
                @click="routineFrecuency = 'weekly'"
                @keydown.enter="routineFrecuency = 'weekly'"
                @keydown.space.prevent="routineFrecuency = 'weekly'"
              >
                <i
                  class="bi bi-calendar-week d-block mb-1 freq-btn-icon"
                  aria-hidden="true"
                ></i
                >Weekly
              </div>
            </div>
            <div class="col-4">
              <div
                class="freq-btn"
                :class="
                  routineFrecuency === 'monthly' ? 'freq-btn-monthly' : ''
                "
                role="radio"
                :aria-checked="routineFrecuency === 'monthly'"
                tabindex="0"
                @click="routineFrecuency = 'monthly'"
                @keydown.enter="routineFrecuency = 'monthly'"
                @keydown.space.prevent="routineFrecuency = 'monthly'"
              >
                <i
                  class="bi bi-calendar-month d-block mb-1 freq-btn-icon"
                  aria-hidden="true"
                ></i
                >Monthly
              </div>
            </div>
          </div>
          <select v-model="routineFrecuency" class="d-none" aria-hidden="true">
            <option value="daily">Daily</option>
            <option value="weekly">Weekly</option>
            <option value="monthly">Monthly</option>
          </select>
        </div>

        <div v-if="routineFrecuency == 'weekly'" class="form-section fade-up">
          <label class="form-label-accessible mb-3">
            <i
              class="bi bi-calendar-week me-2 text-weekly"
              aria-hidden="true"
            ></i>
            <span class="text-weekly">Days of the week</span>
            <span class="required-star ms-1" aria-hidden="true">*</span>
          </label>
          <div
            class="days-grid"
            role="group"
            aria-label="Select days of the week"
          >
            <div
              v-for="day in weekDaysList"
              :key="day"
              class="day-btn"
              :class="routineDays.includes(day) ? 'day-btn-selected' : ''"
              role="checkbox"
              :aria-checked="routineDays.includes(day)"
              tabindex="0"
              @click="toggleDaySelection(day)"
              @keydown.enter="toggleDaySelection(day)"
              @keydown.space.prevent="toggleDaySelection(day)"
            >
              {{ day.slice(0, 3).toUpperCase() }}
            </div>
          </div>
          <p class="days-hint" aria-live="polite">
            <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
            {{ routineDays.length }} day(s) selected
          </p>
        </div>

        <div v-if="routineFrecuency == 'monthly'" class="form-section fade-up">
          <label for="edit-dayofmonth" class="form-label-accessible mb-2">
            <i
              class="bi bi-calendar-event me-2 text-monthly"
              aria-hidden="true"
            ></i>
            <span class="text-monthly">Day of the month</span>
            <span class="required-star ms-1" aria-hidden="true">*</span>
          </label>
          <p class="field-hint mb-2">Between 1 and 31</p>
          <input
            id="edit-dayofmonth"
            v-model="routineDayOfMonth"
            type="number"
            min="1"
            max="31"
            class="form-control dopamine-input input-date"
            placeholder="e.g. 15"
            style="max-width: 140px"
            aria-required="true"
          />
        </div>

        <div class="form-section">
          <label class="form-label-accessible mb-3">
            <i class="bi bi-list-check me-2" aria-hidden="true"></i>Steps
          </label>

          <div class="d-flex gap-2 mb-3">
            <label for="edit-new-step" class="visually-hidden"
              >Add a step</label
            >
            <input
              id="edit-new-step"
              v-model="stepInputText"
              type="text"
              class="form-control dopamine-input flex-grow-1 input-lg"
              placeholder="Add a step..."
              @keydown.enter.prevent="addNewStep"
            />
            <button
              type="button"
              class="btn-dopamine btn-dopamine-ghost step-add-btn"
              aria-label="Add step to routine"
              @click="addNewStep"
            >
              <i class="bi bi-plus me-1" aria-hidden="true"></i> Add
            </button>
          </div>

          <div
            v-if="routineSteps.length > 0"
            class="d-flex flex-column gap-2"
            role="list"
            aria-label="Routine steps"
          >
            <div
              v-for="(step, index) in routineSteps"
              :key="step.id || index"
              class="step-item"
              :class="step.done ? 'step-item-done' : ''"
              role="listitem"
            >
              <i
                class="bi bi-grip-vertical step-drag-icon"
                aria-hidden="true"
              ></i>
              <span
                class="flex-grow-1 step-item-text"
                :class="step.done ? 'text-decoration-line-through' : ''"
              >
                {{ step.title }}
              </span>
              <button
                type="button"
                class="btn-dopamine btn-dopamine-danger step-remove-btn"
                :aria-label="'Remove step: ' + step.title"
                @click="deleteStep(step, index)"
              >
                <i class="bi bi-x" aria-hidden="true"></i>
              </button>
            </div>
          </div>

          <p v-else class="days-hint">
            <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
            No steps yet.
          </p>
        </div>

        <div class="d-flex gap-3 flex-wrap pb-2">
          <button
            type="submit"
            class="btn-dopamine btn-dopamine-primary form-action-btn"
          >
            <i class="bi bi-check2 me-2" aria-hidden="true"></i> Save Changes
          </button>
          <button
            type="button"
            class="btn-dopamine btn-dopamine-cancel form-action-btn"
            aria-label="Cancel and go back to routines"
            @click="router.push('/routines')"
          >
            <i class="bi bi-x me-2" aria-hidden="true"></i> Cancel
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.editroutine-wrapper {
  min-height: 100vh;
  background-color: var(--bg-subtle);
  padding: 2.5rem 1.5rem 5rem;
  font-family: "Atkinson Hyperlegible", sans-serif;
}

.editroutine-container {
  max-width: 720px;
  margin: 0 auto;
}

@media (max-width: 768px) {
  .editroutine-wrapper {
    padding: 1.5rem 1rem 4rem;
  }
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
.breadcrumb-visible a {
  color: var(--cinnamon-mid) !important;
  font-weight: 700 !important;
  font-size: 1rem !important;
}
.breadcrumb-visible .current {
  color: var(--cinnamon-dark) !important;
  font-weight: 700 !important;
}
.breadcrumb-visible .separator {
  color: var(--vanilla-mid) !important;
}

.form-label-accessible {
  font-family: "Atkinson Hyperlegible", sans-serif;
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
}
.field-hint {
  font-size: 0.82rem;
  color: var(--cinnamon-soft);
  margin: 0;
}

kbd {
  font-family: "Atkinson Hyperlegible", sans-serif;
  font-size: 0.78rem;
  background: var(--bg-subtle);
  border: 1px solid var(--vanilla-mid);
  border-radius: 4px;
  padding: 0.1rem 0.4rem;
  color: var(--cinnamon-dark);
  box-shadow: 0 1px 0 var(--vanilla-mid);
}

.icon-input {
  font-size: 1.4rem !important;
  max-width: 120px;
}
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
  font-family: "Atkinson Hyperlegible", sans-serif;
  font-size: 0.9rem;
  font-weight: 700;
  border-radius: 8px;
  border: 1.5px solid var(--vanilla-mid);
  background: var(--bg-subtle);
  color: var(--cinnamon-mid);
  transition: all 0.15s;
}
.freq-btn:hover {
  background: var(--vanilla-light);
}
.freq-btn:focus-visible {
  outline: 3px solid var(--cinnamon-mid);
  outline-offset: 2px;
}
.freq-btn-icon {
  font-size: 1.3rem;
}
.freq-btn-daily {
  background: var(--bg-base);
  border-color: var(--cinnamon-mid);
  color: var(--cinnamon-dark);
}
.freq-btn-weekly {
  background: var(--state-info-bg);
  border-color: var(--btn-info);
  color: #2a5068;
}
.freq-btn-monthly {
  background: var(--vanilla-light);
  border-color: var(--vanilla-deep);
  color: var(--cinnamon-dark);
}

.text-weekly {
  color: #2a5068;
}
.text-monthly {
  color: var(--vanilla-deep);
}

.days-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}
.day-btn {
  min-width: 52px;
  height: 52px;
  border-radius: 10px;
  border: 2px solid var(--vanilla-light);
  background: var(--bg-subtle);
  color: var(--cinnamon-mid);
  font-family: "Atkinson Hyperlegible", sans-serif;
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
.day-btn:hover {
  border-color: var(--btn-info);
  background: var(--state-info-bg);
  color: #2a5068;
}
.day-btn:focus-visible {
  outline: 3px solid var(--btn-info);
  outline-offset: 2px;
}
.day-btn-selected {
  background: var(--btn-info) !important;
  border-color: var(--btn-info) !important;
  color: #fff !important;
}
.days-hint {
  font-size: 0.82rem;
  color: var(--cinnamon-soft);
  margin: 0.6rem 0 0;
}

.step-add-btn {
  min-height: 48px;
  white-space: nowrap;
  font-family: "Atkinson Hyperlegible", sans-serif;
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
.step-item-done {
  background: var(--state-ok-bg);
  border-color: #c8e4ca;
  border-left-color: var(--state-ok);
}
.step-drag-icon {
  color: var(--vanilla-mid);
  font-size: 1rem;
}
.step-item-text {
  font-family: "Atkinson Hyperlegible", sans-serif;
  font-size: 0.92rem;
  font-weight: 600;
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

.form-action-btn {
  font-family: "Atkinson Hyperlegible", sans-serif;
  font-size: 1rem;
  font-weight: 700;
  min-height: 48px;
  padding: 0.7rem 1.5rem;
}
</style>
