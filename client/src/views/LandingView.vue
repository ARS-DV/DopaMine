<script setup>
import { onMounted } from "vue";
import { useRouter } from "vue-router";
import { useUserStore } from "@/stores/userStore";

const userStore = useUserStore();
const router = useRouter();

//si el usuario ya esta logueado, lo mandamos al home directamente
onMounted(function () {
  if (userStore.isLogged()) {
    router.push("/home");
  }
});
</script>

<template>
  <div class="landing-wrapper">

    <!-- header minimo con logo y botones de acceso -->
    <header class="landing-header">
      <div class="landing-header-inner">
        <div class="landing-logo">
          <img
            src="../assets/logo.png"
            alt=""
            class="landing-logo-img"
            onerror="this.style.display='none'"
          />
          DopaMine
        </div>
        <div class="d-flex gap-2">
          <RouterLink to="/login" class="btn-dopamine btn-dopamine-ghost landing-nav-btn">
            Sign In
          </RouterLink>
          <RouterLink to="/singup" class="btn-dopamine btn-dopamine-primary landing-nav-btn">
            Sign Up
          </RouterLink>
        </div>
      </div>
    </header>

    <main class="landing-main">

      <!-- seccion hero principal -->
      <section class="hero-section fade-up">
        <div class="hero-deco">
          <i class="bi bi-lightning-charge-fill"></i>
        </div>
        <h1 class="hero-title">
          Your habits,<br />
          <em>your way.</em>
        </h1>
        <p class="hero-sub">
          DopaMine is a task, habit and routine manager designed for people with ADHD.
          Simple, clear and built around how your brain actually works.
        </p>
        <div class="d-flex gap-3 justify-content-center flex-wrap mt-4">
          <RouterLink to="/singup" class="btn-dopamine btn-dopamine-primary hero-cta">
            <i class="bi bi-rocket-takeoff me-2"></i> Get started — it's free
          </RouterLink>
          <RouterLink to="/login" class="btn-dopamine btn-dopamine-ghost hero-cta">
            <i class="bi bi-box-arrow-in-right me-2"></i> Sign in
          </RouterLink>
        </div>
      </section>

      <!-- tarjetas de funcionalidades -->
      <section class="features-section fade-up delay-2">
        <h2 class="features-title">Everything you need, nothing you don't</h2>

        <div class="row g-4">
          <div class="col-12 col-md-4">
            <div class="feature-card">
              <div class="feature-icon-wrap feature-icon-tasks">
                <i class="bi bi-check2-square"></i>
              </div>
              <h3 class="feature-name">Tasks</h3>
              <p class="feature-desc">
                Create tasks with deadlines, difficulty levels and step-by-step checklists.
                Never lose track of what needs to be done.
              </p>
            </div>
          </div>

          <div class="col-12 col-md-4">
            <div class="feature-card">
              <div class="feature-icon-wrap feature-icon-habits">
                <i class="bi bi-arrow-repeat"></i>
              </div>
              <h3 class="feature-name">Habits</h3>
              <p class="feature-desc">
                Track daily, weekly or monthly habits with a three-state system that
                rewards effort — even when you don't fully complete something.
              </p>
            </div>
          </div>

          <div class="col-12 col-md-4">
            <div class="feature-card">
              <div class="feature-icon-wrap feature-icon-routines">
                <i class="bi bi-list-check"></i>
              </div>
              <h3 class="feature-name">Routines</h3>
              <p class="feature-desc">
                Group your recurring activities into routines. Mark them step by step
                and watch your completion percentage grow throughout the day.
              </p>
            </div>
          </div>
        </div>
      </section>

      <!-- explicacion del sistema de tres estados -->
      <section class="states-section fade-up delay-3">
        <div class="states-inner">
          <div class="states-text">
            <h2 class="features-title text-start">Tried counts too</h2>
            <p class="states-desc">
              Most apps only know done or not done. DopaMine has a third state —
              <strong>Tried</strong> — for when you made an effort but didn't quite finish.
              Your streak doesn't break, and your best streak is saved forever.
            </p>
            <p class="states-desc">
              Designed with ADHD in mind, based on real feedback from people who know
              how frustrating it is to lose everything over one bad day.
            </p>
          </div>
          <!-- visualizacion de los tres estados -->
          <div class="states-visual">
            <div class="state-pill state-pending">
              <i class="bi bi-circle me-2"></i> Pending
            </div>
            <div class="state-arrow"><i class="bi bi-arrow-down"></i></div>
            <div class="state-pill state-tried">
              <i class="bi bi-dash-circle me-2"></i> Tried
            </div>
            <div class="state-arrow"><i class="bi bi-arrow-down"></i></div>
            <div class="state-pill state-done">
              <i class="bi bi-check-circle-fill me-2"></i> Done
            </div>
          </div>
        </div>
      </section>

      <!-- cta final de registro -->
      <section class="cta-section fade-up delay-4">
        <h2 class="cta-title">Ready to build better habits?</h2>
        <p class="cta-sub">Free, no credit card required.</p>
        <RouterLink to="/singup" class="btn-dopamine btn-dopamine-primary hero-cta">
          <i class="bi bi-person-plus me-2"></i> Create your account
        </RouterLink>
      </section>

    </main>

    <!-- pie de pagina -->
    <footer class="landing-footer">
      <p>DopaMine · Built for ADHD brains · DAW Project</p>
    </footer>

  </div>
</template>

<style scoped>
/* contenedor de toda la pagina */
.landing-wrapper {
  min-height: 100vh;
  background: var(--bg-subtle);
  font-family: "Atkinson Hyperlegible", sans-serif;
  display: flex;
  flex-direction: column;
}

/* header fijo en la parte superior */
.landing-header {
  background: var(--cinnamon-dark);
  padding: 0 2rem;
  height: 64px;
  display: flex;
  align-items: center;
  position: sticky;
  top: 0;
  z-index: 100;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
}

.landing-header-inner {
  max-width: 1100px;
  margin: 0 auto;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

/* logo del header */
.landing-logo {
  font-family: var(--font-serif);
  font-size: 1.3rem;
  font-weight: 700;
  color: var(--bg-base);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.landing-logo-img { width: 60px; height: 60px; border-radius: 6px; object-fit: contain; }
.landing-logo em  { font-style: italic; font-weight: 300; color: var(--vanilla-light); }

/* botones del header de landing */
.landing-nav-btn {
  font-weight: 700;
  font-size: 0.85rem;
  color: var(--bg-base);
  border-color: rgba(237, 217, 163, 0.4);
}
.landing-nav-btn:hover { background: rgba(255, 255, 255, 0.1); color: var(--vanilla-light); }

/* contenedor del contenido principal */
.landing-main {
  flex-grow: 1;
  max-width: 1100px;
  margin: 0 auto;
  width: 100%;
  padding: 0 2rem 4rem;
}

/* seccion hero */
.hero-section {
  text-align: center;
  padding: 5rem 1rem 4rem;
}

.hero-deco {
  font-size: 3rem;
  color: var(--cinnamon-mid);
  margin-bottom: 1.5rem;
  opacity: 0.7;
}

.hero-title {
  font-size: clamp(2.2rem, 5vw, 3.5rem);
  font-weight: 700;
  color: var(--cinnamon-dark);
  line-height: 1.2;
  margin-bottom: 1.2rem;
}
.hero-title em { font-style: italic; color: var(--cinnamon-mid); font-weight: 300; }

.hero-sub {
  font-size: 1.1rem;
  color: var(--cinnamon-soft);
  max-width: 560px;
  margin: 0 auto;
  line-height: 1.7;
}

.hero-cta {
  font-size: 1rem;
  font-weight: 700;
  min-height: 48px;
  padding: 0.75rem 1.8rem;
}

/* seccion de tarjetas de funcionalidades */
.features-section { padding: 4rem 0; }

.features-title {
  font-size: 1.6rem;
  font-weight: 700;
  color: var(--cinnamon-dark);
  text-align: center;
  margin-bottom: 2.5rem;
}

.feature-card {
  background: var(--bg-card);
  border: 1.5px solid var(--vanilla-mid);
  border-radius: 16px;
  padding: 1.8rem 1.5rem;
  height: 100%;
  box-shadow: 0 2px 12px rgba(92, 51, 23, 0.07);
  transition: box-shadow 0.2s, transform 0.2s;
}
.feature-card:hover { box-shadow: 0 6px 24px rgba(92, 51, 23, 0.13); transform: translateY(-3px); }

/* icono de cada tarjeta */
.feature-icon-wrap {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  margin-bottom: 1rem;
}
.feature-icon-tasks    { background: var(--state-error-bg); color: var(--state-error); }
.feature-icon-habits   { background: var(--state-warn-bg);  color: var(--state-warn); }
.feature-icon-routines { background: var(--state-ok-bg);    color: var(--state-ok); }

.feature-name { font-size: 1.1rem; font-weight: 700; color: var(--cinnamon-dark); margin-bottom: 0.6rem; }
.feature-desc { font-size: 0.9rem; color: var(--cinnamon-soft); line-height: 1.6; margin: 0; }

/* seccion de explicacion de los tres estados */
.states-section { padding: 4rem 0; }

.states-inner {
  background: var(--bg-card);
  border: 1.5px solid var(--vanilla-mid);
  border-radius: 20px;
  padding: 3rem;
  display: flex;
  gap: 3rem;
  align-items: center;
  flex-wrap: wrap;
  box-shadow: 0 2px 12px rgba(92, 51, 23, 0.07);
}

.states-text  { flex: 1; min-width: 260px; }
.states-desc  { font-size: 0.95rem; color: var(--cinnamon-soft); line-height: 1.7; margin-bottom: 0.8rem; }

/* visualizacion de los tres estados */
.states-visual { display: flex; flex-direction: column; align-items: center; gap: 0.5rem; flex-shrink: 0; }

.state-pill {
  font-size: 0.95rem;
  font-weight: 700;
  padding: 0.65rem 1.4rem;
  border-radius: 50px;
  display: flex;
  align-items: center;
  min-width: 150px;
  justify-content: center;
}
.state-pending { background: var(--bg-subtle);    color: var(--cinnamon-soft); border: 2px solid var(--vanilla-mid); }
.state-tried   { background: var(--state-warn-bg); color: var(--state-warn);   border: 2px solid var(--state-warn); }
.state-done    { background: var(--state-ok-bg);   color: var(--state-ok);     border: 2px solid var(--state-ok); }
.state-arrow   { color: var(--vanilla-mid); font-size: 1rem; }

/* seccion de cta final */
.cta-section {
  text-align: center;
  padding: 4rem 1rem;
  background: var(--bg-card);
  border: 1.5px solid var(--vanilla-mid);
  border-radius: 20px;
  box-shadow: 0 2px 12px rgba(92, 51, 23, 0.07);
}

.cta-title { font-size: 1.8rem; font-weight: 700; color: var(--cinnamon-dark); margin-bottom: 0.5rem; }
.cta-sub   { font-size: 0.95rem; color: var(--cinnamon-soft); margin-bottom: 1.5rem; }

/* pie de pagina */
.landing-footer {
  background: var(--cinnamon-dark);
  text-align: center;
  padding: 1.2rem;
  font-size: 0.82rem;
  color: rgba(237, 217, 163, 0.6);
}

/* responsive */
@media (max-width: 768px) {
  .landing-main   { padding: 0 1rem 3rem; }
  .hero-section   { padding: 3rem 0.5rem 2.5rem; }
  .states-inner   { padding: 2rem 1.5rem; flex-direction: column; }
  .landing-header { padding: 0 1rem; }
}
</style>