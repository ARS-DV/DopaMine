import { createRouter, createWebHistory } from "vue-router";
import { useUserStore } from "@/stores/userStore";

import LandingView from "@/views/Landingview.vue";
import Login from "@/views/login.vue";
import Singup from "@/views/singup.vue";
import HomeView from "@/views/HomeView.vue";
import TasksView from "@/views/TasksView.vue";
import HabitsView from "@/views/HabitsView.vue";
import RoutinesView from "@/views/RoutinesView.vue";
import CalendarView from "@/views/CalendarView.vue";
import ProgressView from "@/views/ProgressView.vue";
import NewHabitView from "@/views/NewHabitView.vue";
import NewTaskView from "@/views/NewTaskView.vue";
import NewRoutineView from "@/views/NewRoutineView.vue";
import EditTaskView from "@/views/EditTaskView.vue";
import EditRoutineView from "@/views/EditRoutineView.vue";
import AdminView from "@/views/AdminView.vue";
import ProfileView from "@/views/ProfileView.vue";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    // rutas públicas
    { path: "/", component: LandingView, meta: { hideHeader: true } },
    { path: "/login", component: Login, meta: { hideHeader: true } },
    { path: "/singup", component: Singup, meta: { hideHeader: true } },

    // rutas protegidas
    { path: "/home", component: HomeView },
    { path: "/tasks", component: TasksView },
    { path: "/habits", component: HabitsView },
    { path: "/routines", component: RoutinesView },
    { path: "/calendar", component: CalendarView },
    { path: "/progress", component: ProgressView },
    { path: "/admin", component: AdminView },
    { path: "/profile", component: ProfileView },

    {
      path: "/habits/new",
      component: NewHabitView,
      meta: { hideHeader: true },
    },
    { path: "/tasks/new", component: NewTaskView, meta: { hideHeader: true } },
    {
      path: "/routines/new",
      component: NewRoutineView,
      meta: { hideHeader: true },
    },

    {
      path: "/tasks/edit/:id",
      component: EditTaskView,
      meta: { hideHeader: true },
    },
    {
      path: "/routines/edit/:id",
      component: EditRoutineView,
      meta: { hideHeader: true },
    },
  ],
});

// ── GUARD DE RUTAS ───────────────────────────────────────────
router.beforeEach((to, from, next) => {
  const userStore = useUserStore();
  const isLogged = userStore.isLogged();

  // rutas que no necesitan login
  const publicPaths = ["/", "/login", "/singup"];

  // si ya está logueado e intenta ir a login, registro o landing → home
  if (publicPaths.includes(to.path) && isLogged) {
    next("/home");
    return;
  }

  // si no está logueado e intenta acceder a una ruta protegida → landing
  if (!publicPaths.includes(to.path) && !isLogged) {
    next("/");
    return;
  }

  next();
});

// ── TÍTULO DINÁMICO POR RUTA ─────────────────────────────────
router.afterEach((to) => {
  const titles = {
    "/": "DopaMine",
    "/login": "Sign In — DopaMine",
    "/singup": "Sign Up — DopaMine",
    "/home": "Today — DopaMine",
    "/tasks": "Tasks — DopaMine",
    "/habits": "Habits — DopaMine",
    "/routines": "Routines — DopaMine",
    "/calendar": "Calendar — DopaMine",
    "/progress": "Progress — DopaMine",
    "/admin": "Admin — DopaMine",
    "/profile": "Profile — DopaMine",
    "/habits/new": "New Habit — DopaMine",
    "/tasks/new": "New Task — DopaMine",
    "/routines/new": "New Routine — DopaMine",
  };
  document.title = titles[to.path] || "DopaMine";
});

export default router;
