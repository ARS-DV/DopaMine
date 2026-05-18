<script setup>
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import { useUserStore } from "@/stores/userStore";
import { rutaApi } from "@/config.js";

const userStore = useUserStore();
const router = useRouter();

//variables reactivas globales
const habits = ref([]);
const loading = ref(true);
const error = ref("");
const filter = ref("all");

//funcion principal para cargar los habitos del usuario
function fetchHabits() {
  loading.value = true;
  error.value = "";

  fetch(rutaApi + "?entity=habits&user_id=" + userStore.user.id)
    .then(function (res) {
      return res.json();
    })
    .then(function (data) {
      habits.value = data;
      loading.value = false;
    })
    .catch(function (err) {
      console.error(err);
      error.value = "Error loading habits";
      loading.value = false;
    });
}

//funcion auxiliar para el texto del estado
function doneLabel(val) {
  if (val == 2) {
    return "Done";
  } else if (val == 1) {
    return "Tried";
  } else {
    return "Pending";
  }
}

//funcion para ciclar el estado del habito: 0 -> 1 -> 2 -> 0
function cycleState(habit) {
  let currentState = habit.done_today;
  if (currentState == null || currentState == undefined) {
    currentState = 0;
  }

  let nextState = (parseInt(currentState) + 1) % 3;

  fetch(rutaApi + "?entity=habits&id=" + habit.id, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ done: nextState }),
  })
    .then(function (res) {
      return res.json();
    })
    .then(function (data) {
      if (data.status === "success") {
        habit.done_today = nextState;
        habit.streak = data.current_streak;
      }
    });
}

//funcion para borrar un habito
function deleteHabit(id) {
  let confirmation = confirm("Delete this habit?");
  if (confirmation === false) {
    return;
  }

  fetch(rutaApi + "?entity=habits&id=" + id, { method: "DELETE" })
    .then(function (res) {
      return res.json();
    })
    .then(function (data) {
      if (data.status === "success") {
        habits.value = habits.value.filter((h) => h.id !== id);
      } else {
        error.value = "Error deleting habit";
      }
    });
}

//filtros de la lista segun la opcion activa
const filteredHabits = computed(function () {
  if (filter.value == "daily") {
    return habits.value.filter((h) => h.frecuency == "daily");
  }
  if (filter.value == "weekly") {
    return habits.value.filter((h) => h.frecuency == "weekly");
  }
  if (filter.value == "monthly") {
    return habits.value.filter((h) => h.frecuency == "monthly");
  }
  if (filter.value == "done") {
    return habits.value.filter((h) => h.done_today == 2);
  }
  return habits.value;
});

//funcion para comprobar si el habito toca hoy
function isTodayScheduled(habit) {
  let today = new Date();
  let dayName = today
    .toLocaleString("en-US", { weekday: "long" })
    .toLowerCase();
  let dayOfMonth = today.getDate();

  if (habit.frecuency == "daily") {
    return true;
  }
  if (habit.frecuency == "weekly") {
    return habit.days && habit.days.includes(dayName);
  }
  if (habit.frecuency == "monthly") {
    return parseInt(habit.dayOfMonth) === dayOfMonth;
  }
  return false;
}

//cuando carga la vista se dispara la carga de habitos
onMounted(function () {
  fetchHabits();
});
</script>

<template>
  <div class="habits-container">
    <!-- cabecera con titulo y boton de nuevo habito -->
    <div class="d-flex align-items-end justify-content-between mb-4 fade-up">
      <h1 class="page-title">
        <em>build your</em>
        Habits
      </h1>
      <button
        class="btn-dopamine btn-dopamine-primary"
        aria-label="Create new habit"
        @click="router.push('/habits/new')"
      >
        <i class="bi bi-plus me-1" aria-hidden="true"></i> New Habit
      </button>
    </div>

    <!-- mensaje de error -->
    <div v-if="error" class="error-text mb-3" role="alert">
      <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i
      >{{ error }}
    </div>

    <!-- spinner de carga -->
    <div v-if="loading" class="loading-text" aria-live="polite">
      <div class="spinner-border spinner-border-sm me-2" role="status">
        <span class="visually-hidden">Loading habits...</span>
      </div>
      Loading...
    </div>

    <template v-else>
      <!-- estadisticas de habitos del dia -->
      <div class="row g-3 mb-4 fade-up delay-1" aria-label="Habit statistics">
        <div class="col-6 col-md-3">
          <div class="stat-strip strip-neutral">
            <div>
              <div class="stat-num">{{ habits.length }}</div>
              <div class="stat-label">Total habits</div>
            </div>
            <i class="bi bi-arrow-repeat stat-icon ms-3" aria-hidden="true"></i>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-strip strip-ok">
            <div>
              <div class="stat-num">
                {{ habits.filter((h) => h.done_today == 2).length }}
              </div>
              <div class="stat-label">Done today</div>
            </div>
            <i class="bi bi-check-circle stat-icon ms-3" aria-hidden="true"></i>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-strip strip-warn">
            <div>
              <div class="stat-num">
                {{ habits.filter((h) => h.done_today == 1).length }}
              </div>
              <div class="stat-label">Tried today</div>
            </div>
            <i class="bi bi-dash-circle stat-icon ms-3" aria-hidden="true"></i>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-strip strip-info">
            <div>
              <div class="stat-num">
                {{ Math.max(...habits.map((h) => h.streak || 0), 0) }}
              </div>
              <div class="stat-label">Best streak</div>
            </div>
            <i class="bi bi-fire stat-icon ms-3" aria-hidden="true"></i>
          </div>
        </div>
      </div>

      <!-- botones de filtro por frecuencia -->
      <div
        class="d-flex flex-wrap gap-2 mb-4 fade-up delay-2"
        role="group"
        aria-label="Filter habits"
      >
        <button
          class="filter-tab"
          :class="filter === 'all' ? 'active' : ''"
          @click="filter = 'all'"
        >
          All ({{ habits.length }})
        </button>
        <button
          class="filter-tab"
          :class="filter === 'daily' ? 'active' : ''"
          @click="filter = 'daily'"
        >
          Daily
        </button>
        <button
          class="filter-tab"
          :class="filter === 'weekly' ? 'active' : ''"
          @click="filter = 'weekly'"
        >
          Weekly
        </button>
        <button
          class="filter-tab"
          :class="filter === 'monthly' ? 'active' : ''"
          @click="filter = 'monthly'"
        >
          Monthly
        </button>
        <button
          class="filter-tab"
          :class="filter === 'done' ? 'active' : ''"
          @click="filter = 'done'"
        >
          Done today
        </button>
      </div>

      <!-- lista vacia -->
      <div v-if="filteredHabits.length == 0" class="empty-state fade-up">
        <i class="bi bi-arrow-repeat empty-icon" aria-hidden="true"></i>
        <p class="empty-title">No habits found</p>
        <button
          class="btn-dopamine btn-dopamine-primary mt-2"
          @click="router.push('/habits/new')"
        >
          <i class="bi bi-plus me-1" aria-hidden="true"></i> Create your first
          habit
        </button>
      </div>

      <!-- lista de habitos -->
      <div v-else class="d-flex flex-column gap-3" role="list">
        <article
          v-for="(habit, index) in filteredHabits"
          :key="habit.id"
          class="habit-card fade-up"
          :class="
            habit.done_today == 2
              ? 'card-done'
              : habit.done_today == 1
                ? 'card-tried'
                : habit.frecuency === 'weekly'
                  ? 'freq-weekly'
                  : habit.frecuency === 'monthly'
                    ? 'freq-monthly'
                    : 'freq-daily'
          "
          :style="{ animationDelay: index * 0.04 + 's' }"
          role="listitem"
        >
          <div class="habit-card-body">
            <!-- izquierda: boton de estado y datos del habito -->
            <div class="d-flex align-items-center gap-3 flex-grow-1">
              <!-- boton circular para ciclar el estado -->
              <button
                class="habit-state-btn"
                :class="
                  habit.done_today == 2
                    ? 'state-done'
                    : habit.done_today == 1
                      ? 'state-tried'
                      : 'state-pending'
                "
                :disabled="!isTodayScheduled(habit)"
                :aria-label="
                  'Mark ' +
                  habit.title +
                  ' as ' +
                  (habit.done_today == 2
                    ? 'pending'
                    : habit.done_today == 1
                      ? 'done'
                      : 'tried')
                "
                :aria-pressed="habit.done_today != 0"
                @click="cycleState(habit)"
              >
                <i
                  class="bi"
                  :class="
                    habit.done_today == 2
                      ? 'bi-check-circle-fill'
                      : habit.done_today == 1
                        ? 'bi-dash-circle'
                        : 'bi-circle'
                  "
                  aria-hidden="true"
                ></i>
              </button>

              <!-- informacion del habito: titulo, badges y frecuencia -->
              <div class="flex-grow-1" style="min-width: 0">
                <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                  <span class="habit-title">
                    <span v-if="habit.icon">{{ habit.icon }} </span
                    >{{ habit.title }}
                  </span>
                  <span
                    class="bdg"
                    :class="
                      habit.frecuency === 'daily'
                        ? 'bdg-daily'
                        : habit.frecuency === 'weekly'
                          ? 'bdg-weekly'
                          : 'bdg-monthly'
                    "
                  >
                    {{ habit.frecuency }}
                  </span>
                  <span
                    class="bdg"
                    :class="
                      habit.done_today == 2
                        ? 'bdg-done'
                        : habit.done_today == 1
                          ? 'bdg-tried'
                          : 'bdg-daily'
                    "
                  >
                    {{ doneLabel(habit.done_today) }}
                  </span>
                  <span
                    v-if="isTodayScheduled(habit) == false"
                    class="bdg bdg-info"
                  >
                    <i class="bi bi-calendar-x me-1" aria-hidden="true"></i>Not
                    today
                  </span>
                </div>

                <!-- dias de la semana si es semanal -->
                <div
                  v-if="habit.days && habit.days.length"
                  class="habit-meta habit-meta-weekly"
                >
                  <i class="bi bi-calendar-week me-1" aria-hidden="true"></i>
                  {{ habit.days.join(", ") }}
                </div>

                <!-- dia del mes si es mensual -->
                <div
                  v-if="habit.frecuency === 'monthly'"
                  class="habit-meta habit-meta-monthly"
                >
                  <i class="bi bi-calendar-event me-1" aria-hidden="true"></i>
                  Day {{ habit.dayOfMonth }} of each month
                </div>
              </div>
            </div>

            <!-- derecha: racha, boton editar y boton borrar -->
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
              <div
                class="habit-streak text-center"
                :aria-label="'Streak: ' + (habit.streak || 0) + ' days'"
              >
                <div class="habit-streak-num">{{ habit.streak || 0 }}</div>
                <div class="habit-streak-label">streak</div>
              </div>
              <button
                class="btn-dopamine btn-dopamine-ghost habit-icon-btn"
                :aria-label="'Edit habit: ' + habit.title"
                @click="router.push('/habits/edit/' + habit.id)"
              >
                <i class="bi bi-pencil" aria-hidden="true"></i>
              </button>
              <button
                class="btn-dopamine btn-dopamine-danger habit-icon-btn"
                :aria-label="'Delete habit: ' + habit.title"
                @click="deleteHabit(habit.id)"
              >
                <i class="bi bi-trash" aria-hidden="true"></i>
              </button>
            </div>
          </div>
        </article>
      </div>
    </template>

    <!-- boton flotante de nuevo habito -->
    <button
      class="fab"
      aria-label="Create new habit"
      @click="router.push('/habits/new')"
    >
      <i class="bi bi-plus" aria-hidden="true"></i>
    </button>
  </div>
</template>

<style scoped>
/* contenedor general de la vista */
.habits-container {
  width: 100%;
  padding: 2.5rem 3rem 5rem;
  font-family: "Atkinson Hyperlegible", sans-serif;
}

@media (max-width: 768px) {
  .habits-container {
    padding: 1.5rem 1rem 5rem;
  }
}

/* tarjeta de habito */
.habit-card {
  border-radius: 12px;
  border: 1.5px solid var(--vanilla-mid);
  border-left: 4px solid var(--vanilla-mid);
  background: var(--bg-base);
  box-shadow: 0 2px 8px rgba(92, 51, 23, 0.07);
  transition: box-shadow 0.15s;
}

.habit-card:hover {
  box-shadow: 0 4px 16px rgba(92, 51, 23, 0.11);
}

/* colores segun estado */
.card-done {
  border-left-color: var(--state-ok);
  background: var(--state-ok-bg);
}
.card-tried {
  border-left-color: var(--state-warn);
  background: var(--state-warn-bg);
}

.habit-card-body {
  padding: 1.1rem 1.3rem;
  display: flex;
  align-items: center;
  gap: 1rem;
}

/* titulo del habito */
.habit-title {
  font-family: "Atkinson Hyperlegible", sans-serif;
  font-size: 1rem;
  font-weight: 700;
  color: var(--cinnamon-dark);
}

/* metadatos de frecuencia */
.habit-meta {
  font-family: "Atkinson Hyperlegible", sans-serif;
  font-size: 0.9rem;
  color: var(--cinnamon-soft);
  margin-top: 0.25rem;
  display: flex;
  align-items: center;
  font-weight: 600;
  gap: 0.2rem;
}

/* color azul para habitos semanales */
.habit-meta-weekly {
  color: #2a5068;
}

/* color dorado para habitos mensuales */
.habit-meta-monthly {
  color: var(--vanilla-deep);
}

/* badges mas grandes y visibles */
:deep(.bdg),
.bdg-override {
  font-size: 0.72rem !important;
  padding: 0.2rem 0.6rem !important;
}

/* boton circular de estado */
.habit-state-btn {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  border: 2px solid var(--vanilla-mid);
  background: transparent;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s;
  font-size: 1.5rem;
  flex-shrink: 0;
}

.habit-state-btn:hover:not(:disabled) {
  border-color: var(--cinnamon-mid);
  transform: scale(1.05);
}

.habit-state-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
.habit-state-btn:focus-visible {
  outline: 3px solid var(--cinnamon-mid);
  outline-offset: 3px;
}

.state-pending {
  color: var(--vanilla-mid);
}
.state-tried {
  color: var(--state-warn);
  border-color: var(--state-warn);
  background: var(--state-warn-bg);
}
.state-done {
  color: var(--state-ok);
  border-color: var(--state-ok);
  background: var(--state-ok-bg);
}

/* numero y etiqueta de la racha */
.habit-streak-num {
  font-family: "Atkinson Hyperlegible", sans-serif;
  font-size: 1.4rem;
  font-weight: 700;
  color: var(--state-warn);
  line-height: 1;
}

.habit-streak-label {
  font-size: 0.6rem;
  color: var(--cinnamon-soft);
  text-transform: uppercase;
  letter-spacing: 1px;
}

/* botones de editar y borrar */
.habit-icon-btn {
  width: 44px;
  height: 44px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.95rem;
  border-radius: 10px;
}

.habit-icon-btn:focus-visible {
  outline: 3px solid var(--cinnamon-mid);
  outline-offset: 3px;
}
</style>
