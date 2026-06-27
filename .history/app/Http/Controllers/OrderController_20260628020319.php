<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{

    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|string|in:pending,confirmed,cancelled',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $orders = Order::with('products')
            ->where('user_id', auth('api')->id())
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->paginate($request->per_page ?? 10);

        return response()->json($orders, 200);
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'products'                 => 'required|array|min:1',
            'products.*.product_id'   => 'required|exists:products,id',
            'products.*.quantity'     => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            $totalAmount = 0;
            $orderItems  = [];

            foreach ($request->products as $item) {
                $product = Product::find($item['product_id']);

                if ($product->stock < $item['quantity']) {
                    DB::rollBack();
                    return response()->json([
                        'error' => "Not enough stock for ({$product->name}). Available: {$product->stock}"
                    ], 400);
                }

                $totalAmount += $product->price * $item['quantity'];

                $orderItems[$product->id] = [
                    'quantity' => $item['quantity'],
                    'price'    => $product->price,
                ];

                $product->decrement('stock', $item['quantity']);
            }

            $order = Order::create([
                'user_id'      => auth('api')->id(),
                'total_amount' => $totalAmount,
                'status'       => 'pending',
            ]);

            $order->products()->attach($orderItems);

            DB::commit();

            return response()->json([
                'message' => 'Order created successfully',
                'order'   => $order->load('products'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error'   => 'An error occurred while creating the order',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        if ($order->user_id !== auth('api')->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:pending,confirmed,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $order->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Order updated successfully',
            'order'   => $order->fresh('products'),
        ], 200);
    }

    public function show($id)
    {
        $order = Order::with('products')->where('id', $id)->first();

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        if ($order->user_id !== auth('api')->id()) {
            return response()->json(['error' => 'You are not authorized to view this order'], 403);
        }

        return response()->json($order, 200);
    }


    public function destroy($id)
    {
        $order = Order::with('products')->find($id);

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        if ($order->user_id !== auth('api')->id()) {
            return response()->json(['error' => 'You are not authorized to delete this order'], 403);
        }

        if ($order->hasPayments()) {
            return response()->json([
                'error' => 'Cannot delete order because it has associated payments.'
            ], 422);
        }

        DB::beginTransaction();

        try {
            foreach ($order->products as $product) {
                $quantityToRestore = $product->pivot->quantity;
                $product->increment('stock', $quantityToRestore);
            }

            $order->delete();

            DB::commit();

            return response()->json(['message' => 'Order deleted successfully and stock restored'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'An error occurred while deleting the order', 'details' => $e->getMessage()], 500);
        }
    }
}
