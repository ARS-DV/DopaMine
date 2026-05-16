<script setup>
// imports para Vue, router y API
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/userStore'
import { rutaApi } from '@/config.js'

const userStore = useUserStore()
const router    = useRouter()

const avatarBase = 'http://localhost/DopaMine_Server/uploads/avatars/'

// variables del formulario
const nickNameInput = ref('')
const emailInput    = ref('')
const pswdInput     = ref('')
const pswdConfirm   = ref('')
const showPswd      = ref(false)
const showConfirm   = ref(false)

// avatar
const avatarPreview  = ref(null)   // URL de preview local (antes de subir)
const avatarFile     = ref(null)   // File object seleccionado
const avatarInputRef = ref(null)   // referencia al input file oculto

// estados
const loading        = ref(false)
const successMessage = ref('')
const errorMessage   = ref('')

// cargar datos del usuario desde Pinia al montar
function loadUserData() {
  nickNameInput.value = userStore.user.nickName || ''
  emailInput.value    = userStore.user.email    || ''
}

// abrir el selector de archivo
function openAvatarPicker() {
  avatarInputRef.value.click()
}

// previsualizar imagen seleccionada
function onAvatarSelected(event) {
  let file = event.target.files[0]
  if (!file) return

  let validTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif']
  if (!validTypes.includes(file.type)) {
    errorMessage.value = 'Only JPG, PNG, WEBP or GIF images are allowed'
    return
  }

  if (file.size > 2 * 1024 * 1024) {
    errorMessage.value = 'Image must be smaller than 2MB'
    return
  }

  avatarFile.value    = file
  avatarPreview.value = URL.createObjectURL(file)
  errorMessage.value  = ''
}

// URL del avatar actual (preview local si existe, BD si no, nulo si ninguno)
const currentAvatar = computed(function() {
  if (avatarPreview.value) return avatarPreview.value
  if (userStore.user.avatar) return avatarBase + userStore.user.avatar
  return null
})

// iniciales para el avatar por defecto
const initials = computed(function() {
  let name = userStore.user.nickName || ''
  return name.slice(0, 2).toUpperCase()
})

// guardar perfil: primero el avatar si hay uno nuevo, luego los datos de texto
async function saveProfile() {
  successMessage.value = ''
  errorMessage.value   = ''

  // validaciones
  if (nickNameInput.value.trim() === '') {
    errorMessage.value = 'Nickname is required'
    return
  }

  if (emailInput.value.trim() === '' || !emailInput.value.includes('@')) {
    errorMessage.value = 'Enter a valid email address'
    return
  }

  if (pswdInput.value !== '' && pswdInput.value.length < 7) {
    errorMessage.value = 'Password must be at least 7 characters'
    return
  }

  if (pswdInput.value !== pswdConfirm.value) {
    errorMessage.value = 'Passwords do not match'
    return
  }

  loading.value = true

  // subir avatar primero si hay uno nuevo seleccionado
  if (avatarFile.value) {
    let formData = new FormData()
    formData.append('user_id', userStore.user.id)
    formData.append('avatar',  avatarFile.value)

    try {
      let res  = await fetch('http://localhost/DopaMine_Server/upload_avatar.php', {
        method: 'POST',
        body:   formData
      })
      let data = await res.json()

      if (data.status === 'success') {
        userStore.updateUser({ avatar: data.avatar })
        avatarFile.value      = null
        avatarPreview.value   = null
      } else {
        errorMessage.value = data.message
        loading.value      = false
        return
      }
    } catch (err) {
      errorMessage.value = 'Error uploading avatar'
      loading.value      = false
      return
    }
  }

  // actualizar datos de texto
  let profileData = {
    nickName: nickNameInput.value.trim(),
    email:    emailInput.value.trim(),
    pswd:     pswdInput.value || null
  }

  fetch(rutaApi + "?entity=users&id=" + userStore.user.id, {
    method:  'PUT',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify(profileData)
  })
  .then(function(res) { return res.json() })
  .then(function(data) {
    loading.value = false

    if (data.status === 'success') {
      // sincronizar Pinia con los datos actualizados
      userStore.updateUser({
  nickName: data.user.nickName,
  email:    data.user.email,
  avatar:   data.user.avatar || userStore.user.avatar
})

      // limpiar campos de contraseña
      pswdInput.value   = ''
      pswdConfirm.value = ''

      successMessage.value = 'Profile updated successfully'
    } else {
      errorMessage.value = data.message
    }
  })
  .catch(function() {
    loading.value      = false
    errorMessage.value = 'Connection error'
  })
}

onMounted(function() {
  loadUserData()
})
</script>

<template>
  <div class="profile-wrapper">
    <div class="profile-container">

      <!-- CABECERA -->
      <div class="mb-4 fade-up">
        <h1 class="page-title">
          <em>your</em>
          Profile
        </h1>
      </div>

      <!-- ÉXITO -->
      <div v-if="successMessage" class="success-text mb-4 fade-up" role="status" aria-live="polite">
        <i class="bi bi-check-circle me-2" aria-hidden="true"></i>
        <strong>{{ successMessage }}</strong>
      </div>

      <!-- ERROR -->
      <div v-if="errorMessage" class="error-text mb-4 fade-up" role="alert">
        <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>
        <strong>{{ errorMessage }}</strong>
      </div>

      <form @submit.prevent="saveProfile" class="d-flex flex-column gap-4 fade-up delay-1" novalidate>

        <!-- AVATAR -->
        <div class="form-section">
          <label class="form-label-accessible mb-3">
            <i class="bi bi-person-circle me-2" aria-hidden="true"></i>Profile picture
          </label>

          <div class="avatar-area">
            <!-- Preview del avatar -->
            <div class="avatar-preview-wrap">
              <img
                v-if="currentAvatar"
                :src="currentAvatar"
                alt="Your profile picture"
                class="avatar-img"
              >
              <div
                v-else
                class="avatar-initials"
                aria-label="Default avatar with your initials"
              >
                {{ initials }}
              </div>

              <!-- Botón de cámara encima del avatar -->
              <button
                type="button"
                class="avatar-edit-btn"
                aria-label="Change profile picture"
                @click="openAvatarPicker"
              >
                <i class="bi bi-camera" aria-hidden="true"></i>
              </button>
            </div>

            <div class="avatar-info">
              <p class="avatar-hint">
                JPG, PNG, WEBP or GIF · Max 2MB
              </p>
              <button
                type="button"
                class="btn-dopamine btn-dopamine-ghost avatar-btn"
                aria-label="Select a new profile picture from your device"
                @click="openAvatarPicker"
              >
                <i class="bi bi-upload me-2" aria-hidden="true"></i>
                Choose image
              </button>
              <p v-if="avatarFile" class="avatar-selected-name" aria-live="polite">
                <i class="bi bi-check-circle me-1" aria-hidden="true"></i>
                {{ avatarFile.name }} selected
              </p>
            </div>
          </div>

          <!-- Input file oculto -->
          <input
            ref="avatarInputRef"
            type="file"
            accept="image/jpeg,image/png,image/webp,image/gif"
            class="d-none"
            aria-hidden="true"
            tabindex="-1"
            @change="onAvatarSelected"
          >
        </div>

        <!-- NICKNAME -->
        <div class="form-section">
          <label for="profile-nickname" class="form-label-accessible">
            <i class="bi bi-person me-2" aria-hidden="true"></i>Nickname <span class="required-star" aria-hidden="true">*</span>
          </label>
          <input
            id="profile-nickname"
            v-model="nickNameInput"
            type="text"
            class="form-control dopamine-input input-lg"
            placeholder="Your nickname"
            aria-required="true"
            autocomplete="nickname"
          >
        </div>

        <!-- EMAIL -->
        <div class="form-section">
          <label for="profile-email" class="form-label-accessible">
            <i class="bi bi-envelope me-2" aria-hidden="true"></i>Email <span class="required-star" aria-hidden="true">*</span>
          </label>
          <input
            id="profile-email"
            v-model="emailInput"
            type="email"
            class="form-control dopamine-input input-lg"
            placeholder="example@mail.com"
            aria-required="true"
            autocomplete="email"
          >
        </div>

        <!-- CONTRASEÑA -->
        <div class="form-section">
          <label class="form-label-accessible mb-1">
            <i class="bi bi-lock me-2" aria-hidden="true"></i>Change password
          </label>
          <p class="field-hint mb-3">Leave blank to keep your current password</p>

          <!-- Nueva contraseña -->
          <div class="mb-3">
            <label for="profile-pswd" class="pswd-label">New password</label>
            <div class="pswd-wrapper">
              <input
                id="profile-pswd"
                v-model="pswdInput"
                :type="showPswd ? 'text' : 'password'"
                class="form-control dopamine-input input-lg"
                placeholder="Min. 7 characters"
                autocomplete="new-password"
                aria-describedby="pswd-hint"
              >
              <button
                type="button"
                class="pswd-toggle"
                :aria-label="showPswd ? 'Hide password' : 'Show password'"
                :aria-pressed="showPswd"
                @click="showPswd = !showPswd"
              >
                <i class="bi" :class="showPswd ? 'bi-eye-slash' : 'bi-eye'" aria-hidden="true"></i>
              </button>
            </div>
            <p id="pswd-hint" class="field-hint mt-1">Minimum 7 characters</p>
          </div>

          <!-- Confirmar contraseña -->
          <div>
            <label for="profile-pswd-confirm" class="pswd-label">Confirm new password</label>
            <div class="pswd-wrapper">
              <input
                id="profile-pswd-confirm"
                v-model="pswdConfirm"
                :type="showConfirm ? 'text' : 'password'"
                class="form-control dopamine-input input-lg"
                placeholder="Repeat your new password"
                autocomplete="new-password"
              >
              <button
                type="button"
                class="pswd-toggle"
                :aria-label="showConfirm ? 'Hide confirm password' : 'Show confirm password'"
                :aria-pressed="showConfirm"
                @click="showConfirm = !showConfirm"
              >
                <i class="bi" :class="showConfirm ? 'bi-eye-slash' : 'bi-eye'" aria-hidden="true"></i>
              </button>
            </div>

            <!-- Indicador de coincidencia -->
            <p
              v-if="pswdConfirm.length > 0"
              class="pswd-match-hint"
              :class="pswdInput === pswdConfirm ? 'match-ok' : 'match-fail'"
              aria-live="polite"
            >
              <i class="bi me-1" :class="pswdInput === pswdConfirm ? 'bi-check-circle' : 'bi-x-circle'" aria-hidden="true"></i>
              {{ pswdInput === pswdConfirm ? 'Passwords match' : 'Passwords do not match' }}
            </p>
          </div>
        </div>

        <!-- BOTONES -->
        <div class="d-flex gap-3 flex-wrap pb-2">
          <button
            type="submit"
            class="btn-dopamine btn-dopamine-primary form-action-btn"
            :disabled="loading"
            :aria-busy="loading"
          >
            <span v-if="loading">
              <span class="spinner-border spinner-border-sm me-2" role="status">
                <span class="visually-hidden">Saving...</span>
              </span>
              Saving...
            </span>
            <span v-else>
              <i class="bi bi-check2 me-2" aria-hidden="true"></i> Save changes
            </span>
          </button>
          <button
            type="button"
            class="btn-dopamine btn-dopamine-cancel form-action-btn"
            aria-label="Cancel and go back"
            @click="router.back()"
          >
            <i class="bi bi-x me-2" aria-hidden="true"></i> Cancel
          </button>
        </div>

      </form>
    </div>
  </div>
</template>

<style scoped>
.profile-wrapper {
  min-height: 100vh;
  background-color: var(--bg-subtle);
  padding: 2.5rem 1.5rem 5rem;
  font-family: 'Atkinson Hyperlegible', sans-serif;
}

.profile-container {
  max-width: 600px;
  margin: 0 auto;
}

@media (max-width: 768px) {
  .profile-wrapper { padding: 1.5rem 1rem 4rem; }
}

/* SECCIONES */
.form-section {
  background: var(--bg-card);
  border: 1.5px solid var(--vanilla-mid);
  border-radius: 12px;
  padding: 1.3rem 1.4rem;
}

/* LABELS */
.form-label-accessible {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 1rem;
  font-weight: 700;
  color: var(--cinnamon-dark);
  display: flex;
  align-items: center;
  margin-bottom: 0.5rem;
}

.required-star { color: var(--state-error); font-weight: 700; }
.field-hint    { font-size: 0.82rem; color: var(--cinnamon-soft); margin: 0; }

.input-lg { font-size: 1rem !important; padding: 0.7rem 0.9rem !important; min-height: 48px !important; }

/* MENSAJE DE ÉXITO */
.success-text {
  background: var(--state-ok-bg);
  border: 1.5px solid var(--state-ok);
  border-radius: 10px;
  padding: 0.85rem 1.1rem;
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.95rem;
  font-weight: 600;
  color: #2E5E32;
  display: flex;
  align-items: center;
}

/* ÁREA DE AVATAR */
.avatar-area {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.avatar-preview-wrap {
  position: relative;
  flex-shrink: 0;
}

.avatar-img {
  width: 88px;
  height: 88px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid var(--vanilla-mid);
  display: block;
}

.avatar-initials {
  width: 88px;
  height: 88px;
  border-radius: 50%;
  background: var(--cinnamon-mid);
  color: #fff;
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 1.8rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 3px solid var(--vanilla-mid);
}

.avatar-edit-btn {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 30px;
  height: 30px;
  border-radius: 50%;
  background: var(--cinnamon-dark);
  color: #fff;
  border: 2px solid var(--bg-card);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  cursor: pointer;
  transition: background 0.15s;
}

.avatar-edit-btn:hover { background: var(--cinnamon-mid); }

.avatar-edit-btn:focus-visible {
  outline: 3px solid var(--cinnamon-mid);
  outline-offset: 3px;
}

.avatar-info {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.avatar-hint {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.8rem;
  color: var(--cinnamon-soft);
  margin: 0;
}

.avatar-btn {
  min-height: 44px;
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-weight: 700;
  font-size: 0.9rem;
  width: fit-content;
}

.avatar-selected-name {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--state-ok);
  margin: 0;
}

/* CONTRASEÑA */
.pswd-label {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.88rem;
  font-weight: 700;
  color: var(--cinnamon-dark);
  display: block;
  margin-bottom: 0.4rem;
}

.pswd-wrapper {
  position: relative;
}

.pswd-wrapper .dopamine-input {
  padding-right: 3rem !important;
}

.pswd-toggle {
  position: absolute;
  right: 0.8rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: var(--cinnamon-soft);
  font-size: 1.1rem;
  cursor: pointer;
  padding: 0.2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  transition: color 0.15s;
}

.pswd-toggle:hover { color: var(--cinnamon-dark); }

.pswd-toggle:focus-visible {
  outline: 3px solid var(--cinnamon-mid);
  outline-offset: 2px;
}

.pswd-match-hint {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 0.82rem;
  font-weight: 600;
  margin: 0.4rem 0 0;
  display: flex;
  align-items: center;
}

.match-ok   { color: var(--state-ok); }
.match-fail { color: var(--state-error); }

/* BOTONES */
.form-action-btn {
  font-family: 'Atkinson Hyperlegible', sans-serif;
  font-size: 1rem;
  font-weight: 700;
  min-height: 48px;
  padding: 0.7rem 1.5rem;
}

.form-action-btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}
</style>