// ==================== 1. Sesión, almacenamiento y utilidades ====================
let appUser=null,csrfToken=null;
const $=(selector,root=document)=>root.querySelector(selector);
const esc=value=>String(value??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
// Persistencia central en PHP; el navegador conserva el carrito temporal.
// Cliente HTTP del backend PHP. Toda mutación envía el token CSRF de la sesión.
async function api(path, options = {}) {
  const method = (options.method || 'GET').toUpperCase();
  const endpoint = path.startsWith('api/') ? path : `api/${path}`;
  const headers = new Headers(options.headers || {});
  headers.set('Accept', 'application/json');

  if (options.body && !(options.body instanceof FormData) && typeof options.body !== 'string') {
    options.body = JSON.stringify(options.body);
  }
  if (options.body && !(options.body instanceof FormData)) {
    headers.set('Content-Type', 'application/json');
  }
  if (!['GET', 'HEAD'].includes(method) && csrfToken) {
    headers.set('X-CSRF-Token', csrfToken);
  }

  let response;
  try {
    response = await fetch(endpoint, {
      ...options,
      method,
      headers,
      credentials: 'same-origin'
    });
  } catch (error) {
    throw new Error('No hay conexión con el servidor. Revisa que Apache esté encendido.');
  }

  const result = response.status === 204 ? {} : await response.json().catch(() => ({}));
  if (!response.ok) {
    if (response.status === 401 && !location.pathname.endsWith('/login.html')) {
      const role = appUser?.rol || 'CLIENTE';
      location.href = `login.html?rol=${role}&mensaje=sesion`;
    }
    throw new Error(result.error || 'No fue posible completar la solicitud.');
  }
  return result;
}
// ==================== 2. Inicio de interfaz y componentes compartidos ====================
function message(target, text, isError = false) {
  if (!target) return;
  target.textContent = text;
  target.classList.toggle('error', isError);
  target.classList.toggle('success', !isError);
}

// Agrega títulos accesibles a las celdas cuando una tabla pasa a tarjetas en móvil.
function labelResponsiveTables() {
  document.querySelectorAll('table').forEach(table => {
    const labels = [...table.querySelectorAll('thead th')]
      .map(cell => cell.textContent.trim());

    table.querySelectorAll('tbody tr').forEach(row => {
      [...row.children].forEach((cell, index) => {
        if (cell.tagName === 'TD' && !cell.hasAttribute('colspan')) {
          cell.dataset.label = labels[index] || '';
        }
      });
    });
  });
}

// Inicializa las piezas que están presentes en cada página sin duplicar lógica.
async function init() {
  renderShell();
  labelResponsiveTables();
  new MutationObserver(labelResponsiveTables).observe(document.body, {
    childList: true,
    subtree: true
  });

  try {
    const session = await api('auth.php');
    appUser = session.user;
    csrfToken = session.csrf;
  } catch (error) {
    console.error('No fue posible consultar la sesión local de PanelApp:', error);
  }

  renderNav();
  const authForm = $('[data-auth-form]');
  if (authForm) {
    setupLoginRole(authForm);
    bindAuth();
  }

  if ($('[data-product-list]')) loadProducts();
  if ($('[data-dashboard]')) loadDashboard();
  if ($('[data-client-table]')) {
    if ($('[data-client-form]')) bindClientForm();
    loadClients();
  }
  if ($('[data-order-table]')) loadOrders();
  if ($('[data-payment-table]')) loadPayments();
  if ($('[data-delivery-table]')) loadDeliveries();
  if ($('[data-contact-form]')) bindContact();
}
// En el inicio de sesión se elige primero el rol; el registro conserva su propio selector.
function setupLoginRole(form) {
  if (form.dataset.action !== 'login') return;

  const params = new URLSearchParams(location.search);
  const role = params.get('rol');
  const validRole = ['CLIENTE', 'ADMIN'].includes(role);
  const choices = $('[data-role-choices]');
  const caption = $('[data-login-role]');

  form.dataset.expectedRole = validRole ? role : '';
  form.hidden = !validRole;
  if (choices) choices.hidden = validRole;
  if (caption && validRole) {
    caption.textContent = role === 'ADMIN' ? 'Acceso de administrador' : 'Acceso de cliente';
  }
  if (params.get('mensaje') === 'sesion' && validRole) {
    message($('.notice', form), 'Tu sesión terminó. Ingresa nuevamente.', true);
  }
}

// Inserta encabezado y pie compartidos para que todas las páginas mantengan el mismo estilo.
function renderShell() {
  const header = $('[data-site-header]');
  if (header) {
    header.className = 'site-header';
    header.innerHTML = `
      <div class="container nav">
        <a class="brand" href="index.html" aria-label="Panela Oro Miel, inicio">
          <img class="brand-logo" src="img/logo.png" alt="Logo Panela Oro Miel"
            onload="this.nextElementSibling.hidden=true" onerror="this.hidden=true">
          <span class="brand-mark" aria-hidden="true">P</span>
          <span class="brand-name">Panela Oro Miel<small>Tradición colombiana</small></span>
        </a>
        <button class="menu-toggle" aria-label="Abrir menú" aria-expanded="false"
          aria-controls="primary-navigation">☰ <span>Menú</span></button>
        <nav class="nav-links" id="primary-navigation" aria-label="Navegación principal">
          <a href="index.html">Inicio</a>
          <a href="nosotros.html">Nosotros</a>
          <a href="mision-vision.html">Misión y visión</a>
          <a href="productos.html">Productos</a>
          <a href="contacto.html">Contacto</a>
          <a href="panelapp.html">PanelApp</a>
          <span data-user-links></span>
        </nav>
      </div>`;
  }

  const footer = $('[data-site-footer]');
  if (footer) {
    footer.className = 'site-footer';
    footer.innerHTML = `
      <div class="container">
        <div class="footer-grid">
          <div><strong>Panela Oro Miel S.A.S.</strong><p>Panela colombiana con tradición y compromiso.</p></div>
          <div><strong>Visítanos</strong><p>Puente Aranda · Bogotá, Colombia</p>
            <p><a href="tel:+573178852768">317 885 2768</a> · <a href="tel:+573115366702">311 536 6702</a></p></div>
          <div><strong>¿Necesitas ayuda?</strong>
            <p><a href="mailto:panelapp2016@gmail.com">panelapp2016@gmail.com</a></p>
            <p><a href="contacto.html">Contáctanos</a></p></div>
        </div>
        <small>© Panela Oro Miel S.A.S. · PanelApp, gestión de pedidos.</small>
      </div>`;
  }

  $('.menu-toggle')?.addEventListener('click', event => {
    const links = $('.nav-links');
    const isOpen = links.classList.toggle('open');
    event.currentTarget.setAttribute('aria-expanded', String(isOpen));
    event.currentTarget.setAttribute('aria-label', isOpen ? 'Cerrar menú' : 'Abrir menú');
  });
}
// ==================== 3. Acceso, navegación y sesión ====================
// Construye las opciones de navegación según el rol activo.
function renderNav() {
  const userLinks = $('[data-user-links]');
  if (!userLinks) return;

  if (!appUser) {
    userLinks.innerHTML = '<a class="button small-button nav-login" href="login.html">Ingresar</a>';
    return;
  }

  const adminLinks = appUser.rol === 'ADMIN'
    ? '<a href="dashboard.html">Panel</a><a href="productos.html">Productos</a><a href="pedidos.html">Pedidos</a><a href="clientes.html">Clientes</a>'
    : '<a href="productos.html">Catálogo</a><a href="pedidos.html">Mis pedidos</a>';

  userLinks.innerHTML = `${adminLinks}
    <a href="pagos.html">Pagos</a>
    <a href="entregas.html">${appUser.rol === 'ADMIN' ? 'Entregas' : 'Seguimiento'}</a>
    <button class="button small-button" data-logout>Salir</button>`;

  $('[data-logout]')?.addEventListener('click', logout);
}
async function logout() {
  try {
    await api('auth.php', { method: 'DELETE' });
  } finally {
    location.href = 'index.html';
  }
}

// Registro local o inicio de sesión; al registrarse abre la vista inicial de su rol.
function bindAuth() {
  const form = $('[data-auth-form]');

  form.addEventListener('submit', async event => {
    event.preventDefault();
    const formData = Object.fromEntries(new FormData(form));
    const notice = $('.notice', form);
    const expectedRole = form.dataset.expectedRole;
    formData.action = form.dataset.action;

    try {
      let result = await api('auth.php', {
        method: 'POST',
        body: JSON.stringify(formData)
      });

      if (formData.action === 'register') {
        result = await api('auth.php', {
          method: 'POST',
          body: JSON.stringify({ ...formData, action: 'login' })
        });
      }

      csrfToken = result.csrf;

      if (expectedRole && result.user.rol !== expectedRole) {
        await api('auth.php', { method: 'DELETE' });
        throw new Error(
          `Esta cuenta no es de ${expectedRole === 'ADMIN' ? 'administrador' : 'cliente'}. Elige el acceso que corresponde a tu cuenta.`
        );
      }

      appUser = result.user;
      location.href = appUser.rol === 'ADMIN' ? 'dashboard.html' : 'productos.html';
    } catch (error) {
      message(notice, error.message, true);
    }
  });
}
// ==================== 4. Catálogo y CRUD de productos ====================
function currency(value) {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0
  }).format(Number(value) || 0);
}

// Lee el catálogo y muestra controles CRUD solo para una sesión de administrador.
async function loadProducts() {
  const productList = $('[data-product-list]');
  const adminPanel = $('[data-product-admin]');
  const productTable = $('[data-product-table]');

  try {
    const products = await api('productos.php');
    const catalogProducts = products.filter(product => product.estado === 'ACTIVO');
    if (productList) {
      productList.innerHTML = catalogProducts.length
        ? catalogProducts.map(product => `
          <article class="product-card">
            ${product.imagen
              ? `<img src="${esc(product.imagen)}" alt="${esc(product.nombre)}"
                   onerror="this.hidden=true;this.nextElementSibling.hidden=false">
                 <div class="image-placeholder" hidden role="img" aria-label="Imagen no disponible"></div>`
              : '<div class="image-placeholder" role="img" aria-label="Imagen no disponible"></div>'}
            <div class="pad">
              <h2>${esc(product.nombre)}</h2>
              <p>${esc(product.descripcion)}</p>
              <strong>${currency(product.precio)}</strong>
              <p>${(product.disponible ?? Number(product.stock) > 0) ? 'Disponible' : 'Agotado'}</p>
              ${appUser?.rol === 'CLIENTE' && (product.disponible ?? Number(product.stock) > 0)
                ? `<button class="button" data-add="${product.id}">Agregar al carrito</button>`
                : ''}
            </div>
          </article>`).join('')
        : '<p>Aún no hay productos publicados. El administrador puede agregarlos desde Gestionar catálogo.</p>';
    }

    if (adminPanel && appUser?.rol === 'ADMIN') {
      adminPanel.hidden = false;
      fillProductTable(products, productTable);
      bindProductForm();
    }

    productList?.querySelectorAll('[data-add]').forEach(button => {
      button.addEventListener('click', () => addToCart(Number(button.dataset.add)));
    });
  } catch (error) {
    console.error('Error al cargar productos:', error);
    if (productList) productList.textContent = error.message;
    if (adminPanel && appUser?.rol === 'ADMIN') {
      message($('.notice', adminPanel), error.message, true);
    }
  }
}
// CRUD de productos: muestra registros y conecta las acciones de editar/desactivar.
function fillProductTable(products, table) {
  if (!table) return;

  table.innerHTML = products.length
    ? products.map(product => `
      <tr>
        <td>${esc(product.nombre)}</td>
        <td>${currency(product.precio)}</td>
        <td>${Number(product.stock)}</td>
        <td>${esc(product.estado)}</td>
        <td>
          <div class="table-actions">
            <button type="button" class="button small-button" data-edit="${Number(product.id)}">Editar</button>
            ${product.estado === 'ACTIVO'
              ? `<button type="button" class="button button-light small-button" data-disable="${Number(product.id)}">Desactivar</button>`
              : ''}
          </div>
        </td>
      </tr>`).join('')
    : '<tr><td colspan="5">No hay productos registrados. Crea el primero desde el formulario.</td></tr>';

  table.querySelectorAll('[data-edit]').forEach(button => {
    button.addEventListener('click', () => {
      const product = products.find(item => Number(item.id) === Number(button.dataset.edit));
      const form = $('[data-product-form]');
      if (!product || !form) return;

      form.reset();
      for (const field of ['id', 'nombre', 'descripcion', 'precio', 'stock', 'estado']) {
        form.elements[field].value = product[field] ?? '';
      }
      message($('.notice', form), '');
      form.scrollIntoView({ behavior: 'smooth' });
      form.elements.nombre.focus();
    });
  });

  table.querySelectorAll('[data-disable]').forEach(button => {
    button.addEventListener('click', async () => {
      if (!confirm('¿Desactivar este producto? Dejará de aparecer en el catálogo.')) return;
      button.disabled = true;
      try {
        await api(`productos.php?id=${button.dataset.disable}`, { method: 'DELETE' });
        await loadProducts();
        message($('.notice', $('[data-product-form]')), 'Producto desactivado correctamente.');
      } catch (error) {
        message($('.notice', $('[data-product-form]')), error.message, true);
      } finally {
        button.disabled = false;
      }
    });
  });
}
function bindProductForm() {
  const form = $('[data-product-form]');
  if (form.dataset.bound === 'true') return;
  form.dataset.bound = 'true';

  const submitButton = form.querySelector('[type="submit"]');
  const notice = $('.notice', form);
  form.addEventListener('reset', () => message(notice, ''));

  form.addEventListener('submit', async event => {
    event.preventDefault();
    const isEditing = Boolean(form.elements.id.value);
    submitButton.disabled = true;

    try {
      const formData = new FormData(form);
      const imageFile = formData.get('imagen_file');
      let imageData = '';

      if (imageFile instanceof File && imageFile.size) {
        const upload = new FormData();
        upload.append('imagen', imageFile);
        const result = await api('subir-imagen.php', { method: 'POST', body: upload });
        if (!result?.imagen) throw new Error('No se pudo cargar la imagen del producto.');
        imageData = result.imagen;
      }

      const product = {
        nombre: String(formData.get('nombre') || '').trim(),
        descripcion: String(formData.get('descripcion') || '').trim(),
        precio: Number(formData.get('precio')),
        stock: Number(formData.get('stock')),
        estado: String(formData.get('estado') || 'ACTIVO')
      };
      if (imageData) product.imagen = imageData;

      const path = isEditing
        ? `productos.php?id=${encodeURIComponent(form.elements.id.value)}`
        : 'productos.php';
      await api(path, {
        method: isEditing ? 'PUT' : 'POST',
        body: JSON.stringify(product)
      });

      form.reset();
      await loadProducts();
      message(notice, isEditing ? 'Producto actualizado correctamente.' : 'Producto creado correctamente.');
    } catch (error) {
      console.error('Error al guardar producto:', error);
      message(notice, error.message || 'No fue posible guardar el producto.', true);
    } finally {
      submitButton.disabled = false;
    }
  });
}
// ==================== 5. Carrito, pedidos, pagos y seguimiento ====================
// El carrito vive en el navegador; no confirma pagos ni envía la orden a un servidor.
function addToCart(productId) {
  const cart = JSON.parse(localStorage.getItem('panelapp_cart') || '[]');
  const existingItem = cart.find(item => item.producto_id === productId);
  if (existingItem) existingItem.cantidad += 1;
  else cart.push({ producto_id: productId, cantidad: 1 });
  localStorage.setItem('panelapp_cart', JSON.stringify(cart));

  let toast = $('[data-toast]');
  if (!toast) {
    toast = document.createElement('div');
    toast.className = 'toast success';
    toast.dataset.toast = '';
    toast.setAttribute('role', 'status');
    document.body.append(toast);
  }
  toast.textContent = 'Producto agregado al carrito. Puedes revisar tu compra en “Mis pedidos”.';
  toast.classList.add('visible');
  clearTimeout(window.panelToastTimer);
  window.panelToastTimer = setTimeout(() => toast.classList.remove('visible'), 4200);
}

// ==================== 5. Panel, clientes, pedidos, pagos y entregas ====================
// Construye indicadores administrativos a partir de los datos locales.
async function loadDashboard() {
  const dashboard = $('[data-dashboard]');
  try {
    const data = await api('dashboard.php');
    const stats = {
      'Ventas confirmadas': currency(data.ventas),
      Pedidos: data.pedidos,
      'Clientes registrados': data.clientes,
      'Productos activos': data.productos,
      'Pagos pendientes': data.pagos_pendientes,
      'Unidades vendidas': data.unidades
    };
    dashboard.innerHTML = Object.entries(stats)
      .map(([label, value]) => `<article class="stat"><span>${esc(label)}</span><strong>${esc(value)}</strong></article>`)
      .join('');

    const salesSummary = $('[data-sales-summary]');
    if (salesSummary) {
      salesSummary.textContent = `${data.ventas_confirmadas} pagos aprobados · ${data.unidades} unidades vendidas. Las ventas solo incluyen pagos aprobados de pedidos no cancelados.`;
    }
  } catch (error) {
    dashboard.innerHTML = `<p class="notice error">${esc(error.message)}</p>`;
  }
}

// Consulta de clientes disponible para el administrador.
async function loadClients() {
  try {
    const clients = await api('clientes.php');
    $('[data-client-table]').innerHTML = clients.map(client => `
      <tr>
        <td>${esc(client.nombre)}</td>
        <td>${esc(client.correo)}</td>
        <td>${esc(client.telefono || '—')}</td>
        <td>${esc(client.direccion || '—')}</td>
        <td>${esc(client.estado)}</td>
        <td>${client.cantidad_pedidos}</td>
        <td><div class="table-actions">
          <button class="button small-button" type="button" data-client-edit="${Number(client.id)}">Editar</button>
          ${client.estado === 'ACTIVO' ? `<button class="button button-light small-button" type="button" data-client-disable="${Number(client.id)}">Desactivar</button>` : ''}
        </div></td>
      </tr>`).join('');

    const table = $('[data-client-table]');
    table.querySelectorAll('[data-client-edit]').forEach(button => {
      button.addEventListener('click', () => {
        const client = clients.find(item => Number(item.id) === Number(button.dataset.clientEdit));
        const form = $('[data-client-form]');
        if (!client || !form) return;
        form.elements.id.value = client.id;
        for (const field of ['nombre', 'correo', 'telefono', 'direccion']) {
          form.elements[field].value = client[field] || '';
        }
        form.elements.password.value = '';
        form.elements.password.required = false;
        $('[data-client-form-title]').textContent = 'Editar cuenta de cliente';
        form.querySelector('[type="submit"]').textContent = 'Guardar cambios';
        form.scrollIntoView({ behavior: 'smooth' });
        form.elements.nombre.focus();
      });
    });

    table.querySelectorAll('[data-client-disable]').forEach(button => {
      button.addEventListener('click', async () => {
        if (!confirm('¿Desactivar esta cuenta? El cliente ya no podrá ingresar.')) return;
        try {
          await api(`usuarios.php?id=${button.dataset.clientDisable}`, { method: 'DELETE' });
          await loadClients();
          message($('.notice', $('[data-client-form]')), 'Cuenta de cliente desactivada.');
        } catch (error) {
          message($('.notice', $('[data-client-form]')), error.message, true);
        }
      });
    });
  } catch (error) {
    message($('.notice'), error.message, true);
  }
}

// Conecta crear/editar cliente con la API PHP de usuarios.
function bindClientForm() {
  const form = $('[data-client-form]');
  if (!form || form.dataset.bound === 'true') return;
  form.dataset.bound = 'true';

  form.addEventListener('reset', () => {
    setTimeout(() => {
      form.elements.id.value = '';
      form.elements.password.required = true;
      $('[data-client-form-title]').textContent = 'Crear cuenta de cliente';
      form.querySelector('[type="submit"]').textContent = 'Crear cliente';
    }, 0);
  });

  form.addEventListener('submit', async event => {
    event.preventDefault();
    const editing = Boolean(form.elements.id.value);
    const formData = new FormData(form);
    const body = Object.fromEntries(formData);
    if (editing) delete body.password;

    try {
      await api(editing ? `usuarios.php?id=${form.elements.id.value}` : 'usuarios.php', {
        method: editing ? 'PUT' : 'POST',
        body: JSON.stringify(body)
      });
      form.reset();
      await loadClients();
      message($('.notice', form), editing ? 'Cambios de cliente guardados.' : 'Cuenta de cliente creada.');
    } catch (error) {
      message($('.notice', form), error.message, true);
    }
  });
}
async function loadOrders(){const table=$('[data-order-table]'),productsBox=$('[data-order-products]');try{const orders=await api('pedidos.php');table.innerHTML=orders.map(o=>`<tr><td>#${o.id}</td><td>${esc(o.cliente||appUser?.nombre||'')}</td><td>${currency(o.total)}</td><td>${esc(o.fecha)}</td><td>${esc(o.estado)}</td><td>${appUser?.rol==='ADMIN'?`<select data-state="${o.id}">${['PENDIENTE','CONFIRMADO','EN_PREPARACION','ENVIADO','ENTREGADO','CANCELADO'].map(s=>`<option ${o.estado===s?'selected':''}>${s}</option>`).join('')}</select> <button class="button small-button" data-save-state="${o.id}">Guardar</button>`:`<button class="button small-button" data-detail="${o.id}">Ver detalle</button>`}</td></tr>`).join('');table.querySelectorAll('[data-save-state]').forEach(b=>b.onclick=async()=>{try{await api(`pedidos.php?id=${b.dataset.saveState}`,{method:'PUT',body:JSON.stringify({estado:$(`[data-state="${b.dataset.saveState}"]`).value})});loadOrders();}catch(e){message($('.notice'),e.message,true);}});table.querySelectorAll('[data-detail]').forEach(b=>b.onclick=async()=>{try{const o=await api(`pedidos.php?id=${b.dataset.detail}`);alert(o.detalle.map(d=>`${d.producto} × ${d.cantidad} — ${currency(d.subtotal)}`).join('\n'));}catch(e){message($('.notice'),e.message,true);}});
 if(appUser?.rol==='CLIENTE'){const products=await api('productos.php');productsBox.innerHTML=products.length?products.map(p=>`<label class="card order-product">${esc(p.nombre)}<span>${currency(p.precio)} · ${(p.disponible??Number(p.stock)>0)?'Disponible':'Agotado'}</span><input aria-label="Cantidad de ${esc(p.nombre)}" type="number" min="0" value="0" data-cart-qty="${p.id}" ${!(p.disponible??Number(p.stock)>0)?'disabled':''}></label>`).join(''):'<article class="card"><h2>Aún no hay productos</h2><p>Cuando el administrador publique productos, aparecerán aquí.</p></article>';const cartProducts=()=>{const quantities=new Map();for(const item of JSON.parse(localStorage.getItem('panelapp_cart')||'[]'))quantities.set(Number(item.producto_id),(quantities.get(Number(item.producto_id))||0)+Number(item.cantidad));productsBox.querySelectorAll('[data-cart-qty]').forEach(input=>quantities.set(Number(input.dataset.cartQty),(quantities.get(Number(input.dataset.cartQty))||0)+Number(input.value||0)));return [...quantities].filter(([,cantidad])=>cantidad>0).map(([producto_id,cantidad])=>({producto_id,cantidad}));};const drawSummary=()=>{const items=cartProducts(),lines=$('[data-cart-lines]'),totalBox=$('[data-cart-total]');if(!lines)return;lines.innerHTML=items.length?items.map(item=>{const product=products.find(p=>Number(p.id)===item.producto_id);return product?`<div class="cart-line"><span>${esc(product.nombre)} <small>× ${item.cantidad}</small></span><strong>${currency(Number(product.precio)*item.cantidad)}</strong></div>`:''}).join(''):'<p class="muted">Aún no agregas productos. Elige cantidades arriba o desde el catálogo.</p>';totalBox.textContent=currency(items.reduce((sum,item)=>sum+(Number(products.find(p=>Number(p.id)===item.producto_id)?.precio)||0)*item.cantidad,0));};productsBox.querySelectorAll('[data-cart-qty]').forEach(input=>input.addEventListener('input',drawSummary));drawSummary();$('[data-create-order]').hidden=false;$('[data-address-label]').hidden=false;$('[data-create-order]').onclick=async()=>{const items=cartProducts();if(!items.length){message($('.notice'),'Tu carrito está vacío. Selecciona al menos un producto para continuar.',true);return;}const direccion=$('[data-delivery-address]').value.trim();if(!direccion){message($('.notice'),'Escribe la dirección completa donde quieres recibir el pedido.',true);$('[data-delivery-address]').focus();return;}const resumen=items.map(item=>{const p=products.find(product=>Number(product.id)===item.producto_id);return `${item.cantidad} × ${p?.nombre||'Producto'}`;}).join('\n');if(!confirm(`Confirma tu pedido:\n\n${resumen}\n\nTotal: ${$('[data-cart-total]').textContent}\nEntrega: ${direccion}\n\n¿Deseas crear el pedido?`))return;try{const result=await api('pedidos.php',{method:'POST',body:JSON.stringify({items,direccion})});localStorage.removeItem('panelapp_cart');message($('.notice'),`¡Pedido confirmado! Tu número es #${result.id}. Puedes solicitar el registro del pago y consultar el seguimiento en el menú.`);productsBox.querySelectorAll('[data-cart-qty]').forEach(input=>input.value='0');drawSummary();loadOrders();}catch(e){message($('.notice'),e.message,true);}};}}catch(e){message($('.notice'),e.message,true);}}
async function loadPayments(){try{const rows=await api('pagos.php');$('[data-payment-table]').innerHTML=rows.map(p=>`<tr><td>${p.id}</td><td>#${p.pedido_id}</td><td>${esc(p.cliente||'—')}</td><td>${currency(p.monto)}</td><td>${esc(p.metodo)}</td><td>${esc(p.estado)}</td><td>${appUser?.rol==='ADMIN'?`<select data-payment-state="${p.id}">${['PENDIENTE','APROBADO','RECHAZADO','REEMBOLSADO'].map(s=>`<option ${p.estado===s?'selected':''}>${s}</option>`).join('')}</select><button class="button small-button" data-payment-save="${p.id}">Guardar</button>`:'—'}</td></tr>`).join('');$('[data-payment-table]').querySelectorAll('[data-payment-save]').forEach(b=>b.onclick=async()=>{try{await api('pagos.php',{method:'PUT',body:JSON.stringify({id:b.dataset.paymentSave,estado:$(`[data-payment-state="${b.dataset.paymentSave}"]`).value})});loadPayments();}catch(e){message($('.notice'),e.message,true);}});if(appUser?.rol==='CLIENTE'){const form=$('[data-payment-request]');form.hidden=false;const own=await api('pedidos.php');const eligible=own.filter(p=>p.estado!=='CANCELADO');form.elements.pedido_id.innerHTML=eligible.map(p=>`<option value="${p.id}">Pedido #${p.id} — ${currency(p.total)} (${esc(p.estado)})</option>`).join('');form.onsubmit=async e=>{e.preventDefault();try{await api('pagos.php',{method:'POST',body:JSON.stringify({pedido_id:Number(form.elements.pedido_id.value),metodo:form.elements.metodo.value})});message($('.notice',form),'Solicitud registrada; no se realizó ningún cobro.');loadPayments();}catch(err){message($('.notice',form),err.message,true);}};if(!eligible.length)message($('.notice',form),'Primero debes crear un pedido.',true);}}catch(e){message($('.notice'),e.message,true);}}
async function loadDeliveries(){try{const rows=await api('entregas.php'),tbody=$('[data-delivery-table]'),tracking=$('[data-tracking-cards]');if(tracking){const descriptions={PENDIENTE:'Estamos organizando tu pedido.',EN_RUTA:'Tu pedido salió a entrega.',ENTREGADA:'El pedido figura como entregado.',NOVEDAD:'El administrador registró una novedad.'};tracking.innerHTML=rows.length?rows.map(r=>`<article class="card tracking-card"><p class="eyebrow">Pedido #${Number(r.pedido_id)}</p><h2>${esc(r.estado.replaceAll('_',' '))}</h2><p>${esc(descriptions[r.estado]||'Estado actualizado por el administrador.')}</p><p><strong>Entrega:</strong> ${esc(r.direccion)}</p>${r.fecha_programada?`<p><strong>Fecha estimada:</strong> ${esc(r.fecha_programada)}</p>`:''}</article>`).join(''):'<article class="card"><h2>Aún no tienes entregas</h2><p>Cuando confirmes un pedido, aparecerá su seguimiento en esta sección.</p></article>';}
tbody.innerHTML=rows.map(r=>`<tr><td>${r.id}</td><td>#${r.pedido_id}</td><td>${esc(r.cliente||appUser?.nombre||'')}</td><td>${appUser?.rol==='ADMIN'?`<input aria-label="Dirección de entrega" data-delivery-address="${r.id}" value="${esc(r.direccion)}">`:esc(r.direccion)}</td><td>${appUser?.rol==='ADMIN'?`<input aria-label="Fecha programada" type="datetime-local" data-delivery-date="${r.id}" value="${r.fecha_programada?esc(r.fecha_programada.replace(' ','T').slice(0,16)):''}">`:esc(r.fecha_programada||'Pendiente')}</td><td>${appUser?.rol==='ADMIN'?`<select aria-label="Estado de entrega" data-delivery-state="${r.id}">${['PENDIENTE','EN_RUTA','ENTREGADA','NOVEDAD'].map(s=>`<option ${r.estado===s?'selected':''}>${s}</option>`).join('')}</select>`:esc(r.estado.replaceAll('_',' '))}</td><td>${appUser?.rol==='ADMIN'?`<button class="button small-button" data-delivery-save="${r.id}">Guardar cambios</button>`:'—'}</td></tr>`).join('');tbody.querySelectorAll('[data-delivery-save]').forEach(b=>b.onclick=async()=>{const id=b.dataset.deliverySave;try{await api('entregas.php',{method:'PUT',body:JSON.stringify({id:Number(id),estado:$(`[data-delivery-state="${id}"]`).value,direccion:$(`[data-delivery-address="${id}"]`).value,fecha_programada:$(`[data-delivery-date="${id}"]`).value.replace('T',' ')||null})});message($('.notice'),'Estado de entrega actualizado correctamente.');loadDeliveries();}catch(e){message($('.notice'),e.message,true);}});}catch(e){message($('.notice'),e.message,true);}}
// ==================== 6. Contacto y arranque de la aplicación ====================
function bindContact(){const form=$('[data-contact-form]');form.onsubmit=e=>{e.preventDefault();if(!form.reportValidity())return;const d=Object.fromEntries(new FormData(form)),notice=$('.notice',form),link=document.createElement('a');link.href=`mailto:panelapp2016@gmail.com?subject=${encodeURIComponent('Contacto web - '+d.nombre)}&body=${encodeURIComponent(d.mensaje+'\n\nCorreo: '+d.correo)}`;link.textContent='Abrir borrador y enviar correo';link.target='_blank';link.rel='noopener noreferrer';notice.replaceChildren(document.createTextNode('¡Gracias por tu mensaje! Nos comunicaremos pronto contigo. Para que podamos recibirlo, abre el borrador de correo y presiona “Enviar”. '),link);notice.classList.remove('error');notice.classList.add('success');};}
document.addEventListener('DOMContentLoaded',init);
