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

//array con emojis
const emojis = ["🔄", "💧", "🏃", "📚", "🧘", "🥗", "💊", "🛌", "🧹", "💼"];

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
      <nav class="breadcrumb-dopamine breadcrumb-visible mb-4 fade-up">
        <RouterLink to="/"><i class="bi bi-house me-1"></i>Home</RouterLink>
        <span class="separator"><i class="bi bi-chevron-right"></i></span>
        <RouterLink to="/habits">Habits</RouterLink>
        <span class="separator"><i class="bi bi-chevron-right"></i></span>
        <span class="current">New Habit</span>
      </nav>

      <!-- CABECERA -->
      <div class="mb-4 fade-up delay-1">
        <h1 class="page-title">
          <em>start a new</em>
          Habit
        </h1>
      </div>

      <!-- ERROR -->
      <div v-if="error" class="error-text mb-4">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>{{ error }}</strong>
      </div>

      <!-- FORMULARIO -->
      <form @submit.prevent="saveHabit" class="d-flex flex-column gap-4 fade-up delay-2">

        <!-- NOMBRE -->
        <div class="form-section">
          <label class="form-label-accessible">
            <i class="bi bi-pencil me-2"></i>Habit name <span class="required-star">*</span>
          </label>
          <input
            v-model="title"
            type="text"
            class="form-control dopamine-input input-lg"
            placeholder="e.g. Drink water, Read 10 min..."
          >
        </div>

        <!-- DESCRIPCIÓN -->
        <div class="form-section">
          <label class="form-label-accessible">
            <i class="bi bi-text-left me-2"></i>Description
          </label>
          <input
            v-model="description"
            type="text"
            class="form-control dopamine-input input-lg"
            placeholder="Add more details..."
          >
        </div>

        <!-- SELECTOR DE ICONO -->
        <div class="form-section">
          <label class="form-label-accessible mb-3">
            <i class="bi bi-emoji-smile me-2"></i>Icon
          </label>
          <div class="emoji-grid">
            <button
              v-for="e in emojis"
              :key="e"
              type="button"
              class="emoji-btn"
              :class="icon == e ? 'emoji-btn-selected' : ''"
              @click="icon = e"
            >
              {{ e }}
            </button>
          </div>
          <div v-if="icon" class="emoji-selected-preview mt-3">
            <span class="emoji-preview-icon">{{ icon }}</span>
            <span class="emoji-preview-text">Selected icon</span>
          </div>
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
                :class="frecuency === 'daily' ? 'freq-btn-daily' : ''"
                @click="frecuency = 'daily'"
              >
                <i class="bi bi-sun d-block mb-1 freq-btn-icon"></i>
                Daily
              </div>
            </div>
            <div class="col-4">
              <div
                class="freq-btn"
                :class="frecuency === 'weekly' ? 'freq-btn-weekly' : ''"
                @click="frecuency = 'weekly'"
              >
                <i class="bi bi-calendar-week d-block mb-1 freq-btn-icon"></i>
                Weekly
              </div>
            </div>
            <div class="col-4">
              <div
                class="freq-btn"
                :class="frecuency === 'monthly' ? 'freq-btn-monthly' : ''"
                @click="frecuency = 'monthly'"
              >
                <i class="bi bi-calendar-month d-block mb-1 freq-btn-icon"></i>
                Monthly
              </div>
            </div>
          </div>
          <!-- Select oculto para mantener la lógica -->
          <select v-model="frecuency" class="d-none">
            <option value="daily">Daily</option>
            <option value="weekly">Weekly</option>
            <option value="monthly">Monthly</option>
          </select>
        </div>

        <!-- DÍAS DE LA SEMANA si es semanal -->
        <div v-if="frecuency == 'weekly'" class="form-section fade-up">
          <label class="form-label-accessible mb-3">
            <i class="bi bi-calendar-week me-2 text-weekly"></i>
            <span class="text-weekly">Days of the week</span>
            <span class="required-star ms-1">*</span>
          </label>
          <div class="days-grid">
            <div
              v-for="day in daysList"
              :key="day"
              class="day-btn"
              :class="selectedDays.includes(day) ? 'day-btn-selected' : ''"
              @click="selectDay(day)"
            >
              <!-- Checkbox oculto para mantener la lógica -->
              <input
                type="checkbox"
                :value="day"
                :checked="selectedDays.includes(day)"
                @change="selectDay(day)"
                class="d-none"
              >
              {{ day.slice(0, 3).toUpperCase() }}
            </div>
          </div>
          <p class="days-hint">
            <i class="bi bi-info-circle me-1"></i>
            {{ selectedDays.length }} day(s) selected
          </p>
        </div>

        <!-- DÍA DEL MES si es mensual -->
        <!--TODO : Validar febrero-->
        <div v-if="frecuency == 'monthly'" class="form-section fade-up">
          <label class="form-label-accessible mb-2">
            <i class="bi bi-calendar-event me-2 text-monthly"></i>
            <span class="text-monthly">Day of the month</span>
            <span class="required-star ms-1">*</span>
          </label>
          <p class="field-hint mb-2">Between 1 and 31</p>
          <input
            v-model="dayOfMonth"
            type="number"
            min="1"
            max="31"
            class="form-control dopamine-input input-date"
            placeholder="e.g. 15"
            style="max-width: 140px"
          >
        </div>

        <!-- BOTONES -->
        <div class="d-flex gap-3 flex-wrap pb-2">
          <button type="submit" class="btn-dopamine btn-dopamine-primary form-action-btn">
            <i class="bi bi-check2 me-2"></i> Create Habit
          </button>
          <button
            type="button"
            class="btn-dopamine btn-dopamine-cancel form-action-btn"
            @click="router.push('/habits')"
          >
            <i class="bi bi-x me-2"></i> Cancel
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

.newhabit-container {
  max-width: 720px;
  margin: 0 auto;
}

@media (max-width: 768px) {
  .newhabit-wrapper { padding: 1.5rem 1rem 4rem; }
}

/* SECCIONES */
.form-section {
  background: var(--bg-card);
  border: 1.5px solid var(--vanilla-mid);
  border-radius: 12px;
  padding: 1.2rem 1.3rem;
}

/* BREADCRUMB VISIBLE */
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

.breadcrumb-visible .separator { color: var(--vanilla-mid) !important; }

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

.required-star { color: var(--state-error); font-weight: 700; }
.field-hint { font-size: 0.82rem; color: var(--cinnamon-soft); margin: 0; }

/* INPUT GRANDE */
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

/* SELECTOR EMOJI */
.emoji-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.emoji-btn {
  width: 52px;
  height: 52px;
  font-size: 1.4rem;
  border-radius: 10px;
  border: 2px solid var(--vanilla-light);
  background: var(--bg-subtle);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s;
}

.emoji-btn:hover {
  border-color: var(--vanilla-mid);
  background: var(--vanilla-light);
  transform: scale(1.08);
}

.emoji-btn-selected {
  border-color: var(--cinnamon-mid) !important;
  background: var(--vanilla-light) !important;
  box-shadow: 0 0 0 3px rgba(139, 94, 60, 0.2);
}

.emoji-selected-preview {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.5rem 0.8rem;
  background: var(--bg-subtle);
  border-radius: 8px;
  border: 1px solid var(--vanilla-light);
  width: fit-content;
}

.emoji-preview-icon  { font-size: 1.3rem; }
.emoji-preview-text  { font-size: 0.82rem; color: var(--cinnamon-soft); font-weight: 600; }

/* BOTONES DE FRECUENCIA */
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

.freq-btn:hover      { background: var(--vanilla-light); }
.freq-btn-icon       { font-size: 1.3rem; }

.freq-btn-daily   { background: var(--bg-base);       border-color: var(--cinnamon-mid); color: var(--cinnamon-dark); }
.freq-btn-weekly  { background: var(--state-info-bg);  border-color: var(--btn-info);     color: #2A5068; }
.freq-btn-monthly { background: var(--vanilla-light);  border-color: var(--vanilla-deep); color: var(--cinnamon-dark); }

/* COLORES POR FRECUENCIA */
.text-weekly  { color: #2A5068; }
.text-monthly { color: var(--vanilla-deep); }

/* SELECTOR DE DÍAS */
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

.day-btn:hover {
  border-color: var(--btn-info);
  background: var(--state-info-bg);
  color: #2A5068;
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

/* BOTONES ACCIÓN */
.form-action-btn {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 1rem;
  font-weight: 700;
  min-height: 48px;
  padding: 0.7rem 1.5rem;
}
</style>