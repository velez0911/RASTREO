const API_URL = 'http://localhost/gym_app/api.php';

// Registrar un nuevo usuario
async function registrarUsuario(nombre, email, password) {
    const res = await fetch(`${API_URL}?accion=registro`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ nombre, email, password })
    });
    const data = await res.json();
    if (data.status === 'success') {
        localStorage.setItem('usuarioActivo', JSON.stringify(data.user));
        alert('Registro exitoso. ¡Bienvenido!');
    } else {
        alert('Error: ' + data.message);
    }
}

// Iniciar sesión
async function iniciarSesion(email, password) {
    const res = await fetch(`${API_URL}?accion=login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password })
    });
    const data = await res.json();
    if (data.status === 'success') {
        localStorage.setItem('usuarioActivo', JSON.stringify(data.user));
        cargarRegistrosUsuario();
    } else {
        alert('Error: ' + data.message);
    }
}

// Cargar registros filtrados por el ID del usuario actual
async function cargarRegistrosUsuario() {
    const usuario = JSON.parse(localStorage.getItem('usuarioActivo'));
    if (!usuario) return;

    const res = await fetch(`${API_URL}?accion=registros&usuario_id=${usuario.id}`);
    const registros = await res.json();
    
    // Renderizar los registros en la interfaz
    console.log("Registros del usuario:", registros);
}

// Cerrar sesión
function cerrarSesion() {
    localStorage.removeItem('usuarioActivo');
    window.location.reload();
}