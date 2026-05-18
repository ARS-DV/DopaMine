<script setup>
import { ref, onMounted, nextTick } from "vue";
import { Chart, registerables } from "chart.js";
import { useUserStore } from "@/stores/userStore";
import { rutaApi } from "@/config.js";

//registrar todos los modulos de chart.js
Chart.register(...registerables);

const userStore = useUserStore();

//variables reactivas globales
const loading = ref(true);
const errorMessage = ref("");
const statsData = ref(null);

//referencias a los canvas de los graficos
const habitsChartRef = ref(null);
const tasksChartRef = ref(null);
const routinesChartRef = ref(null);

//instancias guardadas para poder destruirlos si se recargan
let habitsChart = null;
let tasksChart = null;
let routinesChart = null;

//funcion principal para cargar los datos de estadisticas
function loadProgressData() {
  loading.value = true;
  errorMessage.value = "";

  let url = rutaApi + "?entity=records&user_id=" + userStore.user.id;

  fetch(url)
    .then(function (res) {
      return res.json();
    })
    .then(function (data) {
      statsData.value = data;
      loading.value = false;
      //esperamos a que Vue actualice el DOM antes de dibujar los graficos
      nextTick(function () {
        buildCharts();
      });
    })
    .catch(function () {
      errorMessage.value = "Error loading progress data";
      loading.value = false;
    });
}

//funcion que construye los tres graficos con chart.js
function buildCharts() {
  if (!statsData.value) {
    return;
  }

  let data = statsData.value;

  //destruimos los graficos anteriores si ya existian
  if (habitsChart) { habitsChart.destroy(); }
  if (tasksChart) { tasksChart.destroy(); }
  if (routinesChart) { routinesChart.destroy(); }

  //colores de la paleta de la app
  let colorDone  = "#7A9E7E";
  let colorTried = "#C9A030";
  let colorFail  = "#E8E0D8";

  //grafico de barras apiladas para habitos (done / tried / pending por dia)
  if (habitsChartRef.value && data.habits) {
    habitsChart = new Chart(habitsChartRef.value, {
      type: "bar",
      data: {
        labels: data.habits.labels,
        datasets: [
          { label: "Done",    data: data.habits.done,    backgroundColor: colorDone,  borderRadius: 4 },
          { label: "Tried",   data: data.habits.tried,   backgroundColor: colorTried, borderRadius: 4 },
          { label: "Pending", data: data.habits.pending, backgroundColor: colorFail,  borderRadius: 4 },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            labels: { font: { family: "Atkinson Hyperlegible", size: 13 }, color: "#5C3317" },
          },
        },
        scales: {
          x: { stacked: true, ticks: { font: { family: "Atkinson Hyperlegible", size: 12 }, color: "#8B5E3C" }, grid: { color: "#F3EDE3" } },
          y: { stacked: true, ticks: { font: { family: "Atkinson Hyperlegible", size: 12 }, color: "#8B5E3C" }, grid: { color: "#F3EDE3" } },
        },
      },
    });
  }

  //grafico de dona para tareas (a tiempo / tarde / pendiente)
  if (tasksChartRef.value && data.tasks) {
    tasksChart = new Chart(tasksChartRef.value, {
      type: "doughnut",
      data: {
        labels: ["Done on time", "Done late", "Pending"],
        datasets: [{
          data: [data.tasks.on_time, data.tasks.late, data.tasks.pending],
          backgroundColor: [colorDone, colorTried, colorFail],
          borderWidth: 0,
          hoverOffset: 6,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: "65%",
        plugins: {
          legend: {
            position: "bottom",
            labels: { font: { family: "Atkinson Hyperlegible", size: 13 }, color: "#5C3317", padding: 16 },
          },
        },
      },
    });
  }

  //grafico de linea para rutinas (porcentaje de completitud por semana)
  if (routinesChartRef.value && data.routines) {
    routinesChart = new Chart(routinesChartRef.value, {
      type: "line",
      data: {
        labels: data.routines.labels,
        datasets: [{
          label: "Completion %",
          data: data.routines.completion,
          borderColor: "#5C3317",
          backgroundColor: "rgba(92, 51, 23, 0.08)",
          borderWidth: 2.5,
          pointBackgroundColor: "#5C3317",
          pointRadius: 5,
          tension: 0.3,
          fill: true,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            labels: { font: { family: "Atkinson Hyperlegible", size: 13 }, color: "#5C3317" },
          },
        },
        scales: {
          x: { ticks: { font: { family: "Atkinson Hyperlegible", size: 12 }, color: "#8B5E3C" }, grid: { color: "#F3EDE3" } },
          y: {
            min: 0,
            max: 100,
            ticks: {
              font: { family: "Atkinson Hyperlegible", size: 12 },
              color: "#8B5E3C",
              callback: function (val) { return val + "%"; },
            },
            grid: { color: "#F3EDE3" },
          },
        },
      },
    });
  }
}

//cuando carga la vista, se dispara la carga de datos
onMounted(function () {
  loadProgressData();
});
</script>

<template>
  <div class="progress-container">

    <!-- cabecera con mes actual -->
    <div class="d-flex align-items-end justify-content-between mb-4 fade-up">
      <h1 class="page-title"><em>track your</em> Progress</h1>
      <span class="month-label">
        <i class="bi bi-calendar3 me-2"></i>
        {{ new Date().toLocaleString("en-GB", { month: "long", year: "numeric" }) }}
      </span>
    </div>

    <!-- mensaje de error -->
    <div v-if="errorMessage" class="error-text mb-3" role="alert">
      <i class="bi bi-exclamation-triangle me-2"></i>{{ errorMessage }}
    </div>

    <!-- spinner de carga -->
    <div v-if="loading" class="loading-text">
      <div class="spinner-border spinner-border-sm me-2" role="status"></div>
      Loading your progress...
    </div>

    <template v-else-if="statsData">

      <!-- resumen mensual en numeros -->
      <div class="row g-3 mb-5 fade-up delay-1">
        <div class="col-6 col-md-3">
          <div class="stat-strip strip-ok">
            <div>
              <div class="stat-num">{{ statsData.summary?.habits_done ?? 0 }}</div>
              <div class="stat-label">Habits done</div>
            </div>
            <i class="bi bi-arrow-repeat stat-icon ms-3"></i>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-strip strip-info">
            <div>
              <div class="stat-num">{{ statsData.summary?.tasks_done ?? 0 }}</div>
              <div class="stat-label">Tasks done</div>
            </div>
            <i class="bi bi-check2-square stat-icon ms-3"></i>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-strip strip-warn">
            <div>
              <div class="stat-num">{{ statsData.summary?.best_streak ?? 0 }}</div>
              <div class="stat-label">Best streak</div>
            </div>
            <i class="bi bi-fire stat-icon ms-3"></i>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-strip strip-neutral">
            <div>
              <div class="stat-num">{{ statsData.summary?.routines_done ?? 0 }}</div>
              <div class="stat-label">Routines done</div>
            </div>
            <i class="bi bi-list-check stat-icon ms-3"></i>
          </div>
        </div>
      </div>

      <!-- grafico de barras de habitos -->
      <div class="chart-card mb-4 fade-up delay-2">
        <div class="chart-card-header">
          <h2 class="chart-title">
            <i class="bi bi-arrow-repeat me-2"></i>Habits this month
          </h2>
          <p class="chart-sub">Daily completion by day</p>
        </div>
        <div class="chart-wrapper">
          <canvas ref="habitsChartRef"></canvas>
        </div>
      </div>

      <!-- fila con grafico de dona de tareas y linea de rutinas -->
      <div class="row g-4 mb-4">

        <!-- grafico de dona de tareas -->
        <div class="col-12 col-md-5 fade-up delay-3">
          <div class="chart-card h-100">
            <div class="chart-card-header">
              <h2 class="chart-title">
                <i class="bi bi-check2-square me-2"></i>Tasks this month
              </h2>
              <p class="chart-sub">Completion breakdown</p>
            </div>
            <div class="chart-wrapper-sm">
              <canvas ref="tasksChartRef"></canvas>
            </div>
          </div>
        </div>

        <!-- grafico de linea de rutinas -->
        <div class="col-12 col-md-7 fade-up delay-4">
          <div class="chart-card h-100">
            <div class="chart-card-header">
              <h2 class="chart-title">
                <i class="bi bi-list-check me-2"></i>Routines this month
              </h2>
              <p class="chart-sub">Weekly completion percentage</p>
            </div>
            <div class="chart-wrapper">
              <canvas ref="routinesChartRef"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- ranking de rachas si hay datos -->
      <div v-if="statsData.streaks && statsData.streaks.length > 0" class="chart-card fade-up delay-5">
        <div class="chart-card-header">
          <h2 class="chart-title">
            <i class="bi bi-fire me-2"></i>Top streaks
          </h2>
          <p class="chart-sub">Your best habits by streak</p>
        </div>

        <ol class="streaks-list">
          <li
            v-for="(item, index) in statsData.streaks"
            :key="item.id"
            class="streak-row"
            :class="index === 0 ? 'streak-first' : ''"
          >
            <span class="streak-position">{{ index + 1 }}</span>
            <span class="streak-name flex-grow-1">{{ item.title }}</span>
            <div class="d-flex gap-3 align-items-center">
              <div class="streak-stat">
                <div class="streak-stat-num">{{ item.current_streak }}</div>
                <div class="streak-stat-label">current</div>
              </div>
              <div class="streak-divider"></div>
              <div class="streak-stat">
                <div class="streak-stat-num text-warn">{{ item.best_streak }}</div>
                <div class="streak-stat-label">best</div>
              </div>
            </div>
          </li>
        </ol>
      </div>

    </template>

    <!-- estado vacio cuando no hay datos -->
    <div v-else class="empty-state fade-up">
      <i class="bi bi-bar-chart empty-icon"></i>
      <p class="empty-title">No data yet</p>
      <p class="empty-sub">Complete some habits or tasks to see your progress here</p>
    </div>

  </div>
</template>

<style scoped>
/* contenedor general */
.progress-container {
  width: 100%;
  padding: 2.5rem 3rem 5rem;
  font-family: "Atkinson Hyperlegible", sans-serif;
}

@media (max-width: 768px) {
  .progress-container { padding: 1.5rem 1rem 4rem; }
}

/* etiqueta del mes en la cabecera */
.month-label {
  font-size: 0.9rem;
  color: var(--cinnamon-soft);
  font-weight: 600;
}

/* tarjeta contenedora de cada grafico */
.chart-card {
  background: var(--bg-card);
  border: 1.5px solid var(--vanilla-mid);
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 2px 12px rgba(92, 51, 23, 0.07);
}

.chart-card-header { margin-bottom: 1.2rem; }

.chart-title {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--cinnamon-dark);
  margin: 0 0 0.2rem;
}

.chart-sub {
  font-size: 0.82rem;
  color: var(--cinnamon-soft);
  margin: 0;
}

/* altura de los contenedores de canvas */
.chart-wrapper    { position: relative; height: 260px; }
.chart-wrapper-sm { position: relative; height: 240px; }

/* lista de rachas como ol/li */
.streaks-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  list-style: none;
  padding: 0;
  margin: 0;
}

/* fila de cada habito en el ranking */
.streak-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.85rem 1rem;
  border-radius: 10px;
  background: var(--bg-base);
  border: 1px solid var(--vanilla-light);
  transition: background 0.15s;
}
.streak-row:hover { background: var(--bg-subtle); }

/* fila del primer puesto resaltada */
.streak-row.streak-first {
  background: var(--state-warn-bg);
  border-color: #d4a830;
}

/* numero de posicion */
.streak-position {
  font-size: 1rem;
  font-weight: 700;
  color: var(--cinnamon-soft);
  width: 24px;
  text-align: center;
  flex-shrink: 0;
}
.streak-row.streak-first .streak-position { color: var(--state-warn); }

/* nombre del habito */
.streak-name {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--cinnamon-dark);
}

/* columna de numeros de racha */
.streak-stat { text-align: center; min-width: 44px; }

.streak-stat-num {
  font-size: 1.2rem;
  font-weight: 700;
  color: var(--cinnamon-dark);
  line-height: 1;
}
.streak-stat-num.text-warn { color: var(--state-warn); }

.streak-stat-label {
  font-size: 0.6rem;
  color: var(--cinnamon-soft);
  text-transform: uppercase;
  letter-spacing: 1px;
}

/* separador entre racha actual y mejor racha */
.streak-divider {
  width: 1px;
  height: 30px;
  background: var(--vanilla-mid);
  flex-shrink: 0;
}

@media (max-width: 576px) {
  .streak-row { flex-wrap: wrap; gap: 0.5rem; }
}
</style>