<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/category', function () {
    $cat = Cache::remember('categories', 60, function () {
        return DB::table('Category')
            ->select('id', 'name')
            ->get();
    });
    return response()->json($cat);
});

Route::put('/category/{id}', function (Request $request, $id) {
    $validatedData = $request->validate([
        'name' => 'required|max:255',
    ]);
    
    DB::table('Category')
    ->where('id', $id)
        ->update(['name' => $validatedData['name']]);

        Cache::forget('categories');

        return response()->json(['message' => 'Category updated successfully']);
    });

    Route::post('/category', function (Request $request) {
    $validatedData = $request->validate([
        'name' => 'required|max:255',
    ]);
    
    $id = DB::table('Category')->insertGetId([
        'name' => $validatedData['name'],
    ]);

    Cache::forget('categories');

    return response()->json(['id' => $id, 'name' => $validatedData['name']]);
});

Route::delete('/category/{id}', function ($id) {
    DB::table('Category')->where('id', $id)->delete();
    
    Cache::forget('categories');
    
    return response()->json(['message' => 'Category deleted successfully']);
});

Route::get('/dish/{id}', function ($id) {
    $dish = Cache::remember('dishes_' . $id, 60, function () use ($id) {
        return DB::table('Dishes')
            ->select('id', 'name', 'price', 'C_id')
            ->where('C_id', $id)
            ->get();
    });

    return response()->json($dish);
});

Route::get('/dishes', function () {
    $dishes = DB::table('Dishes')
        ->select('id', 'name', 'price', 'C_id')
        ->get();

    return response()->json($dishes);
});

Route::post('/dish', function (Request $request) {
    $validatedData = $request->validate([
        'name' => 'required|max:255',
        'price' => 'required|numeric',
        'C_id' => 'required|integer',
    ]);

    $id = DB::table('Dishes')->insertGetId([
        'name' => $validatedData['name'],
        'price' => $validatedData['price'],
        'C_id' => $validatedData['C_id'],
    ]);

    // Clear the dishes cache
    Cache::forget('dishes_' . $validatedData['C_id']);

    return response()->json(['id' => $id, 'name' => $validatedData['name'], 'price' => $validatedData['price'], 'C_id' => $validatedData['C_id']]);
});

Route::put('/dish/{id}', function (Request $request, $id) {
    $validatedData = $request->validate([
        'name' => 'required|max:255',
        'price' => 'required|numeric',
        'C_id' => 'required|integer',
    ]);

    DB::table('Dishes')
        ->where('id', $id)
        ->update([
            'name' => $validatedData['name'],
            'price' => $validatedData['price'],
            'C_id' => $validatedData['C_id'],
        ]);

    // Clear the dishes cache
    Cache::forget('dishes_' . $validatedData['C_id']);

    return response()->json(['message' => 'Dish updated successfully']);
});

Route::delete('/dish/{id}', function ($id) {
    $dish = DB::table('Dishes')->where('id', $id)->first();
    DB::table('Dishes')->where('id', $id)->delete();

    // Clear the dishes cache
    Cache::forget('dishes_' . $dish->C_id);

    return response()->json(['message' => 'Dish deleted successfully']);
});

Route::get('/table/{id}', function ($id) {
    $orders = DB::table('Orders')
        ->join('Order_details', 'Orders.id', '=', 'Order_details.order_id')
        ->join('Dishes', 'Order_details.dish_id', '=', 'Dishes.id')
        ->where('Orders.paid', false)
        ->where('Orders.table_id', $id)
        ->select('Orders.id as order_id', 'Dishes.name as dish_name', 'Order_details.status as dish_status', 'Order_details.quantity as dish_quantity', 'Dishes.price as dish_price', 'Order_details.notes as dish_note')
        ->get();

    return response()->json($orders);
});

Route::get('/tables', function () {
    $tables = DB::table('Tables')
        ->select('num', 'status')
        ->get();

    return response()->json($tables);
});

Route::post('/table', function (Request $request) {
    $validatedData = $request->validate([
        'num' => 'required|integer',
    ]);

    DB::table('Tables')->insert([
        'num' => $validatedData['num'],
    ]);

    return response()->json(['message' => 'Table created successfully']);
});

Route::put('/table/{id}', function (Request $request, $id) {
    $validatedData = $request->validate([
        'num' => 'required|integer',
        'status' => 'required|string',
    ]);

    DB::table('Tables')
        ->where('num', $id)
        ->update([
            'num' => $validatedData['num'],
            'status' => $validatedData['status'],
        ]);

    return response()->json(['message' => 'Table updated successfully']);
});

Route::delete('/table/{id}', function ($id) {
    DB::table('Tables')->where('num', $id)->delete();

    return response()->json(['message' => 'Table deleted successfully']);
});

Route::post('/order', function (Request $request) {
    $validatedData = $request->validate([
        'table_id' => 'required|integer',
        'dishes' => 'required|array',
        'dishes.*.id' => 'required|integer',
        'dishes.*.quantity' => 'required|integer',
        'dishes.*.notes' => 'nullable|string',
    ]);

    // Check if there's an existing unpaid order for this table
    $orderId = DB::table('Orders')
        ->where('table_id', $validatedData['table_id'])
        ->where('paid', false)
        ->value('id');

    // If there's no existing order, create a new one
    if (!$orderId) {
        $orderId = DB::table('Orders')->insertGetId([
            'table_id' => $validatedData['table_id'],
            'paid' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('Tables')
            ->where('num', $validatedData['table_id'])
            ->update(['status' => 'occupied']);
    } else {
        DB::table('Orders')
            ->where('id', $orderId)
            ->update(['updated_at' => now()]);
    }

    // Add the new dishes to the order
    foreach ($validatedData['dishes'] as $dish) {
        DB::table('Order_details')->insert([
            'order_id' => $orderId,
            'dish_id' => $dish['id'],
            'quantity' => $dish['quantity'],
            'notes' => $dish['notes'] ?? null,
        ]);
    }

    return response()->json(['message' => 'Order updated successfully', 'order_id' => $orderId]);
});

Route::get('/kitchen', function () {
    $dishes = DB::table('Order_details')
        ->join('Dishes', 'Order_details.dish_id', '=', 'Dishes.id')
        ->join('Orders', 'Order_details.order_id', '=', 'Orders.id')
        ->join('Tables', 'Orders.table_id', '=', 'Tables.num')
        ->whereIn('Order_details.status', ['not started', 'pending'])
        ->select('Order_details.id as order_detail_id', 'Dishes.name as dish_name', 'Order_details.status as dish_status', 'Order_details.quantity as dish_quantity', 'Order_details.notes as dish_notes', 'Tables.num as table_number')
        ->get();

    return response()->json($dishes);
});

Route::put('/kitchen/order-detail/{orderDetailId}', function (Request $request, $orderDetailId) {
    $validatedData = $request->validate([
        'status' => 'required|in:not started,pending,serving,canceled',
    ]);

    DB::table('Order_details')
        ->where('id', $orderDetailId)
        ->update(['status' => $validatedData['status']]);

    return response()->json(['message' => 'Dish status updated successfully']);
});

Route::get('/server', function () {
    $orders = DB::table('Orders')
        ->join('Order_details', 'Orders.id', '=', 'Order_details.order_id')
        ->join('Dishes', 'Order_details.dish_id', '=', 'Dishes.id')
        ->where('Order_details.status', 'serving')
        ->select('Orders.id as order_id', 'Dishes.name as dish_name', 'Order_details.status as dish_status', 'Order_details.quantity as dish_quantity', 'Dishes.price as dish_price', 'Order_details.notes as dish_note')
        ->get();

    return response()->json($orders);
});

Route::put('/server/order-detail/{orderDetailId}', function (Request $request, $orderDetailId) {
    $validatedData = $request->validate([
        'status' => 'required|in:done,canceled',
    ]);

    DB::table('Order_details')
        ->where('id', $orderDetailId)
        ->update(['status' => $validatedData['status']]);

    return response()->json(['message' => 'Order detail status updated successfully']);
});

Route::put('/cashier/order/{orderId}', function (Request $request, $orderId) {
    // Update the order status to paid
    DB::table('Orders')
        ->where('id', $orderId)
        ->update(['paid' => true]);

    // Get the table id associated with the order
    $tableId = DB::table('Orders')
        ->where('id', $orderId)
        ->value('table_id');

    // Set the table status back to available
    DB::table('Tables')
        ->where('num', $tableId)
        ->update(['status' => 'available']);

    return response()->json(['message' => 'Order paid and table status updated successfully']);
});