import { createRouter, createWebHistory } from "vue-router";
import Login        from "@/views/login.vue";
import Singup       from "@/views/singup.vue";
import HomeView     from "@/views/HomeView.vue";
import TasksView    from "@/views/TasksView.vue";
import HabitsView   from "@/views/HabitsView.vue";
import RoutinesView from "@/views/RoutinesView.vue";
import CalendarView from "@/views/CalendarView.vue";
import ProgressView from "@/views/ProgressView.vue";
import NewHabitView   from "@/views/NewHabitView.vue";
import NewTaskView    from "@/views/NewTaskView.vue";
import NewRoutineView from "@/views/NewRoutineView.vue"; // ✅ corregido — era RoutinesView.vue
import EditTaskView    from "@/views/EditTaskView.vue";
import EditRoutineView from "@/views/EditRoutineView.vue";
import AdminView   from "@/views/AdminView.vue";
import ProfileView from "@/views/ProfileView.vue";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: "/login",  name: "login",  component: Login  },
    { path: "/singup", name: "singup", component: Singup },

    { path: "/",         component: HomeView     },
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
  ],
});

export default router;