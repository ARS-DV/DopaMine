<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useUserStore } from "@/stores/userStore";
import { rutaApi } from "@/config.js";

const userStore = useUserStore();
const router = useRouter();

// ── ESTADO ───────────────────────────────────────────────────
const error = ref("");
const dias = [
  "monday",
  "tuesday",
  "wednesday",
  "thursday",
  "friday",
  "saturday",
  "sunday",
];

const form = ref({
  title: "",
  descrip: "",
  icon: "",
  frecuency: "daily",
  dayOfMonth: null,
  days: [],
});

// ── HELPERS ───────────────────────────────────────────────────

function toggleDay(day) {
  const idx = form.value.days.indexOf(day);
  if (idx === -1) {
    form.value.days.push(day);
  } else {
    form.value.days.splice(idx, 1);
  }
}
// Añade este array
const emojis = [
  "🔄",
  "💧",
  "🏃",
  "📚",
  "🧘",
  "🥗",
  "💊",
  "🛌",
  "🧹",
  "💼",
  "✍️",
  "🎯",
  "🚴",
  "🏋️",
  "🎨",
  "🎸",
  "🌿",
  "🐾",
  "☀️",
  "🌙",
];

// ── API CALL ─────────────────────────────────────────────────

async function createHabit() {
  error.value = "";

  if (!form.value.title.trim()) {
    error.value = "Title is required";
    return;
  }

  if (form.value.frecuency === "weekly" && form.value.days.length === 0) {
    error.value = "Select at least one day";
    return;
  }

  if (form.value.frecuency === "monthly" && !form.value.dayOfMonth) {
    error.value = "Day of month is required";
    return;
  }

  const body = {
    user_id: userStore.user.id,
    title: form.value.title,
    descrip: form.value.descrip,
    icon: form.value.icon,
    frecuency: form.value.frecuency,
    dayOfMonth:
      form.value.frecuency === "monthly" ? form.value.dayOfMonth : null,
    days: form.value.frecuency === "weekly" ? form.value.days : [],
  };

  const res = await fetch(`${rutaApi}?entity=habits`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(body),
  });
  const data = await res.json();

  if (data.status === "success") {
    router.push("/habits");
  } else {
    error.value = "Error creating habit";
  }
}
</script>

<template>
  <div>
    <!-- PAN DE MIGA -->
    <nav>
      <RouterLink to="/">Home</RouterLink>
      <span> > </span>
      <RouterLink to="/habits">Habits</RouterLink>
      <span> > </span>
      <span>New Habit</span>
    </nav>

    <h1>New Habit</h1>

    <!-- ERROR -->
    <p v-if="error">{{ error }}</p>

    <!-- FORMULARIO -->
    <form @submit.prevent="createHabit">
      <div>
        <label>Title *</label>
        <input v-model="form.title" type="text" placeholder="Habit name" />
      </div>

      <div>
        <label>Description</label>
        <input
          v-model="form.descrip"
          type="text"
          placeholder="Optional description"
        />
      </div>

      <div>
        <label>Icon</label>
        <div>
          <button
            v-for="emoji in emojis"
            :key="emoji"
            type="button"
            :style="form.icon === emoji ? 'outline: 2px solid black' : ''"
            @click="form.icon = emoji"
          >
            {{ emoji }}
          </button>
        </div>
        <span>Selected: {{ form.icon || "none" }}</span>
      </div>

      <div>
        <label>Frequency</label>
        <select v-model="form.frecuency">
          <option value="daily">Daily</option>
          <option value="weekly">Weekly</option>
          <option value="monthly">Monthly</option>
        </select>
      </div>

      <!-- Días específicos si es semanal -->
      <div v-if="form.frecuency === 'weekly'">
        <label>Days</label>
        <div>
          <label v-for="day in dias" :key="day">
            <input
              type="checkbox"
              :value="day"
              :checked="form.days.includes(day)"
              @change="toggleDay(day)"
            />
            {{ day }}
          </label>
        </div>
      </div>

      <!-- Día del mes si es mensual -->
      <div v-if="form.frecuency === 'monthly'">
        <label>Day of month (1-31)</label>
        <input
          v-model.number="form.dayOfMonth"
          type="number"
          min="1"
          max="31"
        />
      </div>

      <button type="submit">Create Habit</button>
      <button type="button" @click="router.push('/habits')">Cancel</button>
    </form>
  </div>
</template>
