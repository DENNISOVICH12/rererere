<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal de notificaciones del mesero.
// Autoriza: mesero, admin, cocinero y barra — cualquier rol de staff.
// El restaurantId se valida solo si el usuario tiene restaurant_id asignado.
Broadcast::channel('restaurant.{restaurantId}.waiters', function ($user, $restaurantId) {
    $rolesPermitidos = ['mesero', 'admin', 'cocinero', 'barra'];
    $rol = strtolower((string) ($user->rol ?? ''));

    if (!in_array($rol, $rolesPermitidos, true)) {
        return false;
    }

    // Si el usuario tiene restaurant_id, verificar que coincida
    $userRestaurantId = (int) ($user->restaurant_id ?? 0);
    if ($userRestaurantId > 0 && $userRestaurantId !== (int) $restaurantId) {
        return false;
    }

    return true;
});