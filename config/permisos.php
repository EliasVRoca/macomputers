<?php

// Fuente única de módulos del sistema. Cada clave es un permiso que se guarda en roles.permisos.
// El navleft y el middleware "permiso:clave" se construyen a partir de esta lista.
return [

    'grupos' => [
        'Administración' => ['usuarios', 'roles', 'sucursales'],
        'Catálogo' => ['categorias', 'productos', 'proveedores'],
        'Inventario' => ['disponibilidad', 'inventario', 'traspasos', 'compras'],
        'Ventas' => ['ventas', 'apartados', 'encargos', 'cambios', 'clientes'],
        'Servicio técnico' => ['servicios'],
    ],

    'modulos' => [
        'usuarios' => ['label' => 'Usuarios', 'route' => 'usuarios.index'],
        'roles' => ['label' => 'Roles', 'route' => 'roles.index'],
        'sucursales' => ['label' => 'Sucursales', 'route' => 'sucursales.index'],
        'categorias' => ['label' => 'Categorías', 'route' => 'categorias.index'],
        'productos' => ['label' => 'Productos', 'route' => 'productos.index'],
        'proveedores' => ['label' => 'Proveedores', 'route' => 'proveedores.index'],
        'disponibilidad' => ['label' => 'Disponibilidad', 'route' => 'disponibilidad.index'],
        'inventario' => ['label' => 'Inventario', 'route' => 'inventario.index'],
        'traspasos' => ['label' => 'Traspasos', 'route' => 'traspasos.index'],
        'compras' => ['label' => 'Compras', 'route' => 'compras.index'],
        'ventas' => ['label' => 'Ventas', 'route' => 'ventas.index'],
        'apartados' => ['label' => 'Apartados', 'route' => 'apartados.index'],
        'encargos' => ['label' => 'Encargos', 'route' => 'encargos.index'],
        'cambios' => ['label' => 'Cambios', 'route' => 'cambios.index'],
        'clientes' => ['label' => 'Clientes', 'route' => 'clientes.index'],
        'servicios' => ['label' => 'Servicios técnicos', 'route' => 'servicios.index'],
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
            'permisos' => ['disponibilidad', 'inventario', 'traspasos', 'ventas', 'apartados', 'encargos', 'cambios', 'clientes'],
            'es_sistema' => false,
        ],
        'Técnico' => [
            'descripcion' => 'Registra y actualiza el servicio técnico.',
            'permisos' => ['disponibilidad', 'servicios'],
            'es_sistema' => false,
        ],
    ],
];
