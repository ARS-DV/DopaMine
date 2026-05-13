<script setup>
// imports para Vue, FullCalendar y API
import { ref, onMounted } from 'vue'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'
import { useUserStore } from '@/stores/userStore'
import { rutaApi } from '@/config.js'

const userStore = useUserStore()

// lista de eventos para el calendario
const loading        = ref(false)
const errorMessage   = ref('')

// evento seleccionado para el panel lateral
const selectedEvent = ref(null)

// configuracion de FullCalendar
const calendarOptions = ref({
  plugins:      [dayGridPlugin, timeGridPlugin, interactionPlugin],
  initialView:  'dayGridMonth',
  locale:       'en-GB',
  height:       'auto',
  headerToolbar: {
    left:   'prev,next today',
    center: 'title',
    right:  'dayGridMonth,timeGridWeek'
  },
  // ✅ events como función — FullCalendar pasa start y end automáticamente
  events: function(info, successCallback, failureCallback) {
    let start = info.startStr.split('T')[0]
    let end   = info.endStr.split('T')[0]
    let url   = rutaApi + "?entity=calendar&user_id=" + userStore.user.id
              + "&start=" + start + "&end=" + end

    fetch(url)
      .then(function(res) { return res.json() })
      .then(function(data) {
        successCallback(data)
        loading.value = false
      })
      .catch(function() {
        errorMessage.value = 'Error loading calendar'
        failureCallback()
        loading.value = false
      })
  },
  eventClick:   handleEventClick,
  eventDisplay: 'block',
  dayMaxEvents: 3,
})

// funcion para cargar los eventos del servidor


// funcion al hacer clic en un evento
function handleEventClick(info) {
  selectedEvent.value = {
    title:      info.event.title,
    start:      info.event.startStr,
    end:        info.event.endStr,
    type:       info.event.extendedProps.type       || '',
    difficulty: info.event.extendedProps.difficulty || '',
    frecuency:  info.event.extendedProps.frecuency  || '',
    descrip:    info.event.extendedProps.descrip    || '',
    color:      info.event.backgroundColor
  }
}

// funcion para cerrar panel lateral
function closePanel() {
  selectedEvent.value = null
}

// funcion para mostrar el tipo en español legible
function getTypeLabel(type) {
  if (type === 'task')    { return 'Task' }
  if (type === 'habit')   { return 'Habit' }
  if (type === 'routine') { return 'Routine' }
  return type
}

// icono segun tipo de evento
function getTypeIcon(type) {
  if (type === 'task')    { return 'bi-check2-square' }
  if (type === 'habit')   { return 'bi-arrow-repeat' }
  if (type === 'routine') { return 'bi-list-check' }
  return 'bi-calendar-event'
}


</script>

<template>
  <div class="calendar-container">

    <!-- CABECERA -->
    <div class="d-flex align-items-end justify-content-between mb-4 fade-up">
      <h1 class="page-title">
        <em>plan your</em>
        Calendar
      </h1>
      <!-- LEYENDA -->
      <div class="d-flex gap-3 align-items-center">
        <div class="legend-item">
          <span class="legend-dot dot-task"></span>
          <span class="legend-label">Tasks</span>
        </div>
        <div class="legend-item">
          <span class="legend-dot dot-habit"></span>
          <span class="legend-label">Habits</span>
        </div>
        <div class="legend-item">
          <span class="legend-dot dot-routine"></span>
          <span class="legend-label">Routines</span>
        </div>
      </div>
    </div>

    <!-- ERROR -->
    <div v-if="errorMessage" class="error-text mb-3">
      <i class="bi bi-exclamation-triangle me-2"></i>{{ errorMessage }}
    </div>

    <!-- LOADING -->
    <div v-if="loading" class="loading-text">
      <div class="spinner-border spinner-border-sm me-2" role="status"></div>
      Loading calendar...
    </div>

    <template v-else>
      <div class="row g-4">

        <!-- CALENDARIO -->
        <div :class="selectedEvent ? 'col-12 col-lg-8' : 'col-12'">
          <div class="calendar-card fade-up delay-1">
            <FullCalendar :options="calendarOptions" />
          </div>
        </div>

        <!-- PANEL LATERAL — evento seleccionado -->
        <div v-if="selectedEvent" class="col-12 col-lg-4 fade-up">
          <div class="event-panel">

            <!-- CABECERA DEL PANEL -->
            <div class="event-panel-header" :style="{ borderLeftColor: selectedEvent.color }">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="event-type-badge">
                  <i class="bi me-1" :class="getTypeIcon(selectedEvent.type)"></i>
                  {{ getTypeLabel(selectedEvent.type) }}
                </span>
                <button class="btn-dopamine btn-dopamine-ghost btn-close-panel" @click="closePanel">
                  <i class="bi bi-x"></i>
                </button>
              </div>
              <h2 class="event-panel-title">{{ selectedEvent.title }}</h2>
            </div>

            <!-- DETALLES -->
            <div class="event-panel-body">

              <div v-if="selectedEvent.start" class="event-detail-row">
                <i class="bi bi-calendar3 detail-icon"></i>
                <div>
                  <div class="detail-label">Date</div>
                  <div class="detail-value">{{ selectedEvent.start }}</div>
                </div>
              </div>

              <div v-if="selectedEvent.difficulty" class="event-detail-row">
                <i class="bi bi-bar-chart detail-icon"></i>
                <div>
                  <div class="detail-label">Difficulty</div>
                  <div class="detail-value">
                    <span class="bdg" :class="'bdg-' + selectedEvent.difficulty">
                      {{ selectedEvent.difficulty }}
                    </span>
                  </div>
                </div>
              </div>

              <div v-if="selectedEvent.frecuency" class="event-detail-row">
                <i class="bi bi-arrow-repeat detail-icon"></i>
                <div>
                  <div class="detail-label">Frequency</div>
                  <div class="detail-value">
                    <span class="bdg" :class="selectedEvent.frecuency === 'daily' ? 'bdg-daily' : selectedEvent.frecuency === 'weekly' ? 'bdg-weekly' : 'bdg-monthly'">
                      {{ selectedEvent.frecuency }}
                    </span>
                  </div>
                </div>
              </div>

              <div v-if="selectedEvent.descrip" class="event-detail-row">
                <i class="bi bi-text-left detail-icon"></i>
                <div>
                  <div class="detail-label">Description</div>
                  <div class="detail-value">{{ selectedEvent.descrip }}</div>
                </div>
              </div>

            </div>
          </div>
        </div>

      </div>
    </template>

  </div>
</template>

<style scoped>
/* CONTENEDOR */
.calendar-container {
  width: 100%;
  padding: 2.5rem 3rem 5rem;
  font-family: 'Atkinson Hyperlegible', var(--font-mono), sans-serif;
}

@media (max-width: 768px) {
  .calendar-container { padding: 1.5rem 1rem 4rem; }
}

/* LEYENDA */
.legend-item {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.legend-dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  flex-shrink: 0;
}

.dot-task    { background: var(--state-error); }
.dot-habit   { background: var(--btn-info); }
.dot-routine { background: var(--state-ok); }

.legend-label {
  font-size: 0.85rem;
  color: var(--cinnamon-mid);
  font-family: 'Atkinson Hyperlegible', sans-serif;
}

/* CARD DEL CALENDARIO */
.calendar-card {
  background: var(--bg-card);
  border: 1.5px solid var(--vanilla-mid);
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 2px 12px rgba(92, 51, 23, 0.08);
}

/* ── OVERRIDE FULLCALENDAR ── */

/* Tipografía accesible en todo el calendario */
:deep(.fc) {
  font-family: 'Atkinson Hyperlegible', sans-serif !important;
  font-size: 1rem !important;
}

/* Cabecera del calendario (mes, botones prev/next) */
:deep(.fc-toolbar-title) {
  font-family: 'Atkinson Hyperlegible', sans-serif !important;
  font-size: 1.3rem !important;
  font-weight: 700 !important;
  color: var(--cinnamon-dark) !important;
}

/* Botones prev/next/today/vistas */
:deep(.fc-button) {
  background: var(--btn-primary) !important;
  border-color: var(--btn-primary) !important;
  font-family: 'Atkinson Hyperlegible', sans-serif !important;
  font-size: 0.9rem !important;
  padding: 0.5rem 0.9rem !important;
  border-radius: 8px !important;
  min-height: 44px !important;
}

:deep(.fc-button:hover) {
  background: var(--btn-primary-h) !important;
  border-color: var(--btn-primary-h) !important;
}

:deep(.fc-button-active) {
  background: var(--cinnamon-mid) !important;
  border-color: var(--cinnamon-mid) !important;
}

/* Nombres de los días (Mon, Tue...) */
:deep(.fc-col-header-cell-cushion) {
  font-family: 'Atkinson Hyperlegible', sans-serif !important;
  font-size: 0.9rem !important;
  font-weight: 700 !important;
  color: var(--cinnamon-mid) !important;
  padding: 0.5rem 0 !important;
  text-decoration: none !important;
}

/* Números de día */
:deep(.fc-daygrid-day-number) {
  font-family: 'Atkinson Hyperlegible', sans-serif !important;
  font-size: 0.95rem !important;
  font-weight: 700 !important;
  color: var(--cinnamon-dark) !important;
  text-decoration: none !important;
  padding: 0.4rem !important;
}

/* Día actual resaltado */
:deep(.fc-day-today) {
  background: var(--vanilla-light) !important;
  opacity: 1 !important;
}

:deep(.fc-day-today .fc-daygrid-day-number) {
  background: var(--cinnamon-dark) !important;
  color: var(--bg-base) !important;
  border-radius: 50% !important;
  width: 32px !important;
  height: 32px !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
}

/* Eventos en el calendario */
:deep(.fc-event) {
  border-radius: 6px !important;
  border: none !important;
  padding: 0.15rem 0.4rem !important;
  font-size: 0.82rem !important;
  font-family: 'Atkinson Hyperlegible', sans-serif !important;
  font-weight: 600 !important;
  cursor: pointer !important;
}

:deep(.fc-event:hover) {
  filter: brightness(0.9) !important;
}

/* Texto "more" cuando hay muchos eventos */
:deep(.fc-daygrid-more-link) {
  font-size: 0.8rem !important;
  color: var(--cinnamon-mid) !important;
  font-family: 'Atkinson Hyperlegible', sans-serif !important;
}

/* Separadores de celdas */
:deep(.fc-scrollgrid) {
  border-color: var(--vanilla-mid) !important;
}

:deep(.fc-scrollgrid td),
:deep(.fc-scrollgrid th) {
  border-color: var(--vanilla-light) !important;
}

/* ── PANEL LATERAL ── */
.event-panel {
  background: var(--bg-card);
  border: 1.5px solid var(--vanilla-mid);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(92, 51, 23, 0.08);
  position: sticky;
  top: 80px;
}

.event-panel-header {
  padding: 1.2rem 1.4rem;
  border-left: 5px solid var(--vanilla-mid);
  background: var(--bg-base);
}

.event-type-badge {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--cinnamon-soft);
  text-transform: uppercase;
  letter-spacing: 1px;
}

.event-panel-title {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 1.2rem;
  font-weight: 700;
  color: var(--cinnamon-dark);
  margin: 0.4rem 0 0;
  line-height: 1.3;
}

.btn-close-panel {
  width: 32px;
  height: 32px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
}

.event-panel-body {
  padding: 1rem 1.4rem 1.4rem;
}

.event-detail-row {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.75rem 0;
  border-bottom: 1px solid var(--bg-subtle);
}

.event-detail-row:last-child {
  border-bottom: none;
}

.detail-icon {
  font-size: 1.1rem;
  color: var(--cinnamon-soft);
  margin-top: 2px;
  flex-shrink: 0;
}

.detail-label {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.72rem;
  color: var(--cinnamon-soft);
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-bottom: 0.2rem;
}

.detail-value {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--cinnamon-dark);
}
</style>