<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useUserStore } from "@/stores/userStore";
import { rutaApi } from "@/config.js";

// inicializar las herramientas de Pinia y router
const userStore = useUserStore();
const router = useRouter();

//variables para el formulario
const title = ref("");
const description = ref("");
const icon = ref("");
const frecuency = ref("daily");
const dayOfMonth = ref(null);
const selectedDays = ref([]); // array para los dias de la senana

const error = ref(""); //variable para errores

//array de los dias de la semana para el v for
const daysList = [
  "monday", "tuesday", "wednesday", "thursday", 
  "friday", "saturday", "sunday"
];

//funcion para seleccionar o desmarcar dias
function selectDay(day) {
  let position = selectedDays.value.indexOf(day);
  
  if (position == -1) {
    //si no está, se mete
    selectedDays.value.push(day);
  } else {
    //si está, lo quitamos
    selectedDays.value.splice(position, 1);
  }
}

//funcion principal para guardar el habito
async function saveHabit() {
  error.value = "";

  //validaciones
  if (title.value == "") {
    error.value = "Title is required";
    return;
  }
  if (frecuency.value == "weekly" && selectedDays.value.length == 0) {
    error.value = "You must choose a day of the week";
    return;
  }

  //variable que contiene los datos a pasar al backend para su procesamiento
  let habitData = {
    user_id: userStore.user.id, //para el id se saca de Pinia
    title: title.value,
    descrip: description.value,
    icon: icon.value,
    frecuency: frecuency.value,
    dayOfMonth: frecuency.value == "monthly" ? dayOfMonth.value : null,
    days: frecuency.value == "weekly" ? selectedDays.value : []
  };

  // peticion post con fetch
  fetch(rutaApi + "?entity=habits", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(habitData)
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status === "success") {
        alert("Habit successfully created!");
        router.push("/habits"); //volvemos a la lista
      } else {
        error.value = "Error: " + data.message;
      }
    })
    .catch((err) => {
      console.error("Request error", err);
      error.value = "Unable to connect to the server";
    });
}
</script>

<template>
  <div class="newhabit-wrapper">
    <div class="newhabit-container">

      <!-- PAN DE MIGA -->
      <nav class="breadcrumb-dopamine breadcrumb-visible mb-4 fade-up" aria-label="Breadcrumb navigation">
        <RouterLink to="/home"><i class="bi bi-house me-1" aria-hidden="true"></i>Home</RouterLink>
        <span class="separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
        <RouterLink to="/habits">Habits</RouterLink>
        <span class="separator" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
        <span class="current" aria-current="page">New Habit</span>
      </nav>

      <!-- CABECERA -->
      <div class="mb-4 fade-up delay-1">
        <h1 class="page-title">
          <em>start a new</em>
          Habit
        </h1>
      </div>

      <!-- ERROR -->
      <div v-if="error" class="error-text mb-4" role="alert">
        <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>
        <strong>{{ error }}</strong>
      </div>

      <!-- FORMULARIO -->
      <form @submit.prevent="saveHabit" class="d-flex flex-column gap-4 fade-up delay-2" novalidate>

        <!-- NOMBRE -->
        <div class="form-section">
          <label for="habit-title" class="form-label-accessible">
            <i class="bi bi-pencil me-2" aria-hidden="true"></i>Habit name <span class="required-star" aria-hidden="true">*</span>
          </label>
          <input
            id="habit-title"
            v-model="title"
            type="text"
            class="form-control dopamine-input input-lg"
            placeholder="e.g. Drink water, Read 10 min..."
            maxlength="200"
            aria-required="true"
          >
          <p class="char-hint text-end mt-1" aria-live="polite">{{ title.length }}/200</p>
        </div>

        <!-- DESCRIPCIÓN -->
        <div class="form-section">
          <label for="habit-descrip" class="form-label-accessible">
            <i class="bi bi-text-left me-2" aria-hidden="true"></i>Description
          </label>
          <input
            id="habit-descrip"
            v-model="description"
            type="text"
            class="form-control dopamine-input input-lg"
            placeholder="Add more details..."
            maxlength="500"
          >
        </div>

        <!-- ICONO — input libre con hint de emoji picker -->
        <div class="form-section">
          <label for="habit-icon" class="form-label-accessible">
            <i class="bi bi-emoji-smile me-2" aria-hidden="true"></i>Icon
          </label>
          <input
            id="habit-icon"
            v-model="icon"
            type="text"
            class="form-control dopamine-input input-lg icon-input"
            placeholder="e.g. 💧"
            maxlength="4"
            aria-describedby="habit-icon-hint"
          >
          <p id="habit-icon-hint" class="field-hint mt-2">
            <i class="bi bi-keyboard me-1" aria-hidden="true"></i>
            <strong>Windows:</strong> press <kbd>Win + .</kbd> &nbsp;·&nbsp;
            <strong>Mac:</strong> press <kbd>Cmd + Ctrl + Space</kbd> to open the emoji picker
          </p>
          <!-- preview del icono seleccionado -->
          <div v-if="icon" class="icon-preview mt-2" aria-live="polite">
            <span class="icon-preview-emoji" aria-hidden="true">{{ icon }}</span>
            <span class="icon-preview-text">Selected icon</span>
          </div>
        </div>

        <!-- FRECUENCIA -->
        <div class="form-section">
          <label class="form-label-accessible mb-3">
            <i class="bi bi-arrow-repeat me-2" aria-hidden="true"></i>Frequency <span class="required-star" aria-hidden="true">*</span>
          </label>
          <div class="row g-2" role="radiogroup" aria-label="Select frequency">
            <div class="col-4">
              <div
                class="freq-btn"
                :class="frecuency === 'daily' ? 'freq-btn-daily' : ''"
                role="radio" :aria-checked="frecuency === 'daily'" tabindex="0"
                @click="frecuency = 'daily'"
                @keydown.enter="frecuency = 'daily'"
                @keydown.space.prevent="frecuency = 'daily'"
              >
                <i class="bi bi-sun d-block mb-1 freq-btn-icon" aria-hidden="true"></i>
                Daily
              </div>
            </div>
            <div class="col-4">
              <div
                class="freq-btn"
                :class="frecuency === 'weekly' ? 'freq-btn-weekly' : ''"
                role="radio" :aria-checked="frecuency === 'weekly'" tabindex="0"
                @click="frecuency = 'weekly'"
                @keydown.enter="frecuency = 'weekly'"
                @keydown.space.prevent="frecuency = 'weekly'"
              >
                <i class="bi bi-calendar-week d-block mb-1 freq-btn-icon" aria-hidden="true"></i>
                Weekly
              </div>
            </div>
            <div class="col-4">
              <div
                class="freq-btn"
                :class="frecuency === 'monthly' ? 'freq-btn-monthly' : ''"
                role="radio" :aria-checked="frecuency === 'monthly'" tabindex="0"
                @click="frecuency = 'monthly'"
                @keydown.enter="frecuency = 'monthly'"
                @keydown.space.prevent="frecuency = 'monthly'"
              >
                <i class="bi bi-calendar-month d-block mb-1 freq-btn-icon" aria-hidden="true"></i>
                Monthly
              </div>
            </div>
          </div>
          <!-- Select oculto para mantener la lógica -->
          <select v-model="frecuency" class="d-none" aria-hidden="true">
            <option value="daily">Daily</option>
            <option value="weekly">Weekly</option>
            <option value="monthly">Monthly</option>
          </select>
        </div>

        <!-- DÍAS DE LA SEMANA si es semanal -->
        <div v-if="frecuency == 'weekly'" class="form-section fade-up">
          <label class="form-label-accessible mb-3">
            <i class="bi bi-calendar-week me-2 text-weekly" aria-hidden="true"></i>
            <span class="text-weekly">Days of the week</span>
            <span class="required-star ms-1" aria-hidden="true">*</span>
          </label>
          <div class="days-grid" role="group" aria-label="Select days of the week">
            <div
              v-for="day in daysList"
              :key="day"
              class="day-btn"
              :class="selectedDays.includes(day) ? 'day-btn-selected' : ''"
              role="checkbox"
              :aria-checked="selectedDays.includes(day)"
              tabindex="0"
              @click="selectDay(day)"
              @keydown.enter="selectDay(day)"
              @keydown.space.prevent="selectDay(day)"
            >
              <!-- Checkbox oculto para mantener la lógica -->
              <input
                type="checkbox"
                :value="day"
                :checked="selectedDays.includes(day)"
                @change="selectDay(day)"
                class="d-none"
                aria-hidden="true"
              >
              {{ day.slice(0, 3).toUpperCase() }}
            </div>
          </div>
          <p class="days-hint" aria-live="polite">
            <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
            {{ selectedDays.length }} day(s) selected
          </p>
        </div>

        <!-- DÍA DEL MES si es mensual -->
        <!--TODO : Validar febrero-->
        <div v-if="frecuency == 'monthly'" class="form-section fade-up">
          <label for="habit-dayofmonth" class="form-label-accessible mb-2">
            <i class="bi bi-calendar-event me-2 text-monthly" aria-hidden="true"></i>
            <span class="text-monthly">Day of the month</span>
            <span class="required-star ms-1" aria-hidden="true">*</span>
          </label>
          <p class="field-hint mb-2">Between 1 and 31</p>
          <input
            id="habit-dayofmonth"
            v-model="dayOfMonth"
            type="number"
            min="1"
            max="31"
            class="form-control dopamine-input input-date"
            placeholder="e.g. 15"
            style="max-width: 140px"
            aria-required="true"
          >
        </div>

        <!-- BOTONES -->
        <div class="d-flex gap-3 flex-wrap pb-2">
          <button type="submit" class="btn-dopamine btn-dopamine-primary form-action-btn">
            <i class="bi bi-check2 me-2" aria-hidden="true"></i> Create Habit
          </button>
          <button
            type="button"
            class="btn-dopamine btn-dopamine-cancel form-action-btn"
            aria-label="Cancel and go back to habits"
            @click="router.push('/habits')"
          >
            <i class="bi bi-x me-2" aria-hidden="true"></i> Cancel
          </button>
        </div>

      </form>
    </div>
  </div>
</template>

<style scoped>
.newhabit-wrapper {
  min-height: 100vh;
  background-color: var(--bg-subtle);
  padding: 2.5rem 1.5rem 5rem;
  font-family: 'Atkinson Hyperlegible', sans-serif;
}

.newhabit-container { max-width: 720px; margin: 0 auto; }

@media (max-width: 768px) { .newhabit-wrapper { padding: 1.5rem 1rem 4rem; } }

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

/* ICONO — input libre */
.icon-input { font-size: 1.4rem !important; max-width: 120px; }

kbd { font-family: 'Atkinson Hyperlegible', sans-serif; font-size: 0.78rem; background: var(--bg-subtle); border: 1px solid var(--vanilla-mid); border-radius: 4px; padding: 0.1rem 0.4rem; color: var(--cinnamon-dark); box-shadow: 0 1px 0 var(--vanilla-mid); }

.icon-preview { display: flex; align-items: center; gap: 0.6rem; padding: 0.5rem 0.8rem; background: var(--bg-subtle); border-radius: 8px; border: 1px solid var(--vanilla-light); width: fit-content; }
.icon-preview-emoji { font-size: 1.3rem; }
.icon-preview-text  { font-family: 'Atkinson Hyperlegible', sans-serif; font-size: 0.82rem; color: var(--cinnamon-soft); font-weight: 600; }

.freq-btn { text-align: center; padding: 0.9rem 0.5rem; cursor: pointer; font-family: 'Atkinson Hyperlegible', sans-serif; font-size: 0.9rem; font-weight: 700; border-radius: 8px; border: 1.5px solid var(--vanilla-mid); background: var(--bg-subtle); color: var(--cinnamon-mid); transition: all 0.15s; }
.freq-btn:hover           { background: var(--vanilla-light); }
.freq-btn:focus-visible   { outline: 3px solid var(--cinnamon-mid); outline-offset: 2px; }
.freq-btn-icon            { font-size: 1.3rem; }
.freq-btn-daily   { background: var(--bg-base);       border-color: var(--cinnamon-mid); color: var(--cinnamon-dark); }
.freq-btn-weekly  { background: var(--state-info-bg);  border-color: var(--btn-info);     color: #2A5068; }
.freq-btn-monthly { background: var(--vanilla-light);  border-color: var(--vanilla-deep); color: var(--cinnamon-dark); }

.text-weekly  { color: #2A5068; }
.text-monthly { color: var(--vanilla-deep); }

.days-grid { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.day-btn { min-width: 52px; height: 52px; border-radius: 10px; border: 2px solid var(--vanilla-light); background: var(--bg-subtle); color: var(--cinnamon-mid); font-family: 'Atkinson Hyperlegible', sans-serif; font-size: 0.82rem; font-weight: 700; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.15s; padding: 0 0.6rem; user-select: none; }
.day-btn:hover        { border-color: var(--btn-info); background: var(--state-info-bg); color: #2A5068; }
.day-btn:focus-visible { outline: 3px solid var(--btn-info); outline-offset: 2px; }
.day-btn-selected     { background: var(--btn-info) !important; border-color: var(--btn-info) !important; color: #fff !important; }
.days-hint            { font-size: 0.82rem; color: var(--cinnamon-soft); margin: 0.6rem 0 0; }

.form-action-btn { font-family: 'Atkinson Hyperlegible', sans-serif; font-size: 1rem; font-weight: 700; min-height: 48px; padding: 0.7rem 1.5rem; }
</style>