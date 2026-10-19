<?php

// Fuente única de módulos del sistema. Cada clave es un permiso que se guarda en roles.permisos.
// El riel, el panel, la paleta de comandos y el middleware "permiso:clave" salen de aquí.
return [

    // Grupos del riel de navegación, con su icono.
    'grupos' => [
        'Administración' => [
            'icon' => 'shield',
            'modulos' => ['usuarios', 'roles', 'sucursales'],
        ],
        'Catálogo' => [
            'icon' => 'grid',
            'modulos' => ['categorias', 'productos', 'proveedores'],
        ],
        'Inventario' => [
            'icon' => 'warehouse',
            'modulos' => ['buscador', 'disponibilidad', 'inventario', 'traspasos', 'compras'],
        ],
        'Ventas' => [
            'icon' => 'shopping-cart',
            'modulos' => ['ventas', 'apartados', 'encargos', 'cambios', 'clientes'],
        ],
        'Servicio técnico' => [
            'icon' => 'wrench',
            'modulos' => ['servicios'],
        ],
    ],

    'modulos' => [
        'usuarios' => ['label' => 'Usuarios', 'route' => 'usuarios.index', 'icon' => 'users'],
        'roles' => ['label' => 'Roles', 'route' => 'roles.index', 'icon' => 'key-round'],
        'sucursales' => ['label' => 'Sucursales', 'route' => 'sucursales.index', 'icon' => 'building'],
        'categorias' => ['label' => 'Categorías', 'route' => 'categorias.index', 'icon' => 'tag'],
        'productos' => ['label' => 'Productos', 'route' => 'productos.index', 'icon' => 'package'],
        'proveedores' => ['label' => 'Proveedores', 'route' => 'proveedores.index', 'icon' => 'truck'],
        'disponibilidad' => ['label' => 'Disponibilidad', 'route' => 'disponibilidad.index', 'icon' => 'map-pin'],
        'buscador' => ['label' => 'Buscador', 'route' => 'buscador.index', 'icon' => 'search'],
        'inventario' => ['label' => 'Inventario', 'route' => 'inventario.index', 'icon' => 'warehouse'],
        'traspasos' => ['label' => 'Traspasos', 'route' => 'traspasos.index', 'icon' => 'arrow-left-right'],
        'compras' => ['label' => 'Compras', 'route' => 'compras.index', 'icon' => 'clipboard-list'],
        'ventas' => ['label' => 'Ventas', 'route' => 'ventas.index', 'icon' => 'receipt'],
        'apartados' => ['label' => 'Apartados', 'route' => 'apartados.index', 'icon' => 'bookmark'],
        'encargos' => ['label' => 'Encargos', 'route' => 'encargos.index', 'icon' => 'inbox'],
        'cambios' => ['label' => 'Cambios', 'route' => 'cambios.index', 'icon' => 'repeat'],
        'clientes' => ['label' => 'Clientes', 'route' => 'clientes.index', 'icon' => 'user-round'],
        'servicios' => ['label' => 'Servicios técnicos', 'route' => 'servicios.index', 'icon' => 'wrench'],
    ],

    // Acciones rápidas de la paleta de comandos (Ctrl/Cmd+K).
    'acciones' => [
        ['label' => 'Nueva venta', 'route' => 'ventas.create', 'icon' => 'receipt', 'permiso' => 'ventas'],
        ['label' => 'Nuevo apartado', 'route' => 'apartados.create', 'icon' => 'bookmark', 'permiso' => 'apartados'],
        ['label' => 'Nuevo encargo', 'route' => 'encargos.create', 'icon' => 'inbox', 'permiso' => 'encargos'],
        ['label' => 'Nuevo cambio', 'route' => 'cambios.create', 'icon' => 'repeat', 'permiso' => 'cambios'],
        ['label' => 'Nuevo cliente', 'route' => 'clientes.create', 'icon' => 'user-round', 'permiso' => 'clientes'],
        ['label' => 'Registrar traspaso', 'route' => 'traspasos.create', 'icon' => 'arrow-left-right', 'permiso' => 'traspasos'],
        ['label' => 'Nueva compra', 'route' => 'compras.create', 'icon' => 'clipboard-list', 'permiso' => 'compras'],
        ['label' => 'Nuevo servicio', 'route' => 'servicios.create', 'icon' => 'wrench', 'permiso' => 'servicios'],
        ['label' => 'Nuevo producto', 'route' => 'productos.create', 'icon' => 'package', 'permiso' => 'productos'],
        ['label' => 'Nuevo proveedor', 'route' => 'proveedores.create', 'icon' => 'truck', 'permiso' => 'proveedores'],
        ['label' => 'Nuevo usuario', 'route' => 'usuarios.create', 'icon' => 'users', 'permiso' => 'usuarios'],
        ['label' => 'Consultar disponibilidad', 'route' => 'disponibilidad.index', 'icon' => 'map-pin', 'permiso' => 'disponibilidad'],
        ['label' => 'Buscar producto', 'route' => 'buscador.index', 'icon' => 'search', 'permiso' => 'buscador'],
    ],

    // Roles iniciales. El rol "Gerente" es de sistema: siempre tiene todos los módulos.
    'roles' => [
        'Gerente' => [
            'descripcion' => 'Administra ambas sucursales, usuarios, roles y catálogo (RF2 a RF6, RF10 y RF11).',
            'permisos' => ['*'],
            'es_sistema' => true,
        ],
        'Vendedor' => [
            'descripcion' => 'Atiende clientes, ventas, apartados, encargos, cambios e inventario de su sucursal.',
            'permisos' => ['buscador', 'disponibilidad', 'inventario', 'traspasos', 'ventas', 'apartados', 'encargos', 'cambios', 'clientes'],
            'es_sistema' => false,
        ],
        'Técnico' => [
            'descripcion' => 'Registra y actualiza el servicio técnico.',
            'permisos' => ['buscador', 'disponibilidad', 'servicios'],
            'es_sistema' => false,
        ],
    ],
];
