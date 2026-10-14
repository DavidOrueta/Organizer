<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { apiEnabled, apiRequest, setApiToken } from './api'
import {
  Activity,
  ArrowDown,
  ArrowUpRight,
  Bell,
  CalendarDays,
  Check,
  CheckCheck,
  ChevronDown,
  Circle,
  Clock3,
  PanelsTopLeft,
  LayoutDashboard,
  LogOut,
  MessageCircle,
  MoreHorizontal,
  Plus,
  Search,
  Send,
  ShieldCheck,
  SlidersHorizontal,
  Sparkles,
  Users,
  X,
} from '@lucide/vue'

const people = reactive([
  { name: 'Laura Méndez', role: 'Product lead', initials: 'LM', color: 'coral' },
  { name: 'Diego Ruiz', role: 'Diseño', initials: 'DR', color: 'blue' },
  { name: 'Marta Soler', role: 'Desarrollo', initials: 'MS', color: 'green' },
  { name: 'Pablo Gil', role: 'Desarrollo', initials: 'PG', color: 'gold' },
])

const projects = reactive([
  { id: 'atlas', name: 'Atlas mobile', code: 'AT', color: 'coral', description: 'Rediseño de la experiencia móvil' },
  { id: 'web', name: 'Web platform', code: 'WP', color: 'blue', description: 'Nueva plataforma de producto' },
  { id: 'brand', name: 'Brand refresh', code: 'BR', color: 'green', description: 'Sistema visual y comunicación' },
])

const seedTasks = [
  { id: 'T-104', title: 'Revisar flujo de onboarding', description: 'Cerrar los últimos detalles del recorrido inicial y validar el estado vacío.', status: 'progress', priority: 'high', assignee: 'Laura Méndez', dueDate: '2026-10-12', projectId: 'atlas', labels: ['Producto', 'UX'], comments: [{ author: 'Diego Ruiz', text: 'He actualizado las pantallas de bienvenida.', time: '09:42' }] },
  { id: 'T-108', title: 'Preparar componentes de navegación', description: 'Unificar navegación inferior y cabecera para los distintos tamaños.', status: 'todo', priority: 'medium', assignee: 'Diego Ruiz', dueDate: '2026-10-14', projectId: 'atlas', labels: ['Diseño'], comments: [] },
  { id: 'T-112', title: 'Integrar analítica de eventos', description: 'Añadir eventos de activación y revisar nombres con producto.', status: 'todo', priority: 'low', assignee: 'Pablo Gil', dueDate: '2026-10-16', projectId: 'atlas', labels: ['Desarrollo'], comments: [] },
  { id: 'T-115', title: 'Prototipo de pantalla de inicio', description: 'Probar dos variantes de jerarquía y compartir con el equipo.', status: 'progress', priority: 'medium', assignee: 'Diego Ruiz', dueDate: '2026-10-10', projectId: 'atlas', labels: ['Diseño', 'UX'], comments: [{ author: 'Laura Méndez', text: 'La segunda opción funciona mejor.', time: 'Ayer' }, { author: 'Diego Ruiz', text: 'Perfecto, preparo la versión final.', time: '10:16' }] },
  { id: 'T-119', title: 'Auditar contraste de color', description: 'Comprobar accesibilidad de las combinaciones de la nueva paleta.', status: 'done', priority: 'high', assignee: 'Marta Soler', dueDate: '2026-10-08', projectId: 'atlas', labels: ['Accesibilidad'], comments: [] },
  { id: 'T-121', title: 'Documentar decisiones de producto', description: 'Dejar un resumen de decisiones y enlaces para el siguiente ciclo.', status: 'done', priority: 'low', assignee: 'Laura Méndez', dueDate: '2026-10-07', projectId: 'atlas', labels: ['Producto'], comments: [] },
  { id: 'T-203', title: 'Definir estructura del catálogo', description: 'Acordar filtros, categorías y jerarquía de navegación.', status: 'progress', priority: 'high', assignee: 'Marta Soler', dueDate: '2026-10-11', projectId: 'web', labels: ['Producto'], comments: [] },
  { id: 'T-206', title: 'Revisar textos de interfaz', description: 'Ajustar microcopy para el primer lanzamiento.', status: 'todo', priority: 'medium', assignee: 'Laura Méndez', dueDate: '2026-10-13', projectId: 'web', labels: ['Contenido'], comments: [] },
  { id: 'T-301', title: 'Presentar propuesta tipográfica', description: 'Preparar muestras de tipografía para la revisión del viernes.', status: 'progress', priority: 'medium', assignee: 'Diego Ruiz', dueDate: '2026-10-09', projectId: 'brand', labels: ['Diseño'], comments: [] },
]

function loadState() {
  try {
    return JSON.parse(localStorage.getItem('organizer-workspace') || localStorage.getItem('nexo-workspace') || '{}')
  } catch {
    return {}
  }
}

const saved = loadState()
if (saved.projects) projects.splice(0, projects.length, ...saved.projects)
if (saved.people) people.splice(0, people.length, ...saved.people)
if (apiEnabled) {
  projects.splice(0)
  people.splice(0)
}
const tasks = ref(saved.tasks || seedTasks)
const currentUser = ref(apiEnabled ? null : saved.user === undefined ? { name: 'Laura Méndez', role: 'admin', email: 'laura@equipo.com' } : saved.user)
const workspaceLoading = ref(apiEnabled)
const activeProjectId = ref(apiEnabled ? '' : saved.projectId || 'atlas')
const currentView = ref('board')
const menuOpen = ref(false)
const mobileNavOpen = ref(false)
const taskModalOpen = ref(false)
const projectModalOpen = ref(false)
const personModalOpen = ref(false)
const authMode = ref('login')
const filtersOpen = ref(false)
const draggedTaskId = ref(null)
const commentDraft = ref('')
const authEmail = ref('')
const authPassword = ref('')
const authPasswordConfirm = ref('')
const authName = ref('')
const authError = ref('')
const apiError = ref('')
const searchQuery = ref('')
const statusFilter = ref('all')
const personFilter = ref('all')
const dueFilter = ref('all')
const taskDraft = ref(null)
const projectDraft = ref({ name: '', code: '', description: '' })
const personDraft = ref({ name: '', email: '', role: 'Desarrollo' })

const columns = [
  { id: 'todo', label: 'Pendiente', tone: 'slate', icon: Circle },
  { id: 'progress', label: 'En curso', tone: 'orange', icon: Activity },
  { id: 'done', label: 'Hecho', tone: 'green', icon: Check },
]

const activeProject = computed(() => projects.find((project) => project.id === activeProjectId.value) || projects[0] || null)
const canAdminProject = computed(() => {
  if (!currentUser.value) return false
  if (!apiEnabled) return currentUser.value.role === 'admin'
  return activeProject.value?.members?.find((member) => member.id === currentUser.value.id)?.pivot?.role === 'admin'
})
const projectTasks = computed(() => tasks.value.filter((task) => task.projectId === activeProjectId.value))
const boardTasks = computed(() => projectTasks.value.filter((task) => {
  const search = searchQuery.value.trim().toLocaleLowerCase('es')
  const matchesSearch = !search || `${task.title} ${task.description} ${task.id} ${task.labels.join(' ')}`.toLocaleLowerCase('es').includes(search)
  const matchesStatus = statusFilter.value === 'all' || task.status === statusFilter.value
  const matchesPerson = personFilter.value === 'all' || task.assignee === personFilter.value
  const due = new Date(`${task.dueDate}T23:59:59`)
  const today = new Date()
  today.setHours(23, 59, 59, 999)
  const nextWeek = new Date(today)
  nextWeek.setDate(nextWeek.getDate() + 7)
  const matchesDue = dueFilter.value === 'all'
    || (dueFilter.value === 'overdue' && due < today && task.status !== 'done')
    || (dueFilter.value === 'week' && due >= new Date() && due <= nextWeek)
  return matchesSearch && matchesStatus && matchesPerson && matchesDue
}))
const doneCount = computed(() => projectTasks.value.filter((task) => task.status === 'done').length)
const overdueCount = computed(() => projectTasks.value.filter((task) => isOverdue(task)).length)
const activeCount = computed(() => projectTasks.value.filter((task) => task.status !== 'done').length)
const selectedTask = computed(() => taskDraft.value)
const viewTitle = computed(() => currentView.value === 'dashboard' ? 'Resumen' : currentView.value === 'team' ? 'Equipo' : 'Tablero')
const activeFiltersCount = computed(() => [statusFilter.value !== 'all', personFilter.value !== 'all', dueFilter.value !== 'all'].filter(Boolean).length)

function mapApiProject(project, index) {
  return {
    id: String(project.id),
    name: project.name,
    code: project.key,
    color: ['coral', 'blue', 'green'][index % 3],
    description: project.description || '',
    members: project.members || [],
  }
}

function mapApiTask(task, projectId) {
  return {
    id: String(task.id),
    title: task.title,
    description: task.description || '',
    status: task.status === 'in_progress' ? 'progress' : task.status,
    priority: task.priority,
    assignee: task.assignee?.name || currentUser.value?.name || 'Sin asignar',
    assigneeId: task.assignee_id || task.assignee?.id || null,
    dueDate: task.due_at?.slice(0, 10) || '',
    projectId: String(projectId),
    labels: [],
    comments: (task.comments || []).map((comment) => ({
      apiId: comment.id,
      author: comment.author?.name || 'Miembro',
      text: comment.body,
      time: comment.created_at ? new Date(comment.created_at).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' }) : '',
    })),
  }
}

async function loadApiTasks(projectId) {
  const response = await apiRequest(`/projects/${projectId}/tasks`)
  return response.data.map((task) => mapApiTask(task, projectId))
}

async function loadApiWorkspace() {
  const response = await apiRequest('/projects')
  const apiProjects = response.data
  projects.splice(0, projects.length, ...apiProjects.map(mapApiProject))
  const members = new Map()
  apiProjects.forEach((project) => project.members.forEach((member) => members.set(member.id, {
    id: member.id,
    name: member.name,
    role: member.pivot?.role === 'admin' ? 'Administración' : 'Miembro',
    initials: initials(member.name),
    color: ['coral', 'blue', 'green', 'gold'][members.size % 4],
  })))
  people.splice(0, people.length, ...members.values())
  if (!apiProjects.length) {
    activeProjectId.value = ''
    tasks.value = []
    return
  }
  const selected = apiProjects.find((project) => String(project.id) === activeProjectId.value) || apiProjects[0]
  activeProjectId.value = String(selected.id)
  const taskGroups = await Promise.all(apiProjects.map((project) => loadApiTasks(project.id)))
  tasks.value = taskGroups.flat()
}

onMounted(async () => {
  if (!apiEnabled) return
  if (!localStorage.getItem('organizer-api-token') && !localStorage.getItem('nexo-api-token')) {
    workspaceLoading.value = false
    return
  }
  try {
    const { user } = await apiRequest('/auth/me')
    await loadApiWorkspace()
    currentUser.value = user
  } catch {
    setApiToken(null)
    currentUser.value = null
  } finally {
    workspaceLoading.value = false
  }
})

watch([tasks, currentUser, activeProjectId, projects, people], () => {
  if (!apiEnabled) localStorage.setItem('organizer-workspace', JSON.stringify({ tasks: tasks.value, user: currentUser.value, projectId: activeProjectId.value, projects, people }))
}, { deep: true })

function isOverdue(task) {
  return task.status !== 'done' && new Date(`${task.dueDate}T23:59:59`) < new Date()
}

function dateLabel(date) {
  const day = new Date(`${date}T12:00:00`)
  return new Intl.DateTimeFormat('es-ES', { day: 'numeric', month: 'short' }).format(day).replace('.', '')
}

function dueText(task) {
  if (isOverdue(task)) return 'Vencida'
  if (!task.dueDate) return 'Sin fecha'
  if (task.dueDate === new Date().toISOString().slice(0, 10)) return 'Hoy'
  return dateLabel(task.dueDate)
}

function initials(name) {
  return name.split(' ').map((part) => part[0]).slice(0, 2).join('').toUpperCase()
}

function person(name) {
  return people.find((member) => member.name === name) || { name, initials: initials(name), color: 'blue', role: 'Miembro' }
}

function tasksInColumn(columnId) {
  return boardTasks.value.filter((task) => task.status === columnId)
}

function openNewTask(status = 'todo') {
  taskDraft.value = {
    id: null,
    title: '',
    description: '',
    status,
    priority: 'medium',
    assignee: currentUser.value?.name || people[0].name,
    dueDate: new Date(Date.now() + 7 * 86400000).toISOString().slice(0, 10),
    labels: [],
    comments: [],
  }
  taskModalOpen.value = true
}

function openTask(task) {
  taskDraft.value = JSON.parse(JSON.stringify(task))
  taskModalOpen.value = true
}

async function saveTask() {
  if (!taskDraft.value?.title.trim()) return
  if (apiEnabled) {
    const draft = taskDraft.value
    const payload = {
      title: draft.title.trim(),
      description: draft.description || null,
      status: draft.status === 'progress' ? 'in_progress' : draft.status,
      priority: draft.priority,
      due_at: draft.dueDate || null,
    }
    if (canAdminProject.value) {
      payload.assignee_id = people.find((member) => member.name === draft.assignee)?.id || null
    }
    try {
      const isNew = !draft.id
      const path = isNew ? `/projects/${activeProjectId.value}/tasks` : `/tasks/${draft.id}`
      const response = await apiRequest(path, { method: isNew ? 'POST' : 'PATCH', body: JSON.stringify(payload) })
      for (const comment of draft.comments.filter((item) => !item.apiId)) {
        await apiRequest(`/tasks/${response.data.id}/comments`, { method: 'POST', body: JSON.stringify({ body: comment.text }) })
      }
      await loadApiWorkspace()
      taskModalOpen.value = false
      taskDraft.value = null
      apiError.value = ''
    } catch (error) {
      apiError.value = error.message
    }
    return
  }
  if (taskDraft.value.id) {
    const index = tasks.value.findIndex((task) => task.id === taskDraft.value.id)
    if (index >= 0) tasks.value[index] = { ...taskDraft.value }
  } else {
    const number = Math.max(0, ...tasks.value.map((task) => Number(task.id.split('-')[1]) || 0)) + 1
    tasks.value.unshift({ ...taskDraft.value, id: `T-${number}`, projectId: activeProjectId.value })
  }
  taskModalOpen.value = false
  taskDraft.value = null
}

async function addComment() {
  const text = commentDraft.value.trim()
  if (!text || !taskDraft.value) return
  if (apiEnabled && taskDraft.value.id) {
    try {
      const response = await apiRequest(`/tasks/${taskDraft.value.id}/comments`, { method: 'POST', body: JSON.stringify({ body: text }) })
      taskDraft.value.comments.push({ apiId: response.data.id, author: currentUser.value.name, text: response.data.body, time: new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' }) })
      commentDraft.value = ''
    } catch (error) {
      apiError.value = error.message
    }
    return
  }
  taskDraft.value.comments.push({ author: currentUser.value.name, text, time: new Intl.DateTimeFormat('es-ES', { hour: '2-digit', minute: '2-digit' }).format(new Date()) })
  commentDraft.value = ''
}

async function moveTask(taskId, status) {
  const task = tasks.value.find((item) => item.id === taskId)
  if (task && (canAdminProject.value || task.assignee === currentUser.value.name)) {
    if (apiEnabled) {
      try {
        await apiRequest(`/tasks/${taskId}`, { method: 'PATCH', body: JSON.stringify({ status: status === 'progress' ? 'in_progress' : status }) })
      } catch (error) {
        apiError.value = error.message
        draggedTaskId.value = null
        return
      }
    }
    task.status = status
  }
  draggedTaskId.value = null
}

function startDrag(event, task) {
  if (!canAdminProject.value && task.assignee !== currentUser.value.name) {
    event.preventDefault()
    return
  }
  draggedTaskId.value = task.id
  event.dataTransfer.effectAllowed = 'move'
  event.dataTransfer.setData('text/plain', task.id)
}

async function finishLogin() {
  const email = authEmail.value.trim() || 'laura@equipo.com'
  authError.value = ''
  if (apiEnabled) {
    try {
      const registering = authMode.value === 'register'
      const response = await apiRequest(`/auth/${registering ? 'register' : 'login'}`, {
        method: 'POST',
        body: JSON.stringify(registering
          ? { name: authName.value.trim(), email, password: authPassword.value, password_confirmation: authPasswordConfirm.value }
          : { email, password: authPassword.value }),
      })
      setApiToken(response.token)
      currentUser.value = response.user
      await loadApiWorkspace()
      authPassword.value = ''
      authPasswordConfirm.value = ''
    } catch (error) {
      setApiToken(null)
      currentUser.value = null
      authError.value = error.message
    }
    return
  }
  const name = authMode.value === 'register' ? authName.value.trim() || 'Nuevo miembro' : (people.find((member) => member.name.toLocaleLowerCase('es').includes(email.split('@')[0].toLocaleLowerCase('es'))) || people[0]).name
  if (authMode.value === 'register' && !people.some((member) => member.name === name)) {
    people.push({ name, role: 'Miembro', initials: initials(name), color: 'blue' })
  }
  currentUser.value = { name, role: 'member', email }
}

async function signOut() {
  if (apiEnabled && (localStorage.getItem('organizer-api-token') || localStorage.getItem('nexo-api-token'))) {
    try {
      await apiRequest('/auth/logout', { method: 'POST' })
    } catch {
      setApiToken(null)
    }
    setApiToken(null)
  }
  currentUser.value = null
  menuOpen.value = false
  authMode.value = 'login'
  authError.value = ''
}

function clearFilters() {
  statusFilter.value = 'all'
  personFilter.value = 'all'
  dueFilter.value = 'all'
}

function openProjectForm() {
  projectDraft.value = { name: '', code: '', description: '' }
  projectModalOpen.value = true
}

async function saveProject() {
  const name = projectDraft.value.name.trim()
  const code = projectDraft.value.code.trim().toUpperCase()
  if (!name || !code) return
  if (apiEnabled) {
    try {
      const response = await apiRequest('/projects', {
        method: 'POST',
        body: JSON.stringify({ name, key: code, description: projectDraft.value.description.trim() || null }),
      })
      await loadApiWorkspace()
      activeProjectId.value = String(response.data.id)
      projectModalOpen.value = false
      apiError.value = ''
    } catch (error) {
      apiError.value = error.message
    }
    return
  }
  const colors = ['coral', 'blue', 'green']
  const project = { id: `project-${Date.now()}`, name, code, color: colors[projects.length % colors.length], description: projectDraft.value.description.trim() }
  projects.push(project)
  activeProjectId.value = project.id
  currentView.value = 'board'
  projectModalOpen.value = false
}

async function selectProject(project) {
  activeProjectId.value = project.id
  currentView.value = 'board'
  mobileNavOpen.value = false
  if (apiEnabled) {
    try {
      const apiTasks = await loadApiTasks(project.id)
      tasks.value = [...tasks.value.filter((task) => task.projectId !== project.id), ...apiTasks]
    } catch (error) {
      apiError.value = error.message
    }
  }
}

function savePerson() {
  if (apiEnabled) {
    apiRequest(`/projects/${activeProjectId.value}/members`, {
      method: 'POST',
      body: JSON.stringify({ email: personDraft.value.email.trim(), role: personDraft.value.role }),
    }).then(loadApiWorkspace).then(() => {
      personModalOpen.value = false
      apiError.value = ''
    }).catch((error) => {
      apiError.value = error.message
    })
    return
  }
  const name = personDraft.value.name.trim()
  if (!name || people.some((member) => member.name.toLocaleLowerCase('es') === name.toLocaleLowerCase('es'))) return
  people.push({ name, role: personDraft.value.role, initials: initials(name), color: ['blue', 'green', 'gold', 'coral'][people.length % 4] })
  personModalOpen.value = false
}
</script>

<template>
  <section v-if="workspaceLoading" class="auth-screen auth-loading-screen" aria-live="polite">
    <div class="auth-loading-brand"><span class="brand-glyph"><PanelsTopLeft :size="19" /></span> Organizer</div>
    <div class="auth-loading-content"><span class="loading-mark"><i></i></span><strong>Preparando tu espacio</strong><span>Un momento, estamos sincronizando el equipo.</span></div>
    <span class="auth-version">ORGANIZER · ESPACIO DE TRABAJO</span>
  </section>

  <section v-else-if="!currentUser" class="auth-screen">
    <div class="auth-art" aria-hidden="true">
      <div class="auth-orbit orbit-one"></div>
      <div class="auth-orbit orbit-two"></div>
      <div class="auth-art-copy"><span class="eyebrow">TRABAJO CONECTADO</span><p>Las buenas ideas<br>llegan <em>más lejos</em><br>en equipo.</p><span class="auth-art-note">Un espacio claro para avanzar juntos.</span></div>
      <div class="auth-art-mark"><PanelsTopLeft :size="18" /> Organizer <span>WORKSPACE</span></div>
    </div>
    <div class="auth-form-side">
      <div class="auth-mobile-brand"><span class="brand-glyph"><PanelsTopLeft :size="18" /></span> Organizer</div>
      <form class="auth-form" @submit.prevent="finishLogin">
        <span class="eyebrow">{{ authMode === 'login' ? 'QUÉ BUENO VERTE' : 'EMPIEZA POR AQUÍ' }}</span>
        <h1>{{ authMode === 'login' ? 'Entra a tu espacio.' : 'Crea tu espacio.' }}</h1>
        <p class="auth-subtitle">{{ authMode === 'login' ? 'Tu equipo ya está en marcha.' : 'Organiza el trabajo desde el primer día.' }}</p>
        <p v-if="authError" class="form-error" role="alert">{{ authError }}</p>
        <label v-if="authMode === 'register'" class="field-label">Nombre completo<input v-model="authName" autocomplete="name" placeholder="Tu nombre" required></label>
        <label class="field-label">Correo electrónico<input v-model="authEmail" type="email" autocomplete="email" placeholder="nombre@empresa.com" required></label>
        <label class="field-label">Contraseña<input v-model="authPassword" type="password" autocomplete="current-password" placeholder="••••••••" required minlength="8"></label>
        <label v-if="authMode === 'register'" class="field-label">Repite la contraseña<input v-model="authPasswordConfirm" type="password" autocomplete="new-password" placeholder="••••••••" required minlength="8"></label>
        <button class="button button-primary auth-submit" type="submit">{{ authMode === 'login' ? 'Iniciar sesión' : 'Crear cuenta' }}<ArrowUpRight :size="17" /></button>
        <p class="auth-switch">{{ authMode === 'login' ? '¿Todavía no tienes cuenta?' : '¿Ya tienes una cuenta?' }} <button type="button" @click="authMode = authMode === 'login' ? 'register' : 'login'; authError = ''">{{ authMode === 'login' ? 'Regístrate' : 'Inicia sesión' }}</button></p>
        <div class="auth-trust"><ShieldCheck :size="16" /> Acceso seguro para tu equipo</div>
      </form>
      <span class="auth-version">ORGANIZER · ESPACIO DE TRABAJO</span>
    </div>
  </section>

  <div v-else class="app-shell">
    <div v-if="apiError" class="api-error" role="alert"><span>{{ apiError }}</span><button aria-label="Cerrar aviso" @click="apiError = ''"><X :size="16" /></button></div>
    <button v-if="mobileNavOpen" class="mobile-scrim" aria-label="Cerrar navegación" @click="mobileNavOpen = false"></button>
    <aside class="sidebar" :class="{ 'sidebar-open': mobileNavOpen }">
      <a class="brand" href="#" @click.prevent="currentView = 'dashboard'">
        <span class="brand-glyph"><PanelsTopLeft :size="17" /></span><span class="brand-name">Organizer</span><span class="brand-plan">TEAM</span>
      </a>

      <div class="workspace-switcher">
        <span class="workspace-avatar">S</span>
        <span class="workspace-info"><strong>Studio Norte</strong><small>Plan de equipo</small></span>
        <ChevronDown :size="15" />
      </div>

      <nav class="primary-nav" aria-label="Navegación principal">
        <span class="nav-caption">ESPACIO DE TRABAJO</span>
        <button class="nav-link" :class="{ selected: currentView === 'dashboard' }" @click="currentView = 'dashboard'; mobileNavOpen = false"><LayoutDashboard :size="17" /><span>Resumen</span><span class="nav-shortcut">⌘ 1</span></button>
        <button class="nav-link" :class="{ selected: currentView === 'board' }" @click="currentView = 'board'; personFilter = currentUser.name; mobileNavOpen = false"><CheckCheck :size="17" /><span>Mis tareas</span><span class="nav-count">{{ tasks.filter((task) => task.assignee === currentUser.name && task.status !== 'done').length }}</span></button>
        <button class="nav-link" :class="{ selected: currentView === 'team' }" @click="currentView = 'team'; mobileNavOpen = false"><Users :size="17" /><span>Equipo</span></button>
      </nav>

      <div class="project-section">
        <div class="section-heading"><span class="nav-caption">PROYECTOS</span><button v-if="currentUser.role === 'admin'" class="icon-button tiny" title="Nuevo proyecto" aria-label="Nuevo proyecto" @click="openProjectForm"><Plus :size="16" /></button></div>
        <button v-for="project in projects" :key="project.id" class="project-link" :class="{ 'project-selected': activeProjectId === project.id && currentView === 'board' }" @click="selectProject(project)">
          <span class="project-mark" :class="`tone-${project.color}`">{{ project.code }}</span><span>{{ project.name }}</span><span class="project-dot"></span>
        </button>
      </div>

      <div class="sidebar-bottom">
        <div class="capacity-box"><div class="capacity-title"><Sparkles :size="15" /><span>Ritmo del equipo</span></div><div class="capacity-meter"><span></span></div><div class="capacity-caption"><span>Semana 41</span><strong>78% capacidad</strong></div></div>
        <button class="profile-row" @click="menuOpen = !menuOpen"><span class="avatar avatar-coral">{{ initials(currentUser.name) }}</span><span class="profile-info"><strong>{{ currentUser.name }}</strong><small>{{ canAdminProject ? 'Administradora' : 'Miembro' }}</small></span><MoreHorizontal :size="18" /></button>
      </div>
    </aside>

    <div class="main-shell">
      <header class="topbar">
        <div class="mobile-brand"><button class="icon-button mobile-menu-button" aria-label="Abrir navegación" @click="mobileNavOpen = true"><span class="menu-lines"></span></button><span class="brand-glyph"><PanelsTopLeft :size="16" /></span></div>
        <div class="breadcrumbs"><span>Studio Norte</span><span class="crumb-divider">/</span><strong>{{ currentView === 'board' ? activeProject?.name || 'Mis proyectos' : viewTitle }}</strong></div>
        <div class="topbar-actions">
          <label class="top-search"><Search :size="16" /><input v-model="searchQuery" placeholder="Buscar tareas..." aria-label="Buscar tareas"><kbd>⌘ K</kbd></label>
          <button class="icon-button notification-button" title="Notificaciones" aria-label="Notificaciones"><Bell :size="18" /><i></i></button>
          <div class="top-profile-wrap"><button class="top-avatar avatar avatar-coral" aria-label="Abrir menú de usuario" @click="menuOpen = !menuOpen">{{ initials(currentUser.name) }}</button>
            <div v-if="menuOpen" class="account-menu"><div class="account-heading"><strong>{{ currentUser.name }}</strong><small>{{ currentUser.email }}</small></div><button v-if="!apiEnabled" @click="currentUser.role = currentUser.role === 'admin' ? 'member' : 'admin'; menuOpen = false"><ShieldCheck :size="15" /> Cambiar a {{ currentUser.role === 'admin' ? 'miembro' : 'admin' }} <span class="demo-pill">DEMO</span></button><button @click="signOut"><LogOut :size="15" /> Cerrar sesión</button></div>
          </div>
        </div>
      </header>

      <main class="page-content">
        <section v-if="!projects.length" class="empty-workspace"><span class="empty-workspace-mark"><PanelsTopLeft :size="20" /></span><span class="eyebrow">TU SIGUIENTE PASO</span><h1>Aquí empieza el trabajo en equipo<span class="heading-period">.</span></h1><p>Crea un proyecto para organizar tareas, fechas y responsables.</p><button v-if="currentUser.role === 'admin'" class="button button-primary" @click="openProjectForm"><Plus :size="17" /> Crear proyecto</button><span v-else class="empty-member-note">Pide a una persona administradora que te añada a un proyecto.</span></section>
        <template v-else-if="currentView === 'board'">
          <section class="page-heading board-heading">
            <div><div class="project-kicker"><span class="project-mark" :class="`tone-${activeProject.color}`">{{ activeProject.code }}</span><span>PROYECTO</span><span class="kicker-divider">·</span><span>{{ projectTasks.length }} tareas</span></div><h1>{{ activeProject.name }}<button class="title-menu icon-button tiny" aria-label="Opciones del proyecto"><MoreHorizontal :size="20" /></button></h1><p>{{ activeProject.description }}</p></div>
            <div class="heading-actions"><button class="button button-secondary" @click="currentView = 'dashboard'"><ArrowUpRight :size="16" /> Ver resumen</button><button class="button button-primary" @click="openNewTask()"><Plus :size="17" /> Nueva tarea</button></div>
          </section>
          <section class="board-toolbar" aria-label="Filtros del tablero">
            <div class="view-toggle"><button class="view-tab active"><CheckCheck :size="15" /> Tablero</button><button class="view-tab" @click="currentView = 'dashboard'"><Activity :size="15" /> Actividad</button></div>
            <div class="filter-controls"><div class="assignee-stack" aria-label="Miembros del proyecto"><button v-for="member in people.slice(0, 4)" :key="member.name" class="avatar avatar-small" :class="`avatar-${member.color}`" :title="member.name" @click="personFilter = personFilter === member.name ? 'all' : member.name">{{ member.initials }}</button><button class="avatar avatar-small avatar-add" title="Ver equipo" @click="currentView = 'team'"><Plus :size="13" /></button></div>
              <span class="toolbar-separator"></span><button class="filter-button" :class="{ 'filter-active': filtersOpen || activeFiltersCount }" @click="filtersOpen = !filtersOpen"><SlidersHorizontal :size="15" /> Filtros<span v-if="activeFiltersCount" class="filter-count">{{ activeFiltersCount }}</span></button><button class="icon-button tiny" title="Ordenar tareas"><ArrowDown :size="16" /></button>
            </div>
          </section>
          <section v-if="filtersOpen" class="filter-panel">
            <label>Estado<select v-model="statusFilter"><option value="all">Todos los estados</option><option value="todo">Pendiente</option><option value="progress">En curso</option><option value="done">Hecho</option></select></label>
            <label>Responsable<select v-model="personFilter"><option value="all">Todo el equipo</option><option v-for="member in people" :key="member.name" :value="member.name">{{ member.name }}</option></select></label>
            <label>Fecha límite<select v-model="dueFilter"><option value="all">Cualquier fecha</option><option value="week">Próximos 7 días</option><option value="overdue">Vencidas</option></select></label>
            <button v-if="activeFiltersCount" class="clear-filters" @click="clearFilters">Limpiar filtros</button>
          </section>

          <section class="board-grid" aria-label="Tablero de tareas">
            <div v-for="column in columns" :key="column.id" class="board-column" @dragover.prevent @drop.prevent="moveTask(draggedTaskId, column.id)">
              <div class="column-heading"><div class="column-name"><span class="column-indicator" :class="`indicator-${column.tone}`"><component :is="column.icon" :size="13" /></span><h2>{{ column.label }}</h2><span class="column-count">{{ tasksInColumn(column.id).length }}</span></div><button class="icon-button tiny" title="Añadir tarea" :aria-label="`Añadir tarea a ${column.label}`" @click="openNewTask(column.id)"><Plus :size="17" /></button></div>
              <div class="task-list">
                <article v-for="task in tasksInColumn(column.id)" :key="task.id" class="task-card" :class="{ 'task-dragging': draggedTaskId === task.id }" draggable="true" @dragstart="startDrag($event, task)" @dragend="draggedTaskId = null" @click="openTask(task)">
                  <div class="task-card-top"><span class="task-id">{{ task.id }}</span><button class="icon-button task-more" :aria-label="`Opciones de ${task.title}`" @click.stop="openTask(task)"><MoreHorizontal :size="17" /></button></div>
                  <h3>{{ task.title }}</h3><p v-if="task.description" class="task-description">{{ task.description }}</p>
                  <div class="task-tags"><span v-for="label in task.labels.slice(0, 2)" :key="label" class="task-tag">{{ label }}</span><span class="priority-label" :class="`priority-${task.priority}`"><i></i>{{ task.priority === 'high' ? 'Alta' : task.priority === 'medium' ? 'Media' : 'Baja' }}</span></div>
                  <div class="task-card-footer"><span class="due-date" :class="{ overdue: isOverdue(task) }"><CalendarDays :size="14" />{{ dueText(task) }}</span><span v-if="task.comments.length" class="comment-count"><MessageCircle :size="14" />{{ task.comments.length }}</span><span class="avatar avatar-small task-owner" :class="`avatar-${person(task.assignee).color}`" :title="task.assignee">{{ person(task.assignee).initials }}</span></div>
                </article>
                <button class="add-card-button" @click="openNewTask(column.id)"><Plus :size="15" /> Añadir tarea</button>
                <div v-if="!tasksInColumn(column.id).length" class="empty-column">Suelta una tarea aquí</div>
              </div>
            </div>
          </section>
          <footer class="board-footer"><span><span class="footer-live-dot"></span> Actualizado ahora</span><span>{{ boardTasks.length }} de {{ projectTasks.length }} tareas</span><button v-if="searchQuery || activeFiltersCount" @click="searchQuery = ''; clearFilters()">Quitar filtros</button></footer>
        </template>

        <template v-else-if="currentView === 'dashboard'">
          <section class="page-heading dashboard-heading"><div><div class="project-kicker"><span class="eyebrow-dot"></span> VIERNES, 9 DE OCTUBRE</div><h1>Un equipo en movimiento<span class="heading-period">.</span></h1><p>Esto es lo que pasa en Studio Norte esta semana.</p></div><button class="button button-primary" @click="currentView = 'board'; openNewTask()"><Plus :size="17" /> Nueva tarea</button></section>
          <section class="metric-grid">
            <article class="metric-card metric-main"><span class="metric-label">Tareas activas</span><strong>{{ activeCount }}</strong><span class="metric-foot"><span class="metric-trend"><ArrowUpRight :size="13" /> 12%</span> respecto a la semana pasada</span><div class="metric-decoration"><span></span><span></span><span></span><span></span><span></span><span></span><span></span></div></article>
            <article class="metric-card"><span class="metric-label">Completadas</span><strong>{{ doneCount }}<small> / {{ projectTasks.length }}</small></strong><div class="metric-progress"><span :style="{ width: `${projectTasks.length ? Math.round(doneCount / projectTasks.length * 100) : 0}%` }"></span></div><span class="metric-foot">{{ projectTasks.length ? Math.round(doneCount / projectTasks.length * 100) : 0 }}% del proyecto</span></article>
            <article class="metric-card"><span class="metric-label">Fuera de plazo</span><strong :class="{ 'metric-alert': overdueCount }">{{ overdueCount }}</strong><span class="metric-foot">{{ overdueCount ? 'Necesitan atención' : 'Todo bajo control' }}</span><span class="metric-icon metric-icon-orange"><Clock3 :size="19" /></span></article>
            <article class="metric-card"><span class="metric-label">Personas activas</span><strong>{{ people.length }}</strong><span class="metric-foot">En {{ projects.length }} proyectos</span><span class="metric-icon metric-icon-green"><Users :size="19" /></span></article>
          </section>
          <section class="dashboard-panels">
            <div class="dashboard-panel status-panel"><div class="panel-heading"><div><span class="eyebrow">VISTA GENERAL</span><h2>Trabajo por estado</h2></div><button class="icon-button tiny" title="Abrir tablero" @click="currentView = 'board'"><ArrowUpRight :size="17" /></button></div><div class="status-chart"><div v-for="column in columns" :key="column.id" class="status-row"><div class="status-row-label"><span class="column-indicator" :class="`indicator-${column.tone}`"><component :is="column.icon" :size="12" /></span><span>{{ column.label }}</span><strong>{{ projectTasks.filter((task) => task.status === column.id).length }}</strong></div><div class="status-track"><span :class="`bar-${column.tone}`" :style="{ width: `${projectTasks.length ? Math.max(projectTasks.filter((task) => task.status === column.id).length / projectTasks.length * 100, 3) : 0}%` }"></span></div></div></div><div class="chart-legend"><span><i class="legend-dot dot-orange"></i>En progreso</span><span><i class="legend-dot dot-green"></i>Completado</span></div></div>
            <div class="dashboard-panel workload-panel"><div class="panel-heading"><div><span class="eyebrow">CARGA DE TRABAJO</span><h2>Por persona</h2></div><button class="text-button" @click="currentView = 'team'">Ver equipo <ArrowUpRight :size="14" /></button></div><div class="workload-list"><div v-for="member in people" :key="member.name" class="workload-row"><span class="avatar avatar-small" :class="`avatar-${member.color}`">{{ member.initials }}</span><div class="workload-person"><strong>{{ member.name }}</strong><small>{{ tasks.filter((task) => task.assignee === member.name && task.status !== 'done').length }} tareas abiertas</small></div><div class="workload-bar"><span :class="`bar-${member.color}`" :style="{ width: `${Math.min(100, tasks.filter((task) => task.assignee === member.name && task.status !== 'done').length * 25 + 18)}%` }"></span></div><span class="workload-percent">{{ Math.min(100, tasks.filter((task) => task.assignee === member.name && task.status !== 'done').length * 25 + 18) }}%</span></div></div></div>
          </section>
          <section class="dashboard-panel due-panel"><div class="panel-heading"><div><span class="eyebrow">PRÓXIMOS HITOS</span><h2>Lo que viene</h2></div><button class="text-button" @click="currentView = 'board'">Ver tablero <ArrowUpRight :size="14" /></button></div><div class="upcoming-list"><button v-for="task in [...projectTasks].filter((item) => item.status !== 'done' && item.dueDate).sort((a, b) => a.dueDate.localeCompare(b.dueDate)).slice(0, 3)" :key="task.id" class="upcoming-row" @click="currentView = 'board'; openTask(task)"><span class="upcoming-date"><strong>{{ new Date(`${task.dueDate}T12:00:00`).getDate() }}</strong><small>{{ new Intl.DateTimeFormat('es-ES', { month: 'short' }).format(new Date(`${task.dueDate}T12:00:00`)).replace('.', '') }}</small></span><span class="upcoming-copy"><strong>{{ task.title }}</strong><small>{{ task.id }} <span>·</span> {{ task.assignee }}</small></span><span class="priority-label" :class="`priority-${task.priority}`"><i></i>{{ task.priority === 'high' ? 'Alta' : task.priority === 'medium' ? 'Media' : 'Baja' }}</span><ArrowUpRight class="upcoming-arrow" :size="16" /></button></div></section>
        </template>

        <template v-else>
          <section class="page-heading team-heading"><div><div class="project-kicker"><Users :size="15" /> TU ESPACIO</div><h1>Un equipo, muchas ideas<span class="heading-period">.</span></h1><p>Personas y proyectos que hacen avanzar Studio Norte.</p></div><button v-if="canAdminProject" class="button button-primary" @click="personDraft = { name: '', email: '', role: apiEnabled ? 'member' : 'Desarrollo' }; personModalOpen = true"><Plus :size="17" /> Añadir persona</button></section>
          <section class="team-grid"><article v-for="member in people" :key="member.name" class="team-card"><span class="avatar avatar-large" :class="`avatar-${member.color}`">{{ member.initials }}</span><span class="team-role">{{ member.role }}</span><h2>{{ member.name }}</h2><p>{{ tasks.filter((task) => task.assignee === member.name && task.status !== 'done').length }} tareas abiertas</p><div class="team-card-bottom"><div class="team-projects"><span v-for="project in projects.filter((item) => tasks.some((task) => task.projectId === item.id && task.assignee === member.name))" :key="project.id" class="project-mark" :class="`tone-${project.color}`" :title="project.name">{{ project.code }}</span></div><span class="team-presence"><i></i> Activo</span></div></article></section>
        </template>
      </main>
    </div>

    <div v-if="projectModalOpen" class="modal-backdrop" @click.self="projectModalOpen = false">
      <section class="task-modal compact-modal" role="dialog" aria-modal="true" aria-label="Nuevo proyecto"><header class="modal-header"><span class="modal-task-id">NUEVO PROYECTO</span><button class="icon-button" aria-label="Cerrar" @click="projectModalOpen = false"><X :size="19" /></button></header><form class="task-form" @submit.prevent="saveProject"><h2 class="small-modal-title">Crea un espacio para el trabajo.</h2><label class="field-label">Nombre del proyecto<input v-model="projectDraft.name" placeholder="Ej. Atlas mobile" required maxlength="80"></label><label class="field-label">Código corto<input v-model="projectDraft.code" placeholder="AT" required maxlength="8"></label><label class="field-label">Descripción<input v-model="projectDraft.description" placeholder="Qué quiere conseguir el equipo" maxlength="160"></label><footer class="modal-footer"><span></span><div><button type="button" class="button button-secondary" @click="projectModalOpen = false">Cancelar</button><button class="button button-primary" type="submit"><Plus :size="15" /> Crear proyecto</button></div></footer></form></section>
    </div>

    <div v-if="personModalOpen" class="modal-backdrop" @click.self="personModalOpen = false">
      <section class="task-modal compact-modal" role="dialog" aria-modal="true" aria-label="Añadir persona"><header class="modal-header"><span class="modal-task-id">NUEVA PERSONA</span><button class="icon-button" aria-label="Cerrar" @click="personModalOpen = false"><X :size="19" /></button></header><form class="task-form" @submit.prevent="savePerson"><h2 class="small-modal-title">Añade a alguien al equipo.</h2><label v-if="apiEnabled" class="field-label">Correo electrónico<input v-model="personDraft.email" type="email" placeholder="nombre@empresa.com" required></label><label v-else class="field-label">Nombre completo<input v-model="personDraft.name" placeholder="Nombre y apellidos" required maxlength="80"></label><label class="field-label">{{ apiEnabled ? 'Rol en el proyecto' : 'Área' }}<select v-model="personDraft.role"><template v-if="apiEnabled"><option value="member">Miembro</option><option value="admin">Admin</option></template><template v-else><option>Producto</option><option>Diseño</option><option>Desarrollo</option></template></select></label><footer class="modal-footer"><span></span><div><button type="button" class="button button-secondary" @click="personModalOpen = false">Cancelar</button><button class="button button-primary" type="submit"><Plus :size="15" /> Añadir persona</button></div></footer></form></section>
    </div>

    <div v-if="taskModalOpen && selectedTask" class="modal-backdrop" @click.self="taskModalOpen = false" @keydown.esc="taskModalOpen = false">
      <section class="task-modal" role="dialog" aria-modal="true" :aria-label="selectedTask.id ? 'Editar tarea' : 'Nueva tarea'">
        <header class="modal-header"><span class="modal-task-id">{{ selectedTask.id || 'NUEVA TAREA' }}</span><button class="icon-button" aria-label="Cerrar" @click="taskModalOpen = false"><X :size="19" /></button></header>
        <form class="task-form" @submit.prevent="saveTask"><input v-model="selectedTask.title" class="task-title-input" placeholder="Nombre de la tarea" aria-label="Nombre de la tarea" required maxlength="120"><textarea v-model="selectedTask.description" class="task-description-input" placeholder="Añade una descripción..." rows="3"></textarea>
          <div class="task-fields"><label>Estado<select v-model="selectedTask.status"><option value="todo">Pendiente</option><option value="progress">En curso</option><option value="done">Hecho</option></select></label><label>Prioridad<select v-model="selectedTask.priority"><option value="high">Alta</option><option value="medium">Media</option><option value="low">Baja</option></select></label><label>Responsable<select v-model="selectedTask.assignee" :disabled="!canAdminProject"><option v-for="member in people" :key="member.name" :value="member.name">{{ member.name }}</option></select></label><label>Fecha límite<input v-model="selectedTask.dueDate" type="date"></label></div>
          <div class="comments-section"><div class="comments-heading"><h3>Comentarios</h3><span>{{ selectedTask.comments.length }}</span></div><div v-if="selectedTask.comments.length" class="comment-list"><div v-for="(comment, index) in selectedTask.comments" :key="`${comment.author}-${index}`" class="comment-row"><span class="avatar avatar-small" :class="`avatar-${person(comment.author).color}`">{{ initials(comment.author) }}</span><div><div class="comment-meta"><strong>{{ comment.author }}</strong><span>{{ comment.time }}</span></div><p>{{ comment.text }}</p></div></div></div><div v-else class="no-comments">Todavía no hay comentarios.</div><div class="comment-composer"><span class="avatar avatar-small avatar-coral">{{ initials(currentUser.name) }}</span><input v-model="commentDraft" placeholder="Escribe un comentario..." aria-label="Escribe un comentario" @keydown.enter.prevent="addComment"><button type="button" class="icon-button send-button" aria-label="Añadir comentario" :disabled="!commentDraft.trim()" @click="addComment"><Send :size="16" /></button></div></div>
          <footer class="modal-footer"><span class="modal-updated"><Clock3 :size="14" /> Se guarda al confirmar</span><div><button type="button" class="button button-secondary" @click="taskModalOpen = false">Cancelar</button><button class="button button-primary" type="submit"><Check :size="16" /> Guardar tarea</button></div></footer>
        </form>
      </section>
    </div>
  </div>
</template>
