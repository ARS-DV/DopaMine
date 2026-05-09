<script setup>
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import { useUserStore } from "@/stores/userStore";
import { rutaApi } from "@/config.js";

const userStore = useUserStore();
const router = useRouter();

// ── ESTADO ───────────────────────────────────────────────────
const habits = ref([]);
const loading = ref(true);
const error = ref("");
const filter = ref("all");

// ── API CALLS ─────────────────────────────────────────────────

async function fetchHabits() {
  loading.value = true;
  error.value = "";
  try {
    const res = await fetch(
      `${rutaApi}?entity=habits&user_id=${userStore.user.id}`,
    );
    const data = await res.json();
    habits.value = data;
  } catch (e) {
    error.value = "Error loading habits";
  } finally {
    loading.value = false;
  }
}

async function toggleDone(habit) {
  const newDone = habit.done_today ? 0 : 1;

  const res = await fetch(`${rutaApi}?entity=habits&id=${habit.id}`, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ done: newDone }),
  });
  const data = await res.json();

  if (data.status === "success") {
    habit.done_today = !!newDone;
  }
}
const doneLabel = (val) => {
  if (val == 2) return "✓ Done";
  if (val == 1) return "~ Tried";
  return "○ Pending";
};

// Cicla entre 0 → 1 → 2 → 0
async function cycleState(habit) {
  const next = (parseInt(habit.done_today ?? 0) + 1) % 3;

  const res = await fetch(`${rutaApi}?entity=habits&id=${habit.id}`, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ done: next }),
  });
  const data = await res.json();

  if (data.status === "success") {
    habit.done_today = next;
    habit.streak = data.current_streak;
  }
}

async function deleteHabit(id) {
  if (!confirm("Delete this habit?")) return;

  const res = await fetch(`${rutaApi}?entity=habits&id=${id}`, {
    method: "DELETE",
  });
  const data = await res.json();

  if (data.status === "success") {
    habits.value = habits.value.filter((h) => h.id !== id);
  } else {
    error.value = "Error deleting habit";
  }
}

// ── FILTROS ───────────────────────────────────────────────────

const filteredHabits = computed(() => {
  switch (filter.value) {
    case "daily":
      return habits.value.filter((h) => h.frecuency === "daily");
    case "weekly":
      return habits.value.filter((h) => h.frecuency === "weekly");
    case "monthly":
      return habits.value.filter((h) => h.frecuency === "monthly");
    case "done":
      return habits.value.filter((h) => h.done_today);
    default:
      return habits.value;
  }
});
function isTodayScheduled(habit) {
  const today = new Date();
  const dayName = today
    .toLocaleString("en-US", { weekday: "long" })
    .toLowerCase();
  const dayOfMonth = today.getDate();

  if (habit.frecuency === "daily") return true;

  if (habit.frecuency === "weekly") {
    return habit.days && habit.days.includes(dayName);
  }

  if (habit.frecuency === "monthly") {
    return parseInt(habit.dayOfMonth) === dayOfMonth;
  }

  return false;
}

// ── LIFECYCLE ─────────────────────────────────────────────────

onMounted(() => {
  fetchHabits();
});
</script>

<template>
  <div>
    <h1>Habits</h1>

    <!-- ERROR -->
    <p v-if="error">{{ error }}</p>

    <!-- LOADING -->
    <p v-if="loading">Loading...</p>

    <template v-else>
      <!-- BOTÓN IR A CREAR -->
      <button @click="router.push('/habits/new')">+ New Habit</button>

      <!-- FILTROS -->
      <div>
        <button @click="filter = 'all'">All ({{ habits.length }})</button>
        <button @click="filter = 'daily'">Daily</button>
        <button @click="filter = 'weekly'">Weekly</button>
        <button @click="filter = 'monthly'">Monthly</button>
        <button @click="filter = 'done'">Done today</button>
      </div>

      <!-- LISTA VACÍA -->
      <p v-if="filteredHabits.length === 0">No habits found</p>

      <!-- LISTA DE HÁBITOS -->
      <ul v-else>
        <li v-for="habit in filteredHabits" :key="habit.id">
          <button @click="cycleState(habit)">
            {{ doneLabel(habit.done_today) }}
          </button>
          <span v-if="!isTodayScheduled(habit)">(not today)</span>

          <span>{{ habit.icon }} {{ habit.title }}</span>
          <span>{{ habit.frecuency }}</span>

          <span v-if="habit.days && habit.days.length">
            ({{ habit.days.join(", ") }})
          </span>

          <span v-if="habit.frecuency === 'monthly'">
            day {{ habit.dayOfMonth }}
          </span>

          <span>🔥 {{ habit.streak }} streak</span>
          <span>Total: {{ habit.total_done }}</span>
          <span v-if="habit.done_today">✓ Done today</span>

          <button @click="deleteHabit(habit.id)">Delete</button>
        </li>
      </ul>
    </template>
  </div>
</template>
