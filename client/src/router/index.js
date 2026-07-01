import { createRouter, createWebHistory } from "vue-router";
import { useUserStore } from "@/stores/userStore";

import Landingview from "@/views/LandingView.vue";
import Login         from "@/views/login.vue";
import Singup        from "@/views/singup.vue";
import HomeView      from "@/views/HomeView.vue";
import TasksView     from "@/views/TasksView.vue";
import HabitsView    from "@/views/HabitsView.vue";
import RoutinesView  from "@/views/RoutinesView.vue";
import CalendarView  from "@/views/CalendarView.vue";
import ProgressView  from "@/views/ProgressView.vue";
import NewHabitView  from "@/views/NewHabitView.vue";
import NewTaskView   from "@/views/NewTaskView.vue";
import NewRoutineView  from "@/views/NewRoutineView.vue";
import EditTaskView    from "@/views/EditTaskView.vue";
import EditRoutineView from "@/views/EditRoutineView.vue";
import AdminView     from "@/views/AdminView.vue";
import ProfileView   from "@/views/ProfileView.vue";
import EditHabitView from "@/views/EditHabitView.vue";
import ArchiveTasksView from "@/views/ArchiveTasksView.vue";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    // rutas publicas
    { path: "/",       component: Landingview, meta: { hideHeader: true } },
    { path: "/login",  component: Login,       meta: { hideHeader: true } },
    { path: "/singup", component: Singup,      meta: { hideHeader: true } },

    // rutas protegidas
    { path: "/home",     component: HomeView     },
    { path: "/tasks",    component: TasksView    },
    { path: "/habits",   component: HabitsView   },
    { path: "/routines", component: RoutinesView },
    { path: "/calendar", component: CalendarView },
    { path: "/progress", component: ProgressView },
    { path: "/admin",    component: AdminView    },
    { path: "/profile",  component: ProfileView  },

    { path: "/habits/new",   component: NewHabitView,   meta: { hideHeader: true } },
    { path: "/tasks/new",    component: NewTaskView,    meta: { hideHeader: true } },
    { path: "/routines/new", component: NewRoutineView, meta: { hideHeader: true } },

    { path: "/tasks/edit/:id",    component: EditTaskView,    meta: { hideHeader: true } },
    { path: "/routines/edit/:id", component: EditRoutineView, meta: { hideHeader: true } },
    { path: "/habits/edit/:id",   component: EditHabitView,   meta: { hideHeader: true } },
    { path: "/tasks/archive", component: ArchiveTasksView, meta: { hideHeader: false } },
  ],
});

// guard de rutas: redirige segun si el usuario esta logueado o no
router.beforeEach(function (to, from) {
  const userStore   = useUserStore();
  const isLogged    = userStore.isLogged();
  const publicPaths = ["/", "/login", "/singup"];

  // si ya esta logueado e intenta ir a una ruta publica, lo mandamos al home
  if (publicPaths.includes(to.path) && isLogged) {
    return "/home";
  }

  // si no esta logueado e intenta ir a una ruta protegida, lo mandamos a la landing
  if (!publicPaths.includes(to.path) && !isLogged) {
    return "/";
  }
});

// titulo dinamico de la pagina segun la ruta
router.afterEach(function (to) {
  const titles = {
    "/":             "DopaMine",
    "/login":        "Sign In — DopaMine",
    "/singup":       "Sign Up — DopaMine",
    "/home":         "Today — DopaMine",
    "/tasks":        "Tasks — DopaMine",
    "/habits":       "Habits — DopaMine",
    "/routines":     "Routines — DopaMine",
    "/calendar":     "Calendar — DopaMine",
    "/progress":     "Progress — DopaMine",
    "/admin":        "Admin — DopaMine",
    "/profile":      "Profile — DopaMine",
    "/habits/new":   "New Habit — DopaMine",
    "/tasks/new":    "New Task — DopaMine",
    "/routines/new": "New Routine — DopaMine",
  };
  document.title = titles[to.path] || "DopaMine";
});

export default router;