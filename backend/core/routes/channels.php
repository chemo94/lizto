<?php

use App\Models\Ride;
use App\Models\Favor;
use App\Models\DeliveryOrder;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('rider-driver.{driverId}', function ($user, $driverId) {
    if ($user instanceof \App\Models\Driver) {
        return (int) $user->id === (int) $driverId;
    }
    if ($user instanceof \App\Models\Admin) {
        return true;
    }
    return false;
});

Broadcast::channel('rider-user.{userId}', function ($user, $userId) {
    if ($user instanceof \App\Models\User) {
        return (int) $user->id === (int) $userId;
    }
    if ($user instanceof \App\Models\Admin) {
        return true;
    }
    return false;
});

Broadcast::channel('ride-location.{rideId}', function ($user, $rideId) {
    $ride = Ride::find($rideId);
    if (!$ride) return false;
    return (int) $user->id === (int) $ride->user_id || (int) $user->id === (int) $ride->driver_id;
});

Broadcast::channel('delivery-order.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('favor.{favorId}', function ($user, $favorId) {
    $favor = Favor::find($favorId);
    if (!$favor) return false;
    return (int) $user->id === (int) $favor->user_id
        || ($user instanceof \App\Models\Driver && (int) $favor->courier_id === (int) $user->id);
});

Broadcast::channel('favor-customer.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('courier.{courierId}', function ($user, $courierId) {
    if (!$user) return false;
    return ($user instanceof \App\Models\Driver && (int) $user->id === (int) $courierId)
        || $user instanceof \App\Models\Admin;
});

Broadcast::channel('admin-notifications', function ($user) {
    return $user && ($user instanceof \App\Models\Admin || $user instanceof \App\Models\User);
});

Broadcast::channel('seller.{sellerId}', function ($user, $sellerId) {
    return (int) $user->id === (int) $sellerId || $user instanceof \App\Models\Admin;
});

Broadcast::channel('nearby-couriers', function ($user) {
    return $user !== null;
});

Broadcast::channel('nearby-drivers', function ($user) {
    return $user !== null;
});

Broadcast::channel('tracking.{orderId}', function ($user, $orderId) {
    $order = DeliveryOrder::find($orderId);
    if (!$order) return false;
    return (int) $user->id === (int) $order->user_id
        || ($user instanceof \App\Models\Driver && (int) $order->driver_id === (int) $user->id);
});

Broadcast::channel('delivery-order.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('job.{jobId}', function ($user, $jobId) {
    $order = DeliveryOrder::find($jobId);
    if ($order) {
        return (int) $user->id === (int) $order->user_id
            || ($user instanceof \App\Models\Driver && (int) $order->driver_id === (int) $user->id);
    }
    $favor = Favor::find($jobId);
    if ($favor) {
        return (int) $user->id === (int) $favor->user_id
            || ($user instanceof \App\Models\Driver && (int) $favor->courier_id === (int) $user->id);
    }
    return false;
});
