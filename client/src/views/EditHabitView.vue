<script setup>
import { ref, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import { useUserStore } from "@/stores/userStore";
import { rutaApi } from "@/config.js";

const userStore = useUserStore();
const router = useRouter();
const route = useRoute();
const habitId = route.params.id;

//variables de estado de la vista
const loading = ref(true);
const errorMessage = ref("");

//variables del formulario
const titleInput = ref("");
const descripInput = ref("");
const iconInput = ref("");
const frecuency = ref("daily");
const dayOfMonth = ref(null);
const selectedDays = ref([]);

//lista de dias de la semana
const weekDaysList = [
  "monday",
  "tuesday",
  "wednesday",
  "thursday",
  "friday",
  "saturday",
  "sunday",
];

//funcion para cargar los datos del habito a editar
function loadHabitData() {
  loading.value = true;

  fetch(rutaApi + "?entity=habits&id=" + habitId)
    .then(function (res) {
      return res.json();
    })
    .then(function (data) {
      if (!data || data.status === "error") {
        errorMessage.value = "Habit not found";
        loading.value = false;
        return;
      }

      //rellenamos los campos con los datos existentes
      titleInput.value = data.title || "";
      descripInput.value = data.descrip || "";
      iconInput.value = data.icon || "";
      frecuency.value = data.frecuency || "daily";
      dayOfMonth.value = data.dayOfMonth || null;
      selectedDays.value = data.days || [];
      loading.value = false;
    })
    .catch(function () {
      errorMessage.value = "Error loading habit";
      loading.value = false;
    });
}

//funcion para marcar o desmarcar un dia de la semana
function toggleDay(day) {
  let index = selectedDays.value.indexOf(day);
  if (index === -1) {
    selectedDays.value.push(day);
  } else {
    selectedDays.value.splice(index, 1);
  }
}

//funcion principal para guardar los cambios
function updateHabit() {
  errorMessage.value = "";

  //validaciones basicas
  if (titleInput.value.trim() === "") {
    errorMessage.value = "Title is required";
    return;
  }
  if (frecuency.value === "weekly" && selectedDays.value.length === 0) {
    errorMessage.value = "Select at least one day";
    return;
  }

  //objeto con los datos actualizados para enviar
  let habitData = {
    title: titleInput.value.trim(),
    descrip: descripInput.value || null,
    icon: iconInput.value || null,
    frecuency: frecuency.value,
    dayOfMonth: frecuency.value === "monthly" ? dayOfMonth.value : null,
    days: frecuency.value === "weekly" ? selectedDays.value : [],
  };

  //peticion put para actualizar el habito
  fetch(rutaApi + "?entity=habits&id=" + habitId, {
    method: "PUT",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(habitData),
  })
    .then(function (res) {
      return res.json();
    })
    .then(function (data) {
      if (data.status === "success") {
        router.push("/habits");
      } else {
        errorMessage.value = data.message || "Error updating habit";
      }
    })
    .catch(function () {
      errorMessage.value = "Connection error";
    });
}

//cuando carga la vista, se buscan los datos del habito
onMounted(function () {
  loadHabitData();
});
</script>

<template>
  <div class="edithabit-wrapper">
    <div class="edithabit-container">
      <!-- miga de pan -->
      <nav class="breadcrumb-dopamine breadcrumb-visible mb-4 fade-up">
        <RouterLink to="/home"><i class="bi bi-house me-1"></i>Home</RouterLink>
        <span class="separator"><i class="bi bi-chevron-right"></i></span>
        <RouterLink to="/habits">Habits</RouterLink>
        <span class="separator"><i class="bi bi-chevron-right"></i></span>
        <span class="current">Edit Habit</span>
      </nav>

      <!-- cabecera -->
      <div class="mb-4 fade-up delay-1">
        <h1 class="page-title"><em>editing a</em> Habit</h1>
      </div>

      <!-- spinner mientras carga -->
      <div v-if="loading" class="loading-text">
        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
        Loading habit...
      </div>

      <!-- mensaje de error -->
      <div v-if="errorMessage" class="error-text mb-4" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>{{ errorMessage }}</strong>
      </div>

      <!-- formulario, solo visible cuando termina de cargar -->
      <form
        v-if="!loading"
        @submit.prevent="updateHabit"
        class="d-flex flex-column gap-4 fade-up delay-2"
        novalidate
      >
        <!-- nombre del habito -->
        <div class="form-section">
          <label for="edit-title" class="auth-label">
            <i class="bi bi-pencil me-2"></i>Habit name *
          </label>
          <input
            id="edit-title"
            v-model="titleInput"
            type="text"
            class="form-control dopamine-input input-lg"
            placeholder="e.g. Drink water, Read 10 min..."
            maxlength="200"
          />
          <p class="char-hint text-end mt-1">{{ titleInput.length }}/200</p>
        </div>

        <!-- descripcion opcional -->
        <div class="form-section">
          <label for="edit-descrip" class="auth-label">
            <i class="bi bi-text-left me-2"></i>Description
          </label>
          <input
            id="edit-descrip"
            v-model="descripInput"
            type="text"
            class="form-control dopamine-input input-lg"
            placeholder="Add more details..."
            maxlength="500"
          />
        </div>

        <!-- icono con picker nativo del sistema -->
        <div class="form-section">
          <label for="edit-icon" class="auth-label">
            <i class="bi bi-emoji-smile me-2"></i>Icon
          </label>
          <input
            id="edit-icon"
            v-model="iconInput"
            type="text"
            class="form-control dopamine-input input-lg icon-input"
            placeholder="e.g. 💧"
            maxlength="4"
          />
          <p class="field-hint mt-2">
            <i class="bi bi-keyboard me-1"></i>
            <strong>Windows:</strong> <kbd>Win + .</kbd> &nbsp;·&nbsp;
            <strong>Mac:</strong> <kbd>Cmd + Ctrl + Space</kbd>
          </p>
          <!-- preview del icono -->
          <div v-if="iconInput" class="icon-preview mt-2">
            <span class="icon-preview-emoji">{{ iconInput }}</span>
            <span class="icon-preview-text">Selected icon</span>
          </div>
        </div>

        <!-- selector de frecuencia -->
        <div class="form-section">
          <label class="auth-label mb-3">
            <i class="bi bi-arrow-repeat me-2"></i>Frequency *
          </label>
          <div class="row g-2">
            <div class="col-4">
              <div
                class="freq-btn"
                :class="frecuency === 'daily' ? 'freq-btn-daily' : ''"
                @click="frecuency = 'daily'"
              >
                <i class="bi bi-sun d-block mb-1 freq-btn-icon"></i>Daily
              </div>
            </div>
            <div class="col-4">
              <div
                class="freq-btn"
                :class="frecuency === 'weekly' ? 'freq-btn-weekly' : ''"
                @click="frecuency = 'weekly'"
              >
                <i class="bi bi-calendar-week d-block mb-1 freq-btn-icon"></i
                >Weekly
              </div>
            </div>
            <div class="col-4">
              <div
                class="freq-btn"
                :class="frecuency === 'monthly' ? 'freq-btn-monthly' : ''"
                @click="frecuency = 'monthly'"
              >
                <i class="bi bi-calendar-month d-block mb-1 freq-btn-icon"></i
                >Monthly
              </div>
            </div>
          </div>
          <!-- select oculto que guarda el valor real -->
          <select v-model="frecuency" class="d-none">
            <option value="daily">Daily</option>
            <option value="weekly">Weekly</option>
            <option value="monthly">Monthly</option>
          </select>
        </div>

        <!-- dias de la semana si es semanal -->
        <div v-if="frecuency === 'weekly'" class="form-section fade-up">
          <label class="auth-label mb-3">
            <i class="bi bi-calendar-week me-2 text-weekly"></i>
            <span class="text-weekly">Days of the week *</span>
          </label>
          <div class="days-grid">
            <div
              v-for="day in weekDaysList"
              :key="day"
              class="day-btn"
              :class="selectedDays.includes(day) ? 'day-btn-selected' : ''"
              @click="toggleDay(day)"
            >
              {{ day.slice(0, 3).toUpperCase() }}
            </div>
          </div>
          <p class="days-hint">{{ selectedDays.length }} day(s) selected</p>
        </div>

        <!-- dia del mes si es mensual -->
        <div v-if="frecuency === 'monthly'" class="form-section fade-up">
          <label for="edit-dayofmonth" class="auth-label mb-2">
            <i class="bi bi-calendar-event me-2 text-monthly"></i>
            <span class="text-monthly">Day of the month *</span>
          </label>
          <p class="field-hint mb-2">Between 1 and 31</p>
          <input
            id="edit-dayofmonth"
            v-model="dayOfMonth"
            type="number"
            min="1"
            max="31"
            class="form-control dopamine-input input-date"
            placeholder="e.g. 15"
            style="max-width: 140px"
          />
        </div>

        <!-- botones de accion -->
        <div class="d-flex gap-3 flex-wrap pb-2">
          <button
            type="submit"
            class="btn-dopamine btn-dopamine-primary form-action-btn"
          >
            <i class="bi bi-check2 me-2"></i> Save Changes
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
/* contenedor y centrado */
.edithabit-wrapper {
  min-height: 100vh;
  background-color: var(--bg-subtle);
  padding: 2.5rem 1.5rem 5rem;
  font-family: "Atkinson Hyperlegible", sans-serif;
}
.edithabit-container {
  max-width: 720px;
  margin: 0 auto;
}
@media (max-width: 768px) {
  .edithabit-wrapper {
    padding: 1.5rem 1rem 4rem;
  }
}

/* secciones del formulario */
.form-section {
  background: var(--bg-card);
  border: 1.5px solid var(--vanilla-mid);
  border-radius: 12px;
  padding: 1.2rem 1.3rem;
}

/* miga de pan visible */
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
}
.breadcrumb-visible .current {
  color: var(--cinnamon-dark) !important;
  font-weight: 700 !important;
}
.breadcrumb-visible .separator {
  color: var(--vanilla-mid) !important;
}

/* etiqueta de campo */
.auth-label {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--cinnamon-dark);
  display: flex;
  align-items: center;
  margin-bottom: 0.5rem;
}

.field-hint {
  font-size: 0.82rem;
  color: var(--cinnamon-soft);
  margin: 0;
}
.char-hint {
  font-size: 0.72rem;
  color: var(--cinnamon-soft);
  margin: 0;
}

/* input grande y input de fecha */
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

/* input del icono mas grande para ver el emoji */
.icon-input {
  font-size: 1.4rem !important;
  max-width: 120px;
}

/* preview del icono seleccionado */
.icon-preview {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.5rem 0.8rem;
  background: var(--bg-subtle);
  border-radius: 8px;
  border: 1px solid var(--vanilla-light);
  width: fit-content;
}
.icon-preview-emoji {
  font-size: 1.3rem;
}
.icon-preview-text {
  font-size: 0.82rem;
  color: var(--cinnamon-soft);
  font-weight: 600;
}

/* botones de frecuencia */
.freq-btn {
  text-align: center;
  padding: 0.9rem 0.5rem;
  cursor: pointer;
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

/* colores de etiquetas */
.text-weekly {
  color: #2a5068;
}
.text-monthly {
  color: var(--vanilla-deep);
}

/* selector de dias */
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

/* botones de guardar y cancelar */
.form-action-btn {
  font-size: 1rem;
  font-weight: 700;
  min-height: 48px;
  padding: 0.7rem 1.5rem;
}

kbd {
  font-size: 0.78rem;
  background: var(--bg-subtle);
  border: 1px solid var(--vanilla-mid);
  border-radius: 4px;
  padding: 0.1rem 0.4rem;
  color: var(--cinnamon-dark);
  box-shadow: 0 1px 0 var(--vanilla-mid);
}
</style>
